<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    private static string $viewsPath = '';

    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\');
    }

    public static function getViewsPath(): string
    {
        if (self::$viewsPath === '') {
            self::$viewsPath = dirname(__DIR__) . '/Views';
        }
        return self::$viewsPath;
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $base = self::getViewsPath();
        $viewFile = $base . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException(sprintf('View file not found: %s', $viewFile));
        }

        extract($data);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            $basePath = Url::basePath();
            return $basePath !== '' ? self::prefixLinks($content, $basePath) : $content;
        }

        $layoutFile = $base . '/' . str_replace('.', '/', $layout) . '.php';
        if (!file_exists($layoutFile)) {
            $basePath = Url::basePath();
            return $basePath !== '' ? self::prefixLinks($content, $basePath) : $content;
        }

        ob_start();
        require $layoutFile;
        $output = ob_get_clean();

        $basePath = Url::basePath();
        return $basePath !== '' ? self::prefixLinks($output, $basePath) : $output;
    }

    public static function prefixLinks(string $html, string $basePath): string
    {
        if ($basePath === '') {
            return $html;
        }

        $baseTrimmed = trim($basePath, '/');

        return (string) preg_replace_callback(
            '#\b(href|action|src)=([\"\'])\/(?!\/)([^\s\"\'>]*)#i',
            function (array $matches) use ($basePath, $baseTrimmed): string {
                $attr = $matches[1];
                $quote = $matches[2];
                $path = $matches[3];

                // 1. External ERP modules & root scripts
                if (preg_match('~^(dashboard\.php|purchasing|sales|Inventory|api|koneksi\.php|session\.php|index\.php|logout\.php|verif_login\.php)~i', $path)) {
                    return $matches[0];
                }

                // 2. Already prefixed with $baseTrimmed
                if (str_starts_with($path, $baseTrimmed . '/') || str_starts_with($path, $baseTrimmed . '?') || str_starts_with($path, $baseTrimmed . '#') || $path === $baseTrimmed) {
                    return $matches[0];
                }

                // 3. Already prefixed with 'expenses' specifically
                if (preg_match('~^expenses([/?#].*)?$~i', $path)) {
                    return $matches[0];
                }

                return sprintf('%s=%s%s/%s', $attr, $quote, $basePath, ltrim($path, '/'));
            },
            $html
        );
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function formatRupiah(mixed $amount): string
    {
        $num = (float) ($amount ?? 0);
        return 'Rp ' . number_format($num, 0, ',', '.');
    }

    public static function formatDate(?string $date, string $format = 'd M Y'): string
    {
        if (!$date) {
            return '-';
        }
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return '-';
        }

        // Bahasa Indonesia months
        $months = [
            'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
            'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agu',
            'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
        ];

        $formatted = date($format, $timestamp);
        return strtr($formatted, $months);
    }

    public static function statusLabel(string $status): string
    {
        return match (strtoupper($status)) {
            'DRAFT' => 'Draft',
            'SUBMITTED' => 'Menunggu persetujuan',
            'APPROVED' => 'Disetujui',
            'REFUSED' => 'Ditolak',
            'POSTED' => 'Dibukukan',
            'REIMBURSEMENT_PENDING' => 'Menunggu pembayaran',
            'PAID' => 'Dibayar',
            'CANCELLED' => 'Dibatalkan',
            'PENDING' => 'Menunggu',
            'PROCESSING' => 'Sedang diproses',
            'FAILED' => 'Gagal',
            default => $status,
        };
    }

    public static function statusBadge(string $status): string
    {
        $label = self::e(self::statusLabel($status));
        $cssClass = match (strtoupper($status)) {
            'DRAFT' => 'badge-draft',
            'SUBMITTED' => 'badge-submitted',
            'APPROVED' => 'badge-approved',
            'REFUSED' => 'badge-refused',
            'POSTED' => 'badge-posted',
            'REIMBURSEMENT_PENDING' => 'badge-reimbursement-pending',
            'PAID' => 'badge-paid',
            'CANCELLED' => 'badge-cancelled',
            'PENDING' => 'badge-warning',
            'PROCESSING' => 'badge-info',
            'FAILED' => 'badge-danger',
            default => 'badge-secondary',
        };

        return sprintf('<span class="status-badge %s">%s</span>', $cssClass, $label);
    }
}
