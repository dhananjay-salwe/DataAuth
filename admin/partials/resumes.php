<?php
include '../../config/db_connect.php';

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
        if (file_exists("../../uploads/" . $filename)) {
            unlink("../../uploads/" . $filename);
        }

        // Delete the record from the database
        $sql = "DELETE FROM resumes WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            echo "<div class='alert alert-success'>Resume deleted successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error deleting record: " . mysqli_error($conn) . "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Error: Resume not found!</div>";
    }
}

// Fetch all resumes
$sql = "SELECT * FROM resumes ORDER BY upload_date DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "<div class='alert alert-danger'>Query failed: " . mysqli_error($conn) . "</div>";
    exit();
}
?>

<style>
  .resume-container {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    padding: 30px;
    margin: 30px;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
  }

  .resume-title {
    text-align: center;
    font-size: 28px;
    color: #004080;
    font-weight: 600;
    margin-bottom: 20px;
  }

  .custom-table {
    background-color: white;
    border-radius: 12px;
    overflow: hidden;
  }

  .custom-table th {
    background-color: #003d80;
    color: white;
  }

  .custom-table td {
    vertical-align: middle;
  }

  .btn-danger.btn-sm {
    font-size: 14px;
    padding: 5px 10px;
  }

  .btn-back {
    margin-bottom: 20px;
    font-weight: 500;
  }

  .alert {
    margin: 10px 0 20px;
  }
</style>

<div class="resume-container">
  <h2 class="resume-title">Manage Resumes</h2>

  <!-- Back Button -->
  <button onclick="loadContent('partials/dashboard_home.php')" class="btn btn-outline-primary btn-back mb-3">
  <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
</button>


  <div id="resume-alerts"></div>

  <table class="table table-bordered custom-table">
    <thead>
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
            <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this resume?')">Delete</a>
          </td>
        </tr>
      <?php } } ?>
    </tbody>
  </table>
</div>

<script>
  function goBackToDashboard() {
    document.getElementById("main-content").innerHTML = `
      <h2><span class='emoji'>👋</span>Welcome, Admin!</h2>
      <p>Select an option from the sidebar to manage content.</p>
    `;
  }
</script>
