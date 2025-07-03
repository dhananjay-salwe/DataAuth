<?php
session_start(); // Ensure session is started

// --- Configuration ---
define('UPLOAD_DIR', __DIR__ . '/../uploads/'); // Use absolute path based on this script's dir
define('MAX_FILE_SIZE_MB', 5);
define('MAX_FILE_SIZE_BYTES', MAX_FILE_SIZE_MB * 1024 * 1024);
define('ALLOWED_MIME_TYPE', 'application/pdf');
// Set DEBUG_MODE to false in production to hide detailed DB errors from users
define('DEBUG_MODE', true); // SET TO FALSE IN PRODUCTION

// --- Error Reporting ---
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL); // Report all errors...
    ini_set('log_errors', 1); // ...but log them instead of displaying
    // Ensure 'error_log' in php.ini is set to a writable file path
}

// --- Response Header ---
header('Content-Type: application/json');

// --- Includes ---
// Ensure vendor autoload and DB connect paths are correct relative to *this* script's location
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_connect.php'; // Use require_once

// --- Check Database Connection ---
if (!$conn) {
    error_log("Database connection failed in upload.php"); // Log specific error
    echo json_encode(['success' => false, 'error' => 'Database connection error. Please try again later.']);
    exit;
}

// --- Use Statements ---
use Smalot\PdfParser\Parser;

// --- Function Definitions ---

/**
 * Attempts to extract a full name from resume text.
 */
function extractFullName(string $text): ?string
{
    // Normalize line endings, remove excessive blank lines, trim start/end
    $text = trim(preg_replace('/(\r\n|\r|\n){2,}/', "\n", $text));
    $lines = explode("\n", $text);

    // Check for explicit "Name:" pattern first (case-insensitive, multiline)
    if (preg_match('/^\s*Name\s*[:\-]\s*(.+)/im', $text, $matches)) {
        $potential_name = trim($matches[1]);
        if (strlen($potential_name) > 2 && strlen($potential_name) < 60 && preg_match('/[a-zA-Z]/', $potential_name)) {
             // Remove potential contact info sometimes appended after name on same line
             $potential_name = preg_replace('/(\s*\|.*|\s+•.*|\s+-\s+.*)/', '', $potential_name);
             // Further check: ensure it doesn't look like an email or common header
             if (!filter_var(trim($potential_name), FILTER_VALIDATE_EMAIL) && !preg_match('/(resume|curriculum|contact|email|phone|profile)/i', $potential_name)) {
                  return trim($potential_name);
             }
        }
    }

    // Check the first few lines (e.g., top 7) for likely name patterns
    $lines_to_check = array_slice($lines, 0, 7);
    $name_pattern = '/^([A-Z][a-zA-Z\'\-\.]+ H+(?:\s+[A-Z][a-zA-Z\'\-\.]+)+)$/u'; // More unicode friendly, allows middle initials/titles
    $all_caps_pattern = '/^([A-Z\'\-]+\s+[A-Z\'\-]+(?:\s+[A-Z\'\-]+)*)$/';

    foreach ($lines_to_check as $line) {
        $line = trim($line);
        $line_len = strlen($line);

        // Skip empty lines or lines clearly not names
        if (empty($line) || $line_len < 3 || $line_len > 60) continue; // Basic length check
        if (preg_match('/\d{4}/', $line)) continue; // Contains 4 consecutive digits (likely year/phone part)
        if (preg_match('/(resume|curriculum vitae|c\.v\.|portfolio|contact|email|e-mail|phone|mobile|tel|linkedin|github|address|website|profile)/i', $line)) continue;
        if (preg_match('/(university|institute|college|school|academy|b\.tech|m\.tech|b\.sc|m\.sc|ph\.d|bachelor|master|degree|student|gpa)/i', $line)) continue;
        if (filter_var($line, FILTER_VALIDATE_EMAIL)) continue;
        // Slightly more robust phone check (catches more variations, still heuristic)
        if (preg_match('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $line)) continue;
        if (str_word_count($line) > 5) continue; // Unlikely name if too many words

        // Check patterns
        if (preg_match($name_pattern, $line)) return $line;
        if (preg_match($all_caps_pattern, $line)) return $line;
    }

    // Fallback: Look for the shortest line in the first 3 lines that has at least two words? (Risky heuristic)
    // $first_lines = array_slice(array_filter(array_map('trim', $lines)), 0, 3);
    // usort($first_lines, fn($a, $b) => strlen($a) <=> strlen($b));
    // if (isset($first_lines[0]) && str_word_count($first_lines[0]) >= 2 && strlen($first_lines[0]) < 50) return $first_lines[0];

    return null; // Give up if no likely candidate found
}

/**
 * Extracts text section based on a starting keyword until the next likely section header or end of text.
 */
function extractSection(string $text, string $keyword): ?string
{
    // Expanded list of common headings
    $common_headings = [
        'Experience', 'Work Experience', 'Employment History', // Experience variations
        'Education', 'Academic Background', // Education variations
        'Skills', 'Technical Skills', 'Software Skills', 'Expertise', // Skills variations
        'Projects', 'Personal Projects', // Projects variations
        'Summary', 'Profile', 'Objective', // Summary variations
        'Awards', 'Honors', 'Achievements', // Awards variations
        'Publications', 'Presentations', // Publications variations
        'References', 'Recommendations',
        'Languages', 'Certifications', 'Licenses', 'Training', 'Courses',
        'Volunteer Experience', 'Activities', 'Interests'
    ];
    // Ensure keyword matching is case-insensitive later using 'i' flag
    $other_headings = array_filter($common_headings, fn($h) => strcasecmp($h, $keyword) !== 0);
    // Create a robust pattern for stop words (start of line, optional colon/space/newline)
    $stop_pattern = '(^\h*(' . implode('|', array_map('preg_quote', $other_headings, ['/'])) . ')\b.*$)'; // \h* = horizontal whitespace, \b=word boundary

    // Main pattern: Find keyword (case-insensitive, start of line), capture non-greedily until stop pattern or end of string
    $pattern = '/^\h*' . preg_quote($keyword, '/') . '\b.*?\n(.*?)(?=' . $stop_pattern . '|\Z)/ims';
     // `i` = case-insensitive, `m` = ^/$ match start/end of line, `s` = dot matches newline

    if (preg_match($pattern, $text, $matches)) {
        $section_text = trim($matches[1]);
        // Further cleanup: remove list bullets/numbering if desired (optional)
        // $section_text = preg_replace('/^[\s\*\-•]\s+/m', '', $section_text);
        $section_text = preg_replace('/(\r\n|\r|\n){3,}/', "\n\n", $section_text); // Reduce excessive newlines but keep paragraphs
        return $section_text;
    }
    return null; // Section keyword not found
}

// --- Main Script ---

// 1. Validate Request Method and File Presence
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'error' => "Invalid request method."]);
    exit;
}
if (!isset($_FILES["resume"]) || !is_uploaded_file($_FILES["resume"]["tmp_name"])) {
     echo json_encode(['success' => false, 'error' => "No resume file uploaded or upload error."]);
    exit;
}

