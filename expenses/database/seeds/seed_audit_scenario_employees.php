<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/autoload.php';
\App\Core\Env::load(dirname(__DIR__, 2) . '/.env');

$pdo = \App\Core\Database::pdo();

echo "Seeding/Verifying HRIS Employees for Audit Scenario...\n";

// 1. Update Budi Santoso to HR Department (Dept 4)
$pdo->exec("
    UPDATE employees 
    SET department_id = 4, 
        divisi = 'Human Resources' 
    WHERE employee_id = 2 OR email = 'budi@example.test'
");
echo "[OK] Budi Santoso verified in HR Department.\n";

// 2. Ensure Andi Pratama exists
$chkAndi = $pdo->query("SELECT employee_id FROM employees WHERE full_name = 'Andi Pratama' OR email = 'andi.pratama@example.test'")->fetchColumn();
if (!$chkAndi) {
    $maxEmpId = (int) $pdo->query("SELECT MAX(employee_id) FROM employees")->fetchColumn();
    $nextEmpId = max(25, $maxEmpId + 1);

    $stmt = $pdo->prepare("
        INSERT INTO employees (
            employee_id, employee_number, full_name, email, department_id, manager_id,
            position, bank_name, bank_account, join_date, status, nama, divisi, jabatan, gaji, tanggal_masuk
        ) VALUES (
            :emp_id, 'EMP-0106', 'Andi Pratama', 'andi.pratama@example.test', 1, 1,
            'IT Specialist', 'BCA', '6789012345', '2026-10-15', 'ACTIVE', 'Andi Pratama', 'Teknologi Informasi', 'IT Specialist', 8000000.00, '2026-10-15'
        )
    ");
    $stmt->execute(['emp_id' => $nextEmpId]);
    $andiEmpId = $nextEmpId;
    echo "[OK] Andi Pratama inserted as employee_id: {$andiEmpId}.\n";
} else {
    $andiEmpId = (int) $chkAndi;
    echo "[OK] Andi Pratama already exists as employee_id: {$andiEmpId}.\n";
}

// Insert user for Andi if not exists
$chkUserAndi = $pdo->query("SELECT id FROM users WHERE username = 'andi_employee' OR email = 'andi.pratama@example.test'")->fetchColumn();
if (!$chkUserAndi) {
    $maxUserId = (int) $pdo->query("SELECT MAX(id) FROM users")->fetchColumn();
    $nextUserId = max(25, $maxUserId + 1);
    $stmtUser = $pdo->prepare("
        INSERT INTO users (id, user_id, name, username, email, password_hash, role, is_active, role_id, employee_id)
        VALUES (:id, :uid, 'Andi Pratama', 'andi_employee', 'andi.pratama@example.test', '$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy', 'EMPLOYEE', 1, 15, :emp_id)
    ");
    $stmtUser->execute(['id' => $nextUserId, 'uid' => $nextUserId, 'emp_id' => $andiEmpId]);
    echo "[OK] User andi_employee created.\n";
}

// 3. Ensure Citra Amelia exists
$chkCitra = $pdo->query("SELECT employee_id FROM employees WHERE full_name = 'Citra Amelia' OR email = 'citra.amelia@example.test'")->fetchColumn();
if (!$chkCitra) {
    $maxEmpId = (int) $pdo->query("SELECT MAX(employee_id) FROM employees")->fetchColumn();
    $nextEmpId = max(26, $maxEmpId + 1);

    $stmt = $pdo->prepare("
        INSERT INTO employees (
            employee_id, employee_number, full_name, email, department_id, manager_id,
            position, bank_name, bank_account, join_date, status, nama, divisi, jabatan, gaji, tanggal_masuk
        ) VALUES (
            :emp_id, 'EMP-0107', 'Citra Amelia', 'citra.amelia@example.test', 2, 1,
            'Finance Specialist', 'Mandiri', '7890123456', '2026-10-15', 'ACTIVE', 'Citra Amelia', 'Keuangan & Akuntansi', 'Finance Specialist', 8500000.00, '2026-10-15'
        )
    ");
    $stmt->execute(['emp_id' => $nextEmpId]);
    $citraEmpId = $nextEmpId;
    echo "[OK] Citra Amelia inserted as employee_id: {$citraEmpId}.\n";
} else {
    $citraEmpId = (int) $chkCitra;
    echo "[OK] Citra Amelia already exists as employee_id: {$citraEmpId}.\n";
}

// Insert user for Citra if not exists
$chkUserCitra = $pdo->query("SELECT id FROM users WHERE username = 'citra_employee' OR email = 'citra.amelia@example.test'")->fetchColumn();
if (!$chkUserCitra) {
    $maxUserId = (int) $pdo->query("SELECT MAX(id) FROM users")->fetchColumn();
    $nextUserId = max(26, $maxUserId + 1);
    $stmtUser = $pdo->prepare("
        INSERT INTO users (id, user_id, name, username, email, password_hash, role, is_active, role_id, employee_id)
        VALUES (:id, :uid, 'Citra Amelia', 'citra_employee', 'citra.amelia@example.test', '$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy', 'EMPLOYEE', 1, 15, :emp_id)
    ");
    $stmtUser->execute(['id' => $nextUserId, 'uid' => $nextUserId, 'emp_id' => $citraEmpId]);
    echo "[OK] User citra_employee created.\n";
}

echo "Seed verification completed successfully.\n";
