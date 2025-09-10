<?php
// Admin Layout Template
// This file provides a consistent layout for all admin pages

function renderAdminHeader($pageTitle, $currentPage = '') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: <?php echo APP_THEME_COLOR; ?>;
            --gold-color: #D4AF37;
            --dark-bg: #1a1a1a;
            --card-bg: #ffffff;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color));
            border-right: none;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: white;
            border-radius: 10px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background-color: white;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .main-content {
            padding: 30px;
            background-color: #f8f9fa;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color));
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color));
            border: none;
            border-radius: 10px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gold-color), var(--primary-color));
            transform: translateY(-2px);
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--gold-color));
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="d-flex flex-column p-3">
                    <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
                        <span class="fs-4 text-white"><?php echo APP_NAME; ?></span>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link <?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="users.php" class="nav-link <?php echo ($currentPage == 'users') ? 'active' : ''; ?>">
                                <i class="bi bi-people me-2"></i>
                                Manage Users
                            </a>
                        </li>
                        <li>
                            <a href="rooms.php" class="nav-link <?php echo ($currentPage == 'rooms') ? 'active' : ''; ?>">
                                <i class="bi bi-door-open me-2"></i>
                                Manage Rooms
                            </a>
                        </li>
                        <li>
                            <a href="content_management.php" class="nav-link <?php echo ($currentPage == 'content') ? 'active' : ''; ?>">
                                <i class="bi bi-file-text me-2"></i>
                                Content Management
                            </a>
                        </li>
                        <li>
                            <a href="reports.php" class="nav-link <?php echo ($currentPage == 'reports') ? 'active' : ''; ?>">
                                <i class="bi bi-graph-up me-2"></i>
                                Reports
                            </a>
                        </li>
                        <li>
                            <a href="qr_generator.php" class="nav-link <?php echo ($currentPage == 'qr') ? 'active' : ''; ?>">
                                <i class="bi bi-qr-code me-2"></i>
                                QR Generator
                            </a>
                        </li>
                        <li>
                            <a href="leave_management.php" class="nav-link <?php echo ($currentPage == 'leave') ? 'active' : ''; ?>">
                                <i class="bi bi-calendar-check me-2"></i>
                                Leave Management
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <strong>Admin</strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                            <li><a class="dropdown-item" href="#">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
<?php
}

function renderAdminPageHeader($title, $subtitle = '') {
?>
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="mb-1"><?php echo $title; ?></h1>
                            <?php if ($subtitle): ?>
                                <p class="mb-0"><?php echo $subtitle; ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <div class="h5 mb-0"><?php echo date('F j, Y'); ?></div>
                            <small><?php echo date('l, g:i A'); ?></small>
                        </div>
                    </div>
                </div>
<?php
}

function renderAdminFooter() {
?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
?>