<?php
/**
 * VAMS - Visitor Access Management System
 * Navbar Include File
 * 
 * Top navigation bar with user info and logout button
 */

if (!defined('VAMS_INCLUDED')) {
    require_once __DIR__ . '/../config/constants.php';
}

$current_user_name = getCurrentUserName();
$current_user_role = getCurrentUserRole();
$current_user_avatar = getCurrentUserAvatar();
$avatar_url = $current_user_avatar ? ASSETS_URL . '/uploads/avatars/' . $current_user_avatar : 'https://via.placeholder.com/40';
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom fixed-top">
    <div class="container-fluid">
        <!-- Sidebar Toggle Button -->
        <button class="btn btn-outline-secondary me-2" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- Brand -->
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/dashboard/index.php">
            <i class="bi bi-shield-check text-primary me-2"></i>
            VAMS
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navbar Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- User Info -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <img src="<?php echo $avatar_url; ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($current_user_name); ?></span>
                        <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($current_user_role); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/index.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/profile/profile.php">
                            <i class="bi bi-person me-2"></i>Profile
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/profile/change_password.php">
                            <i class="bi bi-key me-2"></i>Change Password
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/modules/auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
