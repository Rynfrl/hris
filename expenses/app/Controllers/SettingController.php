<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\View;
use App\Core\Url;
use App\Repositories\UserRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\AuditRepository;
use Throwable;

class SettingController
{
    private UserRepository $userRepo;
    private CategoryRepository $categoryRepo;
    private DepartmentRepository $departmentRepo;
    private EmployeeRepository $employeeRepo;
    private AuditRepository $auditRepo;

    public function __construct(
        ?UserRepository $userRepo = null,
        ?CategoryRepository $categoryRepo = null,
        ?DepartmentRepository $departmentRepo = null,
        ?EmployeeRepository $employeeRepo = null,
        ?AuditRepository $auditRepo = null
    ) {
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->categoryRepo = $categoryRepo ?? new CategoryRepository();
        $this->departmentRepo = $departmentRepo ?? new DepartmentRepository();
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
        $this->auditRepo = $auditRepo ?? new AuditRepository();
    }

    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if ($user['role_name'] !== 'ADMIN' && !Auth::hasPermission('settings.manage')) {
            http_response_code(403);
            echo View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
            exit;
        }
    }

    // --- USERS ---
    public function users(): string
    {
        $this->ensureAdmin();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = ['search' => $_GET['search'] ?? null];
        $data = $this->userRepo->getAll($filters, $page, 20);
        $roles = $this->userRepo->getRoles();
        $employees = $this->employeeRepo->getAll();

        return View::render('settings/users', [
            'title' => 'Manajemen Pengguna',
            'users' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'roles' => $roles,
            'employees' => $employees,
            'filters' => $filters,
            'user' => Auth::user(),
        ]);
    }

    public function createUser(): void
    {
        $this->ensureAdmin();
        try {
            $username = trim($_POST['username'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $employeeId = !empty($_POST['employee_id']) ? (int) $_POST['employee_id'] : null;

            if ($username === '' || $email === '' || $password === '' || $roleId <= 0) {
                throw new \InvalidArgumentException('Username, email, kata sandi, dan role wajib diisi.');
            }

            // Check duplicate
            $exist = $this->userRepo->findByUsernameOrEmail($email) ?: $this->userRepo->findByUsernameOrEmail($username);
            if ($exist) {
                throw new \InvalidArgumentException('Username atau email sudah digunakan oleh pengguna lain.');
            }

            $userId = $this->userRepo->create([
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role_id' => $roleId,
                'employee_id' => $employeeId,
                'is_active' => (int) ($_POST['is_active'] ?? 1),
            ]);

            $this->auditRepo->logActivity(Auth::id(), 'USER_CREATED', 'users', $userId, 'Membuat pengguna baru: ' . $username);
            Flash::success('Pengguna ' . $username . ' berhasil dibuat.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/settings/users');
    }

    public function updateUser(string|int $id): void
    {
        $this->ensureAdmin();
        $userId = (int) $id;
        try {
            $data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => strtolower(trim($_POST['email'] ?? '')),
                'role_id' => (int) ($_POST['role_id'] ?? 0),
                'employee_id' => !empty($_POST['employee_id']) ? (int) $_POST['employee_id'] : null,
                'is_active' => (int) ($_POST['is_active'] ?? 1),
            ];

            if (!empty($_POST['password'])) {
                $data['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $this->userRepo->update($userId, $data);
            $this->auditRepo->logActivity(Auth::id(), 'USER_UPDATED', 'users', $userId, 'Memperbarui pengguna ID: ' . $userId);
            Flash::success('Data pengguna berhasil diperbarui.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/settings/users');
    }

    // --- CATEGORIES ---
    public function categories(): string
    {
        $this->ensureAdmin();
        $categories = $this->categoryRepo->getAll(false);

        return View::render('settings/categories', [
            'title' => 'Kategori Pengeluaran',
            'categories' => $categories,
            'user' => Auth::user(),
        ]);
    }

    public function createCategory(): void
    {
        $this->ensureAdmin();
        try {
            $code = strtoupper(trim($_POST['category_code'] ?? ''));
            $name = trim($_POST['category_name'] ?? '');

            if ($code === '' || $name === '') {
                throw new \InvalidArgumentException('Kode dan nama kategori wajib diisi.');
            }

            $catId = $this->categoryRepo->create([
                'category_code' => $code,
                'category_name' => $name,
                'description' => $_POST['description'] ?? '',
                'default_amount' => !empty($_POST['default_amount']) ? (float) $_POST['default_amount'] : null,
                'is_fixed_price' => (int) ($_POST['is_fixed_price'] ?? 0),
                'is_active' => (int) ($_POST['is_active'] ?? 1),
            ]);

            $this->auditRepo->logActivity(Auth::id(), 'CATEGORY_CREATED', 'expense_categories', $catId, 'Membuat kategori: ' . $code);
            Flash::success('Kategori pengeluaran berhasil ditambahkan.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/settings/categories');
    }

    public function updateCategory(string|int $id): void
    {
        $this->ensureAdmin();
        $catId = (int) $id;
        try {
            $this->categoryRepo->update($catId, [
                'category_code' => strtoupper(trim($_POST['category_code'] ?? '')),
                'category_name' => trim($_POST['category_name'] ?? ''),
                'description' => $_POST['description'] ?? '',
                'default_amount' => !empty($_POST['default_amount']) ? (float) $_POST['default_amount'] : null,
                'is_fixed_price' => (int) ($_POST['is_fixed_price'] ?? 0),
                'is_active' => (int) ($_POST['is_active'] ?? 1),
            ]);

            $this->auditRepo->logActivity(Auth::id(), 'CATEGORY_UPDATED', 'expense_categories', $catId, 'Memperbarui kategori ID: ' . $catId);
            Flash::success('Kategori pengeluaran berhasil diperbarui.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/settings/categories');
    }

    // --- DEPARTMENTS ---
    public function departments(): string
    {
        $this->ensureAdmin();
        $departments = $this->departmentRepo->getAll();
        $employees = $this->employeeRepo->getAll();

        return View::render('settings/departments', [
            'title' => 'Manajemen Departemen',
            'departments' => $departments,
            'employees' => $employees,
            'user' => Auth::user(),
        ]);
    }

    public function createDepartment(): void
    {
        $this->ensureAdmin();
        try {
            $code = strtoupper(trim($_POST['department_code'] ?? ''));
            $name = trim($_POST['department_name'] ?? '');

            if ($code === '' || $name === '') {
                throw new \InvalidArgumentException('Kode dan nama departemen wajib diisi.');
            }

            $deptId = $this->departmentRepo->create([
                'department_code' => $code,
                'department_name' => $name,
                'manager_id' => !empty($_POST['manager_id']) ? (int) $_POST['manager_id'] : null,
                'description' => $_POST['description'] ?? '',
                'is_active' => (int) ($_POST['is_active'] ?? 1),
            ]);

            $this->auditRepo->logActivity(Auth::id(), 'DEPARTMENT_CREATED', 'departments', $deptId, 'Membuat departemen: ' . $code);
            Flash::success('Departemen berhasil ditambahkan.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/settings/departments');
    }

    public function updateDepartment(string|int $id): void
    {
        $this->ensureAdmin();
        $deptId = (int) $id;
        try {
            $this->departmentRepo->update($deptId, [
                'department_code' => strtoupper(trim($_POST['department_code'] ?? '')),
                'department_name' => trim($_POST['department_name'] ?? ''),
                'manager_id' => !empty($_POST['manager_id']) ? (int) $_POST['manager_id'] : null,
                'description' => $_POST['description'] ?? '',
                'is_active' => (int) ($_POST['is_active'] ?? 1),
            ]);

            $this->auditRepo->logActivity(Auth::id(), 'DEPARTMENT_UPDATED', 'departments', $deptId, 'Memperbarui departemen ID: ' . $deptId);
            Flash::success('Departemen berhasil diperbarui.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/settings/departments');
    }

    // --- AUDIT LOGS ---
    public function auditLogs(): string
    {
        $this->ensureAdmin();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $module = $_GET['module'] ?? null;
        $data = $this->auditRepo->getAuditLogs($page, 25, $module);

        return View::render('settings/audit_logs', [
            'title' => 'Audit Log Sistem',
            'logs' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'module' => $module,
            'user' => Auth::user(),
        ]);
    }
}
