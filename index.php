<?php
/**
 * Modern Analytics Dashboard
 * Shree Laxmi Expenses
 */

$page_title = 'Fleet Analytics Dashboard';
require_once __DIR__ . '/includes/header.php';

// Date filter for stats (default: current month)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$branch_filter = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;

// Restrict branch manager to their own branch
if ($user['role'] === 'branch_manager' && !empty($user['branch_id'])) {
    $branch_filter = (int)$user['branch_id'];
}

// 1. Calculate KPI Metrics
$where = "WHERE transaction_date BETWEEN :start AND :end";
$params = [':start' => $start_date, ':end' => $end_date];

if ($branch_filter) {
    $where .= " AND branch_id = :branch_id";
    $params[':branch_id'] = $branch_filter;
}

// Total Income
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions $where AND type = 'income'");
$stmt->execute($params);
$total_income = (float)$stmt->fetchColumn();

// Total Expense
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions $where AND type = 'expense'");
$stmt->execute($params);
$total_expense = (float)$stmt->fetchColumn();

// Net Profit
$net_profit = $total_income - $total_expense;
$profit_margin = $total_income > 0 ? round(($net_profit / $total_income) * 100, 1) : 0;

// Diesel Specific Stats
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as diesel_amount, COALESCE(SUM(fuel_liters), 0) as diesel_liters 
                       FROM transactions t 
                       JOIN categories c ON t.category_id = c.id 
                       $where AND (c.name LIKE '%Diesel%' OR c.name LIKE '%Fuel%')");
$stmt->execute($params);
$diesel_stats = $stmt->fetch();
$diesel_cost = (float)($diesel_stats['diesel_amount'] ?? 0);
$diesel_liters = (float)($diesel_stats['diesel_liters'] ?? 0);

