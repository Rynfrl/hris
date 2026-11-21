<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;

$suggestedPayNumber = $suggestedPayNumber ?? ('PAY-' . date('Ym') . '-0001');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Antrean &amp; Pembayaran Reimbursement</h2>
        <p class="text-secondary small mb-0">Satu Laporan = Satu Reimbursement = Satu Pembayaran Penuh (Tidak menerima pembayaran parsial)</p>
    </div>
    <div>
        <a href="/reimbursements/history" class="btn btn-outline-secondary btn-sm">
            Riwayat Pembayaran Selesai
        </a>
    </div>
</div>

<!-- 1. Posted Reports ready to enter reimbursement queue -->
<?php if (!empty($postedReports)): ?>
    <div class="card card-gt mb-4 border-warning">
        <div class="card-header bg-warning bg-opacity-10 border-bottom py-3">
            <h5 class="card-title h6 fw-bold mb-0 text-dark">Laporan Selesai Dibukukan (Siap Masuk Antrean Reimbursement)</h5>
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

<!-- 2. Active Reimbursements Waiting for Full Payment -->
<div class="card card-gt">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="card-title h6 fw-bold mb-0">Daftar Antrean Pembayaran Reimbursement Satu Laporan Penuh</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>No. Laporan</th>
                    <th>Judul Laporan</th>
                    <th>Karyawan Terlibat</th>
                    <th>Total Nominal Disetujui</th>
                    <th>Penerima Dana</th>
                    <th>Status</th>
                    <th class="text-end">Aksi Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activeReimbursements)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="fw-semibold">Tidak ada reimbursement yang menunggu pembayaran.</div>
                            <div class="small">Laporan yang telah dibukukan oleh Finance akan muncul di daftar antrean ini.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activeReimbursements as $rem): ?>
                        <?php
                            $approved = (float) ($rem['approved_amount'] ?? $rem['amount'] ?? 0);
                            $empCount = (int) ($rem['employee_count'] ?? 1);
                            $empSummary = !empty($rem['employees_summary']) ? $rem['employees_summary'] : ($rem['employee_name'] ?? '-');
                        ?>
                        <tr>
                            <td>
                                <a href="/reports/<?= (int) $rem['report_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($rem['report_number']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= View::e($rem['report_title'] ?? $rem['title'] ?? '-') ?></div>
                            </td>
                            <td>
                                <span class="badge bg-secondary rounded-pill me-1"><?= $empCount ?> Karyawan</span>
                                <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?= View::e($empSummary) ?></small>
                            </td>
                            <td><strong class="text-primary fs-6"><?= View::formatRupiah($approved) ?></strong></td>
                            <td>
                                <div><strong><?= View::e($rem['recipient_name'] ?? 'Penanggung Jawab Laporan') ?></strong></div>
                                <?php if (!empty($rem['bank_name'])): ?>
                                    <div class="text-muted small"><?= View::e($rem['bank_name']) ?> &bull; <?= View::e($rem['account_number'] ?? '-') ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= View::statusBadge($rem['status']) ?></td>
                            <td class="text-end">
                                <?php if ($rem['status'] === 'PENDING'): ?>
                                    <form action="/reimbursements/<?= (int) $rem['reimbursement_id'] ?>/processing" method="POST" class="d-inline me-1">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-info">Proses</button>
                                    </form>
                                <?php endif; ?>

                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal<?= (int) $rem['reimbursement_id'] ?>">
                                    Bayar Penuh
                                </button>

                                <!-- Full Payment Modal -->
                                <div class="modal fade" id="payModal<?= (int) $rem['reimbursement_id'] ?>" tabindex="-1" aria-labelledby="payModalLabel<?= (int) $rem['reimbursement_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg text-start">
                                        <form action="/reimbursements/<?= (int) $rem['reimbursement_id'] ?>/payment" method="POST" enctype="multipart/form-data">
                                            <?= Csrf::field() ?>
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title h6 fw-bold" id="payModalLabel<?= (int) $rem['reimbursement_id'] ?>">Pencairan Reimbursement Laporan <?= View::e($rem['report_number']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="alert alert-info border p-3 mb-3 small">
                                                        <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> Aturan Pembayaran Keuangan:</div>
                                                        <div>1 Laporan Gabungan = 1 Kali Reimbursement = 1 Kali Pembayaran Penuh. Tidak mendukung pembayaran parsial/bertahap.</div>
                                                        <hr class="my-2">
                                                        <div>Laporan: <strong><?= View::e($rem['report_number']) ?> &bull; <?= View::e($rem['report_title'] ?? $rem['title'] ?? '') ?></strong></div>
                                                        <div>Karyawan Terlibat (<?= $empCount ?>): <strong><?= View::e($empSummary) ?></strong></div>
                                                    </div>

                                                    <input type="hidden" name="amount" value="<?= $approved ?>">

                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold small">Total Dana Dicairkan (Penuh)</label>
                                                            <div class="form-control form-control-sm bg-light fw-bold text-success fs-6"><?= View::formatRupiah($approved) ?></div>
                                                            <small class="text-muted">Total seluruh pengeluaran dalam laporan gabungan</small>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold small">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                                            <input type="date" name="payment_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>">
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold small">Nama Penerima / Rekening PIC <span class="text-danger">*</span></label>
                                                            <input type="text" name="recipient_name" class="form-control form-control-sm" required value="<?= View::e($rem['recipient_name'] ?? 'Penanggung Jawab Laporan') ?>" placeholder="Contoh: Admin Expense / Andi Pratama">
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold small">Metode Pembayaran <span class="text-danger">*</span></label>
                                                            <select name="payment_method" class="form-select form-select-sm" required>
                                                                <option value="BANK_TRANSFER" selected>Transfer Bank</option>
                                                                <option value="CASH">Tunai (Cash)</option>
                                                                <option value="OTHER">Lainnya</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <label class="form-label small">Nama Bank Tujuan</label>
                                                            <input type="text" name="bank_name" class="form-control form-control-sm" value="<?= View::e($rem['bank_name'] ?? 'BCA') ?>" placeholder="Contoh: BCA / Mandiri">
                                                        </div>

                                                        <div class="col-md-4">
                                                            <label class="form-label small">Nomor Rekening</label>
                                                            <input type="text" name="account_number" class="form-control form-control-sm" value="<?= View::e($rem['account_number'] ?? '') ?>" placeholder="Contoh: 1234567890">
                                                        </div>

                                                        <div class="col-md-4">
                                                            <label class="form-label small">Atas Nama Rekening</label>
                                                            <input type="text" name="account_holder" class="form-control form-control-sm" value="<?= View::e($rem['account_holder'] ?? '') ?>" placeholder="Nama pemilik rekening">
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label fw-semibold small">No. Referensi / Nomor Transaksi Bank <span class="text-danger">*</span></label>
                                                            <input type="text" name="bank_reference" class="form-control form-control-sm" placeholder="Contoh: TRF-EXP-<?= date('Ymd') ?>-001" maxlength="150" required>
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label fw-semibold small">Upload Bukti Transfer / Pembayaran Penuh <span class="text-danger">*</span></label>
                                                            <input type="file" name="proof" class="form-control form-control-sm" accept="image/*,.pdf" required>
                                                            <small class="text-muted">Lampirkan bukti mutasi bank atau resi transfer (JPG, PNG, PDF)</small>
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label small">Catatan Tambahan (Opsional)</label>
                                                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan pembayaran reimbursement penuh..."></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-success px-3">Konfirmasi Pembayaran Penuh</button>
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
