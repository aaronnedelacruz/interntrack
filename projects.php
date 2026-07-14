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

$result = $conn->query("SELECT * FROM users WHERE id = '$user_id'");
$user = $result->fetch_assoc();

$projects = $conn->query("
    SELECT *
    FROM projects
    WHERE user_id = '$user_id'
    ORDER BY work_date DESC, start_time DESC
");
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
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
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

      :root {
        /* Colors */
        --primary: #005f73;
        --accent: #0a9396;
        --warning: #ee9b00;
        --background: #fff9f4;
        --surface: #ffffff;

        /* Text */
        --text-primary: #005f73;
        --text-secondary: #555;
        --text-muted: #777;

        /* Borders */
        --border: #ddd;
        --border-light: #ececec;
        --header-bg: #eef8f8;
        --header-border: #d9ecec;

        /* Shadows */
        --shadow: 0 10px 25px rgba(0, 0, 0, 0.08);

        /* Radius */
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 18px;

        /* Animation */
        --transition: 0.2s ease;
      }

      body {
        background: #fff9f4;
        display: flex;
        color: #005f73;
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
   QUICK ACTIONS
========================== */

      .quick-actions {
        display: flex;
        gap: 16px;
        margin-bottom: 30px;
      }

      .quick-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;

        padding: 16px 20px;

        border: none;
        border-radius: 16px;

        font-size: 16px;
        font-weight: 600;

        color: #fff9f4;
        cursor: pointer;

        transition: all 0.25s ease;
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
      }

      .quick-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
      }

      .quick-btn span:first-child {
        font-size: 22px;
        font-weight: 700;
      }

      /* New Project */

      .project-btn {
        background: linear-gradient(135deg, #005669, #0a9396);
      }

      .project-btn:hover {
        background: linear-gradient(135deg, #005f73, #0a9396);
      }

      /* Start Timer */

      .timer-btn {
        background: linear-gradient(135deg, #d76b00, #ffba39);
        color: #fff9f4;
      }

      .timer-btn:hover {
        background: linear-gradient(135deg, #b85d00, #d98f00);
      }

      /* ==========================
      NEW LOG MODAL
      ========================== */

      .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.45);
        justify-content: center;
        align-items: center;
        z-index: 9999;
      }

      .modal-content {
        background: #fff;

        width: 600px;
        max-width: 90%;

        padding: 30px;

        border-radius: 16px;

        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
      }

      .modal-header {
        display: flex;

        justify-content: space-between;
        align-items: center;

        margin-bottom: 25px;
      }

      .modal-header h2 {
        margin: 0;
      }

      .close {
        font-size: 30px;

        cursor: pointer;

        font-weight: bold;
      }

      .input-group {
        display: flex;

        flex-direction: column;

        margin-bottom: 18px;
      }

      .input-group label {
        font-weight: 600;

        margin-bottom: 8px;
      }

      .input-group input,
      .input-group textarea {
        width: 100%;

        padding: 12px;

        border: 1px solid #ddd;

        border-radius: 10px;

        font-size: 15px;
      }

      .input-group textarea {
        resize: vertical;
      }

      .time-row {
        display: flex;

        gap: 20px;
      }

      .time-row .input-group {
        flex: 1;
      }

      .save-log-btn {
        width: 100%;

        padding: 14px;

        border: none;

        border-radius: 10px;

        background: #0A9396;

        color: white;

        font-size: 16px;

        font-weight: 600;

        cursor: pointer;

        transition: 0.3s;
      }

      .save-log-btn:hover {
        background: #087f81;
      }

      /* ==========================
   TABLE TOOLBAR
========================== */

      .table-toolbar {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 20px;
      }

      /* Shared styling */

      .table-toolbar input,
      .table-toolbar select {
        height: 44px;
        padding: 0 14px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 15px;
        background: #fff;
        transition: border-color 0.2s ease;
      }

      .table-toolbar input:focus,
      .table-toolbar select:focus {
        border-color: #0a9396;
        outline: none;
      }

      /* Search */

      .search-box {
        position: relative;
        flex: 1 1 320px;
        min-width: 280px;
      }

      .search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
        pointer-events: none;
      }

      .search-box input {
        width: 100%;
        padding-left: 40px;
      }

      /* ==========================
   INPUT & SELECT WRAPPERS
========================== */

      .input-box,
      .select-box {
        position: relative;
        display: flex;
        align-items: center;
      }

      .input-box i {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
        pointer-events: none;
        font-size: 16px;
      }

      .select-box i {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
        pointer-events: none;
        font-size: 14px;
      }

      .input-box input {
        width: 100%;
        padding-right: 42px;
      }

      .select-box select {
        width: 100%;
        padding-right: 42px;

        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
      }

      .input-box input[type="date"]::-webkit-calendar-picker-indicator {
        opacity: 0;
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
      }

      /* Individual filter widths */

      .filter-date {
        flex: 0 0 310px;
      }

      .filter-view {
        flex: 0 0 180px;
      }

      .filter-sort {
        flex: 0 0 180px;
      }

      /* ==========================
      SUCCESS MESSAGE
      ========================== */

      .success-message {
          background: #d1fae5;
          color: #065f46;
          padding: 12px 16px;
          margin: 20px 0;
          border-radius: 10px;
          border-left: 5px solid #10b981;
          font-weight: 600;
      }

      /* ==========================
   WORK ACCOMPLISHMENTS TABLE
========================== */

      .table-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      }

      /* ---------- Header ---------- */

      .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 22px;
      }

      .table-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #005f73;
      }

      .table-year {
        font-size: 30px;
        font-weight: 700;
        color: #f0a51a;
        letter-spacing: 1px;
      }

      /* ---------- Table ---------- */

      .project-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
      }

      /* ---------- Header Row ---------- */

      .project-table thead th {
        background: #005f73;
        color: #fff9f4;
        padding: 16px 18px;
        text-align: left;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0.3px;
      }

      .project-table thead th:first-child {
        border-top-left-radius: 12px;
      }

      .project-table thead th:last-child {
        border-top-right-radius: 12px;
        text-align: center;
      }

      /* ---------- Body ---------- */

      .project-table tbody tr {
        transition: all 0.25s ease;
      }

      .project-table tbody tr:hover {
        background: #f5fbfc;
      }

      .project-table tbody td {
        padding: 18px;
        font-size: 15px;
        color: #555;
        border-bottom: 1px solid #ececec;
        vertical-align: middle;
      }

      /* Project */

      .project-table tbody td:first-child {
        font-weight: 600;
        color: #005f73;
      }

      /* Activity */

      .project-table tbody td:nth-child(2) {
        color: #444;
        font-weight: 500;
      }

      /* Hours */

      .project-table tbody td:nth-child(3) {
        font-weight: 700;
        color: #0a9396;
        white-space: nowrap;
      }

      /* Date */

      .project-table tbody td:nth-child(4) {
        color: #666;
        white-space: nowrap;
      }

      /* Actions */

      .project-table td:last-child,
      .project-table th:last-child {
        text-align: center;
        white-space: nowrap;
      }

      .project-table td:last-child i {
        font-size: 17px;
        margin: 0 7px;
        cursor: pointer;
        color: #005f73;
        transition: all 0.2s ease;
      }

      .project-table td:last-child .bi-pencil-square:hover {
        color: #0a9396;
        transform: scale(1.12);
      }

      .project-table td:last-child .bi-trash:hover {
        color: #ee9b00;
        transform: scale(1.12);
      }

      /* Remove last border */

      .project-table tbody tr:last-child td {
        border-bottom: none;
      }

      /* Zebra striping */

      .project-table tbody tr:nth-child(even) {
        background: #fcfdfd;
      }

      .project-table tbody tr:nth-child(even):hover {
        background: #f5fbfc;
      }
      /* ==========================
   RESPONSIVE TABLE
========================== */

      @media (max-width: 900px) {
        .table-card {
          overflow-x: auto;
        }

        .project-table {
          min-width: 800px;
        }
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
            <a href="dashboard.php">
              <i class="bi bi-ui-checks-grid"></i>
              <span>Dashboard</span>
            </a>
          </li>

          <li>
            <a href="projects.php" class="active">
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
          <h1>My Projects</h1>

          <p>Manage internship projects and work sessions.</p>
        </div>

        <div class="date">
          📅 <?= date("F j, Y"); ?>
        </div>
      </div>

      <!-- QUICK ACTIONS -->

      <div class="quick-actions">
        <button class="quick-btn project-btn" onclick="openModal()">
          <span>+</span>
          <span>New Log</span>
        </button>

        <button class="quick-btn timer-btn">
          <span>▶</span>
          <span>Start Timer</span>
        </button>
      </div>

      <div id="logModal" class="modal">
        <div class="modal-content">

          <div class="modal-header">
            <h2>New Work Log</h2>
            <span class="close" onclick="closeModal()">&times;</span>
          </div>

          <form action="projects_add.php" method="POST">

            <div class="input-group">
              <label>Project Name</label>
              <input
                type="text"
                name="project_name"
                required
              >
            </div>

            <div class="input-group">
              <label>Activity</label>

              <textarea
                name="activity"
                rows="4"
                required
              ></textarea>
            </div>

            <div class="input-group">
              <label>Date</label>

              <input
                type="date"
                id="work_date"
                name="work_date"
                required
              >
            </div>

            <div class="time-row">

              <div class="input-group">
                <label>Start Time</label>

                <input
                  type="time"
                  id="start_time"
                  name="start_time"
                  required
                >
              </div>

              <div class="input-group">
                <label>End Time</label>

                <input
                  type="time"
                  id="end_time"
                  name="end_time"
                  required
                >
              </div>

            </div>

            <div class="input-group">
              <label>Hours</label>

              <input
                  type="number"
                  id="hours"
                  name="hours"
                  step="0.25"
                  readonly
              >
            </div>

            <button class="save-log-btn">
              Save Log
            </button>

          </form>
        </div>
      </div>

      <!-- SUCCESS MESSAGE -->
      <?php if (isset($_GET['success'])): ?>
          <div class="success-message">
            <i class="bi bi-check-circle-fill"></i>
             Work log saved successfully.
          </div>
      <?php endif; ?>

      <!-- TABLE -->

      <div class="table-card">
        <div class="table-header">
          <h2>Work Accomplishments</h2>
          <span class="table-year">2026</span>
        </div>

        <div class="table-toolbar">
          <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search logs" />
          </div>

          <div class="input-box filter-date">
            <input id="filter-date" type="text" placeholder="Select Date" />
            <i class="bi bi-calendar"></i>
          </div>

          <div class="select-box filter-view">
            <select>
              <option>Weekly View</option>
              <option>Monthly View</option>
            </select>
            <i class="bi bi-chevron-down"></i>
          </div>

          <div class="select-box filter-sort">
            <select>
              <option>Sort by Latest</option>
              <option>Sort by Oldest</option>
              <option>Highest Hours</option>
              <option>Lowest Hours</option>
            </select>
            <i class="bi bi-chevron-down"></i>
          </div>
        </div>

        <table class="project-table">
          <thead>
            <tr>
              <th>Project</th>
              <th>Activity</th>
              <th>Hours</th>
              <th>Date and Time</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody>
            <?php while($row = $projects->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['project_name']); ?></td>
                <td><?= htmlspecialchars($row['activity']); ?></td>
                <td><?= $row['hours']; ?></td>
                <td>
                    <?= date("F j, Y", strtotime($row['work_date'])); ?>
                    •
                    <?= date("g:i A", strtotime($row['start_time'])); ?>
                    –
                    <?= date("g:i A", strtotime($row['end_time'])); ?>
                </td>

                <td>
                    <i class="bi bi-pencil-square"></i>
                    <i class="bi bi-trash"></i>
                </td>
            </tr>
            <?php endwhile; ?>

          </tbody>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
      flatpickr("#filter-date", {
        dateFormat: "M j, Y",
        allowInput: true,
      });
    </script>
    <script>
      const start = document.querySelector("[name='start_time']");
      const end = document.querySelector("[name='end_time']");
      const hours = document.getElementById("hours");

      function calculateHours() {

      if (!start.value || !end.value) return;

      const startDate = new Date("2000-01-01T" + start.value);
      const endDate = new Date("2000-01-01T" + end.value);

      let diff = (endDate - startDate) / (1000 * 60 * 60);

      if (diff < 0) diff += 24; // Handles overnight shifts

      hours.value = diff.toFixed(2);
      }

      start.addEventListener("change", calculateHours);
      end.addEventListener("change", calculateHours);
    </script>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
          document.getElementById("work_date").valueAsDate = new Date();
      });
    </script>
    <script>
      function openModal() {
        document.getElementById("logModal").style.display = "flex";

        // Today's date
        document.getElementById("work_date").valueAsDate = new Date();

        // Default work schedule from profile
        document.getElementById("start_time").value = "<?= $user['start_time']; ?>";
        document.getElementById("end_time").value = "<?= $user['end_time']; ?>";

        calculateHours();
      }

      function closeModal() {
          document.getElementById("logModal").style.display = "none";
      }
      </script>
      <script>
        document.addEventListener("DOMContentLoaded", function () {

            const successMessage = document.querySelector(".success-message");

            if (successMessage) {

                setTimeout(() => {
                    successMessage.style.transition = "opacity 0.5s ease";
                    successMessage.style.opacity = "0";

                    setTimeout(() => {
                        successMessage.remove();
                    }, 500);

                }, 5000); // 5 seconds
            }

        });
      </script>
  </body>
</html>
