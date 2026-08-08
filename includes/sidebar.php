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
            
            <!-- Visits (Phase 3 - disabled) -->
            <li class="nav-item disabled">
                <a class="nav-link" href="#">
                    <i class="bi bi-calendar-check"></i>
                    <span class="nav-text">Visits</span>
                </a>
            </li>
            
            <li class="nav-item sidebar-section">
                <span class="nav-text text-muted small">Management</span>
            </li>
            
            <li class="nav-item disabled">
                <a class="nav-link" href="#">
                    <i class="bi bi-check-circle"></i>
                    <span class="nav-text">Approvals</span>
                </a>
            </li>
            
            <li class="nav-item disabled">
                <a class="nav-link" href="#">
                    <i class="bi bi-graph-up"></i>
                    <span class="nav-text">Reports</span>
                </a>
            </li>
            
            <!-- Admin-only sections -->
            <?php if ($current_user_role === 'Admin'): ?>
            <li class="nav-item sidebar-section">
                <span class="nav-text text-muted small">Administration</span>
            </li>
            
            <li class="nav-item disabled">
                <a class="nav-link" href="#">
                    <i class="bi bi-people-fill"></i>
                    <span class="nav-text">Users</span>
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
