USE quickbite_db;

-- Safe migration for the earlier QuickBite database structure.
DELIMITER $$
CREATE PROCEDURE quickbite_add_missing_columns()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='pickup_time') THEN
        ALTER TABLE orders ADD COLUMN pickup_time DATETIME NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='payment_method') THEN
        ALTER TABLE orders ADD COLUMN payment_method VARCHAR(60) NOT NULL DEFAULT 'Cash on Pickup';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='payment_status') THEN
        ALTER TABLE orders ADD COLUMN payment_status VARCHAR(60) NOT NULL DEFAULT 'Pending';
    END IF;
END$$
DELIMITER ;
CALL quickbite_add_missing_columns();
DROP PROCEDURE quickbite_add_missing_columns;

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

INSERT INTO admin (username, password)
SELECT 'admin', '$2y$12$UkvSkvGUFHS4wS2W9LuI2uxHMc.itvhqsAI4T.PqXo61Eipq6192C'
WHERE NOT EXISTS (SELECT 1 FROM admin WHERE username = 'admin');
