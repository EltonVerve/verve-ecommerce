-- =========================================================
-- VERVE — Database Schema
-- A general-purpose e-commerce store (electronics, fashion,
-- home goods, anything you sell — not print-specific).
-- =========================================================
-- How to use this file:
-- 1. Open phpMyAdmin (or the mysql command line)
-- 2. Create a database called `verve` (or let this file do it)
-- 3. Import/run this file against that database
-- =========================================================

CREATE DATABASE IF NOT EXISTS verve CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE verve;

-- ---------------------------------------------------------
-- USERS
-- Every customer account. Passwords are NEVER stored as
-- plain text — only a bcrypt hash (see actions/register.php).
-- ---------------------------------------------------------
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(120)    NOT NULL,
    email           VARCHAR(150)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    phone           VARCHAR(30)     NULL,
    role            ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- ADDRESSES
-- A customer can save more than one shipping address.
-- ---------------------------------------------------------
CREATE TABLE addresses (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT             NOT NULL,
    label           VARCHAR(50)     DEFAULT 'Home',
    full_name       VARCHAR(120)    NOT NULL,
    line1           VARCHAR(200)    NOT NULL,
    line2           VARCHAR(200)    NULL,
    city            VARCHAR(100)    NOT NULL,
    state           VARCHAR(100)    NULL,
    postal_code     VARCHAR(20)     NULL,
    country         VARCHAR(100)    NOT NULL,
    phone           VARCHAR(30)     NULL,
    is_default      TINYINT(1)      DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- CATEGORIES
-- e.g. Electronics, Fashion, Home & Living, Beauty, Sports
-- ---------------------------------------------------------
CREATE TABLE categories (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)    NOT NULL,
    slug            VARCHAR(100)    NOT NULL UNIQUE,
    description     VARCHAR(255)    NULL,
    image           VARCHAR(255)    NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- PRODUCTS
-- price is the base selling price. compare_at_price lets us
-- show a strikethrough "was" price for sale items. stock is
-- the real inventory count — every product here is a stocked
-- item (this is a normal store, not made-to-order).
-- ---------------------------------------------------------
CREATE TABLE products (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    category_id       INT             NOT NULL,
    name              VARCHAR(150)    NOT NULL,
    slug              VARCHAR(150)    NOT NULL UNIQUE,
    description       TEXT            NULL,
    price             DECIMAL(10,2)   NOT NULL,
    compare_at_price  DECIMAL(10,2)   NULL,
    sku               VARCHAR(60)     NULL,
    image             VARCHAR(255)    NULL,
    stock             INT             NOT NULL DEFAULT 0,
    is_featured       TINYINT(1)      DEFAULT 0,
    is_active         TINYINT(1)      DEFAULT 1,
    created_at        TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- PRODUCT IMAGES
-- Extra gallery images beyond the main products.image.
-- ---------------------------------------------------------
CREATE TABLE product_images (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT          NOT NULL,
    filename    VARCHAR(255) NOT NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- PRODUCT VARIANT GROUPS
-- e.g. "Size", "Colour" — each product can have several.
-- ---------------------------------------------------------
CREATE TABLE product_option_groups (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    product_id      INT             NOT NULL,
    name            VARCHAR(80)     NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- PRODUCT VARIANT VALUES
-- e.g. under "Colour": "Black" (+$0.00), "Rose Gold" (+$5.00)
-- price_delta is ADDED to the base price when chosen.
-- ---------------------------------------------------------
CREATE TABLE product_option_values (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    group_id        INT             NOT NULL,
    label           VARCHAR(80)     NOT NULL,
    price_delta     DECIMAL(10,2)   NOT NULL DEFAULT 0,
    FOREIGN KEY (group_id) REFERENCES product_option_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- REVIEWS
-- Simple star rating + comment, tied to a product.
-- ---------------------------------------------------------
CREATE TABLE reviews (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    product_id      INT             NOT NULL,
    user_id         INT             NULL,
    author_name     VARCHAR(120)    NOT NULL,
    rating          TINYINT         NOT NULL,
    comment         TEXT            NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- WISHLIST
-- ---------------------------------------------------------
CREATE TABLE wishlist_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT             NOT NULL,
    product_id      INT             NOT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wish (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- CART ITEMS
-- One row per item a customer has added. Selected options
-- are stored as JSON, e.g. {"Colour":"Black","Size":"M"}.
-- Guests can also have a cart via session_id.
-- ---------------------------------------------------------
CREATE TABLE cart_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT             NULL,
    session_id      VARCHAR(100)    NULL,
    product_id      INT             NOT NULL,
    quantity        INT             NOT NULL DEFAULT 1,
    selected_options JSON           NULL,
    unit_price      DECIMAL(10,2)   NOT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- ORDERS
-- Created once a cart is checked out.
-- ---------------------------------------------------------
CREATE TABLE orders (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT             NULL,
    address_id          INT             NULL,
    contact_email       VARCHAR(254)    NULL,
    delivery_address    JSON            NULL,
    guest_access_hash   CHAR(64)        NULL,
    status              ENUM('pending','paid','processing','shipped','completed','cancelled')
                                        NOT NULL DEFAULT 'pending',
    subtotal            DECIMAL(10,2)   NOT NULL,
    shipping_fee        DECIMAL(10,2)   NOT NULL DEFAULT 0,
    discount_total       DECIMAL(10,2)   NOT NULL DEFAULT 0,
    total               DECIMAL(10,2)   NOT NULL,
    payment_method      VARCHAR(50)     NULL,
    coupon_code         VARCHAR(40)     NULL,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (address_id) REFERENCES addresses(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- ORDER ITEMS
-- A snapshot of what was bought.
-- ---------------------------------------------------------
CREATE TABLE order_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT             NOT NULL,
    product_id      INT             NULL,
    product_name    VARCHAR(150)    NOT NULL,
    quantity        INT             NOT NULL,
    selected_options JSON           NULL,
    unit_price      DECIMAL(10,2)   NOT NULL,
    line_total      DECIMAL(10,2)   NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- COUPONS
-- Simple percentage or flat discount codes for checkout.
-- ---------------------------------------------------------
CREATE TABLE coupons (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(40)     NOT NULL UNIQUE,
    type            ENUM('percent','flat') NOT NULL DEFAULT 'percent',
    value           DECIMAL(10,2)   NOT NULL,
    is_active       TINYINT(1)      DEFAULT 1,
    expires_at      DATE            NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- CONTACT MESSAGES
-- Enquiries submitted through the public contact form.
-- ---------------------------------------------------------
CREATE TABLE contact_messages (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT              NULL,
    enquiry_type     ENUM('order_issue','product_question','returns','general') NOT NULL,
    name             VARCHAR(120)     NOT NULL,
    email            VARCHAR(254)     NOT NULL,
    phone            VARCHAR(40)      NULL,
    order_reference  VARCHAR(80)      NULL,
    message          TEXT             NOT NULL,
    ip_hash          CHAR(64)         NOT NULL,
    status           ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
    created_at       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contact_status_created (status, created_at),
    CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- PASSWORD RESETS
-- ---------------------------------------------------------
CREATE TABLE password_resets (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT             NOT NULL,
    token           VARCHAR(64)     NOT NULL UNIQUE,
    expires_at      DATETIME        NOT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- SAMPLE DATA — so the site isn't empty while you build it
-- =========================================================

INSERT INTO categories (name, slug, description) VALUES
('Electronics', 'electronics', 'Phones, audio, and everyday gadgets'),
('Fashion', 'fashion', 'Clothing, shoes and accessories'),
('Home & Living', 'home-living', 'Furniture, decor and kitchenware'),
('Beauty & Personal Care', 'beauty', 'Skincare, haircare and grooming'),
('Sports & Outdoors', 'sports', 'Fitness gear and outdoor equipment');

INSERT INTO products (category_id, name, slug, description, price, compare_at_price, sku, image, stock, is_featured) VALUES
(1, 'Wireless Over-Ear Headphones', 'wireless-over-ear-headphones', 'Noise-isolating over-ear headphones with 30-hour battery life and a folding design for travel.', 79.99, 99.99, 'ELE-1001', 'headphones.png', 42, 1),
(1, 'Smart Fitness Watch', 'smart-fitness-watch', 'Track steps, heart rate and sleep with a bright always-on display and 7-day battery.', 129.00, NULL, 'ELE-1002', 'smartwatch.png', 30, 1),
(1, 'Portable Bluetooth Speaker', 'portable-bluetooth-speaker', 'Compact, waterproof speaker with rich bass, great for outdoor use.', 45.50, 59.00, 'ELE-1003', 'speaker.png', 60, 0),
(2, 'Classic Denim Jacket', 'classic-denim-jacket', 'A timeless denim jacket that pairs with almost anything in your closet.', 54.00, NULL, 'FAS-2001', 'denim-jacket.png', 25, 1),
(2, 'Everyday Sneakers', 'everyday-sneakers', 'Lightweight, breathable sneakers built for all-day comfort.', 62.00, 75.00, 'FAS-2002', 'sneakers.png', 50, 0),
(3, 'Ceramic Pour-Over Coffee Set', 'ceramic-pour-over-coffee-set', 'A minimalist ceramic dripper and carafe set for slow, better coffee at home.', 38.00, NULL, 'HOM-3001', 'coffee-set.png', 20, 1),
(3, 'Linen Throw Pillow Cover', 'linen-throw-pillow-cover', 'Soft, breathable linen cover that instantly refreshes any sofa or bed.', 18.00, NULL, 'HOM-3002', 'pillow-cover.png', 80, 0),
(4, 'Vitamin C Serum', 'vitamin-c-serum', 'Brightening daily serum with vitamin C and hyaluronic acid.', 24.00, 29.00, 'BEA-4001', 'serum.png', 70, 1),
(5, 'Foldable Yoga Mat', 'foldable-yoga-mat', 'Travel-friendly, non-slip yoga mat that folds down to fit in any bag.', 32.00, NULL, 'SPO-5001', 'yoga-mat.png', 45, 0);

-- Variants for Denim Jacket (product_id = 4)
INSERT INTO product_option_groups (product_id, name) VALUES (4, 'Size');
SET @g1 = LAST_INSERT_ID();
INSERT INTO product_option_values (group_id, label, price_delta) VALUES
(@g1, 'S', 0.00), (@g1, 'M', 0.00), (@g1, 'L', 0.00), (@g1, 'XL', 3.00);

-- Variants for Sneakers (product_id = 5)
INSERT INTO product_option_groups (product_id, name) VALUES (5, 'Size');
SET @g2 = LAST_INSERT_ID();
INSERT INTO product_option_values (group_id, label, price_delta) VALUES
(@g2, 'UK 6', 0.00), (@g2, 'UK 7', 0.00), (@g2, 'UK 8', 0.00), (@g2, 'UK 9', 0.00), (@g2, 'UK 10', 0.00);

INSERT INTO product_option_groups (product_id, name) VALUES (5, 'Colour');
SET @g3 = LAST_INSERT_ID();
INSERT INTO product_option_values (group_id, label, price_delta) VALUES
(@g3, 'Black', 0.00), (@g3, 'White', 0.00), (@g3, 'Grey', 2.00);

INSERT INTO categories (name, slug, description, image) VALUES
('Books & Stationery', 'books-stationery', 'Good reads, notebooks and desk essentials', 'category-books-stationery.png'),
('Bags & Travel', 'bags-travel', 'Backpacks, luggage and travel essentials', 'category-bags-travel.png'),
('Toys & Games', 'toys-games', 'Playtime favourites and games to share', 'category-toys-games.png');

UPDATE categories SET image = CONCAT('category-', slug, '.png')
WHERE slug IN ('electronics', 'fashion', 'home-living', 'beauty', 'sports') AND image IS NULL;

CREATE TABLE IF NOT EXISTS customer_sessions (
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    expires_at BIGINT NOT NULL DEFAULT 0,
    user_id INT NOT NULL,
    password_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    INDEX (user_id)
);

INSERT INTO coupons (code, type, value, is_active) VALUES
('WELCOME10', 'percent', 10.00, 1);

-- Sample admin account: email admin@verve.test / password Admin123!
-- (hash generated for 'Admin123!')
INSERT INTO users (full_name, email, password_hash, role) VALUES
('Store Admin', 'admin@verve.test', '$2y$10$e9xHuKmGAshiVPtbdSdqwu1cdAzTvB8oGbrTu3QEJvwA6bnOTYAaa', 'admin');
