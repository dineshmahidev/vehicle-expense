<?php
/**
 * Global Configuration and Session Management
 * Shree Laxmi Expenses
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Shree Laxmi Expenses');
define('APP_TAGLINE', 'Lorry Fleet & Transport Expense Management');
define('APP_CURRENCY', '₹');
define('BASE_URL', '/vehicle');

// Include Database
require_once __DIR__ . '/database.php';

// Authentication Helpers
function is_logged_in() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function user_role() {
    return $_SESSION['user']['role'] ?? 'guest';
}

function is_admin() {
    return user_role() === 'admin';
}

function is_manager() {
    return in_array(user_role(), ['admin', 'branch_manager']);
}

function is_driver() {
    return user_role() === 'driver';
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Please sign in to access this page.'
        ];
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Access Denied: Administrator rights required.'
        ];
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}

function require_manager() {
    require_login();
    if (!is_manager()) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Access Denied: Manager or Administrator rights required.'
        ];
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
