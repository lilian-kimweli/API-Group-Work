<?php
require_once 'Database.php';

class TwoFactorAuth {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Generate a random secret for 2FA
    public function generateSecret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 characters
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    // Enable 2FA for a user
    public function enable2FA($user_id, $secret) {
        $query = "UPDATE users SET two_factor_enabled = TRUE, two_factor_secret = :secret WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':secret', $secret);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

    // Disable 2FA for a user
    public function disable2FA($user_id) {
        $query = "UPDATE users SET two_factor_enabled = FALSE, two_factor_secret = NULL WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

    // Verify 2FA code
    public function verifyCode($secret, $code) {
        // For simplicity, we'll use a time-based code verification
        // In a real application, you'd use a library like RobThree/TwoFactorAuth
        return $this->verifyTOTP($secret, $code);
    }

    // Simple TOTP verification (for demonstration)
    private function verifyTOTP($secret, $code) {
        // This is a simplified version - in production use a proper library
        $timeSlice = floor(time() / 30);
        $validCodes = [];
        
        // Check current time and previous/next 30-second window
        for ($i = -1; $i <= 1; $i++) {
            $validCodes[] = $this->getTOTPCode($secret, $timeSlice + $i);
        }
        
        return in_array($code, $validCodes);
    }

    private function getTOTPCode($secret, $timeSlice) {
        // Simplified TOTP calculation
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hm = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashpart = substr($hm, $offset, 4);
        $value = unpack('N', $hashpart);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;
        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode($secret) {
        // Simple base32 decoding
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bufferSize = 0;
        $result = '';
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            $buffer = ($buffer << 5) | strpos($base32Chars, $char);
            $bufferSize += 5;
            
            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $result .= chr(($buffer >> $bufferSize) & 0xFF);
            }
        }
        
        return $result;
    }

    // Generate backup codes
    public function generateBackupCodes() {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        }
        return $codes;
    }

    // Save backup codes
    public function saveBackupCodes($user_id, $codes) {
        $query = "UPDATE users SET two_factor_backup_codes = :codes WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $codesJson = json_encode($codes);
        $stmt->bindParam(':codes', $codesJson);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

    // Get user's 2FA status
    public function get2FAStatus($user_id) {
        $query = "SELECT two_factor_enabled, two_factor_secret FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>