-- Jalankan hanya jika tabel users kosong atau belum ada super admin.
INSERT INTO users (outlet_id, role_id, name, username, email, phone, password, daily_salary, is_active, created_at, updated_at)
SELECT 1, r.id, 'Super Admin', 'admin', 'admin@simresto.local', NULL, '$2y$10$G/69B6W1QVF7ZWgcVYjSO.8BbdLWRlRroa2Ipj9t3ua3GNIZhjqNC', 0, 1, NOW(), NOW()
FROM roles r
WHERE r.code='super_admin'
AND NOT EXISTS (SELECT 1 FROM users WHERE username='admin');
-- Password default: admin123. Segera ganti setelah login pertama.
