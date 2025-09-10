<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect_to_dashboard();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate credentials against database
    $stmt = $conn->prepare("SELECT id, username, password, role, first_name, last_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            
            redirect_to_dashboard();
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo APP_THEME_COLOR; ?>;
            --primary-rgb: 212, 175, 55; /* Gold color RGB */
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, html {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .split-screen {
            display: flex;
            height: 100vh;
        }
        
        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.9) 0%, rgba(var(--primary-rgb), 0.7) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="%23ffffff" opacity="0.1"/><circle cx="80" cy="40" r="1" fill="%23ffffff" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            opacity: 0.3;
        }
        
        .right-panel {
            flex: 1;
            background: linear-gradient(45deg, rgba(0,0,0,0.4), rgba(0,0,0,0.6)), 
                        url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80') center/cover;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .promo-content {
            text-align: center;
            color: white;
            z-index: 2;
            padding: 2rem;
        }
        
        .promo-content h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .promo-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .login-form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 400px;
            width: 90%;
            position: relative;
            z-index: 2;
        }
        
        .login-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 20px;
            z-index: -1;
        }
        
        .login-title {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-floating > .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(var(--primary-rgb), 0.2);
            border-radius: 12px;
            padding: 1rem 0.75rem;
            transition: all 0.3s ease;
        }
        
        .form-floating > .form-control:focus {
            background: rgba(255, 255, 255, 1);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
        }
        
        .form-floating > label {
            color: #666;
            font-weight: 500;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, #B8941F 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(var(--primary-rgb), 0.3);
        }
        
        .alert {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-radius: 12px;
            color: #721c24;
            margin-bottom: 1.5rem;
        }
        
        .back-to-home {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            z-index: 3;
        }
        
        .back-to-home:hover {
            color: white;
            transform: translateX(-5px);
        }
        
        @media (max-width: 768px) {
            .split-screen {
                flex-direction: column;
            }
            
            .left-panel {
                min-height: 100vh;
            }
            
            .right-panel {
                display: none;
            }
            
            .login-form-container {
                margin: 2rem auto;
            }
            
            .promo-content h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="split-screen">
        <div class="left-panel">
            <a href="public/landing.php" class="back-to-home">
                <i class="fas fa-arrow-left me-2"></i>Back to Home
            </a>
            
            <div class="login-form-container">
                <h1 class="login-title">Welcome</h1>
                <p class="login-subtitle">Sign in to your account</p>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                        <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </form>
                
                <div class="login-link">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="promo-content">
                <h1>Torres Hotel</h1>
                <p>Experience luxury and comfort in our world-class accommodations</p>
                <div class="d-flex justify-content-center gap-3">
                    <div class="text-center">
                        <i class="fas fa-star fa-2x mb-2"></i>
                        <p class="mb-0">5-Star Service</p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-wifi fa-2x mb-2"></i>
                        <p class="mb-0">Free WiFi</p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-swimming-pool fa-2x mb-2"></i>
                        <p class="mb-0">Pool & Spa</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>