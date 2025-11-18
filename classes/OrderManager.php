<?php
require_once 'Database.php';

class OrderManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Create new order
    public function createOrder(Order $order, $cart_items) {
        try {
            $this->db->beginTransaction();

            // Insert the main order
            $order_query = "INSERT INTO orders (customer_id, order_date, total_amount, status, shipping_address) 
                           VALUES (:customer_id, :order_date, :total_amount, :status, :shipping_address)";
            $order_stmt = $this->db->prepare($order_query);
            $order_data = $order->toArray();
            $order_stmt->execute($order_data);
            
            $order_id = $this->db->lastInsertId();

            // Insert order items
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                          VALUES (:order_id, :product_id, :quantity, :unit_price)";
            $item_stmt = $this->db->prepare($item_query);

            foreach ($cart_items as $product_id => $item) {
                $item_stmt->execute([
                    'order_id' => $order_id,
                    'product_id' => $product_id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price']
                ]);

                // Update product inventory
                $update_query = "UPDATE products SET on_hand_quantity = on_hand_quantity - :quantity WHERE id = :product_id";
                $update_stmt = $this->db->prepare($update_query);
                $update_stmt->execute([
                    'quantity' => $item['quantity'],
                    'product_id' => $product_id
                ]);
            }

            $this->db->commit();
            return $order_id;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Get orders by customer
    public function getOrdersByCustomer($customer_id) {
        $query = "SELECT o.*, COUNT(oi.id) as item_count 
                  FROM orders o 
                  LEFT JOIN order_items oi ON o.id = oi.order_id 
                  WHERE o.customer_id = :customer_id 
                  GROUP BY o.id 
                  ORDER BY o.order_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get order details with items
    public function getOrderDetails($order_id) {
        // Get order info
        $order_query = "SELECT o.*, c.name as customer_name 
                       FROM orders o 
                       LEFT JOIN customers c ON o.customer_id = c.id 
                       WHERE o.id = :order_id";
        $order_stmt = $this->db->prepare($order_query);
        $order_stmt->bindParam(':order_id', $order_id);
        $order_stmt->execute();
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        // Get order items
        $items_query = "SELECT oi.*, p.name as product_name 
                       FROM order_items oi 
                       LEFT JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = :order_id";
        $items_stmt = $this->db->prepare($items_query);
        $items_stmt->bindParam(':order_id', $order_id);
        $items_stmt->execute();
        
        $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }

    // Update order status
    public function updateOrderStatus($order_id, $status) {
        $query = "UPDATE orders SET status = :status WHERE id = :order_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':order_id', $order_id);
        
        return $stmt->execute();
    }
}
?>
