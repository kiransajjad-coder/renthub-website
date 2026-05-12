<?php
require 'vendor/autoload.php'; 

try {
    // MongoDB Connection
    $client = new MongoDB\Client("mongodb://localhost:27017");

    // Aik choti si command database list check karne ke liye
    $client->listDatabases();

    echo "<h1>✅ MUBARAK HO!</h1>";
    echo "<p>PHP aur MongoDB ka connection bilkul sahi kaam kar raha hai.</p>";
} catch (Exception $e) {
    echo "<h1>❌ Connection Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>