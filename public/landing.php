<?php
// Check if we're being included from index.php or accessed directly
if (!defined('CONFIG_LOADED')) {
    require_once '../includes/config.php';
    require_once '../includes/content_helper.php';
}

// Get content for different sections
$heroContent = getSectionContentArray('hero');
$featuresContent = getSectionContentArray('features');
$aboutContent = getSectionContentArray('about');
$testimonialsContent = getSectionContentArray('testimonials');
$footerContent = getSectionContentArray('footer');
$navigationContent = getSectionContentArray('navigation');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Torres Farm Hotel & Resort</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gold: #D4AF37;
            --dark-blue: #1a365d;
            --light-gray: #f8f9fa;
            --text-dark: #2d3748;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            overflow-x: hidden;
        }
        
        /* Animation Keyframes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Animation Classes */
        .animate-on-scroll {
            opacity: 0;
            transition: all 0.8s ease;
        }
        
        .animate-on-scroll.animated {
            opacity: 1;
        }
        
        /* Fade In Up */
        .animate-on-scroll[data-animation*="fade-in-up"] {
            transform: translateY(30px);
        }
        
        .animate-on-scroll[data-animation*="fade-in-up"].animated {
            transform: translateY(0);
        }
        
        /* Fade In Left */
        .animate-on-scroll[data-animation*="fade-in-left"] {
            transform: translateX(-30px);
        }
        
        .animate-on-scroll[data-animation*="fade-in-left"].animated {
            transform: translateX(0);
        }
        
        /* Fade In Right */
        .animate-on-scroll[data-animation*="fade-in-right"] {
            transform: translateX(30px);
        }
        
        .animate-on-scroll[data-animation*="fade-in-right"].animated {
            transform: translateX(0);
        }
        
        /* Scale In */
        .animate-on-scroll[data-animation*="scale-in"] {
            transform: scale(0.8);
        }
        
        .animate-on-scroll[data-animation*="scale-in"].animated {
            transform: scale(1);
        }
        
        /* Animation Delays */
        .animate-on-scroll[data-animation*="animate-delay-1"] {
            transition-delay: 0.1s;
        }
        
        .animate-on-scroll[data-animation*="animate-delay-2"] {
            transition-delay: 0.2s;
        }
        
        .animate-on-scroll[data-animation*="animate-delay-3"] {
            transition-delay: 0.3s;
        }
        
        .animate-on-scroll[data-animation*="animate-delay-4"] {
            transition-delay: 0.4s;
        }
        
        .animate-on-scroll[data-animation*="animate-delay-5"] {
            transition-delay: 0.5s;
        }
        
        .animate-on-scroll[data-animation*="animate-delay-6"] {
            transition-delay: 0.6s;
        }
        
        .playfair {
            font-family: 'Playfair Display', serif;
        }
        
        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            padding: 1rem 0;
            transition: all 0.3s ease;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand {
            font-weight: 600;
            color: var(--text-dark) !important;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary-gold);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .nav-link:hover {
            color: var(--primary-gold) !important;
            transform: translateY(-2px);
        }
        
        .nav-link:hover::before {
            width: 80%;
        }
        
        .btn-book {
            background: var(--primary-gold);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-book::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-book:hover {
            background: #b8941f;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.3);
        }
        
        .btn-book:hover::before {
            left: 100%;
        }
        
        /* Hero Section */
        .hero-section {
            height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('<?php echo escapeContent($heroContent['hero_background_image'] ?? 'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80'); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            position: relative;
            padding-top: 80px;
        }
        
        .hero-content {
            color: white;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .booking-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            transform: translateY(20px);
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.5s forwards;
        }
        
        .booking-form h3 {
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: var(--primary-gold);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
            transform: translateY(-2px);
            background: white;
        }
        
        .form-control:hover {
            border-color: #cbd5e0;
            transform: translateY(-1px);
        }
        
        .btn-check-availability {
            background: var(--primary-gold);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-check-availability::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-check-availability:hover {
            background: #b8941f;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        }
        
        .btn-check-availability:hover::before {
            left: 100%;
        }
        
        .btn-check-availability:active {
            transform: translateY(-1px);
        }
        
        /* Feature Cards */
        .features-section {
            padding: 5rem 0;
            background: var(--light-gray);
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(212, 175, 55, 0.05));
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 1;
        }
        
        .feature-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }
        
        .feature-card:hover::before {
            opacity: 1;
        }
        
        .feature-card > * {
            position: relative;
            z-index: 2;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .feature-icon::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transition: all 0.4s ease;
            transform: translate(-50%, -50%);
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        }
        
        .feature-card:hover .feature-icon::before {
            width: 100%;
            height: 100%;
        }
        
        .feature-title {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }
        
        .feature-description {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        /* Section Styling */
        .section-padding {
            padding: 5rem 0;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3rem;
            color: var(--text-dark);
        }
        
        .section-title .highlight {
            color: var(--primary-gold);
        }
        
        /* About Section */
        .about-section {
            background: white;
        }
        
        .about-image {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .about-content {
            padding: 2rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--light-gray);
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-gold);
        }
        
        .discover-btn {
            background: var(--primary-gold);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .discover-btn:hover {
            background: #b8941f;
            transform: translateY(-2px);
        }
        
        /* Testimonials */
        .testimonials-section {
            background: var(--light-gray);
        }
        
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            margin: 1rem;
        }
        
        .stars {
            color: var(--primary-gold);
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: 1.5rem;
            color: #64748b;
        }
        
        .testimonial-author {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .testimonial-role {
            font-size: 0.9rem;
            color: #64748b;
        }
        
        /* Footer */
        .footer {
            background: var(--dark-blue);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        /* QR Codes Section Styling */
        .qr-codes-section {
            background: var(--light-gray);
        }
        
        .qr-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(212, 175, 55, 0.2);
        }
        
        .qr-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
            border-color: var(--primary-gold);
        }
        
        .qr-code-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .qr-title {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .booking-form {
                margin-top: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .qr-codes-section {
                padding: 1rem;
            }
            
            .qr-card {
                padding: 1rem !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand playfair" href="#">
                <img src="<?php echo escapeContent($navigationContent['site_logo'] ?? '../assets/images/logo.png'); ?>" alt="Torres Farm" height="40" class="me-2">
                <?php echo escapeContent($navigationContent['site_title'] ?? 'Torres Farm Hotel & Resort'); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#rooms">Rooms & Suites</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#facilities">Facilities</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#gallery">Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#events">Events & Offers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact Us</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a href="../login.php" class="btn btn-book">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="hero-title playfair">
                            <?php echo $heroContent['hero_title'] ?? 'Luxury<br><span style="color: var(--primary-gold);">Redefined</span>'; ?>
                        </h1>
                        <p class="hero-subtitle">
                            <?php echo escapeContent($heroContent['hero_subtitle'] ?? 'Experience unparalleled elegance and comfort in our world-class accommodations'); ?>
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">

                    
                    <div class="booking-form">
                        <h3 class="playfair">Book Your Stay</h3>
                        <form id="bookingForm" action="api/check_availability.php" method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label" for="checkin">Check-in</label>
                                    <input type="date" id="checkin" name="checkin" class="form-control" required>
                                    <div class="invalid-feedback">Please select a check-in date.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="checkout">Check-out</label>
                                    <input type="date" id="checkout" name="checkout" class="form-control" required>
                                    <div class="invalid-feedback">Please select a check-out date.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label" for="guests">Guests</label>
                                    <select id="guests" name="guests" class="form-control" required>
                                        <option value="">Select guests</option>
                                        <option value="1">1 Guest</option>
                                        <option value="2" selected>2 Guests</option>
                                        <option value="3">3 Guests</option>
                                        <option value="4">4 Guests</option>
                                        <option value="5">5 Guests</option>
                                        <option value="6">6 Guests</option>
                                    </select>
                                    <div class="invalid-feedback">Please select number of guests.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="roomType">Room Type</label>
                                    <select id="roomType" name="room_type" class="form-control" required>
                                        <option value="">Select room type</option>
                                        <option value="all" selected>All Types</option>
                                        <option value="standard">Standard Room</option>
                                        <option value="deluxe">Deluxe Room</option>
                                        <option value="suite">Suite</option>
                                        <option value="family">Family Room</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a room type.</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-check-availability" id="checkAvailabilityBtn">
                                <span class="btn-text">Check Availability</span>
                                <span class="btn-loading d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Checking...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card animate-on-scroll" data-animation="fade-in-up animate-delay-1">
                        <div class="feature-icon">
                            <i class="<?php echo escapeContent($featuresContent['feature_1_icon'] ?? 'bi bi-star-fill'); ?>"></i>
                        </div>
                        <h4 class="feature-title"><?php echo escapeContent($featuresContent['feature_1_title'] ?? '5-Star Luxury'); ?></h4>
                        <p class="feature-description">
                            <?php echo escapeContent($featuresContent['feature_1_description'] ?? 'World-class amenities and exceptional service'); ?>
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card animate-on-scroll" data-animation="fade-in-up animate-delay-2">
                        <div class="feature-icon">
                            <i class="<?php echo escapeContent($featuresContent['feature_2_icon'] ?? 'bi bi-geo-alt-fill'); ?>"></i>
                        </div>
                        <h4 class="feature-title"><?php echo escapeContent($featuresContent['feature_2_title'] ?? 'Prime Location'); ?></h4>
                        <p class="feature-description">
                            <?php echo escapeContent($featuresContent['feature_2_description'] ?? '33 miniature attractions across 14 hectares'); ?>
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card animate-on-scroll" data-animation="fade-in-up animate-delay-3">
                        <div class="feature-icon">
                            <i class="<?php echo escapeContent($featuresContent['feature_3_icon'] ?? 'bi bi-shield-check'); ?>"></i>
                        </div>
                        <h4 class="feature-title"><?php echo escapeContent($featuresContent['feature_3_title'] ?? 'Safe & Secure'); ?></h4>
                        <p class="feature-description">
                            <?php echo escapeContent($featuresContent['feature_3_description'] ?? 'Your safety and comfort is our priority'); ?>
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card animate-on-scroll" data-animation="fade-in-up animate-delay-4">
                        <div class="feature-icon">
                            <i class="<?php echo escapeContent($featuresContent['feature_4_icon'] ?? 'bi bi-clock'); ?>"></i>
                        </div>
                        <h4 class="feature-title"><?php echo escapeContent($featuresContent['feature_4_title'] ?? '24/7 Service'); ?></h4>
                        <p class="feature-description">
                            <?php echo escapeContent($featuresContent['feature_4_description'] ?? 'Round-the-clock assistance and support'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- QR Codes Section -->
    <?php
    // Get active QR codes from database
    $qrCodes = getActiveQRCodes();
    if (!empty($qrCodes)): ?>
    <section class="qr-codes-section section-padding" style="background: var(--light-gray);">
        <div class="container">
            <div class="text-center mb-5 animate-on-scroll" data-animation="fade-in-up">
                <h2 class="section-title playfair"><i class="bi bi-qr-code me-2"></i> Quick <span class="highlight">Access</span></h2>
                <p class="text-muted">Scan QR codes for instant access to our services and information</p>
            </div>
            <div class="row g-4 justify-content-center">
                <?php $delay = 1; foreach ($qrCodes as $qr): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="qr-card text-center p-4 bg-white rounded-3 shadow-sm h-100 animate-on-scroll" data-animation="scale-in animate-delay-<?php echo $delay; ?>">
                        <div class="qr-code-container mb-3">
                            <canvas class="qr-code-canvas mx-auto" data-content="<?php echo escapeContent($qr['content']); ?>" width="120" height="120" style="max-width: 120px; max-height: 120px;"></canvas>
                        </div>
                        <h5 class="qr-title text-dark mb-2"><?php echo escapeContent($qr['title']); ?></h5>
                        <p class="text-muted small mb-0"><?php echo ucfirst($qr['type']); ?></p>
                    </div>
                </div>
                <?php $delay++; endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- About Section -->
    <section id="about" class="about-section section-padding">
        <div class="container">
            <h2 class="section-title playfair animate-on-scroll" data-animation="fade-in-up">
                <?php echo $aboutContent['about_title'] ?? 'About <span class="highlight">Torres Farm</span>'; ?>
            </h2>
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-content animate-on-scroll" data-animation="fade-in-left">
                        <p class="lead">
                            <?php echo escapeContent($aboutContent['about_lead'] ?? 'Torres Farm Hotel & Resort is the happiest place in Cavite, located in Naic. Featuring 33 miniature attractions of different countries, spanning over 14 hectares.'); ?>
                        </p>
                        <p>
                            <?php echo escapeContent($aboutContent['about_description'] ?? 'Experience the magic of world travel without leaving the Philippines. Our resort combines luxury accommodations with unique cultural experiences, making every stay unforgettable.'); ?>
                        </p>
                        <div class="stats-grid animate-on-scroll" data-animation="fade-in-up animate-delay-2">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo escapeContent($aboutContent['about_stat_1_number'] ?? '33'); ?></div>
                                <div><?php echo escapeContent($aboutContent['about_stat_1_label'] ?? 'Miniature Attractions'); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo escapeContent($aboutContent['about_stat_2_number'] ?? '14'); ?></div>
                                <div><?php echo escapeContent($aboutContent['about_stat_2_label'] ?? 'Hectares'); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo escapeContent($aboutContent['about_stat_3_number'] ?? '5★'); ?></div>
                                <div><?php echo escapeContent($aboutContent['about_stat_3_label'] ?? 'World-Class Service'); ?></div>
                            </div>
                        </div>
                        <button class="btn discover-btn animate-on-scroll" data-animation="fade-in-up animate-delay-3">
                            Discover More <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-image animate-on-scroll" data-animation="fade-in-right">
                        <img src="<?php echo escapeContent($aboutContent['about_image'] ?? 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2080&q=80'); ?>" alt="Torres Farm Resort" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Rooms Section -->
    <section id="rooms" class="section-padding" style="background: var(--light-gray);">
        <div class="container">
            <h2 class="section-title playfair animate-on-scroll" data-animation="fade-in-up">
                Rooms & <span class="highlight">Suites</span>
            </h2>
            <p class="text-center mb-5 animate-on-scroll" data-animation="fade-in-up animate-delay-1" style="max-width: 600px; margin: 0 auto;">
                Choose from our carefully curated selection of luxurious accommodations, each designed to provide the ultimate comfort and elegance.
            </p>
        </div>
    </section>

    <!-- Facilities Section -->
    <section id="facilities" class="section-padding">
        <div class="container">
            <h2 class="section-title playfair animate-on-scroll" data-animation="fade-in-up">
                Facilities & <span class="highlight">Amenities</span>
            </h2>
        </div>
    </section>

    <!-- Special Offers Section -->
    <section id="events" class="section-padding" style="background: var(--light-gray);">
        <div class="container">
            <h2 class="section-title playfair animate-on-scroll" data-animation="fade-in-up">
                Special <span class="highlight">Offers</span> & Promotions
            </h2>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="section-padding">
        <div class="container">
            <h2 class="section-title playfair animate-on-scroll" data-animation="fade-in-up">
                Our <span class="highlight">Gallery</span>
            </h2>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section section-padding">
        <div class="container">
            <h2 class="section-title playfair animate-on-scroll" data-animation="fade-in-up">
                <?php echo $testimonialsContent['testimonials_title'] ?? 'What Our <span class="highlight">Guests</span> Say'; ?>
            </h2>
            <div class="row">
                <div class="col-lg-4">
                    <div class="testimonial-card animate-on-scroll" data-animation="fade-in-up animate-delay-1">
                        <div class="stars">
                            <?php echo generateStarRating(intval($testimonialsContent['testimonial_1_rating'] ?? 5)); ?>
                        </div>
                        <p class="testimonial-text">
                            "<?php echo escapeContent($testimonialsContent['testimonial_1_text'] ?? 'This booking system has transformed how we manage reservations. Our no-show rate dropped by 40%!'); ?>"
                        </p>
                        <div class="testimonial-author"><?php echo escapeContent($testimonialsContent['testimonial_1_author'] ?? 'Sarah Johnson'); ?></div>
                        <div class="testimonial-role"><?php echo escapeContent($testimonialsContent['testimonial_1_role'] ?? 'Hotel Manager'); ?></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="testimonial-card animate-on-scroll" data-animation="fade-in-up animate-delay-2">
                        <div class="stars">
                            <?php echo generateStarRating(intval($testimonialsContent['testimonial_2_rating'] ?? 4)); ?>
                        </div>
                        <p class="testimonial-text">
                            "<?php echo escapeContent($testimonialsContent['testimonial_2_text'] ?? 'The intuitive interface makes scheduling a breeze. Our customers love the seamless experience.'); ?>"
                        </p>
                        <div class="testimonial-author"><?php echo escapeContent($testimonialsContent['testimonial_2_author'] ?? 'Michael Chen'); ?></div>
                        <div class="testimonial-role"><?php echo escapeContent($testimonialsContent['testimonial_2_role'] ?? 'Event Coordinator'); ?></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="testimonial-card animate-on-scroll" data-animation="fade-in-up animate-delay-3">
                        <div class="stars">
                            <?php echo generateStarRating(intval($testimonialsContent['testimonial_3_rating'] ?? 5)); ?>
                        </div>
                        <p class="testimonial-text">
                            "<?php echo escapeContent($testimonialsContent['testimonial_3_text'] ?? 'Revenue increased by 25% after implementing this system. Highly recommend!'); ?>"
                        </p>
                        <div class="testimonial-author"><?php echo escapeContent($testimonialsContent['testimonial_3_author'] ?? 'Emma Rodriguez'); ?></div>
                        <div class="testimonial-role"><?php echo escapeContent($testimonialsContent['testimonial_3_role'] ?? 'Business Owner'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Nearby Attractions Section -->
    <section class="section-padding">
        <div class="container">
            <h2 class="section-title playfair animate-on-scroll" data-animation="fade-in-up">
                Nearby <span class="highlight">Attractions</span>
            </h2>
            <div class="row">
                <div class="col-lg-8">
                    <div class="animate-on-scroll" data-animation="fade-in-left" style="background: #e2e8f0; height: 400px; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                        <p class="text-muted">Interactive Map Coming Soon</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="p-4">
                        <h4>Explore the Area</h4>
                        <p>Discover amazing attractions and landmarks near Torres Farm Hotel & Resort.</p>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-geo-alt-fill text-warning me-2"></i> Naic Historic Town</li>
                            <li class="mb-2"><i class="bi bi-geo-alt-fill text-warning me-2"></i> Cavite Beaches</li>
                            <li class="mb-2"><i class="bi bi-geo-alt-fill text-warning me-2"></i> Local Markets</li>
                            <li class="mb-2"><i class="bi bi-geo-alt-fill text-warning me-2"></i> Cultural Sites</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h5 class="playfair mb-3"><?php echo escapeContent($footerContent['footer_hotel_name'] ?? 'Torres Farm Hotel & Resort'); ?></h5>
                    <p><?php echo escapeContent($footerContent['footer_description'] ?? 'Experience the magic of world travel without leaving the Philippines.'); ?></p>
                    <div class="social-links">
                        <a href="<?php echo escapeContent($footerContent['footer_facebook_url'] ?? '#'); ?>"><i class="bi bi-facebook"></i></a>
                        <a href="<?php echo escapeContent($footerContent['footer_twitter_url'] ?? '#'); ?>"><i class="bi bi-twitter"></i></a>
                        <a href="<?php echo escapeContent($footerContent['footer_instagram_url'] ?? '#'); ?>"><i class="bi bi-instagram"></i></a>
                        <a href="<?php echo escapeContent($footerContent['footer_linkedin_url'] ?? '#'); ?>"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <h6>Contact Information</h6>
                    <p><i class="bi bi-geo-alt-fill me-2"></i> <?php echo escapeContent($footerContent['footer_address'] ?? 'Naic, Cavite, Philippines'); ?></p>
                    <p><i class="bi bi-telephone-fill me-2"></i> <?php echo escapeContent($footerContent['footer_phone'] ?? '+63 (xxx) xxx-xxxx'); ?></p>
                    <p><i class="bi bi-envelope-fill me-2"></i> <?php echo escapeContent($footerContent['footer_email'] ?? 'info@torresfarm.com'); ?></p>
                </div>
                <div class="col-lg-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo escapeContent($footerContent['footer_link_1_url'] ?? '#about'); ?>" class="text-white-50"><?php echo escapeContent($footerContent['footer_link_1_text'] ?? 'About Us'); ?></a></li>
                        <li><a href="<?php echo escapeContent($footerContent['footer_link_2_url'] ?? '#rooms'); ?>" class="text-white-50"><?php echo escapeContent($footerContent['footer_link_2_text'] ?? 'Rooms & Suites'); ?></a></li>
                        <li><a href="<?php echo escapeContent($footerContent['footer_link_3_url'] ?? '#facilities'); ?>" class="text-white-50"><?php echo escapeContent($footerContent['footer_link_3_text'] ?? 'Facilities'); ?></a></li>
                        <li><a href="<?php echo escapeContent($footerContent['footer_link_4_url'] ?? 'login.php'); ?>" class="text-white-50"><?php echo escapeContent($footerContent['footer_link_4_text'] ?? 'Login'); ?></a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p><?php echo escapeContent($footerContent['footer_copyright'] ?? '&copy; ' . date('Y') . ' Torres Farm Hotel & Resort. All rights reserved.'); ?></p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- QRious Library for QR Code Generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Booking Form Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const bookingForm = document.getElementById('bookingForm');
            const checkinInput = document.getElementById('checkin');
            const checkoutInput = document.getElementById('checkout');
            const submitBtn = document.getElementById('checkAvailabilityBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            checkinInput.min = today;
            checkoutInput.min = today;

            // Set default dates
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const dayAfter = new Date();
            dayAfter.setDate(dayAfter.getDate() + 2);
            
            checkinInput.value = tomorrow.toISOString().split('T')[0];
            checkoutInput.value = dayAfter.toISOString().split('T')[0];

            // Update checkout minimum date when checkin changes
            checkinInput.addEventListener('change', function() {
                const checkinDate = new Date(this.value);
                const nextDay = new Date(checkinDate);
                nextDay.setDate(nextDay.getDate() + 1);
                checkoutInput.min = nextDay.toISOString().split('T')[0];
                
                // Update checkout if it's before the new minimum
                if (checkoutInput.value && new Date(checkoutInput.value) <= checkinDate) {
                    checkoutInput.value = nextDay.toISOString().split('T')[0];
                }
            });

            // Form validation and submission
            bookingForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Remove previous validation classes
                bookingForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                
                let isValid = true;
                
                // Validate dates
                const checkinDate = new Date(checkinInput.value);
                const checkoutDate = new Date(checkoutInput.value);
                const todayDate = new Date();
                todayDate.setHours(0, 0, 0, 0);
                
                if (!checkinInput.value || checkinDate < todayDate) {
                    checkinInput.classList.add('is-invalid');
                    isValid = false;
                }
                
                if (!checkoutInput.value || checkoutDate <= checkinDate) {
                    checkoutInput.classList.add('is-invalid');
                    isValid = false;
                }
                
                // Validate other fields
                const requiredFields = ['guests', 'roomType'];
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    }
                });
                
                if (isValid) {
                    // Show loading state
                    submitBtn.disabled = true;
                    btnText.classList.add('d-none');
                    btnLoading.classList.remove('d-none');
                    
                    // Prepare form data
                    const formData = new FormData(bookingForm);
                    
                    // Make API call
                    fetch('api/check_availability.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show availability results
                            showAvailabilityResults(data.data);
                        } else {
                            // Show error message
                            alert('Error: ' + (data.message || 'Please check your input and try again.'));
                        }
                    })
                    .catch(error => {
                        console.error('Booking API Error:', error);
                        alert('Sorry, there was an error checking availability. Please try again later.');
                    })
                    .finally(() => {
                        // Reset loading state
                        submitBtn.disabled = false;
                        btnText.classList.remove('d-none');
                        btnLoading.classList.add('d-none');
                    });
                }
            });

            // Real-time validation feedback
            bookingForm.querySelectorAll('input, select').forEach(field => {
                field.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid') && this.value) {
                        this.classList.remove('is-invalid');
                    }
                });
            });
        });

        // Scroll-triggered animations
        function initScrollAnimations() {
            const animateElements = document.querySelectorAll('.animate-on-scroll');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animated');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            animateElements.forEach(element => {
                observer.observe(element);
            });
        }
        
        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.15)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
            }
            
            // Parallax effect for hero section
            const heroSection = document.querySelector('.hero-section');
            if (heroSection) {
                const scrolled = window.pageYOffset;
                const parallax = scrolled * 0.5;
                heroSection.style.transform = `translateY(${parallax}px)`;
            }
            
            // Function to display availability results
            function showAvailabilityResults(data) {
                const resultsHtml = `
                    <div class="alert alert-success mt-3" role="alert">
                        <h5 class="alert-heading">Available Rooms Found!</h5>
                        <p><strong>Check-in:</strong> ${data.checkin} | <strong>Check-out:</strong> ${data.checkout}</p>
                        <p><strong>Guests:</strong> ${data.guests} | <strong>Nights:</strong> ${data.nights}</p>
                        <hr>
                        ${data.available_rooms.length > 0 ? 
                            data.available_rooms.map(room => `
                                <div class="border rounded p-3 mb-2 bg-light">
                                    <h6 class="mb-1">${room.name}</h6>
                                    <p class="mb-1">Max Guests: ${room.max_guests} | Available: ${room.available_count} rooms</p>
                                    <p class="mb-0"><strong>₱${room.price_per_night.toLocaleString()}/night | Total: ₱${room.total_price.toLocaleString()}</strong></p>
                                </div>
                            `).join('') 
                            : '<p>No rooms available for your selected dates and guest count.</p>'
                        }
                        <small class="text-muted">To proceed with booking, please contact us or visit our reservations page.</small>
                    </div>
                `;
                
                // Insert results after the form
                const form = document.getElementById('bookingForm');
                const existingResults = document.getElementById('availabilityResults');
                
                if (existingResults) {
                    existingResults.remove();
                }
                
                const resultsDiv = document.createElement('div');
                resultsDiv.id = 'availabilityResults';
                resultsDiv.innerHTML = resultsHtml;
                form.parentNode.insertBefore(resultsDiv, form.nextSibling);
                
                // Scroll to results
                resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });

        // Initialize animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize scroll animations
            initScrollAnimations();
            
            // Add staggered animation to hero content
            const heroTitle = document.querySelector('.hero-title');
            const heroSubtitle = document.querySelector('.hero-subtitle');
            const heroButtons = document.querySelectorAll('.hero-section .btn');
            
            if (heroTitle) {
                heroTitle.style.opacity = '0';
                heroTitle.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    heroTitle.style.transition = 'all 0.8s ease-out';
                    heroTitle.style.opacity = '1';
                    heroTitle.style.transform = 'translateY(0)';
                }, 300);
            }
            
            if (heroSubtitle) {
                heroSubtitle.style.opacity = '0';
                heroSubtitle.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    heroSubtitle.style.transition = 'all 0.8s ease-out';
                    heroSubtitle.style.opacity = '1';
                    heroSubtitle.style.transform = 'translateY(0)';
                }, 600);
            }
            
            heroButtons.forEach((btn, index) => {
                btn.style.opacity = '0';
                btn.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    btn.style.transition = 'all 0.8s ease-out';
                    btn.style.opacity = '1';
                    btn.style.transform = 'translateY(0)';
                }, 900 + (index * 200));
            });
            
            // Generate QR codes for landing page
            const qrCanvases = document.querySelectorAll('.qr-code-canvas');
            qrCanvases.forEach(canvas => {
                const content = canvas.getAttribute('data-content');
                if (content) {
                    try {
                        new QRious({
                            element: canvas,
                            value: content,
                            size: 150,
                            background: 'white',
                            foreground: '#1a365d',
                            padding: 15
                        });
                        
                        // Add click event to QR codes for mobile users
                        canvas.style.cursor = 'pointer';
                        canvas.addEventListener('click', function() {
                            // For mobile devices, show the content in an alert or modal
                            if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                                const qrCard = canvas.closest('.qr-card');
                                const title = qrCard.querySelector('.qr-title').textContent;
                                
                                if (content.startsWith('http')) {
                                    if (confirm(`Open ${title}?\n${content}`)) {
                                        window.open(content, '_blank');
                                    }
                                } else {
                                    alert(`${title}:\n${content}`);
                                }
                            }
                        });
                    } catch (error) {
                        console.error('Error generating QR code:', error);
                        // Show fallback text if QR generation fails
                        canvas.style.display = 'none';
                        const fallback = document.createElement('div');
                        fallback.className = 'text-muted small';
                        fallback.textContent = 'QR Code';
                        canvas.parentNode.appendChild(fallback);
                    }
                }
            });
        });
    </script>
</body>
</html>