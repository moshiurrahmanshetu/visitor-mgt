<?php

/**
 * Check if current user has one of the specified roles.
 *
 * Supports:
 * hasRole('Admin')
 * hasRole(['Admin', 'Staff'])
 */
function hasRole($roles)
{
    if (!isset($_SESSION['role_name'])) {
        return false;
    }

    if (is_string($roles)) {
        $roles = [$roles];
    }

    return in_array($_SESSION['role_name'], $roles, true);
}


/**
 * Check if current user has any of the specified roles.
 */
function hasAnyRole($roles)
{
    return hasRole($roles);
}


/**
 * Generate CSRF token for forms.
 */
function generateCsrfToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


/**
 * Validate CSRF token.
 */
function validateCsrfToken($token)
{
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}


/**
 * Regenerate CSRF token.
 */
function regenerateCsrfToken()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}