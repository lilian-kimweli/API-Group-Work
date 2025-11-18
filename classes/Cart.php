<?php
class Cart {
    private $items = [];

    public function __construct() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // FIX: Always ensure cart is a valid array
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $this->items = $_SESSION['cart'];
    }

    // Add item to cart
    public function addItem($product_id, $product_name, $price, $quantity = 1) {
        // Ensure items is an array
        if (!is_array($this->items)) {
            $this->items = [];
        }
        
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
        // Always return an array
        return is_array($this->items) ? $this->items : [];
    }

    // Get cart total - FIXED with proper validation
    public function getTotal() {
        $total = 0;
        if (is_array($this->items)) {
            foreach ($this->items as $item) {
                // Check if item is a valid array with required keys
                if (is_array($item) && isset($item['price']) && isset($item['quantity'])) {
                    $total += $item['price'] * $item['quantity'];
                }
            }
        }
        return $total;
    }

    // Get item count - FIXED with proper validation
    public function getItemCount() {
        $count = 0;
        if (is_array($this->items)) {
            foreach ($this->items as $item) {
                // Check if item is a valid array with quantity
                if (is_array($item) && isset($item['quantity'])) {
                    $count += $item['quantity'];
                }
            }
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
        return empty($this->items) || !is_array($this->items);
    }

    // Save cart to session
    private function saveCart() {
        $_SESSION['cart'] = $this->items;
    }

    // Debug method to check cart structure
    public function debugCart() {
        echo "<pre>Cart Items: ";
        print_r($this->items);
        echo "</pre>";
        echo "<pre>Session Cart: ";
        print_r($_SESSION['cart']);
        echo "</pre>";
    }
}
?>
