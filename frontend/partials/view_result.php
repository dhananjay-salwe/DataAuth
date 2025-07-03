<?php
// Ensure errors are displayed for debugging (remove or adjust for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Make sure this path is correct and the file exists
include_once('../../config/db_connect.php');

// Check if the database connection was successful
if (!$conn) {
    // Display a user-friendly error message and stop execution
    die("Database connection failed: " . mysqli_connect_error());
}

// --- Helper Functions ---

function normalize_string(?string $str): string {
    // Ensure the input is treated as UTF-8 if applicable
    // $str = mb_strtolower($str ?? '', 'UTF-8'); // Consider if you have non-ASCII characters
    return trim(strtolower($str ?? ''));
}

function calculate_string_similarity_percent(?string $str1, ?string $str2): float {
    $str1_norm = normalize_string($str1);
    $str2_norm = normalize_string($str2);
    if ($str1_norm === '' && $str2_norm === '') { return 100.0; }
    if ($str1_norm === '' || $str2_norm === '') { return 0.0; }
    $percent = 0.0;
    // Note: similar_text might be computationally expensive for very long strings
    similar_text($str1_norm, $str2_norm, $percent);
    return round($percent, 2);
}

function compare_skills_percent(?string $skills1_str, ?string $skills2_str): float {
    $skills1_norm = normalize_string($skills1_str);
    $skills2_norm = normalize_string($skills2_str);
    if ($skills1_norm === '' && $skills2_norm === '') { return 100.0; }
    if ($skills1_norm === '' || $skills2_norm === '') { return 0.0; }

    // Use a robust regex for splitting, accounts for various whitespace/delimiters
    $arr1 = preg_split('/[,\n;]+|\s{2,}/', $skills1_norm, -1, PREG_SPLIT_NO_EMPTY);
    $arr2 = preg_split('/[,\n;]+|\s{2,}/', $skills2_norm, -1, PREG_SPLIT_NO_EMPTY);

    // Trim items and filter out any potential empty strings after split
    $arr1 = array_filter(array_map('trim', $arr1));
    $arr2 = array_filter(array_map('trim', $arr2));

    // Make items unique within each list
    $arr1 = array_unique($arr1);
    $arr2 = array_unique($arr2);

    if (empty($arr1) && empty($arr2)) { return 100.0; }
    if (empty($arr1) || empty($arr2)) { return 0.0; }

    $intersection = array_intersect($arr1, $arr2);
    $union = array_unique(array_merge($arr1, $arr2));

    if (count($union) == 0) return 0.0; // Avoid division by zero

    $jaccardIndex = count($intersection) / count($union);
    return round($jaccardIndex * 100, 2);
}


// --- Main Logic ---

$latestSession = null;
$resume = null;
$linkedin = null;
$errorMessage = null;
$matchScores = ['full_name' => 0.0, 'skills' => 0.0, 'education' => 0.0, 'experience' => 0.0];
$totalMatch = 0.0;

// 1. Get the latest session_id that exists in BOTH resumes and linkedin_data
// Ensure table and column names match your database exactly
$sessionQuery = "
    SELECT r.session_id
    FROM resumes r
    INNER JOIN linkedin_data l ON r.session_id = l.session_id
    ORDER BY r.id DESC
    LIMIT 1";

$sessionStmt = mysqli_prepare($conn, $sessionQuery);
if ($sessionStmt) {
    if(mysqli_stmt_execute($sessionStmt)) {
        $sessionResult = mysqli_stmt_get_result($sessionStmt);
        $sessionRow = mysqli_fetch_assoc($sessionResult); // Fetches the row or null
        $latestSession = $sessionRow['session_id'] ?? null; // Use null coalescing operator
    } else {
        $errorMessage = "Error executing session query: " . mysqli_stmt_error($sessionStmt);
    }
    mysqli_stmt_close($sessionStmt);
} else {
    // More specific error for prepare failure
    $errorMessage = "Error preparing session query: " . mysqli_error($conn);
}

