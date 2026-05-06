<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB error']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id  = $_SESSION['id'];
$movie_id = isset($data['movie_id'])    ? intval($data['movie_id'])    : 0;
$rating   = isset($data['rating'])      ? intval($data['rating'])      : 0;
$review   = isset($data['review_text']) ? trim($data['review_text'])   : '';

if ($movie_id < 1 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$stmt = $conn->prepare(
    "INSERT INTO MOVIE_REVIEWS (user_id, movie_id, rating, review_text)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE rating = VALUES(rating), review_text = VALUES(review_text), created_at = CURRENT_TIMESTAMP"
);
$stmt->bind_param('iiis', $user_id, $movie_id, $rating, $review);
$ok = $stmt->execute();
$stmt->close();

// Return updated avg
$avg_stmt = $conn->prepare("SELECT ROUND(AVG(rating),1) as avg_rating, COUNT(*) as total FROM MOVIE_REVIEWS WHERE movie_id = ?");
$avg_stmt->bind_param('i', $movie_id);
$avg_stmt->execute();
$row = $avg_stmt->get_result()->fetch_assoc();
$avg_stmt->close();
$conn->close();

echo json_encode(['success' => $ok, 'avg_rating' => $row['avg_rating'], 'total' => $row['total']]);
