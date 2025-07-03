<?php
session_start();
session_destroy();
header("Location: admin_login.php"); // Redirect to the admin login page
exit();
?>
