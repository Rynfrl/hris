<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    sendJsonResponse(['status' => 'ok']);
}

inventoryRequireApiAccess();

try {
    $pdo = getDbConnection();

    // ======================================================================
    // 1. GET: Ambil daftar / detail supplier dari tabel MySQL `suppliers`
    // ======================================================================
    if ($method === 'GET') {
        // Ambil riwayat supplier (penambahan & penghapusan) beserta informasi petugas & rolenya
        if (isset($_GET['include_deleted']) && $_GET['include_deleted'] == '1') {
            $stmt = $pdo->prepare(
                "(SELECT 
                    CONCAT('del_', s.supplier_id) AS log_id,
                    s.supplier_id AS id,
                    'DELETE' AS action_type,
                    'Hapus Supplier' AS action_label,
                    COALESCE(s.original_code, s.supplier_code) AS code,
                    s.supplier_name AS name,
                    s.phone, s.email, s.address,
                    s.deleted_at AS action_date,
                    s.deleted_at,
                    COALESCE(s.deletion_reason, 'Dihapus melalui modul Purchasing') AS deletion_reason,
                    s.deletion_notes,
                    COALESCE(u.name, al_u.name, 'Petugas Divisi Purchasing') AS deleted_by_name,
                    COALESCE(u.role, al_u.role, 'Admin_Purchase') AS deleted_by_role
                 FROM suppliers s
                 LEFT JOIN users u ON u.user_id = s.deleted_by
                 LEFT JOIN (
                     SELECT entity_id, MAX(activity_id) AS max_act_id
                     FROM activity_logs
                     WHERE entity_type = 'supplier' AND action = 'DELETE'
                     GROUP BY entity_id
                 ) al_max ON al_max.entity_id = s.supplier_id
                 LEFT JOIN activity_logs al ON al.activity_id = al_max.max_act_id
                 LEFT JOIN users al_u ON al_u.user_id = al.user_id
                 WHERE s.deleted_at IS NOT NULL)

                 UNION ALL

                 (SELECT 
                    CONCAT('add_', s.supplier_id) AS log_id,
                    s.supplier_id AS id,
                    'CREATE' AS action_type,
                    'Tambah Supplier' AS action_label,
                    COALESCE(s.original_code, s.supplier_code) AS code,
                    s.supplier_name AS name,
                    s.phone, s.email, s.address,
                    s.created_at AS action_date,
                    s.created_at AS deleted_at,
                    COALESCE(NULLIF(TRIM(s.creation_reason), ''), 'Tidak ada alasan khusus') AS deletion_reason,
                    NULL AS deletion_notes,
                    COALESCE(u.name, al_u.name, 'Petugas Pengadaan / Gudang') AS deleted_by_name,
                    COALESCE(u.role, al_u.role, 'Admin_Warehouse') AS deleted_by_role
                 FROM suppliers s
                 LEFT JOIN users u ON u.user_id = s.created_by
                 LEFT JOIN (
                     SELECT entity_id, MAX(activity_id) AS max_act_id
                     FROM activity_logs
                     WHERE entity_type = 'supplier' AND action = 'CREATE'
                     GROUP BY entity_id
                 ) al_max ON al_max.entity_id = s.supplier_id
                 LEFT JOIN activity_logs al ON al.activity_id = al_max.max_act_id
                 LEFT JOIN users al_u ON al_u.user_id = al.user_id
                 WHERE s.created_at IS NOT NULL)

                 ORDER BY action_date DESC"
            );
            $stmt->execute();
            $deletedSuppliers = $stmt->fetchAll();

            sendJsonResponse([
                'status' => 'success',
                'count'  => count($deletedSuppliers),
                'data'   => $deletedSuppliers
            ]);
        }

        // Ambil detail satu supplier aktif berdasarkan ID
        if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare(
                "SELECT supplier_id AS id,
                        COALESCE(original_code, supplier_code) AS code,
                        supplier_name AS name,
                        phone, email, address, is_active AS active, created_at, updated_at
                 FROM suppliers
                 WHERE supplier_id = ? AND deleted_at IS NULL"
            );
            $stmt->execute([$id]);
            $supplier = $stmt->fetch();
            if (!$supplier) {
                sendJsonResponse(['status' => 'error', 'message' => 'Supplier tidak ditemukan.'], 404);
            }
            sendJsonResponse(['status' => 'success', 'data' => $supplier]);
        }

        $search = trim((string)($_GET['search'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));

        $conditions = ['s.deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            $conditions[] = '(s.supplier_code LIKE ? OR s.supplier_name LIKE ? OR s.phone LIKE ? OR s.email LIKE ? OR s.address LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }

        if ($status === 'aktif' || $status === '1') {
            $conditions[] = 's.is_active = 1';
        } elseif ($status === 'nonaktif' || $status === '0') {
            $conditions[] = 's.is_active = 0';
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        // Ambil data supplier aktif beserta jumlah riwayat transaksi stok masuk
        $stmt = $pdo->prepare(
            "SELECT s.supplier_id AS id,
                    COALESCE(s.original_code, s.supplier_code) AS code,
                    s.supplier_name AS name,
                    s.phone, s.email, s.address, s.is_active AS active, s.created_at, s.updated_at,
                    COUNT(DISTINCT sm.stock_movement_id) AS total_transactions
             FROM suppliers s
             LEFT JOIN stock_movements sm ON sm.supplier_id = s.supplier_id
             $where
             GROUP BY s.supplier_id
             ORDER BY s.supplier_id DESC"
        );
        $stmt->execute($params);
        $suppliers = $stmt->fetchAll();

        sendJsonResponse([
            'status' => 'success',
            'count'  => count($suppliers),
            'data'   => $suppliers
        ]);
    }

    // ======================================================================
    // 2. POST: Tambah Supplier Baru / Toggle Status
    // ======================================================================
    if ($method === 'POST') {
        inventoryRequireApiAccess(true);
        $body = inventoryRequestBody();
        $action = trim((string)($body['action'] ?? $_GET['action'] ?? ''));

        // Action Toggle Status
        if ($action === 'toggle_status') {
            $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) {
                sendJsonResponse(['status' => 'error', 'message' => 'ID supplier tidak valid.'], 400);
            }

            $checkStmt = $pdo->prepare('SELECT supplier_name, is_active FROM suppliers WHERE supplier_id = ? AND deleted_at IS NULL');
            $checkStmt->execute([$id]);
            $sup = $checkStmt->fetch();
            if (!$sup) {
                sendJsonResponse(['status' => 'error', 'message' => 'Supplier tidak ditemukan atau sudah dihapus.'], 404);
            }

            $newStatus = ($sup['is_active'] == 1) ? 0 : 1;
            $updateStmt = $pdo->prepare('UPDATE suppliers SET is_active = ?, updated_at = NOW() WHERE supplier_id = ?');
            $updateStmt->execute([$newStatus, $id]);

            $statusLabel = $newStatus == 1 ? 'diaktifkan' : 'dinonaktifkan';

            if (!empty($_SESSION['user_id'])) {
                try {
                    $logStmt = $pdo->prepare(
                        'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, summary, ip_address, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, NOW())'
                    );
                    $logStmt->execute([
                        (int)$_SESSION['user_id'],
                        'UPDATE',
                        'supplier',
                        $id,
                        "Status supplier {$sup['supplier_name']} {$statusLabel}",
                        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    ]);
                } catch (Throwable $e) {}
            }

            sendJsonResponse([
                'status'  => 'success',
                'message' => "Supplier '{$sup['supplier_name']}' berhasil {$statusLabel}.",
                'data'    => [
                    'id'     => $id,
                    'active' => $newStatus
                ]
            ]);
        }

        // Tambah Supplier Baru: Seluruh inputan wajib diisi
        $name = trim((string)($body['name'] ?? $body['supplier_name'] ?? ''));
        $code = strtoupper(trim((string)($body['code'] ?? $body['supplier_code'] ?? '')));
        $phone = trim((string)($body['phone'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $address = trim((string)($body['address'] ?? ''));

        if ($name === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Nama supplier wajib diisi.'], 400);
        }
        if (inventoryTextLength($name) > 150) {
            sendJsonResponse(['status' => 'error', 'message' => 'Nama supplier maksimal 150 karakter.'], 400);
        }
        if ($phone === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Nomor telepon supplier wajib diisi.'], 400);
        }
        if ($email === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Alamat email resmi supplier wajib diisi.'], 400);
        }
        if ($address === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Alamat kantor / operasional supplier wajib diisi.'], 400);
        }

        // Validasi Email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Format email supplier tidak valid (contoh: kontak@perusahaan.co.id).'], 400);
        }

        // Validasi Nomor Telepon
        $digitsOnly = preg_replace('/[^\d]/', '', $phone);
        if (strlen($digitsOnly) < 8 || strlen($digitsOnly) > 16 || !preg_match('/^[\+]?[(]?[0-9]{2,4}[)]?[-\s\.]?[0-9]{3,4}[-\s\.]?[0-9]{3,6}$/', $phone)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Format nomor telepon tidak valid. Masukkan 8-16 digit angka (contoh: 021-5551234 atau 081234567890).'], 400);
        }

        // Auto-generate kode jika tidak diisi user: format SUP-00X
        if ($code === '') {
            $maxStmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(COALESCE(original_code, supplier_code), 5) AS UNSIGNED)) FROM suppliers WHERE supplier_code LIKE 'SUP-%' OR original_code LIKE 'SUP-%'");
            $maxNum = (int)$maxStmt->fetchColumn();
            $nextNum = $maxNum > 0 ? $maxNum + 1 : 1;
            $code = sprintf('SUP-%03d', $nextNum);

            // Double check keunikan kode di supplier aktif
            $checkStmt = $pdo->prepare('SELECT 1 FROM suppliers WHERE supplier_code = ? AND deleted_at IS NULL');
            $checkStmt->execute([$code]);
            while ($checkStmt->fetchColumn()) {
                $nextNum++;
                $code = sprintf('SUP-%03d', $nextNum);
                $checkStmt->execute([$code]);
            }
        } else {
            // Validasi format kode supplier (alphanumeric & hyphens)
            if (!preg_match('/^[A-Z0-9\-]{3,30}$/', $code)) {
                sendJsonResponse(['status' => 'error', 'message' => 'Kode supplier harus berupa huruf kapital, angka, dan tanda hubung (-) 3 hingga 30 karakter (contoh: SUP-005).'], 400);
            }

            // Cek duplikasi kode hanya pada supplier aktif
            $dupStmt = $pdo->prepare('SELECT 1 FROM suppliers WHERE supplier_code = ? AND deleted_at IS NULL');
            $dupStmt->execute([$code]);
            if ($dupStmt->fetchColumn()) {
                sendJsonResponse(['status' => 'error', 'message' => "Kode supplier '{$code}' sudah digunakan oleh supplier aktif lain."], 409);
            }
        }

        $reason = trim((string)($body['reason'] ?? $body['creation_reason'] ?? ''));
        if ($reason === '') {
            $reason = 'Tidak ada alasan khusus';
        }

        $creatorId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $insertStmt = $pdo->prepare(
            'INSERT INTO suppliers (supplier_code, supplier_name, phone, email, address, is_active, created_by, creation_reason, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?, NOW(), NOW())'
        );
        $insertStmt->execute([
            $code,
            $name,
            $phone,
            $email,
            $address,
            $creatorId,
            $reason,
        ]);
        $newId = (int)$pdo->lastInsertId();

        // Log aktivitas jika user_id ada di sesi
        if (!empty($_SESSION['user_id'])) {
            try {
                $logStmt = $pdo->prepare(
                    'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, summary, ip_address, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())'
                );
                $logStmt->execute([
                    (int)$_SESSION['user_id'],
                    'CREATE',
                    'supplier',
                    $newId,
                    "Menambah supplier {$code} - {$name} dari modul Inventory",
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ]);
            } catch (Throwable $e) {}
        }

        sendJsonResponse([
            'status'  => 'success',
            'message' => "Data rekanan pemasok '{$name}' ({$code}) berhasil ditambahkan dan disinkronkan ke sistem.",
            'data'    => [
                'id'      => $newId,
                'code'    => $code,
                'name'    => $name,
                'phone'   => $phone,
                'email'   => $email,
                'address' => $address,
            ]
        ], 201);
    }

    // ======================================================================
    // 3. PUT: Perbarui Data Supplier
    // ======================================================================
    if ($method === 'PUT') {
        inventoryRequireApiAccess(true);
        $body = inventoryRequestBody();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        if ($id <= 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'ID supplier tidak valid.'], 400);
        }

        $checkExist = $pdo->prepare('SELECT supplier_id, supplier_code FROM suppliers WHERE supplier_id = ? AND deleted_at IS NULL');
        $checkExist->execute([$id]);
        $existingSup = $checkExist->fetch();
        if (!$existingSup) {
            sendJsonResponse(['status' => 'error', 'message' => 'Supplier tidak ditemukan.'], 404);
        }

        $name = trim((string)($body['name'] ?? $body['supplier_name'] ?? ''));
        $code = strtoupper(trim((string)($body['code'] ?? $body['supplier_code'] ?? '')));
        $phone = trim((string)($body['phone'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $address = trim((string)($body['address'] ?? ''));
        $active = isset($body['active']) ? (int)(bool)$body['active'] : 1;

        if ($name === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Nama supplier wajib diisi.'], 400);
        }
        if ($code === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Kode supplier wajib diisi.'], 400);
        }
        if ($phone === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Nomor telepon supplier wajib diisi.'], 400);
        }
        if ($email === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Alamat email resmi supplier wajib diisi.'], 400);
        }
        if ($address === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Alamat kantor / operasional supplier wajib diisi.'], 400);
        }

        if (!preg_match('/^[A-Z0-9\-]{3,30}$/', $code)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kode supplier harus berupa huruf kapital, angka, dan tanda hubung (-) 3 hingga 30 karakter.'], 400);
        }

        // Validasi Email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Format email supplier tidak valid.'], 400);
        }

        // Validasi Nomor Telepon
        $digitsOnly = preg_replace('/[^\d]/', '', $phone);
        if (strlen($digitsOnly) < 8 || strlen($digitsOnly) > 16 || !preg_match('/^[\+]?[(]?[0-9]{2,4}[)]?[-\s\.]?[0-9]{3,4}[-\s\.]?[0-9]{3,6}$/', $phone)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Format nomor telepon tidak valid. Masukkan 8-16 digit angka.'], 400);
        }

        // Cek duplikasi kode pada supplier aktif lain
        $dupStmt = $pdo->prepare('SELECT 1 FROM suppliers WHERE supplier_code = ? AND supplier_id != ? AND deleted_at IS NULL');
        $dupStmt->execute([$code, $id]);
        if ($dupStmt->fetchColumn()) {
            sendJsonResponse(['status' => 'error', 'message' => "Kode supplier '{$code}' sudah digunakan oleh supplier lain."], 409);
        }

        $updateStmt = $pdo->prepare(
            'UPDATE suppliers
             SET supplier_code = ?, supplier_name = ?, phone = ?, email = ?, address = ?, is_active = ?, updated_at = NOW()
             WHERE supplier_id = ?'
        );
        $updateStmt->execute([
            $code,
            $name,
            $phone,
            $email,
            $address,
            $active,
            $id
        ]);

        sendJsonResponse([
            'status'  => 'success',
            'message' => "Data rekanan pemasok '{$name}' berhasil diperbarui.",
        ]);
    }

    // ======================================================================
    // 4. DELETE: Hapus Supplier dengan Wajib Alasan (Soft Delete & Bebaskan Kode)
    // ======================================================================
    if ($method === 'DELETE') {
        inventoryRequireApiAccess(true);
        $id = (int)($_GET['id'] ?? 0);
        $body = inventoryRequestBody();
        if ($id <= 0) {
            $id = (int)($body['id'] ?? 0);
        }
        if ($id <= 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'ID supplier tidak valid.'], 400);
        }

        $reason = trim((string)($body['reason'] ?? $_GET['reason'] ?? ''));
        if ($reason === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Alasan penghapusan supplier wajib diisi agar tercatat pada audit trail.'], 422);
        }
        if (inventoryTextLength($reason) < 4) {
            sendJsonResponse(['status' => 'error', 'message' => 'Alasan penghapusan minimal 4 karakter.'], 422);
        }

        $checkStmt = $pdo->prepare('SELECT supplier_code, supplier_name FROM suppliers WHERE supplier_id = ? AND deleted_at IS NULL');
        $checkStmt->execute([$id]);
        $row = $checkStmt->fetch();
        if (!$row) {
            sendJsonResponse(['status' => 'error', 'message' => 'Supplier tidak ditemukan atau sudah dihapus.'], 404);
        }

        $supplierCode = $row['supplier_code'];
        $supplierName = $row['supplier_name'];

        // Bebaskan kode unik agar bisa dipakai kembali untuk supplier baru
        $archivedCode = $supplierCode . '_DEL_' . $id . '_' . time();
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

        $delStmt = $pdo->prepare(
            'UPDATE suppliers
             SET deleted_at = NOW(),
                 is_active = 0,
                 deleted_by = ?,
                 deletion_reason = ?,
                 original_code = ?,
                 supplier_code = ?
             WHERE supplier_id = ?'
        );
        $delStmt->execute([
            $userId,
            $reason,
            $supplierCode,
            $archivedCode,
            $id
        ]);

        // Log aktivitas
        if ($userId) {
            try {
                $logStmt = $pdo->prepare(
                    'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, summary, ip_address, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())'
                );
                $logStmt->execute([
                    $userId,
                    'DELETE',
                    'supplier',
                    $id,
                    "Menghapus supplier {$supplierCode} - {$supplierName}. Alasan: {$reason}",
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ]);
            } catch (Throwable $e) {}
        }

        sendJsonResponse([
            'status'  => 'success',
            'message' => "Supplier '{$supplierName}' berhasil dihapus dan tercatat dalam buku audit.",
        ]);
    }

    sendJsonResponse(['status' => 'error', 'message' => 'Metode request tidak didukung.'], 405);
} catch (Throwable $error) {
    inventoryJsonError($error, 'Managing suppliers');
}
