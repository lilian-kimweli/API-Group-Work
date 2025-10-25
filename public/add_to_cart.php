<?php
session_start();
require_once '../classes/Cart.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // Basic validation
    if ($product_id <= 0 || $price <= 0 || $quantity <= 0) {
        $_SESSION['cart_error'] = "Invalid product information!";
        header('Location: products.php');
        exit;
    }

    // Add item to cart
    $cart = new Cart();
    if ($cart->addItem($product_id, $product_name, $price, $quantity)) {
        $_SESSION['cart_message'] = "'$product_name' added to cart successfully!";
    } else {
        $_SESSION['cart_error'] = "Failed to add product to cart!";
    }

    // Redirect back to product page
    header('Location: view_product.php?id=' . $product_id);
    exit;
} else {
    // If not POST request, redirect to products
    header('Location: products.php');
    exit;
}
?>