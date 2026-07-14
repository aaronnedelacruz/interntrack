<?php
include "database.php";
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="icon" href="images/InternTrack.png" />

    <title>InternTrack | Log In</title>

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
        justify-content: center;
        align-items: center;
        min-height: 100vh;
      }

      .login-container {
        width: 100%;
        max-width: 430px;
        padding: 20px;
      }

      .login-card {
        width: 100%;
        max-width: 380px;
        background: white;
        border-radius: 18px;
        padding: 32px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
      }

      .logo {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 35px;
      }

      .logo img {
        width: 70px;
        height: 70px;
        object-fit: contain;
        border-radius: 10px;
        margin-bottom: 15px;
      }

      .logo h1 {
        color: #005f73;
        font-size: 32px;
      }

      .logo p {
        color: #666;
        margin-top: 8px;
      }

      .input-group {
        margin-bottom: 20px;
      }

      .input-group label {
        display: block;
        margin-bottom: 8px;
        color: #005f73;
        font-weight: 600;
      }

      .input-group input {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #ddd;
        border-radius: 8px;
        outline: none;
        transition: 0.3s;
        font-size: 15px;
      }

      .input-group input:focus {
        border-color: #0a9396;
      }

      .options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        font-size: 14px;
      }

      .options a {
        color: #005f73;
        text-decoration: none;
      }

      .options a:hover {
        color: #0a9396;
      }

      .login-btn {
        width: 100%;
        background: #ee9b00;
        color: white;
        border: none;
        padding: 16px;
        border-radius: 10px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        transition: 0.3s;
      }

      .login-btn:hover {
        background: #d98f00;
      }

      .signup {
        text-align: center;
        margin-top: 25px;
        color: #555;
      }

      .signup a {
        color: #005f73;
        text-decoration: none;
        font-weight: bold;
      }

      .signup a:hover {
        color: #0a9396;
      }

      .back-home {
        text-align: center;
        margin-top: 20px;
      }

      .back-home a {
        color: #005f73;
        text-decoration: none;
      }

      .back-home a:hover {
        color: #0a9396;
      }
    </style>
  </head>

  <body>
    <div class="login-container">
      <div class="login-card">
        <div class="logo">
          <img src="images/InternTrack.png" alt="InternTrack" />

          <h1>InternTrack</h1>

          <p>Welcome back!</p>
        </div>

        <form method="POST" action="login_process.php">
          <div class="input-group">
            <label>Email Address</label>

            <input
              type="email"
              name="email"
              placeholder="Enter your email"
              required
            />
          </div>

          <div class="input-group">
            <label>Password</label>

            <input
              type="password"
              name="password"
              placeholder="Enter your password"
              required
            />
          </div>

          <div class="options">
            <label>
              <input type="checkbox" />
              Remember Me
            </label>

            <a href="#">Forgot Password?</a>
          </div>

          <button type="submit" class="login-btn">Log In</button>
        </form>

        <div class="signup">
          Don't have an account?

          <a href="signup.php"> Sign Up </a>
        </div>

        <div class="back-home">
          <a href="index.html"> Back to Home </a>
        </div>
      </div>
    </div>
  </body>
</html>
