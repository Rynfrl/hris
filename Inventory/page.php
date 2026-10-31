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

if ($role !== 'ADMIN_WAREHOUSE' && in_array(strtolower($pageName), ['categories.html', 'product-form.html', 'reports.html', 'settings.html'], true)) {
    http_response_code(403);
    exit('Halaman ini hanya dapat diakses Admin_Warehouse.');
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
            $isAdminDestination = preg_match('~\bhref="(?:categories|reports|settings)\.html"~i', $link) === 1;

            return $isNavigationItem && $isAdminDestination ? '' : $link;
        },
        $content
    );
}

$accessScript = '<script src="../assets/js/access-control.js" defer></script>';
$content = preg_replace('/<\/body>/i', $accessScript . '</body>', $content, 1);
header('Content-Type: text/html; charset=utf-8');
echo $content;
