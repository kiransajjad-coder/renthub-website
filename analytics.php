<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Admin Authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\UTCDateTime;

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;

    // --- 1. DYNAMIC RANGE SELECTOR ---
    $range = $_GET['range'] ?? 'day'; 
    $current_time = time();

    switch ($range) {
        case 'year':
            // Pichle 5 saal ka data
            $start_date = strtotime("-5 years");
            $group_format = "%Y"; // e.g., 2024, 2025, 2026
            $chart_label = "Yearly Revenue Tracking";
            break;
        case 'month':
            // Pichle 12 mahine
            $start_date = strtotime("-12 months");
            $group_format = "%b %Y"; // e.g., Jan 2026
            $chart_label = "Monthly Revenue Growth";
            break;
        case 'week':
            // Pichle 12 hafte
            $start_date = strtotime("-12 weeks");
            $group_format = "Week %U"; // e.g., Week 18
            $chart_label = "Weekly Performance";
            break;
        default: // day
            // Pichle 30 din
            $start_date = strtotime("-30 days");
            $group_format = "%d %b"; // e.g., 02 May
            $chart_label = "Daily Revenue Flow";
            break;
    }

    $mongo_start_date = new UTCDateTime($start_date * 1000);

    // --- 2. KPI METRICS (Global Stats) ---
    $kpi = $db->payments->aggregate([
        ['$match' => ['status' => 'Successful']],
        ['$group' => [
            '_id' => null,
            'total_rev' => ['$sum' => '$total_paid'],
            'count' => ['$sum' => 1],
            'avg' => ['$avg' => '$total_paid']
        ]]
    ])->toArray();
    $stats = $kpi[0] ?? ['total_rev' => 0, 'count' => 0, 'avg' => 0];

    // --- 3. TREND AGGREGATION (Real-Time Logic) ---
    $trendData = $db->payments->aggregate([
        [
            '$match' => [
                'status' => 'Successful',
                'created_at' => ['$gte' => $mongo_start_date]
            ]
        ],
        [
            '$group' => [
                '_id' => ['$dateToString' => ['format' => $group_format, 'date' => '$created_at']],
                'amount' => ['$sum' => '$total_paid'],
                'sort_date' => ['$min' => '$created_at']
            ]
        ],
        ['$sort' => ['sort_date' => 1]]
    ])->toArray();

    // --- 4. PAYMENT GATEWAY SHARE ---
    $gateways = $db->payments->aggregate([
        ['$match' => ['status' => 'Successful']],
        ['$group' => ['_id' => '$payment_method', 'val' => ['$sum' => '$total_paid']]],
        ['$sort' => ['val' => -1]]
    ])->toArray();

} catch (Exception $e) {
    die("<div style='background:#000; color:gold; padding:50px; text-align:center;'><h2>Intelligence System Offline</h2><p>".$e->getMessage()."</p></div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. Master Control Title -->
    <title>RentHub Analytics | Master Control Terminal</title>

    <!-- 2. Data Sync (30 Seconds Auto-Refresh) -->
    <meta http-equiv="refresh" content="30">

    <!-- 3. Premium Favicon -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 4. Google Fonts (Plus Jakarta & JetBrains Mono) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">

    <!-- 5. Frameworks & Visualization -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --gold: #D4AF37; --bg: #030303; --panel: #0d0d0d; --border: rgba(212,175,55,0.1); }
        body { background: var(--bg); color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .sidebar { width: 85px; position: fixed; height: 100vh; background: #000; border-right: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; padding: 40px 0; z-index: 100; }
        .nav-link { color: #333; font-size: 1.5rem; margin-bottom: 30px; transition: 0.3s; }
        .nav-link.active, .nav-link:hover { color: var(--gold); }

        .content-area { margin-left: 85px; padding: 45px; }
        .glass-card { background: var(--panel); border: 1px solid var(--border); border-radius: 24px; padding: 30px; height: 100%; transition: 0.3s; }
        .glass-card:hover { border-color: rgba(212,175,55,0.3); }

        .btn-filter-group { background: #000; padding: 5px; border-radius: 12px; border: 1px solid var(--border); }
        .btn-filter { color: #888; border: none; background: transparent; padding: 8px 20px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; text-decoration: none; transition: 0.2s; }
        .btn-filter.active { background: var(--gold); color: #000; }

        .text-gold { color: var(--gold); }
        .kpi-val { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; }
    </style>
</head>
<body>

<aside class="sidebar">
  <a href="view-payments.php" class="nav-link active" title="Dashboard"><i class="fa-solid fa-house-chimney"></i></a>
    <a href="analytics.php" class="nav-link" title="Financial Stats"><i class="fa-solid fa-chart-pie"></i></a>
    <a href="manage-users.php" class="nav-link" title="User Management"><i class="fa-solid fa-users-gear"></i></a>
    <a href="properties.php" class="nav-link" title="Property Management"><i class="fa-solid fa-building-shield"></i></a>
    <a href="logout.php" class="nav-link" style="margin-top: auto;" onclick="return confirm('Exit Secure Terminal?')"><i class="fa-solid fa-right-from-bracket text-danger"></i></a>
</aside>

<div class="content-area">
    <header class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <span class="text-gold fw-bold small text-uppercase" style="letter-spacing: 2px;">Market Intelligence</span>
            <h1 class="fw-bold m-0">Revenue <span class="text-gold">Analytics</span></h1>
        </div>
        <div class="btn-filter-group">
            <a href="?range=day" class="btn-filter <?= $range=='day'?'active':'' ?>">Day</a>
            <a href="?range=week" class="btn-filter <?= $range=='week'?'active':'' ?>">Week</a>
            <a href="?range=month" class="btn-filter <?= $range=='month'?'active':'' ?>">Month</a>
            <a href="?range=year" class="btn-filter <?= $range=='year'?'active':'' ?>">Year</a>
        </div>
    </header>

    <!-- KPI Metrics -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="glass-card">
                <p class="text-secondary small fw-bold mb-1">TOTAL VOLUME</p>
                <div class="kpi-val text-gold">Rs. <?= number_format($stats['total_rev']) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card">
                <p class="text-secondary small fw-bold mb-1">AVERAGE ORDER</p>
                <div class="kpi-val">Rs. <?= number_format($stats['avg'], 2) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card">
                <p class="text-secondary small fw-bold mb-1">SETTLED ORDERS</p>
                <div class="kpi-val"><?= $stats['count'] ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Line Chart -->
        <div class="col-lg-8">
            <div class="glass-card">
                <h5 class="fw-bold mb-4"><?= $chart_label ?></h5>
                <canvas id="mainTrendChart"></canvas>
            </div>
        </div>

        <!-- Donut Chart -->
        <div class="col-lg-4">
            <div class="glass-card">
                <h5 class="fw-bold mb-4 text-center">Payment Sources</h5>
                <canvas id="gatewayChart"></canvas>
                <div class="mt-4">
                    <?php foreach($gateways as $g): ?>
                        <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-10 small">
                            <span class="text-secondary"><?= $g['_id'] ?></span>
                            <span class="fw-bold">Rs. <?= number_format($g['val']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Config for Main Trend Chart
    const trendCtx = document.getElementById('mainTrendChart').getContext('2d');
    const grad = trendCtx.createLinearGradient(0, 0, 0, 400);
    grad.addColorStop(0, 'rgba(212, 175, 55, 0.25)');
    grad.addColorStop(1, 'rgba(0, 0, 0, 0)');

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($trendData, '_id')) ?>,
            datasets: [{
                label: 'Gross Revenue',
                data: <?= json_encode(array_column($trendData, 'amount')) ?>,
                borderColor: '#D4AF37',
                borderWidth: 4,
                backgroundColor: grad,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#D4AF37',
                hoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#666', font: { size: 11 } } },
                x: { grid: { display: false }, ticks: { color: '#666', font: { size: 11 } } }
            }
        }
    });

    // Config for Donut Chart
    const gateCtx = document.getElementById('gatewayChart').getContext('2d');
    new Chart(gateCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($gateways, '_id')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($gateways, 'val')) ?>,
                backgroundColor: ['#D4AF37', '#1a1a1a', '#333', '#555'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            cutout: '80%',
            plugins: { legend: { display: false } }
        }
    });
</script>

</body>
</html>