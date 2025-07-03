<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h2>Welcome, <?= $_SESSION["user_name"] ?>!</h2>
    <p>This is your dashboard.</p>
    <a href="logout.php" class="btn btn-danger">Logout</a>
</div>

<?php require 'footer.php'; ?>
</body>
</html>
