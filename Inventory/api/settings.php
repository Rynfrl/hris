<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    sendJsonResponse(['status' => 'ok']);
}

// Hanya Admin Gudang dan Staf yang bisa melihat pengaturan; hanya Admin Gudang yang bisa mengubah
inventoryRequireApiAccess($method !== 'GET');

try {
    $pdo = getDbConnection();

    // Default konfigurasi sistem jika belum ada di database
    $defaultSettings = [
        'company_name'      => 'PT Sinar Nusantara Mandiri',
        'company_npwp'      => '01.234.567.8-901.000',
        'company_email'     => 'kontak@sinarnusantara.co.id',
        'company_phone'     => '021-5889012',
        'company_address'   => 'Gedung Sinar Tower Lt. 12, Jl. Jend. Sudirman Kav. 45, Jakarta Selatan',
        'currency'          => 'IDR',
        'date_format'       => 'd-m-y',
        'default_threshold' => '5',
        'theme_pref'        => 'light',
        'notify_low'        => '1'
    ];

    // =========================================================================
    // GET: Ambil Seluruh Konfigurasi Pengaturan
    // =========================================================================
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $saved = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $merged = array_merge($defaultSettings, $saved);

        sendJsonResponse([
            'status' => 'success',
            'data'   => $merged
        ]);
        exit;
    }

    // =========================================================================
    // POST / PUT: Simpan / Perbarui Konfigurasi Pengaturan
    // =========================================================================
    if ($method === 'POST') {
        $body = inventoryRequestBody();

        // Validasi input
        $companyName = trim((string)($body['company_name'] ?? ''));
        if ($companyName === '') {
            sendJsonResponse([
                'status'  => 'error',
                'message' => 'Nama perusahaan tidak boleh kosong.'
            ], 400);
        }

        $themePref = strtolower(trim((string)($body['theme_pref'] ?? 'light')));
        if (!in_array($themePref, ['light', 'dark', 'system'], true)) {
            $themePref = 'light';
        }

        $payload = [
            'company_name'    => $companyName,
            'company_npwp'    => trim((string)($body['company_npwp'] ?? '')),
            'company_email'   => trim((string)($body['company_email'] ?? '')),
            'company_phone'   => trim((string)($body['company_phone'] ?? '')),
            'company_address' => trim((string)($body['company_address'] ?? '')),
            'theme_pref'      => $themePref
        ];

        // Opsional jika dikirim
        if (isset($body['currency'])) {
            $payload['currency'] = strtolower(trim((string)$body['currency']));
        }
        if (isset($body['date_format'])) {
            $payload['date_format'] = trim((string)$body['date_format']);
        }
        if (isset($body['default_threshold'])) {
            $payload['default_threshold'] = (string)max(1, (int)$body['default_threshold']);
        }
        if (isset($body['notify_low'])) {
            $payload['notify_low'] = !empty($body['notify_low']) ? '1' : '0';
        }

        // Simpan setiap key ke tabel settings
        $upsertStmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, updated_at)
            VALUES (:k, :v, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");

        $pdo->beginTransaction();
        foreach ($payload as $key => $val) {
            $upsertStmt->execute([
                'k' => $key,
                'v' => (string)$val
            ]);
        }
        $pdo->commit();

        sendJsonResponse([
            'status'  => 'success',
            'message' => 'Pengaturan sistem berhasil disimpan.',
            'data'    => $payload
        ]);
        exit;
    }

    sendJsonResponse(['status' => 'error', 'message' => 'Metode HTTP tidak didukung.'], 405);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    inventoryJsonError($e, 'Pengaturan Sistem');
}
