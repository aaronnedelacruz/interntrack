<?php

session_start();

$conn = new mysqli("localhost", "root", "", "interntrack");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['user_id'];


// Get submitted values
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$student_id = $_POST['student_id'];


$company = $_POST['company'];
$department = $_POST['department'];
$supervisor = $_POST['supervisor'];
$school = $_POST['school'];
$professor = $_POST['professor'];

$required_hours = $_POST['required_hours'];
$hours_per_day = $_POST['hours_per_day'];
$working_days = isset($_POST['working_days'])
    ? implode(",", $_POST['working_days'])
    : "";
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$start_date = $_POST['start_date'];


// Update database

$sql = "UPDATE users SET

    first_name = '$first_name',
    last_name = '$last_name',
    email = '$email',
    student_id = '$student_id',


    company = '$company',
    department = '$department',
    supervisor = '$supervisor',
    school = '$school',
    professor = '$professor',

    required_hours = '$required_hours',
    hours_per_day = '$hours_per_day',
    working_days = '$working_days',
    start_time = '$start_time',
    end_time = '$end_time',
    start_date = '$start_date'

WHERE id = '$user_id'";


if ($conn->query($sql) === TRUE) {

    header("Location: profile.php");
    exit();

} else {

    echo "Error updating profile: " . $conn->error;

}


$conn->close();

?>