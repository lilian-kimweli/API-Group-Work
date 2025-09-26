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
    public function readOne() {
        $query = "SELECT id, description, measurements 
                  FROM " . $this->table_name . " 
                  WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->description = $row['description'];
            $this->measurements = json_decode($row['measurements'], true);
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET description=:description, measurements=:measurements 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $measurements_json = json_encode($this->measurements);

        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":measurements", $measurements_json);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Helper method to get measurement details
    public function getMeasurementDetails() {
        if(!empty($this->measurements)) {
            $details = [];
            foreach($this->measurements as $key => $value) {
                $details[] = ucfirst($key) . ": " . $value;
            }
            return implode(", ", $details);
        }
        return "No measurements available";
    }
}
?>