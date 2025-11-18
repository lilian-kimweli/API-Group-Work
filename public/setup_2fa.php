<?php
session_start();
require_once '../config/database.php';
require_once '../classes/TwoFactorAuth.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$twoFA = new TwoFactorAuth();
$error = '';
$success = '';

// Check current 2FA status
$current_status = $twoFA->get2FAStatus($_SESSION['user_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_2fa'])) {
        // Enable 2FA - use the correct method with only user_id
        if ($twoFA->enable2FA($_SESSION['user_id'])) {
            // Generate backup codes (optional - remove if you don't want backup codes)
            $backup_codes = $twoFA->generateBackupCodes($_SESSION['user_id']);
            
            $success = 'Two-Factor Authentication has been enabled successfully!';
            $current_status = $twoFA->get2FAStatus($_SESSION['user_id']);
        } else {
            $error = 'Failed to enable 2FA. Please try again.';
        }
    } elseif (isset($_POST['disable_2fa'])) {
        // Disable 2FA
        if ($twoFA->disable2FA($_SESSION['user_id'])) {
            $success = 'Two-Factor Authentication has been disabled.';
            $current_status = $twoFA->get2FAStatus($_SESSION['user_id']);
        } else {
            $error = 'Failed to disable 2FA. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Setup - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Blubell Inventory System</h1>
            <div class="nav-links">
                <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="dashboard.php" class="nav-btn">Dashboard</a>
                <a href="products.php" class="nav-btn">Products</a>
                <a href="cart.php" class="nav-btn">Cart</a>
                <a href="orders.php" class="nav-btn">Orders</a>
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="setup-container">
            <h2>Two-Factor Authentication Setup</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Current Status -->
            <div class="status-card">
                <h3>Current Status</h3>
                <p>
                    Two-Factor Authentication is currently: 
                    <strong style="color: <?php echo $current_status['two_factor_enabled'] ? '#27ae60' : '#e74c3c'; ?>">
                        <?php echo $current_status['two_factor_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </strong>
                </p>
            </div>

            <div class="demo-info">
                <h4>📧 Email-Based 2FA</h4>
                <p>When 2FA is enabled, you'll receive a 6-digit verification code via email every time you login.</p>
                <p><strong>How it works:</strong></p>
                <ul>
                    <li>Enable 2FA on this page</li>
                    <li>When logging in, enter your username and password</li>
                    <li>Check your email for a 6-digit code</li>
                    <li>Enter the code on the verification page</li>
                    <li>Access your account securely</li>
                </ul>
            </div>

            <?php if (!$current_status['two_factor_enabled']): ?>
                <!-- Enable 2FA Section -->
                <div class="setup-section">
                    <h3>Enable Two-Factor Authentication</h3>
                    <p>Enhance your account security by enabling two-factor authentication.</p>
                    
                    <div class="demo-info">
                        <strong>How it works:</strong>
                        <ul>
                            <li>Click "Enable 2FA" below</li>
                            <li>Next time you login, you'll receive a 6-digit code via email</li>
                            <li>Enter the code to access your account</li>
                            <li>Each code expires in 10 minutes</li>
                        </ul>
                    </div>

                    <form method="POST" action="">
                        <button type="submit" name="enable_2fa" class="btn-enable">Enable 2FA</button>
                    </form>
                </div>
            <?php else: ?>
                <!-- 2FA Enabled Section -->
                <div class="setup-section">
                    <h3>Two-Factor Authentication is Active</h3>
                    <p>Your account is now protected with two-factor authentication.</p>
                    
                    <div class="demo-info">
                        <h4>✅ 2FA is Now Active</h4>
                        <p>Next time you login:</p>
                        <ul>
                            <li>Enter your username and password as usual</li>
                            <li>Check your email for a 6-digit verification code</li>
                            <li>Enter the code on the verification page</li>
                            <li>Access your account securely</li>
                        </ul>
                    </div>

                    <form method="POST" action="">
                        <button type="submit" name="disable_2fa" class="btn-disable" 
                                onclick="return confirm('Are you sure you want to disable 2FA? This reduces your account security.')">
                            Disable 2FA
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div style="margin-top: 30px; text-align: center;">
                <a href="dashboard.php" class="btn" style="background: #7f8c8d; display: inline-block; width: auto;">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>