<?php
require_once 'Database.php';


class UserManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Create new user - SIMPLIFIED (without two_factor_enabled)
    public function createUser(User $user) {
        $query = "INSERT INTO users (username, email, password_hash, role) 
                  VALUES (:username, :email, :password_hash, :role)";
        
        $stmt = $this->db->prepare($query);
        
        // Get user data and remove two_factor_enabled
        $userData = $user->toArray();
        unset($userData['two_factor_enabled']);
        
        return $stmt->execute($userData);
    }

    // Find user by username
    public function findUserByUsername($username) {
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Find user by email
    public function findUserByEmail($email) {
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Verify user credentials
    public function verifyUser($username, $password) {
        $userData = $this->findUserByUsername($username);
        
        if ($userData && password_verify($password, $userData['password_hash'])) {
            return $userData;
        }
        
        return false;
    }

    // Update 2FA status separately
    public function update2FAStatus($user_id, $enabled) {
        $query = "UPDATE users SET two_factor_enabled = :enabled WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $enabled_int = $enabled ? 1 : 0;
        $stmt->bindParam(':enabled', $enabled_int, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
}
?>
