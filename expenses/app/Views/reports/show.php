<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$reportEmployees = $reportEmployees ?? [];
$items = $items ?? [];
$history = $history ?? [];
$comments = $comments ?? [];
$categories = $categories ?? [];
$activeEmployees = $activeEmployees ?? [];
$unassignedExpenses = $unassignedExpenses ?? [];
$postingInfo = $postingInfo ?? null;
$reimbursements = $reimbursements ?? [];

$isDraft = ($report['status'] === 'DRAFT');
$isSubmitted = ($report['status'] === 'SUBMITTED');
$isApproved = ($report['status'] === 'APPROVED');
$isRefused = ($report['status'] === 'REFUSED');
$isPosted = in_array($report['status'], ['POSTED', 'REIMBURSEMENT_PENDING', 'PAID'], true);
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="h4 fw-bold mb-0"><?= View::e($report['report_number']) ?></h2>
            <?= View::statusBadge($report['status']) ?>
        </div>
        <p class="text-secondary small mb-0">
            Dibuat oleh: <strong><?= View::e($report['creator_name'] ?? $report['creator_username'] ?? 'Admin') ?></strong> &bull;
            Dibuat: <?= View::formatDate($report['created_at'], 'd M Y H:i') ?> &bull;
            Terlibat: <strong><?= count($reportEmployees) ?> Karyawan</strong>
        </p>
    </div>

    <!-- Workflow Action Buttons -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= Url::to('/reports') ?>" class="btn btn-outline-secondary btn-sm">Kembali</a>

        <?php if ($canEdit): ?>
            <a href="<?= Url::to('/reports/' . (int) $report['report_id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm">Edit Laporan</a>
        <?php endif; ?>

        <?php if ($canSubmit): ?>
            <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/submit') ?>" method="POST" data-confirm="Ajukan laporan ini untuk persetujuan manajer?">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-primary btn-sm" <?= (empty($items) || empty($reportEmployees)) ? 'disabled title="Tambahkan minimal satu karyawan dan satu transaksi"' : '' ?>>
                    Ajukan Laporan Gabungan
                </button>
            </form>
        <?php endif; ?>

        <?php if ($canRevise): ?>
            <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/revise') ?>" method="POST" data-confirm="Kembalikan laporan ini ke status draft untuk direvisi?">
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
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#postingModal">
                Bukukan (Posting Finance)
            </button>
        <?php endif; ?>

        <?php if ($canReimburse && $report['status'] === 'POSTED'): ?>
            <a href="<?= Url::to('/reimbursements') ?>" class="btn btn-warning btn-sm">
                Lihat Antrean Reimbursement
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Refusal banner -->
<?php if (!empty($report['rejection_reason'])): ?>
    <div class="alert alert-danger mb-4 shadow-sm border-start border-danger border-4">
        <h6 class="alert-heading fw-bold mb-1">Catatan Penolakan Manajer:</h6>
        <p class="mb-0"><?= nl2br(View::e($report['rejection_reason'])) ?></p>
        <?php if (!empty($report['refused_at'])): ?>
            <div class="text-muted small mt-1">Ditolak pada: <?= View::formatDate($report['refused_at'], 'd M Y H:i') ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Posting / Pembukuan banner -->
