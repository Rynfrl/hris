<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="h4 fw-bold mb-0"><?= View::e($report['report_number']) ?></h2>
            <?= View::statusBadge($report['status']) ?>
        </div>
        <p class="text-secondary small mb-0">
            Pemilik: <strong><?= View::e($report['employee_name']) ?></strong> (<?= View::e($report['employee_number']) ?>) &bull;
            Departemen: <?= View::e($report['department_name'] ?? '-') ?> &bull;
            Dibuat: <?= View::formatDate($report['created_at'], 'd M Y H:i') ?>
        </p>
    </div>

    <!-- Workflow Action Buttons -->
    <div class="d-flex flex-wrap gap-2">
        <a href="/reports" class="btn btn-outline-secondary btn-sm">Kembali</a>

        <?php if ($canEdit): ?>
            <a href="/reports/<?= (int) $report['report_id'] ?>/edit" class="btn btn-outline-primary btn-sm">Edit Metadata</a>
        <?php endif; ?>

        <?php if ($canSubmit): ?>
            <form action="/reports/<?= (int) $report['report_id'] ?>/submit" method="POST" data-confirm="Ajukan laporan ini untuk persetujuan manajer?">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-primary btn-sm" <?= empty($items) ? 'disabled title="Tambahkan minimal satu item"' : '' ?>>
                    Ajukan Laporan
                </button>
            </form>
        <?php endif; ?>

        <?php if ($canRevise): ?>
            <form action="/reports/<?= (int) $report['report_id'] ?>/revise" method="POST" data-confirm="Kembalikan laporan ini ke status draft untuk revisi?">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-warning btn-sm">
                    Revisi Laporan (Kembali ke Draft)
                </button>
            </form>
        <?php endif; ?>

        <?php if ($canDecide): ?>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                Setujui Laporan
            </button>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#refuseModal">
                Tolak Laporan
            </button>
        <?php endif; ?>

        <?php if ($canPost): ?>
            <form action="/reports/<?= (int) $report['report_id'] ?>/post" method="POST" data-confirm="Bukukan laporan pengeluaran ini?">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-indigo btn-primary btn-sm">
                    Bukukan (Posting Finance)
                </button>
            </form>
        <?php endif; ?>

        <?php if ($canReimburse && $report['status'] === 'POSTED'): ?>
            <form action="/reports/<?= (int) $report['report_id'] ?>/reimbursement" method="POST" data-confirm="Masukkan laporan ini ke antrean pembayaran reimbursement?">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-warning btn-sm">
                    Proses Reimbursement
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Refusal banner -->
<?php if (!empty($report['rejection_reason'])): ?>
    <div class="alert alert-danger mb-4">
        <h6 class="alert-heading fw-bold mb-1">Catatan Penolakan Manajer:</h6>
        <p class="mb-0"><?= nl2br(View::e($report['rejection_reason'])) ?></p>
        <?php if (!empty($report['refused_at'])): ?>
            <div class="text-muted small mt-1">Ditolak pada: <?= View::formatDate($report['refused_at'], 'd M Y H:i') ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Reimbursement status banner -->
<?php if (!empty($report['reimbursement_id'])): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
        <div>
            <strong>Status Pembayaran:</strong> <?= View::statusBadge($report['reimbursement_status']) ?>
            <?php if (!empty($report['payment_date'])): ?>
                &bull; Tanggal Bayar: <strong><?= View::formatDate($report['payment_date']) ?></strong>
            <?php endif; ?>
            <?php if (!empty($report['payment_reference'])): ?>
                &bull; No. Referensi: <strong><?= View::e($report['payment_reference']) ?></strong>
            <?php endif; ?>
        </div>
        <?php if ($canReimburse && in_array($report['reimbursement_status'], ['PENDING', 'PROCESSING'], true)): ?>
            <a href="/reimbursements" class="btn btn-sm btn-primary">Buka Antrean Keuangan</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Summary & Metadata Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card card-gt h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-2"><?= View::e($report['title']) ?></h5>
                <?php if (!empty($report['description'])): ?>
                    <p class="text-secondary mb-3"><?= nl2br(View::e($report['description'])) ?></p>
                <?php endif; ?>
                <div class="row g-2 text-muted small border-top pt-3">
                    <div class="col-sm-6">
                        Periode: <strong><?= View::formatDate($report['start_date']) ?></strong> s/d <strong><?= View::formatDate($report['end_date']) ?></strong>
                    </div>
                    <div class="col-sm-6">
                        Diajukan: <strong><?= View::formatDate($report['submitted_at'], 'd M Y H:i') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-gt h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-center text-center">
                <div class="metric-label mb-1">Total Pengeluaran Laporan</div>
                <div class="fs-2 fw-bold text-primary mb-1"><?= View::formatRupiah($report['total_amount']) ?></div>
                <div class="text-muted small"><?= count($items) ?> item pengeluaran terdaftar</div>
            </div>
        </div>
    </div>
</div>

