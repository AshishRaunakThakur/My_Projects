<?php
if (!isset($page_title)) $page_title = 'Dashboard';
if (!isset($current_page)) $current_page = basename($_SERVER['PHP_SELF'], '.php');
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'User';
$role_labels = ['admin' => 'Admin Portal', 'citizen' => 'Citizen Portal', 'volunteer' => 'Volunteer Portal', 'ngo' => 'NGO Portal'];
$portal_label = $role_labels[$role] ?? 'Portal';

function navActive($page, $current) {
    return $page === $current ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Asmeera Lifeline</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* ===== APP LAYOUT - LIGHT MODE ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body.app-body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f8;
            min-height: 100vh;
            padding-top: 0;
            overflow-x: hidden;
        }

        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR - COLORFUL ===== */
        .app-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fc 100%);
            color: #1a1a2e;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1040;
            overflow-y: auto;
            transition: transform 0.3s ease;
            border-right: 1px solid #e8ecf4;
            box-shadow: 4px 0 20px rgba(0,0,0,0.04);
        }
        .app-sidebar::-webkit-scrollbar { width: 4px; }
        .app-sidebar::-webkit-scrollbar-track { background: #f5f6fa; }
        .app-sidebar::-webkit-scrollbar-thumb { background: #dc3545; border-radius: 4px; }

        .sidebar-brand {
            padding: 20px 20px 14px;
            border-bottom: 1px solid #eef1f8;
            background: linear-gradient(135deg, #ffffff, #f8f9fc);
        }
        .sidebar-brand a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1a1a2e;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 700;
        }
        .sidebar-brand i { 
            color: #dc3545; 
            font-size: 1.5rem;
            background: linear-gradient(135deg, #dc3545, #e94560);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .sidebar-brand small {
            display: block;
            color: #8e95a9;
            font-size: 0.65rem;
            margin-top: 2px;
            margin-left: 36px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: linear-gradient(135deg, #f8f9fc, #eef1f8);
            margin: 12px 14px;
            border-radius: 12px;
            border: 1px solid #e8ecf4;
        }
        .sidebar-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #dc3545, #e94560);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-avatar i { color: #fff; font-size: 1.1rem; }
        .sidebar-user strong {
            display: block;
            font-size: 0.85rem;
            color: #1a1a2e;
        }
        .sidebar-user .badge {
            font-size: 0.6rem;
            padding: 2px 8px;
            background: linear-gradient(135deg, #dc3545, #e94560);
            color: #fff;
        }

        .sidebar-nav {
            list-style: none;
            padding: 8px 12px 20px;
            margin: 0;
        }
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: #5a607a;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 2px;
            font-weight: 500;
            font-size: 0.88rem;
            transition: all 0.3s ease;
            position: relative;
        }
        /* Colorful Icons */
        .sidebar-nav li a i { 
            width: 20px; 
            text-align: center; 
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav li a[href*="dashboard"] i { color: #dc3545; }
        .sidebar-nav li a[href*="dashboard"]:hover i { color: #dc3545; transform: scale(1.1); }
        .sidebar-nav li a[href*="dashboard"].active { background: rgba(220,53,69,0.1); color: #dc3545; }
        .sidebar-nav li a[href*="dashboard"].active i { color: #dc3545; }
        
        .sidebar-nav li a[href*="request_help"] i { color: #fd7e14; }
        .sidebar-nav li a[href*="request_help"]:hover i { color: #fd7e14; transform: scale(1.1); }
        .sidebar-nav li a[href*="request_help"].active { background: rgba(253,126,20,0.1); color: #fd7e14; }
        .sidebar-nav li a[href*="request_help"].active i { color: #fd7e14; }
        
        .sidebar-nav li a[href*="safe_checkin"] i { color: #28a745; }
        .sidebar-nav li a[href*="safe_checkin"]:hover i { color: #28a745; transform: scale(1.1); }
        .sidebar-nav li a[href*="safe_checkin"].active { background: rgba(40,167,69,0.1); color: #28a745; }
        .sidebar-nav li a[href*="safe_checkin"].active i { color: #28a745; }
        
        .sidebar-nav li a[href*="missing_person"] i { color: #6f42c1; }
        .sidebar-nav li a[href*="missing_person"]:hover i { color: #6f42c1; transform: scale(1.1); }
        .sidebar-nav li a[href*="missing_person"].active { background: rgba(111,66,193,0.1); color: #6f42c1; }
        .sidebar-nav li a[href*="missing_person"].active i { color: #6f42c1; }
        
        .sidebar-nav li a[href*="tasks"] i { color: #0d6efd; }
        .sidebar-nav li a[href*="tasks"]:hover i { color: #0d6efd; transform: scale(1.1); }
        .sidebar-nav li a[href*="tasks"].active { background: rgba(13,110,253,0.1); color: #0d6efd; }
        .sidebar-nav li a[href*="tasks"].active i { color: #0d6efd; }
        
        .sidebar-nav li a[href*="availability"] i { color: #0dcaf0; }
        .sidebar-nav li a[href*="availability"]:hover i { color: #0dcaf0; transform: scale(1.1); }
        .sidebar-nav li a[href*="availability"].active { background: rgba(13,202,240,0.1); color: #0dcaf0; }
        .sidebar-nav li a[href*="availability"].active i { color: #0dcaf0; }
        
        .sidebar-nav li a[href*="resources"] i { color: #20c997; }
        .sidebar-nav li a[href*="resources"]:hover i { color: #20c997; transform: scale(1.1); }
        .sidebar-nav li a[href*="resources"].active { background: rgba(32,201,151,0.1); color: #20c997; }
        .sidebar-nav li a[href*="resources"].active i { color: #20c997; }
        
        .sidebar-nav li a[href*="camps"] i { color: #e83e8c; }
        .sidebar-nav li a[href*="camps"]:hover i { color: #e83e8c; transform: scale(1.1); }
        .sidebar-nav li a[href*="camps"].active { background: rgba(232,62,140,0.1); color: #e83e8c; }
        .sidebar-nav li a[href*="camps"].active i { color: #e83e8c; }
        
        .sidebar-nav li a[href*="users"] i { color: #6f42c1; }
        .sidebar-nav li a[href*="users"]:hover i { color: #6f42c1; transform: scale(1.1); }
        .sidebar-nav li a[href*="users"].active { background: rgba(111,66,193,0.1); color: #6f42c1; }
        .sidebar-nav li a[href*="users"].active i { color: #6f42c1; }
        
        .sidebar-nav li a[href*="requests"] i { color: #dc3545; }
        .sidebar-nav li a[href*="requests"]:hover i { color: #dc3545; transform: scale(1.1); }
        .sidebar-nav li a[href*="requests"].active { background: rgba(220,53,69,0.1); color: #dc3545; }
        .sidebar-nav li a[href*="requests"].active i { color: #dc3545; }
        
        .sidebar-nav li a[href*="ngos"] i { color: #28a745; }
        .sidebar-nav li a[href*="ngos"]:hover i { color: #28a745; transform: scale(1.1); }
        .sidebar-nav li a[href*="ngos"].active { background: rgba(40,167,69,0.1); color: #28a745; }
        .sidebar-nav li a[href*="ngos"].active i { color: #28a745; }
        
        .sidebar-nav li a[href*="volunteers"] i { color: #0d6efd; }
        .sidebar-nav li a[href*="volunteers"]:hover i { color: #0d6efd; transform: scale(1.1); }
        .sidebar-nav li a[href*="volunteers"].active { background: rgba(13,110,253,0.1); color: #0d6efd; }
        .sidebar-nav li a[href*="volunteers"].active i { color: #0d6efd; }
        
        .sidebar-nav li a[href*="checkins"] i { color: #fd7e14; }
        .sidebar-nav li a[href*="checkins"]:hover i { color: #fd7e14; transform: scale(1.1); }
        .sidebar-nav li a[href*="checkins"].active { background: rgba(253,126,20,0.1); color: #fd7e14; }
        .sidebar-nav li a[href*="checkins"].active i { color: #fd7e14; }
        
        .sidebar-nav li a[href*="profile"] i { color: #0dcaf0; }
        .sidebar-nav li a[href*="profile"]:hover i { color: #0dcaf0; transform: scale(1.1); }
        .sidebar-nav li a[href*="profile"].active { background: rgba(13,202,240,0.1); color: #0dcaf0; }
        .sidebar-nav li a[href*="profile"].active i { color: #0dcaf0; }
        
        .sidebar-nav li a[href*="home"] i { color: #20c997; }
        .sidebar-nav li a[href*="home"]:hover i { color: #20c997; transform: scale(1.1); }
        
        .sidebar-nav li a[href*="logout"] i { color: #dc3545; }
        .sidebar-nav li a[href*="logout"]:hover i { color: #dc3545; transform: scale(1.1); }

        .sidebar-nav li a:hover {
            background: rgba(0,0,0,0.04);
            transform: translateX(4px);
        }

        .sidebar-divider {
            padding: 14px 16px 6px;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #b0b6c8;
            font-weight: 600;
            border-top: 1px solid #eef1f8;
            margin-top: 4px;
        }
        .sidebar-divider:first-child { border-top: none; margin-top: 0; }

        /* ===== MAIN CONTENT ===== */
        .app-main {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: #f0f2f8;
        }

        .app-topbar {
            background: #ffffff;
            border-bottom: 1px solid #e8ecf4;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .app-page-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
            flex: 1;
            color: #1a1a2e;
        }
        .app-topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* ===== CLOCK - EXACT SAME AS DASHBOARD ===== */
        .live-clock {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1a1a2e;
            font-family: 'Inter', monospace;
            background: linear-gradient(135deg, #f0f2f8, #e8ecf4);
            padding: 6px 18px;
            border-radius: 20px;
            border: 1px solid #dce0e8;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);
            letter-spacing: 0.5px;
            min-width: 110px;
            text-align: center;
        }
        .live-clock i {
            margin-right: 6px;
            color: #dc3545;
            font-size: 0.8rem;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #5a607a;
            padding: 4px 8px;
            display: none;
            cursor: pointer;
        }
        .sidebar-toggle:hover { color: #dc3545; }

        .app-content {
            padding: 20px 24px;
            flex: 1;
            max-height: calc(100vh - 70px);
            overflow-y: auto;
        }
        .app-content::-webkit-scrollbar { width: 4px; }
        .app-content::-webkit-scrollbar-track { background: #f5f6fa; }
        .app-content::-webkit-scrollbar-thumb { background: #dc3545; border-radius: 4px; }

        .app-mini-footer {
            padding: 12px 24px;
            text-align: center;
            color: #b0b6c8;
            font-size: 0.7rem;
            border-top: 1px solid #e8ecf4;
            background: #ffffff;
        }
        .app-mini-footer p { margin: 0; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .app-sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            .app-sidebar.open {
                transform: translateX(0);
            }
            .app-main {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: block;
            }
            .app-content {
                padding: 14px 16px;
                max-height: calc(100vh - 65px);
            }
            .app-topbar {
                padding: 10px 16px;
            }
            .app-page-title {
                font-size: 1rem;
            }
            .live-clock {
                font-size: 0.7rem;
                padding: 4px 12px;
                min-width: 90px;
            }
        }

        @media (max-width: 480px) {
            .app-content {
                padding: 10px 12px;
                max-height: calc(100vh - 60px);
            }
            .app-topbar {
                padding: 8px 12px;
            }
            .app-page-title {
                font-size: 0.9rem;
            }
            .live-clock {
                font-size: 0.65rem;
                padding: 3px 10px;
                min-width: 80px;
            }
            .live-clock i {
                display: none;
            }
        }

        /* ===== OVERLAY ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1030;
        }
        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body class="app-body">
<div class="app-wrapper">
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="app-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <a href="<?php echo SITE_URL; ?>">
                <i class="fas fa-hand-holding-heart"></i>
                <span>Asmeera</span>
            </a>
            <small><?php echo $portal_label; ?></small>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar"><i class="fas fa-user"></i></div>
            <div>
                <strong><?php echo htmlspecialchars($user_name); ?></strong>
                <span class="badge"><?php echo ucfirst($role); ?></span>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo SITE_URL; ?>dashboard.php" class="<?php echo navActive('dashboard', $current_page); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>

            <?php if ($role === 'citizen'): ?>
            <li><a href="<?php echo SITE_URL; ?>modules/citizen/request_help.php" class="<?php echo navActive('request_help', $current_page); ?>"><i class="fas fa-exclamation-triangle"></i> Request Help</a></li>
            <li><a href="<?php echo SITE_URL; ?>modules/citizen/safe_checkin.php" class="<?php echo navActive('safe_checkin', $current_page); ?>"><i class="fas fa-check-circle"></i> I Am Safe</a></li>
            <li><a href="<?php echo SITE_URL; ?>modules/citizen/missing_person.php" class="<?php echo navActive('missing_person', $current_page); ?>"><i class="fas fa-user-friends"></i> Missing Person</a></li>

            <?php elseif ($role === 'volunteer'): ?>
            <li><a href="<?php echo SITE_URL; ?>modules/volunteer/tasks.php" class="<?php echo navActive('tasks', $current_page); ?>"><i class="fas fa-tasks"></i> My Tasks</a></li>
            <li><a href="<?php echo SITE_URL; ?>modules/volunteer/availability.php" class="<?php echo navActive('availability', $current_page); ?>"><i class="fas fa-toggle-on"></i> Availability</a></li>

            <?php elseif ($role === 'ngo'): ?>
            <li><a href="<?php echo SITE_URL; ?>modules/ngo/resources.php" class="<?php echo navActive('resources', $current_page); ?>"><i class="fas fa-boxes"></i> Resources</a></li>
            <li><a href="<?php echo SITE_URL; ?>modules/ngo/camps.php" class="<?php echo navActive('camps', $current_page); ?>"><i class="fas fa-campground"></i> Relief Camps</a></li>

            <?php elseif ($role === 'admin'): ?>
            <li class="sidebar-divider">📋 Administration</li>
            <li><a href="<?php echo SITE_URL; ?>modules/admin/users.php" class="<?php echo navActive('users', $current_page); ?>"><i class="fas fa-users-cog"></i> Users</a></li>
            <li><a href="<?php echo SITE_URL; ?>modules/admin/requests.php" class="<?php echo navActive('requests', $current_page); ?>"><i class="fas fa-list-alt"></i> Emergencies</a></li>
            <li><a href="<?php echo SITE_URL; ?>modules/admin/ngos.php" class="<?php echo navActive('ngos', $current_page); ?>"><i class="fas fa-building"></i> NGOs</a></li>
            <li><a href="<?php echo SITE_URL; ?>modules/admin/volunteers.php" class="<?php echo navActive('volunteers', $current_page); ?>"><i class="fas fa-hands-helping"></i> Volunteers</a></li>
            <li><a href="<?php echo SITE_URL; ?>modules/admin/missing_persons.php" class="<?php echo navActive('missing_persons', $current_page); ?>"><i class="fas fa-search"></i> Missing Persons</a></li>
            <li><a href="<?php echo SITE_URL; ?>modules/admin/checkins.php" class="<?php echo navActive('checkins', $current_page); ?>"><i class="fas fa-heart"></i> Safe Check-ins</a></li>
            <?php endif; ?>

            <li class="sidebar-divider">👤 Account</li>
            <li><a href="<?php echo SITE_URL; ?>profile.php" class="<?php echo navActive('profile', $current_page); ?>"><i class="fas fa-user-circle"></i> My Profile</a></li>
            <li><a href="<?php echo SITE_URL; ?>"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="<?php echo SITE_URL; ?>logout.php" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="app-main">
        <header class="app-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="app-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
            <div class="app-topbar-actions">
                <!-- ===== CLOCK - SAME AS DASHBOARD, 12-HOUR FORMAT ===== -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <span class="live-clock" id="liveClock">
                     
                    <span id="clockTime">--:--:--</span>
                </span>
                <?php endif; ?>
                <?php if ($role === 'citizen'): ?>
                <a href="<?php echo SITE_URL; ?>modules/citizen/request_help.php" class="btn btn-sm btn-danger d-none d-md-inline-flex"><i class="fas fa-plus"></i> Emergency</a>
                <?php endif; ?>
            </div>
        </header>
        <div class="app-content">