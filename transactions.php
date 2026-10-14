<?php
/**
 * Income & Expense Ledger / Transactions
 * Shree Laxmi Expenses
 */

$page_title = 'Income & Expense Ledger';
require_once __DIR__ . '/includes/header.php';

// Handle Delete Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();
    require_manager();
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->execute([$id]);
    set_flash('success', 'Transaction entry deleted successfully.');
    header("Location: transactions.php");
    exit;
}

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$lorry_filter = isset($_GET['lorry_id']) && $_GET['lorry_id'] !== '' ? (int)$_GET['lorry_id'] : null;
$driver_filter = isset($_GET['driver_id']) && $_GET['driver_id'] !== '' ? (int)$_GET['driver_id'] : null;
$branch_filter = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;
$type_filter = clean($_GET['type'] ?? '');
$category_filter = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;

// Restrict driver to their own transactions if driver
if (is_driver()) {
    $driver_filter = (int)$user['id'];
}

// Build Query
$conditions = ["t.transaction_date BETWEEN :start AND :end"];
$params = [':start' => $start_date, ':end' => $end_date];

if ($lorry_filter) {
    $conditions[] = "t.lorry_id = :lorry_id";
    $params[':lorry_id'] = $lorry_filter;
}
if ($driver_filter) {
    $conditions[] = "t.driver_id = :driver_id";
    $params[':driver_id'] = $driver_filter;
}
if ($branch_filter) {
    $conditions[] = "t.branch_id = :branch_id";
    $params[':branch_id'] = $branch_filter;
}
if ($type_filter) {
    $conditions[] = "t.type = :type";
    $params[':type'] = $type_filter;
}
if ($category_filter) {
    $conditions[] = "t.category_id = :cat_id";
    $params[':cat_id'] = $category_filter;
}

$where_clause = implode(" AND ", $conditions);

// Fetch Summary for filtered set
$summary_sql = "SELECT 
                    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expense,
                    COALESCE(SUM(CASE WHEN c.name LIKE '%Diesel%' OR c.name LIKE '%Fuel%' THEN t.amount ELSE 0 END), 0) as total_diesel,
                    COUNT(t.id) as total_records
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE $where_clause";
$stmt_sum = $pdo->prepare($summary_sql);
$stmt_sum->execute($params);
$sum = $stmt_sum->fetch();

$total_filtered_income = (float)$sum['total_income'];
$total_filtered_expense = (float)$sum['total_expense'];
$filtered_profit = $total_filtered_income - $total_filtered_expense;

