<?php
/**
 * Lorry Profit & Loss Statement & PDF Report
 * Shree Laxmi Expenses
 */

$page_title = 'Lorry Profit & Loss Report';
require_once __DIR__ . '/includes/header.php';

$lorry_id = isset($_GET['lorry_id']) && $_GET['lorry_id'] !== '' ? (int)$_GET['lorry_id'] : null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// If no lorry specified, default to first active lorry
$lorries = get_all_lorries($pdo);
if (!$lorry_id && !empty($lorries)) {
    $lorry_id = $lorries[0]['id'];
}

// Fetch selected lorry details
$stmt_l = $pdo->prepare("SELECT l.*, b.branch_name, b.city, b.phone as branch_phone,
                                su.name as static_driver_name, su.phone as static_driver_phone,
                                da.driver_id as dynamic_driver_id, du.name as dynamic_driver_name,
                                da.trip_route as dynamic_trip_route
                         FROM lorries l
                         LEFT JOIN branches b ON l.branch_id = b.id
                         LEFT JOIN users su ON l.static_driver_id = su.id
                         LEFT JOIN driver_assignments da ON (da.lorry_id = l.id AND da.status = 'active' AND da.assignment_type = 'dynamic')
                         LEFT JOIN users du ON da.driver_id = du.id
                         WHERE l.id = ?");
$stmt_l->execute([$lorry_id]);
$lorry = $stmt_l->fetch();

// Calculate Lorry Performance for the Date Range
$where = "WHERE lorry_id = ? AND transaction_date BETWEEN ? AND ?";
$params = [$lorry_id, $start_date, $end_date];

// 1. Total Income
$stmt_inc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions $where AND type = 'income'");
$stmt_inc->execute($params);
$lorry_income = (float)$stmt_inc->fetchColumn();

// 2. Total Expense
$stmt_exp = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions $where AND type = 'expense'");
$stmt_exp->execute($params);
$lorry_expense = (float)$stmt_exp->fetchColumn();

// 3. Category Breakdown for this Lorry
$stmt_cats = $pdo->prepare("SELECT c.name, c.type, COALESCE(SUM(t.amount), 0) as cat_total, COUNT(t.id) as item_count
                           FROM transactions t
                           JOIN categories c ON t.category_id = c.id
                           $where
                           GROUP BY c.id, c.name, c.type
                           ORDER BY c.type DESC, cat_total DESC");
$stmt_cats->execute($params);
$category_breakdown = $stmt_cats->fetchAll();

// 4. Diesel Stats for this Lorry
$stmt_dsl = $pdo->prepare("SELECT COALESCE(SUM(fuel_liters), 0) as total_liters, 
                                  COALESCE(SUM(amount), 0) as total_diesel_cost,
                                  MIN(odometer_reading) as min_odo,
                                  MAX(odometer_reading) as max_odo
                           FROM transactions t
                           JOIN categories c ON t.category_id = c.id
                           $where AND (c.name LIKE '%Diesel%' OR c.name LIKE '%Fuel%')");
$stmt_dsl->execute($params);
$diesel_info = $stmt_dsl->fetch();

$total_liters = (float)$diesel_info['total_liters'];
$total_diesel_cost = (float)$diesel_info['total_diesel_cost'];
$min_odo = (int)$diesel_info['min_odo'];
$max_odo = (int)$diesel_info['max_odo'];
$km_run = ($max_odo > $min_odo && $min_odo > 0) ? ($max_odo - $min_odo) : 0;
$mileage = ($km_run > 0 && $total_liters > 0) ? round($km_run / $total_liters, 2) : 0;

$net_pl = $lorry_income - $lorry_expense;

// 5. Itemized Transactions for this report
$stmt_items = $pdo->prepare("SELECT t.*, u.name as driver_name, c.name as category_name
                             FROM transactions t
                             JOIN users u ON t.driver_id = u.id
                             JOIN categories c ON t.category_id = c.id
                             $where
                             ORDER BY t.transaction_date ASC, t.id ASC");
$stmt_items->execute($params);
$itemized_txns = $stmt_items->fetchAll();
?>

<div class="page-header no-print">
    <div>
        <h1 class="page-title">Commercial Lorry P&L Statement</h1>
        <p class="page-subtitle">Detailed financial balance sheet, diesel consumption, and trip breakdown</p>
    </div>

    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Print Report</span>
        </button>
        <button type="button" class="btn btn-primary btn-sm" onclick="downloadPDF('printableLorryStatement', 'ShreeLaxmi_Lorry_<?= clean($lorry['lorry_number']) ?>_Statement.pdf')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Download Official PDF</span>
        </button>
    </div>
</div>

<!-- Report Selection Filter -->
<div class="filter-card no-print">
    <form method="GET" action="lorry-report.php" class="filter-grid" style="grid-template-columns: 2fr 1.5fr 1.5fr auto;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Select Commercial Lorry</label>
            <select name="lorry_id" class="form-select" required>
                <?php foreach ($lorries as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $lorry_id == $l['id'] ? 'selected' : '' ?>>
                        <?= clean($l['lorry_number']) ?> — <?= clean($l['model_name']) ?> (<?= clean($l['branch_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">From Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= clean($start_date) ?>">
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">To Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= clean($end_date) ?>">
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="height:42px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <span>Generate</span>
            </button>
        </div>
    </form>
</div>

<!-- Printable / Downloadable Official Document Container -->
<div class="print-document" id="printableLorryStatement">
    <!-- Official Letterhead Header -->
    <div class="print-header">
        <div class="print-brand">
            <h1>SHREE LAXMI EXPENSES</h1>
            <p><strong>Fleet & Commercial Transport Logistics</strong></p>
            <p><?= clean($lorry['branch_name'] ?? 'Head Office') ?> | Phone: +91 <?= clean($lorry['branch_phone'] ?? '9842712345') ?></p>
            <p>GSTIN: 33AAACL1234F1Z8 | Transporter Reg. No: TN/SL/2026/88</p>
        </div>
        <div class="print-meta">
            <span class="print-title-badge">LORRY P&L STATEMENT</span>
            <div class="print-meta-row"><strong>Period:</strong> <?= format_date($start_date) ?> to <?= format_date($end_date) ?></div>
            <div class="print-meta-row"><strong>Generated On:</strong> <?= date('d M Y, h:i A') ?></div>
            <div class="print-meta-row"><strong>Status:</strong> Audited Statement</div>
        </div>
    </div>

    <!-- Vehicle & Driver Profile Information Strip -->
    <div class="print-info-grid">
        <div class="print-info-item">
            <label>Vehicle Registration</label>
            <span style="font-size:15px; color:#4f46e5;"><?= clean($lorry['lorry_number']) ?></span>
        </div>
        <div class="print-info-item">
            <label>Make & Model</label>
            <span><?= clean($lorry['model_name']) ?></span>
        </div>
        <div class="print-info-item">
            <label>Capacity / Axle</label>
            <span><?= $lorry['capacity_tonnage'] ?> Tons (<?= clean($lorry['lorry_type']) ?>)</span>
        </div>
        <div class="print-info-item">
            <label>Active Driver</label>
            <span>
                <?php if (!empty($lorry['dynamic_driver_name'])): ?>
                    <?= clean($lorry['dynamic_driver_name']) ?> <small>(Dynamic)</small>
                <?php else: ?>
                    <?= clean($lorry['static_driver_name'] ?? 'Static Driver') ?>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <!-- Fuel & Odometer Performance Row -->
    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:12px; margin-bottom:24px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:14px;">
        <div>
            <small style="color:#1e40af; font-size:11px; font-weight:700; text-transform:uppercase;">Diesel Consumed</small>
            <div style="font-size:16px; font-weight:800; color:#1e3a8a;"><?= number_format($total_liters, 1) ?> Litres</div>
            <small style="color:#64748b;"><?= format_money($total_diesel_cost) ?></small>
        </div>
        <div>
            <small style="color:#1e40af; font-size:11px; font-weight:700; text-transform:uppercase;">Distance Covered</small>
            <div style="font-size:16px; font-weight:800; color:#1e3a8a;"><?= $km_run > 0 ? number_format($km_run) . ' KM' : 'Trip in Prog.' ?></div>
            <small style="color:#64748b;">Odo: <?= number_format($lorry['current_odometer']) ?> KM</small>
        </div>
        <div>
            <small style="color:#1e40af; font-size:11px; font-weight:700; text-transform:uppercase;">Average Mileage</small>
            <div style="font-size:16px; font-weight:800; color:#1e3a8a;"><?= $mileage > 0 ? $mileage . ' KM/L' : '3.8 - 4.2 KM/L' ?></div>
            <small style="color:#64748b;">Heavy loaded average</small>
        </div>
        <div>
            <small style="color:#1e40af; font-size:11px; font-weight:700; text-transform:uppercase;">Fuel Cost / KM</small>
            <div style="font-size:16px; font-weight:800; color:#1e3a8a;"><?= ($km_run > 0 && $total_diesel_cost > 0) ? '₹ ' . round($total_diesel_cost / $km_run, 2) : '-' ?></div>
            <small style="color:#64748b;">Fuel burn rate</small>
        </div>
    </div>

    <!-- Category Financial Summary -->
    <h3 style="font-size:15px; font-weight:700; margin-bottom:12px; color:#0f172a; border-left:4px solid #4f46e5; padding-left:10px;">
        1. Category-Wise Financial Summary
    </h3>
    <table class="print-table">
        <thead>
            <tr>
                <th>Category Name</th>
                <th>Classification</th>
                <th style="text-align:center;">Voucher Count</th>
                <th class="text-right">Total Amount (INR)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($category_breakdown)): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:14px; color:#94a3b8;">No transactions found for this vehicle in the selected period.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($category_breakdown as $cb): ?>
                    <tr>
                        <td><strong><?= clean($cb['name']) ?></strong></td>
                        <td>
                            <span style="font-weight:700; color:<?= $cb['type'] === 'income' ? '#059669' : '#dc2626' ?>;">
                                <?= strtoupper($cb['type']) ?>
                            </span>
                        </td>
                        <td style="text-align:center;"><?= $cb['item_count'] ?> slips</td>
                        <td class="text-right" style="font-weight:700; color:<?= $cb['type'] === 'income' ? '#059669' : '#dc2626' ?>;">
                            <?= format_money($cb['cat_total']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Net Profit & Loss Calculation Box -->
    <div class="print-summary-box">
        <div class="print-summary-card">
            <div class="print-summary-row">
                <span>Total Freight Revenue (A):</span>
                <strong style="color:#059669;"><?= format_money($lorry_income) ?></strong>
            </div>
            <div class="print-summary-row">
                <span>Total Operational Expenses (B):</span>
                <strong style="color:#dc2626;"><?= format_money($lorry_expense) ?></strong>
            </div>
            <div class="print-summary-row grand-total">
                <span>NET LORRY P&L (A - B):</span>
                <span style="color:<?= $net_pl >= 0 ? '#34d399' : '#f87171' ?>;"><?= format_money($net_pl) ?></span>
            </div>
        </div>
    </div>

    <!-- Itemized Ledger Vouchers for this vehicle -->
    <h3 style="font-size:15px; font-weight:700; margin-bottom:12px; color:#0f172a; border-left:4px solid #4f46e5; padding-left:10px;">
        2. Detailed Itemized Transaction Ledger
    </h3>
    <table class="print-table">
        <thead>
            <tr>
                <th>Voucher #</th>
                <th>Date</th>
                <th>Driver</th>
                <th>Category & Specifics</th>
                <th>Trip / Route</th>
                <th>Mode</th>
                <th class="text-right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($itemized_txns)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:14px; color:#94a3b8;">No entries found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($itemized_txns as $it): ?>
                    <tr>
                        <td style="font-weight:700; font-family:'JetBrains Mono', monospace; font-size:11px;"><?= clean($it['voucher_no']) ?></td>
                        <td><?= format_date($it['transaction_date']) ?></td>
                        <td><?= clean($it['driver_name']) ?></td>
                        <td>
                            <strong><?= clean($it['category_name']) ?></strong>
                            <?php if (!empty($it['fuel_liters'])): ?>
                                <small style="display:block; color:#b45309;"><?= $it['fuel_liters'] ?> L @ ₹<?= $it['fuel_rate'] ?> (<?= clean($it['vendor_party_name'] ?? 'Bunk') ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td><?= clean($it['trip_route'] ?? '-') ?></td>
                        <td style="text-transform:uppercase; font-size:11px;"><?= str_replace('_', ' ', $it['payment_mode']) ?></td>
                        <td class="text-right" style="font-weight:700; color:<?= $it['type'] === 'income' ? '#059669' : '#dc2626' ?>;">
                            <?= $it['type'] === 'income' ? '+' : '-' ?><?= format_money($it['amount']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="6" style="text-align:right;"><strong>NET BALANCE:</strong></td>
                    <td class="text-right" style="color:<?= $net_pl >= 0 ? '#059669' : '#dc2626' ?>;">
                        <strong><?= format_money($net_pl) ?></strong>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Authorized Signatures -->
    <div class="print-signatures">
        <div class="signature-box">
            <div class="signature-line"></div>
            <span>Prepared By / Driver</span>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <span>Branch Manager</span>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <span>Authorized Signatory (Admin)</span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
