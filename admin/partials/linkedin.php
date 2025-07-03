<?php
require_once('../../config/db_connect.php');

// Check if the database connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch LinkedIn profiles from database, selecting only the required columns
// We select id, username, and created_at based on the table structure and your request
$sql = "SELECT id, username, created_at FROM linkedin_data ORDER BY created_at DESC";
$result = $conn->query($sql);

// Check for query errors
if ($result === false) {
    die("Error executing query: " . $conn->error);
}

?>

<style>
  /* Styles for the main content area */
  .content {
    flex-grow: 1;
    /* Removed padding here, spacing handled by .resume-container */
    overflow-y: auto;
    margin-left: 260px; /* Pushes content away from fixed sidebar */
    padding: 0 20px; /* Add some horizontal padding directly to content if needed outside the container margin */
  }


  /* Styles from the provided theme for the container */
  .resume-container {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    padding: 30px;
    margin: 30px auto; /* Use auto for left/right margin to center within content if there's extra space */
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
    max-width: calc(100% - 60px); /* Ensure container doesn't exceed available width minus its own margins */
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
    overflow: hidden; /* Ensures rounded corners are applied to content */
    width: 100%; /* Make the table take the full width of its container */
    margin-bottom: 0; /* Remove default table bottom margin if any */
  }

  .custom-table thead th { /* Target th within thead of custom-table */
     background-color: #003d80; /* Dark blue header background */
     color: white;
     border-color: #003d80; /* Match border color to background */
  }

   .custom-table th,
   .custom-table td {
     padding: 12px 15px; /* Adjust padding for cells */
     border-bottom: 1px solid #dee2e6; /* Add subtle bottom border */
     word-break: break-word; /* Allow long words to break and wrap */
   }

    .custom-table tbody tr:last-child td {
      border-bottom: none; /* Remove bottom border for the last row */
    }

  .custom-table td {
    vertical-align: middle; /* Vertically align cell content */
  }

  /* Specific styling for the delete button */
  .btn-danger.btn-sm {
    font-size: 14px;
    padding: 5px 10px;
    /* Add any other specific button styles if needed */
  }

  /* Styling for the info alert */
  .alert-info {
    margin: 10px 0 20px;
  }

  /* Override default Bootstrap table-dark border if needed */
  .table-dark {
      --bs-table-bg: #003d80; /* Ensure Bootstrap variable uses the desired color */
      --bs-table-border-color: #003d80; /* Match border color */
  }

  /* Ensure table-responsive works correctly */
  .table-responsive {
      overflow-x: auto; /* Make the container scroll horizontally */
      -webkit-overflow-scrolling: touch; /* Improve scrolling on touch devices */
  }

</style>

<div class="container resume-container"> <h3 class="mb-4 resume-title">📎 LinkedIn Profiles</h3> <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive"> <table class="table table-bordered table-hover align-middle custom-table"> <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Uploaded On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr id="row-<?= $row['id'] ?>">
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="deleteLinkedIn(<?= $row['id'] ?>)">🗑️ Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No LinkedIn profiles found.</div>
    <?php endif; ?>

    <?php
    // Close the database connection
    $conn->close();
    ?>
</div>

<script>
function deleteLinkedIn(id) {
    if (confirm("Are you sure you want to delete this LinkedIn profile?")) {
        fetch(`../backend/delete_linkedin.php?id=${id}`, {
            method: 'GET'
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.text();
        })
        .then(response => {
            response = response.trim();
            if (response === 'success') {
                const rowElement = document.getElementById(`row-${id}`);
                if (rowElement) {
                    rowElement.remove();
                } else {
                    console.warn(`Row element with id 'row-${id}' not found.`);
                }
            } else {
                alert("Failed to delete profile. Server response: " + response);
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            alert("An error occurred while trying to delete the profile.");
        });
    }
}
</script>