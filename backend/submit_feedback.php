<?php
include '../config/db_connect.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : null;
    $rating = intval($_POST['rating']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);

    $sql = "INSERT INTO feedback (name, email, rating, review) 
            VALUES ('$name', " . ($email ? "'$email'" : "NULL") . ", $rating, '$review')";

    if (mysqli_query($conn, $sql)) {
        echo "<div class='alert alert-success'>Thank you for your feedback!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
} else {
    echo "<div class='alert alert-warning'>Invalid request.</div>";
}
?>
