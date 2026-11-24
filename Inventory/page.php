<?php

require_once dirname(__DIR__) . '/session.php';

$role = strtoupper(trim((string)($_SESSION['role'] ?? '')));
if (($_SESSION['login'] ?? false) !== true
    || !in_array($role, ['ADMIN_WAREHOUSE', 'WAREHOUSE_STAFF'], true)) {
    header('Location: ../index.php');
    exit();
}

$pageName = basename((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
if (!preg_match('/^[a-z0-9-]+\.html$/i', $pageName)) {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}

if ($role !== 'ADMIN_WAREHOUSE') {
    $lowerPage = strtolower($pageName);
    if ($lowerPage === 'product-form.html') {
        header('Location: products.html');
        exit();
    }
    if (in_array($lowerPage, ['reports.html', 'settings.html'], true)) {
        header('Location: dashboard.html');
        exit();
    }
}

$pageRoot = realpath(__DIR__ . '/pages');
$pagePath = realpath(__DIR__ . '/pages/' . $pageName);
if ($pageRoot === false || $pagePath === false || dirname($pagePath) !== $pageRoot) {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}

$content = file_get_contents($pagePath);
if ($content === false) {
    http_response_code(500);
    exit('Halaman gagal dimuat.');
}

$userName = trim((string)($_SESSION['name'] ?? '')) ?: 'Pengguna';
$userRole = trim((string)($_SESSION['role'] ?? ''));
$initials = implode('', array_map(
    static fn(string $part): string => strtoupper(substr($part, 0, 1)),
    array_slice(preg_split('/\s+/', $userName) ?: [], 0, 2)
));
$escapedUserName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
$escapedUserRole = htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8');
$content = preg_replace_callback(
    '~<(?<tag>span|div)\b(?<attributes>[^>]*\bclass="[^"]*\b(?<profileClass>user-name|user-role|header-user-name|header-user-avatar|user-avatar)\b[^"]*"[^>]*)>.*?</\k<tag>>~is',
    static function (array $matches) use ($escapedUserName, $escapedUserRole, $initials): string {
        $value = match ($matches['profileClass']) {
            'user-name', 'header-user-name' => $escapedUserName,
            'user-role' => $escapedUserRole,
            'header-user-avatar', 'user-avatar' => htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'),
        };

        return '<' . $matches['tag'] . $matches['attributes'] . '>' . $value
            . '</' . $matches['tag'] . '>';
    },
    $content
);

if ($role !== 'ADMIN_WAREHOUSE') {
    $content = preg_replace(
        '~<div\b[^>]*\bclass="[^"]*\bnav-section-title\b[^"]*"[^>]*>\s*Lainnya\s*</div>~i',
        '',
        $content
    );
    $content = preg_replace_callback(
        '~<a\b[^>]*>.*?</a>~is',
        static function (array $matches): string {
            $link = $matches[0];
            $isNavigationItem = preg_match('~\bclass="[^"]*\bnav-item\b[^"]*"~i', $link) === 1;
            $isAdminDestination = preg_match('~\bhref="(?:reports|settings)\.html"~i', $link) === 1;

            return $isNavigationItem && $isAdminDestination ? '' : $link;
        },
        $content
    );

    // Sembunyikan tombol aksi mutasi master data (Tambah Supplier / Kategori / Produk)
    $content = preg_replace('~<button\b[^>]*\bid="btn-add-supplier"[^>]*>.*?</button>~is', '', $content);
    $content = preg_replace('~<button\b[^>]*\bid="btn-add-category"[^>]*>.*?</button>~is', '', $content);
    $content = preg_replace('~<button\b[^>]*\bid="btn-add-product"[^>]*>.*?</button>~is', '', $content);
    $content = preg_replace('~<a\b[^>]*\bhref="[^"]*product-form\.html"[^>]*>.*?</a>~is', '', $content);
}

// Ambil preferensi tema dari database agar seluruh halaman langsung sinkron (server-side rendering)
$themePref = 'light';
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'theme_pref' LIMIT 1");
    $dbTheme = $stmt->fetchColumn();
    if ($dbTheme && in_array(strtolower(trim((string)$dbTheme)), ['light', 'dark', 'system'], true)) {
        $themePref = strtolower(trim((string)$dbTheme));
    }
} catch (Throwable $e) {
    // Fallback jika database belum siap
}

// Pasang atribut data-theme pada <html> secara konsisten
$content = preg_replace_callback(
    '~<html\b(?<attrs>[^>]*)>~i',
    static function (array $m) use ($themePref): string {
        $attrs = $m['attrs'];
        $attrs = preg_replace('/\s*data-theme="[^"]*"/i', '', $attrs);
        return '<html' . $attrs . ' data-theme="' . htmlspecialchars($themePref, ENT_QUOTES, 'UTF-8') . '">';
    },
    $content,
    1
);

// Jika dark mode, pastikan body juga memiliki class dark-theme
if ($themePref === 'dark') {
    $content = preg_replace_callback(
        '~<body\b(?<attrs>[^>]*)>~i',
        static function (array $m): string {
            $attrs = $m['attrs'];
            if (preg_match('/\bclass="([^"]*)"/i', $attrs, $cm)) {
                if (!str_contains($cm[1], 'dark-theme')) {
                    $newClass = trim($cm[1] . ' dark-theme');
                    $attrs = str_replace($cm[0], 'class="' . $newClass . '"', $attrs);
                }
            } else {
                $attrs .= ' class="dark-theme"';
            }
            return '<body' . $attrs . '>';
        },
        $content,
        1
    );
}

// Sinkronkan cache-buster CSS di semua halaman agar browser tidak menggunakan cache lama
$content = preg_replace_callback(
    '~href="\.\./assets/css/(?<file>[a-zA-Z0-9_-]+\.css)(?:\?[^"]*)?"~i',
    static fn(array $m): string => 'href="../assets/css/' . $m['file'] . '?v=20261123_dark2"',
    $content
);

// Bersihkan tag script access-control lama di template jika ada, lalu pasang versi terbaru dengan cache buster
$content = preg_replace('~<script\b[^>]*\bsrc="[^"]*access-control\.js[^"]*"[^>]*>\s*</script>~is', '', $content);
$cacheBuster = time();
$accessScript = '<script src="../assets/js/access-control.js?v=' . $cacheBuster . '"></script>';
$content = preg_replace('/<\/body>/i', $accessScript . '</body>', $content, 1);

// Cegah caching pada browser agar perubahan role & script langsung aktif seketika
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: text/html; charset=utf-8');
echo $content;
