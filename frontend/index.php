<?php
session_start();
require '../config/db_connect.php'; // Ensure path is correct

// --- Login Handling (Keep as is, but ensure login.php is secure) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Consider adding CSRF protection if this is handling form posts
    require '../backend/auth/login.php'; // Ensure path is correct
}

// --- Session/GET Flags (Keep as is, use them later in your HTML/UI) ---
$resumeUploaded = isset($_SESSION['resume_uploaded']) && $_SESSION['resume_uploaded'];
$resumeName = isset($_SESSION['uploaded_resume_name']) ? htmlspecialchars($_SESSION['uploaded_resume_name']) : ''; // Sanitize output

$linkedinFetched = isset($_GET['linkedin_success']) && $_GET['linkedin_success'] == 1;
$linkedinUrl = isset($_GET['linkedin_url']) ? htmlspecialchars($_GET['linkedin_url']) : ''; // Sanitize output

// --- Fetch Data Associated with the CURRENT Session ---

$resumeData = null;
$linkedinData = null;
$currentSessionId = session_id(); // Get the ID for the current user's session

if (!empty($currentSessionId)) {
    // Use prepared statements for security and clarity

    // Fetch the latest resume FOR THIS SESSION
    $resumeSql = "SELECT * FROM resumes WHERE session_id = ? ORDER BY upload_date DESC LIMIT 1";
    $resumeStmt = $conn->prepare($resumeSql);
    if ($resumeStmt) {
        $resumeStmt->bind_param("s", $currentSessionId);
        $resumeStmt->execute();
        $resumeResult = $resumeStmt->get_result();
        if ($resumeResult->num_rows > 0) {
            $resumeData = $resumeResult->fetch_assoc();
        }
        $resumeStmt->close();
    } else {
        // Optional: Log error - echo "Error preparing resume query: " . $conn->error;
    }

    // Fetch the latest LinkedIn data FOR THIS SESSION
    $linkedinSql = "SELECT * FROM linkedin_data WHERE session_id = ? ORDER BY created_at DESC LIMIT 1";
    $linkedinStmt = $conn->prepare($linkedinSql);
    if ($linkedinStmt) {
        $linkedinStmt->bind_param("s", $currentSessionId);
        $linkedinStmt->execute();
        $linkedinResult = $linkedinStmt->get_result();
        if ($linkedinResult->num_rows > 0) {
            $linkedinData = $linkedinResult->fetch_assoc();
        }
        $linkedinStmt->close();
    } else {
         // Optional: Log error - echo "Error preparing LinkedIn query: " . $conn->error;
    }

} else {
    // Optional: Handle case where session ID is missing - echo "Error: Invalid session.";
}

// Determine if results can be shown (i.e., both resume AND LinkedIn data were found for THIS session)
$showResult = ($resumeData !== null && $linkedinData !== null);

// $conn should be closed at the very end of the script execution,
// possibly after the HTML part of the page is generated.
// $conn->close(); // Don't close it here if you need it later on the page

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DataAuth - Resume Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: #f8f9fa;
        }
        .navbar-brand{
            font-family: 'Poppins', sans-serif;
  font-size: 28px;
  font-weight: 700;
  color: white; /* Simple and high contrast */
  letter-spacing: 1px;
  text-transform: uppercase;
  padding: 0.5rem 1rem;
  transition: transform 0.3s ease;
  cursor: pointer;
        }
        .navbar-brand:hover{
            transform: scale(1.05);
            color: #ffd54f; /* Optional: yellow on hover for a nice contrast */
        }

        .navbar-nav {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding-right: 1rem;
}

.navbar-nav .nav-item {
  list-style: none;
}

.navbar-nav .nav-link {
  font-family: 'Poppins', sans-serif;
  font-size: 16px;
  font-weight: 500;
  color: white; /* Use white for visibility on blue background */
  text-decoration: none;
  position: relative;
  transition: color 0.3s ease;
  padding: 0.5rem 0;
}

.navbar-nav .nav-link::after {
  content: '';
  position: absolute;
  width: 0%;
  height: 2px;
  left: 0;
  bottom: 0;
  background-color: #ffd54f; /* Yellow underline on hover */
  transition: width 0.3s ease;
}

.navbar-nav .nav-link:hover {
  color: #ffd54f; /* Yellow text on hover */
}

