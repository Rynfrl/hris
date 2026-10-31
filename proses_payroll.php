<?php
include "session.php";

function payrollFlash(string $message, bool $error = false): void
{
  $_SESSION['dashboard_flash'] = ['message' => $message, 'error' => $error];
  header("Location: dashboard.php");
  exit();
}

function payrollId(mixed $value): ?int
{
  $id = filter_var($value, FILTER_VALIDATE_INT);
  return $id !== false && $id > 0 ? $id : null;
}

function payrollDate(mixed $value): bool
{
  if (!is_string($value)) {
    return false;
  }
  $date = DateTime::createFromFormat('!Y-m-d', $value);
  return $date !== false && $date->format('Y-m-d') === $value;
}

function payrollPeriod(mixed $value): ?string
{
  if (!is_string($value) || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) !== 1) {
    return null;
  }
  return $value . '-01';
}

function payrollSalary(mixed $value): bool
{
  return is_numeric($value) && (float)$value >= 0 && (float)$value <= 9999999999999.99;
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
  payrollFlash("Permintaan tidak valid. Muat ulang halaman lalu coba lagi.", true);
}

include "koneksi.php";
$action = $_POST['action'] ?? '';

try {
  if ($action === 'add_payroll' || $action === 'update_payroll') {
    $id = $action === 'update_payroll' ? payrollId($_POST['id'] ?? null) : null;
    $employeeId = payrollId($_POST['employee_id'] ?? null);
    $period = payrollPeriod($_POST['period'] ?? '');
    $salaryInput = $_POST['total_salary'] ?? '';
    $paymentDate = $_POST['payment_date'] ?? '';
    if (
      ($action === 'update_payroll' && $id === null) ||
      $employeeId === null || $period === null ||
      !payrollSalary($salaryInput) || !payrollDate($paymentDate)
    ) {
      payrollFlash("Data payroll tidak valid. Periksa kembali semua kolom.", true);
    }

    $employeeCheck = $conn->prepare("SELECT id_employee FROM employees WHERE id_employee = ? LIMIT 1");
    $employeeCheck->bind_param("i", $employeeId);
    $employeeCheck->execute();
    if (!$employeeCheck->get_result()->fetch_assoc()) {
      payrollFlash("Karyawan yang dipilih tidak ditemukan.", true);
    }

    if ($action === 'add_payroll') {
      $duplicateCheck = $conn->prepare(
        "SELECT id_payroll FROM payrolls WHERE id_employee = ? AND periode = ? LIMIT 1"
      );
      $duplicateCheck->bind_param("is", $employeeId, $period);
    } else {
      $duplicateCheck = $conn->prepare(
        "SELECT id_payroll FROM payrolls
         WHERE id_employee = ? AND periode = ? AND id_payroll <> ? LIMIT 1"
      );
      $duplicateCheck->bind_param("isi", $employeeId, $period, $id);
    }
    $duplicateCheck->execute();
    if ($duplicateCheck->get_result()->fetch_assoc()) {
      payrollFlash("Payroll untuk karyawan tersebut pada periode itu sudah ada.", true);
    }

    $salary = number_format((float)$salaryInput, 2, '.', '');
    if ($action === 'add_payroll') {
      $stmt = $conn->prepare(
        "INSERT INTO payrolls (id_employee, periode, total_gaji, tanggal_bayar)
         VALUES (?, ?, ?, ?)"
      );
      $stmt->bind_param("isss", $employeeId, $period, $salary, $paymentDate);
      $stmt->execute();
      payrollFlash("Data payroll berhasil ditambahkan.");
    }

    $stmt = $conn->prepare(
      "UPDATE payrolls
       SET id_employee = ?, periode = ?, total_gaji = ?, tanggal_bayar = ?
       WHERE id_payroll = ?"
    );
    $stmt->bind_param("isssi", $employeeId, $period, $salary, $paymentDate, $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
      $exists = $conn->prepare("SELECT id_payroll FROM payrolls WHERE id_payroll = ? LIMIT 1");
      $exists->bind_param("i", $id);
      $exists->execute();
      if (!$exists->get_result()->fetch_assoc()) {
        payrollFlash("Data payroll tidak ditemukan.", true);
      }
    }
    payrollFlash("Data payroll berhasil diperbarui.");
  }

  if ($action === 'delete_payroll') {
    $id = payrollId($_POST['id'] ?? null);
    if ($id === null) {
      payrollFlash("Data payroll tidak valid.", true);
    }
    $stmt = $conn->prepare("DELETE FROM payrolls WHERE id_payroll = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
      payrollFlash("Data payroll tidak ditemukan.", true);
    }
    payrollFlash("Data payroll berhasil dihapus.");
  }

  payrollFlash("Jenis proses payroll tidak dikenal.", true);
} catch (mysqli_sql_exception $exception) {
  if ((int)$exception->getCode() === 1062) {
    payrollFlash("Payroll untuk karyawan tersebut pada periode itu sudah ada.", true);
  }
  error_log("Proses payroll HRIS gagal: " . $exception->getMessage());
  payrollFlash("Data payroll gagal disimpan karena terjadi kesalahan database.", true);
}
