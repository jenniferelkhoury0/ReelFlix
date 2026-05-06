<?php
// Start the session to manage user data across requests
session_start();

// Check if the user is logged in
// If not, redirect them to the login page
if (!isset($_SESSION['id'])) {
    header('Location: login.html'); // Redirect to login page if not logged in
    exit(); // Stop further script execution after redirect
}

// Get the movie ID from the POST request and sanitize/validate it
$movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : null;

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "movies_db";

// Create a connection to the database using MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Retrieve the user ID from the session to associate with the watchlist entry
$user_id = $_SESSION['id'];

// Prepare and execute the insert; return structured response for AJAX
$response = ['success' => false];
if ($movie_id) {
    $add_to_watchlist = $conn->prepare("INSERT INTO WATCHLIST (user_id, movie_id) VALUES (?, ?)");
    $add_to_watchlist->bind_param("ii", $user_id, $movie_id);
    $ok = $add_to_watchlist->execute();
    $add_to_watchlist->close();
    $response['success'] = $ok;
}

// Close the database connection after completing the operation
$conn->close();

// If AJAX request, return JSON; otherwise redirect back
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'genre.php';
    header("Location: " . $referer);
    exit();
}
?>