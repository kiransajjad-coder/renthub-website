<?php
/**
 * RentHub OS | Premium Product Onboarding
 * Collection: renthub_db.products | renthub_db.logs
 */

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// Security: Check Admin Auth
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        $db = $client->renthub_db;
        $collection = $db->products;
        $logCollection = $db->logs;

        // 1. Data Cleaning
        $productName = htmlspecialchars($_POST['name']);
        $category = $_POST['category'];

        // 2. Data Structure (Updated with Quantity)
        $newProduct = [
            "name"       => $productName,
            "category"   => $category,
            "price"      => (int)$_POST['price'],
            "quantity"   => (int)$_POST['quantity'],         // NEW: Initial Stock
            "city"       => htmlspecialchars($_POST['city']),
            "status"     => "available", 
            "image"      => $_POST['image_url'],
            "desc"       => htmlspecialchars($_POST['desc']),
            "rating"     => (float)4.8,
            "views"      => (int)rand(50, 150),
            "created_at" => new MongoDB\BSON\UTCDateTime()
        ];

        // 3. Insert into Products
        $result = $collection->insertOne($newProduct);
        
        if ($result->getInsertedCount() > 0) {
            // 4. System Log
            $logCollection->insertOne([
                "action"    => "Product Added",
                "details"   => "New asset '$productName' deployed. Initial Stock: " . $_POST['quantity'],
                "timestamp" => new MongoDB\BSON\UTCDateTime(),
                "ip"        => $_SERVER['REMOTE_ADDR']
            ]);

            $message = "
            <div class='alert alert-success border-0 bg-success text-white rounded-4 shadow-sm animate__animated animate__fadeIn'>
                <i class='fa-solid fa-crown me-2'></i> <strong>Asset Live:</strong> Successfully deployed to fleet!
            </div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger border-0 bg-danger text-white rounded-4'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. Executive Page Title -->
    <title>Register New Asset | RentHub Command Center</title>

    <!-- 2. Premium Favicon (Gold Crown) -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 3. Google Fonts (Plus Jakarta Sans & Mono) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <!-- 4. Frameworks & Animations -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root { --gold: #D4AF37; --dark: #050505; --card: #111; --border: rgba(212,175,55,0.15); }
        body { background: var(--dark); color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .sidebar { width: 90px; position: fixed; height: 100vh; background: #000; border-right: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; padding: 40px 0; z-index: 1000; }
        .nav-link { color: #333; font-size: 1.6rem; margin-bottom: 35px; transition: 0.3s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { color: var(--gold); }

        .main-content { margin-left: 90px; padding: 60px 8%; }
        .form-card { background: var(--card); border: 1px solid var(--border); border-radius: 35px; padding: 45px; box-shadow: 0 30px 60px rgba(0,0,0,0.6); }
        .form-label { font-weight: 700; color: var(--gold); font-size: 11px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 12px; }
        
        .form-control, .form-select { background: #000; border: 1px solid #222; color: #fff; padding: 18px 22px; border-radius: 18px; transition: 0.3s; }
        .form-control:focus { background: #080808; border-color: var(--gold); color: #fff; box-shadow: none; }

        .img-preview { width: 100%; height: 280px; border-radius: 25px; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; overflow: hidden; background: #000; }
        .img-preview img { width: 100%; height: 100%; object-fit: cover; display: none; }

        .btn-submit { background: var(--gold); color: #000; font-weight: 800; border: none; padding: 20px; border-radius: 22px; width: 100%; transition: 0.4s; letter-spacing: 1px; text-transform: uppercase; }
        .btn-submit:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(212,175,55,0.3); }
        .crown-icon { color: var(--gold); font-size: 2.2rem; filter: drop-shadow(0 0 10px rgba(212,175,55,0.3)); }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="mb-5 crown-icon"><i class="fa-solid fa-crown"></i></div> <!-- Crown Icon -->
    <a href="dashboard.php" class="nav-link active"><i class="fa-solid fa-house"></i></a>
    <a href="properties.php" class="nav-link"><i class="fa-solid fa-boxes-stacked"></i></a>
    <a href="logout.php" class="nav-link mt-auto"><i class="fa-solid fa-power-off text-danger"></i></a>
</nav>

<main class="main-content">
    <div class="mb-5 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="text-gold fw-bold mb-1" style="letter-spacing: 5px;">COMMAND TERMINAL</h6>
            <h1 class="fw-800 m-0">Deploy <span class="text-gold">New Asset</span></h1>
        </div>
        <a href="properties.php" class="btn btn-outline-secondary border-0 fw-bold"><i class="fa fa-arrow-left me-2"></i> BACK</a>
    </div>

    <?= $message ?>

    <form method="POST" class="row g-4 mt-2">
        <div class="col-lg-8">
            <div class="form-card animate__animated animate__fadeInUp">
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label">Asset Title</label>
                        <input type="text" name="name" class="form-control" placeholder="Luxury Wedding Stage Decor" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="" selected disabled>Select...</option>
                            <option value="Decor">Decor</option>
                            <option value="Dresses">Dresses</option>
                            <option value="Cameras">Cameras</option>
                            <option value="Drones">Drones</option>
                            <option value="Projectors">Projectors</option>
                            <option value="Sound">Sound</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Daily Price (PKR)</label>
                        <input type="number" name="price" class="form-control" placeholder="45000" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Total Quantity</label> <!-- Quantity Field Added -->
                        <input type="number" name="quantity" class="form-control" placeholder="10" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" placeholder="Karachi" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Specifications</label>
                        <textarea name="desc" class="form-control" rows="4" placeholder="Detailed technical intel..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card animate__animated animate__fadeInRight">
                <label class="form-label">Media URL</label>
                <input type="url" name="image_url" id="imageInput" class="form-control mb-4" placeholder="Paste high-res image link..." required>
                
                <label class="form-label">Visual Preview</label>
                <div class="img-preview mb-4">
                    <i class="fa fa-camera-retro fs-1 text-muted" id="placeholderIcon"></i>
                    <img src="" id="previewImg">
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-crown me-2"></i> DEPLOY TO SYSTEM <!-- Crown in button -->
                </button>
            </div>
        </div>
    </form>
</main>

<script>
    const imageInput = document.getElementById('imageInput');
    const previewImg = document.getElementById('previewImg');
    const placeholderIcon = document.getElementById('placeholderIcon');

    imageInput.addEventListener('input', function() {
        if (this.value) {
            previewImg.src = this.value;
            previewImg.style.display = 'block';
            placeholderIcon.style.display = 'none';
        }
    });

    previewImg.onerror = function() {
        this.style.display = 'none';
        placeholderIcon.style.display = 'block';
    }
</script>

</body>
</html>