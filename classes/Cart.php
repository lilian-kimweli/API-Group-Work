<?php
class Cart {
    private $items = [];

    public function __construct() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialize cart in session if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $this->items = $_SESSION['cart'];
    }

    // Add item to cart
    public function addItem($product_id, $product_name, $price, $quantity = 1) {
        if (isset($this->items[$product_id])) {
            // Update quantity if item already exists
            $this->items[$product_id]['quantity'] += $quantity;
        } else {
            // Add new item
            $this->items[$product_id] = [
                'name' => $product_name,
                'price' => $price,
                'quantity' => $quantity
            ];
        }
        
        $this->saveCart();
        return true;
    }

    // Remove item from cart
    public function removeItem($product_id) {
        if (isset($this->items[$product_id])) {
            unset($this->items[$product_id]);
            $this->saveCart();
            return true;
        }
        return false;
    }

    // Update item quantity
    public function updateQuantity($product_id, $quantity) {
        if (isset($this->items[$product_id])) {
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                return $this->removeItem($product_id);
            } else {
                $this->items[$product_id]['quantity'] = $quantity;
                $this->saveCart();
                return true;
            }
        }
        return false;
    }

    // Get all cart items
    public function getItems() {
        return $this->items;
    }

    // Get cart total
    public function getTotal() {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    // Get item count
    public function getItemCount() {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    // Clear cart
    public function clear() {
        $this->items = [];
        $this->saveCart();
    }

    // Check if cart is empty
    public function isEmpty() {
        return empty($this->items);
    }

    // Save cart to session
    private function saveCart() {
        $_SESSION['cart'] = $this->items;
    }
}
?>