<?php
session_start();
include '../config/db_connect.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Replace with your actual admin credentials
    $admin_email = "admin@gmail.com";
    $admin_password = "admin123"; // You should hash passwords in a real application

    // Check if input matches admin credentials
    if ($username == $admin_email && $password == $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_dashboard.php"); // Redirect to dashboard
        exit();
    } else {
        echo "<script>alert('Invalid Username or Password!'); window.location.href='admin_login.php';</script>";
    }
}
?>
