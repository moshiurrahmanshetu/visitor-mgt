<?php
/**
 * VAMS - Visitor Access Management System
 * Footer Include File
 * 
 * Contains closing body and html tags, JavaScript includes
 */

if (!defined('VAMS_INCLUDED')) {
    require_once __DIR__ . '/../config/constants.php';
}
?>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="<?php echo ASSETS_URL; ?>/js/sidebar.js"></script>

<!-- Form Validation Script -->
<script>
    // Bootstrap form validation
    (function() {
        'use strict';
        
        // Fetch all forms that need validation
        var forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
    })();
</script>

</body>
</html>
