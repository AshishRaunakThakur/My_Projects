<?php
// includes/header.php
if(!isset($_SESSION)) session_start();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Asmeera Lifeline</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Leaflet Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body<?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? ' class="landing-page"' : ''; ?>>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top glass-effect<?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? ' navbar-landing' : ''; ?>">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <div class="logo-wrapper">
                    <i class="fas fa-hand-holding-heart logo-icon"></i>
                    <span class="logo-text">Asmeera</span>
                    <span class="logo-sub">Lifeline</span>
                </div>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-dropdown" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>profile.php">
                                    <i class="fas fa-user"></i> My Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                        
                        <!-- Notification Bell -->
                        <li class="nav-item">
                            <a class="nav-link notification-bell" href="#" id="notificationToggle">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge" id="notificationCount">0</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                        <li class="nav-item"><a class="nav-link btn-login" href="<?php echo SITE_URL; ?>login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a></li>
                        <li class="nav-item"><a class="nav-link btn-register" href="<?php echo SITE_URL; ?>register.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Notification Panel -->
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            <h6><i class="fas fa-bell"></i> Notifications</h6>
            <button class="close-btn" id="closeNotifications">&times;</button>
        </div>
        <div class="notification-list" id="notificationList">
            <div class="text-center p-3">Loading notifications...</div>
        </div>
    </div>
    
    <main>