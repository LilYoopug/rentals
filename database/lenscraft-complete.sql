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
    status ENUM('aktif', 'nonaktif', 'menunggu') NOT NULL DEFAULT 'aktif',
    phone VARCHAR(30) DEFAULT NULL,
    address_line1 VARCHAR(150) DEFAULT NULL,
    address_line2 VARCHAR(150) DEFAULT NULL,
    city VARCHAR(80) DEFAULT NULL,
    province VARCHAR(80) DEFAULT NULL,
    zip_code VARCHAR(20) DEFAULT NULL,
    country VARCHAR(80) DEFAULT 'Indonesia',
    bio TEXT DEFAULT NULL,
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

CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    language VARCHAR(20) NOT NULL DEFAULT 'id',
    timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Jakarta',
    theme VARCHAR(20) NOT NULL DEFAULT 'dark',
    is_profile_public TINYINT(1) NOT NULL DEFAULT 0,
    allow_marketing TINYINT(1) NOT NULL DEFAULT 0,
    allow_data_export TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id)
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

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    email VARCHAR(120) NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resets_user FOREIGN KEY (user_id) REFERENCES users(id)
);

DELIMITER $$

CREATE TRIGGER trg_rentals_before_insert
BEFORE INSERT ON rentals
FOR EACH ROW
BEGIN
    IF NEW.status IN ('menunggu', 'disetujui', 'mendatang', 'aktif') THEN
        UPDATE products
        SET stock_available = stock_available - 1,
            in_stock = CASE WHEN stock_available - 1 > 0 THEN 1 ELSE 0 END
        WHERE id = NEW.product_id
          AND status = 'aktif'
          AND stock_available > 0;

        IF ROW_COUNT() <> 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock produk tidak tersedia.';
        END IF;
    END IF;

    IF NEW.status IN ('disetujui', 'aktif') AND NEW.approved_at IS NULL THEN
        SET NEW.approved_at = NOW();
    END IF;

    IF NEW.status = 'selesai' AND NEW.completed_at IS NULL THEN
        SET NEW.completed_at = NOW();
    END IF;

    IF NEW.status IN ('dibatalkan', 'ditolak') AND NEW.cancelled_at IS NULL THEN
        SET NEW.cancelled_at = NOW();
    END IF;
END$$

CREATE TRIGGER trg_rentals_before_update
BEFORE UPDATE ON rentals
FOR EACH ROW
BEGIN
    DECLARE old_reserves_stock TINYINT(1) DEFAULT 0;
    DECLARE new_reserves_stock TINYINT(1) DEFAULT 0;

    SET old_reserves_stock = IF(OLD.status IN ('menunggu', 'disetujui', 'mendatang', 'aktif'), 1, 0);
    SET new_reserves_stock = IF(NEW.status IN ('menunggu', 'disetujui', 'mendatang', 'aktif'), 1, 0);

    IF OLD.product_id <> NEW.product_id THEN
        IF old_reserves_stock = 1 THEN
            UPDATE products
            SET stock_available = LEAST(stock_total, stock_available + 1),
                in_stock = CASE WHEN LEAST(stock_total, stock_available + 1) > 0 THEN 1 ELSE 0 END
            WHERE id = OLD.product_id;
        END IF;

        IF new_reserves_stock = 1 THEN
            UPDATE products
            SET stock_available = stock_available - 1,
                in_stock = CASE WHEN stock_available - 1 > 0 THEN 1 ELSE 0 END
            WHERE id = NEW.product_id
              AND status = 'aktif'
              AND stock_available > 0;

            IF ROW_COUNT() <> 1 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock produk tidak tersedia.';
            END IF;
        END IF;
    ELSE
        IF old_reserves_stock = 0 AND new_reserves_stock = 1 THEN
            UPDATE products
            SET stock_available = stock_available - 1,
                in_stock = CASE WHEN stock_available - 1 > 0 THEN 1 ELSE 0 END
            WHERE id = NEW.product_id
              AND status = 'aktif'
              AND stock_available > 0;

            IF ROW_COUNT() <> 1 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock produk tidak tersedia.';
            END IF;
        ELSEIF old_reserves_stock = 1 AND new_reserves_stock = 0 THEN
            UPDATE products
            SET stock_available = LEAST(stock_total, stock_available + 1),
                in_stock = CASE WHEN LEAST(stock_total, stock_available + 1) > 0 THEN 1 ELSE 0 END
            WHERE id = OLD.product_id;
        END IF;
    END IF;

    IF NEW.status IN ('disetujui', 'aktif') AND NEW.approved_at IS NULL THEN
        SET NEW.approved_at = COALESCE(OLD.approved_at, NOW());
    END IF;

    IF NEW.status = 'selesai' AND NEW.completed_at IS NULL THEN
        SET NEW.completed_at = COALESCE(OLD.completed_at, NOW());
    END IF;

    IF NEW.status IN ('dibatalkan', 'ditolak') AND NEW.cancelled_at IS NULL THEN
        SET NEW.cancelled_at = COALESCE(OLD.cancelled_at, NOW());
    END IF;

    IF NEW.status NOT IN ('dibatalkan', 'ditolak') THEN
        SET NEW.cancel_reason = NULL;
    END IF;