// 2. If a session was found, fetch data for that session
if ($latestSession && !$errorMessage) {
    // Fetch resume data
    // Ensure table and column names match your database exactly
    $resumeQuery = "SELECT full_name, skills, education, experience FROM resumes WHERE session_id = ? ORDER BY id DESC LIMIT 1"; // Added ORDER BY id DESC just in case multiple exist for session
    $resumeStmt = mysqli_prepare($conn, $resumeQuery);
    if ($resumeStmt) {
        mysqli_stmt_bind_param($resumeStmt, "s", $latestSession);
        if(mysqli_stmt_execute($resumeStmt)) {
            $resumeResult = mysqli_stmt_get_result($resumeStmt);
            $resume = mysqli_fetch_assoc($resumeResult); // Assigns row array or null
        } else {
             $errorMessage = "Error executing resume query: " . mysqli_stmt_error($resumeStmt);
        }
        mysqli_stmt_close($resumeStmt);
    } else {
        $errorMessage = "Error preparing resume query: " . mysqli_error($conn);
    }

    // Fetch LinkedIn data only if resume query was successful so far
    if (!$errorMessage) {
         // Ensure table and column names match your database exactly
        $linkedinQuery = "SELECT full_name, skills, education, experience FROM linkedin_data WHERE session_id = ? ORDER BY id DESC LIMIT 1"; // Added ORDER BY id DESC
        $linkedinStmt = mysqli_prepare($conn, $linkedinQuery);
        if ($linkedinStmt) {
            mysqli_stmt_bind_param($linkedinStmt, "s", $latestSession);
             if(mysqli_stmt_execute($linkedinStmt)) {
                $linkedinResult = mysqli_stmt_get_result($linkedinStmt);
                $linkedin = mysqli_fetch_assoc($linkedinResult); // Assigns row array or null
            } else {
                $errorMessage = "Error executing LinkedIn query: " . mysqli_stmt_error($linkedinStmt);
            }
            mysqli_stmt_close($linkedinStmt);
        } else {
            $errorMessage = "Error preparing LinkedIn query: " . mysqli_error($conn);
        }
    }

    // 3. Calculate Matches only if BOTH records were successfully fetched and no errors occurred
    if ($resume && $linkedin && !$errorMessage) {
        // Use isset checks for safety, though fetch_assoc should include keys even if value is null
        $matchScores['full_name'] = calculate_string_similarity_percent($resume['full_name'] ?? null, $linkedin['full_name'] ?? null);
        $matchScores['skills'] = compare_skills_percent($resume['skills'] ?? null, $linkedin['skills'] ?? null);
        $matchScores['education'] = calculate_string_similarity_percent($resume['education'] ?? null, $linkedin['education'] ?? null);
        $matchScores['experience'] = calculate_string_similarity_percent($resume['experience'] ?? null, $linkedin['experience'] ?? null);

        // Calculate total average match score
        // Filter out any potential non-numeric scores if functions were modified
        $validScores = array_filter($matchScores, 'is_numeric');
        if (count($validScores) > 0) {
            $totalMatch = array_sum($validScores) / count($validScores);
        } else {
             $totalMatch = 0.0; // Handle case where no valid scores found
        }

    } elseif (!$errorMessage) {
        // Refined error message if data is missing after queries supposedly succeeded
        if (!$resume && !$linkedin) {
             $errorMessage = "No Resume or LinkedIn data found for the latest matching Session ID: " . htmlspecialchars($latestSession);
        } elseif (!$resume) {
             $errorMessage = "No Resume data found for the latest matching Session ID: " . htmlspecialchars($latestSession);
        } elseif (!$linkedin) {
             $errorMessage = "No LinkedIn data found for the latest matching Session ID: " . htmlspecialchars($latestSession);
        }
        // If $errorMessage is already set, it will be shown instead
    }
    // If $errorMessage was set during queries, calculation is skipped and error is shown

} elseif (!$errorMessage) {
    // This message means the INNER JOIN found no common session_id
    $errorMessage = "No session found where both resume and LinkedIn data are present.";
}

