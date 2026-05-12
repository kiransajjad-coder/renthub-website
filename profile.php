<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// 1. Session Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->renthub_db;
    $user_id = new MongoDB\BSON\ObjectId($_SESSION['user_id']);
    
    // 2. Fetch User Data
    $userData = $db->users->findOne(['_id' => $user_id]);

    // 3. Update Logic
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
        $newName = htmlspecialchars($_POST['full_name']);
        $newPhone = htmlspecialchars($_POST['phone'] ?? '');
        
        $updateData = [
            'full_name' => $newName,
            'phone' => $newPhone,
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];

        // Image Processing
        if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] == 0) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            
            $extension = pathinfo($_FILES["profile_img"]["name"], PATHINFO_EXTENSION);
            $fileName = "user_" . time() . "." . $extension;
            
            if (move_uploaded_file($_FILES["profile_img"]["tmp_name"], $targetDir . $fileName)) {
                $updateData['profile_img'] = $fileName;
            }
        }

        $db->users->updateOne(['_id' => $user_id], ['$set' => $updateData]);
        
        // Refresh to show updated data
        header("Location: profile.php?status=success");
        exit();
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Variables for Display
$currentName = $userData['full_name'] ?? $userData['name'] ?? 'User';
$email = $userData['email'] ?? 'N/A';
$profileImg = !empty($userData['profile_img']) 
    ? 'uploads/' . $userData['profile_img'] 
    : "https://ui-avatars.com/api/?name=" . urlencode($currentName) . "&background=d4af37&color=000&bold=true&size=200";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. Page Title -->
    <title>Edit Profile | RentHubPro</title>

    <!-- 2. Premium Favicon (Executive Gold Crown) -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 3. Google Fonts (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <!-- 4. Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --gold: #d4af37; --darker: #0f0f0f; --card-bg: #1a1a1a; }
        body { background-color: var(--darker); color: white; font-family: 'Inter', sans-serif; }
        
        .main-card {
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            margin-top: 50px;
        }

        /* Profile Image Hover Effect */
        .profile-img-wrapper {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            cursor: pointer;
        }

        .profile-preview-lg {
            width: 150px; height: 150px;
            border-radius: 25px;
            object-fit: cover;
            border: 3px solid var(--gold);
            transition: 0.3s;
        }

        .img-overlay {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 25px;
            opacity: 0;
            transition: 0.3s;
            color: white;
        }

        .profile-img-wrapper:hover .img-overlay { opacity: 1; }
        .profile-img-wrapper:hover .profile-preview-lg { filter: blur(2px); }

        .form-control {
            background: #252525;
            border: 1px solid #333;
            color: white;
            padding: 12px;
        }

        .form-control:focus {
            background: #2d2d2d;
            border-color: var(--gold);
            color: white;
            box-shadow: none;
        }

        .btn-gold { background: var(--gold); color: black; font-weight: 700; border: none; }
        .btn-gold:hover { background: #b8962d; color: black; }
        
        #fileInput { display: none; }
    </style>
</head>
<body>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="main-card p-4 p-md-5">
                
                <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                    <div class="alert alert-success bg-success text-white border-0">Profile updated successfully!</div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="text-center mb-5">
                        <h2 class="fw-bold mb-4">Edit Public Profile</h2>
                        
                        <div class="profile-img-wrapper" onclick="document.getElementById('fileInput').click();">
                            <img src="<?= $profileImg ?>" class="profile-preview-lg" id="imgPreview">
                            <div class="img-overlay">
                                <i class="fa fa-camera fa-2x"></i>
                            </div>
                        </div>
                        <input type="file" name="profile_img" id="fileInput" accept="image/*" onchange="previewImage(this)">
                        <p class="text-muted small">Click photo to upload new one</p>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($currentName) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Email Address (Read Only)</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" readonly style="opacity: 0.5;">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label text-muted small">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+92 300 1234567" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>">
                        </div>

                        <div class="col-12 mt-5 d-flex gap-3">
                            <button type="submit" name="update_profile" class="btn btn-gold w-100 py-3">Update My Profile</button>
                            <a href="index.php" class="btn btn-outline-light w-100 py-3">Cancel</a>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imgPreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

</body>
</html>