<?php if ($postingInfo): ?>
    <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center mb-4 shadow-sm">
        <div>
            <div class="fw-bold text-success mb-1">✓ Laporan Telah Dibukukan (POSTED)</div>
            <div class="small">
                No. Jurnal: <strong><?= View::e($postingInfo['journal_reference']) ?></strong> &bull;
                Tanggal Pembukuan: <strong><?= View::formatDate($postingInfo['posting_date']) ?></strong> &bull;
                Dibukukan Oleh: <strong><?= View::e($postingInfo['posted_by_name'] ?? $postingInfo['posted_by_username']) ?></strong>
                <?php if (!empty($postingInfo['notes'])): ?>
                    <br><span class="text-muted">Catatan: <?= View::e($postingInfo['notes']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="/reimbursements" class="btn btn-sm btn-outline-success">Buka Modul Reimbursement</a>
        </div>
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
                        Status Alur: <strong><?= View::statusLabel($report['status']) ?></strong>
                        <?php if (!empty($report['submitted_at'])): ?>
                            (Diajukan <?= View::formatDate($report['submitted_at'], 'd M Y H:i') ?>)
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-gt h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-center text-center">
                <div class="metric-label mb-1">Total Pengeluaran Gabungan</div>
                <div class="fs-2 fw-bold text-primary mb-1"><?= View::formatRupiah($report['total_amount']) ?></div>
                <div class="text-muted small">
                    <strong><?= count($reportEmployees) ?></strong> Karyawan &bull; <strong><?= count($items) ?></strong> Transaksi
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 1: KARYAWAN TERDAFTAR (1 Report -> Banyak Karyawan) -->
<div class="card card-gt mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title h6 fw-bold mb-0">Daftar Karyawan dalam Laporan Gabungan</h5>
            <small class="text-muted">Setiap karyawan memiliki rincian transaksi pengeluaran. Reimbursement dicairkan satu kali penuh untuk satu laporan gabungan.</small>
        </div>
        <?php if ($canEdit): ?>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addEmployeeCollapse">
                + Tambah Karyawan Lain
            </button>
        <?php endif; ?>
    </div>

    <!-- Add Employee Collapse Form -->
    <?php if ($canEdit): ?>
        <div class="collapse border-bottom bg-light p-3" id="addEmployeeCollapse">
            <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/employees') ?>" method="POST" class="row g-2 align-items-center">
                <?= Csrf::field() ?>
                <div class="col-md-9">
                    <select name="employee_id" class="form-select form-select-sm" required>
                        <option value="">-- Pilih Karyawan Aktif HRIS --</option>
                        <?php
                            $currentEmpIds = array_column($reportEmployees, 'employee_id');
                            foreach ($activeEmployees as $ae):
                                if (in_array((int) $ae['employee_id'], $currentEmpIds, true)) continue;
                        ?>
                            <option value="<?= (int) $ae['employee_id'] ?>">
                                <?= View::e($ae['full_name']) ?> (<?= View::e($ae['employee_number'] ?? ('EMP-' . $ae['employee_id'])) ?>) - <?= View::e($ae['department_name'] ?? 'Umum') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Tambahkan ke Laporan</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>No.</th>
                    <th>Nama Karyawan</th>
                    <th>NIK / No. Karyawan</th>
                    <th>Divisi / Departemen</th>
                    <th>Rekening Bank</th>
                    <th class="text-center">Jml Transaksi</th>
                    <th class="text-end">Subtotal Nominal</th>
                    <?php if ($isPosted): ?>
                        <th>Status Reimbursement</th>
                    <?php endif; ?>
                    <?php if ($canEdit): ?>
                        <th class="text-end">Aksi Transaksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportEmployees)): ?>
                    <tr>
                        <td colspan="<?= ($isPosted ? 8 : 7) + ($canEdit ? 1 : 0) ?>" class="text-center py-4 text-muted">
                            Belum ada karyawan yang ditambahkan ke laporan ini. Silakan pilih minimal 1 karyawan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($reportEmployees as $repEmp): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong class="text-dark"><?= View::e($repEmp['employee_name_snapshot']) ?></strong>
                            </td>
                            <td><code><?= View::e($repEmp['employee_number_snapshot']) ?></code></td>
                            <td><span class="badge bg-secondary"><?= View::e($repEmp['department_name_snapshot'] ?? 'Umum') ?></span></td>
                            <td class="small text-muted">
                                <?= !empty($repEmp['bank_name']) ? View::e($repEmp['bank_name'] . ' - ' . $repEmp['bank_account']) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><?= (int) $repEmp['item_count'] ?> item</span>
                            </td>
                            <td class="text-end fw-bold text-primary">
                                <?= View::formatRupiah($repEmp['subtotal_amount']) ?>
                            </td>
                            <?php if ($isPosted): ?>
                                <td>
                                    <?php if (!empty($repEmp['reimbursement_status'])): ?>
                                        <?= View::statusBadge($repEmp['reimbursement_status']) ?>
                                        <div class="small text-muted">
                                            Dibayar: <?= View::formatRupiah($repEmp['paid_amount'] ?? 0) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pending</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <?php if ($canEdit): ?>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-record-expense"
                                                data-bs-toggle="modal"
                                                data-bs-target="#createExpenseModal"
                                                data-rep-emp-id="<?= (int) $repEmp['report_employee_id'] ?>"
                                                data-emp-name="<?= View::e($repEmp['employee_name_snapshot']) ?>">
                                            + Catat Transaksi
                                        </button>
                                        <?php if ((int) $repEmp['item_count'] === 0): ?>
                                            <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/employees/' . (int) $repEmp['report_employee_id'] . '/remove') ?>" method="POST" data-confirm="Hapus karyawan ini dari laporan?">
                                                <?= Csrf::field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Karyawan">✕</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SECTION 2: DETAIL TRANSAKSI PENGELUARAN PER KARYAWAN -->
