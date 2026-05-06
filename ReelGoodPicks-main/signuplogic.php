<?php
// signup.php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "movies_db";
$error = ""; // Initialize error variable
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST["fullname"];
    $email = $_POST["email"];
    $username = $_POST["username"];
    $raw_password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"] ?? '';

    // Confirm passwords match
    if ($raw_password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: signup.php");
        exit;
    }
    $dob = $_POST["dob"];

    // Hash the password
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    // Check if the email or username already exists
    $check = $conn->prepare("SELECT * FROM USERS WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) { // Email or username already exists
        $error = "Email or username already exists";
        $_SESSION['error'] = $error;
        header("Location: signup.php");
        exit;
    }

    // Insert the data into the database
    $stmt = $conn->prepare("INSERT INTO USERS (username, hashedpassword, fullname, email, DOB) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hashed_password, $fullname, $email, $dob);

    // Execute the insert statement
    if ($stmt->execute()) {
        $_SESSION['id'] = $conn->insert_id;
        $_SESSION['email'] = $email;
        $_SESSION['fullname'] = $fullname;
        $_SESSION['username'] = $username;
        header("Location: dashboard.php"); // Redirect to the dashboard
    } else {
        $error = "Error creating account: " . $stmt->error;
        $_SESSION['error'] = $error;
        header("Location: signup.php");
    }

    // Close the statements
    $stmt->close();
    $check->close();
}

// Close the connection
$conn->close();
?>