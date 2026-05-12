<?php
/**
 * RentHub OS | Executive Audit Terminal (EAT)
 * Collection: renthub_db.logs
 */

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// Security Protocol
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;
    $logCollection = $db->logs;

    // Fetch latest 100 entries with descending timestamp
    $logs = $logCollection->find([], [
        'limit' => 100,
        'sort' => ['timestamp' => -1]
    ])->toArray();

} catch (Exception $e) {
    die("<div style='background:#000; color:red; padding:50px; font-family:sans-serif;'>
            <h3>CRITICAL: DATABASE OFFLINE</h3>
            <p>Error: ". $e->getMessage() ."</p>
         </div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. Page Title -->
    <title>Audit Terminal | RentHub Command Center</title>

    <!-- 2. Live Stream Refresh (Every 5 Seconds) -->
    <meta http-equiv="refresh" content="5">

    <!-- 3. Premium Favicon (Executive Gold Crown) -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 4. Google Fonts (Plus Jakarta & JetBrains Mono) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <!-- 5. Bootstrap & Icon Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --gold: #D4AF37; 
            --dark-bg: #050505; 
            --card-bg: #0A0A0A; 
            --border-color: rgba(212,175,55,0.15); 
        }

        body { 
            background: var(--dark-bg); 
            color: #ffffff; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            letter-spacing: -0.2px;
        }

        /* Fixed Sidebar Navigation */
        .sidebar { width: 90px; position: fixed; height: 100vh; background: #000; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; align-items: center; padding: 40px 0; z-index: 1000; }
        .nav-item { color: #333; font-size: 1.6rem; margin-bottom: 35px; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); text-decoration: none; }
        .nav-item:hover, .nav-item.active { color: var(--gold); transform: scale(1.1); }

        .main-content { margin-left: 90px; padding: 60px 80px; }

        /* Terminal Container */
        .terminal-container { 
            background: var(--card-bg); 
            border: 1px solid var(--border-color); 
            border-radius: 35px; 
            overflow: hidden; 
            box-shadow: 0 40px 100px rgba(0,0,0,0.8);
        }

        /* Table Aesthetics */
        .table { margin-bottom: 0; }
        .table thead th { 
            background: #000; 
            color: var(--gold); 
            border: none; 
            padding: 25px 20px; 
            font-size: 10px; 
            font-weight: 800;
            text-transform: uppercase; 
            letter-spacing: 2px;
        }
        .table tbody td { 
            padding: 22px 20px; 
            border-bottom: 1px solid rgba(255,255,255,0.03); 
            vertical-align: middle;
            color: #999;
            font-size: 14px;
        }
        .table tbody tr:hover { background: rgba(212,175,55,0.02); }

        /* Modern Status Badges */
        .badge-pill {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .action-added { background: rgba(0, 255, 136, 0.1); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.2); }
        .action-deleted { background: rgba(255, 50, 80, 0.1); color: #ff3250; border: 1px solid rgba(255, 50, 80, 0.2); }
        .action-updated { background: rgba(0, 187, 255, 0.1); color: #00bbff; border: 1px solid rgba(0, 187, 255, 0.2); }
        .action-system { background: rgba(212, 175, 55, 0.1); color: var(--gold); border: 1px solid var(--border-color); }

        .ip-address { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: #555; }
        .timestamp { font-size: 13px; color: #666; font-weight: 500; }
        .details-text { color: #e0e0e0; font-weight: 600; }

        /* Empty State */
        .empty-audit { padding: 120px 0; text-align: center; color: #444; }
        .empty-audit i { font-size: 4rem; margin-bottom: 20px; color: #1a1a1a; }
    </style>
</head>
<body>

<nav class="sidebar">
   <div class="mb-5" style="color: var(--gold);"><i class="fa-solid fa-crown fs-2"></i></div>
    <a href="view-payments.php" class="nav-item"><i class="fa-solid fa-house"></i></a>
    <a href="properties.php" class="nav-item"><i class="fa-solid fa-boxes-stacked"></i></a>
    <a href="logs.php" class="nav-item active"><i class="fa-solid fa-terminal"></i></a>
    <a href="logout.php" class="nav-item mt-auto"><i class="fa-solid fa-power-off text-danger"></i></a>
</nav>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h6 class="text-gold fw-bold mb-1" style="letter-spacing: 6px;">CORE SECURITY AUDIT</h6>
            <h1 class="fw-800 m-0" style="font-size: 3rem;">Audit <span class="text-gold">Terminal</span></h1>
        </div>
        <div class="text-end">
            <div class="d-flex align-items-center bg-dark border border-secondary px-4 py-2 rounded-pill">
                <div class="spinner-grow spinner-grow-sm text-success me-3" role="status"></div>
                <span class="small fw-bold text-muted">MONITORING ENGINE LIVE</span>
            </div>
        </div>
    </div>

    <div class="terminal-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 15%;">Event Class</th>
                    <th style="width: 50%;">Audit Description</th>
                    <th style="width: 15%;">Network IP</th>
                    <th style="width: 20%;">Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): 
                    $action = $log['action'] ?? 'System';
                    
                    // Logic for Action Badges
                    $badgeClass = 'action-system';
                    if (strpos($action, 'Added') !== false) $badgeClass = 'action-added';
                    if (strpos($action, 'Deleted') !== false) $badgeClass = 'action-deleted';
                    if (strpos($action, 'Updated') !== false) $badgeClass = 'action-updated';
                ?>
                <tr>
                    <td>
                        <span class="badge-pill <?= $badgeClass ?>">
                            <i class="fa-solid fa-circle me-1 small"></i> <?= $action ?>
                        </span>
                    </td>
                    <td class="details-text">
                        <?= htmlspecialchars($log['details'] ?? 'System event recorded without description.') ?>
                    </td>
                    <td>
                        <span class="ip-address">
                            <i class="fa-solid fa-network-wired me-2"></i><?= $log['ip'] ?? '127.0.0.1' ?>
                        </span>
                    </td>
                    <td class="timestamp">
                        <i class="fa-regular fa-calendar-check me-2 opacity-50"></i>
                        <?php 
                            if(isset($log['timestamp'])) {
                                echo $log['timestamp']->toDateTime()->format('M d, Y | H:i:s');
                            }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="4">
                        <div class="empty-audit">
                            <i class="fa-solid fa-magnifying-glass-chart"></i>
                            <h4 class="text-muted">Zero Activity Logs</h4>
                            <p class="small text-secondary">Awaiting first system event... Execute an action to populate this terminal.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>