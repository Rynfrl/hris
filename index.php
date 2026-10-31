<?php
  include "session.php";

  if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
  }

  if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    if (hr_is_sales_role($_SESSION['role'] ?? '')) {
      $dashboard = "sales/index.php";
    } elseif (hr_is_inventory_role($_SESSION['role'] ?? '')) {
      $dashboard = "Inventory/index.php";
    } elseif (hr_is_purchasing_role($_SESSION['role'] ?? '')) {
      $dashboard = "purchasing/dashboard.php";
    } elseif (hr_is_expenses_user($_SESSION['role'] ?? '', $_SESSION['username'] ?? '')) {
      $dashboard = (!empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'expenses.test')
          ? "http://expenses.test/"
          : "expenses/";
    } else {
      $dashboard = "dashboard.php";
    }
    header("Location: " . $dashboard);
    exit();
  }
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HRIS - Login</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    body {
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: #f4f6f8;
    }

    .login-container {
      width: 380px;
      background: white;
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }

    .logo {
      text-align: center;
      margin-bottom: 25px;
    }

    .logo h1 {
      font-size: 28px;
      color: #2563eb;
    }

    .logo p {
      margin-top: 6px;
      color: #777;
      font-size: 14px;
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      margin-bottom: 7px;
      font-size: 14px;
      color: #333;
    }

    .form-group input {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #ddd;
      border-radius: 7px;
      font-size: 14px;
      outline: none;
    }

    .form-group input:focus {
      border-color: #2563eb;
    }

    .btn-login {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 7px;
      background: #2563eb;
      color: white;
      font-size: 15px;
      cursor: pointer;
    }

    .btn-login:hover {
      background: #1d4ed8;
    }

    .error {
      background: #fee2e2;
      color: #b91c1c;
      padding: 10px;
      border-radius: 6px;
      font-size: 13px;
      margin-bottom: 18px;
      text-align: center;
    }

    .footer {
      text-align: center;
      margin-top: 25px;
      font-size: 12px;
      color: #999;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="logo">
      <h1>Danny's ERP Systems</h1>
      <p>Human Resource Information System</p>
    </div>
    <?php if (isset($_GET['error'])): ?>
      <div class="error">
        Username/email atau password salah.
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['inactive'])): ?>
      <div class="error">
        Akun kamu sedang tidak aktif.
      </div>
    <?php endif; ?>
    <form action="verif_login.php" method="POST">
      <div class="form-group">
        <label for="identifier">Username atau Email</label>
        <input
          type="text"
          id="identifier"
          name="identifier"
          placeholder="Masukkan username atau email"
          required
          autocomplete="username">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          placeholder="Masukkan password"
          required
          autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">
        Login
      </button>
    </form>
    <div class="footer">
      HRIS &copy; 2026
    </div>
  </div>
</body>
</html>