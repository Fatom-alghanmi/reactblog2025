<?php
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once('../config/database.php');

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
$userName = $data['userName'] ?? '';
$password = $data['password'] ?? '';

if (!$userName || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing username or password']);
    exit();
}

// Query the database
$stmt = $conn->prepare("SELECT userID, userName, password, emailAddress, failed_attempts, last_faild_login 
                        FROM registrations 
                        WHERE userName = ?");
$stmt->bind_param("s", $userName);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        // Save minimal session info
        $_SESSION['user'] = [
            'userID' => $user['userID'],
            'userName' => $user['userName'],
            'email' => $user['emailAddress']
        ];

        echo json_encode([
            'success' => true,
            'user' => $_SESSION['user'],
            'message' => 'Login successful'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
