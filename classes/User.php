<?php
class User {
    private $id;
    private $username;
    private $email;
    private $password_hash;
    private $role;
    private $two_factor_secret;
    private $created_at;

    // Constructor
    public function __construct($username = '', $email = '', $password = '', $role = 'customer') {
        $this->username = $username;
        $this->email = $email;
        $this->role = $role;
        if (!empty($password)) {
            $this->setPassword($password);
        }
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
    public function getTwoFactorSecret() { return $this->two_factor_secret; }
    public function getCreatedAt() { return $this->created_at; }

    // Setters
    public function setUsername($username) { $this->username = $username; }
    public function setEmail($email) { $this->email = $email; }
    public function setRole($role) { $this->role = $role; }
    public function setTwoFactorSecret($secret) { $this->two_factor_secret = $secret; }

    // Password handling - SECURE!
    public function setPassword($password) {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password) {
        return password_verify($password, $this->password_hash);
    }

    // Convert to array for database operations
    public function toArray() {
        return [
            'username' => $this->username,
            'email' => $this->email,
            'password_hash' => $this->password_hash,
            'role' => $this->role,
            'two_factor_secret' => $this->two_factor_secret
        ];
    }
}
?>