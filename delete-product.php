<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

if (isset($_GET['id'])) {
    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        $db = $client->renthub_db;
        $productId = $_GET['id'];

        // 1. Pehle product ka naam nikal lo log ke liye
        $product = $db->products->findOne(['_id' => new MongoDB\BSON\ObjectId($productId)]);
        $pName = $product ? $product['name'] : "Unknown Asset";

        // 2. Delete the product
        $db->products->deleteOne(['_id' => new MongoDB\BSON\ObjectId($productId)]);

        // 3. LOG THE ACTION (Professional Data)
        $db->logs->insertOne([
            "action" => "Product Deleted",
            "details" => "Asset '$pName' was permanently removed from the database.",
            "timestamp" => new MongoDB\BSON\UTCDateTime(),
            "ip" => $_SERVER['REMOTE_ADDR']
        ]);

        header("Location: properties.php?deleted=success");
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}