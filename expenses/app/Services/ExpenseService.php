<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\ExpenseRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\AuditRepository;
use InvalidArgumentException;
use RuntimeException;

class ExpenseService
{
    private ExpenseRepository $expenseRepo;
    private CategoryRepository $categoryRepo;
    private EmployeeRepository $employeeRepo;
    private AuditRepository $auditRepo;
    private string $storagePath;

    public function __construct(
        ?ExpenseRepository $expenseRepo = null,
        ?CategoryRepository $categoryRepo = null,
        ?AuditRepository $auditRepo = null,
        ?EmployeeRepository $employeeRepo = null
    ) {
        $this->expenseRepo = $expenseRepo ?? new ExpenseRepository();
        $this->categoryRepo = $categoryRepo ?? new CategoryRepository();
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
        $this->auditRepo = $auditRepo ?? new AuditRepository();

        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $this->storagePath = $appConfig['receipts_path'] ?? (dirname(__DIR__, 2) . '/storage/receipts');
    }

    public function createExpense(array $data, array $user): int
    {
        $this->validateExpenseData($data);

        $role = $user['role_name'] ?? '';
        $allowedRoles = ['ADMIN', 'ADMIN_EXPENSE', 'Admin_HR'];
        $userPerms = $user['permissions'] ?? [];
        if (!in_array($role, $allowedRoles, true) && !in_array('expenses.manage', $userPerms, true) && !in_array('expense.manage', $userPerms, true)) {
            throw new RuntimeException('Peran Anda tidak diizinkan untuk membuat pengeluaran.');
        }

        $role = $user['role_name'] ?? '';
        $isAdmin = in_array($role, ['ADMIN', 'ADMIN_EXPENSE', 'Admin_HR'], true);
        $empId = ($isAdmin && !empty($data['employee_id'])) ? (int) $data['employee_id'] : (int) ($user['employee_id'] ?? 0);

        if ($empId <= 0) {
            throw new InvalidArgumentException('Akun Anda tidak terhubung dengan data karyawan yang valid.');
        }

        $emp = $this->employeeRepo->findById($empId);
        if (!$emp || ($emp['status'] ?? '') !== 'ACTIVE') {
            throw new InvalidArgumentException('Karyawan tidak ditemukan atau tidak berstatus aktif.');
        }

        $userId = (int) ($user['user_id'] ?? 0);
        $deptId = !empty($emp['department_id']) ? (int) $emp['department_id'] : (!empty($user['department_id']) ? (int) $user['department_id'] : null);

        return Database::transaction(function () use ($data, $empId, $userId, $deptId, $emp) {
            $insertData = [
                'employee_id' => $empId,
                'category_id' => (int) $data['category_id'],
                'expense_date' => $data['expense_date'],
                'description' => trim($data['description']),
                'amount' => (float) $data['amount'],
                'payment_method' => $this->sanitizePaymentMethod($data['payment_method'] ?? 'CASH'),
                'bank_name' => !empty($data['bank_name']) ? trim((string) $data['bank_name']) : ($emp['bank_name'] ?? null),
                'merchant' => !empty($data['merchant']) ? trim($data['merchant']) : null,
                'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
                'department_id' => $deptId,
                'created_by' => $userId,
            ];

            $expenseId = $this->expenseRepo->create($insertData);

            $this->auditRepo->logActivity(
                $userId,
                'EXPENSE_CREATED',
                'expenses',
                $expenseId,
                sprintf('Membuat pengeluaran baru: %s (Rp %s)', $insertData['description'], number_format($insertData['amount'], 0, ',', '.'))
            );

            return $expenseId;
        });
    }

    public function updateExpense(int $id, array $data, array $user): bool
    {
        $this->validateExpenseData($data);

        $expense = $this->expenseRepo->findById($id);
        if (!$expense || $expense['status'] !== 'DRAFT') {
            throw new RuntimeException('Pengeluaran tidak dapat diubah karena status bukan DRAFT.');
        }

        if (!empty($expense['report_id'])) {
            if (!empty($expense['report_status']) && $expense['report_status'] !== 'DRAFT') {
                throw new RuntimeException('Pengeluaran tidak dapat diubah karena laporan pengeluaran sudah diajukan atau diproses.');
            }
        }

        $userId = (int) ($user['user_id'] ?? 0);

        return Database::transaction(function () use ($id, $data, $userId, $expense) {
            $updateData = [
                'category_id' => (int) $data['category_id'],
                'expense_date' => $data['expense_date'],
                'description' => trim($data['description']),
                'amount' => (float) $data['amount'],
                'payment_method' => $this->sanitizePaymentMethod($data['payment_method'] ?? 'CASH'),
                'bank_name' => !empty($data['bank_name']) ? trim((string) $data['bank_name']) : null,
                'merchant' => !empty($data['merchant']) ? trim($data['merchant']) : null,
                'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
                'updated_by' => $userId,
            ];

            $ok = $this->expenseRepo->update($id, $updateData);

            $this->auditRepo->logAudit(
                $userId,
                'UPDATE',
                'expenses',
                $id,
                ['description' => $expense['description'], 'amount' => $expense['amount']],
                ['description' => $updateData['description'], 'amount' => $updateData['amount']]
            );

            return $ok;
        });
    }

