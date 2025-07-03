<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../config/db_connect.php';

$rapidapi_key = '5934581829msh40e704ccde3083dp187b6bjsn1538efc32cc4'; // ⚠️ Replace this with your actual key

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['linkedin_url'])) {
    $url = trim($_POST['linkedin_url']);

    if (empty($url)) {
        echo json_encode(['success' => false, 'error' => "LinkedIn URL cannot be empty."]);
        exit;
    }

    $encoded_url = urlencode($url);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://linkedin-data-api.p.rapidapi.com/get-profile-data-by-url?url=$encoded_url",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: linkedin-data-api.p.rapidapi.com",
            "x-rapidapi-key: 5934581829msh40e704ccde3083dp187b6bjsn1538efc32cc4"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo json_encode(['success' => false, 'error' => "API request failed: $err"]);
        exit;
    }

    $data = json_decode($response, true);

    if (!$data || isset($data['error'])) {
        echo json_encode([
            'success' => false,
            'error' => "Failed to retrieve LinkedIn data.",
            'raw_response' => $response
        ]);
        exit;
    }

    // 🔍 DEBUG: log raw response for structure check
    if (!isset($data['username']) && !isset($data['fullName']) && !isset($data['skills'])) {
        echo json_encode([
            'success' => false,
            'message' => 'API structure might have changed. Dumping full response for debug:',
            'raw_response' => $data
        ]);
        exit;
    }

    // ✅ Extract fields (adjust if they are nested inside another key like $data['data']['firstName'])
    $linkedin_id = $data['id'] ?? uniqid("li_", true);
    $username = $data['username'] ?? ($data['profile_url'] ?? '');
    $first_name = $data['firstName'] ?? ($data['first_name'] ?? '');
    $last_name = $data['lastName'] ?? ($data['last_name'] ?? '');
    $full_name = trim($first_name . ' ' . $last_name);
    $is_open_to_work = !empty($data['isOpenToWork']) ? 'yes' : 'no';

    // ✅ Normalize arrays
    $skills = json_encode($data['skills'] ?? []);
    $education = json_encode($data['educations'] ?? $data['education'] ?? []);
    $experience = json_encode($data['experience'] ?? $data['positions'] ?? []);

    $sessionId = session_id();

    $stmt = $conn->prepare("
        INSERT INTO linkedin_data 
        (linkedin_id, username, full_name, is_open_to_work, url, skills, education, experience, session_id, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if ($stmt) {
        $stmt->bind_param(
            "sssssssss",
            $linkedin_id,
            $username,
            $full_name,
            $is_open_to_work,
            $url,
            $skills,
            $education,
            $experience,
            $sessionId
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'LinkedIn data fetched and stored successfully.']);
        } else {
            echo json_encode(['success' => false, 'error' => "Insert failed: " . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => "Prepare failed: " . $conn->error]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'error' => "Invalid request method or missing LinkedIn URL."]);
}
?>
