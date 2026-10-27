CREATE DATABASE IF NOT EXISTS takoyaki_shop;
USE takoyaki_shop;


CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'deactivated') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE customer_profile (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    full_name VARCHAR(100),
    contact_number VARCHAR(20),
    default_address VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);


CREATE TABLE flavor (
    flavor_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    image VARCHAR(255),
    is_available TINYINT(1) NOT NULL DEFAULT 1
);


CREATE TABLE flavor_size (
    size_id INT AUTO_INCREMENT PRIMARY KEY,
    flavor_id INT NOT NULL,
    size_label VARCHAR(30) NOT NULL,      
    price DECIMAL(10,2) NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (flavor_id) REFERENCES flavor(flavor_id)
);


CREATE TABLE addon (
    addon_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,          
    price DECIMAL(10,2) NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1
);


CREATE TABLE ingredient (
    ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,          -- e.g. 'Octopus', 'Flour/Batter', 'Sauce'
    unit VARCHAR(20) NOT NULL,           -- e.g. 'pcs', 'g', 'ml'
    stock_qty DECIMAL(10,2) NOT NULL DEFAULT 0,
    low_stock_threshold DECIMAL(10,2) NOT NULL DEFAULT 0
);


CREATE TABLE size_ingredient_usage (
    usage_id INT AUTO_INCREMENT PRIMARY KEY,
    size_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    qty_used DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (size_id) REFERENCES flavor_size(size_id),
    FOREIGN KEY (ingredient_id) REFERENCES ingredient(ingredient_id)
);

CREATE TABLE addon_ingredient_usage (
    usage_id INT AUTO_INCREMENT PRIMARY KEY,
    addon_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    qty_used DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (addon_id) REFERENCES addon(addon_id),
    FOREIGN KEY (ingredient_id) REFERENCES ingredient(ingredient_id)
);


CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(100) NOT NULL
);


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


CREATE TABLE order_item (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    size_id INT NOT NULL,
    quantity INT NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,   -- price at time of order (snapshot)
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (size_id) REFERENCES flavor_size(size_id)
);

CREATE TABLE order_item_addon (
    order_item_id INT NOT NULL,
    addon_id INT NOT NULL,
    addon_price DECIMAL(10,2) NOT NULL,  -- price at time of order (snapshot)
    PRIMARY KEY (order_item_id, addon_id),
    FOREIGN KEY (order_item_id) REFERENCES order_item(order_item_id),
    FOREIGN KEY (addon_id) REFERENCES addon(addon_id)
);



CREATE TABLE payment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    amount_tendered DECIMAL(10,2) NOT NULL,
    change_amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);



CREATE OR REPLACE VIEW order_totals AS
SELECT
    o.order_id,
    o.order_type,
    o.status,
    o.total_amount,
    o.delivery_fee,
    o.order_date
FROM orders o;


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


CREATE OR REPLACE VIEW ingredient_status AS
SELECT
    ingredient_id,
    name,
    unit,
    stock_qty,
    low_stock_threshold,
    CASE WHEN stock_qty <= low_stock_threshold THEN 'LOW STOCK' ELSE 'OK' END AS stock_status
FROM ingredient;


INSERT INTO settings (setting_key, setting_value) VALUES
('delivery_fee', '50.00'),
('min_order_for_delivery', '150.00'),
('opening_time', '10:00:00'),
('closing_time', '20:00:00');



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

INSERT INTO size_ingredient_usage (size_id, ingredient_id, qty_used) VALUES
(1, 1, 4), (1, 2, 100), (1, 3, 20),
(2, 1, 8), (2, 2, 200), (2, 3, 40),
(3, 1, 12), (3, 2, 300), (3, 3, 60);

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT DEFAULT 5,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
