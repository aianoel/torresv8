-- Create RFID cards table for POS payment system
CREATE TABLE IF NOT EXISTS rfid_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_number VARCHAR(50) NOT NULL UNIQUE,
    card_name VARCHAR(100) NOT NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Create sales table (missing from main schema)
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_number VARCHAR(50) NOT NULL UNIQUE,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'rfid') NOT NULL,
    rfid_card_id INT NULL,
    cashier_id INT NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rfid_card_id) REFERENCES rfid_cards(id),
    FOREIGN KEY (cashier_id) REFERENCES users(id)
);

-- Create sale_items table (missing from main schema)
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Create products table (if not exists)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INT,
    stock_quantity INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create categories table (if not exists)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create RFID transaction log table
CREATE TABLE IF NOT EXISTS rfid_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfid_card_id INT NOT NULL,
    transaction_type ENUM('load', 'payment', 'refund') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    sale_id INT NULL,
    processed_by INT NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (rfid_card_id) REFERENCES rfid_cards(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- Insert sample categories
INSERT IGNORE INTO categories (name, description) VALUES
('Food', 'Food items and meals'),
('Beverages', 'Drinks and beverages'),
('Snacks', 'Light snacks and appetizers'),
('Desserts', 'Sweet treats and desserts');

-- Insert sample products
INSERT IGNORE INTO products (name, description, price, category_id, stock_quantity) VALUES
('Fried Rice', 'Delicious fried rice with vegetables', 150.00, 1, 50),
('Grilled Chicken', 'Tender grilled chicken breast', 250.00, 1, 30),
('Soft Drinks', 'Assorted soft drinks', 50.00, 2, 100),
('Coffee', 'Freshly brewed coffee', 80.00, 2, 75),
('Chips', 'Crispy potato chips', 35.00, 3, 80),
('Ice Cream', 'Vanilla ice cream', 120.00, 4, 25);

-- Add indexes for better performance
CREATE INDEX idx_rfid_card_number ON rfid_cards(card_number);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sales_cashier ON sales(cashier_id);
CREATE INDEX idx_rfid_transactions_card ON rfid_transactions(rfid_card_id);
CREATE INDEX idx_rfid_transactions_date ON rfid_transactions(transaction_date);