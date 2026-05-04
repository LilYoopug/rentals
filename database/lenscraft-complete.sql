DROP DATABASE IF EXISTS lenscraft;
CREATE DATABASE lenscraft CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lenscraft;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    username VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'petugas', 'pelanggan') NOT NULL DEFAULT 'pelanggan',
    phone VARCHAR(30) DEFAULT NULL,
    billing_info JSON DEFAULT NULL,
    avatar_path VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_active DATETIME DEFAULT NULL
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    icon VARCHAR(60) DEFAULT 'camera',
    color VARCHAR(30) DEFAULT 'blue',
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(80) NOT NULL,
    category_slug VARCHAR(80) NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_percentage INT NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    image_path VARCHAR(255) NOT NULL DEFAULT 'images/gear-placeholder.svg',
    stock_total INT NOT NULL DEFAULT 1,
    stock_available INT NOT NULL DEFAULT 1,
    in_stock TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rental_code VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL DEFAULT 1,
    daily_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_percentage INT NOT NULL DEFAULT 0,
    delivery_method ENUM('ambil_sendiri', 'diantar') NOT NULL DEFAULT 'ambil_sendiri',
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('menunggu', 'disetujui', 'mendatang', 'aktif', 'selesai', 'dibatalkan', 'ditolak') NOT NULL DEFAULT 'menunggu',
    cancel_reason VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    cancelled_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_rentals_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_rentals_product FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_code VARCHAR(30) NOT NULL UNIQUE,
    rental_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    method VARCHAR(50) NOT NULL DEFAULT 'transfer_bank',
    status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    payer_name VARCHAR(120) DEFAULT NULL,
    payer_email VARCHAR(120) DEFAULT NULL,
    payer_phone VARCHAR(30) DEFAULT NULL,
    reference_code VARCHAR(60) DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payments_rental (rental_id),
    CONSTRAINT fk_payments_rental FOREIGN KEY (rental_id) REFERENCES rentals(id)
);

CREATE TABLE returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_code VARCHAR(30) NOT NULL UNIQUE,
    rental_id INT NOT NULL,
    processed_by INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('menunggu', 'selesai') NOT NULL DEFAULT 'menunggu',
    returned_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_returns_rental (rental_id),
    CONSTRAINT fk_returns_rental FOREIGN KEY (rental_id) REFERENCES rentals(id),
    CONSTRAINT fk_returns_user FOREIGN KEY (processed_by) REFERENCES users(id)
);

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    actor_name VARCHAR(120) NOT NULL,
    actor_role VARCHAR(30) NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id)
);



-- Seed Data

INSERT INTO categories (name, slug, description, icon, color, status) VALUES
('Mirrorless', 'kamera-mirrorless', 'Camera mirrorless untuk foto dan video profesional.', 'camera', 'blue', 'aktif'),
('Lens', 'lensa', 'Pilihan lensa prime dan zoom.', 'lensa', 'purple', 'aktif'),
('Video', 'video', 'Peralatan video untuk produksi konten.', 'video', 'yellow', 'aktif');

