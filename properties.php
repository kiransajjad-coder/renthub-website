<?php
/**
 * RentHub OS | Executive Inventory Command Center
 * Integration: Live Payment Sync (Products <-> Payments)
 * Database: renthub_db
 */

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// Security: Session Validation
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

try {
    // Database Connection Engine
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;
    $collection = $db->products;
    $today = date('Y-m-d'); // Current Date for logic

    // --- State Management: Filters & Search ---
    $activeCategory = $_GET['category'] ?? 'All Items';
    $searchKey = htmlspecialchars($_GET['search'] ?? '');

    // --- High-Performance Aggregation Pipeline ---
    $pipeline = [];

    // 1. Initial Filter
    $match = [
        'name' => ['$exists' => true, '$ne' => "", '$nin' => ["Untitled Asset", "null"]]
    ];
    if ($activeCategory !== 'All Items') {
        $match['category'] = $activeCategory;
    }
    if (!empty($searchKey)) {
        $match['name'] = new MongoDB\BSON\Regex($searchKey, 'i');
    }
    $pipeline[] = ['$match' => $match];

    // 2. Optimized Lookup: Sirf wahi payments uthayein jo 'Successful' hain
    $pipeline[] = [
        '$lookup' => [
            'from' => 'payments',
            'let' => ['p_id' => '$_id'],
            'pipeline' => [
                ['$match' => [
                    '$expr' => [
                        '$and' => [
                            ['$eq' => ['$product_id', '$$p_id']],
                            ['$eq' => ['$status', 'Successful']]
                        ]
                    ]
                ]]
            ],
            'as' => 'sales_history'
        ]
    ];

    // 3. Add Calculated Field: live_sold_count (Sirf current active bookings filter karein)
    // Hum PHP mein loop ke bajaye pipeline mein hi logic handle kar rahe hain
    $pipeline[] = [
        '$addFields' => [
            'live_sold_count' => [
                '$size' => [
                    '$filter' => [
                        'input' => '$sales_history',
                        'as' => 'sale',
                        'cond' => [
                            '$gte' => [
                                ['$ifNull' => ['$$sale.last_day', '9999-12-31']], // Agar date missing ho to future date
                                $today
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    // 4. Sort: Show available items first
    $pipeline[] = ['$sort' => ['status' => 1, '_id' => -1]];

    // Execute Main Query
    $products = $collection->aggregate($pipeline)->toArray();

    // --- Metrics Calculation ---
    $totalCount = count($products);
    $liveCount = 0;
    foreach ($products as $p) {
        $stock = (int)($p['quantity'] ?? 0) - (int)($p['live_sold_count'] ?? 0);
        if ($stock > 0) $liveCount++;
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    die("<div style='height:100vh; display:flex; align-items:center; justify-content:center; background:#000; color:#ff4d4d; font-family:sans-serif;'>
            <div style='text-align:center;'>
                <h2 style='font-weight:800;'>System Offline</h2>
                <p>Database engine failed to respond or collections missing.</p>
            </div>
         </div>");
}

function safePrice($val) { return number_format((float)($val ?? 0), 0); }

function resolveImg($p) {
    if (!empty($p['image'])) return $p['image'];
    if (!empty($p['image_url'])) return $p['image_url'];
    return 'https://images.unsplash.com/photo-1542156822-6924d1a71ace?q=80&w=600&auto=format&fit=crop';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Inventory | RentHub OS</title>
    <meta http-equiv="refresh" content="5">
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { 
            --gold: #D4AF37; 
            --gold-glow: rgba(212, 175, 55, 0.4);
            --dark: #000000; 
            --surface: #111111;
            --border: rgba(255, 255, 255, 0.08); 
            --red: #ff4d4d;
            --green: #22c55e;
        }

        body { background: var(--dark); color: #ffffff; font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.01em; }
        .sidebar { width: 90px; position: fixed; height: 100vh; background: rgba(0,0,0,0.8); backdrop-filter: blur(15px); border-right: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; padding: 40px 0; z-index: 1000; }
        .nav-item { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 15px; color: #444; font-size: 1.4rem; margin-bottom: 25px; transition: 0.4s; text-decoration: none; }
        .nav-item:hover, .nav-item.active { background: var(--gold); color: #000; box-shadow: 0 0 20px var(--gold-glow); }
        .main-content { margin-left: 90px; padding: 50px 70px; }
        .header-title { font-size: 2.8rem; font-weight: 800; letter-spacing: -1px; }
        .metric-card { background: var(--surface); border: 1px solid var(--border); border-radius: 24px; padding: 20px 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .cat-chip { background: #181818; border: 1px solid var(--border); color: #888; padding: 10px 13px; border-radius: 20px; font-weight: 600; font-size: 13px; text-decoration: none; transition: 0.3s; white-space: nowrap; }
        .cat-chip:hover, .cat-chip.active { background: var(--gold); color: #000; border-color: var(--gold); transform: translateY(-1px); }
        .asset-card { background: var(--surface); border-radius: 24px; overflow: hidden; border: 1px solid var(--border); transition: 0.4s; position: relative; }
        .asset-card:hover { border-color: var(--gold); box-shadow: 0 15px 35px rgba(0,0,0,0.6); }
        .is-sold-out { opacity: 0.7; filter: grayscale(0.9); }
        .img-container { height: 180px; position: relative; overflow: hidden; background: #080808; display: flex; align-items: center; justify-content: center; }
        .asset-img { width: 100%; height: 100%; object-fit: cover; }
        .status-badge { position: absolute; top: 12px; right: 12px; font-size: 10px; font-weight: 800; padding: 5px 12px; border-radius: 8px; text-transform: uppercase; z-index: 5; }
        .badge-active { background: rgba(34, 197, 94, 0.2); color: var(--green); border: 1px solid var(--green); }
        .badge-sold-out { background: var(--red); color: #fff; box-shadow: 0 0 15px rgba(255, 77, 77, 0.4); }
        .info-label { color: #666; font-size: 11px; font-weight: 700; text-transform: uppercase; margin: 0; }
        .info-value { color: #fff; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .btn-action-sm { flex: 1; padding: 10px; border-radius: 12px; font-size: 12px; font-weight: 700; text-decoration: none; text-align: center; transition: 0.3s; border: 1px solid var(--border); background: #181818; color: #fff; }
        .btn-action-sm:hover { background: var(--gold); color: #000; border-color: var(--gold); }
        .btn-delete:hover { background: var(--red); color: #fff; border-color: var(--red); }
        .search-box { background: #111; border: 1px solid var(--border); border-radius: 20px; padding: 12px 25px; width: 350px; color: white; transition: 0.3s; }
        .search-box:focus { outline: none; border-color: var(--gold); }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="mb-5" style="color: var(--gold);"><i class="fa-solid fa-crown fs-2"></i></div>
    <a href="view-payments.php" class="nav-item" title="Home"><i class="fa-solid fa-house-chimney"></i></a>
    <a href="properties.php" class="nav-item active" title="Inventory"><i class="fa-solid fa-boxes-stacked"></i></a>
    <a href="manage-users.php" class="nav-item" title="Users"><i class="fa-solid fa-users-gear"></i></a>
    <a href="logout.php" class="nav-item" style="margin-top: auto;" title="Exit"><i class="fa-solid fa-power-off text-danger"></i></a>
</nav>

<main class="main-content">
    <header class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h6 class="text-gold fw-bold mb-1" style="letter-spacing: 5px; opacity: 0.8;">RENTAL FLEET</h6>
            <h1 class="header-title m-0">Asset <span class="text-gold">Command</span></h1>
        </div>
        <div class="d-flex gap-4">
            <div class="metric-card d-none d-lg-block text-center">
                <small class="text-muted fw-bold d-block mb-1" style="font-size: 10px; letter-spacing: 1.5px;">IN STOCK</small>
                <h4 class="m-0 fw-800 text-white"><?= $liveCount ?> <span class="text-muted" style="font-size: 14px;">/ <?= $totalCount ?></span></h4>
            </div>
            <a href="add-product.php" class="btn py-3 px-4 rounded-4 fw-800 d-flex align-items-center" style="background: var(--gold); color: #000; border: none; font-size: 14px;">
                <i class="fa-solid fa-plus-circle me-2 fs-5"></i> CREATE ASSET
            </a>
        </div>
    </header>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div class="filter-scroller d-flex gap-2">
            <?php 
            $categories = ["All Items", "Cameras", "Dresses", "Drones", "Decor", "Projectors", "Sound"];
            foreach($categories as $c): ?>
                <a href="?category=<?= urlencode($c) ?>" class="cat-chip <?= $activeCategory == $c ? 'active' : '' ?>">
                    <?= $c ?>
                </a>
            <?php endforeach; ?>
        </div>
        <form class="position-relative" method="GET">
            <i class="fa fa-search position-absolute text-muted" style="top: 17px; left: 20px;"></i>
            <input type="text" name="search" class="search-box" style="padding-left: 50px;" placeholder="Search asset name..." value="<?= $searchKey ?>">
        </form>
    </div>

    <div class="row g-4">
        <?php foreach ($products as $p): 
            $pid = (string)$p['_id'];
            $originalStock = (int)($p['quantity'] ?? 0); 
            $soldStock = (int)($p['live_sold_count'] ?? 0); 
            $availableStock = $originalStock - $soldStock;
            $isSoldOut = ($availableStock <= 0);
        ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="asset-card <?= $isSoldOut ? 'is-sold-out' : '' ?>">
                <div class="img-container">
                    <img src="<?= resolveImg($p) ?>" class="asset-img" alt="Product">
                    <?php if($isSoldOut): ?>
                        <span class="status-badge badge-sold-out">Rented Out</span>
                    <?php else: ?>
                        <span class="status-badge badge-active">Available</span>
                    <?php endif; ?>
                </div>
                
                <div class="p-4">
                    <p class="info-label">Product Name</p>
                    <h6 class="info-value text-truncate" title="<?= htmlspecialchars($p['name']) ?>">
                        <?= htmlspecialchars($p['name']) ?> 
                        <span class="text-muted" style="font-size: 10px;">(ID: #<?= substr($pid, -5) ?>)</span>
                    </h6>

                    <div class="row g-0 mb-3">
                        <div class="col-6">
                            <p class="info-label">Price</p>
                            <p class="info-value text-gold" style="font-size: 13px;">Rs. <?= safePrice($p['price'] ?? 0) ?></p>
                        </div>
                        <div class="col-6 text-end">
                            <p class="info-label">Current Stock</p>
                            <p class="info-value <?= $isSoldOut ? 'text-danger' : 'text-green' ?>" style="font-size: 13px;">
                                <?= max(0, $availableStock) ?> / <?= $originalStock ?>
                            </p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="info-label" style="font-size: 9px;">Usage Status</span>
                            <span class="info-label text-white" style="font-size: 9px;"><?= $soldStock ?> Busy</span>
                        </div>
                        <div style="height: 4px; width: 100%; background: #222; border-radius: 10px; overflow: hidden; display: flex;">
                            <?php $soldPercent = ($originalStock > 0) ? ($soldStock / $originalStock) * 100 : 0; ?>
                            <div style="width: <?= min(100, $soldPercent) ?>%; background: var(--red); height: 100%;"></div>
                        </div>
                    </div>

                    <p class="info-label">Vendor</p>
                    <p class="info-value" style="font-size: 13px;">
                        <i class="fa-solid fa-store me-1 text-muted"></i> <?= htmlspecialchars($p['vendor_name'] ?? 'Admin Stock') ?>
                    </p>

                    <hr style="border-color: var(--border); margin: 15px 0;">

                    <div class="d-flex gap-2">
                        <a href="edit-product.php?id=<?= $pid ?>" class="btn-action-sm">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                        </a>
                        <a href="delete-product.php?id=<?= $pid ?>" class="btn-action-sm btn-delete" onclick="return confirm('Delete this asset?')">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>