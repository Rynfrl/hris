<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$suggestedRef = $suggestedRef ?? ('JRN-EXP-' . date('Ym') . '-0001');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Pembukuan Laporan (Posting Finance)</h2>
        <p class="text-secondary small mb-0">Laporan gabungan yang telah disetujui manajer dan siap dibukukan serta dimasukkan ke kewajiban reimbursement</p>
    </div>
</div>

<div class="card card-gt">
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>No. Laporan</th>
                    <th>Judul Laporan</th>
                    <th>Karyawan Terlibat</th>
                    <th>Disetujui Pada</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Nominal</th>
                    <th>Status</th>
                    <th class="text-end">Aksi Pembukuan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="fw-semibold">Tidak ada laporan yang menunggu pembukuan.</div>
                            <div class="small">Laporan gabungan yang disetujui manajer akan muncul di antrean ini.</div>
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
                                <a href="<?= Url::to('/reports/' . (int) $item['report_id']) ?>" class="fw-semibold text-primary text-decoration-none">
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
                            <td><?= View::formatDate($item['approved_at'], 'd M Y H:i') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= (int) ($item['item_count'] ?? 0) ?> item</span></td>
                            <td><strong class="text-primary"><?= View::formatRupiah($item['total_amount']) ?></strong></td>
                            <td><?= View::statusBadge($item['status']) ?></td>
                            <td class="text-end">
                                <a href="<?= Url::to('/reports/' . (int) $item['report_id']) ?>" class="btn btn-sm btn-outline-secondary me-1">Rincian</a>
                                <button type="button" class="btn btn-sm btn-primary btn-post-modal"
                                        data-bs-toggle="modal"
                                        data-bs-target="#quickPostModal"
                                        data-report-id="<?= (int) $item['report_id'] ?>"
                                        data-report-num="<?= View::e($item['report_number']) ?>"
                                        data-report-total="<?= View::formatRupiah($item['total_amount']) ?>"
                                        data-emp-count="<?= $empCount ?>">
                                    Bukukan (Post)
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (($totalPages ?? 1) > 1): ?>
        <div class="card-footer bg-white border-top py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="text-muted small">
                Menampilkan halaman <strong><?= (int) ($page ?? 1) ?></strong> dari <strong><?= (int) ($totalPages ?? 1) ?></strong> (Total <?= number_format((int) ($total ?? 0), 0, ',', '.') ?> laporan disetujui)
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

<!-- Modal Quick Post -->
<div class="modal fade" id="quickPostModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="quickPostForm" action="" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Pembukuan Laporan Keuangan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        Laporan: <strong id="modalReportNum">-</strong> &bull; Total: <strong id="modalReportTotal">-</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor Referensi Jurnal <span class="text-danger">*</span></label>
                        <input type="text" name="journal_reference" id="modalJournalRef" class="form-control" required value="<?= View::e($suggestedRef) ?>">
                        <small class="text-muted">Nomor voucher/jurnal akuntansi Finance</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Pembukuan <span class="text-danger">*</span></label>
                        <input type="date" name="posting_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan Pembukuan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Keterangan opsional untuk arsip akuntansi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Konfirmasi Pembukuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const postBtns = document.querySelectorAll('.btn-post-modal');
    const form = document.getElementById('quickPostForm');
    const reportNumSpan = document.getElementById('modalReportNum');
    const reportTotalSpan = document.getElementById('modalReportTotal');

    postBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const reportId = this.getAttribute('data-report-id');
            const reportNum = this.getAttribute('data-report-num');
            const reportTotal = this.getAttribute('data-report-total');

            if (form) {
                form.action = '<?= \App\Core\Url::to('/reports') ?>/' + reportId + '/post';
            }
            if (reportNumSpan) reportNumSpan.textContent = reportNum;
            if (reportTotalSpan) reportTotalSpan.textContent = reportTotal;
        });
    });
});
</script>
