<?php
session_start();

$user = "admin";
$pass = "admin123"; // Aap isay badal saktay hain

if ($_POST['username'] === $user && $_POST['password'] === $pass) {
    $_SESSION['admin_logged_in'] = true;
    header("Location: view-payments.php");
} else {
    header("Location: login.php?error=1");
}
?>