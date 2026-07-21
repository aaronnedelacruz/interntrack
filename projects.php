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
        TIMER PANEL
      ========================== */

      .timer-panel{
          display:none;

          margin-bottom:30px;
          padding:24px;

          background:#fff;
          border-radius:18px;

          box-shadow:0 10px 25px rgba(0,0,0,.08);

          align-items:center;
          gap:30px;
      }

      .timer-panel.active{
          display:flex;
      }

      .timer-left{
          display:flex;
          align-items:center;
          gap:18px;
          min-width:260px;
      }

      .timer-icon{
          width:58px;
          height:58px;

          border-radius:14px;

          background:#eef8f8;

          display:flex;
          justify-content:center;
          align-items:center;

          color:#0A9396;
          font-size:28px;
      }

      .timer-display{
          font-size:42px;
          font-weight:700;
          color:#005F73;

          font-variant-numeric:tabular-nums;
          letter-spacing:2px;
      }

      .timer-right{
          flex:1;

          display:flex;
          flex-direction:column;
          gap:14px;
      }

      .timer-right input{
          width:100%;

          padding:13px 16px;

          border:2px solid #ddd;
          border-radius:10px;

          font-size:15px;

          transition:.25s;
      }

      .timer-right input:focus{
          border-color:#0A9396;
          outline:none;
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
        CLEAR FILTER BUTTON
      ========================== */

      .clear-filter-btn {
        height: 44px;
        padding: 0 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
        border-radius: 8px;
        background: #005f73;
        color: #fff9f4;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
      }

      .clear-filter-btn i {
        font-size: 16px;
      }

      .clear-filter-btn:hover {
        background: #EE9B00;
        transform: translateY(-2px);
      }

      .clear-filter-btn:active {
        transform: scale(0.96);
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
        ACTION BUTTONS
      ========================== */

      .action-buttons{
          display:flex;
          justify-content:center;
          align-items:center;
          gap:10px;
      }

      .action-btn{
          width:36px;
          height:36px;

          display:flex;
          align-items:center;
          justify-content:center;

          border:none;
          outline:none;
          border-radius:10px;

          cursor:pointer;

          transition:0.25s ease;

          font-size:16px;
      }

      /* EDIT */

      .edit-btn{
          background:#0A9396;
          color:#fff;
      }

      .edit-btn i{
          color:#fff;
      }

      .edit-btn:hover{
          background:#087F81;
          transform:translateY(-2px);
      }

      /* DELETE */

      .delete-btn{
          background:#EE9B00;
          color:#fff;
      }

      .delete-btn i{
          color:#fff;
      }

      .delete-btn:hover{
          background:#D48806;
          transform:translateY(-2px);
      }

      .edit-btn:hover i,
      .delete-btn:hover i{
          color:#fff;
      }

      .action-btn:active{
          transform:scale(.95);
      }

      /* ==========================
        RESPONSIVE TABLE
      ========================== */

      @media (max-width:900px) {
          .table-card {
              overflow-x:auto;
          }

          .project-table {
              min-width:800px;
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

          .quick-actions {
              flex-wrap:wrap;
          }

          .quick-btn {
              min-width:220px;
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

          .quick-actions {
              flex-direction:column;
          }

          .quick-btn {
              width:100%;
          }

          .table-toolbar {
              flex-direction:column;
              align-items:stretch;
          }

          .search-box,
          .filter-date,
          .filter-view,
          .filter-sort {
              flex:1 1 100%;
              width:100%;
              min-width:0;
          }

          .time-row {
              flex-direction:column;
              gap:0;
          }

          .modal-content {
              width:95%;
              padding:20px;
          }

          .table-card {
              padding:20px;
          }

          .table-header {
              flex-direction:column;
              align-items:flex-start;
              gap:10px;
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

          .quick-btn {
              padding:14px;
              font-size:14px;
          }

          .table-card {
              padding:15px;
          }

          .table-header h2 {
              font-size:20px;
          }

          .table-year {
              font-size:24px;
          }

          .modal-content {
              padding:15px;
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
        <button id="newLogBtn" class="quick-btn project-btn" onclick="openModal()">
          <span>+</span>
          <span>New Log</span>
        </button>

        <button
            type="button"
            id="timerButton"
            class="quick-btn timer-btn"
            onclick="toggleTimer()">
            <span id="timerIcon">▶</span>
            <span id="timerText">Start Timer</span>
        </button>
      </div>

      <!-- TIMER PANEL -->

      <div class="timer-panel" id="timerPanel">
          <div class="timer-left">
              <div class="timer-icon">
                  <i class="bi bi-stopwatch-fill"></i>
              </div>
              <div class="timer-info">
                  <div class="timer-display" id="timerDisplay">
                      00:00:00
                  </div>
                  <div class="timer-started" id="timerStarted"></div>
              </div>
          </div>

          <div class="timer-right">
              <input
                  type="text"
                  id="timerProject"
                  placeholder="Project Name"
              >
              <input
                  type="text"
                  id="timerActivity"
                  placeholder="Activity"
              >
          </div>

      </div>

      <div id="logModal" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>New Work Log</h2>
            <span class="close" onclick="closeModal()">&times;</span>
          </div>
          <form action="projects_process.php" method="POST">
            <input type="hidden" name="action" value="add" id="formAction">
            <input type="hidden" name="id" id="project_id">
            <div class="input-group">
              <label>Project Name</label>
              <input
                type="text"
                name="project_name"
                id="project_name"
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

            <button
                type="submit"
                class="save-log-btn">
                Save Log
            </button>
          </form>
        </div>
      </div>

      <!-- SUCCESS MESSAGE -->
      <?php if (isset($_GET['success'])): ?>

          <div class="success-message">
              <i class="bi bi-check-circle-fill"></i>

              <?php
              if ($_GET['success'] == "added") {
                  echo "Work log saved successfully.";
              } 
              elseif ($_GET['success'] == "edited") {
                  echo "Work log updated successfully.";
              } 
              elseif ($_GET['success'] == "deleted") {
                  echo "Work log deleted successfully.";
              }
              ?>

          </div>

          <script>
              setTimeout(() => {
                  window.history.replaceState(null, null, window.location.pathname);
              }, 100);
          </script>

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
            <input type="text" id="searchLogs" placeholder="Search project or activity" />
          </div>

          <div class="input-box filter-date">
            <input id="filter-date" type="text" placeholder="Select Date" />
            <i class="bi bi-calendar"></i>
          </div>

          <div class="select-box filter-view">
            <select id="filter-view">
                <option value="all">All Logs</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="7days">Last 7 Days</option>
                <option value="30days">Last 30 Days</option>
            </select>
            <i class="bi bi-chevron-down"></i>
          </div>

          <div class="select-box filter-sort">
            <select id="filter-sort">
              <option value="latest">Sort by Latest</option>
              <option value="oldest">Sort by Oldest</option>
              <option value="highest">Highest Hours</option>
              <option value="lowest">Lowest Hours</option>
            </select>
            <i class="bi bi-chevron-down"></i>
          </div>

          <button id="clearFilters" class="clear-filter-btn">
              <i class="bi bi-x-circle"></i>
              Clear Fliters
          </button>
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

          <tbody id="projectsTable">
            <?php while($row = $projects->fetch_assoc()): ?>
            <tr data-date="<?= $row['work_date']; ?>" data-time="<?= $row['start_time']; ?>" data-hours="<?= $row['hours']; ?>">
                <td><?= htmlspecialchars($row['project_name']); ?></td>
                <td><?= htmlspecialchars($row['activity']); ?></td>
                <td><?= rtrim(rtrim(number_format($row['hours'], 2), '0'), '.'); ?></td>
                <td>
                    <?= date("F j, Y", strtotime($row['work_date'])); ?>
                    •
                    <?= date("g:i A", strtotime($row['start_time'])); ?>
                    –
                    <?= date("g:i A", strtotime($row['end_time'])); ?>
                </td>

                <td class="action-buttons">
                  <button
                      type="button"
                      class="action-btn edit-btn"

                      data-id="<?= $row['id']; ?>"
                      data-project="<?= htmlspecialchars($row['project_name']); ?>"
                      data-activity="<?= htmlspecialchars($row['activity']); ?>"
                      data-date="<?= $row['work_date']; ?>"
                      data-start="<?= $row['start_time']; ?>"
                      data-end="<?= $row['end_time']; ?>"
                      data-hours="<?= $row['hours']; ?>">

                      <i class="bi bi-pencil-square"></i>

                  </button>

                  <form
                      action="projects_process.php"
                      method="POST"
                      style="display:inline;">

                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $row['id']; ?>">

                      <button
                          type="submit"
                          class="action-btn delete-btn"
                          onclick="return confirm('Delete this log?')">

                          <i class="bi bi-trash"></i>

                      </button>

                  </form>

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
          dateFormat: "Y-m-d",
          altInput: true,
          altFormat: "F j, Y",

          onChange: function() {
              filterTable();
          }
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
      let timerRunning=false;
      let timerInterval;
      let elapsedSeconds=0;
      let sessionStart;

      document.addEventListener("DOMContentLoaded", restoreTimer);

      function restoreTimer(){

          const formData = new FormData();
          formData.append("action","get_timer");

          fetch("projects_process.php",{
              method:"POST",
              body:formData
          })
          .then(response=>response.json())
          .then(data=>{

              if(!data.success) return;

              timerRunning = true;

              const parts = data.timer.started_at.split(/[- :]/);

              sessionStart = new Date(
                  parts[0],
                  parts[1]-1,
                  parts[2],
                  parts[3],
                  parts[4],
                  parts[5]
              );

              elapsedSeconds = Math.floor(
                  (new Date() - sessionStart) / 1000
              );

              document.getElementById("timerProject").value =
                  data.timer.project_name;

              document.getElementById("timerActivity").value =
                  data.timer.activity;

              document.getElementById("timerPanel").classList.add("active");

              document.getElementById("timerStarted").textContent =
                  formatTime(sessionStart) + " - In Progress";

              updateTimer();

              timerInterval = setInterval(updateTimer,1000);

              document.getElementById("timerText").textContent =
                  "Finish Session";

              document.getElementById("timerIcon").textContent =
                  "■";

              const newLogBtn = document.getElementById("newLogBtn");

              newLogBtn.disabled = true;
              newLogBtn.style.opacity = ".6";
              newLogBtn.style.cursor = "not-allowed";
          });

      }

      function toggleTimer(){
          if(!timerRunning){
              startTimer();
          }else{
              finishTimer();
          }
      }

      function startTimer(){

          const project = document.getElementById("timerProject").value.trim();
          const activity = document.getElementById("timerActivity").value.trim();


          sessionStart = new Date();
          elapsedSeconds = 0;

          const formData = new FormData();

          formData.append("action", "start_timer");
          formData.append("project_name", project);
          formData.append("activity", activity);
          formData.append(
              "started_at",
              sessionStart.getFullYear() + "-" +
              String(sessionStart.getMonth()+1).padStart(2,"0") + "-" +
              String(sessionStart.getDate()).padStart(2,"0") + " " +
              String(sessionStart.getHours()).padStart(2,"0") + ":" +
              String(sessionStart.getMinutes()).padStart(2,"0") + ":" +
              String(sessionStart.getSeconds()).padStart(2,"0")
          );

          fetch("projects_process.php",{
              method:"POST",
              body:formData
          })
          .then(response=>response.json())
          .then(data=>{

              if(!data.success){
                  alert(data.message || "Failed to start timer.");
                  return;
              }

              timerRunning = true;

              document.getElementById("timerPanel").classList.add("active");

              const newLogBtn = document.getElementById("newLogBtn");
              newLogBtn.disabled = true;
              newLogBtn.style.opacity = ".6";
              newLogBtn.style.cursor = "not-allowed";
              newLogBtn.title = "Finish the active timer first.";

              document.getElementById("timerStarted").textContent =
                  formatTime(sessionStart) + " - In Progress";

              document.getElementById("timerText").textContent = "Finish Session";
              document.getElementById("timerIcon").textContent = "■";

              updateTimer();
              timerInterval = setInterval(updateTimer,1000);

          })
          .catch(() => {
              alert("Unable to start timer.");
          });

      }

      function updateTimer(){
          elapsedSeconds++;

          const hrs=Math.floor(elapsedSeconds/3600);
          const mins=Math.floor((elapsedSeconds%3600)/60);
          const secs=elapsedSeconds%60;

          document.getElementById("timerDisplay").textContent=
              String(hrs).padStart(2,"0")+":"+
              String(mins).padStart(2,"0")+":"+
              String(secs).padStart(2,"0");
      }

      function finishTimer(){
          const project = document.getElementById("timerProject").value;
          const activity = document.getElementById("timerActivity").value;

          if(project.trim()==="" || activity.trim()===""){
              alert("Please enter project name and activity before finishing.");
              return;
          }

          clearInterval(timerInterval);

          timerRunning=false;

          const sessionEnd=new Date();
          const totalHours=((sessionEnd-sessionStart)/1000/60/60).toFixed(2);

          document.getElementById("project_name").value=
              document.getElementById("timerProject").value;

          document.querySelector("textarea[name='activity']").value=
              document.getElementById("timerActivity").value;

          document.getElementById("start_time").value=
              sessionStart.toTimeString().slice(0,5);

          document.getElementById("end_time").value=
              sessionEnd.toTimeString().slice(0,5);

          document.getElementById("hours").value=
              totalHours;

          document.getElementById("timerDisplay").textContent="00:00:00";
          document.getElementById("timerStarted").textContent="";

          document.getElementById("timerPanel").classList.remove("active");

          document.getElementById("timerText").textContent="Start Timer";
          document.getElementById("timerIcon").textContent="▶";

          const newLogBtn = document.getElementById("newLogBtn");

          newLogBtn.disabled = false;
          newLogBtn.style.opacity = "1";
          newLogBtn.style.cursor = "pointer";
          newLogBtn.title = "";

          const finishData = new FormData();

          finishData.append("action", "finish_timer");
          finishData.append("project_name", project);
          finishData.append("activity", activity);

          finishData.append(
              "work_date",
              sessionStart.getFullYear() + "-" +
              String(sessionStart.getMonth()+1).padStart(2,"0") + "-" +
              String(sessionStart.getDate()).padStart(2,"0")
          );

          finishData.append(
              "start_time",
              sessionStart.toTimeString().slice(0,5)
          );

          finishData.append(
              "end_time",
              sessionEnd.toTimeString().slice(0,5)
          );

          finishData.append(
              "hours",
              totalHours
          );

          fetch("projects_process.php", {
              method: "POST",
              body: finishData
          })
          .then(response => response.json())
          .then(data => {
            if(data.success){

                document.getElementById("timerProject").value = "";
                document.getElementById("timerActivity").value = "";

                location.reload();

            }else{
                alert(data.message || "Failed to save session.");
            }
        });
      }

      function formatTime(date){
          let hrs=date.getHours();
          const mins=String(date.getMinutes()).padStart(2,"0");
          const ampm=hrs>=12?"PM":"AM";

          hrs=hrs%12;
          hrs=hrs?hrs:12;

          return hrs+":"+mins+" "+ampm;
      }
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

      document.querySelectorAll(".edit-btn").forEach(button => {
            button.addEventListener("click", function(){
                document.getElementById("logModal").style.display = "flex";
                document.querySelector(".modal-header h2").textContent = "Edit Work Log";
                document.getElementById("formAction").value = "edit";
                document.getElementById("project_id").value =
                    this.dataset.id;
                document.getElementById("project_name").value =
                    this.dataset.project;
                document.querySelector("textarea[name='activity']").value =
                    this.dataset.activity;
                document.getElementById("work_date").value =
                    this.dataset.date;
                document.getElementById("start_time").value =
                    this.dataset.start;
                document.getElementById("end_time").value =
                    this.dataset.end;
                document.getElementById("hours").value =
                    this.dataset.hours;
            });
        });
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
      <script>
        // SEARCH LOGS FILTER
        const searchInput = document.getElementById("searchLogs");
        const dateInput = document.getElementById("filter-date");
        const viewFilter = document.getElementById("filter-view");
        const sortFilter = document.getElementById("filter-sort");
        const tableBody = document.getElementById("projectsTable");

        // Gets the Clear Filters button
        const clearButton = document.getElementById("clearFilters");

        searchInput.addEventListener("input", filterTable);
        dateInput.addEventListener("change", filterTable);
        viewFilter.addEventListener("change", filterTable);
        sortFilter.addEventListener("change", filterTable);

        // Clear button event
        clearButton.addEventListener("click", clearFilters);

        function filterTable() {
            const keyword = searchInput.value.toLowerCase().trim();
            const selectedDate = dateInput.value;
            const selectedView = viewFilter.value;
            const selectedSort = sortFilter.value;

            let rows = Array.from(
                document.querySelectorAll("#projectsTable tr")
            );

            // FILTERING
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const rowDate = new Date(row.dataset.date);
                const today = new Date();

                let matchesSearch =
                    keyword === "" ||
                    text.includes(keyword);

                let matchesDate =
                    selectedDate === "" ||
                    row.dataset.date === selectedDate;

                // PERIOD FILTER
                let matchesView = true;

                if(selectedView === "week") {
                    const firstDay = new Date(today);
                    firstDay.setDate(today.getDate() - today.getDay());
                    matchesView =
                        rowDate >= firstDay &&
                        rowDate <= today;
                }

                if(selectedView === "month") {
                    matchesView =
                        rowDate.getMonth() === today.getMonth()
                        &&
                        rowDate.getFullYear() === today.getFullYear();
                }

                if(selectedView === "7days") {
                    const last7 = new Date(today);
                    last7.setDate(today.getDate() - 7);
                    matchesView =
                        rowDate >= last7 &&
                        rowDate <= today;
                }

                if(selectedView === "30days") {
                    const last30 = new Date(today);
                    last30.setDate(today.getDate() - 30);
                    matchesView =
                        rowDate >= last30 &&
                        rowDate <= today;
                }

                row.style.display =
                    matchesSearch &&
                    matchesDate &&
                    matchesView
                    ? ""
                    : "none";

            });

            // SORTING
            rows.sort((a,b)=>{
                const dateA = new Date(
                    a.dataset.date + " " + a.dataset.time
                );

                const dateB = new Date(
                    b.dataset.date + " " + b.dataset.time
                );

                const hoursA = Number(a.dataset.hours);
                const hoursB = Number(b.dataset.hours);

                switch(selectedSort){

                    case "latest":
                        return dateB - dateA;

                    case "oldest":
                        return dateA - dateB;

                    case "highest":
                        return hoursB - hoursA;

                    case "lowest":
                        return hoursA - hoursB;

                    default:
                        return 0;

                }

            });

            rows.forEach(row => tableBody.appendChild(row));

        }

        // CLEAR ALL FILTERS FUNCTION
        function clearFilters(){

            // Clear search box
            searchInput.value = "";

            // Clear date picker
            // Works with Flatpickr if installed
            if(dateInput._flatpickr){
                dateInput._flatpickr.clear();
            }

            else{
                dateInput.value = "";
            }

            // Reset period filter
            viewFilter.value = "all";

            // Reset sorting
            sortFilter.value = "latest";

            // Refresh table
            filterTable();

        }
    </script>
  </body>
</html>
