<?php

session_start();

$conn = new mysqli("localhost", "root", "", "interntrack");

if ($conn->connect_error) {
    die("Connection failed");
}


$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE id='$user_id'";

$result = $conn->query($sql);

$user = $result->fetch_assoc();

// ===============================
// HOURS SUMMARY
// ===============================

$hours_sql = "
SELECT
    COALESCE(SUM(hours),0) AS completed_hours,
    COUNT(*) AS total_sessions,
    COUNT(DISTINCT work_date) AS total_days
FROM projects
WHERE user_id = '$user_id'
";

$hours_result = $conn->query($hours_sql);
$hours = $hours_result->fetch_assoc();

$completed_hours = (float)$hours['completed_hours'];
$total_sessions = (int)$hours['total_sessions'];
$total_days = (int)$hours['total_days'];

$required_hours = (float)$user['required_hours'];

$remaining_hours = max(
    $required_hours - $completed_hours,
    0
);


// ===============================
// PROGRESS
// ===============================

$progress = 0;

if ($required_hours > 0) {
    $progress = ($completed_hours / $required_hours) * 100;
}

$progress_display = round($progress) . "%";


// ===============================
// FORMAT HOURS
// ===============================

$completed_hours_display = rtrim(
    rtrim(number_format($completed_hours,2),'0'),
    '.'
);

$remaining_hours_display = rtrim(
    rtrim(number_format($remaining_hours,2),'0'),
    '.'
);


// ===============================
// EXPECTED COMPLETION DATE
// ===============================

date_default_timezone_set("Asia/Manila");

$working_days = explode(",", $user['working_days']);

$completion_date = new DateTime();

$hours_left = $remaining_hours;

while ($hours_left > 0) {

    $completion_date->modify("+1 day");

    if (in_array($completion_date->format("l"), $working_days)) {

        $hours_left -= $user['hours_per_day'];

    }

}

$completion_date_display = $completion_date->format("F j, Y");

// ===============================
// INTERNSHIP STATUS
// ===============================

if ($progress >= 100) {
    $internship_status = "Completed";
} else {
    $internship_status = "Active Internship";
}


