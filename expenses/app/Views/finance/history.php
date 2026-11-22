<?php

declare(strict_types=1);

use App\Core\View;
use App\Core\Url;

$postings = $postings ?? [];
$filters = $filters ?? [];
$page = (int) ($page ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$total = (int) ($total ?? 0);
$perPage = (int) ($perPage ?? 20);
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Riwayat Pembukuan (Posting Finance)</h2>
        <p class="text-secondary small mb-0">Catatan seluruh laporan pengeluaran yang telah berhasil dibukukan ke buku besar akuntansi</p>
    </div>
    <div>
        <a href="<?= Url::to('/finance/posting') ?>" class="btn btn-outline-primary btn-sm">
            &larr; Kembali ke Antrean Pembukuan
        </a>
    </div>
</div>

<!-- Filters Toolbar -->
<div class="card card-gt mb-4">
    <div class="card-body p-3">
        <form action="<?= Url::to('/finance/history') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nomor jurnal/nomor laporan/judul/petugas..." value="<?= View::e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="start_date" class="form-control form-control-sm" title="Dari Tanggal Posting" value="<?= View::e($filters['start_date'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="end_date" class="form-control form-control-sm" title="Sampai Tanggal Posting" value="<?= View::e($filters['end_date'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10 / hal</option>
                    <option value="20" <?= $perPage === 20 ? 'selected' : '' ?>>20 / hal</option>
                    <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 / hal</option>
                    <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100 / hal</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="<?= Url::to('/finance/history') ?>" class="btn btn-sm btn-outline-secondary" title="Reset">✕</a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card card-gt">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title h6 fw-bold mb-0">Daftar Laporan yang Telah Dibukukan</h5>
        <span class="badge bg-secondary rounded-pill">Total: <?= number_format($total, 0, ',', '.') ?> Pembukuan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>No. Jurnal</th>
                    <th>No. Laporan</th>
                    <th>Judul Laporan</th>
                    <th>Tgl. Posting</th>
                    <th>Pembuat (Admin)</th>
                    <th>Disetujui Oleh</th>
                    <th>Total Nominal</th>
                    <th>Finance Posting</th>
                    <th>Waktu Catat</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($postings)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <div class="fw-semibold">Belum ada riwayat pembukuan.</div>
                            <div class="small">Laporan yang diposting oleh Finance akan tercatat secara permanen di sini.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($postings as $p): ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace"><?= View::e($p['journal_reference']) ?></span>
                            </td>
                            <td>
                                <a href="<?= Url::to('/reports/' . (int) $p['report_id']) ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($p['report_number']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-medium text-dark text-truncate" style="max-width: 200px;" title="<?= View::e($p['report_title']) ?>">
                                    <?= View::e($p['report_title']) ?>
                                </div>
                                <?php if (!empty($p['notes'])): ?>
                                    <small class="text-muted text-truncate d-block" style="max-width: 200px;" title="<?= View::e($p['notes']) ?>">
                                        <?= View::e($p['notes']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?= View::formatDate($p['posting_date'], 'd M Y') ?></td>
                            <td><?= View::e($p['creator_name'] ?: $p['creator_username']) ?></td>
                            <td><?= View::e($p['manager_name'] ?: ($p['manager_username'] ?: '-')) ?></td>
                            <td><strong class="text-primary"><?= View::formatRupiah($p['total_amount'] ?? 0) ?></strong></td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                    <?= View::e($p['posted_by_name'] ?: $p['posted_by_username']) ?>
                                </span>
                            </td>
                            <td><small class="text-muted"><?= View::formatDate($p['posted_at'] ?? $p['created_at'], 'd M Y H:i') ?></small></td>
                            <td class="text-end">
                                <a href="<?= Url::to('/reports/' . (int) $p['report_id']) ?>" class="btn btn-sm btn-outline-primary">
                                    Detail Laporan
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="text-muted small">
                Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= number_format($total, 0, ',', '.') ?> data)
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $queryParams = $_GET;
                    $queryParams['page'] = max(1, $page - 1);
                    $prevUrl = '?' . http_build_query($queryParams);

                    $queryParams['page'] = min($totalPages, $page + 1);
                    $nextUrl = '?' . http_build_query($queryParams);
                    ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $prevUrl ?>">&laquo; Sebelumnya</a>
                    </li>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php
                        $queryParams['page'] = $i;
                        $pageUrl = '?' . http_build_query($queryParams);
                        ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $pageUrl ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $nextUrl ?>">Berikutnya &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