// Active Fleet Count
$stmt_fleet = $pdo->query("SELECT COUNT(*) as total_lorries, 
                                  SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_lorries 
                           FROM lorries");
$fleet_info = $stmt_fleet->fetch();

// 2. Fetch Category Breakdown for Doughnut Chart
$stmt_cat = $pdo->prepare("SELECT c.name, c.color, SUM(t.amount) as total_cat_amount 
                          FROM transactions t 
                          JOIN categories c ON t.category_id = c.id 
                          $where AND t.type = 'expense' 
                          GROUP BY c.id, c.name, c.color 
                          ORDER BY total_cat_amount DESC LIMIT 7");
$stmt_cat->execute($params);
$expense_categories = $stmt_cat->fetchAll();

// 3. Fetch Recent Transactions
$stmt_txns = $pdo->prepare("SELECT t.*, l.lorry_number, u.name as driver_name, c.name as category_name, c.color as category_color, b.branch_name
                           FROM transactions t
                           JOIN lorries l ON t.lorry_id = l.id
                           JOIN users u ON t.driver_id = u.id
                           JOIN categories c ON t.category_id = c.id
                           JOIN branches b ON t.branch_id = b.id
                           $where
                           ORDER BY t.transaction_date DESC, t.id DESC LIMIT 8");
$stmt_txns->execute($params);
$recent_txns = $stmt_txns->fetchAll();

// 4. Fetch Lorry Quick Overview
$lorries_overview = get_all_lorries($pdo, $branch_filter);
$branches = get_all_branches($pdo);
?>

<!-- Dashboard Header with Filter Bar -->
<div class="page-header">
    <div>
        <h1 class="page-title">Fleet Dashboard</h1>
        <p class="page-subtitle">Real-time overview of lorry logistics, freight income, fuel & operating expenses</p>
    </div>
    
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="lorry-report.php" class="btn btn-outline btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            <span>P&L Statement</span>
        </a>
        <a href="add-transaction.php" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Add Income/Expense</span>
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-card">
    <form method="GET" action="index.php" class="filter-grid">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">From Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= clean($start_date) ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">To Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= clean($end_date) ?>">
        </div>
        
        <?php if (is_admin()): ?>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Branch Hub</label>
                <select name="branch_id" class="form-select">
                    <option value="">All Branches (All India)</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $branch_filter == $b['id'] ? 'selected' : '' ?>>
                            <?= clean($b['branch_name']) ?> (<?= clean($b['city']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn btn-primary" style="height:42px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <span>Filter</span>
            </button>
            <a href="index.php" class="btn btn-outline" style="height:42px;" title="Reset filter">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
            </a>
        </div>
    </form>
</div>

<!-- Modern KPI Stats Grid -->
<div class="stats-grid">
    <!-- Freight Income -->
    <div class="stat-card stat-income">
        <div class="stat-top">
            <span class="stat-label">Total Freight Income</span>
            <div class="stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
        </div>
        <div class="stat-value"><?= format_money($total_income) ?></div>
        <div class="stat-subtext">
            <span class="stat-badge badge-profit">Revenue</span>
            <span>Period cargo loads</span>
        </div>
    </div>

    <!-- Operating Expenses -->
    <div class="stat-card stat-expense">
        <div class="stat-top">
            <span class="stat-label">Total Fleet Expense</span>
            <div class="stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>
            </div>
        </div>
        <div class="stat-value"><?= format_money($total_expense) ?></div>
        <div class="stat-subtext">
            <span class="stat-badge badge-loss">Outflow</span>
            <span>Fuel, Toll, Hamali & Bhatta</span>
        </div>
    </div>

    <!-- Net Profit -->
    <div class="stat-card stat-profit">
        <div class="stat-top">
            <span class="stat-label">Net Fleet Profit / P&L</span>
            <div class="stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="16 12 12 8 8 12"></polyline><line x1="12" y1="16" x2="12" y2="8"></line></svg>
            </div>
        </div>
        <div class="stat-value" style="color: <?= $net_profit >= 0 ? '#10b981' : '#ef4444' ?>;">
            <?= format_money($net_profit) ?>
        </div>
        <div class="stat-subtext">
            <span class="stat-badge <?= $net_profit >= 0 ? 'badge-profit' : 'badge-loss' ?>">
                <?= $profit_margin ?>% Margin
            </span>
            <span>After all trip costs</span>
        </div>
    </div>

    <!-- Diesel Expense -->
    <div class="stat-card stat-diesel">
        <div class="stat-top">
            <span class="stat-label">Diesel Fuel Cost</span>
            <div class="stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v20"></path><path d="M21 12V6a2 2 0 0 0-2-2H7v16h12a2 2 0 0 0 2-2v-3"></path><line x1="3" y1="10" x2="7" y2="10"></line></svg>
            </div>
        </div>
        <div class="stat-value"><?= format_money($diesel_cost) ?></div>
        <div class="stat-subtext">
            <span class="stat-badge" style="background:#fef3c7; color:#b45309;"><?= number_format($diesel_liters, 1) ?> L</span>
            <span><?= $total_expense > 0 ? round(($diesel_cost / $total_expense) * 100, 1) : 0 ?>% of total expense</span>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="charts-grid">
    <!-- Cash Flow Bar/Area Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M18 9l-5 5-4-4-6 6"></path></svg>
                <span>Revenue vs Operational Expense</span>
            </div>
            <span class="pill pill-income">Filtered Timeline</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="cashflowChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Expense Breakdown Doughnut -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                <span>Expense Categories</span>
            </div>
            <span class="pill pill-expense">Breakdown</span>
        </div>
        <div class="card-body">
            <div class="chart-container" style="min-height:280px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Split Tables: Recent Transactions & Fleet Status -->
<div style="display:grid; grid-template-columns: 1.6fr 1.2fr; gap:24px;">
    <!-- Recent Transactions -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                <span>Recent Transactions</span>
            </div>
            <a href="transactions.php" class="btn btn-outline btn-sm">View All Ledger</a>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Voucher / Date</th>
                            <th>Lorry & Driver</th>
                            <th>Category</th>
                            <th style="text-align:right;">Amount</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_txns)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">
                                    No entries found for selected date range.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_txns as $txn): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; font-size:12.5px;"><?= clean($txn['voucher_no']) ?></div>
                                        <small style="color:#64748b;"><?= format_date($txn['transaction_date']) ?></small>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; font-size:13px; color:#0f172a;"><?= clean($txn['lorry_number']) ?></div>
                                        <small style="color:#64748b;"><?= clean($txn['driver_name']) ?></small>
                                    </td>
                                    <td>
                                        <span class="pill" style="background: <?= $txn['type'] === 'income' ? '#d1fae5' : '#fee2e2' ?>; color: <?= $txn['type'] === 'income' ? '#065f46' : '#991b1b' ?>;">
                                            <?= clean($txn['category_name']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right; font-weight:700; color: <?= $txn['type'] === 'income' ? '#10b981' : '#ef4444' ?>;">
                                        <?= $txn['type'] === 'income' ? '+' : '-' ?><?= format_money($txn['amount']) ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="voucher.php?id=<?= $txn['id'] ?>" class="btn btn-outline btn-sm" title="Print/PDF Voucher" style="padding:4px 8px;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Fleet Status & Driver Assignments -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                <span>Fleet Drivers & Trips</span>
            </div>
            <a href="assignments.php" class="btn btn-outline btn-sm">Assign Driver</a>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Lorry</th>
                            <th>Active Driver</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lorries_overview as $l): ?>
                            <tr>
                                <td>
                                    <a href="lorry-report.php?lorry_id=<?= $l['id'] ?>" style="font-weight:700; color:var(--primary);">
                                        <?= clean($l['lorry_number']) ?>
                                    </a>
                                    <div style="font-size:11px; color:#64748b;"><?= clean($l['model_name']) ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($l['dynamic_driver_name'])): ?>
                                        <div style="font-weight:600; font-size:13px; color:#b45309;"><?= clean($l['dynamic_driver_name']) ?></div>
                                        <small style="color:#64748b; font-size:11px;">Trip: <?= clean($l['current_trip_route']) ?></small>
                                    <?php elseif (!empty($l['static_driver_name'])): ?>
                                        <div style="font-weight:600; font-size:13px;"><?= clean($l['static_driver_name']) ?></div>
                                        <small style="color:#64748b; font-size:11px;">Static Default</small>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-style:italic; font-size:12px;">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($l['dynamic_driver_name'])): ?>
                                        <span class="pill pill-dynamic">Dynamic</span>
                                    <?php elseif (!empty($l['static_driver_name'])): ?>
                                        <span class="pill pill-static">Static</span>
                                    <?php else: ?>
                                        <span class="pill pill-idle">Idle</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Prepare chart datasets for JavaScript
$cat_labels = [];
$cat_data = [];
$cat_colors = [];
foreach ($expense_categories as $c) {
    $cat_labels[] = $c['name'];
    $cat_data[] = (float)$c['total_cat_amount'];
    $cat_colors[] = $c['color'];
}

$extra_js = "
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Cash Flow Bar Chart
    const ctxCashflow = document.getElementById('cashflowChart');
    if (ctxCashflow) {
        new Chart(ctxCashflow, {
            type: 'bar',
            data: {
                labels: ['Total Freight Revenue', 'Total Operating Expenses', 'Net Profit'],
                datasets: [{
                    label: 'Amount in ₹',
                    data: [{$total_income}, {$total_expense}, {$net_profit}],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(239, 68, 68, 0.85)',
                        'rgba(79, 70, 229, 0.85)'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₹ ' + context.parsed.y.toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        ticks: {
                            callback: function(value) {
                                return '₹' + (value >= 1000 ? (value/1000) + 'k' : value);
                            }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Expense Category Doughnut Chart
    const ctxCat = document.getElementById('categoryChart');
    if (ctxCat) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: " . json_encode($cat_labels) . ",
                datasets: [{
                    data: " . json_encode($cat_data) . ",
                    backgroundColor: " . json_encode($cat_colors) . ",
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { family: 'Plus Jakarta Sans', size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ₹ ' + context.parsed.toLocaleString('en-IN');
                            }
                        }
                    }
                },
                cutout: '68%'
            }
        });
    }
});
</script>
";

require_once __DIR__ . '/includes/footer.php';
?>
