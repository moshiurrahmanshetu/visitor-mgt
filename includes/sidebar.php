<?php
/**
 * VAMS - Visitor Access Management System
 * Sidebar Include File
 * 
 * Collapsible left sidebar with navigation menu
 */

if (!defined('VAMS_INCLUDED')) {
    require_once __DIR__ . '/../config/constants.php';
}

$current_user_role = getCurrentUserRole();
$current_path = $_SERVER['REQUEST_URI'];
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h5 class="sidebar-brand">
            <i class="bi bi-shield-check text-primary me-2"></i>
            VAMS
        </h5>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/dashboard/index.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/dashboard/index.php">
                    <i class="bi bi-speedometer2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            
            <!-- Profile -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/profile/') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/profile/profile.php">
                    <i class="bi bi-person"></i>
                    <span class="nav-text">Profile</span>
                </a>
            </li>
            
            <!-- Visitor Management -->
            <li class="nav-item sidebar-section">
                <span class="nav-text text-muted small">Visitor Management</span>
            </li>
            
            <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/visitors/add.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/visitors/add.php">
                    <i class="bi bi-person-plus"></i>
                    <span class="nav-text">Add Visitor</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/visitors/list.php') !== false || strpos($current_path, '/modules/visitors/view.php') !== false || strpos($current_path, '/modules/visitors/edit.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/visitors/list.php">
                    <i class="bi bi-people"></i>
                    <span class="nav-text">Visitor List</span>
                </a>
            </li>
            
            <!-- Visit Management -->
            <li class="nav-item sidebar-section">
                <span class="nav-text text-muted small">Visit Management</span>
            </li>
            
            <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/visits/add.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/visits/add.php">
                    <i class="bi bi-calendar-plus"></i>
                    <span class="nav-text">Create Visit</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($current_user_role === 'Employee'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/visits/list.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/visits/list.php?status=Pending">
                    <i class="bi bi-clock-history"></i>
                    <span class="nav-text">My Approvals</span>
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/visits/list.php') !== false || strpos($current_path, '/modules/visits/view.php') !== false || strpos($current_path, '/modules/visits/edit.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/visits/list.php">
                    <i class="bi bi-calendar-check"></i>
                    <span class="nav-text">All Visits</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Check-In / Check-Out (Hidden for Employee) -->
            <?php if (in_array($current_user_role, ['Admin', 'Security', 'Receptionist'])): ?>
            <li class="nav-item sidebar-section">
                <span class="nav-text text-muted small">Check-In / Check-Out</span>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/checkinout/checkin.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/checkinout/checkin.php">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span class="nav-text">Check-In</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/checkinout/checkout.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/checkinout/checkout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="nav-text">Check-Out</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/checkinout/currently_inside.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/checkinout/currently_inside.php">
                    <i class="bi bi-people-fill"></i>
                    <span class="nav-text">Currently Inside</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Reports (Hidden for Employee) -->
            <?php if (in_array($current_user_role, ['Admin', 'Receptionist', 'Security'])): ?>
            <li class="nav-item sidebar-section">
                <span class="nav-text text-muted small">Reports</span>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/reports/index.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/reports/index.php">
                    <i class="bi bi-graph-up"></i>
                    <span class="nav-text">Report Dashboard</span>
                </a>
            </li>
            
            <?php if (in_array($current_user_role, ['Admin', 'Receptionist'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/reports/visitor_history.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/reports/visitor_history.php">
                    <i class="bi bi-clock-history"></i>
                    <span class="nav-text">Visitor History</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/reports/date_wise.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/reports/date_wise.php">
                    <i class="bi bi-calendar-range"></i>
                    <span class="nav-text">Date-Wise Report</span>
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>
            
            <li class="nav-item sidebar-section">
                <span class="nav-text text-muted small">Management</span>
            </li>
            
            <li class="nav-item disabled">
                <a class="nav-link" href="#">
                    <i class="bi bi-check-circle"></i>
                    <span class="nav-text">Approvals</span>
                </a>
            </li>
            
            <!-- Admin-only sections -->
            <?php if ($current_user_role === 'Admin'): ?>
            <li class="nav-item sidebar-section">
                <span class="nav-text text-muted small">Administration</span>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/users/add.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/users/add.php">
                    <i class="bi bi-person-plus"></i>
                    <span class="nav-text">Add User</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($current_path, '/modules/users/list.php') !== false || strpos($current_path, '/modules/users/view.php') !== false || strpos($current_path, '/modules/users/edit.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/modules/users/list.php">
                    <i class="bi bi-people-fill"></i>
                    <span class="nav-text">User List</span>
                </a>
            </li>
            
            <li class="nav-item disabled">
                <a class="nav-link" href="#">
                    <i class="bi bi-gear"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
