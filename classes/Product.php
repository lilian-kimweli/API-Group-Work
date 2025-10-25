<?php
class Product {
    private $id;
    private $name;
    private $production_line_id;
    private $style_id;
    private $lot_id;
    private $size_id;
    private $unit_cost;
    private $unit_price;
    private $on_hand_quantity;
    private $description;

    
    // Constructor
    public function __construct($name = '', $production_line_id = null, $style_id = null, $lot_id = null, $size_id = null, $unit_cost = 0, $unit_price = 0, $on_hand_quantity = 0, $description = '') {
        $this->name = $name;
        $this->production_line_id = $production_line_id;
        $this->style_id = $style_id;
        $this->lot_id = $lot_id;
        $this->size_id = $size_id;
        $this->unit_cost = $unit_cost;
        $this->unit_price = $unit_price;
        $this->on_hand_quantity = $on_hand_quantity;
        $this->description = $description;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getProductionLineId() { return $this->production_line_id; }
    public function getStyleId() { return $this->style_id; }
    public function getLotId() { return $this->lot_id; }
    public function getSizeId() { return $this->size_id; }
    public function getUnitCost() { return $this->unit_cost; }
    public function getUnitPrice() { return $this->unit_price; }
    public function getOnHandQuantity() { return $this->on_hand_quantity; }
    public function getDescription() { return $this->description; }

    // Setters
    public function setName($name) { $this->name = $name; }
    public function setProductionLineId($id) { $this->production_line_id = $id; }
    public function setStyleId($id) { $this->style_id = $id; }
    public function setLotId($id) { $this->lot_id = $id; }
    public function setSizeId($id) { $this->size_id = $id; }
    public function setUnitCost($cost) { $this->unit_cost = $cost; }
    public function setUnitPrice($price) { $this->unit_price = $price; }
    public function setOnHandQuantity($quantity) { $this->on_hand_quantity = $quantity; }
    public function setDescription($description) { $this->description = $description; }

    // Calculate profit margin
    public function getProfitMargin() {
        if ($this->unit_price > 0) {
            return (($this->unit_price - $this->unit_cost) / $this->unit_price) * 100;
        }
        return 0;
    }

    // Convert to array for database operations
    public function toArray() {
        return [
            'name' => $this->name,
            'production_line_id' => $this->production_line_id,
            'style_id' => $this->style_id,
            'lot_id' => $this->lot_id,
            'size_id' => $this->size_id,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->unit_price,
            'on_hand_quantity' => $this->on_hand_quantity,
            'description' => $this->description
        ];
    }
}
?>
