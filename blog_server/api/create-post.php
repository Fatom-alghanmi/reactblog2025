<?php
session_start();

// CORS headers for frontend
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Include DB
require_once('../config/config.php');
require_once('../config/database.php');

// Require login
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check required fields
if (!isset($_POST['title'], $_POST['content'], $_POST['author'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required fields']);
    exit;
}

// Sanitize input
$title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
$content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);
$author = filter_var($_POST['author'], FILTER_SANITIZE_STRING);

// Image upload handling
$uploadDir = __DIR__ . "/uploads/";
$imageName = 'placeholder_100.jpg'; // default placeholder

if (!empty($_FILES['image']['name'])) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $imageName = 'img_' . uniqid() . '.' . $ext;
    $targetFile = $uploadDir . $imageName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        http_response_code(500);
        echo json_encode(['message' => 'Error uploading file']);
        exit;
    }
}

// Insert post into database
$stmt = $conn->prepare("INSERT INTO blog_posts (title, content, author, imageName) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $title, $content, $author, $imageName);

if ($stmt->execute()) {
    $postId = $stmt->insert_id;

    // Respond with full post data including image URL
    echo json_encode([
        'success' => true,
        'message' => 'Post created successfully',
        'post' => [
            'id' => $postId,
            'title' => $title,
            'content' => $content,
            'author' => $author,
            'imageUrl' => "http://localhost/reactblog2025/blog_server/uploads/" . $imageName
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

// Close connections
$stmt->close();
$conn->close();
?>
