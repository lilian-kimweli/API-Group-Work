<?php
class Style {
    private $conn;
    private $table_name = "styles";

    public $id;
    public $name;
    public $production_line_id;

    public function __construct($db) {
        $this->conn = $db;
    }
}
?>