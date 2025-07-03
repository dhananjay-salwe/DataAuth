<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard - DataAuth</title>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
body {
  margin: 0;
  font-family: 'Segoe UI', sans-serif;
  background: linear-gradient(145deg, #e6f0ff, #ffffff);
  min-height: 100vh;
  display: flex;
  /* Remove overflow-y: auto from body */
}

/* Sidebar Styling */
.sidebar {
  background: linear-gradient(200deg, #00264d, #0059b3);
  color: white;
  width: 260px;
  padding: 2rem 1.2rem;
  height: 100vh; /* Keep height 100vh to fill the screen vertically */
  box-shadow: 4px 0 20px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border-right: 1px solid rgba(255, 255, 255, 0.1);
  /* Add fixed positioning */
  position: fixed;
  top: 0;
  left: 0;
  /* Ensure it's above the content */
  z-index: 1000; /* Adjust z-index if needed */
}

.sidebar h4 {
  font-weight: 700;
  font-size: 1.8rem;
  text-align: center;
  color: #fff;
  margin-bottom: 2.5rem;
  letter-spacing: 1px;
  text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.3);
}

.sidebar a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 18px;
  margin: 12px 0;
  font-size: 1.05rem;
  font-weight: 500;
  color: #e0e0e0;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  transition: all 0.3s ease;
  text-decoration: none !important;
  letter-spacing: 0.5px;
  box-shadow: inset 0 0 0 transparent;
}

.sidebar a i {
  font-size: 1.2rem;
  color: #ffffffcc;
  transition: transform 0.3s ease, color 0.3s ease;
}

.sidebar a:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  text-decoration: none !important;
  box-shadow: inset 0 0 8px rgba(255, 255, 255, 0.15);
  transform: translateX(6px);
}

.sidebar a:hover i {
  transform: scale(1.15);
  color: #ffffcc;
}


.logout-btn {
  text-align: center;
}

.logout-btn a {
  padding: 10px 25px;
  border-radius: 8px;
  font-weight: bold;
  background-color: #ff4d4d;
  color: #fff;
  border: none;
  transition: background-color 0.3s ease, transform 0.2s ease;
}

.logout-btn a:hover {
  background-color: #cc0000;
  transform: scale(1.05);
}

/* Main Content */
.content {
  flex-grow: 1;
  padding: 2rem;
  overflow-y: auto;
  /* Add margin to the left equal to the sidebar width */
  margin-left: 260px;
}

  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column justify-content-between">
    <div>
      <h4>DataAuth Admin</h4>
      <a href="#" onclick="loadContent('partials/resumes.php')"><i class="fas fa-file-alt me-2"></i>Manage Resumes</a>
      <a href="#" onclick="loadContent('partials/linkedin.php')"><i class="fas fa-link me-2"></i>Manage LinkedIn URLs</a>
      <a href="#" onclick="loadContent('partials/feedback.php')"><i class="fas fa-comments me-2"></i>Manage Feedback</a>
    </div>
    <div class="logout-btn">
      <a href="admin_logout.php" class="btn btn-danger">Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="content" id="main-content">
    <h2><span class="emoji">👋</span>Welcome, Admin!</h2>
    <p>Select an option from the sidebar to manage content.</p>
  </div>

  <!-- Scripts -->
  <script>
    function loadContent(url) {
      fetch(url)
        .then(response => response.text())
        .then(data => {
          document.getElementById("main-content").innerHTML = data;
        })
        .catch(err => {
          document.getElementById("main-content").innerHTML = `<div class='alert alert-danger'>Error loading content.</div>`;
          console.error(err);
        });
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
