<?php
class ProductionLine {
    private $conn;
    private $table_name = "production_lines";

    public $id;
    public $name;
    public $classification;

 public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, classification=:classification";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->classification = htmlspecialchars(strip_tags($this->classification));

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":classification", $this->classification);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}

?>