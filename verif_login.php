<?php

include "koneksi.php";
include "session.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: index.php");
  exit();
}

$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
  header("Location: index.php?error=1");
  exit();
}

/*
|--------------------------------------------------------------------------
| Cari user berdasarkan username atau email
|--------------------------------------------------------------------------
*/

$sql = "SELECT 
            u.id,
            u.user_id,
            u.employee_id,
            u.role_id,
            u.name,
            u.username,
            u.email,
            u.password_hash,
            r.role_name AS role,
            u.is_active
        FROM users u
        LEFT JOIN roles r ON r.id_role = u.role_id
        WHERE (u.username = ? OR u.email = ?)
        AND u.deleted_at IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
  die("Query gagal: " . $conn->error);
}

$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| User tidak ditemukan
|--------------------------------------------------------------------------
*/

if (!$user) {
  header("Location: index.php?error=1");
  exit();
}

/*
|--------------------------------------------------------------------------
| Cek apakah akun aktif
|--------------------------------------------------------------------------
*/

if ((int)$user['is_active'] !== 1) {
  header("Location: index.php?inactive=1");
  exit();
}

/*
|--------------------------------------------------------------------------
| Cek password
|--------------------------------------------------------------------------
*/

if (
  empty($user['password_hash']) ||
  !password_verify($password, $user['password_hash'])
) {

  header("Location: index.php?error=1");
  exit();
}

/*
|--------------------------------------------------------------------------
| Login berhasil
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);

$_SESSION['login'] = true;
$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = trim((string)$user['role']);
$_SESSION['last_activity'] = time();

if (hr_is_expenses_user($user['role'], $user['username'])) {
  $authUserData = [
    'user_id' => (int) ($user['user_id'] ?? $user['id']),
    'username' => $user['username'],
    'email' => $user['email'],
    'employee_id' => !empty($user['employee_id']) ? (int) $user['employee_id'] : null,
    'role_id' => (int) ($user['role_id'] ?? 0),
    'role_name' => trim((string)$user['role']),
  ];
  $_SESSION['_auth_user'] = $authUserData;

  session_write_close();
  session_name('expenses_session');
  $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
  $_SESSION['_auth_user'] = $authUserData;
  $expensesSessionId = session_id();
  session_write_close();
}

/*
|--------------------------------------------------------------------------
| Update last_login
|--------------------------------------------------------------------------
*/

$update = $conn->prepare(
  "UPDATE users SET last_login = NOW() WHERE id = ?"
);

$update->bind_param("i", $user['id']);
$update->execute();

/*
|--------------------------------------------------------------------------
| Simpan activity log
|--------------------------------------------------------------------------
*/

$ip = $_SERVER['REMOTE_ADDR'] ?? null;

$log = $conn->prepare(
  "INSERT INTO activity_logs
    (user_id, action, entity_type, entity_id, summary, ip_address)
    VALUES (?, 'LOGIN', 'auth', ?, ?, ?)"
);

$summary = "Login " . ($user['email'] ?? $user['username']) . " berhasil";

$log->bind_param(
  "iiss",
  $user['id'],
  $user['id'],
  $summary,
  $ip
);

$log->execute();

/*
|--------------------------------------------------------------------------
| Masuk dashboard
|--------------------------------------------------------------------------
*/

if (hr_is_sales_role($user['role'])) {
  header("Location: sales/index.php");
  exit();
}

if (hr_is_inventory_role($user['role'])) {
  header("Location: Inventory/index.php");
  exit();
}

if (hr_is_purchasing_role($user['role'])) {
  header("Location: purchasing/dashboard.php");
  exit();
}

if (hr_is_expenses_user($user['role'], $user['username'])) {
  $baseExpenseUrl = (!empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'expenses.test')
      ? "http://expenses.test/"
      : "expenses/";
  $target = !empty($expensesSessionId)
      ? $baseExpenseUrl . "?sso_sid=" . urlencode($expensesSessionId)
      : $baseExpenseUrl;
  header("Location: " . $target);
  exit();
}

header("Location: dashboard.php");
exit();
