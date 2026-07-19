<?php
class BranchModel extends Model
{
    /**
     * List all outlets with summary stats.
     */
    public function list(): array
    {
        return $this->all("
            SELECT o.*, o.outlet_code AS code,
                (SELECT COUNT(*) FROM users u WHERE u.outlet_id = o.id AND u.is_active = 1) AS user_count,
                (SELECT COALESCE(SUM(ord.grand_total),0) FROM orders ord WHERE ord.outlet_id = o.id AND ord.business_date = CURDATE() AND ord.payment_status = 'paid') AS today_revenue,
                (SELECT COUNT(*) FROM orders ord WHERE ord.outlet_id = o.id AND ord.business_date = CURDATE() AND ord.payment_status = 'paid') AS today_trx,
                (SELECT ds.status FROM daily_store_sessions ds WHERE ds.outlet_id = o.id AND ds.business_date = CURDATE() ORDER BY ds.id DESC LIMIT 1) AS store_status
            FROM outlets o
            ORDER BY o.is_hq DESC, o.is_active DESC, o.name ASC
        ");
    }

    /**
     * Find single outlet by ID.
     */
    public function find(int $id): ?array
    {
        return $this->one("SELECT * FROM outlets WHERE id = ?", [$id]);
    }

    /**
     * Find outlet by slug.
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->one("SELECT * FROM outlets WHERE slug = ? LIMIT 1", [$slug]);
    }

    /**
     * Create or update an outlet.
     */
    public function store(array $data): int
    {
        $id           = (int)($data['id'] ?? 0);
        $name         = trim($data['name'] ?? '');
        $slug         = trim($data['slug'] ?? '');
        $code         = strtoupper(trim($data['outlet_code'] ?? $data['code'] ?? ''));
        $address      = trim($data['address'] ?? '');
        $phone        = trim($data['phone'] ?? '');
        $closingHour  = trim($data['closing_hour'] ?? '21:00:00');
        $isHQ         = (int)($data['is_hq'] ?? 0);
        $isActive     = (int)($data['is_active'] ?? 1);
        $type         = trim($data['type'] ?? 'owned');

        if ($name === '') {
            throw new RuntimeException('Nama outlet wajib diisi.');
        }

        // Outlet code is required (NOT NULL + UNIQUE in database)
        if ($code === '') {
            throw new RuntimeException('Kode outlet wajib diisi.');
        }

        // Validate type
        $validTypes = ['owned', 'partnership', 'franchise'];
        if (!in_array($type, $validTypes, true)) {
            $type = 'owned';
        }

        // Validate name uniqueness
        $existingName = $this->one("SELECT id FROM outlets WHERE LOWER(name) = LOWER(?) AND id != ? LIMIT 1", [$name, $id]);
        if ($existingName) {
            throw new RuntimeException("Nama outlet \"{$name}\" sudah digunakan.");
        }

        // Validate outlet code uniqueness
        $existingCode = $this->one("SELECT id FROM outlets WHERE outlet_code = ? AND id != ? LIMIT 1", [$code, $id]);
        if ($existingCode) {
            throw new RuntimeException("Kode outlet \"{$code}\" sudah digunakan oleh outlet lain.");
        }

        // Validate slug uniqueness
        if ($slug !== '') {
            // Slug must be URL-safe
            if (!preg_match('/^[a-z0-9][a-z0-9\-]*$/', $slug)) {
                throw new RuntimeException('Slug hanya boleh huruf kecil, angka, dan strip. Contoh: kb, outlet-2.');
            }
            $existing = $this->one("SELECT id FROM outlets WHERE slug = ? AND id != ? LIMIT 1", [$slug, $id]);
            if ($existing) {
                throw new RuntimeException("Slug \"{$slug}\" sudah digunakan oleh outlet lain.");
            }
        } else {
            // Empty slug = HQ root
            $existing = $this->one("SELECT id FROM outlets WHERE (slug = '' OR slug IS NULL) AND id != ? LIMIT 1", [$id]);
            if ($existing && !$isHQ) {
                throw new RuntimeException("Hanya outlet pusat (HQ) yang boleh tanpa slug.");
            }
        }

        // Validate closing hour format
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $closingHour)) {
            $closingHour = '21:00:00';
        }
        if (strlen($closingHour) === 5) {
            $closingHour .= ':00';
        }

        if ($id > 0) {
            // Update (company_id not changed on update)
            $this->execSql("UPDATE outlets SET name=?, slug=?, outlet_code=?, type=?, address=?, phone=?, closing_hour=?, is_hq=?, is_active=?, updated_at=NOW() WHERE id=?",
                [$name, $slug ?: null, $code, $type, $address, $phone, $closingHour, $isHQ, $isActive, $id]);
            return $id;
        }

