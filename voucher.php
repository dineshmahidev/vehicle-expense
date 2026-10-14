<?php
/**
 * Single Transaction Payment Voucher / Receipt Slip
 * Shree Laxmi Expenses
 */

$page_title = 'Payment & Income Voucher';
require_once __DIR__ . '/includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT t.*, l.lorry_number, l.model_name, l.lorry_type,
                              u.name as driver_name, u.phone as driver_phone,
                              c.name as category_name, c.color as category_color,
                              b.branch_name, b.city, b.phone as branch_phone, b.address as branch_address,
                              cb.name as created_by_name
                       FROM transactions t
                       JOIN lorries l ON t.lorry_id = l.id
                       JOIN users u ON t.driver_id = u.id
                       JOIN categories c ON t.category_id = c.id
                       JOIN branches b ON t.branch_id = b.id
                       LEFT JOIN users cb ON t.created_by = cb.id
                       WHERE t.id = ?");
$stmt->execute([$id]);
$txn = $stmt->fetch();

if (!$txn) {
    set_flash('danger', 'Transaction voucher not found.');
    header("Location: transactions.php");
    exit;
}

// Simple Indian Number to Words converter
function number_to_words_inr($number) {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $words = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    ];
    $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];
    
    $str = [];
    $i = 0;
    while ($no > 0) {
        $divider = ($i == 2) ? 10 : 100;
        $number_chunk = $no % $divider;
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number_chunk) {
            $plural = (($counter = count($str)) && $number_chunk > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number_chunk < 21) ? $words[$number_chunk] . ' ' . ($digits[$counter] ?? '') . $plural . ' ' . $hundred
                : $words[floor($number_chunk / 10) * 10] . ' ' . $words[$number_chunk % 10] . ' ' . ($digits[$counter] ?? '') . $plural . ' ' . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point) ? "and " . ($words[$point / 10] ?? '') . " " . ($words[$point = $point % 10] ?? '') . " Paise" : '';
    return "Rupees " . trim($result) . " Only " . $points;
}
?>

<div class="page-header no-print">
    <div>
        <h1 class="page-title">Transaction Slip #<?= clean($txn['voucher_no']) ?></h1>
        <p class="page-subtitle">Official payment acknowledgement and ledger voucher slip</p>
    </div>

    <div style="display:flex; gap:10px;">
        <a href="transactions.php" class="btn btn-outline btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span>Ledger</span>
        </a>
        <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Print Voucher</span>
        </button>
        <button type="button" class="btn btn-primary btn-sm" onclick="downloadPDF('voucherSlipDoc', 'Voucher_<?= clean($txn['voucher_no']) ?>.pdf')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Download PDF</span>
        </button>
    </div>
</div>

