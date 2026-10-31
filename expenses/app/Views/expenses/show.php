<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="h4 fw-bold mb-0"><?= View::e($expense['expense_number']) ?></h2>
            <?= View::statusBadge($expense['status']) ?>
        </div>
        <p class="text-secondary small mb-0">Dibuat oleh <?= View::e($expense['creator_username'] ?? 'User') ?> pada <?= View::formatDate($expense['created_at'], 'd M Y H:i') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/expenses" class="btn btn-outline-secondary btn-sm">Kembali</a>
        <?php if ($canEdit): ?>
            <a href="/expenses/<?= (int) $expense['expense_id'] ?>/edit" class="btn btn-primary btn-sm">Edit Draft</a>
        <?php endif; ?>
        <?php if ($canDelete): ?>
            <form action="/expenses/<?= (int) $expense['expense_id'] ?>/delete" method="POST" class="d-inline" data-confirm="Yakin ingin menghapus pengeluaran ini secara permanen?">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Details -->
    <div class="col-lg-7">
        <div class="card card-gt mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title h6 fw-bold mb-0">Rincian Pengeluaran</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Kategori</div>
                        <div class="fw-semibold"><?= View::e($expense['category_name']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Tanggal Transaksi</div>
                        <div class="fw-semibold"><?= View::formatDate($expense['expense_date']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Nominal</div>
                        <div class="fs-5 fw-bold text-primary"><?= View::formatRupiah($expense['amount']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Metode Pembayaran</div>
                        <div class="fw-semibold"><?= View::e($expense['payment_method']) ?></div>
                    </div>
                    <?php if (!empty($expense['bank_name'])): ?>
                        <div class="col-sm-6">
                            <div class="text-muted small">Bank Transaksi / Rekening</div>
                            <div class="fw-semibold"><span class="badge bg-light text-primary border"><?= View::e($expense['bank_name']) ?></span></div>
                        </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <div class="text-muted small">Deskripsi Keperluan</div>
                        <div class="p-3 bg-light rounded mt-1"><?= nl2br(View::e($expense['description'])) ?></div>
                    </div>
                    <?php if (!empty($expense['merchant'])): ?>
                        <div class="col-sm-6">
                            <div class="text-muted small">Merchant / Toko</div>
                            <div class="fw-semibold"><?= View::e($expense['merchant']) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($expense['employee_name'])): ?>
                        <div class="col-sm-6">
                            <div class="text-muted small">Karyawan Pemilik</div>
                            <div class="fw-semibold"><?= View::e($expense['employee_name']) ?> (<?= View::e($expense['employee_number']) ?>)</div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($expense['notes'])): ?>
                        <div class="col-12">
                            <div class="text-muted small">Catatan Tambahan</div>
                            <div class="text-secondary"><?= nl2br(View::e($expense['notes'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($expense['rejection_reason'])): ?>
            <div class="alert alert-danger mb-4">
                <h6 class="alert-heading fw-bold">Alasan Penolakan</h6>
                <p class="mb-0"><?= nl2br(View::e($expense['rejection_reason'])) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Receipt & Report info -->
    <div class="col-lg-5">
        <!-- Linked Report Card -->
        <div class="card card-gt mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title h6 fw-bold mb-0">Status Laporan Biaya</h5>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($expense['report_id'])): ?>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-semibold"><?= View::e($expense['report_number']) ?></div>
                            <div class="text-muted small"><?= View::e($expense['report_title']) ?></div>
                        </div>
                        <a href="/reports/<?= (int) $expense['report_id'] ?>" class="btn btn-sm btn-outline-primary">Lihat Laporan</a>
                    </div>
                <?php else: ?>
                    <div class="text-muted small">
                        Pengeluaran ini belum dimasukkan ke dalam laporan biaya manapun. Anda dapat memasukkannya pada menu <strong>Laporan Saya</strong>.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Receipt Card -->
        <div class="card card-gt">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title h6 fw-bold mb-0">Bukti Transaksi (Kuitansi)</h5>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($expense['receipt_stored_name'])): ?>
                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            <div class="text-truncate">
                                <div class="fw-semibold small text-truncate"><?= View::e($expense['receipt_original_name']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <?= round(($expense['receipt_size'] ?? 0) / 1024, 1) ?> KB &bull; <?= View::e($expense['receipt_mime']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="/expenses/<?= (int) $expense['expense_id'] ?>/receipt" target="_blank" class="btn btn-sm btn-primary flex-fill">
                                Buka / Unduh Bukti
                            </a>
                            <?php if ($canEdit): ?>
                                <form action="/expenses/<?= (int) $expense['expense_id'] ?>/receipt/remove" method="POST" data-confirm="Hapus bukti kuitansi ini?">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3 text-muted small mb-3">
                        Belum ada bukti kuitansi yang diunggah.
                    </div>
                <?php endif; ?>

                <?php if ($canEdit): ?>
                    <div class="border-top pt-3">
                        <label class="form-label small fw-bold"><?= !empty($expense['receipt_stored_name']) ? 'Ganti Bukti Kuitansi' : 'Unggah Bukti Kuitansi' ?></label>
                        <form action="/expenses/<?= (int) $expense['expense_id'] ?>/receipt" method="POST" enctype="multipart/form-data">
                            <?= Csrf::field() ?>
                            <div class="input-group input-group-sm mb-2">
                                <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" data-max-size="5242880" required>
                                <button type="submit" class="btn btn-primary">Unggah</button>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem;">JPG, PNG, WEBP, atau PDF (maks 5 MB)</div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
