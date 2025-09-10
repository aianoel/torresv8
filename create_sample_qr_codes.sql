-- Create sample QR codes for landing page display
-- Note: These will be inserted with user_id = 1 (admin user)

INSERT INTO qr_codes (code, purpose, data, created_by, is_active, display_on_landing) VALUES
('QR001WIFI', 'wifi', '{"type":"wifi","title":"Free WiFi","content":"WIFI:T:WPA;S:TorresFarm_Guest;P:welcome2024;;"}', 1, 1, 1),
('QR002MENU', 'menu', '{"type":"menu","title":"Restaurant Menu","content":"http://localhost:8000/public/menu.php"}', 1, 1, 1),
('QR003FEEDBACK', 'feedback', '{"type":"feedback","title":"Leave Feedback","content":"http://localhost:8000/public/feedback.php"}', 1, 1, 1),
('QR004CONTACT', 'contact', '{"type":"contact","title":"Contact Info","content":"BEGIN:VCARD\nVERSION:3.0\nFN:Torres Farm Hotel & Resort\nORG:Torres Farm\nTEL:+63-xxx-xxx-xxxx\nEMAIL:info@torresfarm.com\nURL:http://localhost:8000\nADR:;;Naic;Cavite;;4110;Philippines\nEND:VCARD"}', 1, 1, 1);

-- Update the QR codes to have proper expiration dates (optional)
-- UPDATE qr_codes SET expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR) WHERE purpose IN ('wifi', 'menu', 'contact');
-- UPDATE qr_codes SET expires_at = NULL WHERE purpose = 'feedback'; -- Feedback never expires