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

-- MORE DUMMY DATA: categories, products, rentals, payments, returns, password_resets, activity_logs
INSERT INTO categories (name, slug, description, icon, color, status) VALUES
('Lighting','lighting','Studio and portable lighting','lightbulb','orange','aktif'),
('Accessories','aksesoris','Small accessories and consumables','plug','green','aktif'),
('Stabilizers','stabilizer','Gimbals, tripods, and sliders','tripod','gray','aktif');

INSERT INTO products (category_id, name, brand, category_slug, price_per_day, discount_percentage, description, image_path, stock_total, stock_available, in_stock, status, created_at) VALUES
(4, 'Aputure 120d II', 'Aputure', 'lighting', 120000.00, 0, 'Powerful daylight LED.', 'uploads/products/aputure-120d.jpg', 6, 6, 1, 'aktif', NOW()),
(4, 'Godox VL300', 'Godox', 'lighting', 70000.00, 10, 'High output LED for on-location', 'uploads/products/godox-vl300.jpg', 5, 5, 1, 'aktif', NOW()),
(4, 'Nanlite Forza 60', 'Nanlite', 'lighting', 50000.00, 0, 'Compact LED with strong output', 'uploads/products/nanlite-forza60.jpg', 4, 4, 1, 'aktif', NOW()),
(5, 'Extra Battery LP-E6', 'Canon', 'aksesoris', 5000.00, 0, 'Spare battery for Canon cameras', 'uploads/products/battery-lp-e6.jpg', 30, 30, 1, 'aktif', NOW()),
(5, 'Memory Card 128GB', 'SanDisk', 'aksesoris', 10000.00, 0, 'Fast UHS-I memory card', 'uploads/products/sd-128gb.jpg', 50, 50, 1, 'aktif', NOW()),
(5, 'Camera Bag Small', 'Peak Design', 'aksesoris', 20000.00, 0, 'Compact camera bag', 'uploads/products/peakdesign-bag.jpg', 15, 15, 1, 'aktif', NOW()),
(6, 'Zhiyun Weebill S', 'Zhiyun', 'stabilizer', 35000.00, 0, 'Lightweight gimbal', 'uploads/products/weebill-s.jpg', 7, 7, 1, 'aktif', NOW()),
(6, 'Manfrotto Tripod', 'Manfrotto', 'stabilizer', 15000.00, 0, 'Sturdy tripod', 'uploads/products/manfrotto-tripod.jpg', 10, 10, 1, 'aktif', NOW()),
(6, 'Edelkrone Slider', 'Edelkrone', 'stabilizer', 30000.00, 0, 'Compact slider', 'uploads/products/edelkrone-slider.jpg', 3, 3, 1, 'aktif', NOW()),
(5, 'ND Filter Kit', 'Hoya', 'aksesoris', 8000.00, 0, 'Variable ND filter set', 'uploads/products/nd-kit.jpg', 12, 12, 1, 'aktif', NOW()),
(4, 'Aputure Light Dome II', 'Aputure', 'lighting', 8000.00, 0, 'Softbox for Aputure', 'uploads/products/light-dome.jpg', 8, 8, 1, 'aktif', NOW()),
(6, 'Ronin-S', 'DJI', 'stabilizer', 40000.00, 0, 'Gimbal for DSLR & mirrorless', 'uploads/products/ronin-s.jpg', 5, 5, 1, 'aktif', NOW()),
(4, 'Panel Light Bi-color', 'Godox', 'lighting', 15000.00, 0, 'Portable bi-color panel', 'uploads/products/panel-bi-color.jpg', 9, 9, 1, 'aktif', NOW()),
(5, 'External Microphone', 'Sennheiser', 'aksesoris', 12000.00, 0, 'Wireless lavalier', 'uploads/products/sennheiser-lavalier.jpg', 10, 10, 1, 'aktif', NOW()),
(6, 'Glidecam', 'Glidecam', 'stabilizer', 25000.00, 0, 'Weighted stabilizer', 'uploads/products/glidecam.jpg', 2, 2, 1, 'aktif', NOW()),
(4, 'Ring Light 18"', 'Neewer', 'lighting', 8000.00, 0, 'Ring light for portrait', 'uploads/products/ring-light.jpg', 12, 12, 1, 'aktif', NOW()),
(5, 'Tripod Ballhead', 'Benro', 'aksesoris', 5000.00, 0, 'Ball head for tripod', 'uploads/products/benro-ballhead.jpg', 20, 20, 1, 'aktif', NOW()),
(5, 'AC Adapter', 'Sony', 'aksesoris', 6000.00, 0, 'Dummy battery adapter', 'uploads/products/ac-adapter.jpg', 25, 25, 1, 'aktif', NOW()),
(6, 'DJI Pocket 2', 'DJI', 'stabilizer', 30000.00, 0, 'Pocket stabilized camera', 'uploads/products/dji-pocket2.jpg', 6, 6, 1, 'aktif', NOW()),
(4, 'Spotlight 200W', 'Generic', 'lighting', 25000.00, 0, 'Studio spotlight', 'uploads/products/spotlight-200w.jpg', 3, 3, 1, 'aktif', NOW());

