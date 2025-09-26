<?php
class Lot {
    private $conn;
    private $table_name = "lots";

    public $id;
    public $name;

    public function __construct($db) {
        $this->conn = $db;
    }
}
?>