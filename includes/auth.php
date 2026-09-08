<?php
/**
 * Authentication & Session Helpers
 * Provides login checks, role-based authorization, and flash message system.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the logged-in user is an admin
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require user to be logged in, redirect to login page if not
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to access this page.');
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

/**
 * Require user to be an admin, redirect if not
 */
function requireAdmin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to access this page.');
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
    if (!isAdmin()) {
        setFlash('error', 'You do not have permission to access that page.');
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

/**
 * Set a flash message to display on the next page load
 * @param string $type - 'success', 'error', 'warning', 'info'
 * @param string $message - The message text
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear the current flash message
 * @return array|null - ['type' => ..., 'message' => ...] or null
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
