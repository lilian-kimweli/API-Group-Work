<?php
// callback.php - Handle M-Pesa callbacks
require_once 'functions.php';

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
        
        // You can update your database here
        logTransaction("PAYMENT SUCCESS: MerchantID: $merchantRequestID, CheckoutID: $checkoutRequestID", 'successful_payments.log');
        
        http_response_code(200);
        echo json_encode(array("ResultCode" => 0, "ResultDesc" => "Success"));
    } else {
        // Payment failed
        $errorMessage = $callbackData['Body']['stkCallback']['ResultDesc'];
        logTransaction("PAYMENT FAILED: $errorMessage", 'failed_payments.log');
        
        http_response_code(200);
        echo json_encode(array("ResultCode" => 0, "ResultDesc" => "Success")); // Always return success to M-Pesa
    }
} else {
    // Invalid callback
    logTransaction("INVALID CALLBACK: " . $callbackJSON, 'invalid_callbacks.log');
    http_response_code(200);
    echo json_encode(array("ResultCode" => 0, "ResultDesc" => "Success"));
}
?>