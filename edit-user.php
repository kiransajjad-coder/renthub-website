<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: login.php"); 
    exit(); 
}

$id = $_GET['id'] ?? '';

// 1. Validate ID format before doing anything else
if (!preg_match('/^[a-f\d]{24}$/i', $id)) { 
    die("<div style='color:white; background:red; padding:20px;'>Error: Invalid User ID format.</div>"); 
}

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;
    $objId = new MongoDB\BSON\ObjectId($id);

    // 2. Handle the Form Submission (Update)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db->users->updateOne(
            ['_id' => $objId],
            ['$set' => [
                'full_name' => $_POST['full_name'] ?? '',
                'email'     => $_POST['email'] ?? '',
                'phone'     => $_POST['phone'] ?? ''
            ]]
        );
        header("Location: manage-users.php?msg=updated");
        exit();
    }

    // 3. Fetch the user data
    $user = $db->users->findOne(['_id' => $objId]);

    if (!$user) {
        die("<div style='color:white; background:red; padding:20px;'>Error: User not found in database.</div>");
    }

} catch (Exception $e) { 
    die("System Error: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. Page Title -->
    <title>Modify User Protocol | RentHub Admin</title>

    <!-- 2. Premium Favicon (Gold Crown) -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 3. Google Fonts (Plus Jakarta Sans & Mono) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <!-- 4. Bootstrap 5 & Font Awesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #050505; color: white; padding-top: 50px; font-family: sans-serif; }
        .card { background: #0f0f0f; border: 1px solid #D4AF37; padding: 2.5rem; max-width: 500px; margin: auto; border-radius: 15px; }
        .form-label { color: #D4AF37; font-weight: bold; font-size: 0.9rem; }
        .form-control { background: #1a1a1a; color: white; border: 1px solid #333; padding: 12px; }
        .form-control:focus { background: #222; color: white; border-color: #D4AF37; box-shadow: none; }
        .btn-warning { background: #D4AF37; border: none; font-weight: bold; padding: 12px; color: black; }
        .btn-warning:hover { background: #b8952d; }
    </style>
</head>
<body>
    <div class="card shadow-lg">
        <h2 class="mb-4 text-center">Edit <span style="color:#D4AF37;">Profile</span></h2>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" 
                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" 
                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-warning w-100">Update Identity</button>
                <a href="manage-users.php" class="btn btn-link w-100 text-secondary text-decoration-none mt-2">Back to Directory</a>
            </div>
        </form>
    </div>
</body>
</html>