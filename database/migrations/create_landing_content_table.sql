-- Create landing_content table for managing landing page content
CREATE TABLE IF NOT EXISTS landing_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_key VARCHAR(100) NOT NULL UNIQUE,
    content_value TEXT NOT NULL,
    content_type ENUM('text', 'html', 'image') DEFAULT 'text',
    section VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section),
    INDEX idx_content_key (content_key)
);

-- Insert default content for the landing page
INSERT INTO landing_content (content_key, content_value, content_type, section) VALUES
-- Hero Section
('hero_title', 'Luxury<br><span style="color: var(--primary-gold);">Redefined</span>', 'html', 'hero'),
('hero_subtitle', 'Experience unparalleled elegance and comfort in our world-class accommodations', 'text', 'hero'),
('hero_background_image', 'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80', 'image', 'hero'),

-- Features Section
('feature_1_title', '5-Star Luxury', 'text', 'features'),
('feature_1_description', 'World-class amenities and exceptional service', 'text', 'features'),
('feature_1_icon', 'bi bi-star-fill', 'text', 'features'),
('feature_2_title', 'Prime Location', 'text', 'features'),
('feature_2_description', '33 miniature attractions across 14 hectares', 'text', 'features'),
('feature_2_icon', 'bi bi-geo-alt-fill', 'text', 'features'),
('feature_3_title', 'Safe & Secure', 'text', 'features'),
('feature_3_description', 'Your safety and comfort is our priority', 'text', 'features'),
('feature_3_icon', 'bi bi-shield-check', 'text', 'features'),
('feature_4_title', '24/7 Service', 'text', 'features'),
('feature_4_description', 'Round-the-clock assistance and support', 'text', 'features'),
('feature_4_icon', 'bi bi-clock', 'text', 'features'),

-- About Section
('about_title', 'About <span class="highlight">Torres Farm</span>', 'html', 'about'),
('about_lead', 'Torres Farm Hotel & Resort is the happiest place in Cavite, located in Naic. Featuring 33 miniature attractions of different countries, spanning over 14 hectares.', 'text', 'about'),
('about_description', 'Experience the magic of world travel without leaving the Philippines. Our resort combines luxury accommodations with unique cultural experiences, making every stay unforgettable.', 'text', 'about'),
('about_image', 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2080&q=80', 'image', 'about'),
('about_stat_1_number', '33', 'text', 'about'),
('about_stat_1_label', 'Miniature Attractions', 'text', 'about'),
('about_stat_2_number', '14', 'text', 'about'),
('about_stat_2_label', 'Hectares', 'text', 'about'),
('about_stat_3_number', '5★', 'text', 'about'),
('about_stat_3_label', 'World-Class Service', 'text', 'about'),

-- Testimonials Section
('testimonials_title', 'What Our <span class="highlight">Guests</span> Say', 'html', 'testimonials'),
('testimonial_1_text', 'This booking system has transformed how we manage reservations. Our no-show rate dropped by 40%!', 'text', 'testimonials'),
('testimonial_1_author', 'Sarah Johnson', 'text', 'testimonials'),
('testimonial_1_role', 'Hotel Manager', 'text', 'testimonials'),
('testimonial_1_rating', '5', 'text', 'testimonials'),
('testimonial_2_text', 'The intuitive interface makes scheduling a breeze. Our customers love the seamless experience.', 'text', 'testimonials'),
('testimonial_2_author', 'Michael Chen', 'text', 'testimonials'),
('testimonial_2_role', 'Event Coordinator', 'text', 'testimonials'),
('testimonial_2_rating', '4', 'text', 'testimonials'),
('testimonial_3_text', 'Revenue increased by 25% after implementing this system. Highly recommend!', 'text', 'testimonials'),
('testimonial_3_author', 'Emma Rodriguez', 'text', 'testimonials'),
('testimonial_3_role', 'Business Owner', 'text', 'testimonials'),
('testimonial_3_rating', '5', 'text', 'testimonials'),

-- Footer Section
('footer_title', 'Torres Farm Hotel & Resort', 'text', 'footer'),
('footer_description', 'Experience the magic of world travel without leaving the Philippines.', 'text', 'footer'),
('footer_address', 'Naic, Cavite, Philippines', 'text', 'footer'),
('footer_phone', '+63 (xxx) xxx-xxxx', 'text', 'footer'),
('footer_email', 'info@torresfarm.com', 'text', 'footer'),

-- Navigation
('site_title', 'Torres Farm Hotel & Resort', 'text', 'navigation'),
('site_logo', '../assets/images/logo.png', 'image', 'navigation');