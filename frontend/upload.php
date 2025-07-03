<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if file is uploaded
    if (isset($_FILES["resume"]) && $_FILES["resume"]["error"] == 0) {
        $file = $_FILES["resume"];
        $filename = basename($file["name"]);
        $fileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Check if the file is a PDF
        if ($fileType != "pdf") {
            $_SESSION["error"] = "Only PDF files are allowed.";
            header("Location: index.php");
            exit;
        }

        // Define upload directory
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique file name
        $newFilename = uniqid() . ".pdf";
        $filePath = $uploadDir . $newFilename;

        // Move uploaded file to destination
        if (move_uploaded_file($file["tmp_name"], $filePath)) {
            $_SESSION["message"] = "Resume uploaded successfully!";
            $_SESSION["resume_path"] = $filePath; // Store path for further processing
        } else {
            $_SESSION["error"] = "Failed to upload resume.";
        }
    } else {
        $_SESSION["error"] = "No file uploaded.";
    }

    header("Location: index.php");
    exit;
}
?>
