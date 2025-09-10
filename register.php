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
    $confirm_password = $_POST['confirm_password'];
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $checkStmt = $conn->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            if ($existing['username'] === $username) {
                $errors[] = "Username already exists. Please choose a different username.";
            }
            if ($existing['email'] === $email) {
                $errors[] = "Email address already exists. Please use a different email.";
            }
        }
        $checkStmt->close();
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'employee'; // Fixed role for all registered users - cannot be admin or other privileged roles
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $hashed_password, $email, $role, $first_name, $last_name);
        
        if ($stmt->execute()) {
            $success = "Registration successful! You can now <a href='login.php'>log in</a> with your new account.";
            // Clear form data after successful registration
            $_POST = array();
        } else {
            $errors[] = "Error creating account. Please try again later.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: <?php echo APP_THEME_COLOR; ?>;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            margin: 20px;
        }
        
        .register-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .register-header h2 {
            margin: 0;
            font-weight: 300;
        }
        
        .register-body {
            padding: 2rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
        }
        
        .btn-register {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2><i class="bi bi-person-plus"></i> Create Account</h2>
            <p class="mb-0">Join <?php echo APP_NAME; ?></p>
        </div>
        
        <div class="register-body">
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!isset($success)): ?>
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Password must be at least 6 characters long.</div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-register w-100">
                    <i class="bi bi-person-plus"></i> Create Account
                </button>
            </form>
            <?php endif; ?>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>