/**
 * VAMS - Visitor Access Management System
 * Sidebar Toggle Functionality
 * 
 * Handles collapsible sidebar with smooth transitions
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    // Check if elements exist
    if (!sidebar || !mainContent || !sidebarToggle) {
        console.warn('Sidebar elements not found');
        return;
    }
    
    // Load sidebar state from localStorage
    const sidebarState = localStorage.getItem('sidebarCollapsed');
    if (sidebarState === 'true') {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
    }
    
    // Toggle sidebar on button click
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
    });
    
    // Toggle sidebar function
    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        
        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
        
        // Update toggle button icon
        const icon = sidebarToggle.querySelector('i');
        if (icon) {
            if (isCollapsed) {
                icon.classList.remove('bi-list');
                icon.classList.add('bi-arrow-right');
            } else {
                icon.classList.remove('bi-arrow-right');
                icon.classList.add('bi-list');
            }
        }
    }
    
    // Handle mobile sidebar toggle
    if (window.innerWidth <= 768) {
        // On mobile, sidebar is hidden by default
        sidebar.classList.add('collapsed');
        mainContent.classList.remove('expanded');
        
        // Add mobile toggle functionality
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-open');
        }
    });
    
    // Initialize toggle button icon
    const icon = sidebarToggle.querySelector('i');
    if (icon && sidebar.classList.contains('collapsed')) {
        icon.classList.remove('bi-list');
        icon.classList.add('bi-arrow-right');
    }
});

/**
 * Close sidebar on mobile when a nav link is clicked
 */
function closeMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && window.innerWidth <= 768) {
        sidebar.classList.remove('mobile-open');
    }
}

// Add click event to all nav links to close mobile sidebar
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link:not(.disabled)');
    navLinks.forEach(function(link) {
        link.addEventListener('click', closeMobileSidebar);
    });
});
