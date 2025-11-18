<?php
session_start();

require_once '../config/database.php';
require_once '../classes/UserManager.php';
require_once '../classes/TwoFactorAuth.php';

$error = '';
$show_2fa_form = false;
$email_sent_to = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_2fa'])) {
        // 2FA verification
        $twoFA = new TwoFactorAuth();
        $user_id = $_SESSION['2fa_user_id'];
        $is_valid = $twoFA->verifyCode($user_id, $_POST['verification_code']);

        // === DEBUG CODE ===
        echo "<div style='background: yellow; padding: 10px; margin: 10px; border: 2px solid red;'>";
        echo "<h3>DEBUG INFO:</h3>";
        echo "User ID: " . $user_id . "<br>";
        echo "Entered Code: " . htmlspecialchars($_POST['verification_code']) . "<br>";
        echo "Is Valid: " . ($is_valid ? 'YES' : 'NO') . "<br>";
        echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";

        // Check what codes exist in database
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $debug_query = "SELECT * FROM two_factor_codes WHERE user_id = :user_id";
            $debug_stmt = $db->prepare($debug_query);
            $debug_stmt->execute(['user_id' => $user_id]);
            $debug_codes = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Codes in database: " . (count($debug_codes) ? json_encode($debug_codes) : 'NONE') . "<br>";
            
            // Also check expired codes
            $expired_query = "SELECT * FROM two_factor_codes WHERE expires_at < NOW()";
            $expired_stmt = $db->prepare($expired_query);
            $expired_stmt->execute();
            $expired_codes = $expired_stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Expired codes in database: " . count($expired_codes) . "<br>";
            
        } catch (Exception $e) {
            echo "Database error: " . $e->getMessage() . "<br>";
        }
        echo "</div>";
        // === END DEBUG CODE ===
        
        if ($is_valid) {
            // Get user data and complete login
            $userManager = new UserManager();
            $userData = $userManager->getUserById($user_id);
            
            if ($userData) {
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['username'] = $userData['username'];
                $_SESSION['role'] = $userData['role'];
                $_SESSION['loggedin'] = true;
                
                // Clear 2FA session data
                unset($_SESSION['2fa_user_id'], $_SESSION['needs_2fa'], $_SESSION['dev_2fa_code']);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'User not found!';
                $show_2fa_form = true;
            }
        } else {
            $error = 'Invalid or expired verification code!';
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
                    // Generate and store 2FA code
                    $code = $twoFA->generateCode();
                    $twoFA->store2FACode($userData['id'], $code);
                    
                    // Send 2FA code via email
                    $email_sent = $twoFA->send2FACode($userData['id'], $user_status['email'], $userData['username'], $code);
                    
                    if ($email_sent) {
                        $_SESSION['needs_2fa'] = true;
                        $_SESSION['2fa_user_id'] = $userData['id'];
                        $show_2fa_form = true;
                        $email_sent_to = $user_status['email'];
                    } else {
                        $error = 'Failed to send verification code. Please try again.';
                    }
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
    <title>Login - Blubell Inventory</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-container {
            max-width: 450px;
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
            padding: 12px;
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
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .success {
            color: #27ae60;
            background: #e6ffe6;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        .info {
            color: #3498db;
            background: #e6f7ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .code-input {
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: bold;
            font-family: monospace;
        }
        .resend-link {
            text-align: center;
            margin-top: 15px;
            color: #7f8c8d;
        }
        .debug-info {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        .login-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Blubell Inventory System</h1>
        </div>
    </div>

    <div class="container">
        <div class="auth-container">
            <?php if ($show_2fa_form): ?>
                <h2>Verify Your Identity</h2>
                
                <div class="info">
                    <strong>📧 Verification Code Sent</strong><br>
                    We've sent a 6-digit verification code to:<br>
                    <strong><?php echo htmlspecialchars($email_sent_to); ?></strong>
                    <br><br>
                    <small>Check your email and enter the code below. The code will expire in 10 minutes.</small>
                </div>

                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Development Helper - Shows the code for testing -->
                <?php if (isset($_SESSION['dev_2fa_code'])): ?>
                <div class="debug-info">
                    <strong>🛠️ Development Mode:</strong> Since email might not work locally, use this code: 
                    <strong style="font-size: 1.2em; color: #d35400;"><?php echo $_SESSION['dev_2fa_code']; ?></strong>
                    <br><small>Generated at: <?php echo $_SESSION['dev_2fa_time']; ?></small>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="verification_code">Enter Verification Code:</label>
                        <input type="text" id="verification_code" name="verification_code" 
                               required maxlength="6" placeholder="000000" pattern="[0-9]{6}"
                               class="code-input" autocomplete="off">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Enter the 6-digit code from your email
                        </small>
                    </div>
                    <input type="hidden" name="verify_2fa" value="1">
                    <button type="submit" class="btn">Verify & Login</button>
                </form>

                <div class="resend-link">
                    <p>Didn't receive the code? <a href="login.php">Try logging in again</a></p>
                    <p>Or <a href="setup_2fa.php">disable 2FA</a> if you're having trouble</p>
                </div>

            <?php else: ?>
                <h2>Login to Your Account</h2>
                
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['registration_success'])): ?>
                    <div class="success">
                        <?php echo htmlspecialchars($_SESSION['registration_success']); ?>
                        <?php unset($_SESSION['registration_success']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn">Login</button>
                </form>

                <div class="login-links">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                    <p><small><a href="setup_2fa.php">Setup Two-Factor Authentication</a></small></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-advance code input
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('verification_code');
            if (codeInput) {
                codeInput.focus();
                
                // Auto-advance and format
                codeInput.addEventListener('input', function(e) {
                    // Remove non-numeric characters
                    this.value = this.value.replace(/\D/g, '');
                    
                    // Auto-advance to next input if we had multiple inputs
                    if (this.value.length === 6) {
                        this.blur();
                    }
                });
                
                // Allow paste and auto-format
                codeInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').substring(0, 6);
                    this.value = pastedData;
                });
            }
        });
    </script>
</body>
</html>