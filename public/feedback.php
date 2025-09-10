<?php
session_start();
require_once '../includes/config.php';

$qr_id = $_GET['qr_id'] ?? null;
$message = '';
$error = '';

// Validate QR code
if (!$qr_id) {
    $error = 'Invalid QR code. Please scan a valid feedback QR code.';
} else {
    // Check if QR code exists and is active
    $stmt = $conn->prepare("SELECT * FROM qr_codes WHERE id = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->bind_param("i", $qr_id);
    $stmt->execute();
    $qr_result = $stmt->get_result();
    
    if ($qr_result->num_rows === 0) {
        $error = 'QR code not found or has expired.';
    } else {
        $qr_data = $qr_result->fetch_assoc();
        $qr_info = json_decode($qr_data['data'], true);
    }
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $feedback_text = trim($_POST['feedback_text'] ?? '');
    $service_rating = (int)($_POST['service_rating'] ?? 0);
    $cleanliness_rating = (int)($_POST['cleanliness_rating'] ?? 0);
    $amenities_rating = (int)($_POST['amenities_rating'] ?? 0);
    $overall_experience = trim($_POST['overall_experience'] ?? '');
    $would_recommend = isset($_POST['would_recommend']) ? 1 : 0;
    
    // Validate required fields
    if ($rating < 1 || $rating > 5) {
        $error = 'Please provide a valid overall rating (1-5 stars).';
    } elseif (empty($feedback_text)) {
        $error = 'Please provide your feedback.';
    } else {
        // Insert feedback
        $stmt = $conn->prepare("INSERT INTO feedback (qr_code_id, customer_name, customer_email, rating, feedback_text, service_rating, cleanliness_rating, amenities_rating, overall_experience, would_recommend) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issisisssi", $qr_id, $customer_name, $customer_email, $rating, $feedback_text, $service_rating, $cleanliness_rating, $amenities_rating, $overall_experience, $would_recommend);
        
        if ($stmt->execute()) {
            $message = 'Thank you for your feedback! We appreciate your time and input.';
        } else {
            $error = 'Failed to submit feedback. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Feedback | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: <?php echo APP_THEME_COLOR; ?>;
            --primary-gold: #d4af37;
        }
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a1a2e 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .feedback-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .feedback-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .feedback-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-gold));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .feedback-body {
            padding: 2rem;
        }
        .star-rating {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star.active,
        .star:hover {
            color: var(--primary-gold);
        }
        .rating-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-gold));
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            color: white;
        }
        .success-message {
            text-align: center;
            padding: 3rem;
        }
        .success-icon {
            font-size: 4rem;
            color: var(--primary-gold);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="feedback-container">
        <div class="feedback-card">
            <div class="feedback-header">
                <h1><i class="bi bi-chat-heart me-2"></i>We Value Your Feedback</h1>
                <p class="mb-0">Help us improve your experience at <?php echo APP_NAME; ?></p>
            </div>
            
            <div class="feedback-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php elseif ($message): ?>
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3>Thank You!</h3>
                        <p class="text-muted"><?php echo $message; ?></p>
                        <a href="landing.php" class="btn btn-submit mt-3">
                            <i class="bi bi-house me-2"></i>Return to Home
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <!-- Overall Rating -->
                        <div class="rating-section">
                            <h4><i class="bi bi-star me-2"></i>Overall Rating</h4>
                            <p class="text-muted">How would you rate your overall experience?</p>
                            <div class="star-rating" data-rating="rating">
                                <span class="star" data-value="1">★</span>
                                <span class="star" data-value="2">★</span>
                                <span class="star" data-value="3">★</span>
                                <span class="star" data-value="4">★</span>
                                <span class="star" data-value="5">★</span>
                            </div>
                            <input type="hidden" name="rating" id="rating" required>
                        </div>

                        <!-- Detailed Ratings -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="rating-section">
                                    <h6><i class="bi bi-people me-2"></i>Service Quality</h6>
                                    <div class="star-rating" data-rating="service_rating">
                                        <span class="star" data-value="1">★</span>
                                        <span class="star" data-value="2">★</span>
                                        <span class="star" data-value="3">★</span>
                                        <span class="star" data-value="4">★</span>
                                        <span class="star" data-value="5">★</span>
                                    </div>
                                    <input type="hidden" name="service_rating" id="service_rating">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="rating-section">
                                    <h6><i class="bi bi-house-check me-2"></i>Cleanliness</h6>
                                    <div class="star-rating" data-rating="cleanliness_rating">
                                        <span class="star" data-value="1">★</span>
                                        <span class="star" data-value="2">★</span>
                                        <span class="star" data-value="3">★</span>
                                        <span class="star" data-value="4">★</span>
                                        <span class="star" data-value="5">★</span>
                                    </div>
                                    <input type="hidden" name="cleanliness_rating" id="cleanliness_rating">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="rating-section">
                                    <h6><i class="bi bi-wifi me-2"></i>Amenities</h6>
                                    <div class="star-rating" data-rating="amenities_rating">
                                        <span class="star" data-value="1">★</span>
                                        <span class="star" data-value="2">★</span>
                                        <span class="star" data-value="3">★</span>
                                        <span class="star" data-value="4">★</span>
                                        <span class="star" data-value="5">★</span>
                                    </div>
                                    <input type="hidden" name="amenities_rating" id="amenities_rating">
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="customer_name" class="form-label">Your Name (Optional)</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Enter your name">
                            </div>
                            <div class="col-md-6">
                                <label for="customer_email" class="form-label">Email (Optional)</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email" placeholder="Enter your email">
                            </div>
                        </div>

                        <!-- Feedback Text -->
                        <div class="mb-3">
                            <label for="feedback_text" class="form-label">Your Feedback *</label>
                            <textarea class="form-control" id="feedback_text" name="feedback_text" rows="4" required placeholder="Please share your experience with us..."></textarea>
                        </div>

                        <!-- Overall Experience -->
                        <div class="mb-3">
                            <label for="overall_experience" class="form-label">What did you like most about your stay?</label>
                            <textarea class="form-control" id="overall_experience" name="overall_experience" rows="3" placeholder="Tell us about the highlights of your experience..."></textarea>
                        </div>

                        <!-- Recommendation -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="would_recommend" name="would_recommend">
                                <label class="form-check-label" for="would_recommend">
                                    <strong>I would recommend <?php echo APP_NAME; ?> to friends and family</strong>
                                </label>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-submit">
                                <i class="bi bi-send me-2"></i>Submit Feedback
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        document.querySelectorAll('.star-rating').forEach(ratingGroup => {
            const stars = ratingGroup.querySelectorAll('.star');
            const ratingName = ratingGroup.getAttribute('data-rating');
            const hiddenInput = document.getElementById(ratingName);
            
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    const rating = index + 1;
                    hiddenInput.value = rating;
                    
                    // Update visual state
                    stars.forEach((s, i) => {
                        if (i < rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });
                
                star.addEventListener('mouseover', () => {
                    stars.forEach((s, i) => {
                        if (i <= index) {
                            s.style.color = 'var(--primary-gold)';
                        } else {
                            s.style.color = '#ddd';
                        }
                    });
                });
            });
            
            ratingGroup.addEventListener('mouseleave', () => {
                const currentRating = hiddenInput.value;
                stars.forEach((s, i) => {
                    if (i < currentRating) {
                        s.style.color = 'var(--primary-gold)';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
    </script>
</body>
</html>