?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>InternTrack | Profile</title>
    <link rel="icon" href="images/InternTrack.png" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    />

    <style>
      /* RESET */
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
      /* HEADER */
      .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
      }

      .date {
        background: white;
        padding: 12px 18px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        font-weight: 600;
      }

      /* ==========================
PROFILE CARDS
========================== */

      .card {
        background: #ffffff;
        padding: 25px;
        border-radius: 20px;

        box-shadow: 0 10px 25px rgba(0, 95, 115, 0.08);

        margin-bottom: 25px;

        border: 1px solid #d8e1e9;
      }

      /* ==========================
PROFILE HERO
========================== */

      .profile-banner {
        display: flex;
        justify-content: space-between;
        align-items: center;

        gap: 25px;

        padding: 30px;

        background: linear-gradient(135deg, #005669, #0a9396);

        border-radius: 24px;

        color: white;

        margin-bottom: 25px;

        box-shadow: 0 12px 30px rgba(0, 95, 115, 0.18);
      }

      .profile-left {
        display: flex;
        align-items: center;
        gap: 25px;
      }

      .profile-avatar {
        width: 100px;
        height: 100px;

        border-radius: 50%;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 45px;

        background: rgba(255, 255, 255, 0.15);

        border: 4px solid rgba(255, 255, 255, 0.35);
      }

      .profile-details h2 {
        font-size: 30px;
        margin-bottom: 8px;
      }

      .profile-details p {
        font-size: 16px;
        opacity: 0.9;
      }

      .profile-badges {
        display: flex;
        gap: 12px;

        margin-top: 12px;

        flex-wrap: wrap;
      }

      .badge {
        display: flex;

        align-items: center;

        gap: 8px;

        padding: 8px 15px;

        border-radius: 50px;

        font-size: 13px;

        font-weight: 600;
      }

      .badge.school {
        background: #d8eaf0;

        color: #005669;
      }

      .badge.active {
        background: #7c974b;
        color: white;
      }

      .badge.completed {
          background: #2e7d32;
          color: white;
      }

      /* ==========================
PROFILE ACTION ICONS
========================== */

      .profile-actions {
        display: flex;

        gap: 12px;

        margin-left: auto;
      }

      .icon-btn {
        width: 45px;

        height: 45px;

        border: none;

        border-radius: 12px;

        display: flex;

        justify-content: center;

        align-items: center;

        cursor: pointer;

        color: white;

        font-size: 18px;

        transition: 0.2s;
      }

      .edit-btn {
        background: #ee9b00;
      }

      .edit-btn:hover {
        background: #d98f00;
      }

      .save-btn {
        background: #005f73;
      }

      .save-btn:hover {
        background: #004858;
      }

      /* ==========================
PROFILE GRID
========================== */

      .profile-grid {
        display: grid;

        grid-template-columns: repeat(2, minmax(300px, 1fr));

        gap: 25px;
      }

      /* ==========================
SECTION HEADER
========================== */

      .section-title {
        display: flex;

        align-items: center;

        gap: 12px;

        color: white;

        padding: 16px 20px;

        margin: -25px -25px 25px;

        border-radius: 20px 20px 0 0;
      }

      .section-title i {
        font-size: 22px;
      }

      .section-title h2 {
        font-size: 20px;
      }

      /* Teal */

      .section-title.personal {
        background: #005f73;
      }

      /*Cyan*/

      .section-title.internship {
        background: #0a9396;
      }

      /* Blue Green */

      .section-title.settings {
        background: #0da472
      }

      /* Orange */
      .section-title.progress {
        background: #ee9b00;
      }

      /* ==========================
      PROGRESS OVERVIEW
      ========================== */

      

      .progress-card .info-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
          gap: 18px;
      }

      .progress-card .field {
          display: flex;
          flex-direction: column;
          justify-content: center;

          min-height: 100px;

          background: #f8fcfd;
          border: 1px solid #d8e1e9;
          border-radius: 12px;

          padding: 18px;
      }

      .progress-card .field span {
          display: block;

          font-size: 11px;
          text-transform: uppercase;
          letter-spacing: 1px;

          color: #60757f;
          font-weight: 700;

          margin-bottom: 10px;
      }

      .progress-card .field input {
          width: 100%;

          border: none;
          outline: none;
          background: transparent;

          font-size: 22px;
          font-weight: 700;

          color: #005669;
      }

      /* ==========================
      FIELDS
      ========================== */

      .info-grid {
        display: grid;

        grid-template-columns: repeat(2, 1fr);

        gap: 18px;
      }

      .field {
        background: #f8fcfd;

        border: 1px solid #d8e1e9;

        padding: 16px;

        border-radius: 10px;
      }

      .field span {
        display: block;

        font-size: 11px;

        text-transform: uppercase;

        letter-spacing: 1px;

        color: #60757f;

        font-weight: 700;

        margin-bottom: 8px;
      }

      .field input {
        width: 100%;

        border: none;

        outline: none;

        background: transparent;

        font-size: 16px;

        font-weight: 600;

        color: #005669;
      }
      /* ==========================
INTERNSHIP SETTINGS GROUP
========================== */


      .settings-card .field {
        display: flex;

        justify-content: space-between;

        align-items: center;

        background: #f8fcfd;

        border: 1px solid #d8e1e9;

        padding: 16px 18px;

        border-radius: 0;
      }

      /* Top rounded corner */

      .settings-card .field:first-child {
        border-radius: 12px 12px 0 0;
      }

      /* Remove double borders */

      .settings-card .field + .field {
        border-top: none;
      }

      /* Bottom rounded corner */

      .settings-card .field:last-child {
        border-radius: 0 0 12px 12px;
      }

      /* Settings labels */

      .settings-card .field span {
        margin-bottom: 0;
      }

      /* Settings values */

      .settings-card .field input {
        width: auto;

        text-align: right;
      }

      /* ==========================
WORKING DAYS
========================== */

      .working-days {
        grid-column: span 2;
      }

      .days {
        display: flex;

        flex-wrap: wrap;

        gap: 10px;

        margin-top: 12px;
      }

      .days label {
        background: #0a9396;

        color: white;

        padding: 8px 16px;

        border-radius: 50px;

        font-size: 14px;

        font-weight: 600;
      }

      .days input {
        display: inline-block;
        margin-right: 6px;
      }

      /* ==========================
BUTTONS
========================== */

      .profile-actions {
        display: flex;

        gap: 12px;
      }

      .btn {
        padding: 12px 20px;

        border: none;

        border-radius: 12px;

        cursor: pointer;

        font-weight: 700;

        display: flex;

        align-items: center;

        gap: 8px;
      }

      .btn-primary {
        background: #ee9b00;

        color: white;
      }

      .btn-primary:hover {
        background: #d98f00;
      }

      .btn-secondary {
        background: #0a9396;

        color: white;
      }

      .btn-secondary:hover {
        background: #087b80;
      }

      /* Disabled Inputs */

      input[disabled] {
        opacity: 0.85;
      }

      /* ==========================
    RESPONSIVE
    ========================== */

          @media (max-width: 1000px) {
            .profile-grid {
              grid-template-columns: 1fr;
            }

            .profile-banner {
              flex-direction: column;

              align-items: flex-start;
            }

            .info-grid {
              grid-template-columns: 1fr;
            }

            .working-days {
              grid-column: auto;
            }
          }

          /* =========================
      RESPONSIVE
    ========================= */

    @media (max-width:1100px){

        .sidebar{
            width:92px;
            padding:30px 20px;
        }

        .sidebar .logo{
            width:0;
            opacity:0;
        }

        .sidebar a{
            justify-content:center;
            width:52px;
            padding:0;
            margin:0 auto;
            gap:0;
        }

        .sidebar a span,
        .logout span{
            max-width:0;
            opacity:0;
        }

        .logout a{
            justify-content:center;
        }

        .main{
            margin-left:92px;
            width:calc(100% - 92px);
        }

    }

    @media (max-width:768px){

        .main{
            padding:20px;
        }

        .header{
            flex-direction:column;
            align-items:flex-start;
            gap:15px;
        }

    }

    @media (max-width:560px){

        .main{
            padding:15px;
        }

        .header h1{
            font-size:28px;
        }

        .date{
            width:100%;
        }
    }

    
    </style>
  </head>

  <body>
    <div class="main" id="main">
      <div class="header">
        <div>
          <h1>My Profile</h1>

          <p>Manage internship projects and work sessions.</p>
        </div>

        <div class="date">
          📅 <?= date("F j, Y"); ?>
        </div>
      </div>

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
              <a href="reports.php">
                <i class="bi bi-bar-chart-line"></i>
                <span>Reports</span>
              </a>
            </li>

            <li>
              <a href="profile.php" class="active">
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

      <!-- ==========================
          PROFILE HERO
      ========================== -->
      <form method="POST" action="profile_update.php" id="profileForm">
        <div class="profile-banner">
          <div class="profile-left">
            <div class="profile-avatar">
              <i class="bi bi-person-fill"></i>
            </div>

            <div class="profile-details">
              <h2>
                <?= $user['first_name'] . " " . $user['last_name']; ?><!-- Display the user's full name -->
              </h2>
              <p><?= $user['degree_program']; ?></p><!-- Display the user's course -->

              <div class="profile-badges">
                <span class="badge school">
                  <i class="bi bi-mortarboard-fill"></i>
                  <?= $user['school']; ?>
                </span>

                <span class="badge <?= $progress >= 100 ? 'completed' : 'active'; ?>">
                    <i class="bi <?= $progress >= 100 ? 'bi-check-circle-fill' : 'bi-hourglass-split'; ?>"></i>
                    <?= $internship_status; ?>
                </span>
              </div>
            </div>
          </div>
          <div class="profile-actions">
            <button
              type="button"
              class="icon-btn edit-btn"
              onclick="enableEdit()"
              title="Edit Profile"
              >
              <i class="bi bi-pencil-fill"></i>
            </button>
              

            <button
              type="submit"
              class="icon-btn save-btn"
              title="Save Changes"
            >
            <i class="bi bi-check-lg"></i>
            </button>

          </div>
        </div>

        <!-- ==========================
          TOP GRID
        ========================== -->
        <!-- ==========================
      PROFILE GRID
      ========================== -->

      <div class="profile-grid">

        <!-- STUDENT INFORMATION -->

        <div class="card">
          <div class="section-title personal">
            <i class="bi bi-person-vcard-fill"></i>
            <h2>Student Information</h2>
          </div>

          <div class="info-grid">

            <div class="field">
              <span>First Name</span>
              <input
                name="first_name"
                value="<?= $user['first_name']; ?>"
                disabled
              />
            </div>

            <div class="field">
              <span>Last Name</span>
              <input
                name="last_name"
                value="<?= $user['last_name']; ?>"
                disabled
              />
            </div>

            <div class="field">
              <span>Email Address</span>
              <input
                name="email"
                value="<?= $user['email']; ?>"
                disabled
              />
            </div>

            <div class="field">
              <span>Student ID</span>
              <input
                name="student_id"
                value="<?= $user['student_id']; ?>"
                disabled
              />
            </div>

            <div class="field">
              <span>School</span>
              <input
                name="school"
                value="<?= $user['school']; ?>"
                disabled
              />
            </div>

            <div class="field">
              <span>Degree Program</span>
              <input
                name="course"
                value="<?= $user['degree_program']; ?>"
                disabled
              />
            </div>

          </div>
        </div>

        <!-- INTERNSHIP INFORMATION -->

        <div class="card">
          <div class="section-title internship">
            <i class="bi bi-briefcase-fill"></i>
            <h2>Internship Information</h2>
          </div>

          <div class="info-grid">

            <div class="field">
              <span>Company</span>
              <input
                name="company"
                value="<?= $user['company']; ?>"
                disabled
              />
            </div>

            <div class="field">
              <span>Department</span>
              <input
                name="department"
                value="<?= $user['department']; ?>"
                disabled
              />
            </div>

            <div class="field">
              <span>Position / Role</span>
              <input
                name="position"
                value="<?= $user['position_role']; ?>"  
                disabled
              />
            </div>

            <div class="field">
              <span>OJT Supervisor</span>
              <input
                name="supervisor"
                value="<?= $user['supervisor']; ?>"
                disabled
              />
            </div>

            <div class="field">
              <span>OJT Professor</span>
              <input
                name="professor"
                value="<?= $user['professor']; ?>"
                disabled
              />
            </div>

            <div class="field">
                <span>Internship Status</span>
                <input
                    value="<?= $internship_status; ?>"
                    disabled
                />
            </div>
          </div>
        </div>
      </div>

      <!-- ==========================
      INTERNSHIP SCHEDULE
      ========================== -->

      <div class="card settings-card">

        <div class="section-title settings">
          <i class="bi bi-calendar-week-fill"></i>
          <h2>Internship Schedule</h2>
        </div>

        <div class="settings-grid">

          <div class="field">
            <span>Total Required OJT Hours</span>
            <input
              type="number"
              name="required_hours"
              value="<?= $user['required_hours']; ?>"
              disabled
            />
          </div>

          <div class="field">
            <span>Expected Hours per Day</span>
            <input
              type="number"
              name="hours_per_day"
              value="<?= $user['hours_per_day']; ?>"
              disabled
            />
          </div>

          <?php $workingDays = explode(",", $user['working_days']); ?>

          <div class="field working-days">
            <span>Working Days</span>

            <div class="days">

              <label><input type="checkbox" name="working_days[]" value="Monday" <?= in_array("Monday",$workingDays)?"checked":""; ?> disabled /> Monday</label>

              <label><input type="checkbox" name="working_days[]" value="Tuesday" <?= in_array("Tuesday",$workingDays)?"checked":""; ?> disabled /> Tuesday</label>

              <label><input type="checkbox" name="working_days[]" value="Wednesday" <?= in_array("Wednesday",$workingDays)?"checked":""; ?> disabled /> Wednesday</label>

              <label><input type="checkbox" name="working_days[]" value="Thursday" <?= in_array("Thursday",$workingDays)?"checked":""; ?> disabled /> Thursday</label>

              <label><input type="checkbox" name="working_days[]" value="Friday" <?= in_array("Friday",$workingDays)?"checked":""; ?> disabled /> Friday</label>

              <label><input type="checkbox" name="working_days[]" value="Saturday" <?= in_array("Saturday",$workingDays)?"checked":""; ?> disabled /> Saturday</label>

              <label><input type="checkbox" name="working_days[]" value="Sunday" <?= in_array("Sunday",$workingDays)?"checked":""; ?> disabled /> Sunday</label>

            </div>

          </div>

          <div class="field">
            <span>Start Time</span>
            <input
              type="time"
              name="start_time"
              value="<?= $user['start_time']; ?>"
              disabled
            />
          </div>

          <div class="field">
            <span>End Time</span>
            <input
              type="time"
              name="end_time"
              value="<?= $user['end_time']; ?>"
              disabled
            />
          </div>

          <!-- ==========================

          <div class="field">
            <span>Internship Start Date</span>
            <input
              type="date"
              name="start_date"
              value="<?= $user['start_date']; ?>"
              disabled
            />
          </div>


          <div class="field">
            <span>Expected Completion Date</span>
            <input
              type="text"
              value="<?= $completion_date_display; ?>"
              disabled
            />
          </div>

          ========================== -->


        </div>

      </div>

      <!-- ==========================
      PROGRESS OVERVIEW
      ========================== -->

      <div class="card">

        <div class="section-title progress">
          <i class="bi bi-bar-chart-fill"></i>
          <h2>Progress Overview</h2>
        </div>

        <div class="info-grid">

          <div class="field">
            <span>Completed Hours</span>
            <input
              value="<?= $completed_hours_display; ?>"
              disabled
            />
          </div>

          <div class="field">
            <span>Remaining Hours</span>
            <input
              value="<?= $remaining_hours_display; ?>"
              disabled
            />
          </div>

          <div class="field">
            <span>Progress</span>
            <input
              value="<?= $progress_display; ?>"
              disabled
            />
          </div>

          <div class="field">
            <span>Total Days Completed</span>
            <input
              value="<?= $total_days; ?>"
              disabled
            />
          </div>

        </div>
      </form>
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

    function enableEdit() {

        const inputs = document.querySelectorAll("#profileForm input");

        inputs.forEach(input => {
            input.disabled = false;
        });

        document.querySelector(".edit-btn").style.display = "none";
        document.querySelector(".save-btn").style.display = "flex";
    }


    document.addEventListener("DOMContentLoaded", function(){

        document.querySelector(".edit-btn").style.display = "flex";
        document.querySelector(".save-btn").style.display = "none";

    });

    </script>
  </body>
</html>