// Fetch Transactions
$sql = "SELECT t.*, l.lorry_number, l.model_name, u.name as driver_name, c.name as category_name, c.color as category_color, b.branch_name
        FROM transactions t
        JOIN lorries l ON t.lorry_id = l.id
        JOIN users u ON t.driver_id = u.id
        JOIN categories c ON t.category_id = c.id
        JOIN branches b ON t.branch_id = b.id
        WHERE $where_clause
        ORDER BY t.transaction_date DESC, t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ShreeLaxmi_Ledger_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Voucher No', 'Date', 'Type', 'Lorry Number', 'Driver', 'Branch', 'Category', 'Amount (INR)', 'Payment Mode', 'Fuel Litres', 'Fuel Rate', 'Odometer', 'Route', 'Bill Slip No', 'Party Vendor', 'Notes']);
    foreach ($transactions as $row) {
        fputcsv($out, [
            $row['voucher_no'],
            $row['transaction_date'],
            strtoupper($row['type']),
            $row['lorry_number'],
            $row['driver_name'],
            $row['branch_name'],
            $row['category_name'],
            $row['amount'],
            $row['payment_mode'],
            $row['fuel_liters'] ?? '',
            $row['fuel_rate'] ?? '',
            $row['odometer_reading'] ?? '',
            $row['trip_route'] ?? '',
            $row['bill_invoice_no'] ?? '',
            $row['vendor_party_name'] ?? '',
            $row['description'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

$branches = get_all_branches($pdo);
$lorries = get_all_lorries($pdo);
$drivers = get_all_drivers($pdo);
$categories = get_categories($pdo);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Income & Expense Ledger</h1>
        <p class="page-subtitle">Unified transaction stream, route expenditures, fuel entries, and customer freight payments</p>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="<?= $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'export=csv' ?>" class="btn btn-outline btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Export to CSV / Excel</span>
        </a>
        <a href="add-transaction.php" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Add Entry</span>
        </a>
    </div>
</div>

<!-- Ledger Summary Strip -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom:20px;">
    <div class="stat-card" style="padding:16px;">
        <span class="stat-label">Filtered Freight Income</span>
        <div class="stat-value" style="font-size:20px; color:#10b981; margin-top:6px;"><?= format_money($total_filtered_income) ?></div>
    </div>
    <div class="stat-card" style="padding:16px;">
        <span class="stat-label">Filtered Fleet Expenses</span>
        <div class="stat-value" style="font-size:20px; color:#ef4444; margin-top:6px;"><?= format_money($total_filtered_expense) ?></div>
    </div>
    <div class="stat-card" style="padding:16px;">
        <span class="stat-label">Filtered Net Profit</span>
        <div class="stat-value" style="font-size:20px; color:<?= $filtered_profit >= 0 ? '#10b981' : '#ef4444' ?>; margin-top:6px;">
            <?= format_money($filtered_profit) ?>
        </div>
    </div>
    <div class="stat-card" style="padding:16px;">
        <span class="stat-label">Filtered Diesel Cost</span>
        <div class="stat-value" style="font-size:20px; color:#f59e0b; margin-top:6px;"><?= format_money($sum['total_diesel']) ?></div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-card">
    <form method="GET" action="transactions.php" class="filter-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) auto;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">From Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= clean($start_date) ?>">
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">To Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= clean($end_date) ?>">
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="">All Types</option>
                <option value="income" <?= $type_filter === 'income' ? 'selected' : '' ?>>Income Only</option>
                <option value="expense" <?= $type_filter === 'expense' ? 'selected' : '' ?>>Expense Only</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Lorry</label>
            <select name="lorry_id" class="form-select">
                <option value="">All Lorries</option>
                <?php foreach ($lorries as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $lorry_filter == $l['id'] ? 'selected' : '' ?>>
                        <?= clean($l['lorry_number']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (!is_driver()): ?>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Driver</label>
                <select name="driver_id" class="form-select">
                    <option value="">All Drivers</option>
                    <?php foreach ($drivers as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $driver_filter == $d['id'] ? 'selected' : '' ?>>
                            <?= clean($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $category_filter == $c['id'] ? 'selected' : '' ?>>
                        <?= clean($c['name']) ?> (<?= ucfirst($c['type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn btn-primary" style="height:42px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <span>Filter</span>
            </button>
            <a href="transactions.php" class="btn btn-outline" style="height:42px;" title="Reset filter">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
            </a>
        </div>
    </form>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Voucher # / Date</th>
                        <th>Type</th>
                        <th>Lorry & Driver</th>
                        <th>Category</th>
                        <th>Trip Route / Bill Details</th>
                        <th style="text-align:right;">Amount</th>
                        <th>Payment Mode</th>
                        <th style="text-align:center;">Voucher / Receipt</th>
                        <?php if (is_manager()): ?>
                            <th style="text-align:center;">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:40px; color:#94a3b8;">
                                No transactions found matching the filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td>
                                    <a href="voucher.php?id=<?= $t['id'] ?>" style="font-weight:700; color:var(--primary); font-size:13px;">
                                        <?= clean($t['voucher_no']) ?>
                                    </a>
                                    <div style="font-size:11.5px; color:#64748b;"><?= format_date($t['transaction_date']) ?></div>
                                </td>
                                <td>
                                    <span class="pill <?= $t['type'] === 'income' ? 'pill-income' : 'pill-expense' ?>">
                                        <?= ucfirst($t['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:700; color:#0f172a; font-size:13px;"><?= clean($t['lorry_number']) ?></div>
                                    <small style="color:#64748b; font-size:11px;">👤 <?= clean($t['driver_name']) ?></small>
                                </td>
                                <td>
                                    <span class="pill" style="background:#f1f5f9; color:#334155; font-weight:600;">
                                        <?= clean($t['category_name']) ?>
                                    </span>
                                    <?php if (!empty($t['fuel_liters'])): ?>
                                        <div style="font-size:11px; color:#b45309; margin-top:3px; font-weight:600;">
                                            ⛽ <?= $t['fuel_liters'] ?> L @ ₹<?= $t['fuel_rate'] ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($t['trip_route'])): ?>
                                        <div style="font-weight:600; font-size:12px; color:#0f172a;"><?= clean($t['trip_route']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($t['bill_invoice_no'])): ?>
                                        <small style="color:#64748b; font-size:11px;">Slip: <?= clean($t['bill_invoice_no']) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($t['vendor_party_name'])): ?>
                                        <small style="color:#64748b; font-size:11px; display:block;">Party: <?= clean($t['vendor_party_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right; font-weight:800; font-size:14px; color:<?= $t['type'] === 'income' ? '#10b981' : '#ef4444' ?>;">
                                    <?= $t['type'] === 'income' ? '+' : '-' ?><?= format_money($t['amount']) ?>
                                </td>
                                <td>
                                    <span class="pill pill-static" style="font-size:11px; text-transform:uppercase;">
                                        <?= str_replace('_', ' ', $t['payment_mode']) ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:flex; justify-content:center; gap:6px;">
                                        <a href="voucher.php?id=<?= $t['id'] ?>" class="btn btn-outline btn-sm" title="View / Print PDF Voucher" style="padding:4px 8px;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                        </a>
                                        <?php if (!empty($t['receipt_image'])): ?>
                                            <a href="<?= clean($t['receipt_image']) ?>" target="_blank" class="btn btn-outline btn-sm" title="View Uploaded Receipt" style="padding:4px 8px; color:#4f46e5;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php if (is_manager()): ?>
                                    <td style="text-align:center;">
                                        <form method="POST" action="transactions.php" onsubmit="return confirm('Are you sure you want to delete this transaction voucher?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn-logout" title="Delete Entry" style="padding:4px;">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
