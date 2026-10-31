<div class="mb-3 text-danger">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
    </svg>
</div>
<h1 class="h3 fw-bold mb-2">500 — Kesalahan Server Internal</h1>
<p class="text-muted mb-4"><?= \App\Core\View::e($message ?? 'Terjadi kesalahan pada sistem. Silakan hubungi administrator jika masalah berlanjut.') ?></p>
<a href="/dashboard" class="btn btn-primary px-4">Kembali ke Dashboard</a>
