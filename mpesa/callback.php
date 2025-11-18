<?php
// callback.php - Handle M-Pesa callbacks
require_once 'functions.php';
require_once '../config/database.php';
require_once '../classes/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Get the callback data
$callbackJSON = file_get_contents('php://input');
$callbackData = json_decode($callbackJSON, true);

// Log the callback
logTransaction($callbackData, 'mpesa_callbacks.log');

// Check if payment was successful
if (isset($callbackData['Body']['stkCallback']['ResultCode'])) {
    $resultCode = $callbackData['Body']['stkCallback']['ResultCode'];
    
    if ($resultCode == 0) {
        // Payment successful
        $merchantRequestID = $callbackData['Body']['stkCallback']['MerchantRequestID'];
        $checkoutRequestID = $callbackData['Body']['stkCallback']['CheckoutRequestID'];
        
        // Extract order ID from callback metadata
        $order_id = extractOrderIdFromCallback($callbackData);
        
        if ($order_id) {
            // Update order status to paid
            updateOrderPaymentStatus($order_id, 'paid', $callbackData);
            
            logTransaction("PAYMENT SUCCESS: Order #$order_id - MerchantID: $merchantRequestID", 'successful_payments.log');
        }
        
        http_response_code(200);
        echo json_encode(array("ResultCode" => 0, "ResultDesc" => "Success"));
    } else {
        // Payment failed
        $errorMessage = $callbackData['Body']['stkCallback']['ResultDesc'];
        
        // Extract order ID and update status
        $order_id = extractOrderIdFromCallback($callbackData);
        if ($order_id) {
            updateOrderPaymentStatus($order_id, 'failed', $callbackData);
        }
        
        logTransaction("PAYMENT FAILED: Order #$order_id - $errorMessage", 'failed_payments.log');
        
        http_response_code(200);
        echo json_encode(array("ResultCode" => 0, "ResultDesc" => "Success"));
    }
} else {
    // Invalid callback
    logTransaction("INVALID CALLBACK: " . $callbackJSON, 'invalid_callbacks.log');
    http_response_code(200);
    echo json_encode(array("ResultCode" => 0, "ResultDesc" => "Success"));
}

function extractOrderIdFromCallback($callbackData) {
    if (isset($callbackData['Body']['stkCallback']['CallbackMetadata']['Item'])) {
        foreach ($callbackData['Body']['stkCallback']['CallbackMetadata']['Item'] as $item) {
            if ($item['Name'] == 'AccountReference') {
                $ref = $item['Value'];
                // Extract order ID from "Order #123"
                if (preg_match('/Order #(\d+)/', $ref, $matches)) {
                    return $matches[1];
                }
                return $ref;
            }
        }
    }
    return null;
}
?>