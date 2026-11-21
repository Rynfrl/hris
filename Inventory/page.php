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

$erpNavHtml = '
      <div class="nav-section-title">Navigasi ERP</div>
      <a href="../../dashboard.php" class="nav-item" style="color: #38bdf8;">
        <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 8 8 12 12 16"></polyline><line x1="16" y1="12" x2="8" y2="12"></line></svg>
        <span>Kembali ke HRIS</span>
      </a>
      <a href="../../purchasing/dashboard.php" class="nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        <span>Modul Purchasing</span>
      </a>
      <a href="../../sales/index.php" class="nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        <span>Modul Sales</span>
      </a>
      <a href="../../expenses/public/dashboard" class="nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
        <span>Modul Expenses</span>
      </a>
';
$content = preg_replace('/<\/nav>/i', $erpNavHtml . '</nav>', $content, 1);

$accessScript = '<script src="../assets/js/access-control.js" defer></script>';
$content = preg_replace('/<\/body>/i', $accessScript . '</body>', $content, 1);
header('Content-Type: text/html; charset=utf-8');
echo $content;
