-- ============================================================
-- TAKOYAKI SHOP MANAGEMENT SYSTEM
-- Database Schema (built from scratch)
-- Mapped to FR-01 to FR-30
-- ============================================================

CREATE DATABASE IF NOT EXISTS takoyaki_shop;
USE takoyaki_shop;

-- ============================================================
-- MODULE F: USER MANAGEMENT (FR-27 to FR-30)
-- ============================================================

-- FR-27: Register and Login
-- One table for both customers and admin/staff, separated by role
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'deactivated') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- FR-28: Customer Profile Management
-- Extra profile info for customers only (name, contact, default delivery address)
CREATE TABLE customer_profile (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    full_name VARCHAR(100),
    contact_number VARCHAR(20),
    default_address VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- FR-29: Manage Customer Accounts (handled via users table: status column)
-- FR-30: Admin Account Management (handled via users table: role = 'admin')
-- No separate table needed; business logic in admin/staff pages controls this.

-- ============================================================
-- MODULE A: MENU AND INGREDIENT MANAGEMENT (FR-01 to FR-06)
-- ============================================================

-- FR-01: Add and Edit Menu Items (flavors), FR-04: Set Item Availability
CREATE TABLE flavor (
    flavor_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    image VARCHAR(255),
    is_available TINYINT(1) NOT NULL DEFAULT 1
);

-- FR-02: Manage Sizes and Prices (per flavor)
CREATE TABLE flavor_size (
    size_id INT AUTO_INCREMENT PRIMARY KEY,
    flavor_id INT NOT NULL,
    size_label VARCHAR(30) NOT NULL,      -- e.g. '4 pcs', '8 pcs', '12 pcs'
    price DECIMAL(10,2) NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (flavor_id) REFERENCES flavor(flavor_id)
);

-- FR-03: Manage Add-ons, FR-04: Set Item Availability (applies to add-ons too)
CREATE TABLE addon (
    addon_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,          -- e.g. 'Extra Sauce', 'Mayo', 'Bonito Flakes'
    price DECIMAL(10,2) NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1
);

-- FR-05: Ingredient Stock Monitoring
CREATE TABLE ingredient (
    ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,          -- e.g. 'Octopus', 'Flour/Batter', 'Sauce'
    unit VARCHAR(20) NOT NULL,           -- e.g. 'pcs', 'g', 'ml'
    stock_qty DECIMAL(10,2) NOT NULL DEFAULT 0,
    low_stock_threshold DECIMAL(10,2) NOT NULL DEFAULT 0
);

-- FR-06: Manage Ingredient Usage per Size
-- How much of each ingredient a given size consumes
CREATE TABLE size_ingredient_usage (
    usage_id INT AUTO_INCREMENT PRIMARY KEY,
    size_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    qty_used DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (size_id) REFERENCES flavor_size(size_id),
    FOREIGN KEY (ingredient_id) REFERENCES ingredient(ingredient_id)
);

-- FR-06 (extended): add-ons may also use ingredients (e.g. extra sauce uses sauce stock)
CREATE TABLE addon_ingredient_usage (
    usage_id INT AUTO_INCREMENT PRIMARY KEY,
    addon_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    qty_used DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (addon_id) REFERENCES addon(addon_id),
    FOREIGN KEY (ingredient_id) REFERENCES ingredient(ingredient_id)
);

-- ============================================================
-- MODULE B: ORDERING, CART, AND DELIVERY (FR-07 to FR-13)
-- Note: the cart itself (FR-07, FR-08) lives in PHP $_SESSION,
-- not in the database. It only becomes DB rows at checkout.
-- ============================================================

-- FR-09: Calculate Delivery Fee / Order Cut-off settings (simple key-value table)
CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(100) NOT NULL
);

-- FR-11, FR-12: Checkout, Delivery Information, Order Status
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_type ENUM('dine-in', 'pickup', 'delivery') NOT NULL,
    delivery_address VARCHAR(255) DEFAULT NULL,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    pickup_time DATETIME DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('Pending', 'Preparing', 'Ready', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_profile(customer_id)
);

-- Each line item in an order (a specific flavor + size + quantity)
CREATE TABLE order_item (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    size_id INT NOT NULL,
    quantity INT NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,   -- price at time of order (snapshot)
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (size_id) REFERENCES flavor_size(size_id)
);

