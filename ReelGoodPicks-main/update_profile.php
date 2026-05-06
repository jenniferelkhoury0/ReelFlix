<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.html");
    exit();
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "movies_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['id'];
$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update bio if provided
    if (isset($_POST['bio'])) {
        $bio = trim($_POST['bio']);
        $query = "UPDATE USERS SET bio = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $bio, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Handle password change if all password fields are provided
    if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        // Verify current password
        $query = "SELECT hashedpassword FROM USERS WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (password_verify($_POST['current_password'], $user['hashedpassword'])) {
            // Verify new passwords match
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                // Update password
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $query = "UPDATE USERS SET hashedpassword = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $new_password, $user_id);
                $stmt->execute();
                $stmt->close();
                $success = true;
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    } else if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
        // If any password field is filled but not all
        $error = "Please fill in all password fields to change your password.";
    } else {
        // If only bio was updated
        $success = true;
    }
}

// Close connection
$conn->close();

// Redirect back to profile page with status message
$redirect_url = "profile.php";
if ($success) {
    $redirect_url .= "?success=1";
} else if ($error) {
    $redirect_url .= "?error=" . urlencode($error);
}
header("Location: " . $redirect_url);
exit();
?> 