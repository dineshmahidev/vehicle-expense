<?php
/**
 * Helper Utility Functions
 * Shree Laxmi Expenses
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Format Indian Currency
 */
function format_money($amount) {
    $amount = (float)$amount;
    $is_negative = $amount < 0;
    $amount = abs($amount);
    
    $formatted = number_format($amount, 2, '.', ',');
    // Indian numbering format (e.g., 1,23,456.00)
    $exploded = explode('.', $formatted);
    $integer_part = $exploded[0];
    $decimal_part = $exploded[1];
    
    // Replace standard western comma grouping with Indian lakh/crore if desired
    $len = strlen(str_replace(',', '', $integer_part));
    $raw_int = str_replace(',', '', $integer_part);
    if ($len > 3) {
        $last3 = substr($raw_int, -3);
        $remaining = substr($raw_int, 0, -3);
        $remaining_chunks = str_split(strrev($remaining), 2);
        $grouped = strrev(implode(',', $remaining_chunks));
        $integer_part = $grouped . ',' . $last3;
    }
    
    return ($is_negative ? '-' : '') . APP_CURRENCY . ' ' . $integer_part . '.' . $decimal_part;
}

/**
 * Format date in friendly Indian transport format
 */
function format_date($date_str, $include_time = false) {
    if (empty($date_str)) return '-';
    $time = strtotime($date_str);
    if ($include_time) {
        return date('d M Y, h:i A', $time);
    }
    return date('d M Y', $time);
}

/**
 * Sanitize text input
 */
function clean($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate Next Voucher Number
 */
function generate_voucher_no($pdo) {
    $prefix = 'SLE-' . date('Ym') . '-';
    $stmt = $pdo->prepare("SELECT voucher_no FROM transactions WHERE voucher_no LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    
    if ($last) {
        $num = (int)str_replace($prefix, '', $last) + 1;
    } else {
        $num = 1;
    }
    return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}

/**
 * Fetch all active branches
 */
function get_all_branches($pdo) {
    $stmt = $pdo->query("SELECT * FROM branches ORDER BY id ASC");
    return $stmt->fetchAll();
}

/**
 * Fetch active lorries with assigned branch and current driver info
 */
function get_all_lorries($pdo, $branch_id = null) {
    $sql = "SELECT l.*, 
                   b.branch_name, b.city as branch_city,
                   u.name as static_driver_name, u.phone as static_driver_phone,
                   da.driver_id as dynamic_driver_id,
                   du.name as dynamic_driver_name,
                   da.trip_route as current_trip_route
            FROM lorries l
            LEFT JOIN branches b ON l.branch_id = b.id
            LEFT JOIN users u ON l.static_driver_id = u.id
            LEFT JOIN driver_assignments da ON (da.lorry_id = l.id AND da.status = 'active' AND da.assignment_type = 'dynamic')
            LEFT JOIN users du ON da.driver_id = du.id";
            
    if ($branch_id) {
        $sql .= " WHERE l.branch_id = " . (int)$branch_id;
    }
    $sql .= " ORDER BY l.id ASC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Fetch all active drivers
 */
function get_all_drivers($pdo, $branch_id = null) {
    $sql = "SELECT u.*, b.branch_name 
            FROM users u
            LEFT JOIN branches b ON u.branch_id = b.id
            WHERE u.role = 'driver' AND u.status = 'active'";
    if ($branch_id) {
        $sql .= " AND u.branch_id = " . (int)$branch_id;
    }
    $sql .= " ORDER BY u.name ASC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Fetch Categories grouped by type
 */
function get_categories($pdo, $type = null) {
    if ($type) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE type = ? AND status = 'active' ORDER BY name ASC");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }
    return $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY type ASC, name ASC")->fetchAll();
}

/**
 * CSRF Protection Helpers
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            die("Invalid or expired session request (CSRF check failed). Please refresh and try again.");
        }
    }
}
