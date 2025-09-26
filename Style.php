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
    function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, production_line_id=:production_line_id";

        $stmt = $this->conn->prepare($query);
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->production_line_id = htmlspecialchars(strip_tags($this->production_line_id));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":production_line_id", $this->production_line_id);

        return $stmt->execute();
    }

     function read() {
        $query = "SELECT s.id, s.name, p.name as production_line_name 
                  FROM " . $this->table_name . " s
                  LEFT JOIN production_lines p ON s.production_line_id = p.id
                  ORDER BY s.name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    function readOne() {
        $query = "SELECT s.id, s.name, s.production_line_id, p.name as production_line_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN production_lines p ON s.production_line_id = p.id
                  WHERE s.id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->name = $row['name'];
            $this->production_line_id = $row['production_line_id'];
            return true;
        }
        return false;
    }

     function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, production_line_id=:production_line_id 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->production_line_id = htmlspecialchars(strip_tags($this->production_line_id));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":production_line_id", $this->production_line_id);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }
?>