        // Get company_id from existing outlets (all outlets belong to the same company)
        $companyId = (int)($data['company_id'] ?? 0);
        if ($companyId <= 0) {
            $existing = $this->one("SELECT company_id FROM outlets ORDER BY id ASC LIMIT 1");
            $companyId = $existing ? (int)$existing['company_id'] : 1;
        }

        // Insert with company_id (required NOT NULL FK to companies table)
        $this->execSql(
            "INSERT INTO outlets (company_id, name, slug, outlet_code, type, address, phone, closing_hour, is_hq, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
            [$companyId, $name, $slug ?: null, $code, $type, $address, $phone, $closingHour, $isHQ, $isActive]
        );
        $newBranchId = (int)Database::connection()->lastInsertId();

        if ($newBranchId > 0 && empty($isHQ)) {
            $this->cloneProductsToBranch($newBranchId);
        }

        return $newBranchId;
    }

    private function cloneProductsToBranch(int $branchId): void
    {
        // 1. Clone Categories
        $masterCategories = $this->all("SELECT * FROM product_categories WHERE outlet_id = 1");
        $catMap = [];
        foreach ($masterCategories as $cat) {
            $slug = $cat['slug'] . '-b' . $branchId;
            $this->execSql("INSERT INTO product_categories (outlet_id, name, slug, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                [$branchId, $cat['name'], $slug, $cat['sort_order'], $cat['is_active']]);
            $catMap[$cat['id']] = (int)Database::connection()->lastInsertId();
        }

        // 2. Clone Products
        $masterProducts = $this->all("SELECT * FROM products WHERE outlet_id = 1");
        $prodMap = [];
        foreach ($masterProducts as $p) {
            $newCatId = $catMap[$p['category_id']] ?? $p['category_id'];
            $this->execSql("INSERT INTO products (category_id, outlet_id, sku, name, description, image, product_type, unit_name, base_hpp, base_price, margin_amount, margin_percent, lifetime_qty_sold, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [$newCatId, $branchId, $p['sku'], $p['name'], $p['description'], $p['image'], $p['product_type'], $p['unit_name'], $p['base_hpp'], $p['base_price'], $p['margin_amount'], $p['margin_percent'], 0, $p['is_active']]);
            $prodMap[$p['id']] = (int)Database::connection()->lastInsertId();
        }

        // 3. Clone Variants
        $masterVariants = $this->all("SELECT pv.* FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE p.outlet_id = 1");
        foreach ($masterVariants as $v) {
            $newProdId = $prodMap[$v['product_id']] ?? $v['product_id'];
            $this->execSql("INSERT INTO product_variants (product_id, outlet_id, sku, variant_name, image, hpp, selling_price, margin_amount, margin_percent, is_default, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [$newProdId, $branchId, $v['sku'], $v['variant_name'], $v['image'], $v['hpp'], $v['selling_price'], $v['margin_amount'], $v['margin_percent'], $v['is_default'], $v['is_active']]);
        }
    }

    /**
     * Delete an outlet (soft-delete: deactivate + clear slug).
     * Cannot delete HQ or outlets with active users/transactions.
     */
    public function delete(int $id): void
    {
        $outlet = $this->find($id);
        if (!$outlet) {
            throw new RuntimeException('Outlet tidak ditemukan.');
        }
        if (!empty($outlet['is_hq'])) {
            throw new RuntimeException('Outlet pusat (HQ) tidak bisa dihapus.');
        }

        // Check for active users
        $activeUsers = $this->one("SELECT COUNT(*) AS cnt FROM users WHERE outlet_id = ? AND is_active = 1", [$id]);
        if ($activeUsers && (int)$activeUsers['cnt'] > 0) {
            throw new RuntimeException('Outlet masih memiliki ' . $activeUsers['cnt'] . ' user aktif. Nonaktifkan atau pindahkan user terlebih dahulu.');
        }

        // Soft-delete: deactivate and clear slug so it can be reused
        $this->execSql("UPDATE outlets SET is_active = 0, slug = NULL WHERE id = ?", [$id]);
    }

    /**
     * Get list of active outlets (for dropdown).
     */
    public function activeOutlets(): array
    {
        return $this->all("SELECT id, name, slug, outlet_code FROM outlets WHERE is_active = 1 ORDER BY is_hq DESC, name ASC");
    }

    /**
     * Toggle outlet active status.
     */
    public function toggleActive(int $id): void
    {
        $outlet = $this->find($id);
        if (!$outlet) throw new RuntimeException('Outlet tidak ditemukan.');
        if (!empty($outlet['is_hq'])) throw new RuntimeException('Outlet pusat (HQ) tidak bisa dinonaktifkan.');
        $newStatus = $outlet['is_active'] ? 0 : 1;
        $this->execSql("UPDATE outlets SET is_active = ? WHERE id = ?", [$newStatus, $id]);
    }
}
