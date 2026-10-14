<?php
/**
 * Branch Management
 * Shree Laxmi Expenses
 */

$page_title = 'Branch Management';
require_once __DIR__ . '/includes/header.php';
require_manager(); // Admin or Branch Manager

// Handle Add Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();

    if ($_POST['action'] === 'create') {
        $branch_code = clean($_POST['branch_code']);
        $branch_name = clean($_POST['branch_name']);
        $city = clean($_POST['city']);
        $state = clean($_POST['state'] ?? 'Tamil Nadu');
        $contact_person = clean($_POST['contact_person']);
        $phone = clean($_POST['phone']);
        $email = clean($_POST['email']);
        $address = clean($_POST['address']);

        try {
            $stmt = $pdo->prepare("INSERT INTO branches (branch_code, branch_name, city, state, contact_person, phone, email, address) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$branch_code, $branch_name, $city, $state, $contact_person, $phone, $email, $address]);
            set_flash('success', "Branch '{$branch_name}' created successfully!");
            header("Location: branches.php");
            exit;
        } catch (\PDOException $e) {
            set_flash('danger', "Error creating branch: " . $e->getMessage());
        }
    } elseif ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $branch_name = clean($_POST['branch_name']);
        $city = clean($_POST['city']);
        $contact_person = clean($_POST['contact_person']);
        $phone = clean($_POST['phone']);
        $email = clean($_POST['email']);
        $address = clean($_POST['address']);
        $status = clean($_POST['status'] ?? 'active');

        try {
            $stmt = $pdo->prepare("UPDATE branches SET branch_name = ?, city = ?, contact_person = ?, phone = ?, email = ?, address = ?, status = ? WHERE id = ?");
            $stmt->execute([$branch_name, $city, $contact_person, $phone, $email, $address, $status, $id]);
            set_flash('success', "Branch updated successfully!");
            header("Location: branches.php");
            exit;
        } catch (\PDOException $e) {
            set_flash('danger', "Error updating branch: " . $e->getMessage());
        }
    }
}

// Fetch all branches with performance aggregates
$stmt = $pdo->query("SELECT b.*, 
                            COUNT(DISTINCT l.id) as total_lorries,
                            COUNT(DISTINCT u.id) as total_staff,
                            COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
                            COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expense
                     FROM branches b
                     LEFT JOIN lorries l ON l.branch_id = b.id
                     LEFT JOIN users u ON u.branch_id = b.id
                     LEFT JOIN transactions t ON t.branch_id = b.id
                     GROUP BY b.id
                     ORDER BY b.id ASC");
$branches = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Branch Hubs</h1>
        <p class="page-subtitle">Configure regional transport offices, hubs, and contact details</p>
    </div>
    
    <?php if (is_admin()): ?>
        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addBranchModal')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Create New Branch</span>
        </button>
    <?php endif; ?>
</div>

<!-- Branches Grid -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:24px; margin-bottom:30px;">
    <?php foreach ($branches as $b): ?>
        <?php $branch_profit = $b['total_income'] - $b['total_expense']; ?>
        <div class="card" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:space-between;">
            <div class="card-header">
                <div>
                    <span class="pill pill-static" style="font-size:11px; margin-bottom:4px;"><?= clean($b['branch_code']) ?></span>
                    <h3 style="font-size:16px; font-weight:700; color:#0f172a; margin-top:4px;"><?= clean($b['branch_name']) ?></h3>
                </div>
                <span class="pill <?= $b['status'] === 'active' ? 'pill-income' : 'pill-idle' ?>">
                    <?= ucfirst($b['status']) ?>
                </span>
            </div>

            <div class="card-body">
                <p style="font-size:13px; color:#475569; margin-bottom:16px; min-height:38px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:text-bottom; margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <?= clean($b['address'] ?? $b['city']) ?>
                </p>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; background:#f8fafc; padding:12px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:16px;">
                    <div>
                        <small style="color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase;">Contact Person</small>
                        <div style="font-weight:700; font-size:13px;"><?= clean($b['contact_person'] ?? 'Manager') ?></div>
                        <small style="color:#4f46e5;"><?= clean($b['phone']) ?></small>
                    </div>
                    <div>
                        <small style="color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase;">Fleet / Drivers</small>
                        <div style="font-weight:700; font-size:13px; color:#0f172a;"><?= $b['total_lorries'] ?> Lorries</div>
                        <small style="color:#64748b;"><?= $b['total_staff'] ?> Staff/Drivers</small>
                    </div>
                </div>

                <div style="border-top:1px solid #f1f5f9; padding-top:12px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <small style="color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase;">Branch P&L Balance</small>
                        <div style="font-size:15px; font-weight:800; color:<?= $branch_profit >= 0 ? '#10b981' : '#ef4444' ?>;">
                            <?= format_money($branch_profit) ?>
                        </div>
                    </div>
                    <div>
                        <a href="transactions.php?branch_id=<?= $b['id'] ?>" class="btn btn-outline btn-sm">Ledger</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Add New Branch -->
<div class="modal-overlay" id="addBranchModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Create New Branch Hub</h3>
            <button type="button" class="modal-close" onclick="closeModal('addBranchModal')">&times;</button>
        </div>
        <form method="POST" action="branches.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="create">

            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Branch Code *</label>
                        <input type="text" name="branch_code" class="form-control" required placeholder="e.g. BR-MD04">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Name *</label>
                        <input type="text" name="branch_name" class="form-control" required placeholder="e.g. Madurai Logistics Hub">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">City *</label>
                        <input type="text" name="city" class="form-control" required placeholder="e.g. Madurai">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control" value="Tamil Nadu">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" placeholder="e.g. R. Karthik">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="text" name="phone" class="form-control" required placeholder="e.g. 9842712345">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="branch@shreelaxmi.com">
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Full Address / Location</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Street, Highway complex, Pincode..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addBranchModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Branch</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
