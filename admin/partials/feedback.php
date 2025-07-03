<?php
require __DIR__ . '/../../config/db_connect.php';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $conn->query("DELETE FROM feedback WHERE id = $delete_id");
}
?>

<div class="container my-5 p-4 bg-white rounded shadow-sm">
    <!-- Back Button -->
    <button onclick="loadContent('partials/dashboard_home.php')" class="btn btn-outline-primary btn-back mb-3">
        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
    </button>

    <h2 class="text-center mb-4 text-primary">Manage Feedback</h2>

    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
            <thead style="background-color: #002D72; color: white;">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Submitted At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $feedbacks = $conn->query("SELECT * FROM feedback ORDER BY created_at DESC");
                if ($feedbacks && $feedbacks->num_rows > 0):
                    while ($row = $feedbacks->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <?php
                        $rating = (int) $row['rating'];
                        if ($rating > 0) {
                            // Show stars
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating
                                    ? '<i class="fas fa-star text-warning"></i>'
                                    : '<i class="far fa-star text-muted"></i>';
                            }
                            // Fallback number next to stars
                            echo "<br><small>Rating: {$rating}</small>";
                        } else {
                            echo '<span class="text-muted">No rating</span>';
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['review']) ?></td>
                    <td><?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="7" class="text-muted">No feedback available.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
