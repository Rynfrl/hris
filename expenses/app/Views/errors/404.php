<div class="mb-3 text-secondary">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
    </svg>
</div>
<h1 class="h3 fw-bold mb-2">404 — Halaman Tidak Ditemukan</h1>
<p class="text-muted mb-4"><?= \App\Core\View::e($message ?? 'Halaman yang Anda tuju tidak ditemukan atau telah dipindahkan.') ?></p>
<a href="<?= \App\Core\Url::to('/dashboard') ?>" class="btn btn-primary px-4">Kembali ke Dashboard</a>
