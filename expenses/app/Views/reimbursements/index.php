<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;

$suggestedPayNumber = $suggestedPayNumber ?? ('PAY-' . date('Ym') . '-0001');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Antrean &amp; Pembayaran Reimbursement</h2>
        <p class="text-secondary small mb-0">Kelola dan selesaikan pencairan dana biaya masing-masing karyawan (mendukung pembayaran bertahap/parsial)</p>
    </div>
    <div>
        <a href="/reimbursements/history" class="btn btn-outline-secondary btn-sm">
            Riwayat Pembayaran Selesai
        </a>
    </div>
</div>

<!-- 1. Posted Reports ready to enter queue if any -->
<?php if (!empty($postedReports)): ?>
    <div class="card card-gt mb-4 border-warning">
        <div class="card-header bg-warning bg-opacity-10 border-bottom py-3">
            <h5 class="card-title h6 fw-bold mb-0 text-dark">Laporan Dibukukan (Siap Diproses ke Antrean Reimbursement)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-gt mb-0 align-middle">
                <thead>
                    <tr>
                        <th>No. Laporan</th>
                        <th>Judul Laporan</th>
                        <th>Karyawan Terlibat</th>
                        <th>Total Nominal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($postedReports as $rep): ?>
                        <?php
                            $empCount = (int) ($rep['employee_count'] ?? 0);
                            $empSummary = !empty($rep['employees_summary']) ? $rep['employees_summary'] : ($rep['employee_name'] ?? '-');
                        ?>
                        <tr>
                            <td>
                                <a href="/reports/<?= (int) $rep['report_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($rep['report_number']) ?>
                                </a>
                            </td>
                            <td><?= View::e($rep['title']) ?></td>
                            <td>
                                <span class="badge bg-primary rounded-pill me-1"><?= $empCount ?: 1 ?> Karyawan</span>
                                <small class="text-muted"><?= View::e($empSummary) ?></small>
                            </td>
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
        <h5 class="card-title h6 fw-bold mb-0">Daftar Kewajiban Pembayaran Karyawan</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Laporan</th>
                    <th>Karyawan Penerima</th>
                    <th>Rekening Bank</th>
                    <th>Disetujui</th>
                    <th>Sudah Dibayar</th>
                    <th>Sisa Kewajiban</th>
                    <th>Status</th>
                    <th class="text-end">Aksi Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activeReimbursements)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="fw-semibold">Tidak ada kewajiban reimbursement yang menunggu pembayaran.</div>
                            <div class="small">Laporan yang telah dibukukan akan muncul di daftar antrean ini.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activeReimbursements as $rem): ?>
                        <?php
                            $approved = (float) ($rem['approved_amount'] ?? $rem['amount'] ?? 0);
                            $paid = (float) ($rem['paid_amount'] ?? 0);
                            $remaining = max(0.00, round($approved - $paid, 2));
                        ?>
                        <tr>
                            <td>
                                <a href="/reports/<?= (int) $rem['report_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($rem['report_number']) ?>
                                </a>
                                <div class="text-muted small text-truncate" style="max-width: 180px;"><?= View::e($rem['report_title']) ?></div>
                            </td>
                            <td>
                                <strong><?= View::e($rem['employee_name']) ?></strong>
                                <div class="text-muted small"><?= View::e($rem['employee_number']) ?> &bull; <?= View::e($rem['department_name'] ?? 'Umum') ?></div>
                            </td>
                            <td>
                                <div><strong><?= View::e($rem['bank_name'] ?? 'Bank') ?></strong></div>
                                <div class="text-muted small"><code><?= View::e($rem['bank_account'] ?? '-') ?></code></div>
                            </td>
                            <td><strong><?= View::formatRupiah($approved) ?></strong></td>
                            <td class="text-success"><?= View::formatRupiah($paid) ?></td>
                            <td class="fw-bold text-danger"><?= View::formatRupiah($remaining) ?></td>
                            <td><?= View::statusBadge($rem['status']) ?></td>
                            <td class="text-end">
                                <?php if ($rem['status'] === 'PENDING'): ?>
                                    <form action="/reimbursements/<?= (int) $rem['reimbursement_id'] ?>/processing" method="POST" class="d-inline me-1">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-info">Proses</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($remaining > 0): ?>
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal<?= (int) $rem['reimbursement_id'] ?>">
                                        Catat Bayar
                                    </button>
                                <?php endif; ?>

                                <!-- Pay Modal -->
                                <div class="modal fade" id="payModal<?= (int) $rem['reimbursement_id'] ?>" tabindex="-1" aria-labelledby="payModalLabel<?= (int) $rem['reimbursement_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog text-start">
                                        <form action="/reimbursements/<?= (int) $rem['reimbursement_id'] ?>/payment" method="POST" enctype="multipart/form-data">
                                            <?= Csrf::field() ?>
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title h6 fw-bold" id="payModalLabel<?= (int) $rem['reimbursement_id'] ?>">Pencairan Reimbursement Karyawan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="alert alert-light border p-3 mb-3 small">
                                                        Karyawan: <strong><?= View::e($rem['employee_name']) ?></strong> (<?= View::e($rem['employee_number']) ?>)<br>
                                                        Rekening: <strong><?= View::e($rem['bank_name'] ?? 'Bank') ?> - <?= View::e($rem['bank_account'] ?? '-') ?></strong><br>
                                                        Total Disetujui: <strong><?= View::formatRupiah($approved) ?></strong> &bull;
                                                        Sisa: <strong class="text-danger"><?= View::formatRupiah($remaining) ?></strong>
                                                    </div>

                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold small">Nominal Bayar (Rp) <span class="text-danger">*</span></label>
                                                            <input type="number" name="amount" class="form-control form-control-sm" step="0.01" min="1" max="<?= $remaining ?>" required value="<?= $remaining ?>">
                                                            <small class="text-muted">Bisa dibayar sebagian / bertahap</small>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold small">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                                            <input type="date" name="payment_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>">
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold small">Metode Pembayaran</label>
                                                            <select name="payment_method" class="form-select form-select-sm" required>
                                                                <option value="BANK_TRANSFER">Transfer Bank</option>
                                                                <option value="CASH">Tunai (Cash)</option>
                                                                <option value="OTHER">Lainnya</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label small">Nama Bank Pengirim/Tujuan</label>
                                                            <input type="text" name="bank_name" class="form-control form-control-sm" value="<?= View::e($rem['bank_name'] ?? 'BCA') ?>">
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label fw-semibold small">No. Referensi / No. Transaksi Bank</label>
                                                            <input type="text" name="bank_reference" class="form-control form-control-sm" placeholder="Contoh: TRF-20261011-00123" maxlength="150" required>
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label small">Upload Bukti Transfer / Pembayaran</label>
                                                            <input type="file" name="proof" class="form-control form-control-sm" accept="image/*,.pdf">
                                                            <small class="text-muted">Bukti mutasi atau resi transfer (JPG, PNG, PDF)</small>
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label small">Catatan Tambahan (Opsional)</label>
                                                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan bukti transfer..."></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-success px-3">Simpan Pembayaran</button>
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
