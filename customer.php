<?php
class Customer {
    private $conn;
    private $table_name = "customers";

    public $id;
    public $name;
    public $address;
    public $telephone;
    public $email;

    public function __construct($db) {
        $this->conn = $db;
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, address=:address, telephone=:telephone, email=:email";

        $stmt = $this->conn->prepare($query);
        
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->email = htmlspecialchars(strip_tags($this->email));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":email", $this->email);

        return $stmt->execute();
    }

    public function read() {
        $query = "SELECT id, name, address, telephone, email 
                  FROM " . $this->table_name . " 
                  ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    public function readOne() {
        $query = "SELECT id, name, address, telephone, email 
                  FROM " . $this->table_name . " 
                  WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->name = $row['name'];
            $this->address = $row['address'];
            $this->telephone = $row['telephone'];
            $this->email = $row['email'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, address=:address, telephone=:telephone, email=:email 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Get customer's total spending
    public function getTotalSpending() {
        $query = "SELECT SUM(total_amount) as total_spent 
                  FROM transactions 
                  WHERE customer_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_spent'] ? $row['total_spent'] : 0;
    }
}
?>