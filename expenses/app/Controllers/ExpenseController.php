<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\View;
use App\Core\Url;
use App\Repositories\ExpenseRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\EmployeeRepository;
use App\Services\ExpenseService;
use App\Services\AuthorizationService;
use Throwable;

class ExpenseController
{
    private ExpenseRepository $expenseRepo;
    private CategoryRepository $categoryRepo;
    private EmployeeRepository $employeeRepo;
    private ExpenseService $expenseService;
    private AuthorizationService $authService;

    public function __construct(
        ?ExpenseRepository $expenseRepo = null,
        ?CategoryRepository $categoryRepo = null,
        ?EmployeeRepository $employeeRepo = null,
        ?ExpenseService $expenseService = null,
        ?AuthorizationService $authService = null
    ) {
        $this->expenseRepo = $expenseRepo ?? new ExpenseRepository();
        $this->categoryRepo = $categoryRepo ?? new CategoryRepository();
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
        $this->expenseService = $expenseService ?? new ExpenseService();
        $this->authService = $authService ?? new AuthorizationService();
    }

    public function index(): string
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? 'EMPLOYEE';

        // Employee has no access to operational expense menus
        if ($role === 'EMPLOYEE' || (!in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE', 'MANAGER', 'FINANCE'], true) && !in_array('expenses.manage', $user['permissions'] ?? []))) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $filters = [
            'status' => $_GET['status'] ?? null,
            'category_id' => !empty($_GET['category_id']) ? (int) $_GET['category_id'] : null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'search' => $_GET['search'] ?? null,
            'sort' => $_GET['sort'] ?? 'expense_date',
            'order' => $_GET['order'] ?? 'DESC',
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $data = $this->expenseRepo->getExpenses($filters, $page, 20);
        $categories = $this->categoryRepo->getAll(true);

        return View::render('expenses/index', [
            'title' => ($role === 'MANAGER' ? 'Pengeluaran Tim' : 'Pengeluaran'),
            'expenses' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'filters' => $filters,
            'categories' => $categories,
            'user' => $user,
        ]);
    }

    public function create(): string
    {
        Flash::info('Pencatatan pengeluaran baru dilakukan melalui Laporan Gabungan. Silakan buat laporan baru.');
        Url::redirect('/reports/create');
    }

    public function store(): void
    {
        Flash::error('Pencatatan pengeluaran individual telah dinonaktifkan. Seluruh pengeluaran baru harus dibuat melalui Laporan Gabungan.');
        Url::redirect('/reports/create');
    }

    public function show(string|int $id): string
    {
        $expenseId = (int) $id;
        $expense = $this->expenseRepo->findById($expenseId);
        $user = Auth::user();

        if (!$expense) {
            http_response_code(404);
            return View::render('errors/404', ['title' => 'Pengeluaran Tidak Ditemukan'], 'layouts/error');
        }

        if (!$this->authService->canViewExpense($user, $expense)) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        return View::render('expenses/show', [
            'title' => 'Detail Pengeluaran ' . $expense['expense_number'],
            'expense' => $expense,
            'user' => $user,
            'canEdit' => $this->authService->canEditExpense($user, $expense),
            'canDelete' => $this->authService->canDeleteExpense($user, $expense),
        ]);
    }

    public function edit(string|int $id): string
    {
        $expenseId = (int) $id;
        $expense = $this->expenseRepo->findById($expenseId);
        $user = Auth::user();

        if (!$expense) {
            http_response_code(404);
            return View::render('errors/404', ['title' => 'Pengeluaran Tidak Ditemukan'], 'layouts/error');
        }

        if (!$this->authService->canEditExpense($user, $expense)) {
            Flash::error('Anda tidak berhak mengedit pengeluaran ini.');
            Url::redirect('/expenses/' . $expenseId);
        }

        $categories = $this->categoryRepo->getAll(true);
        $employee = !empty($expense['employee_id']) ? $this->employeeRepo->findById((int) $expense['employee_id']) : null;
        $bankAccount = $employee['bank_account'] ?? null;

        $banks = [
            'BCA' => 'Bank Central Asia (BCA)',
            'MANDIRI' => 'Bank Mandiri',
            'BNI' => 'Bank Negara Indonesia (BNI)',
            'BRI' => 'Bank Rakyat Indonesia (BRI)',
            'BSI' => 'Bank Syariah Indonesia (BSI)',
            'CIMB' => 'CIMB Niaga',
            'PERMATA' => 'Bank Permata',
            'DANAMON' => 'Bank Danamon',
            'JAGO' => 'Bank Jago',
            'BTPN' => 'Bank BTPN / Jenius',
            'OTHER' => 'Bank Lainnya',
        ];

        return View::render('expenses/edit', [
            'title' => 'Edit Pengeluaran ' . $expense['expense_number'],
            'expense' => $expense,
            'categories' => $categories,
            'user' => $user,
            'banks' => $banks,
            'bankAccount' => $bankAccount,
        ]);
    }

    public function update(string|int $id): void
    {
        $expenseId = (int) $id;
        $expense = $this->expenseRepo->findById($expenseId);
        $user = Auth::user();

        if (!$expense || !$this->authService->canEditExpense($user, $expense)) {
            Flash::error('Anda tidak berhak mengedit pengeluaran ini.');
            Url::redirect('/expenses');
        }

        try {
            $this->expenseService->updateExpense($expenseId, $_POST, $user);
            Flash::success('Pengeluaran draft berhasil diperbarui.');
            Url::redirect('/expenses/' . $expenseId);
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
            Url::redirect('/expenses/' . $expenseId . '/edit');
        }
    }

    public function delete(string|int $id): void
    {
        $expenseId = (int) $id;
        $expense = $this->expenseRepo->findById($expenseId);
        $user = Auth::user();

        if (!$expense || !$this->authService->canDeleteExpense($user, $expense)) {
            Flash::error('Pengeluaran tidak dapat dihapus.');
            Url::redirect('/expenses');
        }

        try {
            $this->expenseService->deleteExpense($expenseId, $user);
            Flash::success('Pengeluaran draft berhasil dihapus.');
            Url::redirect('/expenses');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
            Url::redirect('/expenses/' . $expenseId);
        }
    }

    public function uploadReceipt(string|int $id): void
    {
        $expenseId = (int) $id;
        $expense = $this->expenseRepo->findById($expenseId);
        $user = Auth::user();

        if (!$expense || !$this->authService->canEditExpense($user, $expense)) {
            Flash::error('Anda tidak berhak mengunggah bukti pada pengeluaran ini.');
            Url::redirect('/expenses/' . $expenseId);
        }

        if (empty($_FILES['receipt'])) {
            Flash::error('Silakan pilih file bukti kuitansi.');
            Url::redirect('/expenses/' . $expenseId);
        }

        try {
            $this->expenseService->uploadReceipt($expenseId, $_FILES['receipt'], $user);
            Flash::success('Kuitansi berhasil diunggah.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/expenses/' . $expenseId);
    }

    public function downloadReceipt(string|int $id): void
    {
        $expenseId = (int) $id;
        $expense = $this->expenseRepo->findById($expenseId);
        $user = Auth::user();

        if (!$expense || empty($expense['receipt_stored_name'])) {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'Kuitansi Tidak Ditemukan'], 'layouts/error');
            exit;
        }

        if (!$this->authService->canViewReceipt($user, $expense)) {
            http_response_code(403);
            echo View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
            exit;
        }

        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $storageDir = $appConfig['receipts_path'] ?? (dirname(__DIR__, 2) . '/storage/receipts');
        $filePath = $storageDir . '/' . basename($expense['receipt_stored_name']);

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'File Kuitansi Tidak Ada di Disk'], 'layouts/error');
            exit;
        }

        $mime = $expense['receipt_mime'] ?: 'application/octet-stream';
        $origName = $expense['receipt_original_name'] ?: 'receipt';
        $size = filesize($filePath);

        // Security headers
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $size);
        header('X-Content-Type-Options: nosniff');
        header(sprintf('Content-Disposition: inline; filename="%s"', addslashes($origName)));

        readfile($filePath);
        exit;
    }

    public function removeReceipt(string|int $id): void
    {
        $expenseId = (int) $id;
        $expense = $this->expenseRepo->findById($expenseId);
        $user = Auth::user();

        if (!$expense || !$this->authService->canEditExpense($user, $expense)) {
            Flash::error('Anda tidak berhak menghapus kuitansi ini.');
            Url::redirect('/expenses/' . $expenseId);
        }

        try {
            $this->expenseService->removeReceipt($expenseId, $user);
            Flash::success('Kuitansi berhasil dihapus.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/expenses/' . $expenseId);
    }
}
