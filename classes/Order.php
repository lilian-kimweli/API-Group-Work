<?php
class Order {
    private $id;
    private $customer_id;
    private $order_date;
    private $total_amount;
    private $status;
    private $shipping_address;
    private $created_at;

    // Constructor
    public function __construct($customer_id = null, $total_amount = 0, $status = 'pending', $shipping_address = '') {
        $this->customer_id = $customer_id;
        $this->total_amount = $total_amount;
        $this->status = $status;
        $this->shipping_address = $shipping_address;
        $this->order_date = date('Y-m-d H:i:s');
    }

    // Getters
    public function getId() { return $this->id; }
    public function getCustomerId() { return $this->customer_id; }
    public function getOrderDate() { return $this->order_date; }
    public function getTotalAmount() { return $this->total_amount; }
    public function getStatus() { return $this->status; }
    public function getShippingAddress() { return $this->shipping_address; }
    public function getCreatedAt() { return $this->created_at; }

    // Setters
    public function setCustomerId($customer_id) { $this->customer_id = $customer_id; }
    public function setTotalAmount($total_amount) { $this->total_amount = $total_amount; }
    public function setStatus($status) { $this->status = $status; }
    public function setShippingAddress($shipping_address) { $this->shipping_address = $shipping_address; }

    // Convert to array for database operations
    public function toArray() {
        return [
            'customer_id' => $this->customer_id,
            'order_date' => $this->order_date,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'shipping_address' => $this->shipping_address
        ];
    }
}
?>