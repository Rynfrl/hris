<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    private const SESSION_KEY = '_auth_user';
    private static ?array $cachedUser = null;
    private static ?array $cachedPermissions = null;

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        Session::start();
        $userData = Session::get(self::SESSION_KEY);
        if (!$userData || !is_array($userData) || empty($userData['user_id'])) {
            if (!empty($_SESSION['login']) && !empty($_SESSION['user_id'])) {
                $userData = [
                    'user_id' => (int) $_SESSION['user_id'],
                    'username' => $_SESSION['username'] ?? '',
                    'email' => $_SESSION['email'] ?? '',
                    'role_name' => $_SESSION['role'] ?? '',
                ];
                Session::set(self::SESSION_KEY, $userData);
            } else {
                return null;
            }
        }

        // Validate user is still active in database
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('
                SELECT COALESCE(u.user_id, u.id) as user_id, u.username, u.email, u.employee_id, u.role_id, u.is_active,
                       r.role_name,
                       e.full_name as employee_name, e.department_id, e.manager_id, e.status as employee_status,
                       d.department_name, d.department_code
                FROM users u
                JOIN roles r ON (r.role_id = u.role_id OR r.id_role = u.role_id)
                LEFT JOIN employees e ON (e.employee_id = u.employee_id OR e.id_employee = u.employee_id)
                LEFT JOIN departments d ON d.department_id = e.department_id
                WHERE (u.user_id = :id1 OR u.id = :id2) AND u.deleted_at IS NULL
                LIMIT 1
            ');
            $stmt->execute([
                'id1' => $userData['user_id'],
                'id2' => $userData['user_id'],
            ]);
            $current = $stmt->fetch();

            if (!$current || (int) $current['is_active'] !== 1) {
                self::logout();
                return null;
            }

            self::$cachedUser = $current;
            return self::$cachedUser;
        } catch (\Throwable) {
            return $userData;
        }
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['user_id'] : null;
    }

    public static function employeeId(): ?int
    {
        $u = self::user();
        return !empty($u['employee_id']) ? (int) $u['employee_id'] : null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u['role_name'] ?? null;
    }

    public static function permissions(): array
    {
        if (self::$cachedPermissions !== null) {
            return self::$cachedPermissions;
        }

        $u = self::user();
        if (!$u || empty($u['role_id'])) {
            self::$cachedPermissions = [];
            return self::$cachedPermissions;
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('
                SELECT p.permission_key
                FROM permissions p
                JOIN role_permissions rp ON rp.permission_id = p.permission_id
                WHERE rp.role_id = :role_id
            ');
            $stmt->execute(['role_id' => $u['role_id']]);
            self::$cachedPermissions = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable) {
            self::$cachedPermissions = [];
        }

        return self::$cachedPermissions;
    }

    public static function hasPermission(string $permissionKey): bool
    {
        return in_array($permissionKey, self::permissions(), true);
    }

    public static function login(array $user): void
    {
        Session::start();
        Session::regenerate(true);
        Session::set(self::SESSION_KEY, [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'employee_id' => $user['employee_id'] ?? null,
            'role_id' => $user['role_id'],
            'role_name' => $user['role_name'] ?? null,
        ]);
        self::$cachedUser = null;
        self::$cachedPermissions = null;

        // Record last_login
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE user_id = :id');
            $stmt->execute(['id' => $user['user_id']]);
        } catch (\Throwable) {
            // Ignore DB error for login update
        }
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        self::$cachedPermissions = null;
        Session::destroy();
    }
}
