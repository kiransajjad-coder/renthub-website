<?php
/**
 * RentHub OS | Booking Summary (Checkout)
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
use MongoDB\BSON\ObjectId;

// 1. Check karein ke user login hai ya nahi
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Data receiving and validation
if (!isset($_POST['product_id']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h2>⚠️ Booking Data Missing</h2>
            <p>Please go back and select valid rental dates.</p>
            <a href='index.php' style='color:#0A192F; font-weight:bold;'>Go to Marketplace</a>
         </div>");
}

$product_id  = $_POST['product_id'];
$start_date  = $_POST['start_date'];
$end_date    = $_POST['end_date'];

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->renthub_db;
    $product = $db->products->findOne(['_id' => new ObjectId($product_id)]);

    if (!$product) {
        die("Product not found in database.");
    }

    // --- LOGIC: Re-calculate Days & Price (Server-Side Validation) ---
    $date1 = new DateTime($start_date);
    $date2 = new DateTime($end_date);
    $interval = $date1->diff($date2);
    $days = $interval->days;
    if ($days <= 0) $days = 1; // Minimum 1 day charge

    $base_price  = $product['price'] * $days;
    $service_fee = 500; 
    $grand_total = $base_price + $service_fee;

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. Page Title -->
    <title>Booking Summary | RentHubPro - Final Review</title>

    <!-- 2. Premium Favicon (Executive Gold Crown) -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 3. Google Fonts (Plus Jakarta Sans - Modern & Professional) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <!-- 4. Bootstrap 5 & Font Awesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --gold: #D4AF37; --navy: #0A192F; --bg: #F8FAFC; }
        body { background: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--navy); }
        .checkout-card { border-radius: 30px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.05); }
        .checkout-header { background: var(--navy); color: white; padding: 30px; border-radius: 30px 30px 0 0; }
        .product-preview { background: #fff; border-radius: 20px; padding: 15px; border: 1px solid #edf2f7; }
        .product-preview img { width: 100%; border-radius: 15px; height: 100px; object-fit: cover; }
        .summary-box { background: #fdfdfd; border: 1px solid #f1f5f9; border-radius: 20px; padding: 20px; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-weight: 500; }
        .total-row { border-top: 2px dashed #e2e8f0; padding-top: 15px; margin-top: 15px; font-weight: 800; font-size: 1.4rem; color: var(--navy); }
        .btn-confirm { background: var(--navy); color: white; border: none; padding: 20px; border-radius: 20px; width: 100%; font-weight: 700; font-size: 1.1rem; transition: 0.3s; }
        .btn-confirm:hover { background: var(--gold); color: black; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(212, 175, 55, 0.2); }
        .date-badge { background: #fff; border: 1px solid #e2e8f0; padding: 10px; border-radius: 15px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card checkout-card">
                <div class="checkout-header text-center">
                    <h4 class="m-0 fw-bold text-uppercase" style="letter-spacing: 2px;">Review Your Booking</h4>
                </div>
                
                <div class="card-body p-4 p-lg-5">
                    <!-- Product Info -->
                    <div class="product-preview mb-4 d-flex align-items-center gap-3">
                        <div style="width: 120px;">
                            <img src="<?= $product['image'] ?>" alt="<?= $product['name'] ?>">
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold m-0"><?= $product['name'] ?></h5>
                            <p class="text-muted small mb-1"><i class="fa fa-location-dot me-1 text-gold"></i> <?= $product['city'] ?></p>
                            <span class="badge bg-light text-navy fw-bold">Rs. <?= number_format($product['price']) ?> / day</span>
                        </div>
                    </div>

                    <!-- Dates Display -->
                    <div class="row mb-4 g-3">
                        <div class="col-6">
                            <div class="date-badge text-center">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Pickup</small>
                                <div class="fw-bold"><?= date('D, M j, Y', strtotime($start_date)) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="date-badge text-center">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Return</small>
                                <div class="fw-bold"><?= date('D, M j, Y', strtotime($end_date)) ?></div>
                            </div>
                        </div>
                        <div class="col-12 text-center">
                            <span class="badge rounded-pill bg-navy px-3 py-2">Total Duration: <?= $days ?> Day(s)</span>
                        </div>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="summary-box">
                        <div class="summary-item">
                            <span class="text-muted">Rental (Rs. <?= number_format($product['price']) ?> × <?= $days ?>)</span>
                            <span>Rs. <?= number_format($base_price) ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="text-muted">Platform Service Fee</span>
                            <span>Rs. <?= number_format($service_fee) ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="text-muted">Security Deposit</span>
                            <span class="text-success fw-bold">Rs. 0 (Waived)</span>
                        </div>
                        
                        <div class="summary-item total-row">
                            <span>Grand Total</span>
                            <span>Rs. <?= number_format($grand_total) ?></span>
                        </div>
                    </div>

                    <!-- Form to Payment -->
                    <form action="payment-method.php" method="POST" class="mt-4">
                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                        <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['name']) ?>">
                        <input type="hidden" name="start_date" value="<?= $start_date ?>">
                        <input type="hidden" name="end_date" value="<?= $end_date ?>">
                        <input type="hidden" name="total_price" value="<?= $grand_total ?>">
                        
                        <button type="submit" class="btn-confirm">
                            PROCEED TO PAYMENT <i class="fa fa-arrow-right ms-2"></i>
                        </button>
                    </form>
                    
                    <div class="d-flex justify-content-center gap-3 mt-4 small text-muted">
                        <span><i class="fa fa-shield-halved text-success"></i> Secure Checkout</span>
                        <span><i class="fa fa-rotate-left"></i> Easy Cancellation</span>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="product-details.php?id=<?= $product_id ?>" class="text-decoration-none text-muted small fw-bold">
                    <i class="fa fa-chevron-left me-1"></i> Edit Booking Details
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>