.navbar-nav .nav-link:hover::after {
  width: 100%;
}

        .hero-section {
            background: linear-gradient(135deg, #0066ff, #003399);
            color: white;
            text-align: center;
            padding: 100px 20px;
            position: relative;
            overflow: hidden;
        }
        .hero-text {
            font-size: 2.5rem;
            font-weight: bold;
            animation: fadeInText 3s infinite alternate;
        }
        @keyframes fadeInText {
            0% { opacity: 0; transform: translateY(-10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .feature-icon {
            font-size: 40px;
            transition: transform 0.3s;
        }
        .feature-icon:hover {
            transform: scale(1.2);
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .step:hover {
            background: #e9ecef;
            transform: translateY(-5px);
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .footer {
            background: #007bff;
            color: white;
            text-align: center;
            padding: 15px;
        }
        .progress-circle {
            position: relative;
        }
        svg text {
            fill: #000;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">DataAuth</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="../admin/admin_login.php">Admin</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero-section">
    <div class="container">
        <h1 class="hero-text">Verify Your Resume's Integrity with Ease</h1>
        <p class="lead">Upload your resume or LinkedIn profile and get instant verification results.</p>
        <a href="#upload-section" class="btn btn-warning btn-lg">Get Started</a>
    </div>
</header>

<!-- Features -->
<section class="container text-center my-5">
    <h2 class="mb-4">Why Choose DataAuth?</h2>
    <div class="row">
        <div class="col-md-4">
            <i class="fas fa-shield-alt text-primary feature-icon"></i>
            <h4>Secure</h4>
            <p>Your data is encrypted and protected with the highest security standards.</p>
        </div>
        <div class="col-md-4">
            <i class="fas fa-bolt text-warning feature-icon"></i>
            <h4>Fast Processing</h4>
            <p>Get instant results in seconds with our optimized verification system.</p>
        </div>
        <div class="col-md-4">
            <i class="fas fa-check-circle text-success feature-icon"></i>
            <h4>Accurate</h4>
            <p>We ensure high accuracy in resume and LinkedIn data extraction.</p>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="container text-center my-5">
    <h2>How It Works</h2>
    <div class="row">
        <div class="col-md-4 step"><h4>1. Upload Resume</h4><p>Upload your resume in PDF format for verification.</p></div>
        <div class="col-md-4 step"><h4>2. Enter LinkedIn Profile</h4><p>Provide your LinkedIn profile link for data matching.</p></div>
        <div class="col-md-4 step"><h4>3. Get Results</h4><p>Instantly receive insights on resume authenticity.</p></div>
    </div>
</section>

<!-- Upload Section -->
<section id="upload-section" class="container my-5">
    <h2 class="text-center">Resume Integrity Checker</h2>
    <div class="row justify-content-center">
        <!-- Resume Upload -->
        <div class="col-md-5 mb-3">
            <div class="card p-4 text-center">
                <h4>Upload Resume</h4>
                <form id="resume-form" action="../backend/upload.php" method="post" enctype="multipart/form-data">
                    <input type="file" name="resume" class="form-control mb-3" required>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
                <?php if ($resumeUploaded): ?>
                    <div class="alert alert-success mt-3">✅ Resume uploaded: <?= htmlspecialchars($resumeName) ?></div>
                <?php endif; ?>
                <div id="resume-message"></div>
            </div>
        </div>

        <!-- LinkedIn Input -->
        <div class="col-md-5 mb-3">
            <div class="card p-4 text-center">
                <h4>Enter LinkedIn Profile</h4>
                <form id="linkedin-form" action="../backend/fetch_linkedin_data.php" method="post">
                    <input type="text" name="linkedin_url" class="form-control mb-3" placeholder="Enter LinkedIn Profile URL" value="<?= htmlspecialchars($linkedinUrl) ?>" required>
                    <button type="submit" class="btn btn-primary">Fetch LinkedIn Data</button>
                </form>
                <?php if ($linkedinFetched): ?>
                    <div class="alert alert-success mt-3">✅ LinkedIn data fetched for:<br><strong><?= htmlspecialchars($linkedinUrl) ?></strong></div>
                <?php endif; ?>
                <div id="linkedin-message"></div>
            </div>
        </div>
    </div>

    <div class="text-center">
  <button id="howResultBtn" class="btn btn-success mt-3">Result</button>
</div>

<!-- This is where the result will load -->
<div id="resultSection" class="mt-4"></div>

</section>

    <!-- desplay feedback section -->
<!-- Display Recent Feedback -->
<section class="container my-5">
    <h3 class="text-center mb-4">What Our Users Say</h3>
    <div class="row justify-content-center">
        <?php
        require '../config/db_connect.php';
        $query = "SELECT name, email, rating, review, created_at FROM feedback ORDER BY created_at DESC LIMIT 3";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 p-3 shadow-sm">
                <div class="mb-2">
                    <?php
                    $stars = intval($row['rating']);
                    for ($i = 0; $i < $stars; $i++) echo '<i class="fas fa-star text-warning"></i>';
                    for ($i = $stars; $i < 5; $i++) echo '<i class="far fa-star text-secondary"></i>';
                    ?>
                </div>
                <p class="fst-italic">"<?= htmlspecialchars($row['review']) ?>"</p>
                <div class="mt-2">
                    <strong><?= htmlspecialchars($row['name']) ?></strong><br/>
                    <?php if (!empty($row['email'])): ?>
                        <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small><br/>
                    <?php endif; ?>
                    <small class="text-muted"><?= date("F j, Y", strtotime($row['created_at'])) ?></small>
                </div>
            </div>
        </div>
        <?php
            endwhile;
        else:
        ?>
        <div class="col-12 text-center">
            <p class="text-muted">No feedback yet. Be the first to share your thoughts!</p>
        </div>
        <?php endif; ?>
    </div>
    
</section>

<!-- Feedback Section -->
<section class="container my-5">
    <h2 class="text-center mb-4">We Value Your Feedback!</h2>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4 shadow-sm">
                <div id="feedback-message"></div> <!-- To show success/error messages -->

                <form id="feedbackForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Your Name:</label>
                        <input type="text" name="name" id="name" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email (optional):</label>
                        <input type="email" name="email" id="email" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label for="rating" class="form-label">Rate Your Experience:</label>
                        <div id="rating" class="d-flex gap-2 justify-content-center">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="rating" id="rating<?= $i ?>" value="<?= $i ?>" required>
                                    <label class="form-check-label" for="rating<?= $i ?>">
                                        <i class="fas fa-star text-warning"></i>
                                    </label>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="review" class="form-label">Your Review:</label>
                        <textarea class="form-control" id="review" name="review" rows="4" placeholder="Share your thoughts..." required></textarea>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-success px-4">Submit Feedback</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 DataAuth : Resume Checker. All rights reserved.</p>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    let resumeUploaded = false;
    let linkedinFetched = false;

    // Handle Resume Upload via AJAX
    document.getElementById('resume-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const messageBox = document.getElementById('resume-message');
        messageBox.innerHTML = 'Uploading...';

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                resumeUploaded = true;
                messageBox.innerHTML = `<div class="alert alert-success">✅ ${data.message}</div>`;
            } else {
                resumeUploaded = false;
                messageBox.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
            }
            checkBothSuccess();
        })
       
    });

    // Handle LinkedIn Fetch via AJAX
    document.getElementById('linkedin-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const messageBox = document.getElementById('linkedin-message');
        messageBox.innerHTML = 'Fetching...';

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                linkedinFetched = true;
                messageBox.innerHTML = `<div class="alert alert-success">✅ ${data.message}</div>`;
            } else {
                linkedinFetched = false;
                messageBox.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
            }
            checkBothSuccess();
        })
       
    });
    
});
</script>


 <!-- new result btn  -->
 <script>
