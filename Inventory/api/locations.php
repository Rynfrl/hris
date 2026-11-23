<?php

require_once __DIR__ . '/bootstrap.php';
inventoryRequireApiAccess();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    $pdo = getDbConnection();

    if ($method === 'GET') {
        $stmt = $pdo->query(
            "SELECT l.location_id, l.location_code, l.location_name, l.area, l.is_active,
                    COUNT(p.product_id) AS product_count
             FROM storage_locations l
             LEFT JOIN products p ON p.location = l.location_name AND p.is_active = 1 AND p.deleted_at IS NULL
             WHERE l.is_active = 1
             GROUP BY l.location_id, l.location_code, l.location_name, l.area, l.is_active
             ORDER BY l.location_name ASC"
        );
        $locations = $stmt->fetchAll();

        foreach ($locations as &$loc) {
            $loc['location_id']   = (int)$loc['location_id'];
            $loc['product_count'] = (int)$loc['product_count'];
        }
        unset($loc);

        sendJsonResponse([
            'status' => 'success',
            'data'   => $locations
        ]);
    }

    if ($method === 'POST') {
        inventoryRequireApiAccess(true);
        $body = inventoryRequestBody();

        $code = strtoupper(trim((string)($body['location_code'] ?? '')));
        $name = trim((string)($body['location_name'] ?? ''));
        $area = trim((string)($body['area'] ?? ''));

        if ($code === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Kode lokasi wajib diisi (contoh: RAK-A1, GUD-01, LKR-B2).'], 422);
        }
        if (strlen($code) > 30) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kode lokasi maksimal 30 karakter.'], 422);
        }

        if ($name === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Nama lokasi wajib diisi (contoh: Rak Elektronik A1, Gudang Utama F1, Loker Transit).'], 422);
        }
        if (strlen($name) > 150) {
            sendJsonResponse(['status' => 'error', 'message' => 'Nama lokasi maksimal 150 karakter.'], 422);
        }

        // Cek duplikasi kode lokasi
        $checkCode = $pdo->prepare('SELECT COUNT(*) FROM storage_locations WHERE location_code = ?');
        $checkCode->execute([$code]);
        if ($checkCode->fetchColumn() > 0) {
            sendJsonResponse(['status' => 'error', 'message' => "Kode lokasi '{$code}' sudah digunakan. Harap gunakan kode lain."], 409);
        }

        // Cek duplikasi nama lokasi
        $checkName = $pdo->prepare('SELECT COUNT(*) FROM storage_locations WHERE location_name = ?');
        $checkName->execute([$name]);
        if ($checkName->fetchColumn() > 0) {
            sendJsonResponse(['status' => 'error', 'message' => "Nama lokasi '{$name}' sudah terdaftar di sistem."], 409);
        }

        $insertStmt = $pdo->prepare('
            INSERT INTO storage_locations (location_code, location_name, area, is_active)
            VALUES (?, ?, ?, 1)
        ');
        $insertStmt->execute([
            $code,
            $name,
            $area !== '' ? $area : null
        ]);

        $newId = (int)$pdo->lastInsertId();

        sendJsonResponse([
            'status'  => 'success',
            'message' => "Lokasi penyimpanan '{$name}' ({$code}) berhasil ditambahkan ke katalog gudang.",
            'data'    => [
                'location_id'   => $newId,
                'location_code' => $code,
                'location_name' => $name,
                'area'          => $area !== '' ? $area : null
            ]
        ], 201);
    }

    if ($method === 'PUT') {
        inventoryRequireApiAccess(true);
        $body = inventoryRequestBody();

        $id   = (int)($body['location_id'] ?? ($_GET['id'] ?? 0));
        $code = strtoupper(trim((string)($body['location_code'] ?? '')));
        $name = trim((string)($body['location_name'] ?? ''));
        $area = trim((string)($body['area'] ?? ''));

        if ($id <= 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'ID lokasi penyimpanan tidak valid.'], 400);
        }

        if ($code === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Kode lokasi wajib diisi.'], 422);
        }
        if (strlen($code) > 30) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kode lokasi maksimal 30 karakter.'], 422);
        }

        if ($name === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Nama lokasi wajib diisi.'], 422);
        }
        if (strlen($name) > 150) {
            sendJsonResponse(['status' => 'error', 'message' => 'Nama lokasi maksimal 150 karakter.'], 422);
        }

        // Cek keberadaan lokasi
        $oldStmt = $pdo->prepare('SELECT location_id, location_code, location_name FROM storage_locations WHERE location_id = ?');
        $oldStmt->execute([$id]);
        $oldLoc = $oldStmt->fetch();
        if (!$oldLoc) {
            sendJsonResponse(['status' => 'error', 'message' => 'Lokasi penyimpanan tidak ditemukan.'], 404);
        }

        // Cek duplikasi kode lokasi pada ID lain
        $checkCode = $pdo->prepare('SELECT COUNT(*) FROM storage_locations WHERE location_code = ? AND location_id <> ?');
        $checkCode->execute([$code, $id]);
        if ($checkCode->fetchColumn() > 0) {
            sendJsonResponse(['status' => 'error', 'message' => "Kode lokasi '{$code}' sudah digunakan oleh lokasi lain."], 409);
        }

        // Cek duplikasi nama lokasi pada ID lain
        $checkName = $pdo->prepare('SELECT COUNT(*) FROM storage_locations WHERE location_name = ? AND location_id <> ?');
        $checkName->execute([$name, $id]);
        if ($checkName->fetchColumn() > 0) {
            sendJsonResponse(['status' => 'error', 'message' => "Nama lokasi '{$name}' sudah digunakan oleh lokasi lain."], 409);
        }

        $pdo->beginTransaction();

        $updateStmt = $pdo->prepare('
            UPDATE storage_locations
            SET location_code = ?, location_name = ?, area = ?
            WHERE location_id = ?
        ');
        $updateStmt->execute([
            $code,
            $name,
            $area !== '' ? $area : null,
            $id
        ]);

        // Jika nama lokasi berubah, sinkronkan juga produk yang menempati lokasi ini
        if ($oldLoc['location_name'] !== $name) {
            $upProd = $pdo->prepare('UPDATE products SET location = ? WHERE location = ?');
            $upProd->execute([$name, $oldLoc['location_name']]);
        }

        $pdo->commit();

        sendJsonResponse([
            'status'  => 'success',
            'message' => "Data lokasi penyimpanan '{$name}' ({$code}) berhasil diperbarui.",
            'data'    => [
                'location_id'   => $id,
                'location_code' => $code,
                'location_name' => $name,
                'area'          => $area !== '' ? $area : null
            ]
        ]);
    }

    if ($method === 'DELETE') {
        inventoryRequireApiAccess(true);
        $body = inventoryRequestBody();
        $id = (int)($body['location_id'] ?? ($body['id'] ?? ($_GET['id'] ?? 0)));

        if ($id <= 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'ID lokasi penyimpanan tidak valid.'], 400);
        }

        // Cek data lokasi
        $locStmt = $pdo->prepare('SELECT location_id, location_code, location_name FROM storage_locations WHERE location_id = ?');
        $locStmt->execute([$id]);
        $loc = $locStmt->fetch();

        if (!$loc) {
            sendJsonResponse(['status' => 'error', 'message' => 'Lokasi penyimpanan tidak ditemukan atau sudah dihapus.'], 404);
        }

        // Hitung jumlah produk aktif yang masih menempati lokasi ini
        $prodCountStmt = $pdo->prepare('
            SELECT COUNT(*) 
            FROM products 
            WHERE location = ? AND is_active = 1 AND deleted_at IS NULL
        ');
        $prodCountStmt->execute([$loc['location_name']]);
        $prodCount = (int)$prodCountStmt->fetchColumn();

        if ($prodCount > 0) {
            sendJsonResponse([
                'status'  => 'error',
                'message' => sprintf(
                    "Lokasi penyimpanan '%s (%s)' tidak dapat dihapus karena masih menampung %d produk aktif di gudang. Harap pindahkan produk-produk tersebut ke lokasi lain terlebih dahulu.",
                    $loc['location_name'],
                    $loc['location_code'],
                    $prodCount
                ),
                'product_count' => $prodCount
            ], 409);
        }

        // Hapus lokasi dari tabel master
        $delStmt = $pdo->prepare('DELETE FROM storage_locations WHERE location_id = ?');
        $delStmt->execute([$id]);

        sendJsonResponse([
            'status'  => 'success',
            'message' => sprintf("Lokasi penyimpanan '%s (%s)' berhasil dihapus dari master data.", $loc['location_name'], $loc['location_code'])
        ]);
    }

    sendJsonResponse(['status' => 'error', 'message' => 'Metode request tidak didukung.'], 405);

} catch (Throwable $error) {
    inventoryJsonError($error, 'Storage locations operation');
}
