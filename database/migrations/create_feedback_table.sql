-- Create feedback table for customer feedback
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_code_id INT NOT NULL,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    feedback_text TEXT,
    service_rating INT CHECK (service_rating >= 1 AND service_rating <= 5),
    cleanliness_rating INT CHECK (cleanliness_rating >= 1 AND cleanliness_rating <= 5),
    amenities_rating INT CHECK (amenities_rating >= 1 AND amenities_rating <= 5),
    overall_experience TEXT,
    would_recommend BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE CASCADE,
    INDEX idx_qr_code_id (qr_code_id),
    INDEX idx_rating (rating),
    INDEX idx_created_at (created_at)
);