<?php
class ProductionLine {
    private $conn;
    private $table_name = "production_lines";

    public $id;
    public $name;
    public $classification;

    public function __construct($db) {
        $this->conn = $db;
    }
}
?>