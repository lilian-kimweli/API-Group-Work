<?php

class Size {
    private $conn;
    private $table_name = "sizes";

    public $id;
    public $description;
    public $measurements; // JSON field for flexible measurements

    public function __construct($db) {
        $this->conn = $db;
    }
}
?>