END$$

CREATE TRIGGER trg_rentals_before_delete
BEFORE DELETE ON rentals
FOR EACH ROW
BEGIN
    IF OLD.status IN ('menunggu', 'disetujui', 'mendatang', 'aktif') THEN
        UPDATE products
        SET stock_available = LEAST(stock_total, stock_available + 1),
            in_stock = CASE WHEN LEAST(stock_total, stock_available + 1) > 0 THEN 1 ELSE 0 END
        WHERE id = OLD.product_id;
    END IF;
END$$

CREATE TRIGGER trg_returns_before_insert
BEFORE INSERT ON returns
FOR EACH ROW
BEGIN
    DECLARE rental_end_date DATE DEFAULT NULL;
    DECLARE rental_daily_rate DECIMAL(10,2) DEFAULT 0;

    IF NEW.status = 'selesai' AND NEW.returned_at IS NULL THEN
        SET NEW.returned_at = NOW();
    END IF;

    SELECT end_date, daily_rate
    INTO rental_end_date, rental_daily_rate
    FROM rentals
    WHERE id = NEW.rental_id
    LIMIT 1;

    IF NEW.status = 'selesai' AND rental_end_date IS NOT NULL THEN
        SET NEW.fine_amount = GREATEST(DATEDIFF(DATE(NEW.returned_at), rental_end_date), 0) * rental_daily_rate;
    ELSE
        SET NEW.fine_amount = 0;
    END IF;
END$$

CREATE TRIGGER trg_returns_after_insert
AFTER INSERT ON returns
FOR EACH ROW
BEGIN
    IF NEW.status = 'selesai' THEN
        UPDATE rentals
        SET status = 'selesai',
            completed_at = COALESCE(NEW.returned_at, NOW())
        WHERE id = NEW.rental_id
          AND status <> 'selesai';
    END IF;
END$$

CREATE TRIGGER trg_returns_before_update
BEFORE UPDATE ON returns
FOR EACH ROW
BEGIN
    DECLARE rental_end_date DATE DEFAULT NULL;
    DECLARE rental_daily_rate DECIMAL(10,2) DEFAULT 0;

    IF NEW.status = 'selesai' AND NEW.returned_at IS NULL THEN
        SET NEW.returned_at = COALESCE(OLD.returned_at, NOW());
    END IF;

    SELECT end_date, daily_rate
    INTO rental_end_date, rental_daily_rate
    FROM rentals
    WHERE id = NEW.rental_id
    LIMIT 1;

    IF NEW.status = 'selesai' AND rental_end_date IS NOT NULL THEN
        SET NEW.fine_amount = GREATEST(DATEDIFF(DATE(NEW.returned_at), rental_end_date), 0) * rental_daily_rate;
    ELSE
        SET NEW.fine_amount = 0;
    END IF;
