<?php

session_start();

$conn = new mysqli("localhost", "root", "", "interntrack");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$action = $_POST['action'] ?? "add";

switch ($action) {

    /* ==========================
       ADD PROJECT
    ========================== */

    case "add":

        $project_name = $_POST['project_name'];
        $activity     = $_POST['activity'];
        $work_date    = $_POST['work_date'];
        $start_time   = $_POST['start_time'];
        $end_time     = $_POST['end_time'];
        $hours        = $_POST['hours'];

        $stmt = $conn->prepare("
            INSERT INTO projects
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
            (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssssd",
            $user_id,
            $project_name,
            $activity,
            $work_date,
            $start_time,
            $end_time,
            $hours
        );

        if (!$stmt->execute()) {
            die("SQL Error: " . $stmt->error);
        }

        $stmt->close();

        header("Location: projects.php?success=added");
        exit();



    /* ==========================
       EDIT PROJECT
    ========================== */

    case "edit":

        $id           = $_POST['id'];
        $project_name = $_POST['project_name'];
        $activity     = $_POST['activity'];
        $work_date    = $_POST['work_date'];
        $start_time   = $_POST['start_time'];
        $end_time     = $_POST['end_time'];
        $hours        = $_POST['hours'];

        $stmt = $conn->prepare("
            UPDATE projects
            SET
                project_name=?,
                activity=?,
                work_date=?,
                start_time=?,
                end_time=?,
                hours=?
            WHERE
                id=?
            AND
                user_id=?
        ");

        $stmt->bind_param(
            "sssssdii",
            $project_name,
            $activity,
            $work_date,
            $start_time,
            $end_time,
            $hours,
            $id,
            $user_id
        );

        $stmt->execute();
        $stmt->close();

        header("Location: projects.php?success=edited");
        exit();



    /* ==========================
       DELETE PROJECT
    ========================== */

    case "delete":

        $id = $_POST['id'];

        $stmt = $conn->prepare("
            DELETE FROM projects
            WHERE
                id=?
            AND
                user_id=?
        ");

        $stmt->bind_param(
            "ii",
            $id,
            $user_id
        );

        $stmt->execute();
        $stmt->close();

        header("Location: projects.php?success=deleted");
        exit();



    default:

        header("Location: projects.php");
        exit();
}

$conn->close();

?>