$file = $_FILES["resume"];

// 2. Check Basic File Upload Errors
if ($file["error"] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => "File size exceeds server limit.",
        UPLOAD_ERR_FORM_SIZE  => "File size exceeds form limit.",
        UPLOAD_ERR_PARTIAL    => "File was only partially uploaded.",
        UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
        UPLOAD_ERR_NO_TMP_DIR => "Server configuration error (missing temp dir).",
        UPLOAD_ERR_CANT_WRITE => "Server configuration error (cannot write to disk).",
        UPLOAD_ERR_EXTENSION  => "Server configuration error (extension stopped upload).",
    ];
    $error_message = $upload_errors[$file["error"]] ?? "Unknown file upload error.";
    echo json_encode(['success' => false, 'error' => $error_message]);
    exit;
}

// 3. Validate File Size
if ($file["size"] > MAX_FILE_SIZE_BYTES) {
    echo json_encode(['success' => false, 'error' => "File size exceeds " . MAX_FILE_SIZE_MB . "MB limit."]);
    exit;
}
if ($file["size"] === 0) {
     echo json_encode(['success' => false, 'error' => "Uploaded file is empty."]);
    exit;
}


// 4. Validate MIME Type (More Reliable)
// Use try-catch for finfo functions in case file disappears or isn't readable
$file_type = null;
try {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $file_type = finfo_file($finfo, $file["tmp_name"]);
        finfo_close($finfo);
    }
} catch (Exception $e) {
    error_log("Finfo Error in upload.php: " . $e->getMessage());
     echo json_encode(['success' => false, 'error' => 'Could not determine file type.']);
     exit;
}

if ($file_type !== ALLOWED_MIME_TYPE) {
    echo json_encode(['success' => false, 'error' => "Only PDF files are allowed (detected type: " . htmlspecialchars($file_type ?: 'unknown') . ")."]);
    exit;
}

// 5. Prepare Upload Directory and Target Path
// Security: Ensure this directory is NOT web-accessible or configured to prevent script execution (e.g., via .htaccess `RemoveHandler .php .phtml .php3` and `php_flag engine off`)
$upload_dir = rtrim(UPLOAD_DIR, '/') . '/'; // Ensure trailing slash
if (!is_dir($upload_dir)) {
    // Attempt to create directory with permissions (0775 recommended)
    // Group ownership may need adjustment depending on server setup (e.g., www-data)
    if (!mkdir($upload_dir, 0775, true)) {
         error_log("Failed to create upload directory: " . $upload_dir);
         echo json_encode(['success' => false, 'error' => "Server error: Could not create storage directory."]);
         exit;
    }
}
if (!is_writable($upload_dir)) {
     error_log("Upload directory not writable: " . $upload_dir);
     echo json_encode(['success' => false, 'error' => "Server error: Storage directory not writable."]);
     exit;
}

