<?php

declare(strict_types=1);

use App\Core\Url;
use App\Core\View;

$currentStatus = $currentStatus ?? 'SUBMITTED';
$isHistory = ($currentStatus === 'HISTORY');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1"><?= $isHistory ? 'Riwayat Keputusan Persetujuan' : 'Antrean Persetujuan Laporan (Manager)' ?></h2>
        <p class="text-secondary small mb-0"><?= $isHistory ? 'Histori jejak keputusan persetujuan dan penolakan laporan berbasis event' : 'Antrean laporan gabungan yang diajukan untuk ditinjau dan diputuskan' ?></p>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-3 gap-2">
    <li class="nav-item">
        <a class="nav-link <?= !$isHistory ? 'active' : '' ?>" href="<?= Url::to('/approvals') ?>">
            Menunggu Persetujuan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $isHistory ? 'active' : '' ?>" href="<?= Url::to('/approvals?status=HISTORY') ?>">
            Riwayat Keputusan
        </a>
    </li>
</ul>

<div class="card card-gt">
    <div class="table-responsive">
        <?php if ($isHistory): ?>
            <!-- Event-based approval history table (Perbaikan 7) -->
            <table class="table table-hover table-gt mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No. Laporan</th>
                        <th>Judul Laporan</th>
                        <th>Pembuat Laporan</th>
                        <th>Keputusan</th>
                        <th>Catatan / Alasan</th>
                        <th>Manajer Pemutus</th>
                        <th>Waktu Keputusan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="fw-semibold">Belum Ada Riwayat Keputusan</div>
                                <div class="small">Belum ada tindakan persetujuan atau penolakan yang dicatat.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $ev): ?>
                            <tr>
                                <td>
                                    <a href="/reports/<?= (int) $ev['report_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                        <?= View::e($ev['report_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= View::e($ev['report_title']) ?></div>
                                    <small class="text-muted"><?= (int) $ev['employee_count'] ?> Karyawan &bull; <?= (int) $ev['item_count'] ?> Transaksi</small>
                                </td>
                                <td>
                                    <?= View::e($ev['creator_name'] ?: $ev['creator_username']) ?>
                                </td>
                                <td>
                                    <?= View::statusBadge($ev['action']) ?>
                                </td>
                                <td class="small" style="max-width: 250px;">
                                    <?= !empty($ev['comment']) ? nl2br(View::e($ev['comment'])) : '<span class="text-muted">-</span>' ?>
                                </td>
                                <td>
                                    <strong><?= View::e($ev['approver_name'] ?: $ev['approver_username']) ?></strong>
                                </td>
                                <td>
                                    <small class="text-muted"><?= View::formatDate($ev['decided_at'], 'd M Y H:i') ?></small>
                                </td>
                                <td class="text-end">
                                    <a href="/reports/<?= (int) $ev['report_id'] ?>" class="btn btn-sm btn-outline-primary">
                                        Buka Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Pending approvals table -->
            <table class="table table-hover table-gt mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No. Laporan</th>
                        <th>Judul Laporan</th>
                        <th>Karyawan yang Diajukan</th>
                        <th>Tanggal Diajukan</th>
                        <th>Jumlah Transaksi</th>
                        <th>Total Nominal</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="mb-2">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-success"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                </div>
                                <div class="fw-semibold">Semua Beres!</div>
                                <div class="small">Tidak ada laporan yang sedang menunggu persetujuan Anda saat ini.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $item): ?>
                            <?php
                                $empCount = (int) ($item['employee_count'] ?? 0);
                                $empSummary = !empty($item['employees_summary']) ? $item['employees_summary'] : ($item['employee_name'] ?? '-');
                            ?>
                            <tr>
                                <td>
                                    <a href="/reports/<?= (int) $item['report_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                        <?= View::e($item['report_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= View::e($item['title']) ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <?php if ($empCount > 1): ?>
                                            <span class="badge bg-primary rounded-pill"><?= $empCount ?> Karyawan</span>
                                        <?php endif; ?>
                                        <span class="text-truncate text-secondary small" style="max-width: 250px;" title="<?= View::e($empSummary) ?>">
                                            <?= View::e($empSummary) ?>
                                        </span>
                                    </div>
                                </td>
                                <td><?= View::formatDate($item['submitted_at'] ?? null, 'd M Y H:i') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= (int) ($item['item_count'] ?? 0) ?> item</span></td>
                                <td><strong class="text-primary"><?= View::formatRupiah($item['total_amount'] ?? 0) ?></strong></td>
                                <td><?= View::statusBadge($item['status'] ?? 'SUBMITTED') ?></td>
                                <td class="text-end">
                                    <a href="/reports/<?= (int) $item['report_id'] ?>" class="btn btn-sm btn-primary">
                                        Tinjau Rincian
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if (($totalPages ?? 1) > 1): ?>
        <div class="card-footer bg-white border-top py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="text-muted small">
                Menampilkan halaman <strong><?= (int) ($page ?? 1) ?></strong> dari <strong><?= (int) ($totalPages ?? 1) ?></strong> (Total <?= number_format((int) ($total ?? 0), 0, ',', '.') ?> laporan)
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $queryParams = $_GET;
                    $curPage = (int) ($page ?? 1);
                    $totPages = (int) ($totalPages ?? 1);
                    $queryParams['page'] = max(1, $curPage - 1);
                    $prevUrl = '?' . http_build_query($queryParams);

                    $queryParams['page'] = min($totPages, $curPage + 1);
                    $nextUrl = '?' . http_build_query($queryParams);
                    ?>
                    <li class="page-item <?= $curPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $prevUrl ?>">&laquo; Sebelumnya</a>
                    </li>
                    <?php for ($i = max(1, $curPage - 2); $i <= min($totPages, $curPage + 2); $i++): ?>
                        <?php
                        $queryParams['page'] = $i;
                        $pageUrl = '?' . http_build_query($queryParams);
                        ?>
                        <li class="page-item <?= $i === $curPage ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $pageUrl ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $curPage >= $totPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $nextUrl ?>">Berikutnya &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
