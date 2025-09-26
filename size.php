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
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET description=:description, measurements=:measurements";

        $stmt = $this->conn->prepare($query);
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        // Handle JSON measurements
        $measurements_json = json_encode($this->measurements);

        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":measurements", $measurements_json);

        return $stmt->execute();
    }

    public function read() {
        $query = "SELECT id, description, measurements 
                  FROM " . $this->table_name . " 
                  ORDER BY description ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>