-- Scope produk dan varian ke outlet aktif.
ALTER TABLE products
    ADD COLUMN outlet_id bigint(20) UNSIGNED DEFAULT NULL AFTER category_id,
    ADD KEY idx_products_outlet (outlet_id),
    ADD CONSTRAINT fk_products_outlet FOREIGN KEY (outlet_id) REFERENCES outlets (id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE product_variants
    ADD COLUMN outlet_id bigint(20) UNSIGNED DEFAULT NULL AFTER product_id,
    ADD KEY idx_product_variants_outlet (outlet_id),
    ADD CONSTRAINT fk_product_variants_outlet FOREIGN KEY (outlet_id) REFERENCES outlets (id) ON DELETE SET NULL ON UPDATE CASCADE;
