<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Antrean Reimbursement</h2>
        <p class="text-secondary small mb-0">Kelola dan selesaikan pencairan dana biaya karyawan</p>
    </div>
    <div>
        <a href="/reimbursements/history" class="btn btn-outline-secondary btn-sm">
            Riwayat Pembayaran Selesai
        </a>
    </div>
</div>

<!-- 1. Posted Reports ready to enter queue -->
<?php if (!empty($postedReports)): ?>
    <div class="card card-gt mb-4 border-warning">
        <div class="card-header bg-warning bg-opacity-10 border-bottom py-3">
            <h5 class="card-title h6 fw-bold mb-0 text-dark">Laporan Dibukukan (Siap Diproses ke Antrean Reimbursement)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-gt mb-0">
                <thead>
                    <tr>
                        <th>No. Laporan</th>
                        <th>Karyawan</th>
                        <th>Judul Laporan</th>
                        <th>Total</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($postedReports as $rep): ?>
                        <tr>
                            <td>
                                <a href="/reports/<?= (int) $rep['report_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($rep['report_number']) ?>
                                </a>
                            </td>
                            <td><?= View::e($rep['employee_name']) ?></td>
                            <td><?= View::e($rep['title']) ?></td>
                            <td><strong><?= View::formatRupiah($rep['total_amount']) ?></strong></td>
                            <td class="text-end">
                                <form action="/reports/<?= (int) $rep['report_id'] ?>/reimbursement" method="POST" class="d-inline">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-primary">Masukkan Antrean Bayar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- 2. Active Reimbursements Waiting for Payment -->
<div class="card card-gt">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="card-title h6 fw-bold mb-0">Antrean Pembayaran Aktif</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0">
            <thead>
                <tr>
                    <th>Laporan</th>
                    <th>Karyawan</th>
                    <th>Rekening Bank</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th class="text-end">Aksi Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activeReimbursements)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <div class="fw-semibold">Tidak ada antrean reimbursement yang menunggu pembayaran.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activeReimbursements as $rem): ?>
                        <tr>
                            <td>
                                <a href="/reports/<?= (int) $rem['report_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($rem['report_number']) ?>
                                </a>
                                <div class="text-muted small"><?= View::e($rem['report_title']) ?></div>
                            </td>
                            <td>
                                <strong><?= View::e($rem['employee_name']) ?></strong>
                                <div class="text-muted small"><?= View::e($rem['employee_number']) ?></div>
                            </td>
                            <td>
                                <div><?= View::e($rem['bank_name'] ?? 'Bank') ?></div>
                                <div class="text-muted small"><?= View::e($rem['bank_account'] ?? '-') ?></div>
                            </td>
                            <td><strong class="text-primary fs-6"><?= View::formatRupiah($rem['amount']) ?></strong></td>
                            <td><?= View::statusBadge($rem['status']) ?></td>
                            <td class="text-end">
                                <?php if ($rem['status'] === 'PENDING'): ?>
                                    <form action="/reimbursements/<?= (int) $rem['reimbursement_id'] ?>/processing" method="POST" class="d-inline me-1">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-info">Proses</button>
                                    </form>
                                <?php endif; ?>

                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal<?= (int) $rem['reimbursement_id'] ?>">
                                    Catat Lunas (PAID)
                                </button>

                                <!-- Pay Modal -->
                                <div class="modal fade" id="payModal<?= (int) $rem['reimbursement_id'] ?>" tabindex="-1" aria-labelledby="payModalLabel<?= (int) $rem['reimbursement_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog text-start">
                                        <form action="/reimbursements/<?= (int) $rem['reimbursement_id'] ?>/paid" method="POST">
                                            <?= Csrf::field() ?>
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title h6 fw-bold" id="payModalLabel<?= (int) $rem['reimbursement_id'] ?>">Konfirmasi Pembayaran Reimbursement</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-muted mb-3">
                                                        Laporan: <strong><?= View::e($rem['report_number']) ?></strong> &bull;
                                                        Karyawan: <strong><?= View::e($rem['employee_name']) ?></strong><br>
                                                        Total Pencairan: <strong class="text-success"><?= View::formatRupiah($rem['amount']) ?></strong>
                                                    </p>
                                                    <div class="row g-2">
                                                        <div class="col-md-6 mb-2">
                                                            <label class="form-label small">Metode Pembayaran</label>
                                                            <select name="payment_method" class="form-select form-select-sm" required>
                                                                <option value="BANK_TRANSFER">Transfer Bank</option>
                                                                <option value="CASH">Tunai (Cash)</option>
                                                                <option value="OTHER">Lainnya</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-2">
                                                            <label class="form-label small">Tanggal Bayar</label>
                                                            <input type="date" name="payment_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>">
                                                        </div>
                                                        <div class="col-12 mb-2">
                                                            <label class="form-label small">No. Referensi / No. Transaksi Bank</label>
                                                            <input type="text" name="payment_reference" class="form-control form-control-sm" placeholder="Contoh: TRF-20261011-00123" maxlength="150" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label small">Catatan Tambahan (Opsional)</label>
                                                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan bukti transfer..."></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-success">Selesaikan Pembayaran</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
