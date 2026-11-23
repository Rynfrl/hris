<div class="mb-3 text-warning">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
    </svg>
</div>
<h1 class="h3 fw-bold mb-2">419 — Sesi Kedaluwarsa</h1>
<p class="text-muted mb-4"><?= \App\Core\View::e($message ?? 'Sesi keamanan (CSRF token) Anda telah kedaluwarsa. Silakan refresh halaman dan coba kembali.') ?></p>
<a href="javascript:history.back()" class="btn btn-outline-secondary me-2 px-4">Kembali</a>
<a href="<?= \App\Core\Url::to('/dashboard') ?>" class="btn btn-primary px-4">Dashboard</a>
