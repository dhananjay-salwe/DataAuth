<?php
require '../../config/db_connect.php';

$email = htmlspecialchars($_POST["email"]);
$password = $_POST["password"];

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user["password"])) {
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["user_name"] = $user["name"];
    header("Location: ../../frontend/dashboard.php");
    exit();
} else {
    $_SESSION["error"] = "Invalid email or password!";
}
?>
