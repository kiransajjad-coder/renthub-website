<?php
/**
 * RentHub OS | Executive Asset Modifier
 * Collection: renthub_db.products | renthub_db.logs
 */

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// Security Protocol
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$message = "";
try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;
    $collection = $db->products;

    // Fetch Existing Data
    if (!isset($_GET['id'])) {
        header("Location: properties.php");
        exit();
    }

    $id = $_GET['id'];
    $product = $collection->findOne(['_id' => new ObjectId($id)]);

    if (!$product) {
        header("Location: properties.php?error=not_found");
        exit();
    }

    // Handle Update Request
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $updateData = [
            '$set' => [
                "name"       => htmlspecialchars($_POST['name']),
                "category"   => $_POST['category'],
                "price"      => (int)$_POST['price'],
                "quantity"   => (int)$_POST['quantity'], // Added Quantity
                "city"       => htmlspecialchars($_POST['city']),
                "image"      => $_POST['image_url'],
                "desc"       => htmlspecialchars($_POST['desc']),
                "status"     => $_POST['status'], // Dynamic Status
                "updated_at" => new UTCDateTime()
            ]
        ];

        $collection->updateOne(['_id' => new ObjectId($id)], $updateData);

        // CREATE AUDIT LOG
        $db->logs->insertOne([
            "action"    => "Asset Modified",
            "details"   => "ID: " . $id . " updated. New Stock: " . $_POST['quantity'],
            "timestamp" => new UTCDateTime(),
            "ip"        => $_SERVER['REMOTE_ADDR']
        ]);

        $message = "
        <div class='alert alert-success border-0 bg-success text-white rounded-4 shadow-lg animate__animated animate__fadeIn'>
            <i class='fa-solid fa-crown me-2'></i> <strong>Success:</strong> System records updated successfully!
        </div>";
        
        // Refresh local data
        $product = $collection->findOne(['_id' => new ObjectId($id)]);
    }

} catch (Exception $e) {
    $message = "<div class='alert alert-danger border-0 bg-danger text-white rounded-4'>Error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. Technical Page Title -->
    <title>Modify Asset | RentHub Command Center</title>

    <!-- 2. Premium Favicon (Gold Crown) -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 3. Google Fonts (Plus Jakarta Sans & Mono) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <!-- 4. Frameworks & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root { --gold: #D4AF37; --dark: #050505; --card: #0F0F0F; --border: rgba(212,175,55,0.15); }
        body { background: var(--dark); color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; min-height: 100vh; }
        
        .sidebar { width: 90px; position: fixed; height: 100vh; background: #000; border-right: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; padding: 40px 0; z-index: 1000; }
        .nav-link { color: #333; font-size: 1.6rem; margin-bottom: 35px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--gold); }

        .main-content { margin-left: 90px; padding: 60px 8%; }
        .form-card { background: var(--card); border: 1px solid var(--border); border-radius: 35px; padding: 45px; box-shadow: 0 40px 80px rgba(0,0,0,0.8); }
        
        .form-label { font-weight: 800; color: var(--gold); font-size: 11px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 12px; display: block; }
        .form-control, .form-select { background: #000; border: 1px solid #1a1a1a; color: #fff; padding: 18px 22px; border-radius: 18px; transition: 0.3s; font-size: 14px; }
        .form-control:focus { background: #000; border-color: var(--gold); box-shadow: 0 0 0 4px rgba(212,175,55,0.1); color: #fff; }

        .img-preview { width: 100%; height: 320px; border-radius: 25px; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; overflow: hidden; background: #000; }
        .img-preview img { width: 100%; height: 100%; object-fit: cover; }

        .btn-update { background: var(--gold); color: #000; font-weight: 800; border: none; padding: 20px; border-radius: 20px; width: 100%; transition: 0.4s; letter-spacing: 1px; text-transform: uppercase; font-size: 14px; }
        .btn-update:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(212,175,55,0.2); background: #e5be40; }
        
        .asset-id-badge { background: rgba(255,255,255,0.05); padding: 5px 15px; border-radius: 10px; font-size: 12px; color: #666; border: 1px solid #222; }
        .crown-logo { color: var(--gold); font-size: 2.5rem; filter: drop-shadow(0 0 10px rgba(212,175,55,0.3)); }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="mb-5 crown-logo"><i class="fa-solid fa-crown"></i></div> <!-- Crown Icon Added -->
    <a href="properties.php" class="nav-link"><i class="fa-solid fa-house"></i></a>
    <a href="properties.php" class="nav-link active"><i class="fa-solid fa-boxes-stacked"></i></a>
    <a href="logs.php" class="nav-link"><i class="fa-solid fa-terminal"></i></a>
    <a href="logout.php" class="nav-link mt-auto"><i class="fa-solid fa-power-off text-danger"></i></a>
</nav>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <div class="asset-id-badge mb-2">SYSTEM OBJECT: <?= (string)$product['_id'] ?></div>
            <h1 class="fw-800 m-0" style="font-size: 2.8rem;">Modify <span class="text-gold">Asset</span></h1>
        </div>
        <a href="properties.php" class="btn btn-outline-secondary border-0 fw-bold px-4 rounded-pill">
            <i class="fa fa-arrow-left me-2"></i> BACK TO FLEET
        </a>
    </div>

    <?= $message ?>

    <form method="POST" class="row g-4 mt-2">
        <div class="col-lg-8">
            <div class="form-card animate__animated animate__fadeInLeft">
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label">Asset Identity Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Classification</label>
                        <select name="category" class="form-select" required>
                            <?php 
                            $cats = ["Cameras", "Sound", "Drones", "Decor", "Projectors", "Dresses"];
                            foreach($cats as $c): ?>
                                <option value="<?= $c ?>" <?= ($product['category'] == $c) ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Current Daily Rate (PKR)</label>
                        <input type="number" name="price" class="form-control" value="<?= $product['price'] ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Total Inventory Quantity</label> <!-- Quantity Added -->
                        <input type="number" name="quantity" class="form-control" value="<?= $product['quantity'] ?? 1 ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Availability Status</label>
                        <select name="status" class="form-select">
                            <option value="available" <?= ($product['status'] == 'available') ? 'selected' : '' ?>>Available</option>
                            <option value="rented" <?= ($product['status'] == 'rented') ? 'selected' : '' ?>>Rented</option>
                            <option value="maintenance" <?= ($product['status'] == 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Location (City)</label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($product['city'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Asset Technical Intel</label>
                        <textarea name="desc" class="form-control" rows="5"><?= htmlspecialchars($product['desc'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card animate__animated animate__fadeInRight">
                <label class="form-label">Media Source (URL)</label>
                <input type="url" name="image_url" id="imageInput" class="form-control mb-4" value="<?= htmlspecialchars($product['image'] ?? '') ?>" required>
                
                <label class="form-label">Visual Integrity Check</label>
                <div class="img-preview mb-4">
                    <img src="<?= $product['image'] ?>" id="previewImg">
                </div>

                <button type="submit" class="btn-update">
                    <i class="fa-solid fa-crown me-2"></i> UPDATE SYSTEM DATA
                </button>
            </div>
        </div>
    </form>
</main>

<script>
    const imageInput = document.getElementById('imageInput');
    const previewImg = document.getElementById('previewImg');

    imageInput.addEventListener('input', function() {
        if (this.value) {
            previewImg.src = this.value;
        }
    });

    previewImg.onerror = function() {
        this.src = 'https://via.placeholder.com/500x500?text=Invalid+Image+URL';
    };
</script>

</body>
</html>