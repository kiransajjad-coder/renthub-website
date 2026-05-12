<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
use MongoDB\BSON\ObjectId;

if (isset($_GET['id'])) {
    try {
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->renthub_db;

        $id = $_GET['id'];
        // Database se payment delete karna
        $result = $db->payments->deleteOne(['_id' => new ObjectId($id)]);

        if ($result->getDeletedCount() === 1) {
            header("Location: view-payments.php?msg=Deleted Successfully");
        } else {
            header("Location: view-payments.php?msg=Error Deleting");
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
?>