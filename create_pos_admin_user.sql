-- Create a POS Admin user for testing
-- Password: admin123 (hashed)

INSERT INTO users (username, password, email, role, first_name, last_name) 
VALUES (
    'posadmin', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'posadmin@hotel.com', 
    'pos_admin', 
    'POS', 
    'Administrator'
);

-- Verify the user was created
SELECT id, username, email, role, first_name, last_name FROM users WHERE role = 'pos_admin';