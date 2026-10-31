<?php
include "session.php";

function divisiFlash(string $message, bool $error = false): void
{
  $_SESSION['dashboard_flash'] = ['message' => $message, 'error' => $error];
  header("Location: dashboard.php");
  exit();
}

function divisiId(mixed $value): ?int
{
  $id = filter_var($value, FILTER_VALIDATE_INT);
  return $id !== false && $id > 0 ? $id : null;
}

function validDivisionName(string $value): bool
{
  return preg_match('/^.{1,100}$/us', $value) === 1;
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
  divisiFlash("Permintaan tidak valid. Muat ulang halaman lalu coba lagi.", true);
}

include "koneksi.php";
$action = $_POST['action'] ?? '';
$transactionStarted = false;

try {
  if ($action === 'add_division') {
    $division = trim($_POST['division'] ?? '');
    if (!validDivisionName($division)) {
      divisiFlash("Nama divisi harus berisi 1 sampai 100 karakter.", true);
    }
    $check = $conn->prepare("SELECT id_divisi FROM divisi WHERE divisi = ? LIMIT 1");
    $check->bind_param("s", $division);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) {
      divisiFlash("Divisi tersebut sudah terdaftar.", true);
    }
    $stmt = $conn->prepare("INSERT INTO divisi (divisi) VALUES (?)");
    $stmt->bind_param("s", $division);
    $stmt->execute();
    divisiFlash("Divisi berhasil ditambahkan.");
  }

  if ($action === 'update_division') {
    $id = divisiId($_POST['id'] ?? null);
    $division = trim($_POST['division'] ?? '');
    if ($id === null || !validDivisionName($division)) {
      divisiFlash("Nama divisi harus berisi 1 sampai 100 karakter.", true);
    }
    $check = $conn->prepare("SELECT id_divisi FROM divisi WHERE divisi = ? AND id_divisi <> ? LIMIT 1");
    $check->bind_param("si", $division, $id);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) {
      divisiFlash("Nama divisi tersebut sudah digunakan.", true);
    }

    $conn->begin_transaction();
    $transactionStarted = true;
    $oldStmt = $conn->prepare("SELECT divisi FROM divisi WHERE id_divisi = ? LIMIT 1");
    $oldStmt->bind_param("i", $id);
    $oldStmt->execute();
    $oldDivision = $oldStmt->get_result()->fetch_assoc();
    if (!$oldDivision) {
      $conn->rollback();
      $transactionStarted = false;
      divisiFlash("Divisi tidak ditemukan.", true);
    }
    $stmt = $conn->prepare("UPDATE divisi SET divisi = ? WHERE id_divisi = ?");
    $stmt->bind_param("si", $division, $id);
    $stmt->execute();
    $employeeUpdate = $conn->prepare("UPDATE employees SET divisi = ? WHERE divisi = ?");
    $employeeUpdate->bind_param("ss", $division, $oldDivision['divisi']);
    $employeeUpdate->execute();
    $conn->commit();
    $transactionStarted = false;
    divisiFlash("Divisi dan data karyawan terkait berhasil diperbarui.");
  }

  if ($action === 'delete_division') {
    $id = divisiId($_POST['id'] ?? null);
    if ($id === null) {
      divisiFlash("Divisi tidak valid.", true);
    }
    $divisionStmt = $conn->prepare("SELECT divisi FROM divisi WHERE id_divisi = ? LIMIT 1");
    $divisionStmt->bind_param("i", $id);
    $divisionStmt->execute();
    $division = $divisionStmt->get_result()->fetch_assoc();
    if (!$division) {
      divisiFlash("Divisi tidak ditemukan.", true);
    }
    $employeeCheck = $conn->prepare("SELECT id_employee FROM employees WHERE divisi = ? LIMIT 1");
    $employeeCheck->bind_param("s", $division['divisi']);
    $employeeCheck->execute();
    if ($employeeCheck->get_result()->fetch_assoc()) {
      divisiFlash("Divisi tidak dapat dihapus karena masih digunakan oleh karyawan.", true);
    }
    $stmt = $conn->prepare("DELETE FROM divisi WHERE id_divisi = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
      divisiFlash("Divisi tidak ditemukan.", true);
    }
    divisiFlash("Divisi berhasil dihapus.");
  }

  divisiFlash("Jenis proses divisi tidak dikenal.", true);
} catch (mysqli_sql_exception $exception) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  if ((int)$exception->getCode() === 1062) {
    divisiFlash("Nama divisi tersebut sudah digunakan.", true);
  }
  error_log("Proses divisi HRIS gagal: " . $exception->getMessage());
  divisiFlash("Data divisi gagal disimpan karena terjadi kesalahan database.", true);
}