<div class="card card-gt mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title h6 fw-bold mb-0">Rincian Transaksi Pengeluaran</h5>
            <small class="text-muted">Detail bukti transaksi per karyawan beserta kategori dan metode pembayaran</small>
        </div>
        <?php if ($canEdit && !empty($unassignedExpenses)): ?>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#linkExistingExpenseCollapse">
                + Hubungkan Pengeluaran Draft Tersimpan
            </button>
        <?php endif; ?>
    </div>

    <!-- Link Existing Draft Expenses Collapse -->
    <?php if ($canEdit && !empty($unassignedExpenses)): ?>
        <div class="collapse border-bottom bg-light p-3" id="linkExistingExpenseCollapse">
            <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/items') ?>" method="POST" class="row g-2 align-items-center">
                <?= Csrf::field() ?>
                <div class="col-md-9">
                    <select name="expense_id" class="form-select form-select-sm" required>
                        <option value="">-- Pilih Pengeluaran Draft Karyawan yang Belum Terdaftar --</option>
                        <?php foreach ($unassignedExpenses as $un): ?>
                            <option value="<?= (int) $un['expense_id'] ?>">
                                <?= View::e($un['employee_name'] ?? 'Karyawan') ?> &bull; <?= View::e($un['expense_number']) ?> &bull; <?= View::formatDate($un['expense_date']) ?> &bull; <?= View::e($un['category_name']) ?>: <?= View::e($un['description']) ?> (<?= View::formatRupiah($un['amount']) ?>)
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
        <table class="table table-hover table-gt mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>No. Pengeluaran</th>
                    <th>Karyawan Pemilik</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Deskripsi & Catatan</th>
                    <th>Metode & Bank</th>
                    <th>Bukti Struk</th>
                    <th class="text-end">Nominal</th>
                    <?php if ($canEdit): ?>
                        <th class="text-end">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="<?= $canEdit ? 9 : 8 ?>" class="text-center py-5 text-muted">
                            Belum ada transaksi pengeluaran pada laporan ini.
                            <?php if ($canEdit && !empty($reportEmployees)): ?>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createExpenseModal">
                                        + Catat Transaksi Pengeluaran Karyawan
                                    </button>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <a href="<?= Url::to('/expenses/' . (int) $item['expense_id']) ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($item['expense_number']) ?>
                                </a>
                            </td>
                            <td>
                                <strong><?= View::e($item['employee_name_snapshot'] ?? $item['current_employee_name'] ?? '-') ?></strong>
                                <div class="small text-muted"><?= View::e($item['department_name_snapshot'] ?? '') ?></div>
                            </td>
                            <td><?= View::formatDate($item['expense_date']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= View::e($item['category_name']) ?></span></td>
                            <td>
                                <div class="fw-medium text-dark"><?= View::e($item['description']) ?></div>
                                <?php if (!empty($item['merchant'])): ?>
                                    <small class="text-muted d-block">Merchant: <?= View::e($item['merchant']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($item['notes'])): ?>
                                    <small class="text-muted fst-italic d-block"><?= View::e($item['notes']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= View::e($item['payment_method']) ?></span>
                                <?php if (!empty($item['bank_name'])): ?>
                                    <small class="d-block text-muted"><?= View::e($item['bank_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['receipt_path'])): ?>
                                    <a href="<?= Url::to('/expenses/' . (int) $item['expense_id'] . '/receipt') ?>" target="_blank" class="btn btn-xs btn-outline-primary" style="font-size: 0.75rem;">
                                        Lihat Bukti
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">Tanpa Bukti</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                <?= View::formatRupiah($item['amount']) ?>
                            </td>
                            <?php if ($canEdit): ?>
                                <td class="text-end">
                                    <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/items/' . (int) $item['expense_id'] . '/remove') ?>" method="POST" data-confirm="Keluarkan transaksi ini dari laporan?">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Lepaskan dari laporan">✕</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="7" class="text-end">TOTAL KESELURUHAN LAPORAN:</td>
                    <td class="text-end text-primary fs-5"><?= View::formatRupiah($report['total_amount']) ?></td>
                    <?php if ($canEdit): ?><td></td><?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- SECTION 3: TIMELINE PERSETUJUAN & DISKUSI -->
<div class="row g-4">
    <!-- Approval History -->
    <div class="col-lg-6">
        <div class="card card-gt h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title h6 fw-bold mb-0">Riwayat Alur Persetujuan (Audit Workflow)</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($history)): ?>
                    <p class="text-muted small mb-0">Belum ada riwayat persetujuan tercatat.</p>
                <?php else: ?>
                    <ul class="timeline-gt mb-0">
                        <?php foreach ($history as $h): ?>
                            <li class="timeline-item mb-3 pb-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong><?= View::statusBadge($h['action']) ?></strong>
                                    <span class="small text-muted"><?= View::formatDate($h['created_at'], 'd M Y H:i') ?></span>
                                </div>
                                <div class="small">
                                    Aktor: <strong><?= View::e($h['approver_name'] ?? $h['username']) ?></strong>
                                </div>
                                <?php if (!empty($h['comment'])): ?>
                                    <div class="small text-secondary mt-1 bg-light p-2 rounded">
                                        <?= nl2br(View::e($h['comment'])) ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Comments & Discussion -->
    <div class="col-lg-6">
        <div class="card card-gt h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title h6 fw-bold mb-0">Catatan & Komentar</h5>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div class="comments-list mb-3" style="max-height: 250px; overflow-y: auto;">
                    <?php if (empty($comments)): ?>
                        <p class="text-muted small mb-0">Belum ada komentar untuk laporan ini.</p>
                    <?php else: ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="p-2 mb-2 rounded bg-light border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="small"><?= View::e($c['author_name'] ?? $c['username']) ?></strong>
                                    <span class="small text-muted" style="font-size: 0.7rem;"><?= View::formatDate($c['created_at'], 'd/m/Y H:i') ?></span>
                                </div>
                                <div class="small text-dark"><?= nl2br(View::e($c['comment_body'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/comments') ?>" method="POST" class="mt-auto">
                    <?= Csrf::field() ?>
                    <div class="input-group">
                        <input type="text" name="comment_body" class="form-control form-control-sm" placeholder="Tulis komentar atau instruksi..." required>
                        <button type="submit" class="btn btn-sm btn-primary">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: CATAT TRANSAKSI LANGSUNG UNTUK KARYAWAN (When Draft) -->
<?php if ($canEdit): ?>
<div class="modal fade" id="createExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/expenses/create') ?>" method="POST" enctype="multipart/form-data">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Catat Transaksi Pengeluaran Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Pilih Karyawan Pemilik Transaksi <span class="text-danger">*</span></label>
                            <select name="report_employee_id" id="modalExpenseReportEmployeeId" class="form-select" required>
                                <option value="">-- Pilih Karyawan Terdaftar --</option>
                                <?php foreach ($reportEmployees as $re): ?>
                                    <option value="<?= (int) $re['report_employee_id'] ?>">
                                        <?= View::e($re['employee_name_snapshot']) ?> (<?= View::e($re['employee_number_snapshot']) ?>) - <?= View::e($re['department_name_snapshot'] ?? 'Umum') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kategori Pengeluaran <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int) $cat['category_id'] ?>"><?= View::e($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" class="form-control" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nominal (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="1" required placeholder="Contoh: 150000">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Metode Pembayaran</label>
                            <select name="payment_method" class="form-select">
                                <option value="BANK_TRANSFER">Transfer Bank</option>
                                <option value="CASH">Tunai (Cash)</option>
                                <option value="CORPORATE_CARD">Kartu Perusahaan</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Bank (Jika Transfer)</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="BCA / Mandiri / BNI">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Toko / Merchant</label>
                            <input type="text" name="merchant" class="form-control" placeholder="Contoh: SPBU Pertamina / Hotel Santika">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi Transaksi <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control" required placeholder="Contoh: Pembelian tiket kereta dinas ke Bandung" minlength="3">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Upload Bukti Struk / Kuitansi</label>
                            <input type="file" name="receipt" class="form-control" accept="image/*,.pdf">
                            <small class="text-muted">JPG, PNG, PDF max 5MB</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Catatan Tambahan</label>
                            <input type="text" name="notes" class="form-control" placeholder="Keterangan opsional">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL: APPROVE (Manager) -->
<?php if ($canDecide): ?>
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/approve') ?>" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Persetujuan Laporan Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p>Anda akan menyetujui laporan gabungan <strong><?= View::e($report['report_number']) ?></strong> dengan total nominal <strong><?= View::formatRupiah($report['total_amount']) ?></strong> mencakup <strong><?= count($reportEmployees) ?> karyawan</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label">Catatan Persetujuan (Opsional)</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="Disetujui untuk diproses ke bagian Keuangan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4">Setujui Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: REFUSE / REJECT (Manager) -->
<div class="modal fade" id="refuseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/refuse') ?>" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">Tolak Laporan Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small">Alasan penolakan wajib diisi agar pembuat laporan dapat melakukan perbaikan dan mengajukan revisi ulang.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" required minlength="5" placeholder="Jelaskan alasan penolakan secara spesifik, misal: Bukti kuitansi karyawan Andi belum resmi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Tolak Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL: POSTING / PEMBUKUAN (Finance) -->
<?php if ($canPost): ?>
<div class="modal fade" id="postingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= Url::to('/reports/' . (int) $report['report_id'] . '/post') ?>" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Pembukuan Laporan (Posting Finance)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p>Pembukuan akan mencatat laporan ke buku besar akuntansi dan secara otomatis membuat satu antrean reimbursement untuk seluruh total pengeluaran laporan.</p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor Referensi Jurnal <span class="text-danger">*</span></label>
                        <input type="text" name="journal_reference" class="form-control" required value="JRN-EXP-<?= date('Ym') ?>-<?= sprintf('%04d', (int) $report['report_id']) ?>">
                        <small class="text-muted">Nomor voucher/jurnal pembukuan akuntansi</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Pembukuan <span class="text-danger">*</span></label>
                        <input type="date" name="posting_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan Pembukuan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Keterangan pembukuan keuangan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Proses Pembukuan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const recordButtons = document.querySelectorAll('.btn-record-expense');
    const modalSelect = document.getElementById('modalExpenseReportEmployeeId');
    recordButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const repEmpId = this.getAttribute('data-rep-emp-id');
            if (modalSelect && repEmpId) {
                modalSelect.value = repEmpId;
            }
        });
    });
});
</script>
