-- ============================================
-- ShopSystem Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS shopdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shopdb;

-- Users table (both admin and customer)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    status ENUM('active','disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    qty INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================
-- Seed Data
-- ============================================

-- Default admin (password: admin123)
INSERT INTO users (fullname, email, phone, password, role) VALUES
('Admin User', 'admin@shop.com', '012-345-6789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample products
INSERT INTO products (name, qty, price, image_url) VALUES
('Wireless Headphones', 50, 79.99, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&q=80'),
('Mechanical Keyboard', 30, 129.99, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=400&q=80'),
('USB-C Hub', 100, 39.99, 'https://images.unsplash.com/photo-1625948515291-69613efd103f?w=400&q=80'),
('Laptop Stand', 45, 49.99, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=400&q=80'),
('Webcam HD 1080p', 25, 89.99, 'https://images.unsplash.com/photo-1587826080692-f439cd0b70da?w=400&q=80'),
('Mouse Pad XL', 80, 19.99, 'https://images.unsplash.com/photo-1616274553766-5c7c0f4e8e3a?w=400&q=80');


use shopdb;
SELECT * FROM users;
SELECT * FROM products;
SELECT * FROM orders;
SELECT * FROM order_items;