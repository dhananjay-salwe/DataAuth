<?php
include '../config/db_connect.php';

// Debug: Check if the database connection is working
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Get file name from the database
    $query = "SELECT filename FROM resumes WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $filename = $row['filename'];

        // Delete the file from the uploads folder
        if (file_exists("../uploads/" . $filename)) {
            unlink("../uploads/" . $filename);
        }

        // Delete the record from the database
        $sql = "DELETE FROM resumes WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            header("Location: manage_resumes.php?success=deleted");
            exit();
        } else {
            echo "Error deleting record: " . mysqli_error($conn);
        }
    } else {
        echo "Error: Resume not found!";
    }
}

// Fetch all resumes
$sql = "SELECT * FROM resumes ORDER BY upload_date DESC";
$result = mysqli_query($conn, $sql);

// Debug: Check if data is fetched
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resumes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="text-primary text-center">Manage Resumes</h2>

    <?php if (isset($_GET['success'])) { ?>
        <div class="alert alert-success">Resume deleted successfully!</div>
    <?php } ?>

    <table class="table table-bordered table-striped mt-3">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Filename</th>
                <th>Upload Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            while ($row = mysqli_fetch_assoc($result)) { 
                if (empty($row['filename'])) {
                    echo "<tr><td colspan='4' class='text-center text-danger'>Error: Filename is missing!</td></tr>";
                } else {
            ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['filename']) ?></td>
                    <td><?= date("d M Y, h:i A", strtotime($row['upload_date'])) ?></td>
                    <td>
                        <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php } } ?>
        </tbody>
    </table>

    <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

</body>
</html>
