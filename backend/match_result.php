<?php
session_start(); // ✅ Required to access $_SESSION variables
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// Simulated data fetching logic (replace with your DB fetch logic)
$resumeData = $_SESSION['resume_data'] ?? [];
$linkedinData = $_SESSION['linkedin_data'] ?? [];

if (empty($resumeData['extracted_text']) || empty($linkedinData['full_name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing resume or LinkedIn data.']);
    exit;
}

// Matching logic
function calculate_match($resumeText, $linkedinText) {
    $resumeText = strtolower($resumeText);
    $resumeWords = preg_split('/[\s,.;:\n\r\t\-]+/', $resumeText);
    $resumeWords = array_filter($resumeWords);
    $linkedinItems = array_filter(array_map('trim', explode(',', strtolower($linkedinText))));
    $matches = 0;
    foreach ($linkedinItems as $item) {
        foreach ($resumeWords as $word) {
            similar_text($item, $word, $percent);
            if ($percent >= 80) {
                $matches++;
                break;
            }
        }
    }
    $total = count($linkedinItems);
    return $total > 0 ? round(($matches / $total) * 100) : 0;
}

$resumeText = strtolower($resumeData['extracted_text']);
$linkedinName = strtolower($linkedinData['full_name']);

$fullNameMatch = strpos($resumeText, $linkedinName) !== false ? 100 : 0;
$skillsMatch = calculate_match($resumeData['extracted_text'], $linkedinData['skills'] ?? '');
$educationMatch = calculate_match($resumeData['extracted_text'], $linkedinData['education'] ?? '');
$experienceMatch = calculate_match($resumeData['extracted_text'], $linkedinData['experience'] ?? '');
$totalMatch = round(($fullNameMatch + $skillsMatch + $educationMatch + $experienceMatch) / 4);

// Return result as JSON
echo json_encode([
    'success' => true,
    'data' => [
        'fullName' => $fullNameMatch,
        'skills' => $skillsMatch,
        'education' => $educationMatch,
        'experience' => $experienceMatch,
        'total' => $totalMatch
    ]
]);
