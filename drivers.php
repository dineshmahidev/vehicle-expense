<?php
/**
 * Driver Directory & Management
 * Shree Laxmi Expenses
 */

$page_title = 'Driver Staff Directory';
require_once __DIR__ . '/includes/header.php';
require_manager(); // Admin or Manager

// Handle Add Driver
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();

    if ($_POST['action'] === 'create') {
        $name = clean($_POST['name']);
        $username = strtolower(clean($_POST['username']));
        $phone = clean($_POST['phone']);
        $password = trim($_POST['password'] ?? 'admin123');
        $branch_id = (int)$_POST['branch_id'];
        $license_number = clean($_POST['license_number']);
        $license_expiry = !empty($_POST['license_expiry']) ? clean($_POST['license_expiry']) : null;

        $pwd_hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (branch_id, name, username, phone, password, role, license_number, license_expiry, status) 
                                   VALUES (?, ?, ?, ?, ?, 'driver', ?, ?, 'active')");
            $stmt->execute([$branch_id, $name, $username, $phone, $pwd_hash, $license_number, $license_expiry]);
            set_flash('success', "Driver '{$name}' added successfully! Login username: {$username}");
            header("Location: drivers.php");
            exit;
        } catch (\PDOException $e) {
            set_flash('danger', "Error creating driver: " . $e->getMessage());
        }
    }
}

// Fetch all drivers with their assigned vehicle & entry count
$stmt = $pdo->query("SELECT u.*, b.branch_name,
                            COUNT(DISTINCT t.id) as total_entries_logged,
                            COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expenses_logged,
                            sl.lorry_number as static_lorry_number,
                            dl.lorry_number as dynamic_lorry_number,
                            da.trip_route as active_trip_route
                     FROM users u
                     LEFT JOIN branches b ON u.branch_id = b.id
                     LEFT JOIN transactions t ON t.driver_id = u.id
                     LEFT JOIN lorries sl ON sl.static_driver_id = u.id
                     LEFT JOIN driver_assignments da ON (da.driver_id = u.id AND da.status = 'active' AND da.assignment_type = 'dynamic')
                     LEFT JOIN lorries dl ON da.lorry_id = dl.id
                     WHERE u.role = 'driver'
                     GROUP BY u.id
                     ORDER BY u.id ASC");
$drivers = $stmt->fetchAll();
$branches = get_all_branches($pdo);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Transport Drivers Directory</h1>
        <p class="page-subtitle">Manage driver accounts, licenses, assigned commercial lorries, and expense submissions</p>
    </div>

    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addDriverModal')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        <span>+ Add New Driver</span>
    </button>
</div>

<!-- Driver Cards Grid -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:24px;">
    <?php foreach ($drivers as $d): ?>
        <div class="card" style="margin-bottom:0;">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="user-avatar" style="width:44px; height:44px; font-size:16px;">
                        <?= strtoupper(substr($d['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h3 style="font-size:15px; font-weight:700; color:#0f172a; margin:0;"><?= clean($d['name']) ?></h3>
                        <small style="color:#64748b;">User: @<?= clean($d['username']) ?></small>
                    </div>
                </div>
                <span class="pill <?= $d['status'] === 'active' ? 'pill-income' : 'pill-idle' ?>">
                    <?= ucfirst($d['status']) ?>
                </span>
            </div>

            <div class="card-body">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                        <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Phone</span>
                        <strong style="font-size:12.5px; color:#4f46e5;"><?= clean($d['phone']) ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                        <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">License No</span>
                        <span style="font-size:12px; font-family:'JetBrains Mono', monospace; font-weight:600;"><?= clean($d['license_number'] ?? 'Not Recorded') ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Home Branch</span>
                        <span style="font-size:12px; font-weight:600; color:#0f172a;"><?= clean($d['branch_name'] ?? 'HQ') ?></span>
                    </div>
                </div>

                <!-- Assignment Status -->
                <div style="margin-bottom:16px; font-size:13px;">
                    <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; display:block; margin-bottom:4px;">Current Lorry Assignment:</span>
                    <?php if (!empty($d['dynamic_lorry_number'])): ?>
                        <div style="font-weight:700; color:#b45309;">
                            🚚 <?= clean($d['dynamic_lorry_number']) ?>
                            <span class="pill pill-dynamic" style="font-size:10px; margin-left:4px;">Dynamic Trip</span>
                        </div>
                        <small style="color:#64748b;">Route: <?= clean($d['active_trip_route']) ?></small>
                    <?php elseif (!empty($d['static_lorry_number'])): ?>
                        <div style="font-weight:700; color:#0f172a;">
                            🚚 <?= clean($d['static_lorry_number']) ?>
                            <span class="pill pill-static" style="font-size:10px; margin-left:4px;">Static Lorry</span>
                        </div>
                    <?php else: ?>
                        <span style="color:#94a3b8; font-style:italic;">No active lorry assigned</span>
                    <?php endif; ?>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f1f5f9; padding-top:12px;">
                    <div>
                        <small style="font-size:11px; color:#64748b; text-transform:uppercase; font-weight:600;">Expenses Logged</small>
                        <div style="font-size:14px; font-weight:800; color:#ef4444;"><?= format_money($d['total_expenses_logged']) ?></div>
                    </div>
                    <a href="transactions.php?driver_id=<?= $d['id'] ?>" class="btn btn-outline btn-sm">
                        <span><?= $d['total_entries_logged'] ?> Entries</span>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Add New Driver -->
<div class="modal-overlay" id="addDriverModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Register New Driver</h3>
            <button type="button" class="modal-close" onclick="closeModal('addDriverModal')">&times;</button>
        </div>
        <form method="POST" action="drivers.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="create">

            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. K. Dhanapal">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username (For Login) *</label>
                        <input type="text" name="username" class="form-control" required placeholder="e.g. dhanapal">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Phone / Mobile No *</label>
                        <input type="text" name="phone" class="form-control" required placeholder="e.g. 9842711105">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Login Password</label>
                        <input type="password" name="password" class="form-control" value="admin123">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Assigned Branch *</label>
                        <select name="branch_id" class="form-select" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= clean($b['branch_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Driving License No</label>
                        <input type="text" name="license_number" class="form-control" placeholder="e.g. TN-28-2020-004321">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">License Expiry Date</label>
                    <input type="date" name="license_expiry" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addDriverModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Driver Account</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
