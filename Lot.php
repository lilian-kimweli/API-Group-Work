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
    function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name";
        $stmt = $this->conn->prepare($query);
        $this->name = htmlspecialchars(strip_tags($this->name));
        $stmt->bindParam(":name", $this->name);
        return $stmt->execute();
    }

     function read() {
        $query = "SELECT id, name FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
?>