INSERT INTO users (fullname, email, username, password, role, phone, billing_info, avatar_path, created_at, last_active) VALUES
('Admin LensCraft', 'admin@lenscraft.local', 'admin', '$2y$12$oq9DiITRAJxiIg4x3.f/cuIEufCQIeKV0xR/MODrENRF0nfrdD5NG', 'admin', '081111111111', NULL, 'uploads/users/admin-lenscraft.jpg', NOW(), NOW()),
('Petugas LensCraft', 'staff@lenscraft.local', 'staff', '$2y$12$YgrTTN4/MFIM9GrPkoD6N.0/IvNyr3DIzgMkiyFISh7mrm4b4l2n.', 'petugas', '082222222222', NULL, 'uploads/users/staff-lenscraft.jpg', NOW(), NOW()),
('Raka Pratama', 'user@example.com', 'user', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', '083333333333', NULL, 'uploads/users/raka-pratama.jpg', NOW(), NOW());

-- User settings are now stored in browser localStorage (no database table needed)

-- Sample products with product images
INSERT INTO products (category_id, name, brand, category_slug, price_per_day, discount_percentage, description, image_path, stock_total, stock_available, in_stock, status, created_at) VALUES
(1, 'Sony A7 III', 'Sony', 'kamera-mirrorless', 500000.00, 30, 'Popular full-frame mirrorless dengan autofocus cepat dan performa video 4K.', 'uploads/products/sony-a7-iii.jpg', 4, 4, 1, 'aktif', NOW()),
(1, 'Canon EOS R5', 'Canon', 'kamera-mirrorless', 1400000.00, 0, 'Kamera high-end untuk foto dan video profesional dengan resolusi 45MP.', 'uploads/products/canon-eos-r5.jpg', 3, 3, 1, 'aktif', NOW()),
(2, 'Sigma 18-35mm f/1.8', 'Sigma', 'lensa', 250000.00, 0, 'Lensa zoom APS-C dengan aperture terang untuk low-light shooting.', 'uploads/products/sigma-18-35.jpg', 5, 5, 1, 'aktif', NOW()),
(2, 'Sony 24-70mm GM', 'Sony', 'lensa', 450000.00, 10, 'Lensa zoom serbaguna untuk produksi harian dengan kualitas G Master.', 'uploads/products/sony-24-70-gm.jpg', 4, 4, 1, 'aktif', NOW()),
(3, 'Panasonic GH6', 'Panasonic', 'video', 650000.00, 0, 'Kamera video hybrid untuk kebutuhan produksi dengan internal recording 5.7K.', 'uploads/products/panasonic-gh6.jpg', 2, 2, 1, 'aktif', NOW()),
(1, 'Nikon Z6 II', 'Nikon', 'kamera-mirrorless', 700000.00, 5, 'Body hybrid full-frame untuk dokumentasi event dan video ringan.', 'uploads/products/nikon-z6ii.jpg', 3, 3, 1, 'aktif', NOW()),
(3, 'Sony FX3', 'Sony', 'video', 1800000.00, 0, 'Cinema line compact untuk produksi komersial dan dokumenter profesional.', 'uploads/products/sony-fx3.jpg', 2, 2, 1, 'aktif', NOW()),
(2, 'Canon RF 24-70mm f/2.8L', 'Canon', 'lensa', 500000.00, 0, 'Zoom serbaguna untuk kebutuhan photo dan wedding shoot dengan IS.', 'uploads/products/canon-rf-24-70.jpg', 3, 3, 1, 'aktif', NOW());

-- Sample rentals with various statuses
-- Note: Stock levels are adjusted based on active rentals
-- Product IDs: 1=Sony A7 III, 2=Canon EOS R5, 3=Sigma 18-35mm, 4=Sony 24-70mm GM, 5=Panasonic GH6, 6=Nikon Z6 II, 7=Sony FX3, 8=Canon RF 24-70mm

INSERT INTO rentals (rental_code, user_id, product_id, start_date, end_date, total_days, daily_rate, discount_percentage, delivery_method, delivery_fee, total_price, status, cancel_reason, created_at, approved_at, completed_at, cancelled_at) VALUES
-- Completed rentals (selesai) - stock already returned
('RNT-2024-001', 3, 1, '2024-03-01', '2024-03-05', 5, 500000.00, 30, 'ambil_sendiri', 0, 1750000.00, 'selesai', NULL, '2024-02-28 10:00:00', '2024-02-28 14:00:00', '2024-03-05 16:00:00', NULL),
('RNT-2024-002', 3, 3, '2024-03-10', '2024-03-12', 3, 250000.00, 0, 'diantar', 50000.00, 800000.00, 'selesai', NULL, '2024-03-08 09:00:00', '2024-03-08 15:00:00', '2024-03-12 18:00:00', NULL),
('RNT-2024-003', 3, 6, '2024-03-15', '2024-03-20', 6, 700000.00, 5, 'ambil_sendiri', 0, 3990000.00, 'selesai', NULL, '2024-03-13 11:00:00', '2024-03-13 16:00:00', '2024-03-20 14:00:00', NULL),

-- Active rentals (aktif) - currently rented, stock reduced
('RNT-2026-004', 3, 1, '2026-04-28', '2026-05-03', 6, 500000.00, 30, 'ambil_sendiri', 0, 2100000.00, 'aktif', NULL, '2026-04-26 10:00:00', '2026-04-26 15:00:00', NULL, NULL),
('RNT-2026-005', 3, 4, '2026-04-29', '2026-05-05', 7, 450000.00, 10, 'diantar', 75000.00, 2907500.00, 'aktif', NULL, '2026-04-27 09:00:00', '2026-04-27 14:00:00', NULL, NULL),

-- Approved/upcoming rentals (disetujui/mendatang) - approved but not started yet, stock reserved
('RNT-2026-006', 3, 2, '2026-05-05', '2026-05-10', 6, 1400000.00, 0, 'ambil_sendiri', 0, 8400000.00, 'disetujui', NULL, '2026-04-30 10:00:00', '2026-04-30 16:00:00', NULL, NULL),
('RNT-2026-007', 3, 5, '2026-05-06', '2026-05-09', 4, 650000.00, 0, 'diantar', 100000.00, 2700000.00, 'mendatang', NULL, '2026-05-01 08:00:00', '2026-05-01 10:00:00', NULL, NULL),

-- Pending approval (menunggu) - waiting for admin approval, stock not yet reserved
('RNT-2026-008', 3, 7, '2026-05-10', '2026-05-15', 6, 1800000.00, 0, 'ambil_sendiri', 0, 10800000.00, 'menunggu', NULL, '2026-05-01 11:00:00', NULL, NULL, NULL),
('RNT-2026-009', 3, 8, '2026-05-12', '2026-05-14', 3, 500000.00, 0, 'diantar', 50000.00, 1550000.00, 'menunggu', NULL, '2026-05-01 12:00:00', NULL, NULL, NULL),

-- Cancelled rentals (dibatalkan) - stock returned/not reserved
('RNT-2026-010', 3, 3, '2026-05-08', '2026-05-12', 5, 250000.00, 0, 'ambil_sendiri', 0, 1250000.00, 'dibatalkan', 'Pelanggan membatalkan pesanan', '2026-04-29 14:00:00', NULL, NULL, '2026-04-30 09:00:00'),

-- Rejected rentals (ditolak) - rejected by admin, stock not reserved
('RNT-2026-011', 3, 5, '2026-05-15', '2026-05-20', 6, 650000.00, 0, 'ambil_sendiri', 0, 3900000.00, 'ditolak', 'Stok tidak tersedia untuk tanggal tersebut', '2026-04-28 16:00:00', NULL, NULL, NULL);

-- Sample payments for rentals
INSERT INTO payments (payment_code, rental_id, amount, method, status, payer_name, payer_email, payer_phone, reference_code, paid_at, created_at) VALUES
('PAY-2024-001', 1, 1750000.00, 'transfer_bank', 'paid', 'Raka Pratama', 'user@example.com', '083333333333', 'TRF20240228001', '2024-02-28 15:00:00', '2024-02-28 14:30:00'),
('PAY-2024-002', 2, 800000.00, 'transfer_bank', 'paid', 'Raka Pratama', 'user@example.com', '083333333333', 'TRF20240308001', '2024-03-08 16:00:00', '2024-03-08 15:30:00'),
('PAY-2024-003', 3, 3990000.00, 'transfer_bank', 'paid', 'Raka Pratama', 'user@example.com', '083333333333', 'TRF20240313001', '2024-03-13 17:00:00', '2024-03-13 16:30:00'),
('PAY-2026-004', 4, 2100000.00, 'transfer_bank', 'paid', 'Raka Pratama', 'user@example.com', '083333333333', 'TRF20260426001', '2026-04-26 16:00:00', '2026-04-26 15:30:00'),
('PAY-2026-005', 5, 2907500.00, 'transfer_bank', 'paid', 'Raka Pratama', 'user@example.com', '083333333333', 'TRF20260427001', '2026-04-27 15:00:00', '2026-04-27 14:30:00'),
('PAY-2026-006', 6, 8400000.00, 'transfer_bank', 'paid', 'Raka Pratama', 'user@example.com', '083333333333', 'TRF20260430001', '2026-04-30 17:00:00', '2026-04-30 16:30:00'),
('PAY-2026-007', 7, 2700000.00, 'transfer_bank', 'paid', 'Raka Pratama', 'user@example.com', '083333333333', 'TRF20260501001', '2026-05-01 11:00:00', '2026-05-01 10:30:00'),
('PAY-2026-008', 8, 10800000.00, 'transfer_bank', 'pending', 'Raka Pratama', 'user@example.com', '083333333333', NULL, NULL, '2026-05-01 11:30:00'),
('PAY-2026-009', 9, 1550000.00, 'transfer_bank', 'pending', 'Raka Pratama', 'user@example.com', '083333333333', NULL, NULL, '2026-05-01 12:30:00');

-- Sample returns for completed rentals
INSERT INTO returns (return_code, rental_id, processed_by, notes, fine_amount, status, returned_at, created_at) VALUES
('RET-2024-001', 1, 2, 'Peralatan dikembalikan dalam kondisi baik. Tidak ada kerusakan.', 0, 'selesai', '2024-03-05 16:00:00', '2024-03-05 16:00:00'),
('RET-2024-002', 2, 2, 'Lensa dikembalikan tepat waktu. Kondisi sangat baik.', 0, 'selesai', '2024-03-12 18:00:00', '2024-03-12 18:00:00'),
('RET-2024-003', 3, 2, 'Kamera dikembalikan dengan sedikit keterlambatan 2 jam. Denda dikenakan.', 50000.00, 'selesai', '2024-03-20 14:00:00', '2024-03-20 14:00:00');

-- Update product stock based on active/approved rentals
-- Sony A7 III (id=1): 4 total, 1 rented (RNT-2026-004 aktif) = 3 available
UPDATE products SET stock_available = 3 WHERE id = 1;

-- Canon EOS R5 (id=2): 3 total, 1 reserved (RNT-2026-006 disetujui) = 2 available
UPDATE products SET stock_available = 2 WHERE id = 2;

-- Sony 24-70mm GM (id=4): 4 total, 1 rented (RNT-2026-005 aktif) = 3 available
UPDATE products SET stock_available = 3 WHERE id = 4;

-- Panasonic GH6 (id=5): 2 total, 1 reserved (RNT-2026-007 mendatang) = 1 available
UPDATE products SET stock_available = 1 WHERE id = 5;

INSERT INTO activity_logs (user_id, actor_name, actor_role, activity_type, message, created_at) VALUES
(1, 'Admin LensCraft', 'admin', 'system', 'Inisialisasi data awal aplikasi.', NOW()),
(2, 'Petugas LensCraft', 'petugas', 'system', 'Setup akun petugas operasional.', NOW()),
(3, 'Raka Pratama', 'pelanggan', 'profile', 'Melengkapi profil akun pelanggan.', NOW()),
(3, 'Raka Pratama', 'pelanggan', 'rental', 'Membuat peminjaman baru: Sony A7 III (RNT-2024-001)', '2024-02-28 10:00:00'),
(1, 'Admin LensCraft', 'admin', 'rental', 'Menyetujui peminjaman: RNT-2024-001', '2024-02-28 14:00:00'),
(2, 'Petugas LensCraft', 'petugas', 'return', 'Memproses pengembalian: RET-2024-001', '2024-03-05 16:00:00'),
(3, 'Raka Pratama', 'pelanggan', 'rental', 'Membuat peminjaman baru: Sigma 18-35mm (RNT-2024-002)', '2024-03-08 09:00:00'),
(1, 'Admin LensCraft', 'admin', 'rental', 'Menyetujui peminjaman: RNT-2024-002', '2024-03-08 15:00:00'),
(2, 'Petugas LensCraft', 'petugas', 'return', 'Memproses pengembalian: RET-2024-002', '2024-03-12 18:00:00'),
(3, 'Raka Pratama', 'pelanggan', 'rental', 'Membuat peminjaman baru: Sony A7 III (RNT-2026-004)', '2026-04-26 10:00:00'),
(1, 'Admin LensCraft', 'admin', 'rental', 'Menyetujui peminjaman: RNT-2026-004', '2026-04-26 15:00:00'),
(3, 'Raka Pratama', 'pelanggan', 'rental', 'Membuat peminjaman baru: Sony 24-70mm GM (RNT-2026-005)', '2026-04-27 09:00:00'),
(1, 'Admin LensCraft', 'admin', 'rental', 'Menyetujui peminjaman: RNT-2026-005', '2026-04-27 14:00:00'),
(3, 'Raka Pratama', 'pelanggan', 'rental', 'Membuat peminjaman baru: Canon EOS R5 (RNT-2026-006)', '2026-04-30 10:00:00'),
(1, 'Admin LensCraft', 'admin', 'rental', 'Menyetujui peminjaman: RNT-2026-006', '2026-04-30 16:00:00'),
(3, 'Raka Pratama', 'pelanggan', 'rental', 'Membatalkan peminjaman: RNT-2026-010', '2026-04-30 09:00:00'),
(1, 'Admin LensCraft', 'admin', 'rental', 'Menolak peminjaman: RNT-2026-011 - Stok tidak tersedia', '2026-04-28 17:00:00');
