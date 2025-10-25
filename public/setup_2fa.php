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
        // Generate new secret and enable 2FA
        $secret = $twoFA->generateSecret();
        if ($twoFA->enable2FA($_SESSION['user_id'], $secret)) {
            $_SESSION['2fa_secret'] = $secret;
            $success = '2FA has been enabled! Please verify your authenticator app.';
        } else {
            $error = 'Failed to enable 2FA. Please try again.';
        }
    } elseif (isset($_POST['verify_2fa'])) {
        // Verify the code and complete setup
        $secret = $_SESSION['2fa_secret'];
        if ($twoFA->verifyCode($secret, $_POST['verification_code'])) {
            // Generate and save backup codes
            $backup_codes = $twoFA->generateBackupCodes();
            $twoFA->saveBackupCodes($_SESSION['user_id'], $backup_codes);
            
            unset($_SESSION['2fa_secret']);
            $success = 'Two-Factor Authentication has been successfully enabled!';
            $current_status = $twoFA->get2FAStatus($_SESSION['user_id']);
        } else {
            $error = 'Invalid verification code. Please try again.';
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

// Get current status again in case it changed
$current_status = $twoFA->get2FAStatus($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Setup - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .setup-container {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
        }
        .qr-section {
            text-align: center;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        .secret-code {
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 18px;
            letter-spacing: 2px;
            margin: 15px 0;
        }
        .backup-codes {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .backup-code {
            font-family: monospace;
            padding: 5px 10px;
            margin: 5px;
            background: white;
            border-radius: 3px;
            display: inline-block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn-enable {
            background: #27ae60;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-disable {
            background: #e74c3c;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-verify {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .error {
            color: #e74c3c;
            background: #ffe6e6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            color: #27ae60;
            background: #e6ffe6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .step {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>BlubellSeek Inventory System</h1>
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
                    <strong><?php echo $current_status['two_factor_enabled'] ? 'ENABLED' : 'DISABLED'; ?></strong>
                </p>
            </div>

            <?php if (!$current_status['two_factor_enabled']): ?>
                <!-- Enable 2FA Section -->
                <?php if (!isset($_SESSION['2fa_secret'])): ?>
                    <div class="setup-section">
                        <h3><span class="step">1</span>Enable Two-Factor Authentication</h3>
                        <p>Enhance your account security by enabling two-factor authentication.</p>
                        <form method="POST" action="">
                            <button type="submit" name="enable_2fa" class="btn-enable">Enable 2FA</button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Setup Instructions -->
                    <div class="setup-section">
                        <h3><span class="step">2</span>Setup Your Authenticator App</h3>
                        
                        <div class="qr-section">
                            <p><strong>Scan this QR code with your authenticator app:</strong></p>
                            <div style="background: white; padding: 20px; display: inline-block; border-radius: 8px;">
                                <!-- In a real app, you'd generate a proper QR code -->
                                <div style="width: 200px; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 2px dashed #ccc;">
                                    <span style="color: #666;">QR Code Placeholder</span>
                                </div>
                            </div>
                            
                            <p><strong>Or enter this code manually:</strong></p>
                            <div class="secret-code">
                                <?php echo $_SESSION['2fa_secret']; ?>
                            </div>
                        </div>

                        <div class="setup-section">
                            <h3><span class="step">3</span>Verify Setup</h3>
                            <p>Enter the 6-digit code from your authenticator app to verify the setup:</p>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="verification_code">Verification Code:</label>
                                    <input type="text" id="verification_code" name="verification_code" required maxlength="6" placeholder="Enter 6-digit code" pattern="[0-9]{6}">
                                </div>
                                <button type="submit" name="verify_2fa" class="btn-verify">Verify and Enable 2FA</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Disable 2FA Section -->
                <div class="setup-section">
                    <h3>Disable Two-Factor Authentication</h3>
                    <p>If you wish to disable two-factor authentication, click the button below.</p>
                    <form method="POST" action="">
                        <button type="submit" name="disable_2fa" class="btn-disable" onclick="return confirm('Are you sure you want to disable 2FA? This reduces your account security.')">Disable 2FA</button>
                    </form>
                </div>
            <?php endif; ?>

            <div style="margin-top: 30px; text-align: center;">
                <a href="dashboard.php" class="btn" style="background: #7f8c8d;">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>