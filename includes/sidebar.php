<?php
/**
 * Modern Sidebar Navigation
 * Shree Laxmi Expenses
 */

$user = current_user();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar no-print" id="sidebar">
    <!-- Brand Logo Area -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <rect x="1" y="3" width="15" height="13"></rect>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg>
        </div>
        <div class="brand-info">
            <h2>Shree Laxmi</h2>
            <span>Fleet & Expenses</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-nav">
        <div class="nav-section-title">Main Operations</div>
        
        <a href="index.php" class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            <span>Dashboard</span>
        </a>

        <a href="transactions.php" class="nav-link <?= in_array($current_page, ['transactions.php', 'voucher.php']) ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            <span>Income & Expenses</span>
        </a>

        <a href="add-transaction.php" class="nav-link <?= $current_page === 'add-transaction.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
            <span>+ Add Entry</span>
        </a>

        <div class="nav-section-title">Fleet & Logistics</div>

        <a href="lorries.php" class="nav-link <?= $current_page === 'lorries.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            <span>Lorries (Fleet)</span>
        </a>

        <a href="assignments.php" class="nav-link <?= $current_page === 'assignments.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            <span>Driver Assignments</span>
        </a>

        <a href="drivers.php" class="nav-link <?= $current_page === 'drivers.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span>Drivers</span>
        </a>

        <div class="nav-section-title">Reports & Statements</div>

        <a href="lorry-report.php" class="nav-link <?= $current_page === 'lorry-report.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            <span>Lorry P&L Report</span>
        </a>

        <?php if (is_admin() || is_manager()): ?>
            <div class="nav-section-title">Administration</div>
            
            <a href="branches.php" class="nav-link <?= $current_page === 'branches.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span>Branch Management</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- User Profile Strip -->
    <div class="sidebar-user">
        <div class="user-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-meta">
                <div class="user-name" title="<?= clean($user['name']) ?>"><?= clean($user['name']) ?></div>
                <div class="user-role-pill"><?= clean($user['role']) ?></div>
            </div>
        </div>
        <a href="logout.php" class="btn-logout" title="Sign Out">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        </a>
    </div>
</aside>
