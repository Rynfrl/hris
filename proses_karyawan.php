<?php
include "session.php";

function karyawanFlash(string $message, bool $error = false): void
{
  $_SESSION['dashboard_flash'] = ['message' => $message, 'error' => $error];
  header("Location: dashboard.php");
  exit();
}

function karyawanId(mixed $value): ?int
{
  $id = filter_var($value, FILTER_VALIDATE_INT);
  return $id !== false && $id > 0 ? $id : null;
}

function karyawanDivision(mysqli $conn, mixed $divisionId): ?string
{
  $id = karyawanId($divisionId);
  if ($id === null) {
    return null;
  }
  $stmt = $conn->prepare("SELECT divisi FROM divisi WHERE id_divisi = ? LIMIT 1");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  return $row['divisi'] ?? null;
}

function karyawanDate(mixed $value): bool
{
  if (!is_string($value)) {
    return false;
  }
  $date = DateTime::createFromFormat('!Y-m-d', $value);
  return $date !== false && $date->format('Y-m-d') === $value;
}

function karyawanSalary(mixed $value): bool
{
  return is_numeric($value) && (float)$value >= 0 && (float)$value <= 9999999999999.99;
}

function karyawanAvailableUser(mysqli $conn, mixed $userId, ?int $employeeId = null): int|false|null
{
  if ($userId === null || $userId === '') {
    return null;
  }

  $id = karyawanId($userId);
  if ($id === null) {
    return false;
  }

  $sql = "SELECT u.id
          FROM users u
          LEFT JOIN employees e ON e.id_user = u.id
          WHERE u.id = ? AND u.deleted_at IS NULL
            AND (u.is_active = 1 OR e.id_employee = ?)";
  if ($employeeId === null) {
    $sql = "SELECT u.id
            FROM users u
            LEFT JOIN employees e ON e.id_user = u.id
            WHERE u.id = ? AND u.deleted_at IS NULL AND u.is_active = 1
              AND e.id_employee IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
  } else {
    $sql .= " AND (e.id_employee IS NULL OR e.id_employee = ?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $id, $employeeId, $employeeId);
  }
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc() ? $id : false;
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
  karyawanFlash("Permintaan tidak valid. Muat ulang halaman lalu coba lagi.", true);
}

include "koneksi.php";
$action = $_POST['action'] ?? '';

