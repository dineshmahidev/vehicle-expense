<?php
/**
 * Header Template
 * Shree Laxmi Expenses
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Enforce login for all protected pages (except login.php)
require_login();

$user = current_user();
$flash = get_flash();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? clean($page_title) . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Application Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/print.css">
    
    <!-- Chart.js & html2pdf.js for rich visualizations and instant PDF downloads -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>

<div class="app-wrapper">
    <!-- Include Modern Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Sticky Modern Topbar -->
        <header class="topbar no-print">
            <div class="topbar-left">
                <button type="button" class="mobile-toggle" id="mobileToggle" aria-label="Toggle Navigation">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div class="topbar-title">
                    <?= isset($page_title) ? clean($page_title) : 'Dashboard' ?>
                </div>
            </div>

            <div class="topbar-actions">
                <?php if (!empty($user['branch_name'])): ?>
                    <div class="branch-badge">
                        <span class="branch-dot"></span>
                        <span><?= clean($user['branch_name']) ?></span>
                    </div>
                <?php else: ?>
                    <div class="branch-badge">
                        <span class="branch-dot" style="background:#6366f1; box-shadow:0 0 8px #6366f1;"></span>
                        <span>All Branches (HQ)</span>
                    </div>
                <?php endif; ?>

                <a href="add-transaction.php" class="btn btn-primary btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>+ Add Entry</span>
                </a>
            </div>
        </header>

        <!-- Page Content Body -->
        <main class="page-body">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?php if ($flash['type'] === 'success'): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php elseif ($flash['type'] === 'danger'): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php endif; ?>
                    <span><?= clean($flash['message']) ?></span>
                </div>
            <?php endif; ?>
