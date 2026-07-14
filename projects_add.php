<?php

session_start();

$conn = new mysqli("localhost", "root", "", "interntrack");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Make sure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get submitted data
$project_name = $_POST['project_name'];
$activity = $_POST['activity'];
$work_date = $_POST['work_date'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$hours = $_POST['hours'];

// Insert into projects table
$sql = "INSERT INTO projects
(
    user_id,
    project_name,
    activity,
    work_date,
    start_time,
    end_time,
    hours
)
VALUES
(
    '$user_id',
    '$project_name',
    '$activity',
    '$work_date',
    '$start_time',
    '$end_time',
    '$hours'
)";

if ($conn->query($sql) === TRUE) {

    header("Location: projects.php?success=1");
    exit();

} else {

    echo "Error: " . $conn->error;

}

$conn->close();

?>