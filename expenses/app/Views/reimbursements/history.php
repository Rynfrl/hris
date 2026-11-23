<?php

declare(strict_types=1);

use App\Core\Url;
use App\Core\View;

$reimbursements = $reimbursements ?? $items ?? [];
$totalPages = (int) ($totalPages ?? 1);
$page = (int) ($page ?? 1);
$total = (int) ($total ?? 0);
$perPage = (int) ($perPage ?? 20);
$filters = $filters ?? [];
$errorMessage = $errorMessage ?? null;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Riwayat Pembayaran Reimbursement</h2>
        <p class="text-secondary small mb-0">Daftar transaksi reimbursement satu laporan penuh yang telah selesai dicairkan oleh Finance</p>
    </div>
    <a href="<?= Url::to('/reimbursements') ?>" class="btn btn-outline-secondary btn-sm">Kembali ke Antrean</a>
</div>

<!-- Search Filter Toolbar -->
<div class="card card-gt mb-4">
    <div class="card-body p-3">
        <form action="<?= Url::to('/reimbursements/history') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-6 col-lg-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Cari nomor laporan, judul, atau penerima dana..." value="<?= View::e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                <?php if (!empty($filters['search'])): ?>
                    <a href="<?= Url::to('/reimbursements/history') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <div class="d-flex align-items-center gap-2">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <strong>Terjadi Kesalahan:</strong>
        </div>
        <p class="mb-0 mt-1 small"><?= View::e($errorMessage) ?></p>
    </div>
<?php endif; ?>

<div class="card card-gt">
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>No. Laporan</th>
                    <th>Judul Laporan</th>
                    <th>Karyawan Terlibat</th>
                    <th>Penerima Dana</th>
                    <th>Total Nominal</th>
                    <th>Metode &amp; Bank</th>
                    <th>No. Referensi</th>
                    <th>Tanggal Bayar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($errorMessage)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-danger">
                            Gagal mengambil data dari database. Silakan periksa log aplikasi atau hubungi administrator.
                        </td>
                    </tr>
                <?php elseif (empty($reimbursements)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            Belum ada riwayat pembayaran reimbursement yang lunas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reimbursements as $item): ?>
                        <?php
                            $empCount = (int) ($item['employee_count'] ?? 1);
                            $empSummary = !empty($item['employees_summary']) ? $item['employees_summary'] : ($item['employee_name'] ?? '-');
                            $bankAcc = $item['bank_account'] ?? $item['account_number'] ?? '-';
                        ?>
                        <tr>
                            <td>
                                <a href="<?= Url::to('/reports/' . (int) $item['report_id']) ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($item['report_number']) ?>
                                </a>
                            </td>
                            <td><?= View::e($item['report_title'] ?? $item['title'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-secondary rounded-pill me-1"><?= $empCount ?> Karyawan</span>
                                <small class="text-muted d-block text-truncate" style="max-width: 200px;" title="<?= View::e($empSummary) ?>"><?= View::e($empSummary) ?></small>
                            </td>
                            <td>
                                <strong><?= View::e($item['recipient_name'] ?? $item['employee_name'] ?? 'Kas Operasional') ?></strong>
                                <?php if (!empty($item['bank_name']) || (!empty($bankAcc) && $bankAcc !== '-')): ?>
                                    <div class="text-muted small">
                                        <?= View::e($item['bank_name'] ?? 'Bank') ?>
                                        <?php if (!empty($bankAcc) && $bankAcc !== '-'): ?>
                                            &bull; <?= View::e(strlen($bankAcc) > 4 ? str_repeat('*', max(0, strlen($bankAcc) - 4)) . substr($bankAcc, -4) : $bankAcc) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong class="text-success fs-6"><?= View::formatRupiah($item['amount'] ?? 0) ?></strong></td>
                            <td>
                                <div><?= View::e($item['payment_method'] ?? 'BANK_TRANSFER') ?></div>
                                <small class="text-muted"><?= View::e($item['bank_name'] ?? '-') ?></small>
                            </td>
                            <td><code><?= View::e($item['payment_reference'] ?? $item['bank_reference'] ?? '-') ?></code></td>
                            <td><?= View::formatDate($item['payment_date'] ?? null) ?></td>
                            <td><?= View::statusBadge($item['status'] ?? 'PAID') ?></td>
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
                Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= number_format($total, 0, ',', '.') ?> transaksi)
            </div>
            <nav aria-label="Navigasi Halaman Riwayat">
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
                        <a class="page-link" href="<?= $nextUrl ?>">Selanjutnya &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
