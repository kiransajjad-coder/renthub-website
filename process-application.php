<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Database Connection
try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Data Collection
    $biz_name   = htmlspecialchars($_POST['biz_name']);
    $category   = htmlspecialchars($_POST['category']);
    $address    = htmlspecialchars($_POST['address']);
    $full_name  = htmlspecialchars($_POST['full_name']);
    $phone      = htmlspecialchars($_POST['phone']);
    $status     = "Pending"; // Default status for new applications
    $created_at = new MongoDB\BSON\UTCDateTime();

    // 2. File Upload Logic
    $document_path = "";
    if (isset($_FILES['document']) && $_FILES['document']['error'] === 0) {
        $upload_dir = '../uploads/vendors/';
        
        // Agar folder nahi hai toh bana do
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
        $file_name = "vendor_" . time() . "." . $file_ext;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['document']['tmp_name'], $target_file)) {
            $document_path = $file_name;
        }
    }

    // 3. Insert into MongoDB
    $insertResult = $db->vendors->insertOne([
        'business_name' => $biz_name,
        'category'      => $category,
        'address'       => $address,
        'owner_name'    => $full_name,
        'phone'         => $phone,
        'document'      => $document_path,
        'status'        => $status,
        'created_at'    => $created_at
    ]);

    // 4. Success UI (Luxury Theme)
    if ($insertResult->getInsertedCount() > 0) {
        showSuccessPage();
    }
}

function showSuccessPage() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Application Submitted | RentHub</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #111; color: #fff; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: sans-serif; }
            .success-box { text-align: center; border: 1px solid #d4af37; padding: 50px; border-radius: 30px; background: rgba(255,255,255,0.05); }
            .icon { font-size: 60px; color: #d4af37; margin-bottom: 20px; }
            .btn-home { background: #d4af37; color: #000; border-radius: 50px; padding: 10px 30px; text-decoration: none; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="success-box">
            <div class="icon">✓</div>
            <h2 style="font-weight: 800;">Application Received!</h2>
            <p class="text-white-50">Our team will review your documents and contact you within 24 hours.</p>
            <br>
            <a href="index.php" class="btn-home">Return to Home</a>
        </div>
    </body>
    </html>
    <?php
}
?>