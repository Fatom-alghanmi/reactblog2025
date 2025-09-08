<?php
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once('../config/config.php');
require_once('../config/database.php');

// Require login
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Pagination
$maxPostsPerPage = 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $maxPostsPerPage;

// Total posts
$countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM blog_posts");
$countRow = mysqli_fetch_assoc($countResult);
$totalPosts = $countRow['total'];

// Fetch posts
$query = "SELECT * FROM blog_posts ORDER BY publish_date DESC LIMIT $offset, $maxPostsPerPage";
$result = mysqli_query($conn, $query);
$posts = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Add full image URL for each post
foreach ($posts as &$post) {
    $post['imageUrl'] = $post['imageName'] 
        ? "http://localhost/reactblog2025/blog_server/uploads/" . $post['imageName'] 
        : "http://localhost/reactblog2025/blog_server/uploads/placeholder_100.jpg";
}

// Response
echo json_encode([
    'success' => true,
    'posts' => $posts,
    'totalPosts' => $totalPosts
]);

mysqli_close($conn);
?>
