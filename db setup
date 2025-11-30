-- Canteen Ordering System Database
-- Run this in phpMyAdmin (XAMPP)

CREATE DATABASE IF NOT EXISTS canteen_system;
USE canteen_system;

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Food Items Table
CREATE TABLE food_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50),
    image_url VARCHAR(255),
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_code VARCHAR(5) UNIQUE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order Items Table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    food_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE CASCADE
);

-- Insert Test Accounts (passwords are hashed - all passwords are "password123")
INSERT INTO users (username, password, full_name, role) VALUES
('admin1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin'),
('teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Teacher', 'teacher'),
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Student', 'student');

-- Insert Sample Food Items
INSERT INTO food_items (name, description, price, category, available) VALUES
('Chicken Burger', 'Juicy grilled chicken burger with lettuce and mayo', 5.99, 'Main Course', TRUE),
('Veggie Pizza', 'Fresh vegetables on crispy thin crust', 7.50, 'Main Course', TRUE),
('Caesar Salad', 'Crisp romaine lettuce with caesar dressing', 4.25, 'Salad', TRUE),
('French Fries', 'Golden crispy french fries', 2.50, 'Sides', TRUE),
('Chocolate Cake', 'Rich chocolate cake with frosting', 3.75, 'Dessert', TRUE),
('Orange Juice', 'Freshly squeezed orange juice', 2.00, 'Beverages', TRUE),
('Coffee', 'Hot brewed coffee', 1.50, 'Beverages', TRUE),
('Fish and Chips', 'Crispy fried fish with chips', 8.99, 'Main Course', TRUE),
('Pasta Carbonara', 'Creamy pasta with bacon', 6.75, 'Main Course', TRUE),
('Ice Cream', 'Vanilla ice cream with toppings', 2.99, 'Dessert', TRUE);