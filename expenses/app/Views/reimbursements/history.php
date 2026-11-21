<?php

declare(strict_types=1);

use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Riwayat Pembayaran Reimbursement</h2>
        <p class="text-secondary small mb-0">Daftar transaksi reimbursement satu laporan penuh yang telah selesai dicairkan oleh Finance</p>
    </div>
    <a href="/reimbursements" class="btn btn-outline-secondary btn-sm">Kembali ke Antrean</a>
</div>

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
                <?php if (empty($reimbursements)): ?>
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
                        ?>
                        <tr>
                            <td>
                                <a href="/reports/<?= (int) $item['report_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($item['report_number']) ?>
                                </a>
                            </td>
                            <td><?= View::e($item['report_title'] ?? $item['title'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-secondary rounded-pill me-1"><?= $empCount ?> Karyawan</span>
                                <small class="text-muted d-block text-truncate" style="max-width: 200px;"><?= View::e($empSummary) ?></small>
                            </td>
                            <td>
                                <strong><?= View::e($item['recipient_name'] ?? $item['employee_name'] ?? 'Penanggung Jawab') ?></strong>
                                <?php if (!empty($item['bank_name'])): ?>
                                    <div class="text-muted small"><?= View::e($item['bank_name']) ?> &bull; <?= View::e($item['account_number'] ?? '-') ?></div>
                                <?php endif; ?>
                            </td>
                            <td><strong class="text-success fs-6"><?= View::formatRupiah($item['amount'] ?? 0) ?></strong></td>
                            <td>
                                <div><?= View::e($item['payment_method'] ?? 'TRANSFER') ?></div>
                                <small class="text-muted"><?= View::e($item['bank_name'] ?? '-') ?></small>
                            </td>
                            <td><code><?= View::e($item['payment_reference'] ?? '-') ?></code></td>
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
        <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Halaman <?= $page ?> dari <?= $totalPages ?> (Total <?= $total ?> transaksi)
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">Sebelumnya</a></li>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Selanjutnya</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
