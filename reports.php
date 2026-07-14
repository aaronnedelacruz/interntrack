<?php

// 1. SESSION + DATABASE CONNECTION
session_start();

$conn = new mysqli("localhost", "root", "", "interntrack");

if ($conn->connect_error) {
    die("Database connection failed");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// 2. GET USER DATA

$user_sql = "
SELECT *
FROM users
WHERE id = '$user_id'
";

$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();


// 3. GET COMPLETED HOURS

$hours_sql = "
SELECT SUM(hours) AS total_hours
FROM projects
WHERE user_id = '$user_id'
";

$hours_result = $conn->query($hours_sql);
$hours_data = $hours_result->fetch_assoc();

$total_hours = $hours_data['total_hours'] ?? 0;


// 4. WORKING DAYS

$working_days = explode(",", $user['working_days']);

date_default_timezone_set("Asia/Manila");

$today = new DateTime();


// ===============================
// 5. REPORT SUMMARY COMPUTATION
// ===============================

$completed_hours = $total_hours;

$required_hours = $user['required_hours'];

$remaining_hours = max(
    $required_hours - $completed_hours,
    0
);


// Progress

$progress = 0;

if ($required_hours > 0) {
    $progress = ($completed_hours / $required_hours) * 100;
}

$progress_display = round($progress) . "%";


// Expected Completion

$completion_date = clone $today;

$hours_left = $remaining_hours;


while($hours_left > 0){

    $completion_date->modify("+1 day");

    $day_name = $completion_date->format("l");

    if(in_array($day_name, $working_days)){

        $hours_left -= $user['hours_per_day'];

    }
}

$completion_date_display = $completion_date->format("F j, Y");

// ===============================
// FORMAT DISPLAY VALUES
// ===============================

$completed_hours_display = rtrim(
    rtrim(number_format($completed_hours, 2), '0'),
    '.'
);

$remaining_hours_display = rtrim(
    rtrim(number_format($remaining_hours, 2), '0'),
    '.'
);

// ===============================
// WEEKLY HOURS DATA
// ===============================

$weekly_hours = [
    "Mon" => 0,
    "Tue" => 0,
    "Wed" => 0,
    "Thu" => 0,
    "Fri" => 0,
    "Sat" => 0,
    "Sun" => 0
];

$week_start = new DateTime("monday this week");
$week_end = new DateTime("sunday this week");

$weekly_sql = "
SELECT
    DAYNAME(work_date) AS day_name,
    SUM(hours) AS total_hours
FROM projects
WHERE user_id = '$user_id'
AND work_date BETWEEN 
'{$week_start->format('Y-m-d')}'
AND
'{$week_end->format('Y-m-d')}'
GROUP BY work_date
";

$weekly_result = $conn->query($weekly_sql);


while($row = $weekly_result->fetch_assoc()){

    $day = substr($row['day_name'], 0, 3);

    $weekly_hours[$day] = $row['total_hours'];
}

// ===============================
// MONTHLY HOURS DATA
// ===============================

$monthly_hours = [
    "Jan" => 0,
    "Feb" => 0,
    "Mar" => 0,
    "Apr" => 0,
    "May" => 0,
    "Jun" => 0,
    "Jul" => 0,
    "Aug" => 0,
    "Sep" => 0,
    "Oct" => 0,
    "Nov" => 0,
    "Dec" => 0
];

$monthly_breakdown = [];

$monthly_sql = "
SELECT
    MONTH(work_date) AS month_number,
    DATE_FORMAT(work_date, '%b') AS month,
    MONTHNAME(work_date) AS month_name,
    SUM(hours) AS total_hours,
    COUNT(DISTINCT work_date) AS sessions
FROM projects
WHERE user_id = '$user_id'
GROUP BY YEAR(work_date), MONTH(work_date)
ORDER BY YEAR(work_date), MONTH(work_date)
";

$monthly_result = $conn->query($monthly_sql);

while ($row = $monthly_result->fetch_assoc()) {

    // For Monthly Chart
    $monthly_hours[$row['month']] = (float)$row['total_hours'];

    // For Monthly Breakdown Table
    $monthly_breakdown[] = [
        "month" => $row['month_name'],
        "hours" => (float)$row['total_hours'],
        "sessions" => (int)$row['sessions']
    ];
}

$monthly_result = $conn->query($monthly_sql);

while ($row = $monthly_result->fetch_assoc()) {
    $monthly_hours[$row['month']] = $row['total_hours'];
}

// ===============================
// WEEKLY BREAKDOWN
// ===============================

$weekly_breakdown = [];

$weekly_breakdown_sql = "
SELECT
    YEAR(work_date) AS year,
    WEEK(work_date, 1) AS week_number,
    MIN(work_date) AS week_start,
    MAX(work_date) AS week_end,
    SUM(hours) AS total_hours,
    COUNT(DISTINCT work_date) AS sessions
FROM projects
WHERE user_id = '$user_id'
GROUP BY YEAR(work_date), WEEK(work_date,1)
ORDER BY YEAR(work_date), WEEK(work_date,1)
";

$weekly_breakdown_result = $conn->query($weekly_breakdown_sql);

$week = 1;

while ($row = $weekly_breakdown_result->fetch_assoc()) {

    $weekly_breakdown[] = [
        "week" => "Week " . $week .
            " (" .
            date("M j, Y", strtotime($row['week_start'])) .
            " - " .
            date("M j, Y", strtotime($row['week_end'])) .
            ")",

        "hours" => (float)$row['total_hours'],

        "sessions" => (int)$row['sessions']
    ];

    $week++;
}

// ===============================
// PROJECT DISTRIBUTION
// ===============================

$project_distribution = [];

$project_sql = "
SELECT
    project_name,
    SUM(hours) AS total_hours
FROM projects
WHERE user_id = '$user_id'
GROUP BY project_name
ORDER BY total_hours DESC
";

$project_result = $conn->query($project_sql);

$colors = [
    "#005669",
    "#0A9396",
    "#7C974B",
    "#EE9B00",
    "#F7CA7A",
    "#AE5A41",
    "#7E57C2",
    "#26A69A",
    "#5C6BC0",
    "#FF7043"
];

$colorIndex = 0;

while ($row = $project_result->fetch_assoc()) {

    $hours = (float)$row['total_hours'];

    $percentage = $completed_hours > 0
        ? round(($hours / $completed_hours) * 100, 1)
        : 0;

    $project_distribution[] = [
        "name" => $row['project_name'],
        "hours" => $hours,
        "percent" => $percentage,
        "color" => $colors[$colorIndex % count($colors)]
    ];

    $colorIndex++;
}

// ===============================
// WEEKLY COMPLETION PLAN
// ===============================

$weekly_plan = [];

if ($remaining_hours > 0) {

    $hours_per_day = (float)$user['hours_per_day'];

    $working_days_per_week = count($working_days);

    $max_weekly_hours = $hours_per_day * $working_days_per_week;

    $hours_left = $remaining_hours;

    while ($hours_left > 0) {

        $target = min($max_weekly_hours, $hours_left);

        $weekly_plan[] = round($target, 2);

        $hours_left -= $target;
    }

}

$balanced_plan = [];

if ($remaining_hours > 0) {

    $weeks_remaining = count($weekly_plan);

    $base = floor(($remaining_hours / $weeks_remaining) * 100) / 100;

    $remaining = $remaining_hours;

    for ($i = 0; $i < $weeks_remaining; $i++) {

        if ($i == $weeks_remaining - 1) {

            $target = round($remaining,2);

        } else {

            $target = $base;

        }

        $balanced_plan[] = $target;

        $remaining -= $target;
    }

}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>InternTrack | Reports</title>
    <link rel="icon" href="images/InternTrack.png" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    />

    <style>
      /* =========================
   RESET
========================= */

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

      /* =========================
   HEADER
========================= */

      .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
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

      /* =========================
   CARDS
========================= */

      .card {
        background: white;
        border-radius: 18px;
        padding: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
      }

      /* =========================
   SUMMARY STATS
========================= */

      .summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
      }

      .stat {
        background: linear-gradient(135deg, #005f73, #0a9396);
        color: #fff;
        text-align: center;
        padding: 24px;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(10, 147, 150, 0.22);
        transition:
          transform 0.25s ease,
          box-shadow 0.25s ease;
      }

      .stat:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(10, 147, 150, 0.35);
      }

      .stat h3 {
        margin: 0 0 12px;
        font-size: 16px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
      }

      .stat strong {
        display: block;
        font-size: 25px;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
      }

      /* Expected Completion Date */
      .stat:last-child strong {
        font-size: 25px;
      }

      /* =========================
   SIDE BY SIDE
========================= */

      .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
      }

      /* =========================
   REPORT LIST
========================= */

      .report-list div {
        padding: 12px 0;
        border-bottom: 1px solid #eee;
        font-weight: 500;
      }

      .report-list div:last-child {
        border-bottom: none;
      }

      /* =========================
   BUTTONS
========================= */

      .btn {
        display: inline-block;
        padding: 12px 18px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-weight: bold;
        margin-right: 10px;
      }

      .btn-primary {
        background: #ee9b00;
        color: white;
      }

      .btn-primary:hover {
        background: #d98f00;
      }

      /* ==========================================
   CHART GRID
========================================== */

      .charts-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 20px;
      }
      /* ==========================================
   BAR CHARTS (Shared)
========================================== */

      .chart-container {
        width: 100%;
        height: 380px;
        background: white;
        padding: 24px;
        border-radius: 18px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      }

      .chart-title {
        font-size: 18px;
        margin-bottom: 20px;
        font-weight: bold;
        color: #005f73;
      }

      .chart {
        display: flex;
        align-items: flex-end;
        justify-content: space-evenly;
        width: 100%;
        height: 270px;
        padding: 12px;
        gap: 14px;
      }

      .bar-wrapper {
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
        flex: 1;
        min-width: 0;
        height: 100%;
      }

      .bar {
        border-radius: 8px 8px 0 0;
        transition:
          height 0.35s ease,
          width 0.35s ease;
      }

      .label {
        margin-top: 8px;
        font-size: clamp(12px, 1vw, 15px);
        color: #333;
        text-align: center;
      }

      .value {
        margin-bottom: 5px;
        font-size: clamp(12px, 1vw, 15px);
        color: #005f73;
        font-weight: 600;
      }

      /* ==========================================
   WEEKLY HOURS CHART
========================================== */

      .weekly-bar {
        width: clamp(40px, 4vw, 100px);
        background: linear-gradient(180deg, #0a9396, #005f73);
      }

      /* ==========================================
   MONTHLY HOURS CHART
========================================== */

      .monthly-bar {
        width: clamp(40px, 4vw, 100px);
        background: linear-gradient(180deg, #ee9b00, #ca6702);
      }

      /* ==========================================
   RESPONSIVE
========================================== */

      @media (max-width: 992px) {
        .charts-grid {
          grid-template-columns: 1fr;
        }

        .chart-container {
          height: 340px;
        }

        .chart {
          height: 230px;
          gap: 10px;
        }

        .weekly-bar {
          width: clamp(20px, 5vw, 50px);
        }

        .monthly-bar {
          width: clamp(30px, 7vw, 65px);
        }
      }
      /* MONTHLY BREAKDOWN TABLE */

      .table-container {
        overflow-x: auto;
      }

      .monthly-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
      }

      .monthly-table thead {
        background: #005f73;
        color: white;
      }

      .monthly-table th,
      .monthly-table td {
        padding: 14px 18px;
        text-align: center;
      }

      .monthly-table tbody tr:nth-child(even) {
        background: #f8f8f8;
      }

      .monthly-table tbody tr:hover {
        background: #e8f7f7;
      }

      .monthly-table th:first-child,
      .monthly-table td:first-child {
        text-align: left;
      }

      .monthly-table td {
        font-weight: 500;
      }

      /* Monthly Total Row */
      .monthly-table tbody tr:last-child td {
        background: rgb(238, 156, 85);
      }

      .monthly-table tbody tr:last-child td:first-child {
        border-bottom-left-radius: 8px;
      }

      .monthly-table tbody tr:last-child td:last-child {
        border-bottom-right-radius: 8px;
      }

      /* ==========================================
   WEEKLY BREAKDOWN TABLE
========================================== */

      .weekly-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        margin-top: 15px;
      }

      .weekly-table thead {
        background: #005f73;
        color: #fff;
      }

      .weekly-table th,
      .weekly-table td {
        padding: 14px 18px;
        vertical-align: middle;
        font-weight: 500;
      }

      /* Weekly Total Row */
      .weekly-table tbody tr:last-child td {
        background: rgb(238, 156, 85);
      }

      .weekly-table tbody tr:last-child td:first-child {
        border-bottom-left-radius: 8px;
      }

      .weekly-table tbody tr:last-child td:last-child {
        border-bottom-right-radius: 8px;
      }

      /* ==========================
   COLUMN WIDTHS
========================== */

      /* Rounded top-left corner */
      .weekly-table thead th:first-child,
      .monthly-table thead th:first-child {
        border-top-left-radius: 8px;
      }

      /* Rounded top-right corner */
      .weekly-table thead th:last-child,
      .monthly-table thead th:last-child {
        border-top-right-radius: 8px;
      }

      /* Week */
      .weekly-table th:nth-child(1),
      .weekly-table td:nth-child(1) {
        width: 19%;
        text-align: left;
        white-space: normal;
        word-break: break-word;
        line-height: 1.45;
      }

      /* Hours */
      .weekly-table th:nth-child(2),
      .weekly-table td:nth-child(2) {
        width: 19%;
        text-align: center;
      }

      /* Sessions */
      .weekly-table th:nth-child(3),
      .weekly-table td:nth-child(3) {
        width: 19%;
        text-align: center;
      }

      /* Avg/Day */
      .weekly-table th:nth-child(4),
      .weekly-table td:nth-child(4) {
        width: 20%;
        text-align: center;
      }

      /* ==========================
   ROW STYLING
========================== */

      .weekly-table tbody tr:nth-child(even) {
        background: #f8f8f8;
      }

      .weekly-table tbody tr:hover {
        background: #e8f7f7;
      }
      /* ==========================
   PROJECT DISTRIBUTION CARD
========================== */

      .chart-card {
        background: #fff;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
      }

      .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
      }

      .chart-header h2 {
        font-size: 20px;
        color: #1f2937;
      }

      .chart-header span {
        color: #6b7280;
        font-size: 14px;
      }

      .pie-wrapper {
        width: 400px;
        height: 400px;
        margin: auto;
      }

      .project-distribution {
        display: grid;
        grid-template-columns: 340px 1fr;
        gap: 35px;
        align-items: center;
        margin-top: 20px;
      }

      .pie-wrapper {
        width: 100%;
        max-width: 320px;
        margin: auto;
      }

      .chart-subtitle {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 14px;
      }

      .project-ranking {
        display: flex;
        flex-direction: column;
        gap: 14px;
      }

      .project-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
        border-radius: 12px;
        background: #f8f8f8;
      }

      .project-item:hover {
        background: #e3edf3;
      }

      .rank-number {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #005f73;
        color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: bold;
        flex-shrink: 0;
      }

      .project-color {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        flex-shrink: 0;
      }

      .project-details {
        flex: 1;
      }

      .project-name {
        font-weight: 600;
        color: #005f73;
      }

      .project-hours {
        color: #666;
        font-size: 13px;
      }

      .project-percent {
        font-weight: 700;
        color: #005f73;
      }

      @media (max-width: 900px) {
        .project-distribution {
          grid-template-columns: 1fr;
        }

        .pie-wrapper {
          max-width: 280px;
        }
      }

      /* ==========================================
   WEEKLY COMPLETION PLAN
========================================== */

      .plan-header {
        margin-bottom: 20px;
      }

      .plan-header h2 {
        margin: 0 0 6px;
        color: var(--primary);
        font-size: 22px;
        font-weight: 700;
      }

      .plan-header p {
        margin: 0;
        color: #6b7280;
        font-size: 14px;
        line-height: 1.6;
      }

      /* ==========================================
   TOGGLE BUTTONS
========================================== */

      .plan-controls {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;

        width: 100%;
        margin: 20px 0 24px;

        padding: 6px;

        background: #dceff0;
        border: 1px solid #c8e5e5;
        border-radius: 12px;
      }

      .plan-btn {
        width: 100%;

        padding: 14px 18px;

        border: none;
        border-radius: 10px;

        background: transparent;

        color: #5f6b6d;

        font-size: 15px;
        font-weight: 700;

        cursor: pointer;

        transition: 0.25s;
      }

      .plan-btn:hover {
        background: rgba(0, 95, 115, 0.08);
        color: var(--primary);
      }

      .plan-btn.active {
        background: #14708a;
        color: #fff;
        box-shadow: 0 6px 18px rgba(10, 147, 150, 0.25);
      }

      .plan-btn:focus {
        outline: none;
      }

      /* ==========================================
   TABLE
========================================== */

      .plan-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
      }

      .plan-table thead {
        background: #005f73;
      }

      .plan-table th {
        padding: 14px 18px;
        text-align: left;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.3px;
      }

      .plan-table td {
        padding: 14px 18px;
        border-bottom: 1px solid rgba(0, 95, 115, 0.08);
        font-size: 14px;
        color: #374151;
      }

      .plan-table tbody tr:nth-child(even) {
        background: #fafdfd;
      }

      .plan-table tbody tr {
        transition: background 0.2s ease;
      }

      .plan-table tbody tr:hover {
        background: #e7f6f6;
      }

      .plan-table td:last-child {
        text-align: right;
        font-weight: 700;
        font-size: 15px;
        color: #005f73;
      }

      /* ==========================================
   MOBILE
========================================== */

      @media (max-width: 768px) {
        .plan-controls {
          flex-direction: column;
        }

        .plan-btn {
          width: 100%;
        }
      }

      /* =========================
   RESPONSIVE
========================= */

      @media (max-width: 900px) {
        .sidebar {
          width: 210px;
        }
        .main {
          margin-left: 210px;
          width: calc(100% - 210px);
        }
        .grid-2 {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 768px) {
        body {
          flex-direction: column;
        }

        .sidebar {
          position: relative;
          width: 100%;
          height: auto;
        }

        .main {
          margin-left: 0;
          width: 100%;
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
            <a href="calendar.php">
              <i class="bi bi-calendar-event"></i>
              <span>Calendar</span>
            </a>
          </li>

          <li>
            <a href="reports.php" class="active">
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
      <!-- HEADER -->
      <div class="header">
        <div>
          <h1>Reports</h1>
          <p>View internship analytics and export reports.</p>
        </div>

        <div class="date">
          📅 <?= date("F j, Y"); ?>
        </div>
      </div>

      <!-- SUMMARY -->
      <div class="card summary">

        <div class="stat">
          <h3>Completed Hours</h3>
          <strong>
            <?= $completed_hours_display; ?>
          </strong>
        </div>


        <div class="stat">
          <h3>Remaining Hours</h3>
          <strong>
            <?= $remaining_hours; ?>
          </strong>
        </div>


        <div class="stat">
          <h3>Total Required Hours</h3>
          <strong>
            <?= $required_hours; ?>
          </strong>
        </div>


        <div class="stat">
          <h3>Progress</h3>
          <strong>
            <?= $progress_display; ?>
          </strong>
        </div>


        <div class="stat">
          <h3>Expected Completion</h3>
          <strong>
            <?= $completion_date_display; ?>
          </strong>
        </div>

      </div>

      <!-- CHARTS -->
      <div class="charts-grid">
        <div class="chart-container">
          <div class="chart-title">Weekly Hours</div>
          <div class="chart" id="weeklyChart"></div>
        </div>

        <div class="chart-container">
          <div class="chart-title">Monthly Hours</div>
          <div class="chart" id="monthlyChart"></div>
        </div>
      </div>

      <!-- WEEKLY BREAKDOWN -->

      <div class="card">
        <h2>Weekly Breakdown</h2>

        <div class="table-container">
          <table class="weekly-table">
            <thead>
              <tr>
                <th>Week</th>
                <th>Hours</th>
                <th>Sessions</th>
                <th>Avg/Day</th>
              </tr>
            </thead>

            <tbody id="weekly-table-body">
              <!-- Generated by JavaScript -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- MONTHLY BREAKDOWN-->
      <div class="card">
        <h2>Monthly Breakdown</h2>

        <div class="table-container">
          <table class="monthly-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Hours</th>
                <th>Sessions</th>
                <th>Avg/Day</th>
              </tr>
            </thead>

            <tbody id="monthly-table-body">
              <!-- Generated by JavaScript -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- SIDE BY SIDE CHARTS -->
      <div class="card">
        <h2>Project Distribution</h2>
        <span class="chart-subtitle">Time Spent per Project</span>

        <div class="project-distribution">
          <div class="pie-wrapper">
            <canvas id="projectPie"></canvas>
          </div>

          <div class="project-ranking" id="projectRanking"></div>
        </div>
      </div>

      <!-- WEEKLY COMPLETION PLAN -->

      <div class="card">
        <div class="plan-header">
          <h2>Weekly Completion Plan</h2>
          <p>
            Recommended hours to render each remaining week in order to complete
            the required OJT hours.
          </p>
        </div>

        <div class="plan-controls">
          <button type="button" class="plan-btn active" id="standardBtn">
            Standard Schedule
          </button>

          <button type="button" class="plan-btn" id="balancedBtn">
            Balanced Plan
          </button>
        </div>

        <table class="plan-table">
          <thead>
            <tr>
              <th>Remaining Week</th>
              <th>Target Hours</th>
            </tr>
          </thead>

          <tbody id="weeklyPlanTable"></tbody>
        </table>
      </div>
    </div>
    <script>
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
      // ==========================
      // WEEKLY HOURS BAR CHART
      // ==========================

      const weeklyData = <?= json_encode([
      ["day"=>"Mon","hours"=>$weekly_hours["Mon"]],
      ["day"=>"Tue","hours"=>$weekly_hours["Tue"]],
      ["day"=>"Wed","hours"=>$weekly_hours["Wed"]],
      ["day"=>"Thu","hours"=>$weekly_hours["Thu"]],
      ["day"=>"Fri","hours"=>$weekly_hours["Fri"]],
      ["day"=>"Sat","hours"=>$weekly_hours["Sat"]],
      ["day"=>"Sun","hours"=>$weekly_hours["Sun"]],
      ]); ?>;

      const weeklyChart = document.querySelector("#weeklyChart");

      if (!weeklyChart) {
        console.error("weeklyChart element not found.");
      }

      function renderWeeklyChart() {
        weeklyChart.innerHTML = "";

        const maxWeeklyHours = Math.max(
          ...weeklyData.map((item) => item.hours),
          1,
        );

        weeklyData.forEach((item) => {
          const wrapper = document.createElement("div");
          wrapper.className = "bar-wrapper";

          const value = document.createElement("div");
          value.className = "value";
          value.textContent = item.hours;

          const bar = document.createElement("div");
          bar.className = "bar weekly-bar";
          bar.style.height = `${(item.hours / maxWeeklyHours) * 100}%`;

          const label = document.createElement("div");
          label.className = "label";
          label.textContent = item.day;

          wrapper.appendChild(value);
          wrapper.appendChild(bar);
          wrapper.appendChild(label);

          weeklyChart.appendChild(wrapper);
        });
      }

      renderWeeklyChart();
    </script>
    <script>
      // ==========================
      // MONTHLY HOURS BAR CHART
      // ==========================

      const monthlyHoursData = <?= json_encode([
        ["month"=>"Jan","hours"=>$monthly_hours["Jan"]],
        ["month"=>"Feb","hours"=>$monthly_hours["Feb"]],
        ["month"=>"Mar","hours"=>$monthly_hours["Mar"]],
        ["month"=>"Apr","hours"=>$monthly_hours["Apr"]],
        ["month"=>"May","hours"=>$monthly_hours["May"]],
        ["month"=>"Jun","hours"=>$monthly_hours["Jun"]],
        ["month"=>"Jul","hours"=>$monthly_hours["Jul"]],
        ["month"=>"Aug","hours"=>$monthly_hours["Aug"]],
        ["month"=>"Sep","hours"=>$monthly_hours["Sep"]],
        ["month"=>"Oct","hours"=>$monthly_hours["Oct"]],
        ["month"=>"Nov","hours"=>$monthly_hours["Nov"]],
        ["month"=>"Dec","hours"=>$monthly_hours["Dec"]],
      ]); ?>.filter(item => item.hours > 0);

      const monthlyChart = document.querySelector("#monthlyChart");

      if (!monthlyChart) {
        console.error("monthlyChart element not found.");
      }

      function renderMonthlyChart() {
        monthlyChart.innerHTML = "";

        const maxMonthlyHours = Math.max(
          ...monthlyHoursData.map((item) => item.hours),
          1,
        );

        monthlyHoursData.forEach((item) => {
          const wrapper = document.createElement("div");
          wrapper.className = "bar-wrapper";

          const value = document.createElement("div");
          value.className = "value";
          value.textContent = item.hours;

          const bar = document.createElement("div");
          bar.className = "bar monthly-bar";
          bar.style.height = `${(item.hours / maxMonthlyHours) * 100}%`;

          const label = document.createElement("div");
          label.className = "label";
          label.textContent = item.month;

          wrapper.appendChild(value);
          wrapper.appendChild(bar);
          wrapper.appendChild(label);

          monthlyChart.appendChild(wrapper);
        });
      }

      renderMonthlyChart();
    </script>
    <script>
      // Monthly Breakdown Data
      const monthlyData = <?= json_encode($monthly_breakdown); ?>;

      function renderMonthlyBreakdown() {
        const tbody = document.getElementById("monthly-table-body");

        tbody.innerHTML = "";

        let totalHours = 0;
        let totalSessions = 0;

        monthlyData.forEach((item) => {
          const avg = (item.hours / item.sessions).toFixed(1);

          totalHours += item.hours;
          totalSessions += item.sessions;

          tbody.innerHTML += `
        <tr>
          <td>${item.month}</td>
          <td>${item.hours}</td>
          <td>${item.sessions}</td>
          <td>${avg}</td>
        </tr>
      `;
        });

        tbody.innerHTML += `
      <tr style="background:#005f73;color:#fff;font-weight:bold;">
        <td>Total</td>
        <td>${totalHours}</td>
        <td>${totalSessions}</td>
        <td>${(totalHours / totalSessions).toFixed(1)}</td>
      </tr>
    `;
      }

      renderMonthlyBreakdown();
    </script>
    <script>
      // ======================================
      // WEEKLY BREAKDOWN
      // ======================================

      const weeklyBreakdownData = <?= json_encode($weekly_breakdown); ?>;
      function renderWeeklyBreakdown() {
        const tbody = document.getElementById("weekly-table-body");

        tbody.innerHTML = "";

        let totalHours = 0;
        let totalSessions = 0;

        weeklyBreakdownData.forEach((item) => {
          const avg = (item.hours / item.sessions).toFixed(1);

          totalHours += item.hours;
          totalSessions += item.sessions;

          tbody.innerHTML += `
      <tr>
        <td>${item.week}</td>
        <td>${item.hours}</td>
        <td>${item.sessions}</td>
        <td>${avg}</td>
      </tr>
    `;
        });

        tbody.innerHTML += `
    <tr style="background:#005f73;color:#fff;font-weight:bold;">
      <td>Total</td>
      <td>${totalHours}</td>
      <td>${totalSessions}</td>
      <td>${(totalHours / totalSessions).toFixed(1)}</td>
    </tr>
  `;
      }

      renderWeeklyBreakdown();
    </script>
    <script>
      // Project data (used by BOTH the pie chart and ranking)

      const projectData = <?= json_encode($project_distribution); ?>;

      projectData.sort((a, b) => b.hours - a.hours);

      const ranking = document.getElementById("projectRanking");

      ranking.innerHTML = "";

      projectData.forEach((project, index) => {
        const percent = project.percent.toFixed(1);

        ranking.innerHTML += `
    <div class="project-item">

      <div class="rank-number">
        ${index + 1}
      </div>

      <div
        class="project-color"
        style="background:${project.color}">
      </div>

      <div class="project-details">
        <div class="project-name">${project.name}</div>
        <div class="project-hours">${project.hours} hrs</div>
      </div>

      <div class="project-percent">
        ${percent}%
      </div>

    </div>
  `;
      });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script.js"></script>
    <script>
      // Project Distribution Pie Chart
      const ctx = document.getElementById("projectPie");

      new Chart(ctx, {
        type: "pie",
        data: {
          labels: projectData.map((project) => project.name),

          datasets: [
            {
              data: projectData.map((project) => project.hours),

              backgroundColor: projectData.map((project) => project.color),
              borderColor: "#ffffff",
              borderWidth: 3,
              hoverOffset: 12,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              callbacks: {
                label: function (context) {
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const value = context.raw;
                  const percentage = ((value / total) * 100).toFixed(1);
                  return `${context.label}: ${value} hrs (${percentage}%)`;
                },
              },
            },
          },
          animation: {
            animateRotate: true,
            animateScale: true,
            duration: 1400,
            easing: "easeOutQuart",
          },
        },
      });
    </script>
    <script>
      // ======================================
      // WEEKLY COMPLETION PLAN
      // ======================================

      const remainingHours = <?= $remaining_hours ?>;

      const standardPlan = <?= json_encode($weekly_plan) ?>;

      const balancedPlan = <?= json_encode($balanced_plan) ?>;


      // ======================================

      const table = document.getElementById("weeklyPlanTable");

      const standardBtn = document.getElementById("standardBtn");
      const balancedBtn = document.getElementById("balancedBtn");

      // Default
      generateWeeklyPlan("standard");

      // ======================================

      standardBtn.addEventListener("click", () => {
        standardBtn.classList.add("active");
        balancedBtn.classList.remove("active");

        generateWeeklyPlan("standard");
      });

      balancedBtn.addEventListener("click", () => {
        balancedBtn.classList.add("active");
        standardBtn.classList.remove("active");

        generateWeeklyPlan("balanced");
      });

      // ======================================

      function generateWeeklyPlan(mode) {

    table.innerHTML = "";

    if (remainingHours <= 0) {

        table.innerHTML = `
            <tr>
                <td colspan="2">
                    🎉 Required OJT Hours Completed
                </td>
            </tr>
        `;

        return;
    }

    const plan = mode === "standard"
        ? standardPlan
        : balancedPlan;

    plan.forEach((hours, index)=>{

        table.innerHTML += `
            <tr>
                <td>Week ${index + 1}</td>
                <td>${hours} hrs</td>
            </tr>
        `;

    });

}
    </script>
  </body>
</html>
