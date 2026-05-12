<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;
} catch (Exception $e) { 
    die("Database Connection Error."); 
}

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$oid = new MongoDB\BSON\ObjectId($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUser = $db->users->findOne(['_id' => $oid]);

    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $phone     = htmlspecialchars(trim($_POST['phone']));
    
    $updateData = [
        'full_name'  => $full_name,
        'phone'      => $phone,
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ];

    // --- 📸 Gallery Image Upload Logic ---
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === 0) {
        // Folder path check
        $uploadDir = 'uploads/profiles/';
        if (!is_dir($uploadDir)) { 
            mkdir($uploadDir, 0777, true); 
        }

        $fileName = $_FILES['profile_img']['name'];
        $fileTmp  = $_FILES['profile_img']['tmp_name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExt, $allowedExts)) {
            // Naya unique naam taake cache ka masla na ho
            $newFileName = "pro_" . $user_id . "_" . time() . "." . $fileExt;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $targetPath)) {
                // Purani photo delete karna taake storage full na ho
                if (!empty($currentUser['profile_img']) && file_exists($currentUser['profile_img'])) {
                    @unlink($currentUser['profile_img']);
                }
                
                // Dashboard ke liye relative path save karein
                $updateData['profile_img'] = $targetPath;
            }
        }
    }

    if (!empty($_POST['password'])) {
        $updateData['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }

    try {
        $db->users->updateOne(['_id' => $oid], ['$set' => $updateData]);
        header("Location: user-dashboard.php?status=updated");
    } catch (Exception $e) {
        header("Location: user-dashboard.php?status=error");
    }
    exit();
}