// Generate unique name and target path
$file_original_name = basename($file["name"]); // Get original name for storage/reference
$file_extension = pathinfo($file_original_name, PATHINFO_EXTENSION); // Keep original extension (should be pdf)
$unique_basename = uniqid("resume_", true) . '.' . strtolower($file_extension);
$target_path = $upload_dir . $unique_basename;


// 6. Move Uploaded File
if (!move_uploaded_file($file["tmp_name"], $target_path)) {
    // Log detailed error if possible, provide generic message to user
    error_log("Failed to move uploaded file to: " . $target_path . " from " . $file["tmp_name"]);
    echo json_encode(['success' => false, 'error' => "Could not save uploaded file."]);
    exit;
}

// 7. Parse PDF Text
$text = '';
$parser = new Parser(); // Assuming Smalot\PdfParser
try {
    $pdf = $parser->parseFile($target_path);
    $text = $pdf->getText();
    if (empty(trim($text))) {
         // Handle cases where PDF is image-based or parsing yields no text
          @unlink($target_path); // Delete the empty/unparsable file
          echo json_encode(['success' => false, 'error' => "PDF parsing failed: No text content found. The PDF might contain only images."]);
          exit;
    }
    // Optional: Limit extracted text size if needed for database constraints
    // $text = mb_substr($text, 0, 65535); // Example: Limit to TEXT field size

} catch (Exception $e) {
     @unlink($target_path); // Clean up failed upload
     error_log("PDF Parsing Error (Smalot): " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => "Failed to parse PDF content: " . (DEBUG_MODE ? $e->getMessage() : 'Please ensure the PDF is valid.')]);
    exit;
} finally {
    // Ensure parser resources are potentially freed if applicable (depends on library)
    unset($pdf, $parser);
}

// 8. Extract Structured Data
$full_name = extractFullName($text);
$skills = extractSection($text, 'Skills');
$education = extractSection($text, 'Education');
$experience = extractSection($text, 'Experience');
// Add extraction for other sections here if desired

// 9. Get Session ID
$sessionId = session_id();
if (empty($sessionId)) {
     @unlink($target_path); // Clean up if session is invalid
    error_log("Upload attempt failed due to missing session ID.");
    echo json_encode(['success' => false, 'error' => "Your session is invalid or has expired. Please refresh and try again."]);
    exit;
}

// 10. Insert Extracted Data into Database
// Ensure table/column names are correct
$sql = "INSERT INTO resumes
        (filename, original_name, extracted_text, upload_date, session_id, full_name, skills, education, experience)
        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
     @unlink($target_path); // Clean up
    error_log("DB Prepare failed (resumes insert): " . $conn->error);
    echo json_encode(['success' => false, 'error' => "Database error (Code: R1). Please contact support."]);
    exit;
}

// Bind parameters carefully (s = string). Extracted text can be large.
// Ensure DB columns allow NULLs for extracted fields.
$stmt->bind_param("ssssssss",
    $unique_basename,       // Use the generated unique name
    $file_original_name,    // Store the original name
    $text,                  // Full extracted text (potentially large)
    $sessionId,             // Link to the user session
    $full_name,             // Extracted name (or null)
    $skills,                // Extracted skills (or null)
    $education,             // Extracted education (or null)
    $experience             // Extracted experience (or null)
);

if (!$stmt->execute()) {
     @unlink($target_path); // Clean up
    error_log("DB Execute failed (resumes insert): " . $stmt->error . " | Session: " . $sessionId);
    echo json_encode(['success' => false, 'error' => "Database error (Code: R2). Please try again."]);
    // Consider specific error codes like 1062 for duplicate entry if relevant
    $stmt->close();
    // Only close $conn if this is the end of the script and it won't be needed further
    // $conn->close();
    exit;
}

$inserted_id = $stmt->insert_id; // Get the ID of the inserted row

$stmt->close();
// Optionally close $conn here if it's not needed anymore in this request lifecycle
// $conn->close();


// 11. Set session flags for UI feedback (optional)
$_SESSION['resume_uploaded'] = true;
$_SESSION['uploaded_resume_name'] = $file_original_name;

// 12. Success Response
echo json_encode([
    'success' => true,
    'message' => "Resume uploaded and parsed successfully.",
    'resume_id' => $inserted_id,
    'original_name' => htmlspecialchars($file_original_name),
    'unique_name' => htmlspecialchars($unique_basename),
    'extracted' => [ // Send back what was extracted (or null)
        'full_name' => $full_name !== null ? htmlspecialchars($full_name) : null,
        'skills' => $skills !== null ? htmlspecialchars($skills) : null,
        'education' => $education !== null ? htmlspecialchars($education) : null,
        'experience' => $experience !== null ? htmlspecialchars($experience) : null
    ]
]);
?>