-- Add-ons selected for a specific order item
CREATE TABLE order_item_addon (
    order_item_id INT NOT NULL,
    addon_id INT NOT NULL,
    addon_price DECIMAL(10,2) NOT NULL,  -- price at time of order (snapshot)
    PRIMARY KEY (order_item_id, addon_id),
    FOREIGN KEY (order_item_id) REFERENCES order_item(order_item_id),
    FOREIGN KEY (addon_id) REFERENCES addon(addon_id)
);

-- ============================================================
-- MODULE D: PAYMENT RECORDING (FR-18 to FR-22)
-- ============================================================

CREATE TABLE payment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    amount_tendered DECIMAL(10,2) NOT NULL,
    change_amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

-- ============================================================
-- MODULE E: REPORTS (FR-23 to FR-26)
-- These are VIEWS, computed from the tables above — no new tables needed.
-- ============================================================

-- FR-23/24: Sales report + summary helper view
CREATE OR REPLACE VIEW order_totals AS
SELECT
    o.order_id,
    o.order_type,
    o.status,
    o.total_amount,
    o.delivery_fee,
    o.order_date
FROM orders o;

-- FR-25: Best-Selling Flavor Report helper view
CREATE OR REPLACE VIEW flavor_sales AS
SELECT
    f.flavor_id,
    f.name AS flavor_name,
    fs.size_id,
    fs.size_label,
    SUM(oi.quantity) AS total_qty_sold
FROM order_item oi
INNER JOIN flavor_size fs ON oi.size_id = fs.size_id
INNER JOIN flavor f ON fs.flavor_id = f.flavor_id
INNER JOIN orders o ON oi.order_id = o.order_id
WHERE o.status = 'Completed'
GROUP BY f.flavor_id, f.name, fs.size_id, fs.size_label;

-- FR-26: Ingredient Usage and Stock Report helper view
CREATE OR REPLACE VIEW ingredient_status AS
SELECT
    ingredient_id,
    name,
    unit,
    stock_qty,
    low_stock_threshold,
    CASE WHEN stock_qty <= low_stock_threshold THEN 'LOW STOCK' ELSE 'OK' END AS stock_status
FROM ingredient;

-- ============================================================
-- DEFAULT SETTINGS (for FR-09 delivery fee, FR-14 cut-off hours)
-- ============================================================
INSERT INTO settings (setting_key, setting_value) VALUES
('delivery_fee', '50.00'),
('min_order_for_delivery', '150.00'),
('opening_time', '10:00:00'),
('closing_time', '20:00:00');

-- ============================================================
-- SAMPLE DATA (optional, for testing — safe to delete later)
-- ============================================================

INSERT INTO flavor (name, description, is_available) VALUES
('Original', 'Classic takoyaki with octopus filling', 1),
('Cheese', 'Takoyaki topped with melted cheese', 1),
('Spicy', 'Takoyaki with spicy sauce drizzle', 1);

INSERT INTO flavor_size (flavor_id, size_label, price) VALUES
(1, '4 pcs', 60.00), (1, '8 pcs', 110.00), (1, '12 pcs', 150.00),
(2, '4 pcs', 70.00), (2, '8 pcs', 125.00), (2, '12 pcs', 170.00),
(3, '4 pcs', 65.00), (3, '8 pcs', 115.00), (3, '12 pcs', 155.00);

INSERT INTO addon (name, price) VALUES
('Extra Sauce', 10.00),
('Extra Mayo', 10.00),
('Bonito Flakes', 15.00);

INSERT INTO ingredient (name, unit, stock_qty, low_stock_threshold) VALUES
('Octopus', 'pcs', 200, 30),
('Batter', 'g', 5000, 500),
('Takoyaki Sauce', 'ml', 2000, 300),
('Mayo', 'ml', 1500, 300),
('Bonito Flakes', 'g', 800, 100);

-- Example usage: 4 pcs uses 4 octopus pieces + 100g batter (size_id 1 = Original 4pcs)
INSERT INTO size_ingredient_usage (size_id, ingredient_id, qty_used) VALUES
(1, 1, 4), (1, 2, 100), (1, 3, 20),
(2, 1, 8), (2, 2, 200), (2, 3, 40),
(3, 1, 12), (3, 2, 300), (3, 3, 60);
