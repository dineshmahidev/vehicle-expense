<?php
/**
 * Modern Login Screen with Quick-Switcher Demo Chips
 * Shree Laxmi Expenses
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT u.*, b.branch_name 
                               FROM users u 
                               LEFT JOIN branches b ON u.branch_id = b.id 
                               WHERE u.username = ? OR u.phone = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        // Allow password_verify or demo fallback 'admin123'
        $password_matches = false;
        if ($user) {
            if (password_verify($password, $user['password']) || $password === 'admin123') {
                $password_matches = true;
            }
        }

        if ($user && $password_matches) {
            if ($user['status'] !== 'active') {
                $error = 'Your account has been deactivated. Please contact administrator.';
            } else {
                // Success: store session
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'branch_id' => $user['branch_id'],
                    'branch_name' => $user['branch_name'] ?? 'Head Office',
                    'phone' => $user['phone']
                ];

                set_flash('success', "Welcome back, {$user['name']}!");
                
                // If user is a driver, redirect directly to add-transaction or dashboard
                if ($user['role'] === 'driver') {
                    header("Location: " . BASE_URL . "/add-transaction.php");
                } else {
                    header("Location: " . BASE_URL . "/index.php");
                }
                exit;
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-logo">
        <div class="brand-icon" style="width:50px; height:50px;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <rect x="1" y="3" width="15" height="13"></rect>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg>
        </div>
        <div>
            <h1 style="font-size:20px; font-weight:800; color:#0f172a; margin:0; line-height:1.2;">Shree Laxmi</h1>
            <span style="font-size:12px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Fleet & Expense Portal</span>
        </div>
    </div>

    <h2 style="font-size:16px; font-weight:700; margin-bottom:6px; color:#1e293b;">Sign In to your Account</h2>
    <p style="font-size:13px; color:#64748b; margin-bottom:22px;">Enter your transport credentials to manage lorry expenses and loads.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom:18px; padding:10px 14px; font-size:13px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?= clean($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label class="form-label">Username / Mobile No</label>
            <input type="text" name="username" id="loginUsername" class="form-control" required placeholder="e.g. admin or 9842700001" value="<?= clean($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" style="display:flex; justify-content:space-between;">
                <span>Password</span>
            </label>
            <input type="password" name="password" id="loginPassword" class="form-control" required placeholder="Enter your password" value="admin123">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; padding:12px; font-size:14px; margin-top:10px;">
            <span>Sign In to System</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </button>
    </form>

    <!-- Quick Demo Role Switcher -->
    <div class="login-demo-roles">
        <span style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:#94a3b8; margin-bottom:10px; letter-spacing:0.5px;">Quick Test Login (Click to fill):</span>
        <div>
            <span class="demo-chip" onclick="quickFill('admin', 'admin123')">👑 Admin</span>
            <span class="demo-chip" onclick="quickFill('murugan', 'admin123')">🏢 Branch Manager</span>
            <span class="demo-chip" onclick="quickFill('driver1', 'admin123')">🚚 Driver 1 (Arumugam)</span>
            <span class="demo-chip" onclick="quickFill('driver2', 'admin123')">🚚 Driver 2 (Rajendran)</span>
        </div>
    </div>
</div>

<script>
function quickFill(user, pass) {
    document.getElementById('loginUsername').value = user;
    document.getElementById('loginPassword').value = pass;
}
</script>

</body>
</html>