    public function deleteExpense(int $id, array $user): bool
    {
        $expense = $this->expenseRepo->findById($id);
        if (!$expense || $expense['status'] !== 'DRAFT') {
            throw new RuntimeException('Pengeluaran tidak dapat dihapus karena status bukan DRAFT.');
        }

        if (!empty($expense['report_id'])) {
            throw new RuntimeException('Pengeluaran tidak dapat dihapus karena masih terhubung dengan laporan. Lepaskan terlebih dahulu.');
        }

        $userId = (int) ($user['user_id'] ?? 0);

        return Database::transaction(function () use ($id, $expense, $userId) {
            // Remove physical receipt if exists
            if (!empty($expense['receipt_path'])) {
                $filePath = $this->storagePath . '/' . $expense['receipt_stored_name'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $ok = $this->expenseRepo->delete($id);

            $this->auditRepo->logActivity(
                $userId,
                'EXPENSE_DELETED',
                'expenses',
                $id,
                'Menghapus pengeluaran draft: ' . $expense['expense_number']
            );

            return $ok;
        });
    }

    public function uploadReceipt(int $expenseId, array $file, array $user): bool
    {
        $expense = $this->expenseRepo->findById($expenseId);
        if (!$expense || $expense['status'] !== 'DRAFT') {
            throw new RuntimeException('Kuitansi hanya dapat diunggah pada pengeluaran berstatus DRAFT.');
        }

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Terjadi kesalahan saat mengunggah file.');
        }

        // Validate file size (max 5 MB)
        $maxBytes = 5 * 1024 * 1024;
        if ($file['size'] > $maxBytes || $file['size'] <= 0) {
            throw new InvalidArgumentException('Ukuran file tidak boleh kosong atau melebihi 5 MB.');
        }

        // Verify MIME using finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        if (!array_key_exists($realMime, $allowedMimes)) {
            throw new InvalidArgumentException('Format file tidak didukung. Harap unggah file JPG, PNG, WEBP, atau PDF.');
        }

        // Generate safe random stored name
        $ext = $allowedMimes[$realMime];
        $storedName = sprintf('%s_%s.%s', bin2hex(random_bytes(16)), time(), $ext);

        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0755, true);
        }

        $destPath = $this->storagePath . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException('Gagal memindahkan file ke penyimpanan privat.');
        }

        // Old file cleanup after success
        $oldStoredName = $expense['receipt_stored_name'] ?? null;

        $receiptData = [
            'receipt_original_name' => basename($file['name']),
            'receipt_stored_name' => $storedName,
            'receipt_path' => 'receipts/' . $storedName,
            'receipt_mime' => $realMime,
            'receipt_size' => $file['size'],
        ];

        $ok = $this->expenseRepo->updateReceipt($expenseId, $receiptData);

        if ($ok && $oldStoredName && file_exists($this->storagePath . '/' . $oldStoredName)) {
            @unlink($this->storagePath . '/' . $oldStoredName);
        }

        $this->auditRepo->logActivity(
            (int) ($user['user_id'] ?? 0),
            'RECEIPT_UPLOADED',
            'expenses',
            $expenseId,
            'Mengunggah kuitansi baru: ' . $receiptData['receipt_original_name']
        );

        return $ok;
    }

    public function removeReceipt(int $expenseId, array $user): bool
    {
        $expense = $this->expenseRepo->findById($expenseId);
        if (!$expense || $expense['status'] !== 'DRAFT') {
            throw new RuntimeException('Kuitansi hanya dapat dihapus pada pengeluaran berstatus DRAFT.');
        }

        $storedName = $expense['receipt_stored_name'] ?? null;
        $ok = $this->expenseRepo->removeReceipt($expenseId);

        if ($ok && $storedName) {
            $filePath = $this->storagePath . '/' . $storedName;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $this->auditRepo->logActivity(
            (int) ($user['user_id'] ?? 0),
            'RECEIPT_REMOVED',
            'expenses',
            $expenseId,
            'Menghapus lampiran kuitansi'
        );

        return $ok;
    }

    private function validateExpenseData(array $data): void
    {
        $desc = trim($data['description'] ?? '');
        if (mb_strlen($desc) < 3 || mb_strlen($desc) > 255) {
            throw new InvalidArgumentException('Deskripsi pengeluaran wajib diisi antara 3 hingga 255 karakter.');
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0 || $amount > 1000000000) {
            throw new InvalidArgumentException('Nominal pengeluaran harus lebih besar dari 0 dan maksimal Rp 1.000.000.000.');
        }

        $date = trim($data['expense_date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException('Tanggal pengeluaran tidak valid (format: YYYY-MM-DD).');
        }

        // Date should not be in the future
        if ($date > date('Y-m-d')) {
            throw new InvalidArgumentException('Tanggal pengeluaran tidak boleh berada di masa depan.');
        }

        $categoryId = (int) ($data['category_id'] ?? 0);
        $category = $this->categoryRepo->findById($categoryId);
        if (!$category || (int) $category['is_active'] !== 1) {
            throw new InvalidArgumentException('Kategori yang dipilih tidak valid atau sudah tidak aktif.');
        }

        if (!empty($data['bank_name']) && mb_strlen(trim((string) $data['bank_name'])) > 100) {
            throw new InvalidArgumentException('Nama bank maksimal 100 karakter.');
        }
    }

    private function sanitizePaymentMethod(string $method): string
    {
        $allowed = ['CASH', 'BANK_TRANSFER', 'CREDIT_CARD', 'DEBIT_CARD', 'OTHER'];
        $clean = strtoupper(trim($method));
        return in_array($clean, $allowed, true) ? $clean : 'CASH';
    }
}
