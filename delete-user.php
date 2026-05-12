<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) { exit("Unauthorized"); }

$id = $_GET['id'] ?? '';

if (preg_match('/^[a-f\d]{24}$/i', $id)) {
    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        $client->renthub_db->users->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        header("Location: manage-users.php?msg=deleted");
    } catch (Exception $e) {
        header("Location: manage-users.php?msg=error");
    }
} else {
    header("Location: manage-users.php?msg=invalid_id");
}
exit();