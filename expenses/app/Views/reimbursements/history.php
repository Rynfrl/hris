<?php

declare(strict_types=1);

use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Riwayat Pembayaran Reimbursement</h2>
        <p class="text-secondary small mb-0">Daftar transaksi reimbursement yang telah selesai dicairkan</p>
    </div>
    <a href="/reimbursements" class="btn btn-outline-secondary btn-sm">Kembali ke Antrean</a>
</div>

<div class="card card-gt">
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0">
            <thead>
                <tr>
                    <th>Laporan</th>
                    <th>Karyawan</th>
                    <th>Nominal</th>
                    <th>Metode</th>
                    <th>No. Referensi</th>
                    <th>Tanggal Bayar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reimbursements)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            Belum ada riwayat pembayaran reimbursement.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reimbursements as $item): ?>
                        <tr>
                            <td>
                                <a href="/reports/<?= (int) $item['report_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($item['report_number']) ?>
                                </a>
                                <div class="text-muted small"><?= View::e($item['report_title']) ?></div>
                            </td>
                            <td><?= View::e($item['employee_name']) ?></td>
                            <td><strong class="text-success"><?= View::formatRupiah($item['amount']) ?></strong></td>
                            <td><?= View::e($item['payment_method']) ?></td>
                            <td><code><?= View::e($item['payment_reference'] ?? '-') ?></code></td>
                            <td><?= View::formatDate($item['payment_date']) ?></td>
                            <td><?= View::statusBadge($item['status']) ?></td>
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
