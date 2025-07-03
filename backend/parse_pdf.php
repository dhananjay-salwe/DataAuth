

<?php
session_start();
require '../config/db_connect.php';
require '../vendor/autoload.php'; 

use Smalot\PdfParser\Parser;

if (isset($_SESSION["resume_path"])) {
    $pdfPath = $_SESSION["resume_path"];

    if (file_exists($pdfPath)) {
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = $pdf->getText();
        
        // Ensure the filename is retrieved correctly
        $fileName = basename($pdfPath);
        
        // Insert filename and extracted text into the database
        $stmt = $conn->prepare("INSERT INTO resumes (file_name, extracted_text, upload_date) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $fileName, $text);

        if ($stmt->execute()) {
            $_SESSION["message"] = "Resume text saved successfully!";
            header("Location: ../frontend/manage_resumes.php");
            exit();
        } else {
            $_SESSION["error"] = "Database error: " . $stmt->error;
        }
    } else {
        $_SESSION["error"] = "File does not exist!";
    }
} else {
    $_SESSION["error"] = "No file uploaded!";
}

header("Location: ../frontend/manage_resumes.php");
exit();
?>

