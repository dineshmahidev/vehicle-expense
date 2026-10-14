<?php
/**
 * Quick Add Income & Expense Entry Form
 * Shree Laxmi Expenses
 */

$page_title = 'Record Income or Expense';
require_once __DIR__ . '/includes/header.php';

// Prepare uploads directory
$upload_dir = __DIR__ . '/uploads/receipts';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Auto-detect assigned lorry for driver
$default_lorry_id = isset($_GET['lorry_id']) ? (int)$_GET['lorry_id'] : null;
$default_driver_id = is_driver() ? (int)$user['id'] : null;

if (is_driver() && !$default_lorry_id) {
    // Check dynamic assignment first
    $stmt_d = $pdo->prepare("SELECT lorry_id FROM driver_assignments WHERE driver_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt_d->execute([$user['id']]);
    $default_lorry_id = $stmt_d->fetchColumn();

    // If not found, check static lorry
    if (!$default_lorry_id) {
        $stmt_s = $pdo->prepare("SELECT id FROM lorries WHERE static_driver_id = ? LIMIT 1");
        $stmt_s->execute([$user['id']]);
        $default_lorry_id = $stmt_s->fetchColumn();
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $type = clean($_POST['type'] ?? 'expense');
    $transaction_date = clean($_POST['transaction_date'] ?? date('Y-m-d'));
    $lorry_id = (int)$_POST['lorry_id'];
    $driver_id = is_driver() ? (int)$user['id'] : (int)$_POST['driver_id'];
    $branch_id = (int)$_POST['branch_id'];
    $category_id = (int)$_POST['category_id'];
    $amount = (float)$_POST['amount'];
    $payment_mode = clean($_POST['payment_mode'] ?? 'cash');

    $fuel_liters = !empty($_POST['fuel_liters']) ? (float)$_POST['fuel_liters'] : null;
    $fuel_rate = !empty($_POST['fuel_rate']) ? (float)$_POST['fuel_rate'] : null;
    $odometer_reading = !empty($_POST['odometer_reading']) ? (int)$_POST['odometer_reading'] : null;
    $trip_route = clean($_POST['trip_route'] ?? '');
    $bill_invoice_no = clean($_POST['bill_invoice_no'] ?? '');
    $vendor_party_name = clean($_POST['vendor_party_name'] ?? '');
    $description = clean($_POST['description'] ?? '');

    // Handle receipt upload
    $receipt_image = null;
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['receipt_file']['tmp_name'];
        $file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9._-]/", "", basename($_FILES['receipt_file']['name']));
        $target_file = $upload_dir . '/' . $file_name;
        
        if (move_uploaded_file($file_tmp, $target_file)) {
            $receipt_image = 'uploads/receipts/' . $file_name;
        }
    }

    if ($amount <= 0) {
        set_flash('danger', 'Please enter a valid amount greater than zero.');
    } else {
        try {
            $voucher_no = generate_voucher_no($pdo);

            $stmt = $pdo->prepare("INSERT INTO transactions 
                (voucher_no, transaction_date, type, lorry_id, driver_id, branch_id, category_id, amount, payment_mode, fuel_liters, fuel_rate, odometer_reading, trip_route, bill_invoice_no, vendor_party_name, receipt_image, description, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $voucher_no, $transaction_date, $type, $lorry_id, $driver_id, $branch_id, $category_id,
                $amount, $payment_mode, $fuel_liters, $fuel_rate, $odometer_reading,
                $trip_route, $bill_invoice_no, $vendor_party_name, $receipt_image, $description, $user['id']
            ]);

            $new_id = $pdo->lastInsertId();

            // If odometer is higher, update lorry current odometer
            if ($odometer_reading) {
                $pdo->prepare("UPDATE lorries SET current_odometer = GREATEST(current_odometer, ?) WHERE id = ?")
                    ->execute([$odometer_reading, $lorry_id]);
            }

            set_flash('success', "Transaction recorded successfully! Voucher No: {$voucher_no}");
            header("Location: voucher.php?id=" . $new_id);
            exit;
        } catch (\PDOException $e) {
            set_flash('danger', "Database error: " . $e->getMessage());
        }
    }
}

$branches = get_all_branches($pdo);
$lorries = get_all_lorries($pdo);
$drivers = get_all_drivers($pdo);
$categories = get_categories($pdo);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add Transport Entry</h1>
        <p class="page-subtitle">Record freight income, diesel filling, toll charges, hamali, and driver allowances</p>
    </div>

    <a href="transactions.php" class="btn btn-outline btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        <span>Back to Ledger</span>
    </a>
