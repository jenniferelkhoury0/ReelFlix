<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : null;
$user_id  = $_SESSION['id'];

$response = ['success' => false];

if ($movie_id) {
    $conn = new mysqli('localhost', 'root', '', 'movies_db');
    $stmt = $conn->prepare("INSERT IGNORE INTO USER_FAVORITES (user_id, movie_id) VALUES (?, ?)");
    $stmt->bind_param('ii', $user_id, $movie_id);
    $response['success'] = $stmt->execute();
    $stmt->close();
    $conn->close();
}

header('Content-Type: application/json');
echo json_encode($response);
