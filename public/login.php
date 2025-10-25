<?php
session_start();
require_once '../config/database.php';
require_once '../classes/UserManager.php';
require_once '../classes/TwoFactorAuth.php';

$error = '';
$show_2fa_form = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_2fa'])) {
        // 2FA verification
        $twoFA = new TwoFactorAuth();
        $user_id = $_SESSION['temp_user_id'];
        
        // Get user's 2FA secret
        $user_status = $twoFA->get2FAStatus($user_id);
        $secret = $user_status['two_factor_secret'];
        
        if ($twoFA->verifyCode($secret, $_POST['verification_code'])) {
            // 2FA successful - complete login
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['username'] = $_SESSION['temp_username'];
            $_SESSION['role'] = $_SESSION['temp_role'];
            $_SESSION['loggedin'] = true;
            
            // Clear temp session data
            unset($_SESSION['temp_user_id'], $_SESSION['temp_username'], $_SESSION['temp_role']);
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid verification code!';
            $show_2fa_form = true;
        }
    } else {
        // Initial login
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password!';
        } else {
            $userManager = new UserManager();
            $userData = $userManager->verifyUser($username, $password);
            
            if ($userData) {
                // Check if 2FA is enabled
                $twoFA = new TwoFactorAuth();
                $user_status = $twoFA->get2FAStatus($userData['id']);
                
                if ($user_status && $user_status['two_factor_enabled']) {
                    // Store temp session data and show 2FA form
                    $_SESSION['temp_user_id'] = $userData['id'];
                    $_SESSION['temp_username'] = $userData['username'];
                    $_SESSION['temp_role'] = $userData['role'];
                    $show_2fa_form = true;
                } else {
                    // No 2FA - direct login
                    $_SESSION['user_id'] = $userData['id'];
                    $_SESSION['username'] = $userData['username'];
                    $_SESSION['role'] = $userData['role'];
                    $_SESSION['loggedin'] = true;
                    
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid username or password!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #2980b9;
        }
        .error {
            color: #e74c3c;
            background: #ffe6e6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info {
            color: #3498db;
            background: #e6f7ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>BlubellSeek Inventory System</h1>
        </div>
    </div>

    <div class="container">
        <div class="auth-container">
            <?php if ($show_2fa_form): ?>
                <h2>Two-Factor Authentication</h2>
                <div class="info">
                    Please enter the 6-digit verification code from your authenticator app.
                </div>
                
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="verification_code">Verification Code:</label>
                        <input type="text" id="verification_code" name="verification_code" required maxlength="6" placeholder="Enter 6-digit code" pattern="[0-9]{6}">
                    </div>
                    <input type="hidden" name="verify_2fa" value="1">
                    <button type="submit" class="btn">Verify Code</button>
                </form>

                <div class="back-link">
                    <a href="login.php">← Back to Login</a>
                </div>

            <?php else: ?>
                <h2>Login to Your Account</h2>
                
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn">Login</button>
                </form>

                <p style="text-align: center; margin-top: 20px;">
                    Don't have an account? <a href="register.php">Register here</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>