USE lenscraft;

INSERT INTO categories (name, slug, description, icon, color, status) VALUES
('Mirrorless', 'mirrorless', 'Camera mirrorless untuk foto dan video profesional.', 'camera', 'blue', 'active'),
('Lens', 'lens', 'Pilihan lensa prime dan zoom.', 'lens', 'purple', 'active'),
('Video', 'video', 'Peralatan video untuk produksi konten.', 'video', 'yellow', 'active');

INSERT INTO users (fullname, email, username, password, role, status, phone, bio, avatar_path, created_at, last_active) VALUES
('Admin LensCraft', 'admin@lenscraft.local', 'admin', '$2y$12$oq9DiITRAJxiIg4x3.f/cuIEufCQIeKV0xR/MODrENRF0nfrdD5NG', 'admin', 'active', '081111111111', 'Administrator utama yang mengelola katalog dan operasional LensCraft.', 'uploads/users/admin-lenscraft.jpg', NOW(), NOW()),
('Staff LensCraft', 'staff@lenscraft.local', 'staff', '$2y$12$YgrTTN4/MFIM9GrPkoD6N.0/IvNyr3DIzgMkiyFISh7mrm4b4l2n.', 'staff', 'active', '082222222222', 'Petugas operasional untuk verifikasi peminjaman dan pengembalian alat.', 'uploads/users/staff-lenscraft.jpg', NOW(), NOW()),
('Raka Pratama', 'user@example.com', 'user', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'user', 'active', '083333333333', 'Konten kreator yang rutin menyewa kamera mirrorless dan lensa portrait.', 'uploads/users/raka-pratama.jpg', NOW(), NOW()),
('Ayu Wicaksana', 'ayu@example.com', 'ayu', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'user', 'active', '081234567801', 'Freelance videografer untuk wedding dan dokumentasi acara kampus.', 'uploads/users/ayu-wicaksana.jpg', NOW(), NOW()),
('Bimo Nugraha', 'bimo@example.com', 'bimo', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'user', 'active', '081234567802', 'Fotografer street dan travel dengan preferensi gear ringan.', 'uploads/users/bimo-nugraha.jpg', NOW(), NOW()),
('Salsa Maharani', 'salsa@example.com', 'salsa', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'user', 'active', '081234567803', 'Creative producer yang sering memesan lighting dan kamera video.', 'uploads/users/salsa-maharani.jpg', NOW(), NOW()),
('Dion Prakoso', 'dion@example.com', 'dion', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'user', 'active', '081234567804', 'Mahasiswa film yang memakai rental untuk tugas produksi mingguan.', 'uploads/users/dion-prakoso.jpg', NOW(), NOW());

INSERT INTO user_settings (user_id, language, timezone, theme, is_profile_public, allow_marketing, allow_data_export, updated_at) VALUES
(1, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(2, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(3, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(4, 'id', 'Asia/Jakarta', 'dark', 0, 1, 1, NOW()),
(5, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(6, 'id', 'Asia/Jakarta', 'dark', 1, 1, 1, NOW()),
(7, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW());

INSERT INTO products (category_id, name, brand, category_slug, price_per_day, discount_percentage, description, image_path, stock_total, stock_available, in_stock, status, created_at) VALUES
(1, 'Sony A7 III', 'Sony', 'mirrorless', 89.00, 30, 'Popular full-frame mirrorless dengan autofocus cepat.', 'uploads/products/sony-a7-iii.jpg', 4, 4, 1, 'active', NOW()),
(1, 'Canon EOS R5', 'Canon', 'mirrorless', 149.00, 0, 'Kamera high-end untuk foto dan video profesional.', 'uploads/products/canon-eos-r5.jpg', 3, 3, 1, 'active', NOW()),
(2, 'Sigma 18-35mm f/1.8', 'Sigma', 'lens', 59.00, 0, 'Lensa zoom APS-C dengan aperture terang.', 'uploads/products/sigma-18-35.jpg', 5, 5, 1, 'active', NOW()),
(2, 'Sony 24-70mm GM', 'Sony', 'lens', 79.00, 10, 'Lensa zoom serbaguna untuk produksi harian.', 'uploads/products/sony-24-70-gm.jpg', 4, 4, 1, 'active', NOW()),
(3, 'Panasonic GH6', 'Panasonic', 'video', 99.00, 0, 'Kamera video hybrid untuk kebutuhan produksi.', 'uploads/products/panasonic-gh6.jpg', 2, 2, 1, 'active', NOW()),
(1, 'Nikon Z6 II', 'Nikon', 'mirrorless', 109.00, 5, 'Body hybrid full-frame untuk dokumentasi event dan video ringan.', 'uploads/products/nikon-z6ii.jpg', 3, 3, 1, 'active', NOW()),
(3, 'Sony FX3', 'Sony', 'video', 189.00, 0, 'Cinema line compact untuk produksi komersial dan dokumenter.', 'uploads/products/sony-fx3.jpg', 2, 2, 1, 'active', NOW()),
(2, 'Canon RF 24-70mm f/2.8L', 'Canon', 'lens', 95.00, 0, 'Zoom serbaguna untuk kebutuhan photo dan wedding shoot.', 'uploads/products/canon-rf-24-70.jpg', 3, 3, 1, 'active', NOW()),
(2, 'Tamron 70-180mm f/2.8', 'Tamron', 'lens', 85.00, 8, 'Tele zoom ringan untuk portrait dan sports coverage.', 'uploads/products/tamron-70-180.jpg', 3, 3, 1, 'active', NOW()),
(3, 'Blackmagic Pocket Cinema Camera 6K', 'Blackmagic', 'video', 169.00, 0, 'Kamera cinema compact untuk short film dan commercial shooting.', 'uploads/products/bmpcc-6k.jpg', 2, 2, 1, 'active', NOW()),
(3, 'Aputure LS 60x', 'Aputure', 'video', 49.00, 0, 'Lampu LED bi-color portable untuk interview dan konten studio.', 'uploads/products/aputure-60x.jpg', 4, 4, 1, 'active', NOW());

INSERT INTO rentals (rental_code, user_id, product_id, start_date, end_date, total_days, daily_rate, discount_percentage, delivery_method, delivery_fee, total_price, status, created_at, approved_at) VALUES
('RENT-2026-A001', 3, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 3, 62.30, 30, 'delivery', 15.00, 201.90, 'active', NOW(), NOW()),
('RENT-2026-A002', 3, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 3, 59.00, 0, 'pickup', 0.00, 177.00, 'completed', NOW(), NOW());

INSERT INTO returns (return_code, rental_id, processed_by, notes, status, returned_at, created_at) VALUES
('RET-2026-A001', 2, 2, 'Pengembalian selesai tanpa kendala.', 'completed', NOW(), NOW());

INSERT INTO activity_logs (user_id, actor_name, actor_role, activity_type, message, created_at) VALUES
(1, 'Admin LensCraft', 'admin', 'system', 'Inisialisasi data awal aplikasi.', NOW()),
(2, 'Staff LensCraft', 'staff', 'rental', 'Memverifikasi peminjaman customer.', NOW()),
(3, 'Raka Pratama', 'user', 'profile', 'Melengkapi profil akun.', NOW());
