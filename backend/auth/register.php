<?php
require '../../config/db_connect.php';

$name = htmlspecialchars($_POST["name"]);
$email = htmlspecialchars($_POST["email"]);
$password = password_hash($_POST["password"], PASSWORD_DEFAULT);

$check_email = $conn->prepare("SELECT * FROM users WHERE email = ?");
$check_email->bind_param("s", $email);
$check_email->execute();
$result = $check_email->get_result();

if ($result->num_rows > 0) {
    $_SESSION["error"] = "Email already registered!";
    header("Location: ../../frontend/signup.php");
    exit();
} else {
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);
    if ($stmt->execute()) {
        $_SESSION["message"] = "Registration successful! You can log in now.";
        header("Location: ../../frontend/index.php");
        exit();
    } else {
        $_SESSION["error"] = "Registration failed. Try again!";
    }
}
?>
