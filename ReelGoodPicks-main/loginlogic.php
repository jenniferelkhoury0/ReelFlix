<?php
session_start();

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

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT * FROM USERS WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password using password_verify since passwords are hashed
        if (password_verify($password, $user['HASHEDPASSWORD'])) {
            // Password is correct, set session variables
            $_SESSION['id'] = $user['ID'];
            $_SESSION['username'] = $user['USERNAME'];
            $_SESSION['fullname'] = $user['FULLNAME'];
            $_SESSION['email'] = $user['EMAIL'];
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Password is incorrect
                $_SESSION['error'] = "Invalid password";
                header("Location: login.html");
            exit();
        }
    } else {
        // User not found
        $_SESSION['error'] = "Username not found";
            header("Location: login.html");
        exit();
    }

    // Close statement
    $stmt->close();
}

// Close connection
$conn->close();
?> 