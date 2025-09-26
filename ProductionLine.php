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

function read() {
        $query = "SELECT id, name, classification 
                  FROM " . $this->table_name . " 
                  ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

     function readOne() {
        $query = "SELECT id, name, classification 
                  FROM " . $this->table_name . " 
                  WHERE id = ? 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->name = $row['name'];
            $this->classification = $row['classification'];
            return true;
        }
        return false;
    }

    function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, classification=:classification 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->classification = htmlspecialchars(strip_tags($this->classification));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":classification", $this->classification);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    function search($keywords) {
        $query = "SELECT id, name, classification 
                  FROM " . $this->table_name . " 
                  WHERE name LIKE ? OR classification LIKE ? 
                  ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);

        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";

        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);

        $stmt->execute();
        return $stmt;
    }

?>