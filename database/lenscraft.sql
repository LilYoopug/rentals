DROP DATABASE IF EXISTS lenscraft;
CREATE DATABASE lenscraft CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lenscraft;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    username VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
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
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
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
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
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
    delivery_method ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup',
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('pending', 'upcoming', 'active', 'completed', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
    cancel_reason VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    cancelled_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_rentals_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_rentals_product FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_code VARCHAR(30) NOT NULL UNIQUE,
    rental_id INT NOT NULL,
    processed_by INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    status ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
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
    IF NEW.status IN ('pending', 'upcoming', 'active') THEN
        UPDATE products
        SET stock_available = stock_available - 1,
            in_stock = CASE WHEN stock_available - 1 > 0 THEN 1 ELSE 0 END
        WHERE id = NEW.product_id
          AND status = 'active'
          AND stock_available > 0;

        IF ROW_COUNT() <> 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock produk tidak tersedia.';
        END IF;
    END IF;

    IF NEW.status = 'active' AND NEW.approved_at IS NULL THEN
        SET NEW.approved_at = NOW();
    END IF;

    IF NEW.status = 'completed' AND NEW.completed_at IS NULL THEN
        SET NEW.completed_at = NOW();
    END IF;

    IF NEW.status IN ('cancelled', 'rejected') AND NEW.cancelled_at IS NULL THEN
        SET NEW.cancelled_at = NOW();
    END IF;
END$$

CREATE TRIGGER trg_rentals_before_update
BEFORE UPDATE ON rentals
FOR EACH ROW
BEGIN
    DECLARE old_reserves_stock TINYINT(1) DEFAULT 0;
    DECLARE new_reserves_stock TINYINT(1) DEFAULT 0;

    SET old_reserves_stock = IF(OLD.status IN ('pending', 'upcoming', 'active'), 1, 0);
    SET new_reserves_stock = IF(NEW.status IN ('pending', 'upcoming', 'active'), 1, 0);

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
              AND status = 'active'
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
              AND status = 'active'
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

    IF NEW.status = 'active' AND NEW.approved_at IS NULL THEN
        SET NEW.approved_at = COALESCE(OLD.approved_at, NOW());
    END IF;

    IF NEW.status = 'completed' AND NEW.completed_at IS NULL THEN
        SET NEW.completed_at = COALESCE(OLD.completed_at, NOW());
    END IF;

    IF NEW.status IN ('cancelled', 'rejected') AND NEW.cancelled_at IS NULL THEN
        SET NEW.cancelled_at = COALESCE(OLD.cancelled_at, NOW());
    END IF;

    IF NEW.status NOT IN ('cancelled', 'rejected') THEN
        SET NEW.cancel_reason = NULL;
    END IF;
END$$

CREATE TRIGGER trg_rentals_before_delete
BEFORE DELETE ON rentals
FOR EACH ROW
BEGIN
    IF OLD.status IN ('pending', 'upcoming', 'active') THEN
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
    IF NEW.status = 'completed' AND NEW.returned_at IS NULL THEN
        SET NEW.returned_at = NOW();
    END IF;
END$$

CREATE TRIGGER trg_returns_after_insert
AFTER INSERT ON returns
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' THEN
        UPDATE rentals
        SET status = 'completed',
            completed_at = COALESCE(NEW.returned_at, NOW())
        WHERE id = NEW.rental_id
          AND status <> 'completed';
    END IF;
END$$

CREATE TRIGGER trg_returns_before_update
BEFORE UPDATE ON returns
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND NEW.returned_at IS NULL THEN
        SET NEW.returned_at = COALESCE(OLD.returned_at, NOW());
    END IF;
END$$

CREATE TRIGGER trg_returns_after_update
AFTER UPDATE ON returns
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status <> 'completed' THEN
        UPDATE rentals
        SET status = 'completed',
            completed_at = COALESCE(NEW.returned_at, NOW())
        WHERE id = NEW.rental_id
          AND status <> 'completed';
    END IF;
END$$

DELIMITER ;