END$$

CREATE TRIGGER trg_returns_after_update
AFTER UPDATE ON returns
FOR EACH ROW
BEGIN
    IF NEW.status = 'selesai' AND OLD.status <> 'selesai' THEN
        UPDATE rentals
        SET status = 'selesai',
            completed_at = COALESCE(NEW.returned_at, NOW())
        WHERE id = NEW.rental_id
          AND status <> 'selesai';
    END IF;
END$$

DELIMITER ;

-- Seed Data

INSERT INTO categories (name, slug, description, icon, color, status) VALUES
('Mirrorless', 'kamera-mirrorless', 'Camera mirrorless untuk foto dan video profesional.', 'camera', 'blue', 'aktif'),
('Lens', 'lensa', 'Pilihan lensa prime dan zoom.', 'lensa', 'purple', 'aktif'),
('Video', 'video', 'Peralatan video untuk produksi konten.', 'video', 'yellow', 'aktif');

INSERT INTO users (fullname, email, username, password, role, status, phone, bio, avatar_path, created_at, last_active) VALUES
('Admin LensCraft', 'admin@lenscraft.local', 'admin', '$2y$12$oq9DiITRAJxiIg4x3.f/cuIEufCQIeKV0xR/MODrENRF0nfrdD5NG', 'admin', 'aktif', '081111111111', 'Administrator utama yang mengelola katalog dan operasional LensCraft.', 'uploads/users/admin-lenscraft.jpg', NOW(), NOW()),
('Petugas LensCraft', 'petugas@lenscraft.local', 'petugas', '$2y$12$YgrTTN4/MFIM9GrPkoD6N.0/IvNyr3DIzgMkiyFISh7mrm4b4l2n.', 'petugas', 'aktif', '082222222222', 'Petugas operasional untuk verifikasi peminjaman dan pengembalian alat.', 'uploads/users/staff-lenscraft.jpg', NOW(), NOW()),
('Raka Pratama', 'pelanggan@example.com', 'pelanggan', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'aktif', '083333333333', 'Konten kreator yang rutin menyewa kamera mirrorless dan lensa portrait.', 'uploads/users/raka-pratama.jpg', NOW(), NOW()),
('Ayu Wicaksana', 'ayu@example.com', 'ayu', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'aktif', '081234567801', 'Freelance videografer untuk wedding dan dokumentasi acara kampus.', 'uploads/users/ayu-wicaksana.jpg', NOW(), NOW()),
('Bimo Nugraha', 'bimo@example.com', 'bimo', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'aktif', '081234567802', 'Fotografer street dan travel dengan preferensi gear ringan.', 'uploads/users/bimo-nugraha.jpg', NOW(), NOW()),
('Salsa Maharani', 'salsa@example.com', 'salsa', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'aktif', '081234567803', 'Creative producer yang sering memesan lighting dan kamera video.', 'uploads/users/salsa-maharani.jpg', NOW(), NOW()),
('Dion Prakoso', 'dion@example.com', 'dion', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'aktif', '081234567804', 'Mahasiswa film yang memakai rental untuk tugas produksi mingguan.', 'uploads/users/dion-prakoso.jpg', NOW(), NOW());

