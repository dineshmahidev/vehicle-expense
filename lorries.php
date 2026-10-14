<?php
/**
 * Lorry Fleet Management
 * Shree Laxmi Expenses
 */

$page_title = 'Lorry Fleet (Vehicles)';
require_once __DIR__ . '/includes/header.php';

// Handle Add Lorry or Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();

    if ($_POST['action'] === 'create') {
        require_manager();
        $lorry_number = strtoupper(clean($_POST['lorry_number']));
        $branch_id = (int)$_POST['branch_id'];
        $model_name = clean($_POST['model_name']);
        $lorry_type = clean($_POST['lorry_type']);
        $capacity_tonnage = (float)$_POST['capacity_tonnage'];
        $current_odometer = (int)$_POST['current_odometer'];
        $static_driver_id = !empty($_POST['static_driver_id']) ? (int)$_POST['static_driver_id'] : null;
        $status = clean($_POST['status'] ?? 'active');

        try {
            $stmt = $pdo->prepare("INSERT INTO lorries (lorry_number, branch_id, model_name, lorry_type, capacity_tonnage, current_odometer, static_driver_id, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$lorry_number, $branch_id, $model_name, $lorry_type, $capacity_tonnage, $current_odometer, $static_driver_id, $status]);
            
            // If static driver assigned, create static assignment record
            if ($static_driver_id) {
                $lorry_id = $pdo->lastInsertId();
                $stmt_as = $pdo->prepare("INSERT INTO driver_assignments (lorry_id, driver_id, branch_id, assignment_type, start_date, start_odometer, status, notes)
                                         VALUES (?, ?, ?, 'static', CURDATE(), ?, 'active', 'Initial static driver assignment')");
                $stmt_as->execute([$lorry_id, $static_driver_id, $branch_id, $current_odometer]);
            }

            set_flash('success', "Lorry '{$lorry_number}' registered successfully!");
            header("Location: lorries.php");
            exit;
        } catch (\PDOException $e) {
            set_flash('danger', "Error adding lorry: " . $e->getMessage());
        }
    } elseif ($_POST['action'] === 'update_status') {
        require_manager();
        $id = (int)$_POST['id'];
        $status = clean($_POST['status']);
        $stmt = $pdo->prepare("UPDATE lorries SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        set_flash('success', "Lorry status updated to " . ucfirst($status));
        header("Location: lorries.php");
        exit;
    }
}

// Fetch all lorries with branch and dynamic/static drivers
$lorries = get_all_lorries($pdo);
$branches = get_all_branches($pdo);
$drivers = get_all_drivers($pdo);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Fleet Vehicles (Lorries)</h1>
        <p class="page-subtitle">Manage heavy commercial vehicles, capacities, odometer readings, and assigned drivers</p>
    </div>
    
    <?php if (is_admin() || is_manager()): ?>
        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addLorryModal')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Register New Lorry</span>
        </button>
    <?php endif; ?>
</div>

<!-- Lorry Fleet Cards Grid -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap:24px; margin-bottom:30px;">
    <?php foreach ($lorries as $l): ?>
        <?php
            // Calculate financial summary for this lorry
            $stmt_calc = $pdo->prepare("SELECT 
                                            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                                            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense
                                        FROM transactions WHERE lorry_id = ?");
            $stmt_calc->execute([$l['id']]);
            $fin = $stmt_calc->fetch();
            $profit = $fin['income'] - $fin['expense'];
        ?>
        <div class="card" style="margin-bottom:0;">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="background:#f1f5f9; padding:8px; border-radius:8px; border:1px solid #cbd5e1;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    </div>
                    <div>
                        <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0; letter-spacing:-0.2px;">
                            <?= clean($l['lorry_number']) ?>
                        </h3>
                        <small style="color:#64748b; font-size:12px;"><?= clean($l['model_name']) ?></small>
                    </div>
                </div>

                <span class="pill <?= $l['status'] === 'active' ? 'pill-income' : ($l['status'] === 'maintenance' ? 'pill-maintenance' : 'pill-idle') ?>">
                    <?= ucfirst($l['status']) ?>
                </span>
            </div>

            <div class="card-body">
                <!-- Vehicle Details Grid -->
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:8px; margin-bottom:16px; background:#f8fafc; padding:10px 14px; border-radius:8px; border:1px solid #e2e8f0;">
                    <div>
                        <small style="display:block; font-size:10.5px; color:#64748b; text-transform:uppercase;">Type</small>
                        <strong style="font-size:12px; color:#0f172a;"><?= clean($l['lorry_type']) ?></strong>
                    </div>
                    <div>
                        <small style="display:block; font-size:10.5px; color:#64748b; text-transform:uppercase;">Capacity</small>
                        <strong style="font-size:12px; color:#0f172a;"><?= $l['capacity_tonnage'] ?> Tons</strong>
                    </div>
                    <div>
                        <small style="display:block; font-size:10.5px; color:#64748b; text-transform:uppercase;">Odometer</small>
                        <strong style="font-size:12px; color:#0f172a;"><?= number_format($l['current_odometer']) ?> KM</strong>
                    </div>
                </div>

                <!-- Driver Assignment Box -->
                <div style="border:1px dashed #cbd5e1; border-radius:8px; padding:12px; margin-bottom:16px; background:#ffffff;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Assigned Driver</span>
                        <?php if (!empty($l['dynamic_driver_name'])): ?>
                            <span class="pill pill-dynamic" style="font-size:10px;">Dynamic Trip</span>
                        <?php elseif (!empty($l['static_driver_name'])): ?>
                            <span class="pill pill-static" style="font-size:10px;">Static Assigned</span>
                        <?php else: ?>
                            <span class="pill pill-idle" style="font-size:10px;">Unassigned</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($l['dynamic_driver_name'])): ?>
                        <div style="font-size:14px; font-weight:700; color:#b45309;">
                            🚚 <?= clean($l['dynamic_driver_name']) ?>
                        </div>
                        <div style="font-size:11.5px; color:#475569; margin-top:2px;">
                            Route: <strong><?= clean($l['current_trip_route']) ?></strong>
                        </div>
                    <?php elseif (!empty($l['static_driver_name'])): ?>
                        <div style="font-size:14px; font-weight:700; color:#0f172a;">
                            👤 <?= clean($l['static_driver_name']) ?>
                        </div>
                        <small style="color:#64748b; font-size:11px;"><?= clean($l['static_driver_phone']) ?></small>
                    <?php else: ?>
                        <div style="color:#94a3b8; font-size:13px; font-style:italic;">No driver currently assigned</div>
                    <?php endif; ?>
                </div>

                <!-- Financial Snapshot -->
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-top:1px solid #f1f5f9;">
                    <div>
                        <small style="color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase;">Net Lorry Profit</small>
                        <div style="font-size:16px; font-weight:800; color:<?= $profit >= 0 ? '#10b981' : '#ef4444' ?>;">
                            <?= format_money($profit) ?>
                        </div>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <a href="lorry-report.php?lorry_id=<?= $l['id'] ?>" class="btn btn-outline btn-sm" title="View Full Lorry P&L Statement">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            <span>P&L Report</span>
                        </a>
                        <a href="add-transaction.php?lorry_id=<?= $l['id'] ?>" class="btn btn-primary btn-sm" title="Add Expense for this lorry">
                            + Expense
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Add New Lorry -->
<div class="modal-overlay" id="addLorryModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Register New Commercial Lorry</h3>
            <button type="button" class="modal-close" onclick="closeModal('addLorryModal')">&times;</button>
        </div>
        <form method="POST" action="lorries.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="create">

            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Lorry Registration Number *</label>
                        <input type="text" name="lorry_number" class="form-control" required placeholder="e.g. TN-28-AL-8899" style="text-transform:uppercase; font-weight:700;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assigned Branch Hub *</label>
                        <select name="branch_id" class="form-select" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= clean($b['branch_name']) ?> (<?= clean($b['city']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Vehicle Model & Make *</label>
                        <input type="text" name="model_name" class="form-control" required placeholder="e.g. BharatBenz 2823R">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Body / Axle Type *</label>
                        <select name="lorry_type" class="form-select" required>
                            <option value="10 Wheeler Multi-Axle">10 Wheeler Multi-Axle</option>
                            <option value="12 Wheeler Open Truck">12 Wheeler Open Truck</option>
                            <option value="14 Wheeler Heavy Haulage" selected>14 Wheeler Heavy Haulage</option>
                            <option value="16 Wheeler Container">16 Wheeler Container</option>
                            <option value="Trailer / Prime Mover">Trailer / Prime Mover</option>
                            <option value="Tipper / Dumper">Tipper / Dumper</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Tonnage Capacity (Tons)</label>
                        <input type="number" step="0.1" name="capacity_tonnage" class="form-control" value="35.0" placeholder="e.g. 35.0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Odometer (KM)</label>
                        <input type="number" name="current_odometer" class="form-control" value="0" placeholder="e.g. 120500">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Default Static Driver (Optional)</label>
                        <select name="static_driver_id" class="form-select">
                            <option value="">-- No Static Driver Assigned --</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= clean($d['name']) ?> (<?= clean($d['phone']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Operational Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active & Running</option>
                            <option value="maintenance">Under Maintenance / Workshop</option>
                            <option value="idle">Idle / Yard Parking</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addLorryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Register Vehicle</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
