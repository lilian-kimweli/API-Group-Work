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
    function readOne() {
        $query = "SELECT id, name FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->name = $row['name'];
            return true;
        }
        return false;
    }

    function update() {
        $query = "UPDATE " . $this->table_name . " SET name=:name WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
    function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    function search($keywords) {
        $query = "SELECT id, name FROM " . $this->table_name . " 
                  WHERE name LIKE ? ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
        $stmt->bindParam(1, $keywords);
        $stmt->execute();
        return $stmt;
    }
?>