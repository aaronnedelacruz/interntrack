<?php
session_start();
$conn = new mysqli("localhost", "root", "", "interntrack");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check login

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// ===============================
// GET USER INFORMATION
// ===============================

$user_sql = "
SELECT *
FROM users
WHERE id = '$user_id'   
";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// ===============================
// GET PROJECT LOG INFORMATION
// ===============================

// Total rendered hours

$hours_sql = "
SELECT 
    SUM(hours) AS total_hours,
    COUNT(DISTINCT work_date) AS active_days
FROM projects
WHERE user_id = '$user_id'
";

$hours_result = $conn->query($hours_sql);
$hours_data = $hours_result->fetch_assoc();
$total_hours = $hours_data['total_hours'] ?? 0;
$total_days = $hours_data['active_days'] ?? 0;

// ===============================
// DAILY AVERAGE HOURS
// ===============================

$daily_average = 0;
if ($total_days > 0) {

    $daily_average = $total_hours / $total_days;
}

// ===============================
// CURRENT MONTH HOURS
// ===============================

$current_month = date("Y-m");
$month_sql = "
SELECT SUM(hours) AS month_hours
FROM projects
WHERE user_id='$user_id'
AND DATE_FORMAT(work_date,'%Y-%m')='$current_month'
";
$month_result = $conn->query($month_sql);
$month_data = $month_result->fetch_assoc();
$month_hours = $month_data['month_hours'] ?? 0;

// ===============================
// TOTAL HOURS THIS WEEK
// ===============================

date_default_timezone_set("Asia/Manila");

$week_start = date("Y-m-d", strtotime("monday this week"));
$week_end   = date("Y-m-d", strtotime("sunday this week"));

$week_sql = "
SELECT SUM(hours) AS week_hours
FROM projects
WHERE user_id = '$user_id'
AND work_date BETWEEN '$week_start' AND '$week_end'
";

$week_result = $conn->query($week_sql);
$week_data = $week_result->fetch_assoc();

$week_hours = $week_data['week_hours'] ?? 0;

$week_hours_display =
rtrim(rtrim(number_format($week_hours,2), '0'), '.');

// ===============================
// NEXT WORKDAY CALCULATION
// ===============================

// Your working days from database
// Example: Monday,Tuesday,Wednesday,Thursday,Friday

$working_days = explode(",", $user['working_days']);
date_default_timezone_set("Asia/Manila");
$today = new DateTime();
$next_workday = clone $today;

while(true){
    $next_workday->modify("+1 day");
    $day_name = $next_workday->format("l");
    if(in_array($day_name, $working_days)){
        break;
    }
}

// ===============================
// FORMAT VALUES
// ===============================

$daily_average_display =
rtrim(rtrim(number_format($daily_average,2), '0'), '.');



// ===============================
// TIME UNTIL NEXT OJT SESSION
// ===============================

date_default_timezone_set("Asia/Manila");

$next_session = clone $next_workday;

// Set next OJT start time
$start_time = $user['start_time'];

$next_session->setTime(
    date("H", strtotime($start_time)),
    date("i", strtotime($start_time))
);

$current_time = new DateTime();
$time_difference = $current_time->diff($next_session);

if ($next_session > $current_time) {

    if ($time_difference->days > 0) {
        $time_until =
        $time_difference->days . " days " .
        $time_difference->h . " hrs";

    } else {
        $time_until =
        $time_difference->h . " hrs";
    }
} else {
    $time_until = "Session started";

}

// ===============================
// HOLIDAYS
// ===============================

$holidays = [
    [
        "name" => "New Year's Day",
        "date" => "January 1, 2026"
    ],
    [
        "name" => "Chinese New Year",
        "date" => "February 17, 2026"
    ],
    [
        "name" => "Maundy Thursday",
        "date" => "April 2, 2026"
    ],
    [
        "name" => "Good Friday",
        "date" => "April 3, 2026"
    ],
    [
        "name" => "Black Saturday",
        "date" => "April 4, 2026"
    ],
    [
        "name" => "Araw ng Kagitingan (Day of Valor)",
        "date" => "April 9, 2026"
    ],
    [
        "name" => "Labor Day",
        "date" => "May 1, 2026"
    ],
    [
        "name" => "Independence Day",
        "date" => "June 12, 2026"
    ],
    [
        "name" => "Ninoy Aquino Day",
        "date" => "August 21, 2026"
    ],
    [
        "name" => "National Heroes Day",
        "date" => "August 31, 2026"
    ],
    [
        "name" => "All Saints' Day",
        "date" => "November 1, 2026"
    ],
    [
        "name" => "All Souls' Day",
        "date" => "November 2, 2026"
    ],
    [
        "name" => "Bonifacio Day",
        "date" => "November 30, 2026"
    ],
    [
        "name" => "Feast of the Immaculate Conception of Mary",
        "date" => "December 8, 2026"
    ],
    [
        "name" => "Christmas Eve",
        "date" => "December 24, 2026"
    ],
    [
        "name" => "Christmas Day",
        "date" => "December 25, 2026"
    ],
    [
        "name" => "Last Day of the Year",
        "date" => "December 31, 2026"
    ]
];

