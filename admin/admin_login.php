<?php
session_start();
include("../config/db_connect.php");

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_username = $_POST["admin_username"];
    $admin_password = $_POST["admin_password"];

    // Check credentials (For now, use hardcoded credentials)
    $correct_username = "admin";
    $correct_password = "admin123"; // Later, store in DB securely

    if ($admin_username === $correct_username && $admin_password === $correct_password) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid Username or Password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
    background: url(./asset/admin-bg.jpg) no-repeat center center fixed;
    background-size: cover;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-container {
    background: rgba(255, 255, 255, 0.15); /* Light transparency */
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.4); /* Subtle glow */
    width: 350px;
    backdrop-filter: blur(15px); /* Glassmorphism effect */
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.login-container h2 {
    text-align: center;
    color: #ffffff;
    font-weight: bold;
}

.input-field {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.4);
    color: white;
}

.input-field::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.btn-login {
    background-color: #007bff;
    color: white;
    width: 100%;
    border: none;
    padding: 10px;
    font-size: 16px;
    transition: 0.3s ease;
    border-radius: 5px;
}

.btn-login:hover {
    background-color: #0056b3;
}

/* Password Toggle */
.password-container {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 1.2rem;
    color: white;
    opacity: 0.7;
}

.toggle-password:hover {
    opacity: 1;
}

    </style>
</head>
<body>

<div class="login-container">
    <h2>Admin Login</h2>
    <form action="admin_auth.php" method="POST">
        <div class="mb-3">
            <label class="form-label">Username:</label>
            <input type="email" class="form-control" name="username" placeholder="Enter email" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password:</label>
            <div class="password-container">
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
            </div>
        </div>
        <button type="submit" class="btn btn-login">Login</button>
    </form>
</div>

<script>
    document.getElementById("togglePassword").addEventListener("click", function() {
        let passwordInput = document.getElementById("password");
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            this.classList.remove("bi-eye-slash");
            this.classList.add("bi-eye");
        } else {
            passwordInput.type = "password";
            this.classList.remove("bi-eye");
            this.classList.add("bi-eye-slash");
        }
    });
</script>

</body>
</html>


