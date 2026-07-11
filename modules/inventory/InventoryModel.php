<?php
class InventoryModel extends Model
{
    public function categories(): array
    {
        return $this->all("SELECT rmc.*, COUNT(rm.id) AS material_count 
            FROM raw_material_categories rmc 
            LEFT JOIN raw_materials rm ON rm.category_id = rmc.id AND rm.is_active = 1
            GROUP BY rmc.id
            ORDER BY rmc.sort_order, rmc.name");
    }

    public function units(): array
    {
        return $this->all("SELECT * FROM units ORDER BY name");
    }

    public function list(string $search = '', int $categoryId = 0): array
    {
        $where = 'WHERE rm.is_active=1';
        $params = [];
        if ($categoryId > 0) {
            $where .= ' AND rm.category_id = ?';
            $params[] = $categoryId;
        }
        if ($search) {
            $where .= ' AND (rm.name LIKE ? OR rm.sku LIKE ?)';
            $s = '%' . $search . '%';
            $params[] = $s;
            $params[] = $s;
        }
        return $this->all("
            SELECT rm.*, rmc.name category_name, u.symbol unit_symbol
            FROM raw_materials rm
            JOIN raw_material_categories rmc ON rmc.id = rm.category_id
            JOIN units u ON u.id = rm.unit_id
            $where
            ORDER BY rmc.sort_order, rmc.name, rm.name
        ", $params);
    }

    public function lowStock(): array
    {
        return $this->all("
            SELECT rm.*, rmc.name category_name, u.symbol unit_symbol
            FROM raw_materials rm
            JOIN raw_material_categories rmc ON rmc.id = rm.category_id
            JOIN units u ON u.id = rm.unit_id
            WHERE rm.is_active = 1 AND rm.stock_qty <= rm.min_stock_qty
            ORDER BY (rm.stock_qty - rm.min_stock_qty) ASC
            LIMIT 100
        ");
    }

    /**
     * Tambah kategori bahan baku baru (satu-satunya input yang diizinkan di Gudang Bahan).
     */
    public function createCategory(string $name, int $sortOrder = 0): int
    {
        $this->execSql(
            "INSERT INTO raw_material_categories (name, sort_order) VALUES (?, ?)",
            [$name, $sortOrder]
        );
        return (int)$this->db->lastInsertId();
    }

    public function createRawMaterial(array $data): int
    {
        $this->execSql(
            "INSERT INTO raw_materials (category_id, unit_id, name, sku, min_stock_qty, is_active, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())",
            [
                $data['category_id'] ?: null,
                $data['unit_id'] ?: null,
                trim($data['name'] ?? ''),
                trim($data['sku'] ?? ''),
                (float)($data['min_stock_qty'] ?? 0)
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function updateRawMaterial(int $id, array $data): void
    {
        $this->execSql(
            "UPDATE raw_materials 
             SET category_id=?, unit_id=?, name=?, sku=?, min_stock_qty=?, updated_at=NOW() 
             WHERE id=?",
            [
                $data['category_id'] ?: null,
                $data['unit_id'] ?: null,
                trim($data['name'] ?? ''),
                trim($data['sku'] ?? ''),
                (float)($data['min_stock_qty'] ?? 0),
                $id
            ]
        );
    }

    public function findOrCreateCategory(string $name): int
    {
        $name = trim($name);
        if ($name === '') return 0;
        $row = $this->one("SELECT id FROM raw_material_categories WHERE name = ? LIMIT 1", [$name]);
        if ($row) return (int)$row['id'];
        return $this->createCategory($name, 0);
    }

    public function findOrCreateUnit(string $symbol): int
    {
        $symbol = trim($symbol);
        if ($symbol === '') $symbol = 'Pcs';
        $row = $this->one("SELECT id FROM units WHERE symbol = ? OR name = ? LIMIT 1", [$symbol, $symbol]);
        if ($row) return (int)$row['id'];
        
        $this->execSql("INSERT INTO units (name, symbol) VALUES (?, ?)", [$symbol, $symbol]);
        return (int)$this->db->lastInsertId();
    }

    public function importRawMaterialsCsv(array $rows): int
    {
        $imported = 0;
        foreach ($rows as $row) {
            $name = trim($row['Nama Bahan'] ?? '');
            if ($name === '') continue;

            $sku = trim($row['SKU / Kode Barang'] ?? '');
            $catName = trim($row['Kategori'] ?? '');
            $unitSymbol = trim($row['Satuan'] ?? 'Pcs');
            $minStock = (float)($row['Minimum Stok'] ?? 0);

            $catId = $this->findOrCreateCategory($catName);
            $unitId = $this->findOrCreateUnit($unitSymbol);

            $this->createRawMaterial([
                'category_id' => $catId ?: null,
                'unit_id' => $unitId,
                'name' => $name,
                'sku' => $sku,
                'min_stock_qty' => $minStock
            ]);
            $imported++;
        }
        return $imported;
    }

    /**
     * Summary stats for the inventory dashboard header.
     */
    public function stats(): array
    {
        $total = $this->one("SELECT COUNT(*) AS cnt FROM raw_materials WHERE is_active = 1");
        $lowStock = $this->one("SELECT COUNT(*) AS cnt FROM raw_materials WHERE is_active = 1 AND stock_qty <= min_stock_qty");
        $outOfStock = $this->one("SELECT COUNT(*) AS cnt FROM raw_materials WHERE is_active = 1 AND stock_qty <= 0");
        $categories = $this->one("SELECT COUNT(*) AS cnt FROM raw_material_categories");

        return [
            'total_materials' => (int)($total['cnt'] ?? 0),
            'low_stock'       => (int)($lowStock['cnt'] ?? 0),
            'out_of_stock'    => (int)($outOfStock['cnt'] ?? 0),
            'total_categories' => (int)($categories['cnt'] ?? 0),
        ];
    }
}
