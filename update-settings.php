<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Security Check
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        $db = $client->renthub_db;

        $new_user = $_POST['new_username'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';

        if (!empty($new_user)) {
            // 1. Update in Database (Collection: admin_config)
            $db->admin_config->updateOne(
                ['type' => 'credentials'],
                ['$set' => [
                    'username' => $new_user,
                    'password' => !empty($new_pass) ? $new_pass : $_SESSION['admin_password']
                ]],
                ['upsert' => true] // Agar pehle se nahi hai toh bana do
            );

            // 2. Update Sessions
            $_SESSION['admin_username'] = $new_user;
            if (!empty($new_pass)) {
                $_SESSION['admin_password'] = $new_pass;
            }

            // 3. UI Response (Ab ye laazmi dikhega)
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Updating Settings...</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                <style>
                    body { background: #f8fafc; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: sans-serif; }
                    .success-card { background: white; padding: 3rem; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
                    .check-icon { font-size: 4rem; color: #10b981; margin-bottom: 1.5rem; animation: scaleIn 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
                    @keyframes scaleIn { from { transform: scale(0); } to { transform: scale(1); } }
                </style>
            </head>
            <body>
                <div class="success-card">
                    <i class="fa-solid fa-circle-check check-icon"></i>
                    <h2 class="fw-bold text-dark">Settings Applied!</h2>
                    <p class="text-secondary">System credentials have been updated in the database.</p>
                    <div class="spinner-border spinner-border-sm text-primary mt-3" role="status"></div>
                    <p class="small text-muted mt-2">Redirecting to Dashboard...</p>
                </div>

                <script>
                    setTimeout(() => {
                        window.location.href = 'view-payments.php';
                    }, 2500);
                </script>
            </body>
            </html>
            <?php
            exit();

        }
    } catch (Exception $e) {
        die("❌ Error updating database: " . $e->getMessage());
    }
} else {
    header("Location: view-payments.php");
    exit();
}