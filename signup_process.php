<?php
include "database.php";

// Only process the form if it was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Check if passwords match
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Check if email already exists
    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        die("Email already exists.");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the user
    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (first_name, last_name, email, password)
         VALUES (?, ?, ?, ?)");

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $first_name,
        $last_name,
        $email,
        $hashed_password
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: login.php");
        exit();
    } else {
        echo "Registration failed.";
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>