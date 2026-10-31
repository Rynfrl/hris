<div class="mb-3 text-secondary">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="15" y1="9" x2="9" y2="15"></line>
        <line x1="9" y1="9" x2="15" y2="15"></line>
    </svg>
</div>
<h1 class="h3 fw-bold mb-2">405 — Metode Tidak Diizinkan</h1>
<p class="text-muted mb-4"><?= \App\Core\View::e($message ?? 'Metode HTTP yang digunakan tidak diizinkan untuk rute ini.') ?></p>
<a href="/dashboard" class="btn btn-primary px-4">Kembali ke Dashboard</a>
