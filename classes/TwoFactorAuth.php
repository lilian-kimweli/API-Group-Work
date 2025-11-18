<?php
require_once 'Database.php';

class TwoFactorAuth {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->ensureTablesExist();
    }

    // Ensure required tables exist
    private function ensureTablesExist() {
        try {
            // Check if two_factor_codes table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'two_factor_codes'");
            if ($stmt->rowCount() == 0) {
                // Create the table if it doesn't exist
                $query = "CREATE TABLE two_factor_codes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    code VARCHAR(6) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $this->db->exec($query);
                error_log("✅ Created two_factor_codes table");
            }
        } catch (Exception $e) {
            error_log("Table creation error: " . $e->getMessage());
        }
    }

    // Generate a random 6-digit code
    public function generateCode() {
        return sprintf("%06d", mt_rand(1, 999999));
    }

    // Store 2FA code in database with expiration
    public function store2FACode($user_id, $code) {
        try {
            // First, clear any existing codes for this user
            $this->clearExpiredCodes();
            
            // Store new code with 10-minute expiration
            $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes
            
            $query = "INSERT INTO two_factor_codes (user_id, code, expires_at) 
                      VALUES (:user_id, :code, :expires_at)";
            $stmt = $this->db->prepare($query);
            
            $result = $stmt->execute([
                'user_id' => $user_id,
                'code' => $code,
                'expires_at' => $expires_at
            ]);
            
            if ($result) {
                error_log("✅ 2FA code stored for user $user_id: $code");
                error_log("✅ Code expires at: $expires_at");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("❌ Store 2FA code error: " . $e->getMessage());
            return false;
        }
    }

    // Clear expired codes
    private function clearExpiredCodes() {
        try {
            $current_time = date('Y-m-d H:i:s');
            $query = "DELETE FROM two_factor_codes WHERE expires_at < :current_time";
            $stmt = $this->db->prepare($query);
            return $stmt->execute(['current_time' => $current_time]);
        } catch (Exception $e) {
            error_log("Clear expired codes error: " . $e->getMessage());
            return false;
        }
    }

    // Verify 2FA code
    public function verifyCode($user_id, $entered_code) {
        try {
            $this->clearExpiredCodes();
            
            // Use the same timezone as PHP
            $current_time = date('Y-m-d H:i:s');
            
            $query = "SELECT * FROM two_factor_codes 
                      WHERE user_id = :user_id AND code = :code AND expires_at > :current_time 
                      LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'user_id' => $user_id,
                'code' => $entered_code,
                'current_time' => $current_time
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Code is valid - delete it
                $delete_query = "DELETE FROM two_factor_codes WHERE id = :id";
                $delete_stmt = $this->db->prepare($delete_query);
                $delete_stmt->execute(['id' => $result['id']]);
                error_log("✅ 2FA code verified for user $user_id");
                return true;
            }
            
            error_log("❌ Invalid 2FA code for user $user_id: $entered_code");
            error_log("❌ Current time: $current_time");
            return false;
        } catch (Exception $e) {
            error_log("Verify code error: " . $e->getMessage());
            return false;
        }
    }

    // Send 2FA code via email
    public function send2FACode($user_id, $email, $username, $code) {
        return $this->sendRealEmail($email, $username, $code);
    }

    // Send actual email using PHPMailer
    private function sendRealEmail($to, $username, $code) {
        try {
            // Load PHPMailer with correct path
            $vendorPath = __DIR__ . '/../vendor/autoload.php';
            if (!file_exists($vendorPath)) {
                error_log("❌ Vendor path not found: $vendorPath");
                // Fallback to development mode
                return $this->fallbackToDevMode($to, $username, $code);
            }
            require_once $vendorPath;
            
            // Load email configuration
            $configPath = __DIR__ . '/../config/email_config.php';
            if (file_exists($configPath)) {
                require_once $configPath;
            } else {
                error_log("❌ Email config file not found: $configPath");
                return $this->fallbackToDevMode($to, $username, $code);
            }
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings for Gmail
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to, $username);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Blubell Inventory Verification Code';
            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                        .container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; }
                        .code { font-size: 32px; font-weight: bold; color: #3498db; text-align: center; margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; letter-spacing: 5px; }
                        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h2>Hello $username!</h2>
                        <p>Your verification code for Blubell Inventory is:</p>
                        <div class='code'>$code</div>
                        <p><strong>This code will expire in 10 minutes.</strong></p>
                        <p>Enter this code on the verification page to complete your login.</p>
                        
                        <div class='footer'>
                            <p>If you didn't request this code, please ignore this email.</p>
                            <p>Blubell Inventory System</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $mail->AltBody = "Hello $username! Your verification code is: $code. This code will expire in 10 minutes.";
            
            $mail->send();
            error_log("✅ 2FA email sent to $username ($to) with code: $code");
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Email sending failed for $to: " . $e->getMessage());
            return $this->fallbackToDevMode($to, $username, $code);
        }
    }

    // Fallback to development mode if email fails
    private function fallbackToDevMode($to, $username, $code) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['dev_2fa_code'] = $code;
        $_SESSION['dev_2fa_time'] = date('Y-m-d H:i:s');
        $_SESSION['dev_2fa_email'] = $to;
        error_log("📧 DEVELOPMENT MODE - Code for $username ($to): $code");
        return true;
    }

    // Enable 2FA for user
    public function enable2FA($user_id) {
        try {
            $query = "UPDATE users SET two_factor_enabled = TRUE WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute(['user_id' => $user_id]);
            
            if ($result) {
                error_log("✅ 2FA enabled for user $user_id");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("❌ Enable 2FA error: " . $e->getMessage());
            return false;
        }
    }

    // Disable 2FA for user
    public function disable2FA($user_id) {
        try {
            $query = "UPDATE users SET two_factor_enabled = FALSE WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute(['user_id' => $user_id]);
            
            if ($result) {
                error_log("✅ 2FA disabled for user $user_id");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("❌ Disable 2FA error: " . $e->getMessage());
            return false;
        }
    }

    // Get 2FA status
    public function get2FAStatus($user_id) {
        try {
            $query = "SELECT two_factor_enabled, email FROM users WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return ['two_factor_enabled' => false, 'email' => ''];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Get 2FA status error: " . $e->getMessage());
            return ['two_factor_enabled' => false, 'email' => ''];
        }
    }
}
?>