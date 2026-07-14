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

$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);

$user = $result->fetch_assoc();

$required_hours = $user['required_hours'];

// Get total completed hours from projects table
$result = $conn->query("
    SELECT SUM(hours) AS completed_hours
    FROM projects
    WHERE user_id = '$user_id'
");

$row = $result->fetch_assoc();
$completed_hours = $row['completed_hours'] ?? 0;
$remaining_hours = max(0, $required_hours - $completed_hours);

$progress = 0;
if ($required_hours > 0) {
    $progress = round(($completed_hours / $required_hours) * 100);
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
   WELCOME CARD
========================== */

      .welcome {
        background: linear-gradient(135deg, #005f73, #0a9396);
        color: white;
        padding: 35px;
        border-radius: 18px;
        margin-bottom: 35px;
      }

      .welcome h2 {
        font-size: 30px;
        margin-bottom: 10px;
      }

      .welcome p {
        font-size: 16px;
        opacity: 0.95;
      }

      /* ========================== SUMMARY CARDS ========================== */
      .summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 35px;
      }
      .card {
        background: white;
        border-radius: 18px;
        padding: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        transition: 0.3s;
      }
      .card:hover {
        transform: translateY(-5px);
      }
      .card h3 {
        font-size: 16px;
        color: #666;
        margin-bottom: 12px;
      }
      .card .number {
        font-size: 36px;
        font-weight: bold;
        color: #005f73;
      }
      .card span {
        display: block;
        margin-top: 8px;
        color: #0a9396;
        font-weight: 600;
      }
      /* ==========================
   PROGRESS
========================== */

      .progress-card {
        background: white;
        border-radius: 18px;
        padding: 30px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      }

      .progress-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
      }

      .progress-info h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #005f73;
      }

      .progress-percent {
        font-size: 30px;
        font-weight: 700;
        color: #005f73;
      }

      .progress-bar {
        width: 100%;
        height: 20px;
        background: #ddd;
        border-radius: 20px;
        overflow: hidden;
      }

      .progress-fill {
        width: var(--progress);
        height: 100%;
        border-radius: 20px;
        background: linear-gradient(to right, #d97706, #fbbf24);
        transition: width 0.5s ease;
      }

      /* ==========================
   RESPONSIVE
========================== */

      @media (max-width: 900px) {
        .sidebar {
          width: 210px;
        }

        .main {
          margin-left: 210px;
          width: calc(100% - 210px);
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

        .header {
          flex-direction: column;
          align-items: flex-start;
          gap: 15px;
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
            <a href="dashboard.php" class="active">
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
      <!-- HEADER -->

      <div class="header">
        <div>
          <h1>Dashboard</h1>
          <p>Monitor your internship progress and attendance.</p>
        </div>

        <div class="date">
          📅 <?= date("F j, Y"); ?>
        </div>
      </div>

      <!-- WELCOME -->

      <div class="welcome">
        <h2>Welcome back, <?= $user['first_name']; ?>!</h2>

        <p>
          Keep tracking your internship hours and stay on top of your OJT
          requirements. You're making great progress toward completing your
          practicum.
        </p>
      </div>

      <!-- SUMMARY -->

      <div class="summary">
        <div class="card">
          <h3>Remaining Hours</h3>

          <div class="number">
            <?= $remaining_hours; ?>
          </div>

          <span>Hours Left</span>
        </div>

        <div class="card">
          <h3>Completed Hours</h3>

          <div class="number">
            <?= rtrim(rtrim(number_format($completed_hours, 2), '0'), '.'); ?>
          </div>

          <span>Hours Completed</span>
        </div>

        <div class="card">
          <h3>Total Required Hours</h3>

          <div class="number">
            <?= $required_hours; ?>
          </div>

          <span>Required OJT Hours</span>
        </div>
      </div>

      <!-- PROGRESS -->

      <div class="progress-card">
        <div class="progress-info">
          <h2>Overall Internship Progress</h2>
          <span class="progress-percent">
            <?= $progress; ?>%
          </span>
        </div>

        <div class="progress-bar">
          <div class="progress-fill" style="--progress: <?= min($progress, 100); ?>%;"></div>
        </div>
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
  </body>
</html>
