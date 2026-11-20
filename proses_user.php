<?php
include "session.php";

function userFlash(string $message, bool $error = false): void
{
  $_SESSION['dashboard_flash'] = ['message' => $message, 'error' => $error];
  header("Location: dashboard.php");
  exit();
}

function userPositiveId(mixed $value): ?int
{
  $id = filter_var($value, FILTER_VALIDATE_INT);
  return $id !== false && $id > 0 ? $id : null;
}

function userRole(mysqli $conn, mixed $roleId): ?array
{
  $id = userPositiveId($roleId);
  if ($id === null) {
    return null;
  }
  $stmt = $conn->prepare("SELECT id_role, role_name FROM roles WHERE id_role = ? LIMIT 1");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: dashboard.php");
  exit();
}
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  header("Location: index.php");
  exit();
}
if (!hr_is_admin($_SESSION['role'] ?? '')) {
  http_response_code(403);
  exit("Akses ditolak.");
}
if (
  !isset($_SESSION['csrf_token'], $_POST['csrf_token']) ||
  !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])
) {
  userFlash("Permintaan tidak valid. Muat ulang halaman lalu coba lagi.", true);
}

include "koneksi.php";
$action = $_POST['action'] ?? '';
$transactionStarted = false;

try {
  if ($action === 'add_user') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = userRole($conn, $_POST['role_id'] ?? null);
    if (
      $name === '' || $username === '' || $email === '' ||
      !filter_var($email, FILTER_VALIDATE_EMAIL) ||
      strlen($password) < 8 || $role === null
    ) {
      userFlash("Data user tidak valid. Pastikan semua kolom benar dan password minimal 8 karakter.", true);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
      "INSERT INTO users (name, username, email, password_hash, role, role_id, is_active)
       VALUES (?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param("sssssi", $name, $username, $email, $passwordHash, $role['role_name'], $role['id_role']);
    $stmt->execute();
    $newUserId = (int)$conn->insert_id;
    if ($newUserId > 0) {
      $conn->query("UPDATE users SET user_id = id WHERE id = {$newUserId} AND user_id IS NULL");
    }
    userFlash("User berhasil ditambahkan.");
  }

  if ($action === 'update_user') {
    $id = userPositiveId($_POST['id'] ?? null);
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = userRole($conn, $_POST['role_id'] ?? null);
    if (
      $id === null || $name === '' || $username === '' || $email === '' ||
      !filter_var($email, FILTER_VALIDATE_EMAIL) ||
      ($password !== '' && strlen($password) < 8) || $role === null
    ) {
      userFlash("Data user tidak valid. Periksa kembali semua kolom.", true);
    }
    if ($id === (int)($_SESSION['user_id'] ?? 0) && $role['role_name'] !== 'Admin') {
      userFlash("Role akun Admin yang sedang digunakan tidak dapat diturunkan.", true);
    }

    $conn->begin_transaction();
    $transactionStarted = true;
    if ($password === '') {
      $stmt = $conn->prepare(
        "UPDATE users SET name = ?, username = ?, email = ?, role = ?, role_id = ?
         WHERE id = ? AND deleted_at IS NULL"
      );
      $stmt->bind_param("ssssii", $name, $username, $email, $role['role_name'], $role['id_role'], $id);
    } else {
      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare(
        "UPDATE users SET name = ?, username = ?, email = ?, password_hash = ?, role = ?, role_id = ?
         WHERE id = ? AND deleted_at IS NULL"
      );
      $stmt->bind_param("sssssii", $name, $username, $email, $passwordHash, $role['role_name'], $role['id_role'], $id);
    }
    $stmt->execute();
    $employeeUpdate = $conn->prepare("UPDATE employees SET nama = ? WHERE id_user = ?");
    $employeeUpdate->bind_param("si", $name, $id);
    $employeeUpdate->execute();
    $conn->commit();
    $transactionStarted = false;

    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
      $_SESSION['name'] = $name;
      $_SESSION['username'] = $username;
      $_SESSION['email'] = $email;
      $_SESSION['role'] = $role['role_name'];
    }
    userFlash("Data user berhasil diperbarui.");
  }

  if ($action === 'delete_user') {
    $id = userPositiveId($_POST['id'] ?? null);
    if ($id === null) {
      userFlash("User tidak valid.", true);
    }
    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
      userFlash("Akun yang sedang digunakan tidak dapat dihapus.", true);
    }
    $conn->begin_transaction();
    $transactionStarted = true;

    $employeeUpdate = $conn->prepare("UPDATE employees SET id_user = NULL WHERE id_user = ?");
    $employeeUpdate->bind_param("i", $id);
    $employeeUpdate->execute();

    try {
      $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND deleted_at IS NULL");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      if ($stmt->affected_rows !== 1) {
        $conn->rollback();
        $transactionStarted = false;
        userFlash("User tidak ditemukan atau sudah dihapus.", true);
      }
    } catch (mysqli_sql_exception $e) {
      if ((int)$e->getCode() === 1451) {
        // Terikat foreign key riwayat transaksi/audit: arsipkan melalui soft-delete
        $softDelete = $conn->prepare("UPDATE users SET deleted_at = NOW(), is_active = 0 WHERE id = ?");
        $softDelete->bind_param("i", $id);
        $softDelete->execute();
      } else {
        throw $e;
      }
    }
    $conn->commit();
    $transactionStarted = false;
    userFlash("User berhasil dihapus permanen. Data karyawan dan riwayat terkait tetap disimpan.");
  }

  userFlash("Jenis proses user tidak dikenal.", true);
} catch (mysqli_sql_exception $exception) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  if ((int)$exception->getCode() === 1062) {
    userFlash("Username atau email tersebut sudah digunakan.", true);
  }
  error_log("Proses user HRIS gagal: " . $exception->getMessage());
  userFlash("Data user gagal disimpan karena terjadi kesalahan database.", true);
}
