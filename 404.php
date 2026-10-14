<?php
/**
 * Modern 404 Error Page
 * Shree Laxmi Expenses
 */

// Set proper HTTP 404 response code
http_response_code(404);

$app_name = 'Shree Laxmi Expenses';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | <?= $app_name ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/vehicle/assets/css/style.css">
</head>
<body class="login-body">

<div class="login-card" style="max-width: 500px; text-align: center;">
    <!-- Icon / Badge -->
    <div style="width: 80px; height: 80px; background: rgba(239, 68, 68, 0.1); border-radius: 24px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.2);">
        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
    </div>

    <!-- 404 Headline -->
    <div style="font-family: 'JetBrains Mono', monospace; font-size: 14px; font-weight: 700; color: #ef4444; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px;">
        Error 404 • Highway Route Lost
    </div>

    <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 10px; letter-spacing: -0.5px;">
        Page or Slip Not Found
    </h1>

    <p style="font-size: 13.5px; color: #64748b; line-height: 1.6; margin-bottom: 28px;">
        The lorry route, transaction voucher, or report you are looking for does not exist or may have been re-routed.
    </p>

    <!-- Navigation Buttons -->
    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
        <a href="/vehicle/index.php" class="btn btn-primary" style="padding: 11px 22px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Fleet Dashboard</span>
        </a>

        <a href="/vehicle/transactions.php" class="btn btn-outline" style="padding: 11px 20px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            <span>Ledger</span>
        </a>
    </div>

    <!-- Quick Footer Brand -->
    <div style="margin-top: 30px; padding-top: 18px; border-top: 1px dashed #e2e8f0; font-size: 12px; color: #94a3b8;">
        <strong><?= $app_name ?></strong> &bull; Commercial Fleet & Expense Management
    </div>
</div>

</body>
</html>
