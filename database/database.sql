CREATE DATABASE IF NOT EXISTS quickbite_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quickbite_db;

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS food (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(50),
    available TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    UNIQUE KEY uq_cart_student_food (student_id, food_id),
    CONSTRAINT fk_cart_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_food FOREIGN KEY (food_id) REFERENCES food(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) DEFAULT 'Pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pickup_time DATETIME NULL,
    payment_method VARCHAR(60) NOT NULL DEFAULT 'Cash on Pickup',
    payment_status VARCHAR(60) NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_orders_student FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id INT NULL,
    food_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_food FOREIGN KEY (food_id) REFERENCES food(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Demo administrator: username admin, password admin123. Change this after first login.
INSERT INTO admin (username, password)
SELECT 'admin', '$2y$12$UkvSkvGUFHS4wS2W9LuI2uxHMc.itvhqsAI4T.PqXo61Eipq6192C'
WHERE NOT EXISTS (SELECT 1 FROM admin WHERE username = 'admin');

-- Add sample menu items only when the food table is empty.
INSERT INTO food (name, description, price, category, image)
SELECT 'Pizza', 'Delicious cheese pizza', 120.00, 'Snacks', 'images/pizza.jpg'
WHERE NOT EXISTS (SELECT 1 FROM food WHERE name = 'Pizza');
INSERT INTO food (name, description, price, category, image)
SELECT 'Burger', 'Fresh veg burger', 80.00, 'Snacks', 'images/burger.jpg'
WHERE NOT EXISTS (SELECT 1 FROM food WHERE name = 'Burger');
INSERT INTO food (name, description, price, category, image)
SELECT 'Sandwich', 'Grilled sandwich', 60.00, 'Snacks', 'images/sandwich.jpg'
WHERE NOT EXISTS (SELECT 1 FROM food WHERE name = 'Sandwich');
INSERT INTO food (name, description, price, category, image)
SELECT 'Cold Coffee', 'Chilled cold coffee', 70.00, 'Drinks', 'images/cold coffee.jpg'
WHERE NOT EXISTS (SELECT 1 FROM food WHERE name = 'Cold Coffee');