// ===============================
// FILTER CURRENT MONTH HOLIDAYS
// ===============================

$current_month = date("F");
$current_year = date("Y");

$monthly_holidays = [];

foreach ($holidays as $holiday) {

    $holiday_date = new DateTime($holiday['date'] . " " . $current_year);

    if ($holiday_date->format("F") == $current_month) {
        $monthly_holidays[] = $holiday;
    }
}

// ===============================
// DEADLINES
// ===============================

$stmt = $conn->prepare("
SELECT
    id,
    title,
    notes,
    due_date,
    due_time,
    is_completed
FROM deadlines
WHERE
    user_id = ?
    AND (
        due_date > CURDATE()
        OR (
            due_date = CURDATE()
            AND (
                due_time IS NULL
                OR due_time >= CURTIME()
            )
        )
    )
ORDER BY
    due_date ASC,
    due_time ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$all_deadlines = $result->fetch_all(MYSQLI_ASSOC);
$upcoming_deadlines = $all_deadlines;
$stmt->close();

$deadlines_by_date = [];
foreach ($all_deadlines as $d) {
    $deadlines_by_date[$d['due_date']][] = $d;
}


?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>InternTrack | Dashboard</title>
    <link rel="icon" href="images/InternTrack.png" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    />

    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family:
          Segoe UI,
          Tahoma,
          Geneva,
          Verdana,
          sans-serif;
      }

      body {
        background: #fff9f4;
        display: flex;
        color: #005f73;
      }

      /* ==========================
      SIDEBAR
      ========================== */

      .sidebar {
        width: 250px;
        height: 100vh;
        background: #005f73;
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 30px 15px;
        transition: all 0.3s ease;
      }

      .sidebar.collapsed {
        width: 92px;
        padding: 30px 20px;
      }

      .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        margin-bottom: 20px;
        transition: all 0.3s ease;
      }

      .sidebar.collapsed .sidebar-header {
        justify-content: center;
      }

      .logo {
        display: flex;
        align-items: center;
        gap: 12px;
        transition: 0.3s;

        overflow: hidden;
        white-space: nowrap;

        width: 170px;
        opacity: 1;

        transition:
          width 0.3s ease,
          opacity 0.25s ease;
      }

      .logo img {
        width: 45px;
        height: 45px;
        border-radius: 6px;
      }

      .logo h2 {
        font-size: 20px;
        color: #ee9b00;
        white-space: nowrap;
      }

      .sidebar.collapsed .logo {
        width: 0;
        opacity: 0;
      }
      .sidebar ul {
        list-style: none;
      }

      .sidebar li {
        margin: 10px 0;
      }

      .sidebar a {
        display: flex;
        align-items: center;

        height: 52px;
        padding: 0 14px;
        gap: 14px;

        border-radius: 10px;
        text-decoration: none;
        color: #fff;

        transition:
          background-color 0.25s ease,
          color 0.25s ease;
      }

      .sidebar a i {
        width: 24px;
        min-width: 24px;
        font-size: 20px;
        text-align: center;
        flex-shrink: 0;
      }

      .sidebar a span {
        white-space: nowrap;
        overflow: hidden;

        max-width: 140px;
        opacity: 1;

        transition:
          max-width 0.3s ease,
          opacity 0.2s ease;
      }

      .sidebar.collapsed a {
        justify-content: center;

        width: 52px;
        height: 52px;

        padding: 0;
        margin: 0 auto;
        gap: 0;
      }

      .sidebar.collapsed a span {
        max-width: 0;
        opacity: 0;
      }
      .sidebar a:hover,
      .sidebar .active {
        background: #0a9396;
      }

      .logout a {
        background: #ee9b00;
        text-align: center;
        font-weight: bold;
      }

      .logout a:hover {
        background: #d98f00;
      }

      /* ==========================
   MAIN CONTENT
========================== */

      .main {
        margin-left: 250px;
        width: calc(100% - 250px);
        padding: 35px;
        transition: all 0.3s ease;
      }

      .main.expanded {
        margin-left: 92px;
        width: calc(100% - 92px);
      }

      /* ==========================
   MENU TOGGLE
========================== */

      #menu-toggle {
        display: flex;
        align-items: center;
        justify-content: center;

        width: 52px;
        height: 52px;

        padding: 0;

        border: none;
        border-radius: 10px;
        background: transparent;
        color: white;
        cursor: pointer;
        transition: 0.3s ease;
      }

      #menu-toggle:hover {
        background: #0a9396;
      }

      #menu-toggle i {
        font-size: 20px; /* same size as sidebar icons */
        line-height: 1;
        transition:
          opacity 0.15s ease,
          transform 0.3s ease;
      }

      .sidebar.collapsed #menu-toggle i {
        transform: rotate(180deg);
      }

      .sidebar.collapsed a span {
        display: none;
      }

      .sidebar.collapsed .logout span {
        display: none;
      }

      .sidebar.collapsed .logout a {
        display: flex;
        justify-content: center;
        align-items: center;
      }

      /* ==========================
   HEADER
========================== */

      .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 35px;
      }

      .header h1 {
        font-size: 34px;
      }

      .header p {
        color: #666;
        margin-top: 5px;
      }

      .date {
        background: white;
        padding: 12px 18px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        font-weight: 600;
      }

      /* ==========================
   SUMMARY CARDS
========================== */

      .summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 35px;
      }

      .card {
        border-radius: 18px;
        padding: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
      }

      .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 35px rgba(0, 0, 0, 0.12);
      }

      .card h3 {
        font-size: 16px;
        font-weight: 500;
        margin-bottom: 12px;
      }

      .card .number {
        font-size: 36px;
        font-weight: 700;
        line-height: 1;
      }

      /* ==========================
   SUMMARY CARDS
========================== */

      .summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 35px;
      }

      .card {
        background: linear-gradient(135deg, #005f73 0%, #0a9396 100%);
        border-radius: 18px;
        padding: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
      }

      .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 35px rgba(0, 0, 0, 0.12);
      }

      .card h3 {
        font-size: 16px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 12px;
      }

      .card .number {
        font-size: 36px;
        font-weight: 700;
        color: #fff9f4;
        line-height: 1;
      }

      /* ==========================
   CALENDAR
========================== */

      .calendar-card {
        background: white;
        padding: 30px;
        border-radius: 18px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
      }

      .month {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
      }

      .month h2 {
        font-size: 28px;
      }

      .month button {
        width: 42px;
        height: 42px;
        border: none;
        border-radius: 8px;
        background: #ee9b00;
        color: white;
        font-size: 18px;
        cursor: pointer;
        transition: 0.3s;
      }

      .month button:hover {
        background: #d98f00;
      }

      .weekdays,
      .days {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
      }

      .weekdays div {
        text-align: center;
        font-weight: bold;
        padding: 10px 0;
      }

      .days div {
        height: 80px;
        border-radius: 10px;
        background: #f5f5f5;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: bold;
        transition: 0.3s;
        cursor: pointer;
      }

      .days div:hover {
        transform: translateY(-3px);
      }

      .calendar-layout {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 25px;
        align-items: start;
        margin-bottom: 30px;
      }

      .events,
      .calendar-card {
        margin-bottom: 0;
      }

      /* Calendar Event Colors */

      .workday {
        background: #d8f3dc !important;
      }

      .holiday {
        background: #ffe8a3 !important;
      }

      .absent {
        background: #ffd6d6 !important;
      }

      .leave {
        background: #cce5ff !important;
      }

      .late {
        background: #ffd9a0 !important;
      }

      .deadline {
        background: #e9d5ff !important;
      }

      .today {
        border: 3px solid #005f73;
      }

      /* ==========================
   LEGEND
========================== */

      .legend {
        display: flex;
        flex-wrap: wrap;
        gap: 25px;
        margin-bottom: 30px;
      }

      .legend div {
        display: flex;
        align-items: center;
        font-weight: 600;
      }

      .box {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        margin-right: 8px;
      }

      .work {
        background: #d8f3dc;
      }

      /* ==========================
   EVENTS PANEL
========================== */

      .events {
        background: #fff;
        padding: 28px;
        border-radius: 18px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      }

      .event-section:not(:last-child) {
        margin-bottom: 24px;
      }

      .event-section h2 {
        font-size: 18px;
        font-weight: 600;
        color: #005f73;
        margin-bottom: 18px;
      }

      .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;

        padding: 8px 0;

        border-bottom: 1px solid #ececec;
      }

      .info-row:last-child {
        border-bottom: none;
      }

      .info-row span {
        font-size: 15px;
        color: #666;
      }

      .info-row strong {
        font-size: 15px;
        font-weight: 600;
        color: #005f73;
        text-align: right;
      }

      .info-row.empty {
        justify-content: flex-start;
      }

      .info-row.empty span {
        color: #999;
        font-style: italic;
      }

      .deadline-preview{
          padding:10px 0;
          border-bottom:1px solid #eef2f4;
      }

      .deadline-preview:last-child{
          border-bottom:none;
      }

      .deadline-preview-title{
          font-size:15px;
          font-weight:600;   /* or 700 if your font doesn't support 800 */
          color:#005F73;
          margin-bottom:2px;
          letter-spacing:0.2px;
      }

      .deadline-preview-date{
          font-size:14px;
          color:#666;
      }

      /* ==========================
        DEADLINE MODAL
      ========================== */

      .days > div {
          position: relative;
          cursor: pointer;
      }

      .days > div.has-deadline::after {
          content: "";
          position: absolute;
          bottom: 6px;
          left: 50%;
          transform: translateX(-50%);
          width: 7px;
          height: 7px;
          border-radius: 50%;
          background: #d64545;
      }

      /* Overlay */

      .modal-overlay {
          display: none;
          position: fixed;
          inset: 0;
          background: rgba(0,0,0,.45);
          align-items: center;
          justify-content: center;
          z-index: 999;
      }

      .modal-overlay.show {
          display: flex;
      }

      /* Modal */

      .modal-box {
          width: 100%;
          max-width: 500px;
          background: #fff;
          border-radius: 18px;
          overflow: hidden;
          box-shadow: 0 20px 45px rgba(0,0,0,.18);
          animation: modalFade .25s ease;
      }

      @keyframes modalFade {
          from {
              opacity: 0;
              transform: translateY(15px) scale(.97);
          }
          to {
              opacity: 1;
              transform: translateY(0) scale(1);
          }
      }

      /* Header */

      .modal-header {
          background: linear-gradient(135deg,#005F73,#0A9396);
          color: white;
          padding: 18px 22px;
          display: flex;
          justify-content: space-between;
          align-items: center;
      }

      .modal-header h3 {
          margin: 0;
          font-size: 18px;
          font-weight: 600;
      }

      .modal-header button {
          border: none;
          background: transparent;
          color: white;
          font-size: 28px;
          cursor: pointer;
          transition: .2s;
      }

      

      /* ==========================
        DEADLINE LIST
      ========================== */

      .deadline-list {
          list-style: none;
          margin: 20px;
          padding: 0;
          max-height: 250px;
          overflow-y: auto;
      }

      .deadline-card {
          background: #fff;
          border: 1px solid #dfe8eb;
          border-radius: 12px;
          padding: 14px 16px;
          margin-bottom: 12px;
          box-shadow: 0 2px 8px rgba(0,0,0,.05);
          transition: .2s;
      }

      .deadline-card:hover {
          border-color: #0A9396;
          box-shadow: 0 4px 12px rgba(0,95,115,.08);
      }

      .deadline-card.completed {
          opacity: .55;
          text-decoration: line-through;
      }

      .deadline-card-header{
          display:flex;
          justify-content:space-between;
          align-items:flex-start;
          gap:16px;
      }

      .deadline-title{
          font-size:16px;
          font-weight:700;
          color:#202124;
          line-height:1.3;
          word-break:break-word;
      }

      .deadline-time{
          display:flex;
          align-items:center;
          gap:6px;

          margin-top:6px;

          font-size:13px;
          font-weight:500;

          color:#005F73;
      }

      .deadline-notes{
          margin-top:12px;

          font-size:14px;
          line-height:1.6;

          color:#343a40;

          white-space:pre-wrap;
          word-break:break-word;
      }

      .delete-deadline-btn {
          width: 34px;
          height: 34px;
          flex-shrink: 0;

          border: none;
          border-radius: 8px;

          background: #f5f5f5;
          color: #888;

          cursor: pointer;
          transition: .2s;
      }

      .deadline-info{
          flex:1;
      }

      .deadline-actions{
          display:flex;
          align-items:center;
          gap:8px;
      }

      .edit-deadline-btn,
      .delete-deadline-btn{

          width:34px;
          height:34px;

          border:none;
          border-radius:8px;

          display:flex;
          align-items:center;
          justify-content:center;

          background:#f4f6f8;

          cursor:pointer;

          transition:.2s;
      }

      .edit-deadline-btn{
          color:#005F73;
      }

      .edit-deadline-btn:hover{
          background:#dff5f7;
      }

      .delete-deadline-btn{
          color:#c43d3d;
      }

      .delete-deadline-btn:hover{
          background:#ffe5e5;
      }

      .empty {
          text-align: center;
          color: #888;
          background: #fafafa;
          border: 1px dashed #d7d7d7;
          border-radius: 10px;
          padding: 18px;
      }

      /* ==========================
        FORM
      ========================== */

      #addDeadlineForm {
          padding: 0 20px 20px;
      }

      #addDeadlineForm label {
          display: block;
          margin-bottom: 14px;
          font-size: 14px;
          font-weight: 600;
          color: #333;
      }

      #addDeadlineForm input,
      #addDeadlineForm textarea {
          width: 100%;
          margin-top: 6px;
          padding: 10px 12px;
          border: 1px solid #d6dde0;
          border-radius: 10px;
          font-size: 14px;
          box-sizing: border-box;
          transition: .2s;
      }

      #addDeadlineForm textarea {
          resize: vertical;
          min-height: 70px;
      }

      #addDeadlineForm input:focus,
      #addDeadlineForm textarea:focus {
          outline: none;
          border-color: #0A9396;
          box-shadow: 0 0 0 3px rgba(10,147,150,.15);
      }

      #addDeadlineForm button {
          width: 100%;
          margin-top: 8px;
          padding: 12px;
          border: none;
          border-radius: 10px;
          background: linear-gradient(135deg,#005F73,#0A9396);
          color: white;
          font-size: 15px;
          font-weight: 600;
          cursor: pointer;
          transition: .25s;
      }

      #addDeadlineForm button:hover {
          transform: translateY(-1px);
          box-shadow: 0 8px 18px rgba(0,95,115,.25);
      }

      /* ==========================
        TOGGLE BUTTON
      ========================== */

      .add-deadline-btn {
          background: none;
          border: none;
          color: #0A9396;
          font-size: 14px;
          font-weight: 600;
          padding: 0;
          margin: 0 20px 16px;
          cursor: pointer;
          transition: .2s;
      }

      .add-deadline-btn:hover {
          color: #005F73;
          text-decoration: underline;
      }

      /* Hidden Form */

      .hidden-form {
          display: none;
      }

      .hidden-form.show {
          display: block;
          animation: fadeDown .25s ease;
      }

      @keyframes fadeDown {
          from {
              opacity: 0;
              transform: translateY(-8px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }

      /* ==========================
        RESPONSIVE
      ========================== */

      @media (max-width:1100px) {
          .sidebar {
              width:92px;
              padding:30px 20px;
          }

          .sidebar .logo {
              width:0;
              opacity:0;
          }

          .sidebar a {
              justify-content:center;
              width:52px;
              padding:0;
              margin:0 auto;
              gap:0;
          }

          .sidebar a span {
              max-width:0;
              opacity:0;
          }

          .logout a {
              justify-content:center;
          }

          .logout span {
              max-width:0;
              opacity:0;
          }

          .main {
              margin-left:92px;
              width:calc(100% - 92px);
          }

          .calendar-layout {
              grid-template-columns:1fr;
          }
      }

      @media (max-width:768px) {
          .main {
              padding:20px;
          }

          .header {
              flex-direction:column;
              align-items:flex-start;
              gap:15px;
          }

          .summary {
              grid-template-columns:1fr;
          }

          .calendar-card {
              padding:20px;
          }

          .events {
              padding:20px;
          }

          .month h2 {
              font-size:24px;
          }

          .days div {
              height:60px;
          }

          .legend {
              gap:15px;
          }
      }

      @media (max-width:480px) {
          .main {
              padding:15px;
          }

          .header h1 {
              font-size:28px;
          }

          .date {
              width:100%;
          }

          .calendar-card {
              padding:15px;
          }

          .weekdays,
          .days {
              gap:5px;
          }

          .days div {
              height:45px;
              font-size:13px;
          }

          .card {
              padding:20px;
          }

          .card .number {
              font-size:30px;
          }

          .deadline-card-header {
              flex-direction:column;
          }
      }
    </style>
  </head>
  <body>
    <!-- SIDEBAR -->

    <div class="sidebar" id="sidebar">
      <!-- Top Section -->
      <div>
        <div class="sidebar-header">
          <div class="logo" id="logo">
            <img src="images/InternTrack.png" alt="Logo" />
            <h2>InternTrack</h2>
          </div>

          <button id="menu-toggle">
            <i class="bi bi-chevron-double-left" id="menu-icon"></i>
          </button>
        </div>

        <ul>
          <li>
            <a href="dashboard.php">
              <i class="bi bi-ui-checks-grid"></i>
              <span>Dashboard</span>
            </a>
          </li>

          <li>
            <a href="projects.php">
              <i class="bi bi-folder2"></i>
              <span>Projects</span>
            </a>
          </li>

          <li>
            <a href="calendar.php" class="active">
              <i class="bi bi-calendar-event"></i>
              <span>Calendar</span>
            </a>
          </li>

          <li>
            <a href="reports.php">
              <i class="bi bi-bar-chart-line"></i>
              <span>Reports</span>
            </a>
          </li>

          <li>
            <a href="profile.php">
              <i class="bi bi-person-circle"></i>
              <span>Profile</span>
            </a>
          </li>
        </ul>
      </div>

      <!-- Bottom Section -->
      <div class="logout">
        <a href="index.html">
          <i class="bi bi-box-arrow-right"></i>
          <span>Log Out</span>
        </a>
      </div>
    </div>

    <!-- MAIN -->

    <div class="main" id="main">
      <div class="header">
        <div>
          <h1>Internship Calendar</h1>
          <p>Track attendance, schedules, holidays, and deadlines.</p>
        </div>

        <div class="date">
          📅 <?= date("F j, Y"); ?>
        </div>
      </div>

      <!-- ATTENDANCE SUMMARY -->

      <div class="summary">
        <div class="card week-card">
          <h3>Total Hours This Week</h3>

          <div class="number">
              <?= $week_hours_display; ?>
          </div>
        </div>

        <div class="card month-card">
          <h3>Total Hours This Month</h3>
          <div class="number">
            <?= rtrim(rtrim(number_format($month_hours, 2), '0'), '.'); ?>
          </div>
        </div>

        <div class="card active-card">
          <h3>Total Days Completed</h3>
          <div class="number">
            <?= $total_days; ?>
          </div>
        </div>

        <div class="card daily-card">
          <h3>Daily Average Hours</h3>
          <div class="number">
            <?= $daily_average_display; ?>
          </div>
        </div>
      </div>

      <!-- CALENDAR SECTION -->

      <div class="calendar-layout">
        <!-- Next Workday Preview -->

        <div class="events">
          <div class="event-section">
            <h2>Next Workday Preview</h2>

            <div class="info-row">
              <span>Next OJT Day</span>
              <strong>
                <?= $next_workday->format("D, F j, Y"); ?>
              </strong>
            </div>

            <div class="info-row">
              <span>Time Until Session</span>
              <strong>
                <?= $time_until; ?>
              </strong>
            </div>

            <div class="info-row">
              <span>Expected Hours</span>
              <strong>
                <?= $user['hours_per_day']; ?> hrs
              </strong>
            </div>

            
          </div>

          <div class="event-section">
            <h2>Deadlines</h2>

            <?php if(count($upcoming_deadlines) > 0): ?>

                <?php foreach(array_slice($upcoming_deadlines, 0, 5) as $deadline): ?>

                    <div class="deadline-preview">

                        <div class="deadline-preview-title">
                            <?= htmlspecialchars($deadline['title']); ?>
                        </div>

                        <div class="deadline-preview-date">

                            <?= date("M j, Y", strtotime($deadline['due_date'])); ?>

                            <?php if(!empty($deadline['due_time'])): ?>
                                |
                                <?= date("g:i A", strtotime($deadline['due_time'])); ?>
                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="info-row empty">
                    <span>No upcoming deadlines.</span>
                </div>

            <?php endif; ?>

        </div>

          <div class="event-section">
            <h2>Holidays</h2>

            <?php if(count($monthly_holidays) > 0): ?>
              <?php foreach($monthly_holidays as $holiday): ?>
                <div class="info-row">
                  <span>
                    <?= $holiday['name']; ?>
                  </span>

                  <strong>
                    <?= $holiday['date']; ?>
                  </strong>
                </div>
              <?php endforeach; ?>

              <?php else: ?>
                <div class="info-row empty">
                  <span>No holidays this month.</span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        
        <!-- Calendar -->
        <div class="calendar-card">
          <div class="month">
            <button>&lt;</button>

            <h2>June 2026</h2>

            <button>&gt;</button>
          </div>

          <div class="weekdays">
            <div>Sun</div>
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
          </div>

          <div class="days" id="days"></div>
        </div>
      </div>

      <div class="modal-overlay" id="deadlineModal">
        <div class="modal-box">

          <div class="modal-header">
            <h3 id="modalDateLabel"></h3>
            <button type="button" id="closeModalBtn">&times;</button>
          </div>

          <ul id="modalDeadlineList" class="deadline-list"></ul>

          <button
            type="button"
            class="add-deadline-btn"
            id="showDeadlineForm">
            + Add Deadline
          </button>

          <form id="addDeadlineForm" class="hidden-form">

            <input type="hidden" id="modalDateInput" name="due_date">

            <label>
              Title
              <input
                type="text"
                name="title"
                id="deadlineTitle"
                maxlength="255"
                required>
            </label>

            <label>
              Time (optional)
              <input
                type="time"
                name="due_time"
                id="deadlineTime">
            </label>

            <label>
              Notes (optional)
              <textarea
                name="notes"
                id="deadlineNotes"
                rows="2"></textarea>
            </label>

            <button type="submit">
              Save Deadline
            </button>

          </form>

        </div>
      </div>
    </div>
    <script>
      //sidebar navigation bar
      const toggleBtn = document.getElementById("menu-toggle");
      const menuIcon = document.getElementById("menu-icon");
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");

      const isCollapsed = localStorage.getItem("sidebar") === "collapsed";

      if (isCollapsed) {
        sidebar.classList.add("collapsed");
        main.classList.add("expanded");
        menuIcon.classList.replace("bi-chevron-double-left", "bi-list");
      } else {
        // FORCE CLEAN DEFAULT STATE
        sidebar.classList.remove("collapsed");
        main.classList.remove("expanded");
        menuIcon.classList.replace("bi-list", "bi-chevron-double-left");
      }

      toggleBtn.addEventListener("click", () => {
        menuIcon.style.opacity = "0";

        setTimeout(() => {
          const isNowCollapsed = sidebar.classList.toggle("collapsed");
          main.classList.toggle("expanded");

          // SAVE STATE
          localStorage.setItem(
            "sidebar",
            isNowCollapsed ? "collapsed" : "expanded",
          );

          // ICON SWITCH
          if (isNowCollapsed) {
            menuIcon.classList.replace("bi-chevron-double-left", "bi-list");
          } else {
            menuIcon.classList.replace("bi-list", "bi-chevron-double-left");
          }

          menuIcon.style.opacity = "1";
        }, 150);
      });
    </script>
    <script>
      //calendar
      const monthNames = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
      ];

      let currentDate = new Date();

      const daysContainer = document.getElementById("days");
      const monthTitle = document.querySelector(".month h2");

      function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();

        const firstDayIndex = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();

        monthTitle.textContent = `${monthNames[month]} ${year}`;

        daysContainer.innerHTML = "";

        // Empty slots before day 1
        for (let i = 0; i < firstDayIndex; i++) {
          const empty = document.createElement("div");
          daysContainer.appendChild(empty);
        }

        // Actual days
        for (let day = 1; day <= lastDate; day++) {
          const cell = document.createElement("div");
          cell.textContent = day;

          const today = new Date();
          if (
            day === today.getDate() &&
            month === today.getMonth() &&
            year === today.getFullYear()
          ) {
            cell.classList.add("today");
          }

          daysContainer.appendChild(cell);
        }
      }

      // Calendar navigation
      document.querySelectorAll(".month button")[0].onclick = () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
      };

      document.querySelectorAll(".month button")[1].onclick = () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
      };

      renderCalendar(currentDate);

      // Returns the next OJT session (8:00 AM, Monday-Friday)
      function getNextWorkday(date) {
        const next = new Date(date);

        // Before 8 AM on a weekday? Today's session is next.
        if (next.getDay() !== 0 && next.getDay() !== 6 && next.getHours() < 8) {
          next.setHours(8, 0, 0, 0);
          return next;
        }

        // Otherwise go to the next weekday
        next.setDate(next.getDate() + 1);

        while (next.getDay() === 0 || next.getDay() === 6) {
          next.setDate(next.getDate() + 1);
        }

        next.setHours(8, 0, 0, 0);

        return next;
      }

      function formatDate(d) {
        return d.toLocaleDateString("en-US", {
          weekday: "short",
          month: "long",
          day: "numeric",
          year: "numeric",
        });
      }

      function updateNextWorkdayPreview() {
        const now = new Date();
        const next = getNextWorkday(now);

        const diffMs = next - now;

        const totalHours = Math.floor(diffMs / (1000 * 60 * 60));
        const days = Math.floor(totalHours / 24);
        const hours = totalHours % 24;

        let countdownText = "";

        if (days === 0) {
          countdownText = `${hours} hr${hours !== 1 ? "s" : ""}`;
        } else {
          countdownText = `${days} day${days !== 1 ? "s" : ""}`;

          if (hours > 0) {
            countdownText += ` and ${hours} hr${hours !== 1 ? "s" : ""}`;
          }
        }

        document.getElementById("next-day").textContent = formatDate(next);

        document.getElementById("expected-hours").textContent = "8 hrs";

        document.getElementById("countdown").textContent = countdownText;
      }

      updateNextWorkdayPreview();

      // Refresh every minute
      setInterval(updateNextWorkdayPreview, 60000);
    </script>
    <script>
      let deadlinesByDate = <?= json_encode($deadlines_by_date) ?>;

      function pad(n) { return String(n).padStart(2, "0"); }
      function toISODate(y, m, d) { return `${y}-${pad(m + 1)}-${pad(d)}`; }

      function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();

        const firstDayIndex = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();

        monthTitle.textContent = `${monthNames[month]} ${year}`;
        daysContainer.innerHTML = "";

        for (let i = 0; i < firstDayIndex; i++) {
          daysContainer.appendChild(document.createElement("div"));
        }

        for (let day = 1; day <= lastDate; day++) {
          const cell = document.createElement("div");
          cell.textContent = day;

          const iso = toISODate(year, month, day);
          cell.dataset.date = iso;

          const today = new Date();
          if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
            cell.classList.add("today");
          }

          if (deadlinesByDate[iso] && deadlinesByDate[iso].length > 0) {
            cell.classList.add("has-deadline");
          }

          cell.addEventListener("click", () => openDeadlineModal(iso));

          daysContainer.appendChild(cell);
        }
      }

      const modal = document.getElementById("deadlineModal");
      const modalDateLabel = document.getElementById("modalDateLabel");
      const modalList = document.getElementById("modalDeadlineList");
      const modalDateInput = document.getElementById("modalDateInput");
      const addForm = document.getElementById("addDeadlineForm");

      const showBtn = document.getElementById("showDeadlineForm");

      showBtn.addEventListener("click", () => {
          addForm.classList.toggle("show");
      });

      function formatModalDate(iso) {
        const [y, m, d] = iso.split("-").map(Number);
        return new Date(y, m - 1, d).toLocaleDateString("en-US", {
          weekday: "long", month: "long", day: "numeric", year: "numeric"
        });
      }

      function formatTime(time) {
          if (!time) return "";

          const [hour, minute] = time.split(":");

          return new Date(0, 0, 0, hour, minute).toLocaleTimeString("en-US", {
              hour: "numeric",
              minute: "2-digit",
              hour12: true
          });
      }

      function renderModalList(iso) {
          const items = deadlinesByDate[iso] || [];

          modalList.innerHTML = items.length
              ? items.map(d => `
                  <li class="deadline-card ${d.is_completed == 1 ? "completed" : ""}" data-id="${d.id}">

                      <div class="deadline-card-header">

                          <div class="deadline-info">

                              <div class="deadline-title">
                                  ${d.title}
                              </div>

                              ${d.due_time ? `
                                  <div class="deadline-time">
                                      <i class="bi bi-clock"></i>
                                      ${formatTime(d.due_time)}
                                  </div>
                              ` : ""}

                          </div>

                          <div class="deadline-actions">

                              <button
                                  class="edit-deadline-btn"
                                  data-id="${d.id}"
                                  title="Edit Deadline">

                                  <i class="bi bi-pencil-square"></i>

                              </button>

                              <button
                                  class="delete-deadline-btn"
                                  data-id="${d.id}"
                                  title="Delete Deadline">

                                  <i class="bi bi-trash-fill"></i>

                              </button>

                          </div>

                      </div>

                      ${d.notes ? `<div class="deadline-notes">${d.notes}</div>` : ""}

                  </li>
              `).join("")
              : `
                  <li class="empty">
                      No deadlines yet for this day.
                  </li>
              `;
      }

      function openDeadlineModal(iso) {
        modalDateLabel.textContent = formatModalDate(iso);
        modalDateInput.value = iso;
        renderModalList(iso);
        modal.classList.add("show");
      }

      document.getElementById("closeModalBtn").onclick = () => modal.classList.remove("show");
      modal.addEventListener("click", (e) => { if (e.target === modal) modal.classList.remove("show"); });

      addForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const payload = {

            action: editingDeadline ? "edit" : "add",
            id: editingDeadline,
            due_date: modalDateInput.value,
            title: document.getElementById("deadlineTitle").value.trim(),
            due_time: document.getElementById("deadlineTime").value || null,
            notes: document.getElementById("deadlineNotes").value.trim() || null,
        };

        if (!payload.title) return;

        const res = await fetch("calendar_deadline_process.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });

        const result = await res.json();
        if (!result.success) {
          alert(result.message || "Could not save deadline.");
          return;
        }

        // update local state so calendar + modal reflect it instantly
        const iso = payload.due_date;

        if (payload.action === "edit") {
            const list = deadlinesByDate[iso] || [];
            const index = list.findIndex(d => d.id == payload.id);

            if (index > -1) {
                list[index] = {
                    ...list[index],
                    title: payload.title,
                    due_time: payload.due_time,
                    notes: payload.notes
                };
            }
            editingDeadline = null;
            addForm.querySelector("button[type='submit']").textContent = "Save Deadline";

        } else {
            if (!deadlinesByDate[iso]) {
                deadlinesByDate[iso] = [];
            }
            deadlinesByDate[iso].push(result.deadline);
        }

        renderModalList(iso);

        const cell = [...daysContainer.children].find(c => c.dataset.date === iso);

        if (cell) {
            cell.classList.add("has-deadline");
        }

        addForm.reset();
        addForm.classList.remove("show");
      });

      renderCalendar(currentDate);

      document.querySelectorAll(".month button")[0].onclick = () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
      };
      document.querySelectorAll(".month button")[1].onclick = () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
      };

      let editingDeadline = null;

      modalList.addEventListener("click", async (e) => {

          const editBtn = e.target.closest(".edit-deadline-btn");
          const deleteBtn = e.target.closest(".delete-deadline-btn");

          if (editBtn) {

              const id = Number(editBtn.dataset.id);

              const deadline = Object.values(deadlinesByDate)
                  .flat()
                  .find(d => d.id == id);

              if (!deadline) return;

              editingDeadline = deadline.id;

              document.getElementById("deadlineTitle").value = deadline.title;
              document.getElementById("deadlineTime").value = deadline.due_time ?? "";
              document.getElementById("deadlineNotes").value = deadline.notes ?? "";

              addForm.classList.add("show");
              addForm.querySelector("button[type='submit']").textContent = "Save Changes";

              return;
          }

          if (deleteBtn) {

              if (!confirm("Delete this deadline?"))
                  return;

              const id = Number(deleteBtn.dataset.id);

              const res = await fetch("calendar_deadline_process.php", {
                  method:"POST",
                  headers:{
                      "Content-Type":"application/json"
                  },
                  body:JSON.stringify({
                      action:"delete",
                      id:id
                  })
              });

              const result = await res.json();

              if(!result.success){
                  alert(result.message);
                  return;
              }

              deadlinesByDate[modalDateInput.value] =
                  deadlinesByDate[modalDateInput.value]
                      .filter(d=>d.id!=id);

              renderModalList(modalDateInput.value);

          }

      });
    </script>
  </body>
</html>
