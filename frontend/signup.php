<?php
session_start();
require '../config/db_connect.php';
require 'navbar.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require '../backend/auth/register.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Signup</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="text-center text-primary">Signup</h2>
    
    <?php if (isset($_SESSION["error"])): ?>
        <div class='alert alert-danger text-center'><?= $_SESSION["error"] ?></div>
        <?php unset($_SESSION["error"]); ?>
    <?php endif; ?>

    <form action="signup.php" method="POST" class="shadow p-4 bg-white">
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Register</button>
        <p class="mt-3">Already have an account? <a href="index.php">Login</a></p>
    </form>
</div>

<?php require 'footer.php'; ?>
</body>
</html>
