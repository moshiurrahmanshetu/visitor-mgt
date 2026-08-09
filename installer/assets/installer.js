/**
 * VAMS Installer JavaScript
 * Handles AJAX requests, form validation, and UI interactions
 */

// Store connection test state
let connectionTested = false;
let lastTestCredentials = {};

// Password visibility toggle
function togglePasswordVisibility(inputId, toggleId) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    
    if (input.type === 'password') {
        input.type = 'text';
        toggle.innerHTML = '👁️';
    } else {
        input.type = 'password';
        toggle.innerHTML = '👁️‍🗨️';
    }
}

// Test database connection (AJAX)
function testConnection() {
    const host = document.getElementById('db_host').value.trim();
    const port = document.getElementById('db_port').value.trim();
    const username = document.getElementById('db_username').value.trim();
    const password = document.getElementById('db_password').value;
    
    const resultDiv = document.getElementById('connection_result');
    const testBtn = document.getElementById('test_connection_btn');
    
    // Disable button and show loading
    testBtn.disabled = true;
    testBtn.innerHTML = 'Testing...';
    resultDiv.className = 'alert alert-info';
    resultDiv.innerHTML = 'Testing connection...';
    
    // Send AJAX request
    fetch('process_test_connection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            host: host,
            port: port,
            username: username,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.className = 'alert alert-success';
            resultDiv.innerHTML = '✅ ' + data.message;
            connectionTested = true;
            lastTestCredentials = { host, port, username, password };
        } else {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = '❌ ' + data.message;
            connectionTested = false;
        }
    })
    .catch(error => {
        resultDiv.className = 'alert alert-danger';
        resultDiv.innerHTML = '❌ Connection failed: ' + error.message;
        connectionTested = false;
    })
    .finally(() => {
        testBtn.disabled = false;
        testBtn.innerHTML = 'Test Connection';
    });
}

// Invalidate connection test on credential change
function invalidateConnectionTest() {
    const host = document.getElementById('db_host').value.trim();
    const port = document.getElementById('db_port').value.trim();
    const username = document.getElementById('db_username').value.trim();
    const password = document.getElementById('db_password').value;
    
    if (host !== lastTestCredentials.host || 
        port !== lastTestCredentials.port || 
        username !== lastTestCredentials.username || 
        password !== lastTestCredentials.password) {
        connectionTested = false;
    }
}

// File upload display
function handleFileUpload(inputId, displayId) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(displayId);
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        display.textContent = 'Selected: ' + file.name + ' (' + formatFileSize(file.size) + ')';
    } else {
        display.textContent = '';
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Validate Step 2 form before proceeding
function validateStep2() {
    const host = document.getElementById('db_host').value.trim();
    const port = document.getElementById('db_port').value.trim();
    const dbname = document.getElementById('db_name').value.trim();
    const username = document.getElementById('db_username').value.trim();
    const fileInput = document.getElementById('db_file');
    
    const errors = [];
    
    if (!host) errors.push('Database Host is required');
    if (!port) errors.push('Database Port is required');
    if (!dbname) errors.push('Database Name is required');
    if (!username) errors.push('Database Username is required');
    
    if (!fileInput.files || !fileInput.files[0]) {
        errors.push('Please upload the SQL database file');
    } else if (!fileInput.files[0].name.toLowerCase().endsWith('.sql')) {
        errors.push('Only .sql files are allowed');
    }
    
    if (!connectionTested) {
        errors.push('Please test the database connection first');
    }
    
    if (errors.length > 0) {
        alert('Please fix the following errors:\n\n' + errors.join('\n'));
        return false;
    }
    
    return true;
}

// Validate Step 3 form before proceeding
function validateStep3() {
    const appName = document.getElementById('app_name').value.trim();
    const adminName = document.getElementById('admin_name').value.trim();
    const adminEmail = document.getElementById('admin_email').value.trim();
    const adminPassword = document.getElementById('admin_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    const errors = [];
    
    if (!appName) errors.push('Application Name is required');
    if (!adminName) errors.push('Admin Full Name is required');
    if (!adminEmail) errors.push('Admin Email is required');
    if (!adminPassword) errors.push('Admin Password is required');
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (adminEmail && !emailRegex.test(adminEmail)) {
        errors.push('Please enter a valid email address');
    }
    
    // Password validation
    if (adminPassword && adminPassword.length < 8) {
        errors.push('Password must be at least 8 characters');
    }
    if (adminPassword && !/[a-zA-Z]/.test(adminPassword)) {
        errors.push('Password must contain at least one letter');
    }
    if (adminPassword && !/[0-9]/.test(adminPassword)) {
        errors.push('Password must contain at least one number');
    }
    
    if (adminPassword !== confirmPassword) {
        errors.push('Passwords do not match');
    }
    
    if (errors.length > 0) {
        alert('Please fix the following errors:\n\n' + errors.join('\n'));
        return false;
    }
    
    return true;
}

// Run installation (AJAX with progress updates)
function runInstallation() {
    const installBtn = document.getElementById('install_btn');
    const progressDiv = document.getElementById('install_progress');
    
    // Disable button and show loading
    installBtn.disabled = true;
    installBtn.innerHTML = 'Installing...';
    progressDiv.innerHTML = '';
    
    // Send AJAX request
    fetch('process_install.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success page
            document.getElementById('install_form').style.display = 'none';
            document.getElementById('success_page').style.display = 'block';
            document.getElementById('admin_email_display').textContent = data.admin_email;
        } else {
            // Show error
            installBtn.disabled = false;
            installBtn.innerHTML = 'Install Now';
            alert('Installation failed: ' + data.message);
        }
    })
    .catch(error => {
        installBtn.disabled = false;
        installBtn.innerHTML = 'Install Now';
        alert('Installation failed: ' + error.message);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for Step 2
    const dbInputs = ['db_host', 'db_port', 'db_username', 'db_password'];
    dbInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', invalidateConnectionTest);
        }
    });
    
    // Add event listener for file upload
    const fileInput = document.getElementById('db_file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            handleFileUpload('db_file', 'file_display');
        });
    }
});
