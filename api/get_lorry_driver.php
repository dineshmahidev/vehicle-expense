<?php
/**
 * API Endpoint: Get Assigned Driver & Branch for a Lorry
 * Shree Laxmi Expenses
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$lorry_id = isset($_GET['lorry_id']) ? (int)$_GET['lorry_id'] : 0;

if (!$lorry_id) {
    echo json_encode(['success' => false, 'message' => 'Lorry ID is required']);
    exit;
}

try {
    // 1. Check for Active Dynamic Assignment first
    $stmt = $pdo->prepare("SELECT da.driver_id, u.name as driver_name, da.branch_id, 'dynamic' as assignment_type, da.trip_route
                           FROM driver_assignments da
                           JOIN users u ON da.driver_id = u.id
                           WHERE da.lorry_id = ? AND da.status = 'active'
                           ORDER BY da.id DESC LIMIT 1");
    $stmt->execute([$lorry_id]);
    $dynamic = $stmt->fetch();

    if ($dynamic) {
        echo json_encode([
            'success' => true,
            'driver_id' => $dynamic['driver_id'],
            'driver_name' => $dynamic['driver_name'],
            'branch_id' => $dynamic['branch_id'],
            'assignment_type' => 'dynamic',
            'trip_route' => $dynamic['trip_route']
        ]);
        exit;
    }

    // 2. Otherwise get static default driver
    $stmt2 = $pdo->prepare("SELECT l.static_driver_id as driver_id, u.name as driver_name, l.branch_id, 'static' as assignment_type
                            FROM lorries l
                            LEFT JOIN users u ON l.static_driver_id = u.id
                            WHERE l.id = ?");
    $stmt2->execute([$lorry_id]);
    $static = $stmt2->fetch();

    if ($static) {
        echo json_encode([
            'success' => true,
            'driver_id' => $static['driver_id'],
            'driver_name' => $static['driver_name'],
            'branch_id' => $static['branch_id'],
            'assignment_type' => 'static',
            'trip_route' => ''
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lorry not found']);
    }
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
