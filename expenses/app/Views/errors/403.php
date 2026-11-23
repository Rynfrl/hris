<div class="mb-3 text-warning">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
    </svg>
</div>
<h1 class="h3 fw-bold mb-2">403 — Akses Ditolak</h1>
<p class="text-muted mb-4"><?= \App\Core\View::e($message ?? 'Anda tidak memiliki hak akses atau wewenang untuk membuka halaman ini.') ?></p>
<a href="<?= \App\Core\Url::to('/dashboard') ?>" class="btn btn-primary px-4">Kembali ke Dashboard</a>
