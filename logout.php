<?php
/**
 * Logout Handler
 * Shree Laxmi Expenses
 */

require_once __DIR__ . '/config/config.php';

session_unset();
session_destroy();

session_start();
$_SESSION['flash'] = [
    'type' => 'info',
    'message' => 'You have successfully signed out.'
];

header("Location: " . BASE_URL . "/login.php");
exit;
