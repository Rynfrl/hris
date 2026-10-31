<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Pembukuan Laporan (Posting)</h2>
        <p class="text-secondary small mb-0">Laporan yang telah disetujui manajer dan siap dibukukan oleh tim Keuangan</p>
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
                    <th>Disetujui Pada</th>
                    <th>Jumlah Item</th>
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
                            <div class="small">Laporan yang disetujui manajer akan muncul di sini.</div>
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
                                <div class="text-muted small"><?= View::e($item['department_name'] ?? '-') ?></div>
                            </td>
                            <td><?= View::e($item['title']) ?></td>
                            <td><?= View::formatDate($item['approved_at'], 'd M Y H:i') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= (int) ($item['item_count'] ?? 0) ?> item</span></td>
                            <td><strong class="text-primary"><?= View::formatRupiah($item['total_amount']) ?></strong></td>
                            <td><?= View::statusBadge($item['status']) ?></td>
                            <td class="text-end">
                                <a href="/reports/<?= (int) $item['report_id'] ?>" class="btn btn-sm btn-outline-secondary me-1">Lihat</a>
                                <form action="/reports/<?= (int) $item['report_id'] ?>/post" method="POST" class="d-inline" data-confirm="Bukukan laporan ini sekarang?">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-primary">Bukukan (Post)</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