document.getElementById("howResultBtn").addEventListener("click", function () {
    fetch("partials/result.php")
        .then(response => response.text())
        .then(html => {
            document.getElementById("resultSection").innerHTML = html;
        })
        .catch(error => {
            console.error("Error loading result:", error);
            document.getElementById("resultSection").innerHTML = "<p>Error loading result.</p>";
        });
});
</script>

</body>
<script>
  document.getElementById("feedbackForm").addEventListener("submit", function (e) {
    e.preventDefault(); // Prevent page reload

    const formData = new FormData(this);

    fetch("../backend/submit_feedback.php", {
      method: "POST",
      body: formData,
    })
      .then(response => response.text())
      .then(data => {
        document.getElementById("feedback-message").innerHTML =
          "<div class='alert alert-success'>Thank you for your feedback!</div>";
        document.getElementById("feedbackForm").reset();
      })
      .catch(error => {
        document.getElementById("feedback-message").innerHTML =
          "<div class='alert alert-danger'>Something went wrong. Please try again.</div>";
        console.error("Feedback submission error:", error);
      });
  });
</script>

<script>
// Resume Upload AJAX
document.getElementById("resume-form").addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    fetch("../backend/auth/upload.php", {
        method: "POST",
        body: formData,
    })
        .then(res => res.text())
        .then(data => {
            document.getElementById("resume-message").innerHTML = "<div class='alert alert-success mt-3'>✅ Resume uploaded successfully.</div>";
        })
        .catch(error => {
            document.getElementById("resume-message").innerHTML = "<div class='alert alert-danger mt-3'>❌ Upload failed.</div>";
        });
});

// LinkedIn Fetch AJAX
document.getElementById("linkedin-form").addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    fetch("../backend/auth/fetch_linkedin_data.php", {
        method: "POST",
        body: formData,
    })
        .then(res => res.text())
        .then(data => {
            document.getElementById("linkedin-message").innerHTML = "<div class='alert alert-success mt-3'>✅ LinkedIn data fetched successfully.</div>";
        })
        .catch(error => {
            document.getElementById("linkedin-message").innerHTML = "<div class='alert alert-danger mt-3'>❌ Fetch failed.</div>";
        });
});

</script>

<script src="../assets/script.js"></script>

</html>

<?php
// Clear session flags
unset($_SESSION['resume_uploaded']);
unset($_SESSION['uploaded_resume_name']);
?>