-- Add many rentals (explicit ids to allow payment references)
INSERT INTO rentals (id, rental_code, user_id, product_id, start_date, end_date, total_days, daily_rate, discount_percentage, delivery_method, delivery_fee, total_price, status, cancel_reason, created_at, approved_at, completed_at, cancelled_at) VALUES
(21, 'RENT-2026-D021', 1, 27, DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 39 DAY), 2, 120000.00, 0, 'ambil_sendiri', 0.00, 240000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(22, 'RENT-2026-D022', 2, 28, DATE_SUB(CURDATE(), INTERVAL 38 DAY), DATE_SUB(CURDATE(), INTERVAL 36 DAY), 3, 70000.00, 0, 'ambil_sendiri', 0.00, 210000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(23, 'RENT-2026-D023', 3, 29, DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 33 DAY), 3, 50000.00, 0, 'diantar', 20000.00, 170000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(24, 'RENT-2026-D024', 4, 30, DATE_SUB(CURDATE(), INTERVAL 32 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), 3, 8000.00, 0, 'ambil_sendiri', 0.00, 24000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(25, 'RENT-2026-D025', 5, 31, DATE_SUB(CURDATE(), INTERVAL 28 DAY), DATE_SUB(CURDATE(), INTERVAL 27 DAY), 2, 5000.00, 0, 'ambil_sendiri', 0.00, 10000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(26, 'RENT-2026-D026', 6, 32, DATE_SUB(CURDATE(), INTERVAL 26 DAY), DATE_SUB(CURDATE(), INTERVAL 24 DAY), 3, 35000.00, 0, 'diantar', 10000.00, 115000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(27, 'RENT-2026-D027', 7, 33, DATE_SUB(CURDATE(), INTERVAL 22 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY), 3, 12000.00, 0, 'ambil_sendiri', 0.00, 36000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(28, 'RENT-2026-D028', 8, 34, DATE_SUB(CURDATE(), INTERVAL 18 DAY), DATE_SUB(CURDATE(), INTERVAL 16 DAY), 3, 12000.00, 0, 'ambil_sendiri', 0.00, 36000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(29, 'RENT-2026-D029', 9, 35, DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY), 3, 25000.00, 0, 'ambil_sendiri', 0.00, 75000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(30, 'RENT-2026-D030', 10, 36, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 9 DAY), 2, 8000.00, 0, 'ambil_sendiri', 0.00, 16000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(31, 'RENT-2026-D031', 11, 37, DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY), 2, 6000.00, 0, 'ambil_sendiri', 0.00, 12000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(32, 'RENT-2026-D032', 12, 38, DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_SUB(CURDATE(), INTERVAL 4 DAY), 3, 4000.00, 0, 'ambil_sendiri', 0.00, 12000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(33, 'RENT-2026-D033', 13, 39, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 3, 25000.00, 0, 'diantar', 10000.00, 85000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(34, 'RENT-2026-D034', 14, 40, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 2, 30000.00, 0, 'ambil_sendiri', 0.00, 60000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(35, 'RENT-2026-D035', 15, 27, DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 2, 120000.00, 0, 'diantar', 50000.00, 290000.00, 'menunggu', NULL, NOW(), NULL, NULL, NULL),
(36, 'RENT-2026-D036', 16, 28, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 3, 70000.00, 0, 'ambil_sendiri', 0.00, 210000.00, 'menunggu', NULL, NOW(), NULL, NULL, NULL),
(37, 'RENT-2026-D037', 17, 29, DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 3, 50000.00, 0, 'diantar', 20000.00, 170000.00, 'menunggu', NULL, NOW(), NULL, NULL, NULL),
(38, 'RENT-2026-D038', 1, 30, DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_SUB(CURDATE(), INTERVAL 48 DAY), 3, 8000.00, 0, 'ambil_sendiri', 0.00, 24000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(39, 'RENT-2026-D039', 2, 31, DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 43 DAY), 3, 5000.00, 0, 'ambil_sendiri', 0.00, 15000.00, 'selesai', NULL, NOW(), NOW(), NOW(), NULL),
(40, 'RENT-2026-D040', 3, 32, DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 22 DAY), 3, 35000.00, 0, 'ambil_sendiri', 0.00, 105000.00, 'menunggu', NULL, NOW(), NULL, NULL, NULL);

INSERT INTO payments (payment_code, rental_id, amount, method, status, payer_name, payer_email, payer_phone, reference_code, paid_at, created_at, updated_at) VALUES
('PAY-2026-D021', 21, 240000.00, 'transfer_bank', 'paid', 'User One', 'user1@example.com', '081800000018', 'TRX-D021', NOW(), NOW(), NOW()),
('PAY-2026-D022', 22, 210000.00, 'kartu_kredit', 'paid', 'Petugas LensCraft', 'petugas@lenscraft.local', '082222222222', 'CC-D022', NOW(), NOW(), NOW()),
('PAY-2026-D023', 23, 170000.00, 'qris', 'paid', 'Raka Pratama', 'pelanggan@example.com', '083333333333', 'QR-D023', NOW(), NOW(), NOW()),
('PAY-2026-D024', 24, 24000.00, 'transfer_bank', 'paid', 'Ayu Wicaksana', 'ayu@example.com', '081234567801', 'TRX-D024', NOW(), NOW(), NOW()),
('PAY-2026-D025', 25, 10000.00, 'transfer_bank', 'paid', 'Bimo Nugraha', 'bimo@example.com', '081234567802', 'TRX-D025', NOW(), NOW(), NOW());

INSERT INTO returns (return_code, rental_id, processed_by, notes, fine_amount, status, returned_at, created_at) VALUES
('RET-2026-D021', 21, 2, 'Kondisi baik, tanpa denda', 0.00, 'selesai', NOW(), NOW()),
('RET-2026-D022', 23, 13, 'Terdapat gores kecil di lensa; denda 50k', 50000.00, 'selesai', NOW(), NOW());

INSERT INTO password_resets (user_id, email, token, expires_at, created_at) VALUES
(4, 'ayu@example.com', 'reset-ayuxxx', DATE_ADD(NOW(), INTERVAL 2 DAY), NOW()),
(5, 'bimo@example.com', 'reset-bimoxxx', DATE_ADD(NOW(), INTERVAL 2 DAY), NOW());

INSERT INTO activity_logs (user_id, actor_name, actor_role, activity_type, message, created_at) VALUES
(21, 'User One', 'pelanggan', 'rental', 'Menambah sewa lampu Aputure 120d II untuk project.', NOW()),
(22, 'Petugas LensCraft', 'petugas', 'payment', 'Memverifikasi pembayaran transfer bank.', NOW()),
(23, 'Raka Pratama', 'pelanggan', 'return', 'Mengembalikan gear dengan kondisi baik.', NOW());

-- Additional dummy data (users, settings, products, rentals, payments, returns, password resets, activity logs)
INSERT INTO users (id, fullname, email, username, password, role, status, phone, bio, avatar_path, created_at, last_active) VALUES
(8, 'Ilham Nur', 'ilham@example.com', 'ilham', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'aktif', '081800000008', 'Enthusiast photographer.', 'uploads/users/ilham.jpg', NOW(), NOW()),
(9, 'Siti Rahma', 'siti@example.com', 'siti', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'aktif', '081800000009', 'Student videographer.', 'uploads/users/siti.jpg', NOW(), NOW()),
(10, 'Andi Wijaya', 'andi@example.com', 'andi', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'menunggu', '081800000010', 'New user awaiting approval.', 'uploads/users/andi.jpg', NOW(), NOW()),
(11, 'Rina Safitri', 'rina@example.com', 'rina', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'nonaktif', '081800000011', 'Inactive user.', 'uploads/users/rina.jpg', NOW(), NOW()),
(12, 'Admin Two', 'admin2@lenscraft.local', 'admin2', '$2y$12$oq9DiITRAJxiIg4x3.f/cuIEufCQIeKV0xR/MODrENRF0nfrdD5NG', 'admin', 'aktif', '081800000012', 'Second administrator.', 'uploads/users/admin2.jpg', NOW(), NOW()),
(13, 'Staff Two', 'staff2@lenscraft.local', 'staff2', '$2y$12$YgrTTN4/MFIM9GrPkoD6N.0/IvNyr3DIzgMkiyFISh7mrm4b4l2n.', 'petugas', 'aktif', '081800000013', 'Front-desk staff.', 'uploads/users/staff2.jpg', NOW(), NOW()),
(14, 'Fajar Nugroho', 'fajar@example.com', 'fajar', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'aktif', '081800000014', 'Weekend shooter.', 'uploads/users/fajar.jpg', NOW(), NOW()),
(15, 'Nia Putri', 'nia@example.com', 'nia', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'aktif', '081800000015', 'Wedding photographer.', 'uploads/users/nia.jpg', NOW(), NOW()),
(16, 'Bayu Santoso', 'bayu@example.com', 'bayu', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'aktif', '081800000016', 'Video enthusiast.', 'uploads/users/bayu.jpg', NOW(), NOW()),
(17, 'Test User', 'testuser@example.com', 'testuser', '$2y$12$E1zpA7lEGZEFLPq0D8Urle5fo8FrKfwRem0Qfu9rmNBc3cVI6t5oa', 'pelanggan', 'menunggu', '081800000017', 'Temporary test account.', 'uploads/users/testuser.jpg', NOW(), NOW());

INSERT INTO user_settings (user_id, language, timezone, theme, is_profile_public, allow_marketing, allow_data_export, updated_at) VALUES
(8, 'id', 'Asia/Jakarta', 'light', 0, 0, 1, NOW()),
(9, 'id', 'Asia/Jakarta', 'dark', 0, 1, 1, NOW()),
(10, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(11, 'id', 'Asia/Jakarta', 'light', 0, 0, 1, NOW()),
(12, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(13, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(14, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(15, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW()),
(16, 'id', 'Asia/Jakarta', 'light', 1, 0, 1, NOW()),
(17, 'id', 'Asia/Jakarta', 'dark', 0, 0, 1, NOW());

INSERT INTO products (id, category_id, name, brand, category_slug, price_per_day, discount_percentage, description, image_path, stock_total, stock_available, in_stock, status, created_at) VALUES
(12, 1, 'Fujifilm X-T4', 'Fujifilm', 'kamera-mirrorless', 300000.00, 10, 'Versatile mirrorless for travel and street photography.', 'uploads/products/fujifilm-xt4.jpg', 12, 12, 1, 'aktif', NOW()),
(13, 1, 'Sony A1', 'Sony', 'kamera-mirrorless', 2500000.00, 5, 'Flagship high-resolution body with fast AF.', 'uploads/products/sony-a1.jpg', 2, 2, 1, 'aktif', NOW()),
(14, 3, 'Canon EOS C70', 'Canon', 'video', 1200000.00, 0, 'Compact cinema camera for professional video.', 'uploads/products/canon-c70.jpg', 3, 3, 1, 'aktif', NOW()),
(15, 3, 'Rode NTG4+', 'Rode', 'video', 50000.00, 0, 'Shotgun microphone for on-camera audio capture.', 'uploads/products/rode-ntg4p.jpg', 8, 8, 1, 'aktif', NOW()),
(16, 2, 'Nikon 50mm f/1.8', 'Nikon', 'lensa', 40000.00, 0, 'Affordable prime for portraits.', 'uploads/products/nikon-50-1.8.jpg', 20, 20, 1, 'aktif', NOW()),
(17, 2, 'Canon 85mm f/1.8', 'Canon', 'lensa', 60000.00, 0, 'Classic portrait lens.', 'uploads/products/canon-85-1.8.jpg', 10, 10, 1, 'aktif', NOW()),
(18, 3, 'GoPro Hero 11', 'GoPro', 'video', 50000.00, 0, 'Action camera for immersive shots.', 'uploads/products/gopro-hero11.jpg', 6, 6, 1, 'aktif', NOW()),
(19, 3, 'DJI RS3', 'DJI', 'video', 75000.00, 0, 'Stabilizer for smooth handheld footage.', 'uploads/products/dji-rs3.jpg', 4, 4, 1, 'aktif', NOW()),
(20, 2, 'Sigma 35mm f/1.4', 'Sigma', 'lensa', 90000.00, 0, 'Sharp wide-aperture prime.', 'uploads/products/sigma-35-1.4.jpg', 8, 8, 1, 'aktif', NOW()),
(21, 2, 'Tamron 17-70mm', 'Tamron', 'lensa', 60000.00, 0, 'Versatile zoom for APS-C bodies.', 'uploads/products/tamron-17-70.jpg', 5, 5, 1, 'aktif', NOW()),
(22, 3, 'Lume Cube Panel', 'Lume Cube', 'video', 30000.00, 0, 'Portable LED panel for interviews.', 'uploads/products/lumecube-panel.jpg', 10, 10, 1, 'aktif', NOW()),
(23, 3, 'Godox SL60W', 'Godox', 'video', 50000.00, 0, 'LED video light with Bowens mount.', 'uploads/products/godox-sl60w.jpg', 6, 0, 0, 'nonaktif', NOW()),
(24, 1, 'Panasonic S5', 'Panasonic', 'kamera-mirrorless', 600000.00, 0, 'Hybrid full-frame for video and photo.', 'uploads/products/panasonic-s5.jpg', 3, 3, 1, 'aktif', NOW()),
(25, 2, 'Voigtlander 35mm f/1.7', 'Voigtlander', 'lensa', 55000.00, 0, 'Compact manual-focus lens.', 'uploads/products/voigtlander-35-1.7.jpg', 2, 2, 1, 'aktif', NOW()),
(26, 3, 'Aputure Amaran 100d', 'Aputure', 'video', 85000.00, 0, 'High-power LED for studio use.', 'uploads/products/aputure-100d.jpg', 4, 4, 1, 'aktif', NOW());

INSERT INTO rentals (id, rental_code, user_id, product_id, start_date, end_date, total_days, daily_rate, discount_percentage, delivery_method, delivery_fee, total_price, status, cancel_reason, created_at, approved_at, completed_at, cancelled_at) VALUES
(3, 'RENT-2026-A003', 8, 12, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 3, 300000.00, 10, 'ambil_sendiri', 0.00, 810000.00, 'menunggu', NULL, NOW(), NULL, NULL, NULL),
(4, 'RENT-2026-A004', 9, 13, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2, 2500000.00, 5, 'diantar', 50000.00, 4725000.00, 'disetujui', NULL, NOW(), NULL, NULL, NULL),
(5, 'RENT-2026-A005', 10, 14, DATE_SUB(CURDATE(), INTERVAL 1 DAY), CURDATE(), 2, 1200000.00, 0, 'ambil_sendiri', 0.00, 2400000.00, 'aktif', NULL, NOW(), NULL, NULL, NULL),
(6, 'RENT-2026-A006', 11, 15, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 3, 50000.00, 0, 'ambil_sendiri', 0.00, 150000.00, 'mendatang', NULL, NOW(), NULL, NULL, NULL),
(7, 'RENT-2026-A007', 3, 18, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), 3, 50000.00, 0, 'ambil_sendiri', 0.00, 150000.00, 'selesai', NULL, NOW(), NOW(), NULL, NULL),
(8, 'RENT-2026-A008', 4, 16, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2, 40000.00, 0, 'ambil_sendiri', 0.00, 80000.00, 'dibatalkan', 'Customer changed mind', NOW(), NULL, NULL, DATE_ADD(NOW(), INTERVAL 0 SECOND)),
(9, 'RENT-2026-A009', 5, 17, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 3, 60000.00, 0, 'ambil_sendiri', 0.00, 180000.00, 'ditolak', 'Stock tidak cukup', NOW(), NULL, NULL, NULL),
(10, 'RENT-2026-A010', 6, 20, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 5, 90000.00, 0, 'ambil_sendiri', 0.00, 450000.00, 'aktif', NULL, NOW(), NULL, NULL, NULL),
(11, 'RENT-2026-A011', 14, 21, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2, 60000.00, 0, 'diantar', 30000.00, 150000.00, 'menunggu', NULL, NOW(), NULL, NULL, NULL),
(12, 'RENT-2026-A012', 15, 22, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 2, 30000.00, 0, 'ambil_sendiri', 0.00, 60000.00, 'disetujui', NULL, NOW(), NULL, NULL, NULL),
(13, 'RENT-2026-A013', 16, 12, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 3, 300000.00, 10, 'ambil_sendiri', 0.00, 810000.00, 'aktif', NULL, NOW(), NULL, NULL, NULL),
(14, 'RENT-2026-A014', 17, 13, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 2, 2500000.00, 0, 'diantar', 50000.00, 5050000.00, 'menunggu', NULL, NOW(), NULL, NULL, NULL),
(15, 'RENT-2026-A015', 8, 14, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 13 DAY), 3, 1200000.00, 0, 'ambil_sendiri', 0.00, 3600000.00, 'selesai', NULL, NOW(), NOW(), NULL, NULL),
(16, 'RENT-2026-A016', 9, 15, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 3, 50000.00, 0, 'ambil_sendiri', 0.00, 150000.00, 'aktif', NULL, NOW(), NULL, NULL, NULL),
(17, 'RENT-2026-A017', 10, 12, DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 3, 300000.00, 10, 'ambil_sendiri', 0.00, 810000.00, 'disetujui', NULL, NOW(), NULL, NULL, NULL),
(18, 'RENT-2026-A018', 11, 13, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 18 DAY), 3, 2500000.00, 0, 'ambil_sendiri', 0.00, 7500000.00, 'dibatalkan', 'Customer no-show', NOW(), NULL, NULL, NULL),
(19, 'RENT-2026-A019', 12, 18, DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 3, 50000.00, 0, 'diantar', 50000.00, 200000.00, 'menunggu', NULL, NOW(), NULL, NULL, NULL),
(20, 'RENT-2026-A020', 13, 19, DATE_SUB(CURDATE(), INTERVAL 1 DAY), CURDATE(), 2, 75000.00, 0, 'ambil_sendiri', 0.00, 150000.00, 'aktif', NULL, NOW(), NULL, NULL, NULL);

INSERT INTO payments (payment_code, rental_id, amount, method, status, payer_name, payer_email, payer_phone, reference_code, paid_at, created_at, updated_at) VALUES
('PAY-2026-A004', 4, 4725000.00, 'transfer_bank', 'paid', 'Siti Rahma', 'siti@example.com', '081800000009', 'TRX123456', NOW(), NOW(), NOW()),
('PAY-2026-A005', 5, 2400000.00, 'kartu_kredit', 'paid', 'Andi Wijaya', 'andi@example.com', '081800000010', 'CC987654', NOW(), NOW(), NOW()),
('PAY-2026-A007', 7, 150000.00, 'transfer_bank', 'paid', 'Raka Pratama', 'pelanggan@example.com', '083333333333', 'TRX789012', NOW(), NOW(), NOW()),
('PAY-2026-A010', 10, 450000.00, 'qris', 'pending', 'Salsa Maharani', 'salsa@example.com', '081234567803', NULL, NULL, NOW(), NOW()),
('PAY-2026-A013', 13, 810000.00, 'transfer_bank', 'paid', 'Bayu Santoso', 'bayu@example.com', '081800000016', 'TRX345678', NOW(), NOW(), NOW());

INSERT INTO returns (return_code, rental_id, processed_by, notes, fine_amount, status, returned_at, created_at) VALUES
('RET-2026-A002', 15, 13, 'Pengembalian selesai, peralatan OK.', 0.00, 'selesai', NOW(), NOW()),
('RET-2026-A003', 5, 13, 'Pengembalian dengan kerusakan kecil; denda terutang.', 100000.00, 'selesai', NOW(), NOW());

INSERT INTO password_resets (user_id, email, token, expires_at, created_at) VALUES
(9, 'siti@example.com', 'token123siti', DATE_ADD(NOW(), INTERVAL 1 DAY), NOW()),
(17, 'testuser@example.com', 'token456test', DATE_ADD(NOW(), INTERVAL 1 DAY), NOW());

INSERT INTO activity_logs (user_id, actor_name, actor_role, activity_type, message, created_at) VALUES
(8, 'Ilham Nur', 'pelanggan', 'rental', 'Membuat permintaan rental untuk Fujifilm X-T4.', NOW()),
(9, 'Siti Rahma', 'pelanggan', 'payment', 'Melakukan pembayaran untuk sewa Sony A1.', NOW()),
(12, 'Admin Two', 'admin', 'system', 'Menambahkan stok produk baru untuk uji coba.', NOW()),
(13, 'Staff Two', 'petugas', 'return', 'Menerima pengembalian dari pelanggan.', NOW()),
(14, 'Fajar Nugroho', 'pelanggan', 'profile', 'Memperbarui profil dan nomor telepon.', NOW()),
(15, 'Nia Putri', 'pelanggan', 'rental', 'Memesan peralatan untuk pemotretan wedding.', NOW()),
(16, 'Bayu Santoso', 'pelanggan', 'rental', 'Menjadwalkan sewa GoPro untuk project olahraga.', NOW()),
(3, 'Raka Pratama', 'pelanggan', 'payment', 'Pembayaran diproses untuk pengembalian selesai.', NOW());
