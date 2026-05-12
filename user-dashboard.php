<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../vendor/autoload.php';
use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;
    $userId = $_SESSION['user_id'];
    $message = "";

    // --- 1. Profile Update Logic ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_profile'])) {
        $updateData = [
            'name' => htmlspecialchars($_POST['full_name']),
            'phone' => htmlspecialchars($_POST['phone_number']),
            'address' => htmlspecialchars($_POST['address'] ?? ''),
            'dob' => $_POST['dob'] ?? '',
            'gender' => $_POST['gender'] ?? ''
        ];
        if (!empty($_FILES['avatar']['name'])) {
            $targetDir = "uploads/profiles/";
            if (!is_dir($targetDir))
                mkdir($targetDir, 0777, true);
            $targetFilePath = $targetDir . $userId . "_" . time() . "_" . basename($_FILES["avatar"]["name"]);
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFilePath)) {
                $updateData['profile_pic'] = $targetFilePath;
            }
        }
        $db->users->updateOne(['_id' => new ObjectId($userId)], ['$set' => $updateData]);
        $message = "Success! Your profile settings have been synchronized.";
    }

    // --- 2. Data Fetching (User & Payments) ---
    $user = $db->users->findOne(['_id' => new ObjectId($userId)]);

    $pipeline = [
        ['$match' => ['user_id' => new ObjectId($userId)]],
        ['$sort' => ['created_at' => -1]],
        [
            '$lookup' => [
                'from' => 'products',
                'localField' => 'product_id',
                'foreignField' => '_id',
                'as' => 'product_details'
            ]
        ]
    ];

    $user_payments_cursor = $db->payments->aggregate($pipeline);
    $user_payments = iterator_to_array($user_payments_cursor);

    // --- 3. Analysis & Stats Variables ---
    $total_spent = 0;
    $completed = 0;
    $calendar_events = [];
    $category_counts = []; // Category-wise spending
    $weekly_spending = [0, 0, 0, 0]; // Simulating 4 weeks for chart

    foreach ($user_payments as $pay) {
        $amount = (float) ($pay['total_paid'] ?? 0);
        $total_spent += $amount;

        $status = $pay['status'] ?? '';
        if ($status == 'Successful' || $status == 'completed')
            $completed++;

        // Category Analysis Logic
        $cat = $pay['product_details'][0]['category'] ?? 'Others';
        if (!isset($category_counts[$cat]))
            $category_counts[$cat] = 0;
        $category_counts[$cat] += $amount;

        // Calendar Logic (Yellow/Red)
        $prodName = $pay['product_details'][0]['name'] ?? 'Rental Item';
        $period_string = $pay['rental_period'] ?? '';

        if (!empty($period_string) && strpos($period_string, ' To ') !== false) {
            $dates = explode(' To ', $period_string);
            $pickDate = trim($dates[0]);
            $returnDate = trim($dates[1]);

            $calendar_events[] = [
                'title' => '📦 Pick: ' . $prodName,
                'start' => $pickDate,
                'backgroundColor' => '#ffc107',
                'borderColor' => '#ffc107',
                'textColor' => '#000'
            ];
            $calendar_events[] = [
                'title' => '🔙 Return: ' . $prodName,
                'start' => $returnDate,
                'backgroundColor' => '#dc3545',
                'borderColor' => '#dc3545',
                'textColor' => '#fff'
            ];
        }
    }

    // Chart Data Preparation
    $catLabels = array_keys($category_counts);
    $catValues = array_values($category_counts);

    $active_rentals = count($user_payments) - $completed;
    $avatar_url = !empty($user['profile_pic']) ? $user['profile_pic'] : "https://ui-avatars.com/api/?name=" . urlencode($user['name'] ?? 'User');

} catch (Exception $e) {
    die("Critical System Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>RentHub Pro | Executive Dashboard</title>

    <!-- 1. Performance: Preconnect to Font domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- 2. Auto Refresh (Useful for Real-time Monitoring) -->
    <meta http-equiv="refresh" content="300"> <!-- Set to 300 for 5 mins; 5s can be aggressive for UX -->

    <!-- 3. Primary Meta Tags (SEO & Brand) -->
    <meta name="description" content="Manage your premium rentals with RentHub Pro. Real-time analytics, inventory tracking, and client concierge.">
    <meta name="author" content="RentHub Executive Team">

    <!-- 4. Modern Favicon -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 5. Stylesheets (Frameworks First) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- 6. Google Font (Plus Jakarta Sans) -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- 7. Heavy Scripts (Deffered for Speed) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <style>
        :root {
            --bg: #050505;
            --card: #111111;
            --gold: #ffc107;
            --border: rgba(194, 165, 165, 0.08);
            --muted: #888888;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: #ffffff;
            scroll-behavior: smooth;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            background: #000;
            border-right: 1px solid var(--border);
            padding: 2rem 1.2rem;
            z-index: 1000;
        }

        .main-content {
            margin-left: 280px;
            padding: 40px;
            min-height: 100vh;
        }

        .nav-link {
            color: var(--muted);
            padding: 14px 18px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: 0.3s;
            margin-bottom: 5px;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 193, 7, 0.1);
            color: var(--gold);
            border-color: rgba(255, 193, 7, 0.2);
        }

        .nav-link i {
            width: 25px;
            font-size: 1.1rem;
        }

        /* Cards */
        .glass-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .stat-card h5 {
            color: #ffffff;
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .stat-card small {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* Tabs Animation */
        .dashboard-section {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .dashboard-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom UI */
        .avatar-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            object-fit: cover;
        }

        .profile-hero-img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 4px solid var(--gold);
            object-fit: cover;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .form-control,
        .form-select {
            background: #181818;
            border: 1px solid var(--border);
            color: #fff;
            padding: 12px;
            border-radius: 12px;
        }

        .form-control:focus {
            background: #222;
            border-color: var(--gold);
            color: #fff;
            box-shadow: none;
        }

        .btn-warning {
            background: var(--gold);
            border: none;
            font-weight: 800;
            color: #000;
            border-radius: 12px;
        }

        .badge-verified {
            background: rgba(0, 255, 127, 0.1);
            color: #00ff7f;
            border: 1px solid rgba(0, 255, 127, 0.2);
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.7rem;
        }

        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            background: #000;
            border-right: 1px solid var(--border-glass);
            padding: 2rem 1rem;
            /* Padding thori adjust ki hai scroll ke liye */
            z-index: 1000;

            /* SCROLL LOGIC */
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Custom Scrollbar for Sidebar (Webkit browsers) */
        .sidebar::-webkit-scrollbar {
            width: 4px;
            /* Bilkul bareek scrollbar */
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 193, 7, 0.2);
            /* Gold tint color */
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
            /* Hover par thora bright ho jaye */
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <!-- Header stays at top -->
        <div class="px-3 mb-5">
            <h2 class="fw-800 text-warning m-0">RENTHUB.</h2>
        </div>

        <!-- Navigation links that will scroll -->
        <nav class="flex-grow-1">
            <div class="px-3 mb-2">
                <small class="text-uppercase fw-bold text-muted" style="font-size: 0.65rem; letter-spacing: 1px;">Main
                    Menu</small>
            </div>
            <a class="nav-link active" onclick="showTab('overview', this)"><i class="fa-solid fa-house"></i>
                Overview</a>
            <a class="nav-link" onclick="showTab('rentals', this)"><i class="fa-solid fa-box"></i> My Bookings</a>
            <a class="nav-link" onclick="showTab('browse', this)"><i class="fa-solid fa-compass"></i> Browse</a>
            <a class="nav-link" onclick="showTab('transactions', this)"><i class="fa-solid fa-receipt"></i>
                Transactions</a>
            <a class="nav-link" onclick="showTab('analysis', this)"><i class="fa-solid fa-chart-line"></i> Analysis</a>
            <a class="nav-link" onclick="showTab('calendar-sec', this)"><i class="fa-solid fa-calendar"></i>
                Schedule</a>

            <hr class="border-secondary opacity-25 mx-3 my-4">

            <div class="px-3 mb-2">
                <small class="text-uppercase fw-bold text-muted"
                    style="font-size: 0.65rem; letter-spacing: 1px;">Account Settings</small>
            </div>
            <a class="nav-link" onclick="showTab('profile-sec', this)"><i class="fa-solid fa-user-circle"></i>
                Profile</a>
            <a class="nav-link" onclick="showTab('settings-sec', this)"><i class="fa-solid fa-sliders"></i> Settings</a>

            <!-- Push Logout to the bottom or let it scroll with links -->
            <div class="mt-4 pb-4">
                <a href="logout.php" class="nav-link text-danger"><i class="fa-solid fa-power-off"></i> Logout</a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-800 text-warning mb-1">SYSTEM ONLINE
                    <span class=" fw-400" style="font-size: 1.2rem;"></span>
                </h2>
                <p class="small">Welcome back, <?= $user['name'] ?></p>
            </div>
            <div class="d-flex align-items-center gap-3 glass-card py-2 px-4 mb-0"
                style="background: rgba(255,255,255,0.03);">
                <div class="text-end d-none d-md-block">
                    <p class="mb-0 fw-bold text-warning small">GOLD MEMBER</p>
                    <p class="mb-0 x-small ">ID: #<?= substr($userId, -5) ?></p>
                </div>
                <img src="<?= $avatar_url ?>" class="avatar-circle">
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-warning border-0 fw-bold rounded-4 py-3 mb-4"><i
                    class="fa-solid fa-circle-check me-2"></i> <?= $message ?></div>
        <?php endif; ?>

        <div id="overview" class="dashboard-section active">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="glass-card stat-card">
                        <h5>Rs. <?= number_format($total_spent) ?></h5><small>Total Expense</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card stat-card">
                        <h5 class="text-warning"><?= $active_rentals ?></h5><small>Running Bookings</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card stat-card">
                        <h5><?= count($user_payments) ?></h5><small>Total Orders</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card stat-card">
                        <h5 class="text-info">4.9 / 5</h5><small>User Rating</small>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-lg-8">
                    <div class="glass-card h-100">
                        <h5 class="fw-bold text-white mb-4">Financial Growth</h5>
                        <canvas id="spendingChart" height="150"></canvas>
                    </div>
                </div>
              <div class="col-lg-4">
    <div class="glass-card h-100" style="background: rgba(255,255,255,0.02); border-radius: 20px; padding: 15px; border: 1px solid rgba(255,255,255,0.05);">
        <h6 class="fw-bold text-white mb-3" style="font-size: 14px;">Notifications</h6>

        <!-- SECTION 1: COMPACT RETURN LIST -->
        <div class="mb-3">
            <p class="text-warning fw-bold mb-2" style="font-size: 9px; letter-spacing: 1px; opacity: 0.8;">RETURN SCHEDULE</p>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <?php foreach ($user_payments as $pay): 
                    $dates = explode(' To ', $pay['rental_period'] ?? '');
                    $returnDate = isset($dates[1]) ? trim($dates[1]) : 'N/A';
                ?>
                <div class="d-flex justify-content-between align-items-center p-2" style="background: rgba(255,255,255,0.03); border-radius: 8px; border-left: 3px solid #ffc107;">
                    <span class="text-white text-truncate me-2" style="font-size: 11px; max-width: 120px;"><?= $pay['product_details'][0]['name'] ?? 'Item' ?></span>
                    <span class="text-warning fw-bold" style="font-size: 10px;"><?= $returnDate ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 0;">

        <!-- SECTION 2: COMPACT MILESTONE -->
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="p-1 bg-info rounded-2"><i class="fa-solid fa-award text-dark" style="font-size: 10px;"></i></div>
            <p class="mb-0 fw-bold text-info" style="font-size: 11px;">Milestone: <?= count($user_payments) ?> Rentals</p>
        </div>
        
        <!-- COMPACT PRODUCT CHIPS -->
        <div class="d-flex flex-wrap gap-1">
            <?php foreach ($user_payments as $p): ?>
                <span style="font-size: 9px; background: rgba(13,202,240,0.1); color: #0dcaf0; padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(13,202,240,0.2);">
                    <?= $p['product_details'][0]['name'] ?? 'Item' ?>
                </span>
            <?php endforeach; ?>
        </div>

    </div>
</div>
            </div>
        </div>
        <div id="rentals" class="dashboard-section mt-5">
            <!-- Glassmorphism Container -->
            <div class="glass-card"
                style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; backdrop-filter: blur(15px); overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">

                <!-- Header -->
                <div
                    class="p-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                    <h4 class="fw-800 text-white m-0" style="letter-spacing: -0.5px;">
                        <i class="fa-solid fa-box-open text-warning me-2"></i> Active Bookings
                    </h4>
                    <span class="badge bg-warning text-dark fw-bold rounded-pill px-3 py-2"
                        style="font-size: 0.7rem;">LIVE INVENTORY</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0"
                        style="--bs-table-bg: transparent; --bs-table-hover-bg: rgba(255,255,255,0.04);">
                        <thead>
                            <tr class="text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px; color: #8e8e93;">
                                <th class="ps-4 py-2 border-0">Order ID</th>
                                <th class="border-0">Product Asset</th>
                                <th class="border-0">Rental Timeline</th>
                                <th class="border-0 text-center">Security</th>
                                <th class="border-0">Total Amount</th>
                                <th class="pe-4 border-0 text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-white border-top-0">
                            <?php foreach ($user_payments as $p):
                                $prodInfo = $p['product_details'][0] ?? null;
                                $prodImg = !empty($prodInfo['image']) ? $prodInfo['image'] : 'https://via.placeholder.com/100';
                                $prodName = $prodInfo['name'] ?? 'Premium Item';
                                ?>
                                <tr class="rental-row" style="transition: transform 0.2s ease;">
                                    <!-- Order ID -->
                                    <td class="ps-4">
                                        <span
                                            class="font-monospace text-white-50 small">#RH-<?= substr((string) $p['_id'], -6) ?></span>
                                    </td>

                                    <!-- PRODUCT IMAGE & NAME -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="position-relative me-3">
                                                <img src="<?= $prodImg ?>"
                                                    style="width: 55px; height: 55px; border-radius: 12px; object-fit: cover; border: 2px solid rgba(255,193,7,0.3);">
                                            </div>
                                            <div>
                                                <div class="fw-bold text-white mb-0" style="font-size: 0.95rem;">
                                                    <?= htmlspecialchars($prodName) ?>
                                                </div>
                                                <div class="text-muted" style="font-size: 0.75rem; opacity: 0.7;">Equipment
                                                    Ref: <?= substr($p['product_id'], -4) ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- RENTAL PERIOD -->
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-warning-emphasis small fw-600">
                                                <i class="fa-regular fa-clock me-1"></i> Period
                                            </span>
                                            <span
                                                class="small opacity-75"><?= htmlspecialchars($p['rental_period'] ?? 'N/A') ?></span>
                                        </div>
                                    </td>

                                    <!-- SECURITY -->
                                    <td class="text-center">
                                        <span class="badge bg-secondary bg-opacity-25 text-white-50 fw-normal">Rs.
                                            5,000</span>
                                    </td>

                                    <!-- TOTAL PAID -->
                                    <td>
                                        <div class="fw-800 text-white">
                                            <span
                                                class="text-warning small me-1">PKR</span><?= number_format($p['total_paid'] ?? 0) ?>
                                        </div>
                                    </td>

                                    <!-- STATUS -->
                                    <td class="pe-4 text-end">
                                        <div
                                            class="d-inline-flex align-items-center bg-success bg-opacity-10 border border-success border-opacity-25 rounded-pill px-3 py-1">
                                            <span class="status-dot me-2"></span>
                                            <span class="text-success fw-bold text-uppercase"
                                                style="font-size: 0.65rem;">Active</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Note -->
                <div class="p-3 bg-white bg-opacity-5 text-center">
                    <p class="text-muted small m-0">Need help with an order? <a href="#"
                            class="text-warning text-decoration-none">Contact Support</a></p>
                </div>
            </div>
        </div>



        <div id="browse" class="dashboard-section <?= isset($_GET['search_text']) ? 'active' : '' ?>">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-800 text-white mb-1">Marketplace</h4>
                    <p class="small text-muted">Premium equipment for your next big event.</p>
                </div>
                <form action="?#browse" method="GET" style="width: 300px;" class="d-flex gap-2">
                    <input type="text" name="search_text" class="form-control" placeholder="Search items..."
                        value="<?= htmlspecialchars($_GET['search_text'] ?? '') ?>">
                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <div class="row g-4">
                <?php
                // 1. Prepare Filter
                $productFilter = [];
                if (!empty($_GET['search_text'])) {
                    $productFilter['name'] = new MongoDB\BSON\Regex($_GET['search_text'], 'i');
                }

                // 2. Fetch data (Using the $db connection already established at the top of your script)
                $products = $db->products->find($productFilter, ['limit' => 12]);
                $foundCount = 0;

                foreach ($products as $item):
                    $foundCount++;
                    ?>
                    <div class="col-md-4">
                        <div class="glass-card p-0 border-0 h-100 shadow-sm overflow-hidden" style="background: #151515;">
                            <div class="position-relative">
                                <img src="<?= $item['image'] ?? 'https://via.placeholder.com/600x400?text=No+Image' ?>"
                                    class="w-100" style="height: 200px; object-fit: cover;">
                                <span class="badge bg-dark position-absolute top-0 end-0 m-3 opacity-75">
                                    <?= $item['category'] ?? 'General' ?>
                                </span>
                            </div>

                            <div class="p-4">
                                <small class="text-warning fw-bold text-uppercase"
                                    style="font-size: 0.7rem; letter-spacing: 1px;">Starting from</small>
                                <h6 class="text-white fw-800 mb-1 mt-1"><?= $item['name'] ?? 'Unnamed Product' ?></h6>
                                <p class="small text-muted mb-3"><?= $item['category'] ?? 'Rental' ?> / Premium Grade</p>

                                <div
                                    class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-25">
                                    <div>
                                        <span class="text-white fw-800 fs-5">Rs.
                                            <?= number_format((int) ($item['price'] ?? 0)) ?></span>
                                        <small class="text-muted">/day</small>
                                    </div>
                                    <a href="product-details.php?id=<?= $item['_id'] ?>"
                                        class="btn btn-sm btn-warning  rounded-pill shadow-sm">
                                        Rent Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($foundCount === 0): ?>
                    <div class="col-12 text-center py-5">
                        <div class="p-5 glass-card border-dashed">
                            <i class="fa-solid fa-box-open fa-3x mb-3"></i>
                            <p class="">No items found matching
                                "<strong><?= htmlspecialchars($_GET['search_text']) ?></strong>".</p>
                            <a href="?#browse" class="btn btn-outline-warning btn-sm">Clear Search</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="transactions" class="dashboard-section mt-5">
            <div class="glass-card"
                style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; backdrop-filter: blur(15px); overflow: hidden;">

                <!-- Header -->
                <div
                    class="p-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                    <h4 class="fw-800 text-white m-0">
                        <i class="fa-solid fa-file-invoice-dollar text-warning me-2"></i> Payment History
                    </h4>
                    <div class="small text-muted">Recent Transactions</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0" style="--bs-table-bg: transparent;">
                        <thead>
                            <tr class="text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px; color: #8e8e93;">
                                <th class="ps-4 py-2 border-0">Reference ID</th>
                                <th class="border-0">Method</th>
                                <th class="border-0">Date</th>
                                <th class="border-0">Base Amount</th>
                                <th class="border-0 text-center">Platform Fee</th>
                                <th class="border-0">Grand Total</th>
                                <th class="pe-4 border-0 text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-white border-top-0">
                            <?php foreach ($user_payments as $pay):
                                // Payment Method Logic
                                $method = strtolower($pay['payment_method'] ?? 'cash');
                                $methodBadge = '';

                                if ($method == 'jazzcash') {
                                    $methodBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1" style="font-size: 10px;">
                                            <i class="fa-solid fa-mobile-screen-button me-1"></i> JazzCash</span>';
                                } elseif ($method == 'easypaisa') {
                                    $methodBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1" style="font-size: 10px;">
                                            <i class="fa-solid fa-wallet me-1"></i> EasyPaisa</span>';
                                } else {
                                    $methodBadge = '<span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25 px-2 py-1" style="font-size: 10px;">Other</span>';
                                }
                                ?>
                                <tr class="txn-row">
                                    <!-- Ref ID -->
                                    <td class="ps-4">
                                        <span
                                            class="font-monospace text-warning small">#TXN-<?= strtoupper(substr((string) $pay['_id'], -6)) ?></span>
                                    </td>

                                    <!-- Payment Method (New Column) -->
                                    <td>
                                        <?= $methodBadge ?>
                                    </td>

                                    <!-- Date -->
                                    <td class="small text-white-50">
                                        <?= date('d M, Y', strtotime($pay['created_at'] ?? 'now')) ?>
                                    </td>

                                    <!-- Amount -->
                                    <td class="small">Rs. <?= number_format($pay['total_paid'] ?? 0) ?></td>

                                    <!-- Tax/Fee -->
                                    <td class="text-center">
                                        <span class="small text-white-50">Rs. 150</span>
                                    </td>

                                    <!-- Total -->
                                    <td>
                                        <div class="fw-bold text-white">Rs.
                                            <?= number_format(($pay['total_paid'] ?? 0) + 150) ?>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="pe-4 text-end">
                                        <span class="badge-verified"
                                            style="background: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.2); padding: 4px 12px; border-radius: 50px; font-size: 10px;">
                                            <i class="fa-solid fa-shield-check me-1"></i> Verified
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>



      <div id="analysis" class="dashboard-section">
    <div class="row g-4">
        <!-- Category Spending Chart -->
        <div class="col-md-6">
            <div class="glass-card text-center h-100" style="background: rgba(255,255,255,0.02); border-radius: 25px; padding: 25px; min-height: 400px;">
                <h5 class="fw-bold text-white mb-4">
                    <i class="fa-solid fa-chart-pie text-warning me-2"></i> Spending by Category
                </h5>
                <!-- Chart Container -->
                <div class="chart-container" style="position: relative; height:300px; width:100%">
                    <canvas id="catChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Popularity Index -->
        <div class="col-md-6">
            <div class="glass-card h-100" style="background: rgba(255,255,255,0.02); border-radius: 25px; padding: 25px;">
                <h5 class="fw-bold text-white mb-4">
                    <i class="fa-solid fa-fire text-warning me-2"></i> Your Rental Trends
                </h5>

                <?php if (empty($category_counts)): ?>
                    <p class="text-white-50 text-center py-5">No data available yet.</p>
                <?php else: ?>
                    <?php foreach ($category_counts as $name => $amount): 
                        $percent = round(($amount / ($total_spent ?: 1)) * 100);
                    ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-white fw-500"><?= htmlspecialchars($name) ?></small>
                            <small class="text-warning"><?= $percent ?>%</small>
                        </div>
                        <div class="progress bg-dark" style="height: 6px; border-radius: 10px; overflow: visible;">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: <?= $percent ?>%; box-shadow: 0 0 10px rgba(255,193,7,0.5); border-radius: 10px;" 
                                 aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

        <div id="profile-sec" class="dashboard-section">
            <div class="glass-card col-lg-9 mx-auto">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="text-center mb-5">
                        <img src="<?= $avatar_url ?>" id="profilePreview" class="profile-hero-img mb-3">
                        <input type="file" name="avatar" id="avatarInput" hidden onchange="previewImage(this)">
                        <br><label for="avatarInput"
                            class="btn btn-sm btn-outline-warning rounded-pill px-4 mt-2">Change Image</label>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6"><label class="small text-muted mb-2">Full Legal Name</label><input
                                type="text" name="full_name" class="form-control" value="<?= $user['name'] ?>"></div>
                        <div class="col-md-6"><label class="small text-muted mb-2">Mobile Number</label><input
                                type="text" name="phone_number" class="form-control"
                                value="<?= $user['phone'] ?? '' ?>"></div>
                        <div class="col-md-6"><label class="small text-muted mb-2">Date of Birth</label><input
                                type="date" name="dob" class="form-control" value="<?= $user['dob'] ?? '' ?>"></div>
                        <div class="col-md-6"><label class="small text-muted mb-2">Gender Identity</label><select
                                name="gender" class="form-select">
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select></div>
                        <div class="col-12"><label class="small text-muted mb-2">Billing Address</label><textarea
                                name="address" class="form-control" rows="3"><?= $user['address'] ?? '' ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="save_profile" class="btn btn-warning w-100 mt-5 py-3 fs-6">UPDATE
                        PROFILE DATA</button>
                </form>
            </div>
        </div>

        <div id="settings-sec" class="dashboard-section">
            <div class="col-lg-10 mx-auto">

                <div class="glass-card mb-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="p-2 bg-warning rounded-3 me-3"><i class="fa-solid fa-shield-halved text-dark"></i>
                        </div>
                        <h5 class="fw-800 text-white mb-0">Security & Privacy</h5>
                    </div>

                    <div class="list-group list-group-flush">
                        <div
                            class="list-group-item bg-transparent px-0 py-3 d-flex justify-content-between align-items-center border-secondary border-opacity-25">
                            <div>
                                <h6 class="mb-1 fw-bold text-white">Two-Factor Authentication</h6>
                                <p class="text-muted small mb-0">Add an extra layer of security to your RentHub account.
                                </p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="2faSwitch" checked>
                            </div>
                        </div>

                        <div
                            class="list-group-item bg-transparent px-0 py-3 d-flex justify-content-between align-items-center border-secondary border-opacity-25">
                            <div>
                                <h6 class="mb-1 fw-bold text-white">Login Alert Notifications</h6>
                                <p class="text-muted small mb-0">Receive email alerts whenever a new device logs into
                                    your profile.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="alertSwitch">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card mb-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="p-2 bg-info rounded-3 me-3"><i class="fa-solid fa-gear text-dark"></i></div>
                        <h5 class="fw-800 text-white mb-0">System Preferences</h5>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="small text-muted mb-2">Default Currency</label>
                            <select class="form-select bg-dark border-secondary text-white border-opacity-25">
                                <option selected>PKR (Rs.)</option>
                                <option>USD ($)</option>
                                <option>EUR (€)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-2">Language</label>
                            <select class="form-select bg-dark border-secondary text-white border-opacity-25">
                                <option selected>English (US)</option>
                                <option>Urdu</option>
                                <option>Spanish</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div
                                class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                <div>
                                    <h6 class="mb-1 fw-bold text-white">Cloud History Backup</h6>
                                    <p class="text-muted small mb-0">Automatically sync invoices and rental history to
                                        the cloud.</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" checked>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card border-danger border-opacity-25" style="background: rgba(220, 53, 69, 0.03);">
                    <div class="d-flex align-items-center mb-4">
                        <div class="p-2 bg-danger rounded-3 me-3"><i
                                class="fa-solid fa-triangle-exclamation text-white"></i></div>
                        <h5 class="fw-800 text-danger mb-0">Danger Zone</h5>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 fw-bold text-white">Delete Account</h6>
                            <p class="text-muted small mb-0">Once deleted, your rental history and data cannot be
                                recovered.</p>
                        </div>
                        <button class="btn btn-outline-danger px-4 fw-bold rounded-pill shadow-sm">Deactivate
                            Account</button>
                    </div>
                </div>

            </div>
        </div>
        <div id="calendar-sec" class="dashboard-section">
            <div class="glass-card" style="background: rgba(255,255,255,0.02); padding: 30px; border-radius: 28px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-800 text-white m-0">
                        <i class="fa-solid fa-calendar-days text-warning me-2"></i> Rental Schedule
                    </h4>
                    <div class="d-flex gap-2">
                        <span class="badge bg-warning text-dark px-3 py-2" style="border-radius: 10px;">
                            <i class="fa-solid fa-box-open me-1"></i> Pick-up
                        </span>
                        <span class="badge bg-danger text-white px-3 py-2" style="border-radius: 10px;">
                            <i class="fa-solid fa-rotate-left me-1"></i> Return
                        </span>
                    </div>
                </div>

                <!-- Calendar Element -->
                <div id='calendar-el'
                    style="background: #111; color: #fff; padding: 20px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);">
                </div>

                <!-- Updated Legend for Color Indicators -->
                <div class="mt-4 d-flex flex-wrap gap-4 p-3"
                    style="background: rgba(255,255,255,0.03); border-radius: 15px;">
                    <div class="d-flex align-items-center">
                        <div
                            style="width: 12px; height: 12px; background: #ffc107; border-radius: 3px; margin-right: 8px;">
                        </div>
                        <small class="text-white-50">Yellow: Item Pick-up Date</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <div
                            style="width: 12px; height: 12px; background: #dc3545; border-radius: 3px; margin-right: 8px;">
                        </div>
                        <small class="text-white-50">Red: Item Return Due Date</small>
                    </div>
                    <div class="ms-auto">
                        <small class="text-muted italic"><i class="fa-solid fa-mouse-pointer me-1"></i> Click any event
                            to see details</small>
                    </div>
                </div>
            </div>
        </div>
    </main>

   <script>
    // 1. GLOBAL VARIABLES
    var calendar;
    var catChartInstance = null; // Chart instance track karne ke liye

    // 2. NAVIGATION & UI FUNCTIONS
    function showTab(id, el) {
        document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));
        const target = document.getElementById(id);
        if (target) target.classList.add('active');

        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        el.classList.add('active');

        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Tab switch hone par Calendar aur Chart ko refresh karna
        if (id === 'calendar-sec' && calendar) {
            setTimeout(() => { calendar.updateSize(); }, 300);
        }
        
        if (id === 'analysis') {
            // Analysis tab par aane par chart ko dobara render/update karna
            setTimeout(() => { initCategoryChart(); }, 300);
        }
    }

    // 3. CHART INITIALIZATION FUNCTION
    function initCategoryChart() {
        const catCanvas = document.getElementById('catChart');
        if (!catCanvas) return;

        const catCtx = catCanvas.getContext('2d');
        const labels = <?php echo json_encode($catLabels ?? []); ?>;
        const data = <?php echo json_encode($catValues ?? []); ?>;

        // Agar pehle se chart bana hua hai toh usey destroy karein taakay naya data dikhe
        if (catChartInstance) {
            catChartInstance.destroy();
        }

        catChartInstance = new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#ffc107', '#ff4d5e', '#0dcaf0', '#6610f2', '#fd7e14'],
                    borderWidth: 0,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#ffffff', padding: 20, font: { size: 12 } }
                    }
                },
                cutout: '75%'
            }
        });
    }

    // 4. INITIALIZATION ON DOM LOAD
    document.addEventListener('DOMContentLoaded', () => {

        // --- 📊 SPENDING CHART ---
        const spendingCtx = document.getElementById('spendingChart');
        if (spendingCtx) {
            new Chart(spendingCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [{
                        label: 'Spending (Rs.)',
                        data: [2000, 5000, 3500, <?= (float)($total_spent ?? 0) ?>],
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#ffc107'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#888' } },
                        x: { grid: { display: false }, ticks: { color: '#888' } }
                    }
                }
            });
        }

        // --- 🍕 CATEGORY CHART INITIAL LOAD ---
        initCategoryChart();

        // --- 📅 FULLCALENDAR ---
        const calendarEl = document.getElementById('calendar-el');
        if (calendarEl) {
            const rentalEvents = <?php echo json_encode($calendar_events ?? []); ?>;

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                initialDate: '2026-05-01',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: rentalEvents,
                eventDisplay: 'block',
                displayEventTime: false,
                height: 'auto',
                eventClick: function (info) {
                    alert("Rental Details:\n" + info.event.title + "\nDate: " + info.event.startStr);
                },
                eventDidMount: function (info) {
                    if (info.event.title.includes('Pick')) {
                        info.el.style.borderLeft = '5px solid #ffc107';
                    } else if (info.event.title.includes('Return')) {
                        info.el.style.borderLeft = '5px solid #dc3545';
                    }
                }
            });
            calendar.render();
        }
    });
</script>
</body>

</html>