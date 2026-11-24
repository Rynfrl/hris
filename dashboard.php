<?php
  include "session.php";

  if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: index.php");
    exit();
  }

  include_once "koneksi.php";

  $isAdmin = hr_is_admin($_SESSION['role'] ?? '');

  // Retrieve division for the logged-in user if available
  $userDivision = null;
  if (!empty($_SESSION['user_id']) && isset($conn) && $conn instanceof mysqli) {
    $divStmt = $conn->prepare("SELECT divisi FROM employees WHERE id_user = ? LIMIT 1");
    if ($divStmt) {
      $divStmt->bind_param("i", $_SESSION['user_id']);
      $divStmt->execute();
      $divRes = $divStmt->get_result();
      if ($divRow = $divRes->fetch_assoc()) {
        $userDivision = $divRow['divisi'] ?? null;
      }
      $divStmt->close();
    }
  }

  $divisions = [];
  $roles = [];
  $users = [];
  $availableEmployeeUsers = [];
  $employeeAccountUsers = [];
  $employees = [];
  $payrolls = [];
  if ($isAdmin) {
    $divisionResult = $conn->query("SELECT id_divisi, divisi FROM divisi ORDER BY divisi");
    $roleResult = $conn->query("SELECT id_role, role_name FROM roles ORDER BY role_name");
    $usersResult = $conn->query(
      "SELECT u.id, u.name, u.username, u.email, u.role, u.role_id, u.is_active,
              r.role_name
              FROM users u
              LEFT JOIN roles r ON r.id_role = u.role_id
              WHERE u.deleted_at IS NULL
              ORDER BY u.name"
    );
    $employeesResult = $conn->query(
      "SELECT e.id_employee, e.id_user, e.nama, e.divisi, e.jabatan, e.gaji,
              e.tanggal_masuk, u.name AS account_name, u.email
              FROM employees e
              LEFT JOIN users u ON u.id = e.id_user
              ORDER BY e.nama"
    );
    $availableEmployeeUsersResult = $conn->query(
      "SELECT u.id, u.name, u.username, u.email, e.id_employee AS linked_employee_id
       FROM users u
       LEFT JOIN employees e ON e.id_user = u.id
       WHERE u.deleted_at IS NULL AND u.is_active = 1 AND e.id_employee IS NULL
       ORDER BY u.name"
    );
    $employeeAccountUsersResult = $conn->query(
      "SELECT u.id, u.name, u.username, u.email, e.id_employee AS linked_employee_id
       FROM users u
       LEFT JOIN employees e ON e.id_user = u.id
       WHERE u.deleted_at IS NULL AND (u.is_active = 1 OR e.id_employee IS NOT NULL)
       ORDER BY u.name"
    );
    $payrollsResult = $conn->query(
      "SELECT p.id_payroll, p.id_employee, p.periode, p.total_gaji, p.tanggal_bayar,
              e.nama AS employee_name
              FROM payrolls p
              INNER JOIN employees e ON e.id_employee = p.id_employee
              ORDER BY p.periode DESC, e.nama"
    );
    if (!$divisionResult || !$roleResult || !$usersResult || !$employeesResult || !$availableEmployeeUsersResult || !$employeeAccountUsersResult || !$payrollsResult) {
      error_log("Gagal memuat data formulir HRIS: " . $conn->error);
      http_response_code(500);
      exit("Data formulir operasional gagal dimuat. Pastikan migrasi database sudah dijalankan.");
    }
    $divisions = $divisionResult->fetch_all(MYSQLI_ASSOC);
    $roles = $roleResult->fetch_all(MYSQLI_ASSOC);
    $users = $usersResult->fetch_all(MYSQLI_ASSOC);
    $availableEmployeeUsers = $availableEmployeeUsersResult->fetch_all(MYSQLI_ASSOC);
    $employeeAccountUsers = $employeeAccountUsersResult->fetch_all(MYSQLI_ASSOC);
    $employees = $employeesResult->fetch_all(MYSQLI_ASSOC);
    $payrolls = $payrollsResult->fetch_all(MYSQLI_ASSOC);
  }

  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $flash = $_SESSION['dashboard_flash'] ?? null;
  unset($_SESSION['dashboard_flash']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - HRIS</title>
  <style>
    html {
      scroll-behavior: smooth;
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: #f8fafc;
      margin: 0;
      color: #1e293b;
    }

    .hris-layout {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      width: 100%;
      margin: 0;
      padding: 0;
      background: #f8fafc;
    }

    .hris-header {
      position: sticky;
      top: 0;
      z-index: 100;
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .hris-topbar {
      max-width: 1400px;
      margin: 0 auto;
      padding: 12px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }

    .hris-brand-area {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: #0f172a;
    }

    .hris-brand-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      background: #2563eb;
      color: #ffffff;
      border-radius: 8px;
      flex-shrink: 0;
    }

    .hris-brand-title {
      font-size: 1.15rem;
      font-weight: 700;
      letter-spacing: -0.01em;
      color: #0f172a;
    }

    .hris-module-tag {
      font-size: 0.72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      padding: 2px 8px;
      background: #eff6ff;
      color: #2563eb;
      border: 1px solid #bfdbfe;
      border-radius: 9999px;
    }

    .hris-user-area {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    .hris-user-profile {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.88rem;
      color: #334155;
    }

    .hris-user-profile strong {
      color: #0f172a;
    }

    .badge-role {
      background: #e2e8f0;
      color: #334155;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.02em;
    }

    .badge-division {
      background: #dbeafe;
      color: #1e40af;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .logout-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #ffffff;
      text-decoration: none;
      background: #ef4444;
      padding: 7px 14px;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 500;
      transition: background 0.15s ease;
    }

    .logout-btn:hover {
      background: #dc2626;
    }

    .hris-nav-bar {
      border-top: 1px solid #f1f5f9;
      background: #f8fafc;
    }

    .hris-nav-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 8px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .hris-nav-group {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-shrink: 0;
    }

    .hris-nav-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 500;
      color: #475569;
      text-decoration: none;
      transition: all 0.15s ease;
      white-space: nowrap;
    }

    .hris-nav-pill:hover {
      background: #e2e8f0;
      color: #0f172a;
    }

    .hris-nav-pill.active {
      background: #2563eb;
      color: #ffffff;
    }

    .hris-nav-divider {
      width: 1px;
      height: 24px;
      background: #cbd5e1;
      flex-shrink: 0;
    }

    .hris-erp-group {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-shrink: 0;
    }

    .hris-erp-label {
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #64748b;
      margin-right: 4px;
      white-space: nowrap;
    }

    .hris-erp-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 10px;
      border-radius: 6px;
      font-size: 0.82rem;
      font-weight: 500;
      color: #0369a1;
      background: #f0f9ff;
      border: 1px solid #bae6fd;
      text-decoration: none;
      transition: all 0.15s ease;
      white-space: nowrap;
    }

    .hris-erp-pill:hover {
      background: #0284c7;
      color: #ffffff;
      border-color: #0284c7;
    }

    .hris-main {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      width: 100%;
    }

    .container {
      max-width: 1400px;
      margin: 24px auto 48px;
      padding: 0 24px;
      width: 100%;
      box-sizing: border-box;
    }

    .welcome-card,
    .operation-card {
      box-sizing: border-box;
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, .06);
    }

    .operations {
      display: flex;
      flex-direction: column;
      gap: 20px;
      margin-top: 24px;
    }

    .operation-card {
      width: 100%;
      overflow: hidden;
    }

    .operation-card .add-form {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      column-gap: 18px;
      row-gap: 22px;
      align-items: end;
      margin: 16px 0 22px;
      padding: 16px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      background: #f9fafb;
    }

    .management-card {
      margin-top: -8px;
    }

    .operation-card h3 {
      margin-bottom: 8px;
      color: #1f2937;
    }

    .operation-card p {
      margin-bottom: 18px;
      color: #6b7280;
      font-size: 14px;
      line-height: 1.5;
    }

    .info {
      margin-top: 15px;
      line-height: 1.8;
    }

    .form-group {
      min-width: 0;
      margin: 0;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #374151;
      font-size: 14px;
    }

    .table-wrap {
      width: 100%;
      overflow-x: auto;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    .data-table th,
    .data-table td {
      padding: 10px 12px;
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
      vertical-align: top;
      white-space: nowrap;
    }

    .data-table th {
      background: #f3f4f6;
      color: #374151;
    }

    .data-table tr:last-child td {
      border-bottom: 0;
    }

    .row-actions {
      display: flex;
      gap: 8px;
      align-items: flex-start;
    }

    .row-actions form {
      margin: 0;
    }

    .action-button,
    .edit-details summary {
      display: inline-block;
      padding: 6px 9px;
      border: 0;
      border-radius: 5px;
      background: #e0e7ff;
      color: #1d4ed8;
      font: inherit;
      cursor: pointer;
    }

    .action-button.delete {
      background: #fee2e2;
      color: #b91c1c;
    }

    .edit-details {
      position: relative;
    }

    .edit-details[open] .edit-form {
      position: static;
      display: grid;
      gap: 10px;
      width: min(320px, 75vw);
      margin-top: 8px;
      padding: 14px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      background: white;
      box-shadow: 0 8px 20px rgba(0, 0, 0, .15);
      white-space: normal;
    }

    .edit-form .form-group input,
    .edit-form .form-group select {
      min-height: 38px;
      padding: 8px 10px;
    }

    .empty-row {
      color: #6b7280;
      text-align: center !important;
    }

    .table-title {
      margin: 18px 0 10px;
      color: #374151;
      font-size: 15px;
    }

    .form-group input,
    .form-group select {
      box-sizing: border-box;
      width: 100%;
      min-height: 44px;
      padding: 11px 12px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 14px;
    }

    .btn-submit {
      width: 100%;
      padding: 11px;
      border: 0;
      border-radius: 6px;
      background: #2563eb;
      color: white;
      font-size: 14px;
      cursor: pointer;
    }

    .btn-submit:hover {
      background: #1d4ed8;
    }

    .notice {
      margin-top: 20px;
      padding: 12px 14px;
      border-radius: 6px;
      background: #dcfce7;
      color: #166534;
    }

    .notice.error {
      background: #fee2e2;
      color: #991b1b;
    }

    .admin-note {
      margin-top: 24px;
      color: #6b7280;
      font-size: 14px;
    }

    .form-hint {
      grid-column: 1 / -1;
      margin: 0;
      color: #92400e;
      font-size: 14px;
    }

    .btn-submit:disabled {
      background: #9ca3af;
      cursor: not-allowed;
    }

    @media (max-width: 900px) {
      .hris-topbar {
        padding: 10px 16px;
      }
      .hris-nav-container {
        padding: 8px 16px;
      }
      .container {
        padding: 0 16px;
        margin: 16px auto 32px;
      }
    }

    @media (max-width: 600px) {
      .hris-topbar {
        flex-direction: column;
        align-items: flex-start;
      }
      .hris-user-area {
        width: 100%;
        justify-content: space-between;
      }
      .operation-card,
      .welcome-card {
        padding: 18px;
      }
    }
  </style>
</head>

<body>
  <div class="hris-layout">
    <!-- Top Navigation Header (Replaces Sidebar) -->
    <header class="hris-header">
      <div class="hris-topbar">
        <div class="hris-brand-area">
          <div class="hris-brand-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="3" width="20" height="14" rx="2"></rect>
              <line x1="8" y1="21" x2="16" y2="21"></line>
              <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
          </div>
          <div>
            <span class="hris-brand-title">HRIS &amp; ERP System</span>
          </div>
          <span class="hris-module-tag">Modul HRIS</span>
        </div>

        <div class="hris-user-area">
          <div class="hris-user-profile">
            <span>Halo, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong></span>
            <span class="badge-role"><?= htmlspecialchars(trim((string)($_SESSION['role'] ?? '-'))) ?></span>
            <?php if (!empty($userDivision)): ?>
              <span class="badge-division" title="Divisi Karyawan"><?= htmlspecialchars($userDivision) ?></span>
            <?php endif; ?>
          </div>
          <a href="logout.php" class="logout-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Logout</span>
          </a>
        </div>
      </div>

      <nav class="hris-nav-bar">
        <div class="hris-nav-container">
          <div class="hris-nav-group">
            <a href="#dashboard" class="hris-nav-pill active">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
              <span>Dashboard HR</span>
            </a>
            <?php if ($isAdmin): ?>
              <a href="#employees" class="hris-nav-pill">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span>Data Karyawan</span>
              </a>
              <a href="#divisions" class="hris-nav-pill">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                <span>Data Divisi</span>
              </a>
              <a href="#payrolls" class="hris-nav-pill">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                <span>Payroll</span>
              </a>
              <a href="#users" class="hris-nav-pill">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <span>Manajemen Pengguna</span>
              </a>
            <?php endif; ?>
          </div>

          <div class="hris-nav-divider"></div>

          <div class="hris-erp-group">
            <span class="hris-erp-label">Modul ERP:</span>
            <a href="/purchasing/dashboard.php" class="hris-erp-pill">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
              <span>Purchasing</span>
            </a>
            <a href="/sales/index.php" class="hris-erp-pill">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
              <span>Sales</span>
            </a>
            <a href="/Inventory/pages/dashboard.html" class="hris-erp-pill">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
              <span>Inventory</span>
            </a>
            <a href="/expenses/dashboard" class="hris-erp-pill">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
              <span>GT Expense</span>
            </a>
          </div>
        </div>
      </nav>
    </header>

    <!-- Main Content Area -->
    <main class="hris-main">
      <div class="container" id="dashboard">
        <div class="welcome-card">
          <h2>
            Selamat datang, <?= htmlspecialchars($_SESSION['name']) ?>!
          </h2>
          <div class="info">
            <strong>Username:</strong>
            <?= htmlspecialchars($_SESSION['username'] ?? '-') ?>
            <br>
            <strong>Email:</strong>
            <?= htmlspecialchars($_SESSION['email'] ?? '-') ?>
            <br>
            <strong>Role:</strong>
            <?= htmlspecialchars(trim((string)($_SESSION['role'] ?? '-'))) ?>
            <?php if (!empty($userDivision)): ?>
              <br>
              <strong>Divisi:</strong>
              <?= htmlspecialchars($userDivision) ?>
            <?php endif; ?>
          </div>
        </div>

    <?php if ($flash): ?>
      <div class="notice <?= !empty($flash['error']) ? 'error' : '' ?>" role="status">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
      <div class="operations">
        <section class="operation-card" id="users">
          <h3>Tambah User</h3>
          <p>Buat akun yang dapat digunakan untuk masuk ke sistem.</p>
          <form class="add-form" action="proses_user.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="action" value="add_user">
              <div class="form-group">
                <label for="user-name">Nama</label>
                <input id="user-name" name="name" type="text" maxlength="150" required>
              </div>
              <div class="form-group">
                <label for="user-username">Username</label>
                <input id="user-username" name="username" type="text" maxlength="50" autocomplete="username" required>
              </div>
              <div class="form-group">
                <label for="user-email">Email</label>
                <input id="user-email" name="email" type="email" maxlength="150" autocomplete="email" required>
              </div>
              <div class="form-group">
                <label for="user-password">Password</label>
                <input id="user-password" name="password" type="password" minlength="8" autocomplete="new-password" required>
              </div>
              <div class="form-group">
                <label for="user-role">Role akses</label>
                <select id="user-role" name="role_id" required>
                  <option value="" disabled selected>Pilih role</option>
                  <?php foreach ($roles as $role): ?>
                    <option value="<?= (int)$role['id_role'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn-submit" type="submit">Simpan User</button>
          </form>
        </section>

        <section class="operation-card management-card">
          <h3>Manajemen User</h3>
          <p>Daftar akun user beserta pilihan untuk mengubah atau menghapusnya.</p>
          <h4 class="table-title">Daftar User</h4>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
              </thead>
              <tbody>
                <?php if (!$users): ?>
                  <tr><td class="empty-row" colspan="6">Belum ada data user.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                  <tr>
                    <td><?= htmlspecialchars($user['name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($user['username'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($user['role_name'] ?? $user['role'] ?? '-') ?></td>
                    <td><?= (int)$user['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></td>
                    <td>
                      <div class="row-actions">
                        <details class="edit-details">
                          <summary>Ubah</summary>
                          <form class="edit-form" action="proses_user.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="update_user">
                            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                            <div class="form-group">
                              <label>Nama</label>
                              <input name="name" type="text" maxlength="150" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                              <label>Username</label>
                              <input name="username" type="text" maxlength="50" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                              <label>Email</label>
                              <input name="email" type="email" maxlength="150" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                              <label>Password baru (kosongkan jika tidak diganti)</label>
                              <input name="password" type="password" minlength="8" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                              <label>Role akses</label>
                              <select name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                  <option value="<?= (int)$role['id_role'] ?>" <?= (int)$user['role_id'] === (int)$role['id_role'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['role_name']) ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <button class="action-button" type="submit">Simpan perubahan</button>
                          </form>
                        </details>
                        <?php if ((int)$user['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                          <form action="proses_user.php" method="POST" onsubmit="return confirm('Hapus user ini secara permanen? Data karyawan dan riwayat terkait tetap disimpan, tetapi tidak lagi terhubung ke akun ini.');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                            <button class="action-button delete" type="submit">Hapus</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="operation-card" id="employees">
          <h3>Tambah Karyawan</h3>
          <p>Tambahkan data karyawan ke tabel employees.</p>
          <form class="add-form" action="proses_karyawan.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="action" value="add_employee">
              <div class="form-group">
                <label for="employee-name">Nama karyawan</label>
                <input id="employee-name" name="name" type="text" maxlength="100" required>
              </div>
              <div class="form-group">
                <label for="employee-user">Akun user (opsional)</label>
                <select id="employee-user" name="user_id">
                  <option value="">Tanpa akun user</option>
                  <?php foreach ($availableEmployeeUsers as $employeeUser): ?>
                    <option value="<?= (int)$employeeUser['id'] ?>">
                      <?= htmlspecialchars($employeeUser['name'] . ' (' . ($employeeUser['username'] ?: $employeeUser['email']) . ')') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (!$availableEmployeeUsers): ?>
                  <small>Tidak ada akun aktif yang belum dikaitkan dengan karyawan.</small>
                <?php endif; ?>
              </div>
              <div class="form-group">
                <label for="employee-division">Divisi</label>
                <select id="employee-division" name="division_id" required>
                  <option value="" disabled selected>Pilih divisi</option>
                  <?php foreach ($divisions as $division): ?>
                    <option value="<?= (int)$division['id_divisi'] ?>"><?= htmlspecialchars($division['divisi']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="employee-position">Jabatan</label>
                <input id="employee-position" name="position" type="text" maxlength="100" required>
              </div>
              <div class="form-group">
                <label for="employee-salary">Gaji</label>
                <input id="employee-salary" name="salary" type="number" min="0" step="0.01" required>
              </div>
              <div class="form-group">
                <label for="employee-start-date">Tanggal masuk</label>
                <input id="employee-start-date" name="start_date" type="date" required>
              </div>
              <button class="btn-submit" type="submit">Simpan Karyawan</button>
          </form>
        </section>

        <section class="operation-card management-card">
          <h3>Manajemen Karyawan</h3>
          <p>Daftar karyawan beserta pilihan untuk mengubah atau menghapusnya.</p>
          <h4 class="table-title">Daftar Karyawan</h4>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Nama</th><th>Akun terkait</th><th>Divisi</th><th>Jabatan</th><th>Gaji</th><th>Tanggal masuk</th><th>Aksi</th></tr>
              </thead>
              <tbody>
                <?php if (!$employees): ?>
                  <tr><td class="empty-row" colspan="7">Belum ada data karyawan.</td></tr>
                <?php endif; ?>
                <?php foreach ($employees as $employee): ?>
                  <tr>
                    <td><?= htmlspecialchars($employee['nama']) ?></td>
                    <td><?= htmlspecialchars($employee['account_name'] ?? '-') ?><br><?= htmlspecialchars($employee['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($employee['divisi']) ?></td>
                    <td><?= htmlspecialchars($employee['jabatan']) ?></td>
                    <td><?= htmlspecialchars(number_format((float)$employee['gaji'], 2, ',', '.')) ?></td>
                    <td><?= htmlspecialchars($employee['tanggal_masuk']) ?></td>
                    <td>
                      <div class="row-actions">
                        <details class="edit-details">
                          <summary>Ubah</summary>
                          <form class="edit-form" action="proses_karyawan.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="update_employee">
                            <input type="hidden" name="id" value="<?= (int)$employee['id_employee'] ?>">
                            <div class="form-group">
                              <label>Akun user (opsional)</label>
                              <select name="user_id">
                                <option value="">Tanpa akun user</option>
                                <?php foreach ($employeeAccountUsers as $employeeUser): ?>
                                  <?php if ($employeeUser['linked_employee_id'] === null || (int)$employeeUser['linked_employee_id'] === (int)$employee['id_employee']): ?>
                                    <option value="<?= (int)$employeeUser['id'] ?>" <?= (int)$employee['id_user'] === (int)$employeeUser['id'] ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($employeeUser['name'] . ' (' . ($employeeUser['username'] ?: $employeeUser['email']) . ')') ?>
                                    </option>
                                  <?php endif; ?>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <div class="form-group">
                              <label>Nama karyawan</label>
                              <input name="name" type="text" maxlength="100" value="<?= htmlspecialchars($employee['nama']) ?>" required>
                            </div>
                            <div class="form-group">
                              <label>Divisi</label>
                              <select name="division_id" required>
                                <?php foreach ($divisions as $division): ?>
                                  <option value="<?= (int)$division['id_divisi'] ?>" <?= $employee['divisi'] === $division['divisi'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($division['divisi']) ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <div class="form-group">
                              <label>Jabatan</label>
                              <input name="position" type="text" maxlength="100" value="<?= htmlspecialchars($employee['jabatan']) ?>" required>
                            </div>
                            <div class="form-group">
                              <label>Gaji</label>
                              <input name="salary" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string)$employee['gaji']) ?>" required>
                            </div>
                            <div class="form-group">
                              <label>Tanggal masuk</label>
                              <input name="start_date" type="date" value="<?= htmlspecialchars($employee['tanggal_masuk']) ?>" required>
                            </div>
                            <button class="action-button" type="submit">Simpan perubahan</button>
                          </form>
                        </details>
                        <form action="proses_karyawan.php" method="POST" onsubmit="return confirm('Hapus data karyawan ini?');">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                          <input type="hidden" name="action" value="delete_employee">
                          <input type="hidden" name="id" value="<?= (int)$employee['id_employee'] ?>">
                          <button class="action-button delete" type="submit">Hapus</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="operation-card" id="divisions">
          <h3>Tambah Divisi / Departemen</h3>
          <p>Tambahkan nama divisi ke tabel divisi.</p>
          <form class="add-form" action="proses_divisi.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="action" value="add_division">
              <div class="form-group">
                <label for="division-name">Nama divisi</label>
                <input id="division-name" name="division" type="text" maxlength="100" required>
              </div>
              <button class="btn-submit" type="submit">Simpan Divisi</button>
          </form>
        </section>

        <section class="operation-card management-card">
          <h3>Manajemen Divisi</h3>
          <p>Daftar divisi beserta pilihan untuk mengubah atau menghapusnya.</p>
          <h4 class="table-title">Daftar Divisi</h4>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Divisi</th><th>Aksi</th></tr>
              </thead>
              <tbody>
                <?php if (!$divisions): ?>
                  <tr><td class="empty-row" colspan="2">Belum ada data divisi.</td></tr>
                <?php endif; ?>
                <?php foreach ($divisions as $division): ?>
                  <tr>
                    <td><?= htmlspecialchars($division['divisi']) ?></td>
                    <td>
                      <div class="row-actions">
                        <details class="edit-details">
                          <summary>Ubah</summary>
                          <form class="edit-form" action="proses_divisi.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="update_division">
                            <input type="hidden" name="id" value="<?= (int)$division['id_divisi'] ?>">
                            <div class="form-group">
                              <label>Nama divisi</label>
                              <input name="division" type="text" maxlength="100" value="<?= htmlspecialchars($division['divisi']) ?>" required>
                            </div>
                            <button class="action-button" type="submit">Simpan perubahan</button>
                          </form>
                        </details>
                        <form action="proses_divisi.php" method="POST" onsubmit="return confirm('Hapus divisi ini?');">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                          <input type="hidden" name="action" value="delete_division">
                          <input type="hidden" name="id" value="<?= (int)$division['id_divisi'] ?>">
                          <button class="action-button delete" type="submit">Hapus</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="operation-card" id="payrolls">
          <h3>Tambah Payroll</h3>
          <p>Catat payroll karyawan pada periode dan tanggal pembayaran.</p>
          <form class="add-form" action="proses_payroll.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="add_payroll">
            <div class="form-group">
              <label for="payroll-employee">Karyawan</label>
              <select id="payroll-employee" name="employee_id" required>
                <option value="" disabled selected>Pilih karyawan</option>
                <?php foreach ($employees as $employee): ?>
                  <option value="<?= (int)$employee['id_employee'] ?>">
                    <?= htmlspecialchars($employee['nama'] . ' - ' . $employee['divisi']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="payroll-period">Periode payroll</label>
              <input id="payroll-period" name="period" type="month" required>
            </div>
            <div class="form-group">
              <label for="payroll-total">Total gaji</label>
              <input id="payroll-total" name="total_salary" type="number" min="0" step="0.01" required>
            </div>
            <div class="form-group">
              <label for="payroll-payment-date">Tanggal bayar</label>
              <input id="payroll-payment-date" name="payment_date" type="date" required>
            </div>
            <button class="btn-submit" type="submit" <?= !$employees ? 'disabled' : '' ?>>Simpan Payroll</button>
          </form>
          <?php if (!$employees): ?>
            <p class="form-hint">Tambahkan data karyawan terlebih dahulu sebelum membuat payroll.</p>
          <?php endif; ?>
        </section>

        <section class="operation-card management-card">
          <h3>Manajemen Payroll</h3>
          <p>Daftar payroll beserta pilihan untuk mengubah atau menghapusnya.</p>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Karyawan</th><th>Periode</th><th>Total gaji</th><th>Tanggal bayar</th><th>Aksi</th></tr>
              </thead>
              <tbody>
                <?php if (!$payrolls): ?>
                  <tr><td class="empty-row" colspan="5">Belum ada data payroll.</td></tr>
                <?php endif; ?>
                <?php foreach ($payrolls as $payroll): ?>
                  <tr>
                    <td><?= htmlspecialchars($payroll['employee_name']) ?></td>
                    <td><?= htmlspecialchars(date('m/Y', strtotime($payroll['periode']))) ?></td>
                    <td><?= htmlspecialchars(number_format((float)$payroll['total_gaji'], 2, ',', '.')) ?></td>
                    <td><?= htmlspecialchars($payroll['tanggal_bayar']) ?></td>
                    <td>
                      <div class="row-actions">
                        <details class="edit-details">
                          <summary>Ubah</summary>
                          <form class="edit-form" action="proses_payroll.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="update_payroll">
                            <input type="hidden" name="id" value="<?= (int)$payroll['id_payroll'] ?>">
                            <div class="form-group">
                              <label>Karyawan</label>
                              <select name="employee_id" required>
                                <?php foreach ($employees as $employee): ?>
                                  <option value="<?= (int)$employee['id_employee'] ?>" <?= (int)$payroll['id_employee'] === (int)$employee['id_employee'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($employee['nama'] . ' - ' . $employee['divisi']) ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <div class="form-group">
                              <label>Periode payroll</label>
                              <input name="period" type="month" value="<?= htmlspecialchars(substr($payroll['periode'], 0, 7)) ?>" required>
                            </div>
                            <div class="form-group">
                              <label>Total gaji</label>
                              <input name="total_salary" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string)$payroll['total_gaji']) ?>" required>
                            </div>
                            <div class="form-group">
                              <label>Tanggal bayar</label>
                              <input name="payment_date" type="date" value="<?= htmlspecialchars($payroll['tanggal_bayar']) ?>" required>
                            </div>
                            <button class="action-button" type="submit">Simpan perubahan</button>
                          </form>
                        </details>
                        <form action="proses_payroll.php" method="POST" onsubmit="return confirm('Hapus data payroll ini?');">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                          <input type="hidden" name="action" value="delete_payroll">
                          <input type="hidden" name="id" value="<?= (int)$payroll['id_payroll'] ?>">
                          <button class="action-button delete" type="submit">Hapus</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    <?php else: ?>
      <p class="admin-note">Formulir operasional hanya tersedia untuk akun Admin.</p>
    <?php endif; ?>
      </div><!-- /.container -->
    </main><!-- /.hris-main -->
  </div><!-- /.hris-layout -->

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const navLinks = document.querySelectorAll('.hris-nav-pill');
      navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
          navLinks.forEach(function (l) { l.classList.remove('active'); });
          this.classList.add('active');
        });
      });

      // Update active nav pill on scroll
      const sections = document.querySelectorAll('section[id], div[id="dashboard"]');
      if ('IntersectionObserver' in window && sections.length > 0) {
        const observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              const id = entry.target.getAttribute('id');
              if (id) {
                navLinks.forEach(function (link) {
                  if (link.getAttribute('href') === '#' + id) {
                    navLinks.forEach(function (l) { l.classList.remove('active'); });
                    link.classList.add('active');
                  }
                });
              }
            }
          });
        }, { rootMargin: '-10% 0px -70% 0px' });

        sections.forEach(function (section) {
          observer.observe(section);
        });
      }
    });
  </script>
</body>
</html>