INSERT INTO user_settings (user_id, language, timezone, theme, is_profile_public, allow_marketing, allow_data_export, updated_at) VALUES
(1, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(2, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(3, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(4, 'id', 'Asia/Jakarta', 'dark', 0, 1, 1, NOW()),
(5, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(6, 'id', 'Asia/Jakarta', 'dark', 1, 1, 1, NOW()),
(7, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW());

INSERT INTO products (category_id, name, brand, category_slug, price_per_day, discount_percentage, description, image_path, stock_total, stock_available, in_stock, status, created_at) VALUES
(1, 'Sony A7 III', 'Sony', 'kamera-mirrorless', 500000.00, 30, 'Popular full-frame mirrorless dengan autofocus cepat.', 'uploads/products/sony-a7-iii.jpg', 4, 4, 1, 'aktif', NOW()),
(1, 'Canon EOS R5', 'Canon', 'kamera-mirrorless', 1400000.00, 0, 'Kamera high-end untuk foto dan video profesional.', 'uploads/products/canon-eos-r5.jpg', 3, 3, 1, 'aktif', NOW()),
(2, 'Sigma 18-35mm f/1.8', 'Sigma', 'lensa', 250000.00, 0, 'Lensa zoom APS-C dengan aperture terang.', 'uploads/products/sigma-18-35.jpg', 5, 5, 1, 'aktif', NOW()),
(2, 'Sony 24-70mm GM', 'Sony', 'lensa', 450000.00, 10, 'Lensa zoom serbaguna untuk produksi harian.', 'uploads/products/sony-24-70-gm.jpg', 4, 4, 1, 'aktif', NOW()),
(3, 'Panasonic GH6', 'Panasonic', 'video', 650000.00, 0, 'Kamera video hybrid untuk kebutuhan produksi.', 'uploads/products/panasonic-gh6.jpg', 2, 2, 1, 'aktif', NOW()),
(1, 'Nikon Z6 II', 'Nikon', 'kamera-mirrorless', 700000.00, 5, 'Body hybrid full-frame untuk dokumentasi event dan video ringan.', 'uploads/products/nikon-z6ii.jpg', 3, 3, 1, 'aktif', NOW()),
(3, 'Sony FX3', 'Sony', 'video', 1800000.00, 0, 'Cinema line compact untuk produksi komersial dan dokumenter.', 'uploads/products/sony-fx3.jpg', 2, 2, 1, 'aktif', NOW()),
(2, 'Canon RF 24-70mm f/2.8L', 'Canon', 'lensa', 500000.00, 0, 'Zoom serbaguna untuk kebutuhan photo dan wedding shoot.', 'uploads/products/canon-rf-24-70.jpg', 3, 3, 1, 'aktif', NOW()),
(2, 'Tamron 70-180mm f/2.8', 'Tamron', 'lensa', 400000.00, 8, 'Tele zoom ringan untuk portrait dan sports coverage.', 'uploads/products/tamron-70-180.jpg', 3, 3, 1, 'aktif', NOW()),
(3, 'Blackmagic Pocket Cinema Camera 6K', 'Blackmagic', 'video', 1600000.00, 0, 'Kamera cinema compact untuk short film dan commercial shooting.', 'uploads/products/bmpcc-6k.jpg', 2, 2, 1, 'aktif', NOW()),
(3, 'Aputure LS 60x', 'Aputure', 'video', 175000.00, 0, 'Lampu LED bi-color portable untuk interview dan konten studio.', 'uploads/products/aputure-60x.jpg', 4, 4, 1, 'aktif', NOW());

INSERT INTO rentals (rental_code, user_id, product_id, start_date, end_date, total_days, daily_rate, discount_percentage, delivery_method, delivery_fee, total_price, status, created_at, approved_at) VALUES
('RENT-2026-A001', 3, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 3, 350000.00, 30, 'diantar', 50000.00, 1100000.00, 'aktif', NOW(), NOW()),
('RENT-2026-A002', 3, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 3, 250000.00, 0, 'ambil_sendiri', 0.00, 750000.00, 'selesai', NOW(), NOW());

INSERT INTO returns (return_code, rental_id, processed_by, notes, status, returned_at, created_at) VALUES
('RET-2026-A001', 2, 2, 'Pengembalian selesai tanpa kendala.', 'selesai', NOW(), NOW());

INSERT INTO activity_logs (user_id, actor_name, actor_role, activity_type, message, created_at) VALUES
(1, 'Admin LensCraft', 'admin', 'system', 'Inisialisasi data awal aplikasi.', NOW()),
(2, 'Staff LensCraft', 'petugas', 'rental', 'Memverifikasi peminjaman customer.', NOW()),
(3, 'Raka Pratama', 'pelanggan', 'profile', 'Melengkapi profil akun.', NOW());
