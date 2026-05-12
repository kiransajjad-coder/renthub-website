<?php
/**
 * RentHub OS | Advanced Booking Engine (Inventory Fix)
 */
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

if (!isset($_SESSION['user_id'])) {
    die("Error: Session expired.");
}

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;
    
    $paymentColl = $db->payments; 
    $productColl = $db->products;

    $p_id       = $_POST['product_id'] ?? '69a680fa33a8eb06370cd739'; // Defaulting to your specific ID for safety
    $new_start  = $_POST['start_date'] ?? ''; 
    $new_end    = $_POST['end_date'] ?? '';   
    $total_paid = (float)($_POST['total_paid'] ?? 0);

    // 1. Double Booking Check
    $overlap = $paymentColl->findOne([
        'product_id' => new ObjectId($p_id),
        'status'     => 'Successful',
        'first_day'  => ['$lte' => $new_end],
        'last_day'   => ['$gte' => $new_start]
    ]);

    if ($overlap) { die("Alert: Already booked for these dates!"); }

    // 2. Prepare Data (As per your JazzCash record)
    $booking_id = new ObjectId();
    $bookingData = [
        "_id"            => $booking_id,
        "user_id"        => new ObjectId($_SESSION['user_id']),    
        "product_id"     => new ObjectId($p_id),    
        "total_paid"     => $total_paid,
        "transaction_id" => $_POST['transaction_id'] ?? ("RHP-" . strtoupper(uniqid())),
        "payment_method" => $_POST['payment_method'] ?? 'JazzCash',
        "rental_period"  => "$new_start To $new_end", 
        "first_day"      => $new_start, 
        "last_day"       => $new_end,   
        "account_holder" => $_POST['account_holder'] ?? 'N/A',
        "account_number" => $_POST['account_number'] ?? 'N/A',
        "status"         => "Successful",
        "created_at"     => new UTCDateTime()                                     
    ];

    // 3. Save Payment
    $res = $paymentColl->insertOne($bookingData);

    if ($res->getInsertedCount() > 0) {
        
        // --- CRITICAL FIX: FORCE UPDATE PRODUCT COLLECTION ---
        // Hum product ko find karenge aur uski quantity -1 karenge
        $updateResult = $productColl->updateOne(
            ['_id' => new ObjectId($p_id)],
            [
                '$inc' => [
                    'quantity'   => -1, // Inventory se 1 unit nikal dein
                    'sold_count' => 1   // Sales record mein 1 jama karein
                ]
            ]
        );

        // 4. Sold Out Check: Agar quantity khatam ho jaye
        $product = $productColl->findOne(['_id' => new ObjectId($p_id)]);
        if ($product && (int)($product['quantity'] ?? 0) <= 0) {
            $productColl->updateOne(
                ['_id' => new ObjectId($p_id)],
                ['$set' => ['status' => 'Sold Out']]
            );
        }

        // Redirect to success
        header("Location: booking-success.php?id=" . $booking_id);
        exit();
    }

} catch (Exception $e) {
    die("System Error: " . $e->getMessage());
}
?>