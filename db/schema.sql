-- Database schema for Hotel Management System

-- Create database
CREATE DATABASE IF NOT EXISTS hotel_management;
USE hotel_management;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'pos_admin', 'pos_cashier', 'frontdesk', 'housekeeping', 'accounting', 'hr') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    room_type VARCHAR(50) NOT NULL,
    description TEXT,
    capacity INT NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Guests table
CREATE TABLE guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    id_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    adults INT NOT NULL DEFAULT 1,
    children INT NOT NULL DEFAULT 0,
    status ENUM('confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'confirmed',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guests(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'bank_transfer') NOT NULL,
    payment_status ENUM('pending', 'completed', 'refunded') DEFAULT 'pending',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    pay_period_start DATE NOT NULL,
    pay_period_end DATE NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    overtime_hours DECIMAL(5,2) DEFAULT 0,
    overtime_rate DECIMAL(10,2) DEFAULT 0,
    overtime_pay DECIMAL(10,2) DEFAULT 0,
    allowances DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    tax_deduction DECIMAL(10,2) DEFAULT 0,
    gross_pay DECIMAL(10,2) NOT NULL,
    net_pay DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Housekeeping table
CREATE TABLE housekeeping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    staff_id INT NOT NULL,
    status ENUM('clean', 'dirty', 'in_progress', 'maintenance') DEFAULT 'clean',
    notes TEXT,
    last_cleaned TIMESTAMP NULL,
    next_cleaning TIMESTAMP NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (staff_id) REFERENCES users(id)
);

-- HR tables
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2) NOT NULL,
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    check_in TIMESTAMP NULL,
    check_out TIMESTAMP NULL,
    status ENUM('present', 'absent', 'late', 'on_leave') NOT NULL,
    notes TEXT,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    type ENUM('vacation', 'sick', 'personal', 'other') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reason TEXT NOT NULL,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- Accounting tables
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    invoice_number VARCHAR(20) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    created_by INT NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    approved_by INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- POS tables
CREATE TABLE pos_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pos_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(20) NOT NULL UNIQUE,
    total_amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card') NOT NULL,
    processed_by INT NOT NULL,
    booking_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (processed_by) REFERENCES users(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE pos_transaction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES pos_transactions(id),
    FOREIGN KEY (product_id) REFERENCES pos_products(id)
);

-- QR Codes table
CREATE TABLE qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(255) NOT NULL UNIQUE,
    purpose VARCHAR(100) NOT NULL,
    data TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_on_landing BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert initial admin user
INSERT INTO users (username, password, email, role, first_name, last_name) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@hotel.com', 'admin', 'Admin', 'User');

-- Insert sample rooms
INSERT INTO rooms (room_number, room_type, description, capacity, price_per_night) VALUES
('101', 'Standard', 'Standard room with queen bed', 2, 99.99),
('102', 'Standard', 'Standard room with queen bed', 2, 99.99),
('201', 'Deluxe', 'Deluxe room with king bed and view', 2, 149.99),
('202', 'Deluxe', 'Deluxe room with king bed and view', 2, 149.99),
('301', 'Suite', 'Luxury suite with separate living area', 4, 249.99);

-- Settings table for system configuration
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample guests
INSERT INTO guests (first_name, last_name, email, phone, address) VALUES
('John', 'Doe', 'john.doe@email.com', '+1234567890', '123 Main St, City, State'),
('Jane', 'Smith', 'jane.smith@email.com', '+1234567891', '456 Oak Ave, City, State'),
('Mike', 'Johnson', 'mike.johnson@email.com', '+1234567892', '789 Pine Rd, City, State');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('hotel_name', 'Torres Hotel', 'Name of the hotel'),
('hotel_address', '123 Main Street, City, State', 'Hotel address'),
('hotel_phone', '+1234567890', 'Hotel phone number'),
('hotel_email', 'info@torreshotel.com', 'Hotel email address'),
('tax_rate', '10.00', 'Tax rate percentage'),
('currency', 'USD', 'Currency code'),
('pos_receipt_footer', 'Thank you for your business!', 'Footer text for POS receipts');