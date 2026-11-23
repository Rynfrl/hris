<?php

require_once __DIR__ . '/bootstrap.php';
inventoryRequireApiAccess();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    sendJsonResponse(['status' => 'ok']);
}

try {
    $pdo = getDbConnection();

    // ======================================================================
    // 1. GET: Ambil daftar / detail kategori dari tabel MySQL `categories`
    // ======================================================================
    if ($method === 'GET') {
        // Ambil riwayat kategori (penambahan & penghapusan) beserta informasi petugas & rolenya
        if (isset($_GET['include_deleted']) && $_GET['include_deleted'] == '1') {
            $stmt = $pdo->prepare(
                "(SELECT 
                    CONCAT('del_', c.id) AS log_id,
                    c.id,
                    'DELETE' AS action_type,
                    'Hapus Kategori' AS action_label,
                    COALESCE(c.original_code, c.code) AS code,
                    c.name, c.description,
                    c.deleted_at AS action_date,
                    c.deleted_at,
                    COALESCE(c.deletion_reason, 'Dihapus dari sistem') AS deletion_reason,
                    COALESCE(u.name, al_u.name, 'Admin Gudang / Purchasing') AS deleted_by_name,
                    COALESCE(u.role, al_u.role, 'Admin_Warehouse') AS deleted_by_role
                 FROM categories c
                 LEFT JOIN users u ON u.user_id = c.deleted_by
                 LEFT JOIN (
                     SELECT entity_id, MAX(activity_id) AS max_act_id
                     FROM activity_logs
                     WHERE entity_type = 'category' AND action = 'DELETE'
                     GROUP BY entity_id
                 ) al_max ON al_max.entity_id = c.id
                 LEFT JOIN activity_logs al ON al.activity_id = al_max.max_act_id
                 LEFT JOIN users al_u ON al_u.user_id = al.user_id
                 WHERE c.deleted_at IS NOT NULL)

                 UNION ALL

                 (SELECT 
                    CONCAT('add_', c.id) AS log_id,
                    c.id,
                    'CREATE' AS action_type,
                    'Tambah Kategori' AS action_label,
                    COALESCE(c.original_code, c.code) AS code,
                    c.name, c.description,
                    c.created_at AS action_date,
                    c.created_at AS deleted_at,
                    COALESCE(NULLIF(TRIM(c.creation_reason), ''), 'Tidak ada alasan khusus') AS deletion_reason,
                    COALESCE(u.name, al_u.name, 'Petugas Gudang / Sistem') AS deleted_by_name,
                    COALESCE(u.role, al_u.role, 'Admin_Warehouse') AS deleted_by_role
                 FROM categories c
                 LEFT JOIN users u ON u.user_id = c.created_by
                 LEFT JOIN (
                     SELECT entity_id, MAX(activity_id) AS max_act_id
                     FROM activity_logs
                     WHERE entity_type = 'category' AND action = 'CREATE'
                     GROUP BY entity_id
                 ) al_max ON al_max.entity_id = c.id
                 LEFT JOIN activity_logs al ON al.activity_id = al_max.max_act_id
                 LEFT JOIN users al_u ON al_u.user_id = al.user_id
                 WHERE c.created_at IS NOT NULL)

                 ORDER BY action_date DESC"
            );
            $stmt->execute();
            $deletedCategories = $stmt->fetchAll();

            sendJsonResponse([
                'status' => 'success',
                'count'  => count($deletedCategories),
                'data'   => $deletedCategories
            ]);
        }

        // Ambil detail satu kategori aktif
        if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
            $catId = (int)$_GET['id'];
            $stmt = $pdo->prepare(
                "SELECT c.id, COALESCE(c.original_code, c.code) AS code, c.name, c.description, c.created_at,
                        COUNT(p.product_id) AS product_count
                 FROM categories c
                 LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1 AND p.deleted_at IS NULL
                 WHERE c.id = ? AND c.deleted_at IS NULL
                 GROUP BY c.id, c.code, c.original_code, c.name, c.description, c.created_at"
            );
            $stmt->execute([$catId]);
            $category = $stmt->fetch();
            if (!$category) {
                sendJsonResponse(['status' => 'error', 'message' => 'Kategori tidak ditemukan.'], 404);
            }

            // Ambil produk dalam kategori ini
            $prodStmt = $pdo->prepare(
                "SELECT p.product_id AS id, p.sku, p.product_name AS name, p.stock, p.unit, p.location, p.is_active
                 FROM products p
                 WHERE p.category_id = ? AND p.deleted_at IS NULL
                 ORDER BY p.product_name"
            );
            $prodStmt->execute([$catId]);
            $category['products'] = $prodStmt->fetchAll();
            $category['product_count'] = count($category['products']);

            sendJsonResponse(['status' => 'success', 'data' => $category]);
        }

        // Ambil semua kategori aktif
        $stmt = $pdo->query(
            "SELECT c.id, COALESCE(c.original_code, c.code) AS code, c.name, c.description, c.created_at,
                    COUNT(p.product_id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1 AND p.deleted_at IS NULL
             WHERE c.deleted_at IS NULL
             GROUP BY c.id, c.code, c.original_code, c.name, c.description, c.created_at
             ORDER BY c.id ASC"
        );
        $categories = $stmt->fetchAll();

        // Ambil daftar produk per kategori
        $productStmt = $pdo->query(
            "SELECT p.category_id, p.product_id, p.sku, p.product_name AS name, p.stock, p.unit, p.location
             FROM products p
             WHERE p.is_active = 1 AND p.deleted_at IS NULL
             ORDER BY p.product_name"
        );
        $allProducts = $productStmt->fetchAll();

        // Kelompokkan produk per category_id
        $productsByCategory = [];
        foreach ($allProducts as $prod) {
            $productsByCategory[$prod['category_id']][] = [
                'id'       => (int)$prod['product_id'],
                'sku'      => $prod['sku'],
                'name'     => $prod['name'],
                'stock'    => (float)$prod['stock'],
                'unit'     => $prod['unit'],
                'location' => $prod['location'] ?? '-',
            ];
        }

        // Gabungkan ke data kategori (kompatibel 100% dengan dashboard & modul lainnya)
        foreach ($categories as &$cat) {
            $cat['products'] = $productsByCategory[$cat['id']] ?? [];
            $cat['product_count'] = (int)$cat['product_count'];
        }
        unset($cat);

        sendJsonResponse(['status' => 'success', 'data' => $categories]);
    }

    // ======================================================================
    // 2. POST: Tambah Kategori Baru ke tabel `categories`
    // ======================================================================
    if ($method === 'POST') {
        inventoryRequireApiAccess(true);
        $body = inventoryRequestBody();
        $name = trim((string)($body['name'] ?? $body['category_name'] ?? ''));
        $code = strtoupper(trim((string)($body['code'] ?? $body['category_code'] ?? '')));
        $desc = trim((string)($body['description'] ?? ''));

        if ($name === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Nama kategori barang wajib diisi.'], 400);
        }
        if (inventoryTextLength($name) > 100) {
            sendJsonResponse(['status' => 'error', 'message' => 'Nama kategori maksimal 100 karakter.'], 400);
        }
        if ($desc === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Deskripsi kategori wajib diisi untuk standar taksonomi barang.'], 400);
        }

        // Auto-generate kode jika kosong: format CAT-XX
        if ($code === '') {
            $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 2));
            if (strlen($prefix) < 2) $prefix = 'KT';
            $code = 'CAT-' . $prefix;

            // Check unik di kategori aktif
            $dupStmt = $pdo->prepare('SELECT 1 FROM categories WHERE code = ? AND deleted_at IS NULL');
            $dupStmt->execute([$code]);
            if ($dupStmt->fetchColumn()) {
                $num = 1;
                while (true) {
                    $tryCode = sprintf('CAT-%s%d', $prefix, $num);
                    $dupStmt->execute([$tryCode]);
                    if (!$dupStmt->fetchColumn()) {
                        $code = $tryCode;
                        break;
                    }
                    $num++;
                }
            }
        } else {
            if (!preg_match('/^[A-Z0-9\-]{2,20}$/', $code)) {
                sendJsonResponse(['status' => 'error', 'message' => 'Kode kategori harus berupa 2-20 karakter huruf kapital, angka, atau tanda hubung (-) (contoh: CAT-EL).'], 400);
            }

            // Cek duplikasi kode hanya pada kategori aktif
            $dupStmt = $pdo->prepare('SELECT 1 FROM categories WHERE code = ? AND deleted_at IS NULL');
            $dupStmt->execute([$code]);
            if ($dupStmt->fetchColumn()) {
                sendJsonResponse(['status' => 'error', 'message' => "Kode kategori '{$code}' sudah digunakan."], 409);
            }
        }

        // Cek duplikasi nama kategori pada kategori aktif
        $dupNameStmt = $pdo->prepare('SELECT 1 FROM categories WHERE LOWER(name) = LOWER(?) AND deleted_at IS NULL');
        $dupNameStmt->execute([$name]);
        if ($dupNameStmt->fetchColumn()) {
            sendJsonResponse(['status' => 'error', 'message' => "Kategori dengan nama '{$name}' sudah ada."], 409);
        }

        $reason = trim((string)($body['reason'] ?? $body['creation_reason'] ?? ''));
        if ($reason === '') {
            $reason = 'Tidak ada alasan khusus';
        }

        $creatorId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $insertStmt = $pdo->prepare(
            'INSERT INTO categories (code, name, description, created_by, creation_reason, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $insertStmt->execute([$code, $name, $desc, $creatorId, $reason]);
        $newId = (int)$pdo->lastInsertId();

        // Log ke activity_logs jika ada session user
        if (!empty($_SESSION['user_id'])) {
            try {
                $logStmt = $pdo->prepare(
                    'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, summary, ip_address, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())'
                );
                $logStmt->execute([
                    (int)$_SESSION['user_id'],
                    'CREATE',
                    'category',
                    $newId,
                    "Menambah kategori barang {$code} - {$name}",
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ]);
            } catch (Throwable $e) {}
        }

        sendJsonResponse([
            'status'  => 'success',
            'message' => "Kategori '{$name}' ({$code}) berhasil ditambahkan ke katalog inventaris.",
            'data'    => [
                'id'          => $newId,
                'code'        => $code,
                'name'        => $name,
                'description' => $desc,
            ]
        ], 201);
    }

    // ======================================================================
    // 3. PUT: Perbarui Data Kategori
    // ======================================================================
    if ($method === 'PUT') {
        inventoryRequireApiAccess(true);
        $body = inventoryRequestBody();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        if ($id <= 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'ID kategori tidak valid.'], 400);
        }

        $checkExist = $pdo->prepare('SELECT id, code, name FROM categories WHERE id = ? AND deleted_at IS NULL');
        $checkExist->execute([$id]);
        $existing = $checkExist->fetch();
        if (!$existing) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kategori tidak ditemukan atau sudah dihapus.'], 404);
        }

        $name = trim((string)($body['name'] ?? $body['category_name'] ?? ''));
        $code = strtoupper(trim((string)($body['code'] ?? $body['category_code'] ?? '')));
        $desc = trim((string)($body['description'] ?? ''));

        if ($name === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Nama kategori wajib diisi.'], 400);
        }
        if ($code === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Kode kategori wajib diisi.'], 400);
        }
        if ($desc === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Deskripsi kategori wajib diisi.'], 400);
        }

        if (!preg_match('/^[A-Z0-9\-]{2,20}$/', $code)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kode kategori harus berupa 2-20 karakter huruf kapital, angka, atau tanda hubung (-).'], 400);
        }

        // Cek duplikasi kode pada ID aktif lain
        $dupStmt = $pdo->prepare('SELECT 1 FROM categories WHERE code = ? AND id != ? AND deleted_at IS NULL');
        $dupStmt->execute([$code, $id]);
        if ($dupStmt->fetchColumn()) {
            sendJsonResponse(['status' => 'error', 'message' => "Kode kategori '{$code}' sudah digunakan oleh kategori lain."], 409);
        }

        // Cek duplikasi nama pada ID aktif lain
        $dupNameStmt = $pdo->prepare('SELECT 1 FROM categories WHERE LOWER(name) = LOWER(?) AND id != ? AND deleted_at IS NULL');
        $dupNameStmt->execute([$name, $id]);
        if ($dupNameStmt->fetchColumn()) {
            sendJsonResponse(['status' => 'error', 'message' => "Kategori dengan nama '{$name}' sudah ada."], 409);
        }

        $updateStmt = $pdo->prepare(
            'UPDATE categories
             SET code = ?, name = ?, description = ?
             WHERE id = ?'
        );
        $updateStmt->execute([$code, $name, $desc, $id]);

        // Log ke activity_logs
        if (!empty($_SESSION['user_id'])) {
            try {
                $logStmt = $pdo->prepare(
                    'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, summary, ip_address, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())'
                );
                $logStmt->execute([
                    (int)$_SESSION['user_id'],
                    'UPDATE',
                    'category',
                    $id,
                    "Memperbarui kategori barang {$code} - {$name}",
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ]);
            } catch (Throwable $e) {}
        }

        sendJsonResponse([
            'status'  => 'success',
            'message' => "Data kategori '{$name}' berhasil diperbarui.",
        ]);
    }

    // ======================================================================
    // 4. DELETE: Hapus Kategori (Soft Delete, Wajib Alasan, Bebaskan Kode)
    // ======================================================================
    if ($method === 'DELETE') {
        inventoryRequireApiAccess(true);
        $id = (int)($_GET['id'] ?? 0);
        $body = inventoryRequestBody();
        if ($id <= 0) {
            $id = (int)($body['id'] ?? 0);
        }
        if ($id <= 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'ID kategori tidak valid.'], 400);
        }

        $reason = trim((string)($body['reason'] ?? $_GET['reason'] ?? ''));
        if ($reason === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Alasan penghapusan kategori wajib diisi untuk kebutuhan audit trail.'], 422);
        }
        if (inventoryTextLength($reason) < 4) {
            sendJsonResponse(['status' => 'error', 'message' => 'Alasan penghapusan minimal 4 karakter.'], 422);
        }

        $checkExist = $pdo->prepare('SELECT id, code, name FROM categories WHERE id = ? AND deleted_at IS NULL');
        $checkExist->execute([$id]);
        $category = $checkExist->fetch();
        if (!$category) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kategori tidak ditemukan atau sudah dihapus.'], 404);
        }

        // Proteksi dependensi produk: Cek apakah ada produk aktif dalam kategori ini
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ? AND deleted_at IS NULL');
        $countStmt->execute([$id]);
        $prodCount = (int)$countStmt->fetchColumn();

        if ($prodCount > 0) {
            sendJsonResponse([
                'status'  => 'error',
                'message' => "Kategori '{$category['name']}' tidak dapat dihapus karena masih memuat {$prodCount} produk aktif. Silakan kosongkan atau pindahkan produk ke kategori lain terlebih dahulu."
            ], 422);
        }

        // Bebaskan kode unik kategori agar bisa dipakai kembali
        $catCode = $category['code'];
        $catName = $category['name'];
        $archivedCode = $catCode . '_DEL_' . $id . '_' . time();
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

        $delStmt = $pdo->prepare(
            'UPDATE categories
             SET deleted_at = NOW(),
                 deleted_by = ?,
                 deletion_reason = ?,
                 original_code = ?,
                 code = ?
             WHERE id = ?'
        );
        $delStmt->execute([
            $userId,
            $reason,
            $catCode,
            $archivedCode,
            $id
        ]);

        // Log ke activity_logs
        if ($userId) {
            try {
                $logStmt = $pdo->prepare(
                    'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, summary, ip_address, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())'
                );
                $logStmt->execute([
                    $userId,
                    'DELETE',
                    'category',
                    $id,
                    "Menghapus kategori barang {$catCode} - {$catName}. Alasan: {$reason}",
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ]);
            } catch (Throwable $e) {}
        }

        sendJsonResponse([
            'status'  => 'success',
            'message' => "Kategori '{$catName}' berhasil dihapus dan tercatat dalam audit log.",
        ]);
    }

    sendJsonResponse(['status' => 'error', 'message' => 'Metode request tidak didukung.'], 405);
} catch (Throwable $error) {
    inventoryJsonError($error, 'Managing categories');
}
