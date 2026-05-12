<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->renthub_db;
        $collection = $db->payments; 

        // 1. DATES CAPTURE KARNE KA SABSE MAZBOOT TAREEQA
        $start = $_POST['start_date'] ?? ''; 
        $end   = $_POST['end_date'] ?? '';
        $rental_period = $_POST['rental_period'] ?? '';

        // Agar start/end khali hain toh rental_period se nikaalo
        if (empty($start) && !empty($rental_period)) {
            $parts = explode(" To ", $rental_period);
            $start = trim($parts[0] ?? '');
            $end   = trim($parts[1] ?? '');
        }

        // AGAR PHIR BHI DATES NAHI MILIIN TOH ERROR DE DO (Taakay double booking na ho)
        if (empty($start) || empty($end)) {
            die("Error: Dates missing. Please ensure your form has start_date and end_date.");
        }

        // --- STEP 2: STRICTOR OVERLAP CHECK ---
        // Hum un records ko check kar rahe hain jahan status Successful hai
        $is_booked = $collection->findOne([
            'product_id' => new ObjectId($_POST['product_id']),
            'status'     => 'Successful',
            'first_day'  => ['$lte' => $end],
            'last_day'   => ['$gte' => $start]
        ]);

        if ($is_booked) {
            die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>
                    <h2 style='color:red;'>🚫 Already Booked!</h2>
                    <p>Maaf kijiyega, ye dates pehle hi book ho chuki hain.</p>
                    <a href='javascript:history.back()'>Wapis Jayein</a>
                 </div>");
        }

        // --- STEP 3: DATA INSERT (Majbooran Fields Save Karwana) ---
        $booking = [
            'user_id'        => new ObjectId($_SESSION['user_id']),
            'product_id'     => new ObjectId($_POST['product_id']),
            'total_paid'     => (int)($_POST['total_paid'] ?? $_POST['total_price'] ?? 0),
            'transaction_id' => $_POST['transaction_id'] ?? 'N/A',
            'payment_method' => $_POST['payment_method'] ?? 'N/A',
            'rental_period'  => "$start To $end",
            
            // CRITICAL: Ye fields database mein naye columns banayengi
            'first_day'      => $start, 
            'last_day'       => $end,
            
            'account_holder' => $_POST['account_holder'] ?? 'N/A',
            'account_number' => $_POST['account_number'] ?? 'N/A',
            'status'         => 'Successful',
            'created_at'     => new UTCDateTime()
        ];

        $collection->insertOne($booking);
        header("Location: booking-success.php");
        exit();

    } catch (Exception $e) { 
        echo "Database Error: " . $e->getMessage(); 
    }
}
?>