<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 1. SESSION SECURITY
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;

    // 2. SEARCH LOGIC
    $search = $_GET['search'] ?? '';
    $filter = [];
    if (!empty($search)) {
        $filter = [
            '$or' => [
                ['transaction_id' => new MongoDB\BSON\Regex($search, 'i')],
                ['account_number' => new MongoDB\BSON\Regex($search, 'i')],
                ['account_holder' => new MongoDB\BSON\Regex($search, 'i')]
            ]
        ];
    }

    // 3. ANALYTICS CALCULATION (Real-time sum of payments)
    $pipeline = [];
    if (!empty($filter)) {
        $pipeline[] = ['$match' => $filter];
    }
    $pipeline[] = ['$group' => ['_id' => null, 'total' => ['$sum' => '$total_paid']]];

    $cursor = $db->payments->aggregate($pipeline);
    $res = $cursor->toArray();
    $total_earnings = !empty($res) ? $res[0]['total'] : 0;

    // 4. CROSS-COLLECTION DATA (Sidebar Related)
    $total_payments = $db->payments->countDocuments($filter);
    $total_users = $db->users->countDocuments(); // Counts from users collection
    $total_properties = $db->properties->countDocuments(); // Counts from properties collection

    // 5. DATA FETCHING (Limit 20 for performance)
    $all_payments = $db->payments->find($filter, ['sort' => ['created_at' => -1], 'limit' => 20]);

} catch (Exception $e) {
    die("<div style='background:#000;color:gold;padding:50px;font-family:sans-serif;'>
            <h2>Critical System Error</h2>
            <p>Connection to MongoDB Failed: " . $e->getMessage() . "</p>
         </div>");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- 1. OS Terminal Title -->
    <title>RentHub OS | Executive Gold Terminal</title>

    <!-- 2. Auto Refresh (30 Seconds - Dashboard ke liye best hai) -->
    <meta http-equiv="refresh" content="30">

    <!-- 3. Executive Favicon -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 4. Modern Fonts & Icon Libraries -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- 5. Data Visualization Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --gold: #D4AF37;
            --gold-glow: rgba(212, 175, 55, 0.3);
            --dark-bg: #09090b;
            --surface: #121215;
            --border: rgba(255, 255, 255, 0.08);
        }

        body {
            background: var(--dark-bg);
            color: #fafafa;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            overflow-x: hidden;
            display: flex;
        }

        /* --- Custom Sidebar --- */
        .sidebar {
            width: 90px;
            background: #000;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 0;
            border-right: 1px solid var(--border);
            z-index: 1000;
        }

        .logo-circle {
            width: 45px;
            height: 45px;
            background: var(--gold);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: black;
            font-weight: 800;
            margin-bottom: 50px;
            box-shadow: 0 0 20px var(--gold-glow);
        }

        .nav-link {
            color: #52525b;
            font-size: 1.5rem;
            margin-bottom: 35px;
            transition: 0.4s;
            cursor: pointer;
            text-decoration: none;
            position: relative;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--gold);
            transform: scale(1.1);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            right: -33px;
            top: 5px;
            height: 25px;
            width: 4px;
            background: var(--gold);
            border-radius: 10px;
        }

        /* --- Main Layout --- */
        .main-content {
            margin-left: 90px;
            width: calc(100% - 90px);
            padding: 40px;
        }

        .glass-card {
            background: var(--surface);
            border-radius: 24px;
            border: 1px solid var(--border);
            padding: 25px;
            transition: 0.3s;
        }

        .glass-card:hover {
            border-color: rgba(212, 175, 55, 0.4);
        }

        .search-box input {
            background: #18181b;
            border: 1px solid var(--border);
            color: white;
            border-radius: 14px;
            padding: 12px 12px 12px 45px;
            width: 100%;
        }

        .text-gold {
            color: var(--gold);
        }

        .badge-status {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bg-jazz {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .bg-easy {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .custom-table thead th {
            background: #18181b;
            color: #71717a;
            border: none;
            padding: 20px;
            font-size: 11px;
            letter-spacing: 1px;
        }

        .custom-table tbody td {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            color: #d4d4d8;
        }

        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #27272a;
            color: #a1a1aa;
            transition: 0.3s;
            text-decoration: none;
        }

        .btn-action:hover {
            background: var(--gold);
            color: black;
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="mb-5 text-gold"><i class="fa-solid fa-crown fs-2"></i></div>
        <a href="view-payments.php" class="nav-link active" title="Dashboard"><i
                class="fa-solid fa-house-chimney"></i></a>
        <a href="analytics.php" class="nav-link" title="Financial Stats"><i class="fa-solid fa-chart-pie"></i></a>
        <a href="manage-users.php" class="nav-link" title="User Management"><i class="fa-solid fa-users-gear"></i></a>
        <a href="properties.php" class="nav-link" title="Property Management"><i
                class="fa-solid fa-building-shield"></i></a>
        <a href="logout.php" class="nav-link" style="margin-top: auto;"
            onclick="return confirm('Exit Secure Terminal?')"><i
                class="fa-solid fa-right-from-bracket text-danger"></i></a>
    </aside>

    <main class="main-content">
        <div class="top-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Command <span class="text-gold">Dashboard</span></h2>
                <p class="text-secondary small" id="live-clock"></p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="search-box position-relative" style="width: 350px;">
                    <form method="GET">
                        <i class="fa-solid fa-magnifying-glass position-absolute"
                            style="left: 18px; top: 16px; color: var(--gold);"></i>
                        <input type="text" name="search" placeholder="Quick Search Ledger..."
                            value="<?= htmlspecialchars($search) ?>">
                    </form>
                </div>
                <div class="glass-card py-2 px-3 border-gold">
                    <span class="text-gold fw-bold small"><i class="fa-solid fa-shield-check"></i> SSL ACTIVE</span>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary fw-bold">TOTAL REVENUE</small>
                            <h3 class="fw-bold mt-1 text-gold">Rs. <?= number_format($total_earnings) ?></h3>
                        </div>
                        <i class="fa-solid fa-wallet fs-2 opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary fw-bold">PLATFORM USERS</small>
                            <h3 class="fw-bold mt-1"><?= $total_users ?></h3>
                        </div>
                        <i class="fa-solid fa-user-group fs-2 opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary fw-bold">PROPERTIES LISTED</small>
                            <h3 class="fw-bold mt-1"><?= $total_properties ?></h3>
                        </div>
                        <i class="fa-solid fa-building fs-2 opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="glass-card h-100">
                    <h6 class="fw-bold m-0 text-secondary mb-4">REVENUE TRENDS (LIVE FEED)</h6>
                    <canvas id="revenueChart" height="120"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="glass-card h-100">
                    <h6 class="text-secondary small fw-bold mb-4">SYSTEM NOTIFICATIONS</h6>
                    <div class="notification-item mb-3 p-2 rounded" style="background: rgba(255,255,255,0.03);">
                        <small class="text-gold fw-bold">NEW PAYMENT</small>
                        <p class="m-0 small text-secondary">A new transaction of Rs. 45,000 just arrived via JazzCash.
                        </p>
                    </div>
                    <div class="notification-item mb-3 p-2 rounded" style="background: rgba(255,255,255,0.03);">
                        <small class="text-info fw-bold">NEW USER</small>
                        <p class="m-0 small text-secondary">A homeowner from DHA Karachi registered a new villa.</p>
                    </div>
                    <a href="logs.php" class="btn btn-sm btn-outline-secondary w-100 rounded-pill mt-auto">View All
                        Activity Logs</a>
                </div>
            </div>
        </div>

        <div class="table-responsive shadow-lg"
            style="background: #0f0f0f; border-radius: 20px; border: 1px solid rgba(212, 175, 55, 0.1);">
            <div
                class="p-4 d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-10">
                <h5 class="fw-bold m-0 text-white"><i class="fa-solid fa-file-invoice-dollar text-gold me-2"></i>
                    Financial Ledger</h5>
                <a href="export-csv.php" class="btn btn-sm btn-gold rounded-pill px-3 fw-bold"
                    style="background: #D4AF37; color: #000; border: none;">Export Master Ledger</a>
            </div>

            <table class="table mb-0 table-dark " style="color: #1a0c0c;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(212, 175, 55, 0.2);">
                        <th class="py-3 px-4" style="color: #D4AF37; font-size: 11px; letter-spacing: 1px;">TIMESTAMP
                        </th>
                        <th class="py-3 px-4" style="color: #D4AF37; font-size: 11px; letter-spacing: 1px;">TRANSACTION
                            ID</th>
                        <th class="py-3 px-4" style="color: #D4AF37; font-size: 11px; letter-spacing: 1px;">ENTITY</th>
                        <th class="py-3 px-4 text-center" style="color: #D4AF37; font-size: 11px; letter-spacing: 1px;">
                            CHANNEL</th>
                        <th class="py-3 px-4 text-end" style="color: #D4AF37; font-size: 11px; letter-spacing: 1px;">
                            AMOUNT</th>
                        <th class="py-3 px-4 text-center" style="color: #D4AF37; font-size: 11px; letter-spacing: 1px;">
                            STATUS</th>
                        <th class="py-3 px-4 text-center" style="color: #D4AF37; font-size: 11px; letter-spacing: 1px;">
                            MANAGE</th>
                    </tr>
                </thead>
                <tbody style="border: none;">
                    <?php foreach ($all_payments as $p): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: 0.3s;"
                            onmouseover="this.style.background='rgba(212,175,55,0.03)'"
                            onmouseout="this.style.background='transparent'">

                            <td class="px-4 py-3 small">
                                <?php
                                $date = $p['created_at']->toDateTime();
                                echo '<div class="text-white fw-bold">' . $date->format('d M, Y') . '</div>';
                                echo '<div class="text-secondary small" style="font-size: 10px;">' . $date->format('h:i A') . '</div>';
                                ?>
                            </td>

                            <td class="px-4 py-3">
                                <code
                                    style="color: #D4AF37; background: rgba(212,175,55,0.1); padding: 4px 8px; border-radius: 5px; font-weight: bold;">
                                                #<?= $p['transaction_id'] ?>
                                            </code>
                            </td>

                            <td class="px-4 py-3">
                                <div class="fw-bold text-white"><?= $p['account_holder'] ?></div>
                                <div class="small text-secondary" style="font-family: monospace;">
                                    <?= $p['account_number'] ?>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php
                                $m = $p['payment_method'];
                                $isJazz = (stripos($m, 'Jazz') !== false);
                                $color = $isJazz ? '#ef4444' : '#22c55e';
                                ?>
                                <span
                                    style="border: 1px solid <?= $color ?>33; background: <?= $color ?>11; color: <?= $color ?>; padding: 4px 12px; border-radius: 6px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                                    <?= $m ?>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-end fw-bold text-white">
                                Rs. <?= number_format($p['total_paid']) ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="small fw-bold" style="color: #22c55e;">
                                    <i class="fa-solid fa-circle-check me-1" style="font-size: 8px;"></i> VERIFIED
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <a href="view-payment.php?id=<?= $p['_id'] ?>" class="me-2"
                                    style="color: #71717a; text-decoration: none;">
                                    <i class="fa-solid fa-eye" onmouseover="this.style.color='#D4AF37'"
                                        onmouseout="this.style.color='#71717a'"></i>
                                </a>
                                <a href="delete-payment.php?id=<?= $p['_id'] ?>"
                                    style="color: #71717a; text-decoration: none;"
                                    onclick="return confirm('Archive this record?')">
                                    <i class="fa-solid fa-trash-can" onmouseover="this.style.color='#ef4444'"
                                        onmouseout="this.style.color='#71717a'"></i>
                                </a>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('live-clock').innerText = now.toLocaleString('en-US', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        }
        setInterval(updateClock, 1000);
        updateClock();

        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Revenue Flow',
                    data: [12000, 19000, 15000, 25000, 22000, 30000, <?= $total_earnings / 5 ?>],
                    borderColor: '#D4AF37',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(212, 175, 55, 0.05)',
                    pointBackgroundColor: '#D4AF37'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#71717a' } },
                    x: { grid: { display: false }, ticks: { color: '#71717a' } }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>