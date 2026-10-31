<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Env;
use App\Core\Database;

Env::load(dirname(__DIR__) . '/.env');

echo "=== Expenses Demo Data Seeder ===\n";

try {
    $pdo = Database::pdo();

    // 1. Fetch Role IDs
    $rolesStmt = $pdo->query('SELECT role_name, role_id FROM roles');
    $roles = $rolesStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $requiredRoles = ['ADMIN', 'MANAGER', 'EMPLOYEE', 'FINANCE'];
    foreach ($requiredRoles as $r) {
        if (!isset($roles[$r])) {
            throw new RuntimeException("Role {$r} belum ada di database. Jalankan bin/seed_roles.php terlebih dahulu.");
        }
    }

    // 2. Ensure Demo Departments
    $departments = [
        ['DEPT-IT', 'Teknologi Informasi', 'Divisi IT & Sistem'],
        ['DEPT-FIN', 'Keuangan & Akuntansi', 'Divisi Finance & Tax'],
        ['DEPT-OPS', 'Operasional & Bisnis', 'Divisi Operasional'],
    ];

    $deptMap = [];
    foreach ($departments as [$code, $name, $desc]) {
        $stmt = $pdo->prepare('SELECT department_id FROM departments WHERE department_code = :code');
        $stmt->execute(['code' => $code]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            $ins = $pdo->prepare('INSERT INTO departments (department_code, department_name, description, is_active) VALUES (:c, :n, :d, 1)');
            $ins->execute(['c' => $code, 'n' => $name, 'd' => $desc]);
            $id = $pdo->lastInsertId();
            echo "[NEW] Department created: {$code} - {$name}\n";
        }
        $deptMap[$code] = (int) $id;
    }

    // 3. Ensure Demo Employees
    // Rina (Manager)
    $mgrStmt = $pdo->prepare('SELECT employee_id FROM employees WHERE employee_number = "EMP-0101"');
    $mgrStmt->execute();
    $rinaEmpId = $mgrStmt->fetchColumn();
    if (!$rinaEmpId) {
        $ins = $pdo->prepare('
            INSERT INTO employees (employee_number, full_name, email, department_id, position, bank_name, bank_account, status)
            VALUES ("EMP-0101", "Rina Manajer", "manager@example.test", :dept, "Engineering Manager", "BCA", "1234567890", "ACTIVE")
        ');
        $ins->execute(['dept' => $deptMap['DEPT-OPS']]);
        $rinaEmpId = (int) $pdo->lastInsertId();
        echo "[NEW] Employee created: EMP-0101 Rina Manajer\n";
    } else {
        $rinaEmpId = (int) $rinaEmpId;
    }

    // Subordinates: Budi & Sari
    $employeesToSeed = [
        ['EMP-0102', 'Budi Santoso', 'budi@example.test', $deptMap['DEPT-OPS'], $rinaEmpId, 'Senior Engineer', 'BCA', '2345678901'],
        ['EMP-0103', 'Sari Putri', 'sari@example.test', $deptMap['DEPT-OPS'], $rinaEmpId, 'Junior Analyst', 'Mandiri', '3456789012'],
        ['EMP-0104', 'Dimas Finance', 'finance@example.test', $deptMap['DEPT-FIN'], null, 'Finance Officer', 'BNI', '4567890123'],
        ['EMP-0105', 'Admin Expenses', 'admin@example.test', $deptMap['DEPT-IT'], null, 'System Administrator', 'BCA', '5678901234'],
    ];

    $empMap = ['EMP-0101' => $rinaEmpId];
    foreach ($employeesToSeed as [$num, $name, $email, $deptId, $mgrId, $pos, $bank, $acc]) {
        $stmt = $pdo->prepare('SELECT employee_id FROM employees WHERE employee_number = :num');
        $stmt->execute(['num' => $num]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            $ins = $pdo->prepare('
                INSERT INTO employees (employee_number, full_name, email, department_id, manager_id, position, bank_name, bank_account, status)
                VALUES (:num, :name, :email, :dept, :mgr, :pos, :bank, :acc, "ACTIVE")
            ');
            $ins->execute([
                'num' => $num,
                'name' => $name,
                'email' => $email,
                'dept' => $deptId,
                'mgr' => $mgrId,
                'pos' => $pos,
                'bank' => $bank,
                'acc' => $acc,
            ]);
            $id = $pdo->lastInsertId();
            echo "[NEW] Employee created: {$num} {$name}\n";
        }
        $empMap[$num] = (int) $id;
    }

    // 4. Ensure Demo Users
    $defaultPassHash = password_hash('Password123!', PASSWORD_DEFAULT);
    $usersToSeed = [
        ['admin_gte', 'admin@example.test', $roles['ADMIN'], $empMap['EMP-0105']],
        ['rina_manager', 'manager@example.test', $roles['MANAGER'], $empMap['EMP-0101']],
        ['budi_employee', 'budi@example.test', $roles['EMPLOYEE'], $empMap['EMP-0102']],
        ['sari_employee', 'sari@example.test', $roles['EMPLOYEE'], $empMap['EMP-0103']],
        ['dimas_finance', 'finance@example.test', $roles['FINANCE'], $empMap['EMP-0104']],
    ];

    $userMap = [];
    foreach ($usersToSeed as [$username, $email, $roleId, $empId]) {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = :u OR email = :e');
        $stmt->execute(['u' => $username, 'e' => $email]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            $ins = $pdo->prepare('
                INSERT INTO users (username, email, password_hash, role_id, employee_id, is_active)
                VALUES (:u, :e, :p, :r, :emp, 1)
            ');
            $ins->execute([
                'u' => $username,
                'e' => $email,
                'p' => $defaultPassHash,
                'r' => $roleId,
                'emp' => $empId,
            ]);
            $id = $pdo->lastInsertId();
            echo "[NEW] User created: {$username} ({$email}) with password 'Password123!'\n";
        } else {
            echo "[EXISTS] User: {$username} ({$email})\n";
        }
        $userMap[$username] = (int) $id;
    }

    // 5. Ensure Demo Categories
    $categories = [
        ['TRAVEL', 'Travel', 'Biaya perjalanan dinas luar kota'],
        ['MEAL', 'Meal', 'Biaya makan operasional'],
        ['TRANSPORT', 'Transport', 'Transportasi taksi/bensin/parkir'],
        ['OFFICE', 'Office Supplies', 'Kebutuhan ATK kantor'],
        ['OTHER', 'Other', 'Biaya operasional lainnya'],
    ];

    $catMap = [];
    foreach ($categories as [$code, $name, $desc]) {
        $stmt = $pdo->prepare('SELECT category_id FROM expense_categories WHERE category_code = :c');
        $stmt->execute(['c' => $code]);
        $catId = $stmt->fetchColumn();
        if (!$catId) {
            $ins = $pdo->prepare('INSERT INTO expense_categories (category_code, category_name, description, is_active) VALUES (:c, :n, :d, 1)');
            $ins->execute(['c' => $code, 'n' => $name, 'd' => $desc]);
            $catId = $pdo->lastInsertId();
        }
        $catMap[$code] = (int) $catId;
    }

    // 6. Ensure Demo Expenses and Reports with diverse workflow states
    // Helper function for creating expense safely
    $createExpense = function (string $num, int $empId, int $catId, string $date, string $desc, float $amount, string $status, int $userId, ?string $bankName = null) use ($pdo) {
        $chk = $pdo->prepare('SELECT expense_id FROM expenses WHERE expense_number = :n');
        $chk->execute(['n' => $num]);
        $id = $chk->fetchColumn();
        if (!$id) {
            $ins = $pdo->prepare('
                INSERT INTO expenses (
                    expense_number, employee_id, category_id, expense_date, description,
                    amount, currency, payment_method, bank_name, is_reimbursable, is_reinvoiceable, reinvoice_status,
                    status, created_by
                ) VALUES (
                    :num, :emp, :cat, :date, :desc,
                    :amt, "IDR", "BANK_TRANSFER", :bank, 1, 0, "NOT_INVOICED",
                    :st, :user
                )
            ');
            $ins->execute([
                'num' => $num,
                'emp' => $empId,
                'cat' => $catId,
                'date' => $date,
                'desc' => $desc,
                'amt' => $amount,
                'bank' => $bankName,
                'st' => $status,
                'user' => $userId,
            ]);
            $id = $pdo->lastInsertId();
            echo "  [NEW] Expense {$num}: {$desc} (Rp " . number_format($amount, 0) . ")\n";
        }
        return (int) $id;
    };

    // Helper function for creating report
    $createReport = function (string $num, int $empId, string $title, array $expenseIds, string $status, ?string $refusalReason = null) use ($pdo, $userMap) {
        $chk = $pdo->prepare('SELECT report_id FROM expense_reports WHERE report_number = :n');
        $chk->execute(['n' => $num]);
        $repId = $chk->fetchColumn();
        if (!$repId) {
            $ins = $pdo->prepare('
                INSERT INTO expense_reports (report_number, employee_id, title, total_amount, status, created_at)
                VALUES (:num, :emp, :title, 0.00, :st, NOW())
            ');
            $ins->execute([
                'num' => $num,
                'emp' => $empId,
                'title' => $title,
                'st' => $status,
            ]);
            $repId = (int) $pdo->lastInsertId();
            echo "[NEW] Report {$num}: {$title} [Status: {$status}]\n";

            // Attach items
            $total = 0.00;
            foreach ($expenseIds as $expId) {
                $itemIns = $pdo->prepare('INSERT IGNORE INTO expense_report_items (report_id, expense_id) VALUES (:r, :e)');
                $itemIns->execute(['r' => $repId, 'e' => $expId]);

                $expAmt = (float) $pdo->query("SELECT amount FROM expenses WHERE expense_id = {$expId}")->fetchColumn();
                $total += $expAmt;
            }

            // Sync total & timestamps
            $upd = $pdo->prepare('UPDATE expense_reports SET total_amount = :tot WHERE report_id = :r');
            $upd->execute(['tot' => $total, 'r' => $repId]);

            // Add approval record
            $appIns = $pdo->prepare('INSERT INTO expense_approvals (report_id, approver_id, action, comment) VALUES (:r, :app, :act, :com)');
            if ($status === 'SUBMITTED') {
                $updSub = $pdo->prepare('UPDATE expense_reports SET submitted_at = NOW() WHERE report_id = :r');
                $updSub->execute(['r' => $repId]);
                $appIns->execute(['r' => $repId, 'app' => $userMap['sari_employee'], 'act' => 'SUBMITTED', 'com' => 'Laporan diajukan.']);
            } elseif ($status === 'APPROVED' || $status === 'POSTED' || $status === 'REIMBURSEMENT_PENDING' || $status === 'PAID') {
                $updApp = $pdo->prepare('UPDATE expense_reports SET submitted_at = NOW(), approved_at = NOW() WHERE report_id = :r');
                $updApp->execute(['r' => $repId]);
                $appIns->execute(['r' => $repId, 'app' => $userMap['rina_manager'], 'act' => 'APPROVED', 'com' => 'Disetujui.']);
            } elseif ($status === 'REFUSED') {
                $updRef = $pdo->prepare('UPDATE expense_reports SET submitted_at = NOW(), refused_at = NOW(), rejection_reason = :re WHERE report_id = :r');
                $updRef->execute(['re' => $refusalReason, 'r' => $repId]);
                $appIns->execute(['r' => $repId, 'app' => $userMap['rina_manager'], 'act' => 'REFUSED', 'com' => $refusalReason]);
            }

            if ($status === 'POSTED' || $status === 'REIMBURSEMENT_PENDING' || $status === 'PAID') {
                $updPost = $pdo->prepare('UPDATE expense_reports SET posted_at = NOW() WHERE report_id = :r');
                $updPost->execute(['r' => $repId]);
            }

            if ($status === 'PAID') {
                $updPaid = $pdo->prepare('UPDATE expense_reports SET paid_at = NOW() WHERE report_id = :r');
                $updPaid->execute(['r' => $repId]);

                // Create reimbursement record
                $reimbIns = $pdo->prepare('
                    INSERT IGNORE INTO reimbursements (report_id, employee_id, amount, payment_method, payment_reference, payment_date, status, processed_by, processed_at)
                    VALUES (:r, :emp, :amt, "BANK_TRANSFER", "TRF-DEMO-20261011-01", CURDATE(), "PAID", :proc, NOW())
                ');
                $reimbIns->execute([
                    'r' => $repId,
                    'emp' => $empId,
                    'amt' => $total,
                    'proc' => $userMap['dimas_finance'],
                ]);
            }
        }
        return (int) $repId;
    };

    // Scenario A: Budi's Draft Report
    $expB1 = $createExpense('EXP-DEMO-001', $empMap['EMP-0102'], $catMap['MEAL'], date('Y-m-d', strtotime('-2 days')), 'Makan siang koordinasi tim', 150000.00, 'DRAFT', $userMap['budi_employee'], 'BCA');
    $expB2 = $createExpense('EXP-DEMO-002', $empMap['EMP-0102'], $catMap['TRANSPORT'], date('Y-m-d', strtotime('-1 days')), 'Taksi Bluebird ke klien', 85000.00, 'DRAFT', $userMap['budi_employee'], 'BCA');
    $repDraft = $createReport('GTE-DEMO-001', $empMap['EMP-0102'], 'Biaya Transport & Jamuan Budi', [$expB1, $expB2], 'DRAFT');

    // Scenario B: Sari's Submitted Report (Waiting for Manager Rina)
    $expS1 = $createExpense('EXP-DEMO-003', $empMap['EMP-0103'], $catMap['TRAVEL'], date('Y-m-d', strtotime('-4 days')), 'Tiket Kereta Argo Parahyangan PP', 450000.00, 'SUBMITTED', $userMap['sari_employee'], 'Mandiri');
    $expS2 = $createExpense('EXP-DEMO-004', $empMap['EMP-0103'], $catMap['MEAL'], date('Y-m-d', strtotime('-3 days')), 'Makan malam dinas Bandung', 175000.00, 'SUBMITTED', $userMap['sari_employee'], 'Mandiri');
    $repSub = $createReport('GTE-DEMO-002', $empMap['EMP-0103'], 'Kunjungan Lapangan Sari ke Bandung', [$expS1, $expS2], 'SUBMITTED');

    // Scenario C: Approved Report Waiting for Finance Posting
    $expA1 = $createExpense('EXP-DEMO-005', $empMap['EMP-0102'], $catMap['OFFICE'], date('Y-m-d', strtotime('-6 days')), 'Buku catatan & tinta printer workshop', 320000.00, 'APPROVED', $userMap['budi_employee'], 'BCA');
    $repApp = $createReport('GTE-DEMO-003', $empMap['EMP-0102'], 'Pengadaan Kebutuhan Workshop Internal', [$expA1], 'APPROVED');

    // Scenario D: Refused Report (Waiting for Revision)
    $expR1 = $createExpense('EXP-DEMO-006', $empMap['EMP-0103'], $catMap['OTHER'], date('Y-m-d', strtotime('-7 days')), 'Pembelian cinderamata tanpa kuitansi resmi', 600000.00, 'REFUSED', $userMap['sari_employee'], 'Mandiri');
    $repRef = $createReport('GTE-DEMO-004', $empMap['EMP-0103'], 'Cinderamata Klien Sari (Ditolak)', [$expR1], 'REFUSED', 'Kuitansi belum resmi dari toko/merchant. Mohon lampirkan faktur atau nota resmi.');

    // Scenario E: Paid Report (Fully Reimbursed)
    $expP1 = $createExpense('EXP-DEMO-007', $empMap['EMP-0102'], $catMap['TRAVEL'], date('Y-m-d', strtotime('-15 days')), 'Bensin dinas luar kota', 250000.00, 'PAID', $userMap['budi_employee'], 'BCA');
    $repPaid = $createReport('GTE-DEMO-005', $empMap['EMP-0102'], 'Operasional Transport Budi Bulan Lalu', [$expP1], 'PAID');

    echo "\n=== Seeding Finished Successfully! ===\n";
    echo "Akun Demo Siap Digunakan:\n";
    echo " 1. Admin   : admin@example.test   / Password123!\n";
    echo " 2. Manager : manager@example.test / Password123!\n";
    echo " 3. Karyawan: budi@example.test    / Password123!\n";
    echo " 4. Karyawan: sari@example.test    / Password123!\n";
    echo " 5. Finance : finance@example.test / Password123!\n";

} catch (Throwable $e) {
    echo "[ERROR] Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