<!-- Printable Voucher Slip -->
<div class="print-document" id="voucherSlipDoc" style="max-width:760px; border:2px solid #0f172a;">
    <!-- Slip Header -->
    <div class="print-header" style="margin-bottom:18px; padding-bottom:18px;">
        <div class="print-brand">
            <h1 style="font-size:22px;">SHREE LAXMI EXPENSES</h1>
            <p><strong>Fleet & Commercial Transport Logistics</strong></p>
            <p><?= clean($txn['branch_name']) ?> | Phone: <?= clean($txn['branch_phone']) ?></p>
        </div>
        <div class="print-meta">
            <span class="print-title-badge" style="background:<?= $txn['type'] === 'income' ? '#059669' : '#dc2626' ?>;">
                <?= $txn['type'] === 'income' ? 'FREIGHT RECEIPT VOUCHER' : 'EXPENSE PAYMENT VOUCHER' ?>
            </span>
            <div class="print-meta-row" style="font-size:13px;"><strong>Voucher No:</strong> <?= clean($txn['voucher_no']) ?></div>
            <div class="print-meta-row"><strong>Date:</strong> <?= format_date($txn['transaction_date']) ?></div>
        </div>
    </div>

    <!-- Particulars Info Grid -->
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin-bottom:20px;">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:12px;">
            <div>
                <small style="color:#64748b; font-size:11px; text-transform:uppercase; font-weight:700;">Commercial Lorry</small>
                <div style="font-size:16px; font-weight:800; color:#4f46e5;"><?= clean($txn['lorry_number']) ?></div>
                <small style="color:#64748b;"><?= clean($txn['model_name']) ?> (<?= clean($txn['lorry_type']) ?>)</small>
            </div>
            <div>
                <small style="color:#64748b; font-size:11px; text-transform:uppercase; font-weight:700;">Driver / Handler</small>
                <div style="font-size:15px; font-weight:700; color:#0f172a;"><?= clean($txn['driver_name']) ?></div>
                <small style="color:#64748b;">Phone: <?= clean($txn['driver_phone']) ?></small>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; border-top:1px dashed #cbd5e1; padding-top:10px;">
            <div>
                <small style="color:#64748b; font-size:11px; text-transform:uppercase; font-weight:700;">Payment Mode</small>
                <div style="font-weight:700; font-size:13px; text-transform:uppercase;"><?= str_replace('_', ' ', $txn['payment_mode']) ?></div>
            </div>
            <div>
                <small style="color:#64748b; font-size:11px; text-transform:uppercase; font-weight:700;">Trip Route</small>
                <div style="font-weight:600; font-size:13px;"><?= clean($txn['trip_route'] ?: '-') ?></div>
            </div>
            <div>
                <small style="color:#64748b; font-size:11px; text-transform:uppercase; font-weight:700;">Odometer Reading</small>
                <div style="font-weight:600; font-size:13px;"><?= $txn['odometer_reading'] ? number_format($txn['odometer_reading']) . ' KM' : '-' ?></div>
            </div>
        </div>
    </div>

    <!-- Voucher Details Table -->
    <table class="print-table" style="margin-bottom:16px;">
        <thead>
            <tr>
                <th>Description & Particulars</th>
                <th>Reference / Party</th>
                <th class="text-right">Amount (INR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong style="font-size:14px;"><?= clean($txn['category_name']) ?></strong>
                    <?php if (!empty($txn['fuel_liters'])): ?>
                        <div style="margin-top:4px; font-size:12px; color:#b45309; font-weight:600;">
                            ⛽ Diesel Quantity: <?= $txn['fuel_liters'] ?> Liters @ ₹<?= $txn['fuel_rate'] ?> / Liter
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($txn['description'])): ?>
                        <div style="margin-top:4px; font-size:12px; color:#475569;">
                            Note: <?= clean($txn['description']) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($txn['bill_invoice_no'])): ?>
                        <div>Slip No: <strong><?= clean($txn['bill_invoice_no']) ?></strong></div>
                    <?php endif; ?>
                    <?php if (!empty($txn['vendor_party_name'])): ?>
                        <small style="color:#64748b;">Vendor: <?= clean($txn['vendor_party_name']) ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-right" style="font-size:18px; font-weight:800; color:<?= $txn['type'] === 'income' ? '#059669' : '#dc2626' ?>;">
                    <?= format_money($txn['amount']) ?>
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="2">
                    <small style="text-transform:uppercase; font-size:11px; color:#64748b;">Amount in Words:</small><br>
                    <strong><?= number_to_words_inr($txn['amount']) ?></strong>
                </td>
                <td class="text-right" style="font-size:18px; font-weight:800; color:<?= $txn['type'] === 'income' ? '#059669' : '#dc2626' ?>;">
                    <?= format_money($txn['amount']) ?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Receipt Photo Attachment Preview if present -->
    <?php if (!empty($txn['receipt_image'])): ?>
        <div style="border:1px dashed #cbd5e1; border-radius:8px; padding:12px; margin-bottom:20px; text-align:center;">
            <small style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:#64748b; margin-bottom:8px;">Attached Bill Slip Copy</small>
            <img src="<?= clean($txn['receipt_image']) ?>" alt="Bill Slip" style="max-height:200px; max-width:100%; border-radius:4px; border:1px solid #e2e8f0;">
        </div>
    <?php endif; ?>

    <!-- Signature Boxes -->
    <div class="print-signatures" style="margin-top:45px;">
        <div class="signature-box">
            <div class="signature-line"></div>
            <span>Driver / Receiver Sign</span>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <span>Accountant / Verified By</span>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <span>Authorized Signatory</span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
