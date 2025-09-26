<?php
class Customer {
    private $conn;
    private $table_name = "customers";

    public $id;
    public $name;
    public $address;
    public $telephone;
    public $email;

    public function __construct($db) {
        $this->conn = $db;
    }
}
?>