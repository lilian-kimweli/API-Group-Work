<?php
class User {
    private $id;
    private $username;
    private $email;
    private $password_hash;
    private $role;
    private $two_factor_enabled;
    private $created_at;

    // Constructor with email and 2FA field
    public function __construct($username = '', $email = '', $password = '', $role = 'customer', $two_factor_enabled = false) {
        $this->username = $username;
        $this->email = $email;
        $this->role = $role;
        $this->two_factor_enabled = (bool)$two_factor_enabled; // Ensure it's boolean
        if (!empty($password)) {
            $this->setPassword($password);
        }
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
    public function getTwoFactorEnabled() { return $this->two_factor_enabled; }
    public function getCreatedAt() { return $this->created_at; }

    // Setters
    public function setUsername($username) { $this->username = $username; }
    public function setEmail($email) { $this->email = $email; }
    public function setRole($role) { $this->role = $role; }
    public function setTwoFactorEnabled($enabled) { $this->two_factor_enabled = (bool)$enabled; }

    // Password handling
    public function setPassword($password) {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password) {
        return password_verify($password, $this->password_hash);
    }

    // Convert to array for database operations - FIXED
    public function toArray() {
        return [
            'username' => $this->username,
            'email' => $this->email,
            'password_hash' => $this->password_hash,
            'role' => $this->role,
            'two_factor_enabled' => $this->two_factor_enabled ? 1 : 0 // Convert to 1 or 0 for MySQL
        ];
    }
}
?>