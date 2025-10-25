<?php
require_once 'Database.php';

class UserManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Create new user
    public function createUser(User $user) {
        $query = "INSERT INTO users (username, email, password_hash, role, two_factor_secret) 
                  VALUES (:username, :email, :password_hash, :role, :two_factor_secret)";
        
        $stmt = $this->db->prepare($query);
        $userData = $user->toArray();
        
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
}
?>