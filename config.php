<?php
class Database {
    private $host = "localhost";
    private $db_name = "bluebell_inventory";
    private $username = "root";
    private $password = "eunice";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
        public function testConnection() {
        try {
            $conn = $this->getConnection();
            return $conn ? "Connected successfully!" : "Connection failed";
        } catch(Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}
?>