<!-- Items Table -->
<div class="card card-gt mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title h6 fw-bold mb-0">Daftar Item Pengeluaran</h5>
        <?php if ($canEdit && !empty($unassignedExpenses)): ?>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addItemCollapse">
                + Tambah Item Pengeluaran
            </button>
        <?php endif; ?>
    </div>

    <!-- Add Item Collapsible -->
    <?php if ($canEdit && !empty($unassignedExpenses)): ?>
        <div class="collapse border-bottom bg-light p-3" id="addItemCollapse">
            <form action="/reports/<?= (int) $report['report_id'] ?>/items" method="POST" class="row g-2 align-items-center">
                <?= Csrf::field() ?>
                <div class="col-md-9">
                    <select name="expense_id" class="form-select form-select-sm" required>
                        <option value="">-- Pilih Pengeluaran Draft --</option>
                        <?php foreach ($unassignedExpenses as $un): ?>
                            <option value="<?= (int) $un['expense_id'] ?>">
                                <?= View::e($un['expense_number']) ?> &bull; <?= View::formatDate($un['expense_date']) ?> &bull; <?= View::e($un['category_name']) ?>: <?= View::e($un['description']) ?> (<?= View::formatRupiah($un['amount']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Masukkan ke Laporan</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0">
            <thead>
                <tr>
                    <th>No. Pengeluaran</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Merchant</th>
                    <th>Bukti</th>
                    <th>Nominal</th>
                    <?php if ($canEdit): ?>
                        <th class="text-end">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="<?= $canEdit ? '8' : '7' ?>" class="text-center py-4 text-muted">
                            Belum ada item pengeluaran pada laporan ini.
                            <?php if ($canEdit): ?>
                                <div class="mt-2">
                                    <a href="/expenses/create" class="btn btn-sm btn-primary">+ Catat Pengeluaran Baru</a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <a href="/expenses/<?= (int) $item['expense_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($item['expense_number']) ?>
                                </a>
                            </td>
                            <td><?= View::formatDate($item['expense_date']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= View::e($item['category_name']) ?></span></td>
                            <td><?= View::e($item['description']) ?></td>
                            <td><?= View::e($item['merchant'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($item['receipt_stored_name'])): ?>
                                    <a href="/expenses/<?= (int) $item['expense_id'] ?>/receipt" target="_blank" class="badge bg-info text-white text-decoration-none" title="Lihat Bukti">
                                        Bukti Ada
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= View::formatRupiah($item['amount']) ?></strong></td>
                            <?php if ($canEdit): ?>
                                <td class="text-end">
                                    <form action="/reports/<?= (int) $report['report_id'] ?>/items/<?= (int) $item['expense_id'] ?>/remove" method="POST" class="d-inline" data-confirm="Lepaskan item pengeluaran ini dari laporan?">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Lepas item">Lepas</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Timeline & Comments -->
<div class="row g-4">
    <!-- Timeline / Approval History -->
    <div class="col-lg-6">
        <div class="card card-gt h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title h6 fw-bold mb-0">Riwayat Persetujuan (Workflow)</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($history)): ?>
                    <div class="text-muted small">Belum ada riwayat persetujuan.</div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($history as $h): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-light text-dark border fw-bold"><?= View::e($h['action']) ?></span>
                                    <span class="text-muted small"><?= View::formatDate($h['created_at'], 'd M Y H:i') ?></span>
                                </div>
                                <div class="small fw-semibold"><?= View::e($h['approver_name'] ?? $h['username']) ?></div>
                                <?php if (!empty($h['comment'])): ?>
                                    <div class="text-secondary small mt-1 p-2 bg-light rounded"><?= nl2br(View::e($h['comment'])) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="col-lg-6">
        <div class="card card-gt h-100 d-flex flex-column">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title h6 fw-bold mb-0">Diskusi &amp; Catatan</h5>
            </div>
            <div class="card-body p-4 flex-fill overflow-auto" style="max-height: 350px;">
                <?php if (empty($comments)): ?>
                    <div class="text-center py-4 text-muted small">Belum ada komentar pada laporan ini.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($comments as $com): ?>
                            <div class="p-3 rounded bg-light border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="small"><?= View::e($com['author_name'] ?? $com['username']) ?></strong>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= View::formatDate($com['created_at'], 'd M Y H:i') ?></span>
                                </div>
                                <div class="small text-secondary"><?= nl2br(View::e($com['comment_body'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-top p-3">
                <form action="/reports/<?= (int) $report['report_id'] ?>/comments" method="POST">
                    <?= Csrf::field() ?>
                    <div class="input-group">
                        <input type="text" name="comment_body" class="form-control form-control-sm" placeholder="Tulis komentar atau pertanyaan..." required>
                        <button type="submit" class="btn btn-sm btn-primary">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<?php if ($canDecide): ?>
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="/reports/<?= (int) $report['report_id'] ?>/approve" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title h6 fw-bold" id="approveModalLabel">Konfirmasi Persetujuan Laporan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Apakah Anda yakin ingin menyetujui laporan <strong><?= View::e($report['report_number']) ?></strong> sebesar <strong><?= View::formatRupiah($report['total_amount']) ?></strong>?
                        </p>
                        <div class="mb-3">
                            <label for="approve_comment" class="form-label small">Catatan Persetujuan (Opsional)</label>
                            <textarea name="comment" id="approve_comment" class="form-control form-control-sm" rows="3" placeholder="Disetujui untuk diproses keuangan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-success">Setujui Laporan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Refuse Modal -->
    <div class="modal fade" id="refuseModal" tabindex="-1" aria-labelledby="refuseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="/reports/<?= (int) $report['report_id'] ?>/refuse" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title h6 fw-bold" id="refuseModalLabel">Tolak Laporan Pengeluaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Laporan akan dikembalikan dengan status <strong>Ditolak (REFUSED)</strong>. Pemilik laporan dapat merevisi dan mengajukan ulang setelah memperbaiki rincian.
                        </p>
                        <div class="mb-3">
                            <label for="refuse_reason" class="form-label small">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="reason" id="refuse_reason" class="form-control form-control-sm" rows="3" required minlength="5" maxlength="2000" placeholder="Jelaskan alasan penolakan atau dokumen yang kurang..."></textarea>
                            <div class="form-text small">Wajib diisi minimal 5 karakter.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger">Tolak Laporan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
