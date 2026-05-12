<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * RentHubPro - Premium Product Details & Auto-Inventory Logic
 * Color Theme: Gold (#D4AF37) & Navy (#0A192F)
 */

require_once __DIR__ . '/../vendor/autoload.php';
use MongoDB\BSON\ObjectId;

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->renthub_db;

    // 1. Request Validation
    $id = $_GET['id'] ?? '';
    if (empty($id) || !preg_match('/^[a-f\d]{24}$/i', $id)) {
        header("Location: search-results.php");
        exit();
    }

    // 2. Fetch Product Data
    $product = $db->products->findOne(['_id' => new ObjectId($id)]);
    if (!$product) {
        die("<div class='alert alert-danger m-5'>Error: Product not found in our catalog.</div>");
    }

    // 3. Advanced Inventory Logic (A to Z Automatic)
    $today = date('Y-m-d');
    $max_capacity = isset($product['quantity']) ? (int) $product['quantity'] : 0;

    // Fetch successful bookings
    $bookings = $db->payments->find([
        'product_id' => new ObjectId($id),
        'status' => 'Successful'
    ]);

    $rented_units = 0;
    foreach ($bookings as $entry) {
        $return_date = "";
        if (isset($entry['last_day'])) {
            $return_date = $entry['last_day'];
        } elseif (isset($entry['rental_period'])) {
            $parts = explode(" To ", $entry['rental_period']);
            $return_date = trim($parts[1] ?? '');
        }

        // Agar return date aaj ya future ki hai, tabhi item "Occupied" hoga.
        // Jaise hi return date guzregi, ye automatically free ho jayega.
        if (!empty($return_date) && $return_date >= $today) {
            $rented_units++;
        }
    }

    $current_stock = max(0, $max_capacity - $rented_units);
    $is_available = ($current_stock > 0);

} catch (Exception $e) {
    error_log($e->getMessage());
    die("<div class='alert alert-danger m-5'>System Maintenance: Please try again later.</div>");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- 1. Page Title -->
    <title><?= htmlspecialchars($product['name']) ?> | RentHubPro</title>

    <!-- 2. Auto Refresh (Every 5 Seconds) -->
    <meta http-equiv="refresh" content="30">

    <!-- 3. Browser Favicon -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- CDNs & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --gold: #D4AF37;
            --navy: #0A192F;
            --slate: #64748b;
            --light: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--light);
            color: var(--navy);
        }

        /* Navbar Styling */
        .navbar {
            background: var(--navy);
            border-bottom: 3px solid var(--gold);
            padding: 15px 0;
        }

        .logo-text {
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .custom-logo {
            height: 45px;
            border-radius: 8px;
        }

        .btn-vendor-nav {
            background: var(--gold);
            color: var(--navy);
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-vendor-nav:hover {
            background: #fff;
            color: var(--navy);
        }

        /* Product Section */
        .main-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .product-gallery img {
            width: 100%;
            height: auto;
            max-height: 450px;
            object-fit: cover;
            border-radius: 20px;
        }

        /* Booking Sidebar */
        .booking-widget {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 30px;
            position: sticky;
            top: 110px;
            box-shadow: 0 15px 35px rgba(10, 25, 47, 0.1);
        }

        .price-label {
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--navy);
        }

        .status-badge {
            padding: 8px 18px;
            border-radius: 50px;
            font-weight: 400;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
        }

        .status-online {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .status-offline {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .form-control-custom {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
        }

        .form-control-custom:focus {
            border-color: var(--gold);
            box-shadow: none;
        }

        .btn-action {
            background: var(--navy);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 15px;
            font-weight: 700;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .btn-action:hover:not(:disabled) {
            background: var(--gold);
            color: var(--navy);
            transform: translateY(-3px);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top custom-nav">
        <div class="container d-flex align-items-center justify-content-between">

            <a class="navbar-brand d-flex align-items-center text-decoration-none" href="index.php">
                <div class="logo-container">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSeIaL6-BRPUh2B5uruCISrVpH_nMd7gkxwLckL1ZDtiw&s"
                        alt="RentHub Logo" class="custom-logo">
                </div>
                <span class="ms-2 fw-bold fs-5 logo-text">
                    <span style="color: #d4af37;">Rent</span><span class="text-white">HubPro</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="mainNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link text-white px-3" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link text-white px-3" href="search-results.php">Browse
                            Rentals</a></li>
                    <li class="nav-item mt-2 mt-lg-0 ms-lg-3">
                        <a class="btn-vendor-nav" href="vendor-apply.php">Become Vendor</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row g-5">
            <!-- Main Product Info -->
            <div class="col-lg-7">
                <div class="product-gallery mb-4">
                    <img src="<?= htmlspecialchars($product['image']) ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>" class="shadow-sm">
                </div>

              <div class="main-card p-4 shadow-sm border-0 rounded-4 bg-white">
    <!-- Header Section: Title, Location & Availability -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h4 class="h4 fw-bold mb-1 text-navy"><?= htmlspecialchars($product['name']) ?></h3>
            <p class="text-muted mb-0">
                <i class="fa-solid fa-location-dot me-2 text-gold"></i>
                <?= htmlspecialchars($product['city'] ?? 'Pakistan') ?>
            </p>
        </div>
        <div class="text-end">
            <span class="status-badge <?= $is_available ? 'status-online' : 'status-offline' ?> px-4 py-1 rounded-pill fw-bold small shadow-sm">
                <i class="fa-solid fa-circle me-2" style="font-size: 6px;"></i>
                <?= $is_available ? "Available ($current_stock units)" : "Currently Booked" ?>
            </span>
        </div>
    </div>

    <!-- Professional Description Section -->
    <div class="product-description-wrapper mt-4">
       
        
        <p class="lh-lg text-secondary mb-4" style="font-size: 1.05rem; border-left: 4px solid var(--gold); padding-left: 20px; background-color: #f8f9fa; padding-top: 15px; padding-bottom: 15px; border-radius: 0 12px 12px 0;">
            Experience the elite standard with our <strong><?= htmlspecialchars($product['name']) ?></strong>. 
            Currently available in <strong><?= htmlspecialchars($product['city'] ?? 'Sialkot') ?></strong>, 
            this item is meticulously maintained and sanitized to ensure the highest standards of quality and hygiene for your special event.
        </p>

        <!-- Trust Features Grid -->
        <div class="row g-3 mt-2">
            <!-- Quality -->
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 rounded-4 bg-white border border-light shadow-sm transition-hover">
                    <div class="icon-box me-3 bg-light rounded-circle p-2">
                        <i class="fa-solid fa-circle-check text-success fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">Quality Verified</h6>
                        <small class="text-muted">Rigorous 10-point check</small>
                    </div>
                </div>
            </div>
            <!-- Delivery -->
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 rounded-4 bg-white border border-light shadow-sm transition-hover">
                    <div class="icon-box me-3 bg-light rounded-circle p-2">
                        <i class="fa-solid fa-truck-fast text-primary fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">On-time Delivery</h6>
                        <small class="text-muted">Guaranteed arrival</small>
                    </div>
                </div>
            </div>
            <!-- Returns -->
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 rounded-4 bg-white border border-light shadow-sm transition-hover">
                    <div class="icon-box me-3 bg-light rounded-circle p-2">
                        <i class="fa-solid fa-rotate-left text-warning fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">Easy Returns</h6>
                        <small class="text-muted">Hassle-free pickup</small>
                    </div>
                </div>
            </div>
            <!-- Support -->
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 rounded-4 bg-white border border-light shadow-sm transition-hover">
                    <div class="icon-box me-3 bg-light rounded-circle p-2">
                        <i class="fa-solid fa-headset text-info fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">24/7 Support</h6>
                        <small class="text-muted">Dedicated concierge</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Technical Details -->
        <?php if(!empty($product['description'])): ?>
        <div class="mt-5 pt-4 border-top">
            <h6 class="fw-bold mb-3 text-navy">
                <i class="fa-solid fa-list-check me-2 text-gold"></i>Item Specifications
            </h6>
            <div class="p-3 rounded-3 bg-light">
                <p class="text-muted small mb-0 lh-base">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
            </div>

            <!-- Booking Sidebar -->
            <div class="col-lg-5">
                <div class="booking-widget">
                    <div class="price-label mb-1">
                        Rs. <?= number_format($product['price']) ?>
                        <span class="fs-6 text-muted fw-normal">/ day</span>
                    </div>
                    <p class="text-muted small mb-4">Transparent pricing. No hidden fees.</p>

                    <form action="checkout.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product['_id'] ?>">
                        <input type="hidden" name="daily_rate" id="daily_rate" value="<?= $product['price'] ?>">
                        <input type="hidden" name="total_price" id="total_price_hidden"
                            value="<?= $product['price'] ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold">Reservation Dates</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-slate fw-600">Start Date</small>
                                    <input type="date" name="start_date" id="start_date"
                                        class="form-control form-control-custom" required min="<?= $today ?>"
                                        <?= !$is_available ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-6">
                                    <small class="text-slate fw-600">End Date</small>
                                    <input type="date" name="end_date" id="end_date"
                                        class="form-control form-control-custom" required min="<?= $today ?>"
                                        <?= !$is_available ? 'disabled' : '' ?>>
                                </div>
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded-4 mb-4 border">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-600 text-slate">Total Payment</span>
                                <span class="h4 fw-bold m-0 text-navy">Rs. <span
                                        id="total_display"><?= number_format($product['price']) ?></span></span>
                            </div>
                        </div>

                        <?php if (!$is_available): ?>
                            <div class="alert alert-danger border-0 rounded-3 mb-3 small fw-600">
                                <i class="fa-solid fa-calendar-xmark me-2"></i>
                                Item is booked until current rental ends.
                            </div>
                            <button type="button" class="btn btn-action" disabled>Not Available</button>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <a href="login.html" class="btn btn-action text-decoration-none d-block text-center">Login to
                                Book</a>
                        <?php else: ?>
                            <button type="submit" class="btn btn-action shadow">Reserve Now</button>
                        <?php endif; ?>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center">
                        <p class="text-muted small mb-0"><i class="fa-solid fa-shield-check text-gold me-1"></i>
                            Verified Premium Vendor</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h4 class="text-white fw-800 mb-4"><span class="text-gold">Rent</span>HubPro</h4>
                    <p>The ultimate rental marketplace for premium gear and luxury assets across Pakistan.</p>
                    <div class="d-flex gap-3 fs-5 mt-4">
                        <a href="https://www.facebook.com/your-profile-id" target="_blank"
                            class="text-white me-3 social-icon">
                            <i class="fab fa-facebook-f"></i>
                        </a>

                        <a href="https://www.instagram.com/kiran" target="_blank" class="text-white me-3 social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>

                        <a href="https://www.twitter.com/your-username" target="_blank" class="text-white social-icon">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://wa.me/yournumber" class="whatsapp-float text-white" target="_blank">
                            <i class="fab fa-whatsapp my-float"></i>

                        </a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6 class="text-white fw-bold mb-4">QUICK LINKS</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-decoration-none text-white d-block mb-2">Browse Gear</a>
                        </li>
                        <li><a href="vendor-apply.php" class="text-decoration-none text-white  d-block mb-2">How it
                                Works</a></li>
                        <li><a href="login.html" class="text-decoration-none text-white d-block mb-2">Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="text-white fw-bold mb-4">SUPPORT</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-white d-block mb-2">FAQs</a></li>
                        <li><a href="#" class="text-decoration-none text-white d-block mb-2">Privacy Policy</a></li>
                        <li><a href="#" class="text-decoration-none text-white d-block mb-2">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-white fw-bold mb-4">NEWSLETTER</h6>
                    <div class="input-group">
                        <input type="text" class="form-control bg-dark border-0 text-white" placeholder="Email Address">
                        <button class="btn btn-gold">JOIN</button>
                    </div>
                </div>
            </div>
            <hr class="mt-5 border-secondary opacity-25">
            <p class="text-center small m-0">© <?= date('Y'); ?> RentHubPro. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const rate = <?= (int) $product['price'] ?>;
        const sDate = document.getElementById('start_date');
        const eDate = document.getElementById('end_date');
        const display = document.getElementById('total_display');
        const hiddenInput = document.getElementById('total_price_hidden');

        function updatePrice() {
            if (sDate.value && eDate.value) {
                const start = new Date(sDate.value);
                const end = new Date(eDate.value);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 1;

                const total = diffDays * rate;
                display.innerText = total.toLocaleString();
                hiddenInput.value = total;
            }
        }

        sDate?.addEventListener('change', () => {
            eDate.min = sDate.value;
            updatePrice();
        });
        eDate?.addEventListener('change', updatePrice);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>