// Only close connection if it was successfully established and is still open
if ($conn && mysqli_ping($conn)) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Results</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Styles as provided in previous snippet - condensed for brevity */
        .progress-circle { width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(from 0deg, #4caf50 0% calc(var(--p, 0) * 1%), #e6e6e6 calc(var(--p, 0) * 1%) 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 20px auto; position: relative; font-size: 16px; transition: background 0.3s ease; }
        .progress-circle strong { font-size: 24px; font-weight: bold; color: #333; }
        .progress-circle span { font-size: 14px; color: #555; margin-top: 5px; text-align: center; }
        .match-results { padding: 30px; background-color: #f8f9fa; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .match-results h2 { margin-bottom: 30px; color: #343a40; }
        .match-results h4 { color: #343a40; font-weight: bold; }
        .error-message { padding: 15px; margin: 20px; border-radius: 4px; text-align: center; }
        .debug-data { margin-top: 40px; padding: 20px; background-color: #f0f0f0; border: 1px solid #ccc; border-radius: 8px; }
        .debug-data h4 { margin-top: 0; margin-bottom: 15px; color: #17a2b8; }
        .debug-data h5 { margin-top: 15px; margin-bottom: 5px; color: #333; }
        .debug-data pre { background-color: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.9em; }
        .alert-warning { color: #856404; background-color: #fff3cd; border-color: #ffeeba; padding: .75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: .25rem; }
    </style>
</head>
<body>

<div class="container mt-4">
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger error-message" role="alert">
            <strong>Error:</strong> <?php echo htmlspecialchars($errorMessage); // Sanitize error message ?>
             <?php if ($latestSession && strpos($errorMessage, 'Session ID:') === false) { echo "<br><small>Checked Session ID: " . htmlspecialchars($latestSession) . "</small>"; } ?>
        </div>
    <?php elseif (!$resume && !$linkedin): ?>
         <div class="alert alert-warning error-message" role="alert">
            No data found to display results. Please upload a resume and fetch LinkedIn data.
             <?php if ($latestSession) { echo "<br><small>(Attempted Session ID: " . htmlspecialchars($latestSession) . ")</small>"; } ?>
        </div>
    <?php else: // Only show results if no error AND at least one data source exists ?>
        <div class="match-results">
            <h2 class="text-center">Resume vs LinkedIn Match</h2>
            <div class="row text-center">
                <?php foreach ($matchScores as $key => $score):
                    $label = ucwords(str_replace('_', ' ', $key)); // Format key to label (e.g., full_name -> Full Name)
                ?>
                <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                    <div class="progress-circle" style="--p: <?= round($score) ?>;">
                        <strong><?= round($score) ?>%</strong>
                        <span><?php echo htmlspecialchars($label); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <h4>Total Match Score: <?= round($totalMatch) ?>%</h4>
            </div>
        </div>

        <div class="debug-data">
             <h4 class="text-center">Debugging Information</h4>
             <p class="text-center text-info"><small>Session ID Checked: <?php echo htmlspecialchars($latestSession ?? 'N/A'); ?></small></p>
             <div class="row mt-3">
                <div class="col-md-6 mb-3">
                    <h5>Resume Data (Normalized)</h5>
                    <?php if ($resume): ?>
                    <pre><?php
                        // Use print_r inside htmlspecialchars for safe display
                        echo htmlspecialchars(print_r([
                            'full_name'  => normalize_string($resume['full_name'] ?? null),
                            'skills'     => normalize_string($resume['skills'] ?? null),
                            'education'  => normalize_string($resume['education'] ?? null),
                            'experience' => normalize_string($resume['experience'] ?? null),
                        ], true));
                    ?></pre>
                    <?php else: ?>
                    <div class="alert alert-warning">Resume data not found for this session.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <h5>LinkedIn Data (Normalized)</h5>
                     <?php if ($linkedin): ?>
                     <pre><?php
                         echo htmlspecialchars(print_r([
                            'full_name'  => normalize_string($linkedin['full_name'] ?? null),
                            'skills'     => normalize_string($linkedin['skills'] ?? null),
                            'education'  => normalize_string($linkedin['education'] ?? null),
                            'experience' => normalize_string($linkedin['experience'] ?? null),
                        ], true));
                     ?></pre>
                     <?php else: ?>
                     <div class="alert alert-warning">LinkedIn data not found for this session.</div>
                     <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>