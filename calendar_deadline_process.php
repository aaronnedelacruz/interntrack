<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "interntrack");

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Not logged in."
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$action = $data["action"] ?? "add";
$user_id = $_SESSION["user_id"];

switch ($action) {

    /* ==========================
       ADD DEADLINE
    ========================== */

    case "add":

        $title = trim($data["title"] ?? "");
        $due_date = $data["due_date"] ?? "";
        $due_time = $data["due_time"] ?: null;
        $notes = $data["notes"] ?: null;

        if ($title === "" || $due_date === "") {
            echo json_encode([
                "success" => false,
                "message" => "Title and date are required."
            ]);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO deadlines
            (user_id, title, notes, due_date, due_time)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issss",
            $user_id,
            $title,
            $notes,
            $due_date,
            $due_time
        );

        $stmt->execute();

        echo json_encode([
            "success" => true,
            "deadline" => [
                "id" => $stmt->insert_id,
                "title" => $title,
                "notes" => $notes,
                "due_date" => $due_date,
                "due_time" => $due_time,
                "is_completed" => 0
            ]
        ]);

        $stmt->close();
        break;

    /* ==========================
       EDIT DEADLINE
    ========================== */

    case "edit":

        $id = intval($data["id"]);

        $title = trim($data["title"] ?? "");
        $notes = $data["notes"] ?: null;
        $due_time = $data["due_time"] ?: null;

        $stmt = $conn->prepare("
            UPDATE deadlines
            SET
                title = ?,
                notes = ?,
                due_time = ?
            WHERE
                id = ?
            AND
                user_id = ?
        ");

        $stmt->bind_param(
            "sssii",
            $title,
            $notes,
            $due_time,
            $id,
            $user_id
        );

        $stmt->execute();

        echo json_encode([
            "success" => true
        ]);

        $stmt->close();
        break;

    /* ==========================
       DELETE DEADLINE
    ========================== */

    case "delete":

        $id = intval($data["id"]);

        $stmt = $conn->prepare("
            DELETE FROM deadlines
            WHERE id = ?
            AND user_id = ?
        ");

        $stmt->bind_param(
            "ii",
            $id,
            $user_id
        );

        $stmt->execute();

        echo json_encode([
            "success" => true
        ]);

        $stmt->close();
        break;

    default:

        echo json_encode([
            "success" => false,
            "message" => "Invalid action."
        ]);

}