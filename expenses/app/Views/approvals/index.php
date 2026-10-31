<?php

declare(strict_types=1);

use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Persetujuan Tim</h2>
        <p class="text-secondary small mb-0">Antrean laporan pengeluaran dari anggota tim langsung Anda (<?= $directReportsCount ?> bawahan langsung)</p>
    </div>
</div>

<div class="card card-gt">
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0">
            <thead>
                <tr>
                    <th>No. Laporan</th>
                    <th>Karyawan</th>
                    <th>Judul Laporan</th>
                    <th>Tanggal Diajukan</th>
                    <th>Jumlah Item</th>
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
                        <tr>
                            <td>
                                <a href="/reports/<?= (int) $item['report_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($item['report_number']) ?>
                                </a>
                            </td>
                            <td>
                                <strong><?= View::e($item['employee_name']) ?></strong>
                                <div class="text-muted small"><?= View::e($item['employee_number']) ?></div>
                            </td>
                            <td><?= View::e($item['title']) ?></td>
                            <td><?= View::formatDate($item['submitted_at'], 'd M Y H:i') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= (int) ($item['item_count'] ?? 0) ?> item</span></td>
                            <td><strong class="text-primary"><?= View::formatRupiah($item['total_amount']) ?></strong></td>
                            <td><?= View::statusBadge($item['status']) ?></td>
                            <td class="text-end">
                                <a href="/reports/<?= (int) $item['report_id'] ?>" class="btn btn-sm btn-primary">
                                    Tinjau &amp; Putuskan
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