</div>

<div style="max-width:820px; margin:0 auto;">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="add-transaction.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="type" id="typeInput" value="expense">

                <!-- Type Switcher -->
                <div class="type-switch-container">
                    <button type="button" class="type-switch-btn active switch-expense" id="switchExpense">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Expense Outflow (Diesel, Toll, Hamali, Repairs)</span>
                    </button>
                    <button type="button" class="type-switch-btn" id="switchIncome">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Income (Freight Load, Advance)</span>
                    </button>
                </div>

                <!-- Lorry and Driver Selection -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Select Lorry *</label>
                        <select name="lorry_id" id="lorry_id" class="form-select" required>
                            <option value="">-- Choose Commercial Lorry --</option>
                            <?php foreach ($lorries as $l): ?>
                                <option value="<?= $l['id'] ?>" <?= $default_lorry_id == $l['id'] ? 'selected' : '' ?>>
                                    <?= clean($l['lorry_number']) ?> (<?= clean($l['model_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Driver *</label>
                        <?php if (is_driver()): ?>
                            <input type="hidden" name="driver_id" value="<?= $user['id'] ?>">
                            <input type="text" class="form-control" value="<?= clean($user['name']) ?> (You)" readonly style="background:#f1f5f9;">
                        <?php else: ?>
                            <select name="driver_id" id="driver_id" class="form-select" required>
                                <option value="">-- Choose Driver --</option>
                                <?php foreach ($drivers as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= $default_driver_id == $d['id'] ? 'selected' : '' ?>>
                                        <?= clean($d['name']) ?> (<?= clean($d['phone']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Date, Branch & Category -->
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Entry Date *</label>
                        <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Branch Hub *</label>
                        <select name="branch_id" id="branch_id" class="form-select" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= clean($b['branch_name']) ?> (<?= clean($b['city']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">-- Choose Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?>">
                                    <?= clean($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Fuel Specific Calculation Section (Auto-toggles when Diesel is selected) -->
                <div id="fuelFieldsSection" style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:18px; margin-bottom:20px; display:none;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2"><path d="M3 2v20"></path><path d="M21 12V6a2 2 0 0 0-2-2H7v16h12a2 2 0 0 0 2-2v-3"></path></svg>
                        <strong style="color:#b45309; font-size:14px;">Diesel Smart Calculator (Liters × Rate = Total)</strong>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Fuel Quantity (Liters)</label>
                            <input type="number" step="0.01" name="fuel_liters" id="fuel_liters" class="form-control" placeholder="e.g. 240.5">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Rate per Liter (₹)</label>
                            <input type="number" step="0.01" name="fuel_rate" id="fuel_rate" class="form-control" value="93.50" placeholder="e.g. 93.50">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Fuel Station / Vendor</label>
                            <input type="text" name="vendor_party_name" class="form-control" placeholder="e.g. IOCL Hubli Bypass">
                        </div>
                    </div>
                </div>

                <!-- Amount and Payment Mode -->
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Total Amount (₹) *</label>
                        <input type="number" step="0.01" name="amount" id="amount" class="form-control" required placeholder="0.00" style="font-size:17px; font-weight:800; color:#0f172a;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Mode *</label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="cash">Cash in Hand</option>
                            <option value="fuel_card">Fuel Card (BPCL/IOCL/HPCL)</option>
                            <option value="upi">UPI / GPay / PhonePe</option>
                            <option value="bank_transfer">Net Banking / NEFT</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Odometer Reading (KM)</label>
                        <input type="number" name="odometer_reading" class="form-control" placeholder="e.g. 145600">
                    </div>
                </div>

                <!-- Trip Route & Bill Details -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Trip Route / Highway Location</label>
                        <input type="text" name="trip_route" class="form-control" placeholder="e.g. Salem to Mumbai (NH 48)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bill / Fastag / Invoice Slip No</label>
                        <input type="text" name="bill_invoice_no" class="form-control" placeholder="e.g. BPCL-99824 or TOLL-120">
                    </div>
                </div>

                <!-- Receipt Upload & Notes -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Attach Bill / Slip Receipt Photo</label>
                        <input type="file" name="receipt_file" class="form-control" accept="image/*,.pdf">
                        <small style="color:#64748b; font-size:11px;">Drivers can take a phone photo of petrol pump slip or toll bill</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes / Remarks</label>
                        <input type="text" name="description" class="form-control" placeholder="Additional details or consignment info">
                    </div>
                </div>

                <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:12px;">
                    <a href="transactions.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="padding:12px 28px; font-size:15px;">
                        <span>Save & Generate Voucher</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
