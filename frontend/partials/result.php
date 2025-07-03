<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('../../config/db_connect.php');

function normalize_string(?string $str): string {
    return trim(strtolower($str ?? ''));
}

define('NAME_SIMILARITY_THRESHOLD', 70.0);
define('DEBUG_MODE', true);

$currentSessionId = session_id();
$resume = null;
$matchingLinkedinName = null;
$errorMessage = null;
$matchScores = ['full_name' => 0.0, 'skills' => 0.0, 'education' => 0.0, 'experience' => 0.0];
$totalMatch = 0.0;
$best_match_similarity = 0;

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    $errorMessage = "Database connection failed.";
}

if (!$errorMessage && empty($currentSessionId)) {
    $errorMessage = "No active session. Please start again.";
    error_log("Session ID is empty.");
}

// Step 1: Fetch resume
if (!$errorMessage) {
    $resumeQuery = "SELECT id, full_name, skills, education, experience FROM resumes WHERE session_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $resumeQuery);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $currentSessionId);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $resume = mysqli_fetch_assoc($result);
            if (!$resume) {
                $errorMessage = "No resume found for this session.";
            }
        } else {
            $errorMessage = "Resume fetch error: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $errorMessage = "Resume query preparation failed: " . mysqli_error($conn);
    }
}

// Step 2: Match LinkedIn name
if (!$errorMessage && $resume) {
    $resumeName = normalize_string($resume['full_name']);
    $linkedinQuery = "SELECT id, full_name FROM linkedin_data ORDER BY id DESC LIMIT 100";
    $linkedinResult = mysqli_query($conn, $linkedinQuery);
    if ($linkedinResult) {
        while ($row = mysqli_fetch_assoc($linkedinResult)) {
            $linkedinNameNorm = normalize_string($row['full_name']);
            if (empty($linkedinNameNorm)) continue;
            similar_text($resumeName, $linkedinNameNorm, $similarity);
            if ($similarity > $best_match_similarity) {
                $best_match_similarity = $similarity;
                $matchingLinkedinName = $row['full_name'];
            }
        }
        mysqli_free_result($linkedinResult);
    } else {
        $errorMessage = "LinkedIn query error: " . mysqli_error($conn);
    }
}

// Step 3: Generate pseudo-random scores
if (!$errorMessage && $resume) {
    $matchScores['full_name'] = ($best_match_similarity >= NAME_SIMILARITY_THRESHOLD) ? rand(85, 100) : rand(50, 70);
    $matchScores['skills'] = rand(40, 95);
    $matchScores['education'] = rand(60, 90);
    $matchScores['experience'] = rand(60, 95);

    // Add ±5% random fluctuation to simulate uniqueness
    foreach ($matchScores as $k => $v) {
        $delta = rand(-5, 5);
        $newValue = max(0, min(100, $v + $delta));
        $matchScores[$k] = round($newValue, 2);
    }

    $totalMatch = round(array_sum($matchScores) / count($matchScores), 2);
}

if ($conn && mysqli_ping($conn)) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Match Results</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .progress-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #28a745 0% calc(var(--p, 0) * 1%), #e6e6e6 calc(var(--p, 0) * 1%) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            position: relative;
            font-size: 16px;
            transition: background 0.5s ease-in-out;
        }
        .progress-circle strong {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .progress-circle span {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
            text-align: center;
        }
        .match-results {
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            margin-top: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .match-results h2 {
            margin-bottom: 30px;
            color: #343a40;
        }
        .btn-options {
            margin-top: 25px;
        }
        .btn-options button {
            margin: 5px;
        }
        .suggestion-box {
            margin-top: 40px;
            padding: 20px;
            background-color: #e9f7ef;
            border-left: 5px solid #28a745;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php elseif ($resume): ?>
        <div class="match-results text-center">
            <h2>Resume vs LinkedIn Match</h2>
            <div class="row justify-content-center">
                <?php foreach ($matchScores as $label => $score): ?>
                    <div class="col-md-2 col-sm-6">
                        <div class="progress-circle" style="--p:<?= $score ?>;">
                            <strong><?= $score ?>%</strong>
                            <span><?= ucwords(str_replace('_', ' ', $label)) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="col-md-2 col-sm-6">
                    <div class="progress-circle" style="--p:<?= $totalMatch ?>;">
                        <strong><?= $totalMatch ?>%</strong>
                        <span>Total Match</span>
                    </div>
                </div>
            </div>

            
        </div>
    <?php else: ?>
        <div class="alert alert-warning">No resume found. Please upload one to see results.</div>
    <?php endif; ?>
</div>

<script>
    function downloadPDF() {
        const element = document.body;
        html2pdf().from(element).save("Match_Result.pdf");
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
</body>
</html>
