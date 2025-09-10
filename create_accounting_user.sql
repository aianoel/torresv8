-- Create accounting user for testing
INSERT INTO users (username, password, email, role, first_name, last_name) 
VALUES ('accounting', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'accounting@hotel.com', 'accounting', 'Accounting', 'User');

-- Note: Password is 'password' (same as admin user)