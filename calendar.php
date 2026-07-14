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
// REMAINING WORK DAYS
// ===============================

$remaining_hours = 
$user['required_hours'] - $total_hours;
$remaining_days = 0;
if ($user['hours_per_day'] > 0) {
    $remaining_days =
    $remaining_hours / $user['hours_per_day'];
}

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

$remaining_days_display =
rtrim(rtrim(number_format($remaining_days,1), '0'), '.');

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

// Temporary until you create a deadlines table

$deadlines = [];
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
        .calendar-layout {
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
        <div class="card daily-card">
          <h3>Daily Average Hours</h3>
          <div class="number">
            <?= $daily_average_display; ?>
          </div>
        </div>

        <div class="card active-card">
          <h3>Total Days Active</h3>
          <div class="number">
            <?= $total_days; ?>
        </div>
        </div>

        <div class="card month-card">
          <h3>Total Hours This Month</h3>
          <div class="number">
            <?= rtrim(rtrim(number_format($month_hours, 2), '0'), '.'); ?>
          </div>
        </div>

        <div class="card remaining-card">
          <h3>Remaining Work Days</h3>
          <div class="number">
            <?= $remaining_days_display = rtrim(rtrim(number_format($remaining_days, 2), '0'), '.'); ?>
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
              <span>Expected Hours</span>
              <strong>
                <?= $user['hours_per_day']; ?> hrs
              </strong>
            </div>

            <div class="info-row">
              <span>Time Until Session</span>
              <strong>
                <?= $time_until; ?>
              </strong>
            </div>
          </div>

          <div class="event-section">
            <h2>Deadlines</h2>

            <div class="info-row empty">
              <span>No upcoming deadlines.</span>
            </div>
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
  </body>
</html>