try {
  if ($action === 'add_employee') {
    $name = trim($_POST['name'] ?? '');
    $division = karyawanDivision($conn, $_POST['division_id'] ?? null);
    $userId = karyawanAvailableUser($conn, $_POST['user_id'] ?? null);
    $position = trim($_POST['position'] ?? '');
    $salaryInput = $_POST['salary'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    if ($name === '' || strlen($name) > 100 || $division === null || $userId === false || $position === '' || !karyawanSalary($salaryInput) || !karyawanDate($startDate)) {
      karyawanFlash("Data karyawan tidak valid. Periksa kembali semua kolom.", true);
    }

    if (strlen($position) > 100) {
      karyawanFlash("Jabatan maksimal 100 karakter.", true);
    }
    $salary = number_format((float)$salaryInput, 2, '.', '');
    $stmt = $conn->prepare(
      "INSERT INTO employees (id_user, nama, divisi, jabatan, gaji, tanggal_masuk)
       VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssss", $userId, $name, $division, $position, $salary, $startDate);
    $stmt->execute();
    $newEmpId = (int)$conn->insert_id;
    if ($newEmpId > 0) {
      $divisionId = karyawanId($_POST['division_id'] ?? null);
      $userEmail = null;
      if ($userId) {
        $uStmt = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
        $uStmt->bind_param("i", $userId);
        $uStmt->execute();
        $uRow = $uStmt->get_result()->fetch_assoc();
        $userEmail = $uRow['email'] ?? null;
      }
      $deptUpdateSql = $divisionId !== null ? "department_id = {$divisionId}," : "";
      $emailUpdateSql = $userEmail !== null ? "email = IFNULL(email, '{$conn->real_escape_string($userEmail)}')," : "";
      $conn->query("UPDATE employees SET 
        employee_id = id_employee, 
        full_name = IFNULL(full_name, nama), 
        position = IFNULL(position, jabatan), 
        join_date = IFNULL(join_date, tanggal_masuk),
        {$deptUpdateSql}
        {$emailUpdateSql}
        employee_number = IFNULL(employee_number, CONCAT('EMP-HRIS-', LPAD(id_employee, 4, '0')))
        WHERE id_employee = {$newEmpId}");
      if ($userId) {
        $conn->query("UPDATE users SET employee_id = {$newEmpId} WHERE id = " . (int)$userId);
      }
    }
    karyawanFlash("Data karyawan berhasil ditambahkan.");
  }

  if ($action === 'update_employee') {
    $id = karyawanId($_POST['id'] ?? null);
    $name = trim($_POST['name'] ?? '');
    $division = karyawanDivision($conn, $_POST['division_id'] ?? null);
    $userId = $id === null ? false : karyawanAvailableUser($conn, $_POST['user_id'] ?? null, $id);
    $position = trim($_POST['position'] ?? '');
    $salaryInput = $_POST['salary'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    if (
      $id === null || $name === '' || strlen($name) > 100 || $division === null || $userId === false ||
      $position === '' || strlen($position) > 100 ||
      !karyawanSalary($salaryInput) || !karyawanDate($startDate)
    ) {
      karyawanFlash("Data karyawan tidak valid. Periksa kembali semua kolom.", true);
    }
    $salary = number_format((float)$salaryInput, 2, '.', '');
    $stmt = $conn->prepare(
      "UPDATE employees SET id_user = ?, nama = ?, divisi = ?, jabatan = ?, gaji = ?, tanggal_masuk = ?
       WHERE id_employee = ?"
    );
    $stmt->bind_param("isssssi", $userId, $name, $division, $position, $salary, $startDate, $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
      $check = $conn->prepare("SELECT id_employee FROM employees WHERE id_employee = ? LIMIT 1");
      $check->bind_param("i", $id);
      $check->execute();
      if (!$check->get_result()->fetch_assoc()) {
        karyawanFlash("Data karyawan tidak ditemukan.", true);
      }
    }
    $divisionId = karyawanId($_POST['division_id'] ?? null);
    $userEmail = null;
    if ($userId) {
      $uStmt = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
      $uStmt->bind_param("i", $userId);
      $uStmt->execute();
      $uRow = $uStmt->get_result()->fetch_assoc();
      $userEmail = $uRow['email'] ?? null;
    }
    $deptUpdateSql = $divisionId !== null ? "department_id = {$divisionId}," : "";
    $emailUpdateSql = $userEmail !== null ? "email = IFNULL(email, '{$conn->real_escape_string($userEmail)}')," : "";
    $conn->query("UPDATE employees SET 
      full_name = nama, 
      position = jabatan, 
      join_date = tanggal_masuk,
      {$deptUpdateSql}
      {$emailUpdateSql}
      employee_id = id_employee
      WHERE id_employee = " . (int)$id);
    if ($userId) {
      $conn->query("UPDATE users SET employee_id = " . (int)$id . " WHERE id = " . (int)$userId);
    }
    karyawanFlash("Data karyawan berhasil diperbarui.");
  }

  if ($action === 'delete_employee') {
    $id = karyawanId($_POST['id'] ?? null);
    if ($id === null) {
      karyawanFlash("Data karyawan tidak valid.", true);
    }
    $payrollCheck = $conn->prepare("SELECT id_payroll FROM payrolls WHERE id_employee = ? LIMIT 1");
    $payrollCheck->bind_param("i", $id);
    $payrollCheck->execute();
    if ($payrollCheck->get_result()->fetch_assoc()) {
      karyawanFlash("Karyawan tidak dapat dihapus karena sudah memiliki riwayat payroll.", true);
    }
    $expenseReportCheck = $conn->prepare("SELECT report_id FROM expense_reports WHERE employee_id = ? LIMIT 1");
    if ($expenseReportCheck) {
      $expenseReportCheck->bind_param("i", $id);
      $expenseReportCheck->execute();
      if ($expenseReportCheck->get_result()->fetch_assoc()) {
        karyawanFlash("Karyawan tidak dapat dihapus karena memiliki riwayat laporan biaya (GT Expense).", true);
      }
    }
    $stmt = $conn->prepare("DELETE FROM employees WHERE id_employee = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
      karyawanFlash("Data karyawan tidak ditemukan.", true);
    }
    karyawanFlash("Data karyawan berhasil dihapus.");
  }

  karyawanFlash("Jenis proses karyawan tidak dikenal.", true);
} catch (mysqli_sql_exception $exception) {
  if ((int)$exception->getCode() === 1451) {
    karyawanFlash("Karyawan tidak dapat dihapus karena masih terhubung dengan dokumen operasional lain.", true);
  }
  error_log("Proses karyawan HRIS gagal: " . $exception->getMessage());
  karyawanFlash("Data karyawan gagal disimpan karena terjadi kesalahan database.", true);
}
