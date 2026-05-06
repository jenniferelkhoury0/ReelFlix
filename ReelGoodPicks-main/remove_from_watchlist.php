<?php
// Start the session to access user data across different pages
session_start();

// Check if the user is logged in by verifying if their ID is stored in the session
// If not, redirect them to the login page
if (!isset($_SESSION['id'])) {
    header('Location: login.html'); // Redirect to the login page if the user is not logged in
    exit(); // Stop further script execution
}

// Get the movie ID from the POST request and sanitize it to prevent XSS attacks
// If no movie ID is provided, set it to null
// Validate movie id
$movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : null;

// Database configuration
$servername = "localhost"; // Database server name
$username = "root"; // Database username
$password = ""; // Database password
$dbname = "movies_db"; // Name of the database

// Create a connection to the database using MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Retrieve the user ID from the session to associate with the watchlist entry
$user_id = $_SESSION['id'];

// Prepare the SQL query to remove the movie from the user's watchlist
$response = ['success' => false];
if ($movie_id) {
    $remove_from_watchlist = $conn->prepare("DELETE FROM WATCHLIST WHERE user_id = ? AND movie_id = ?");
    $remove_from_watchlist->bind_param("ii", $user_id, $movie_id);
    $ok = $remove_from_watchlist->execute();
    $remove_from_watchlist->close();
    $response['success'] = $ok;
}

// Close the database connection after completing the operation
$conn->close();

// Get the referring page URL so that the user can be redirected back to where they came from
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