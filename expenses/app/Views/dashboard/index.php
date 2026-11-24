<?php

declare(strict_types=1);

use App\Core\Url;
use App\Core\View;

$role = $metrics['role'] ?? 'ADMIN';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">
            <?php if ($role === 'MANAGER'): ?>
                Dashboard Persetujuan Manajer
            <?php elseif ($role === 'FINANCE'): ?>
                Dashboard Keuangan &amp; Reimbursement
            <?php else: ?>
                Dashboard Admin Expense
            <?php endif; ?>
        </h2>
        <p class="text-secondary small mb-0">Ringkasan aktivitas dan status operasional pengeluaran perusahaan</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($role === 'MANAGER'): ?>
            <a href="<?= Url::to('/approvals') ?>" class="btn btn-warning btn-sm d-flex align-items-center gap-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                <span>Tinjau Persetujuan Laporan</span>
            </a>
        <?php elseif ($role === 'FINANCE'): ?>
            <a href="<?= Url::to('/finance/posting') ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                <span>Antrean Pembukuan</span>
            </a>
            <a href="<?= Url::to('/reimbursements') ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                <span>Antrean Reimbursement</span>
            </a>
        <?php else: ?>
            <a href="<?= Url::to('/reports/create') ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Buat Laporan Gabungan</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Metric Cards Section -->
<div class="row g-3 mb-4">
    <?php if ($role === 'ADMIN'): ?>
        <!-- Section 1.1: 9 Metrics for Admin Expense -->
        <div class="col-sm-6 col-md-4 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Laporan</div>
                <div class="metric-value"><?= (int) ($metrics['total_reports'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Seluruh laporan</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Laporan Draft</div>
                <div class="metric-value text-secondary"><?= (int) ($metrics['draft_reports_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Belum diajukan</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Laporan Submitted</div>
                <div class="metric-value text-warning"><?= (int) ($metrics['submitted_reports_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Menunggu persetujuan</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Laporan Approved</div>
                <div class="metric-value text-info"><?= (int) ($metrics['approved_reports_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Disetujui manajer</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Laporan Refused</div>
                <div class="metric-value text-danger"><?= (int) ($metrics['refused_reports_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Ditolak / perlu revisi</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Laporan Posted</div>
                <div class="metric-value text-primary"><?= (int) ($metrics['posted_reports_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Sudah dibukukan</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Reimbursement Pending</div>
                <div class="metric-value text-warning"><?= (int) ($metrics['pending_reimb_reports_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Menunggu pencairan</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Laporan Paid</div>
                <div class="metric-value text-success"><?= (int) ($metrics['paid_reports_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Lunas dicairkan</div>
            </div>
        </div>
        <div class="col-12">
            <div class="metric-card bg-light border">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-label fw-bold">Total Nominal Pengeluaran</div>
                        <div class="metric-value text-dark fs-3"><?= View::formatRupiah($metrics['total_expenses_nominal'] ?? 0) ?></div>
                        <div class="text-muted small">Akumulasi seluruh laporan yang valid di sistem</div>
                    </div>
                    <div class="text-end text-muted small d-none d-md-block">
                        <div>Pengguna Sistem: <strong><?= (int) ($metrics['users_count'] ?? 0) ?></strong></div>
                        <div>Kategori Biaya: <strong><?= (int) ($metrics['categories_count'] ?? 0) ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($role === 'MANAGER'): ?>
        <!-- Section 2.1: 6 Metrics for Manager Expense -->
        <div class="col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="metric-label">Total Laporan Masuk</div>
                <div class="metric-value"><?= (int) ($metrics['total_incoming_reports'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Laporan diajukan ke manajer</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="metric-label">Laporan Menunggu Persetujuan</div>
                <div class="metric-value text-warning"><?= (int) ($metrics['pending_approval_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Perlu tindakan review</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="metric-label">Laporan Disetujui (Approved)</div>
                <div class="metric-value text-success"><?= (int) ($metrics['approved_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Telah disetujui manajer</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="metric-label">Laporan Ditolak (Refused)</div>
                <div class="metric-value text-danger"><?= (int) ($metrics['refused_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Dikembalikan ke Admin</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="metric-label">Total Nilai Menunggu Persetujuan</div>
                <div class="metric-value text-warning"><?= View::formatRupiah($metrics['pending_approval_total'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Nominal antrean submit</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="metric-label">Total Nilai Sudah Disetujui</div>
                <div class="metric-value text-success"><?= View::formatRupiah($metrics['approved_total'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Nominal diteruskan ke Finance</div>
            </div>
        </div>

    <?php elseif ($role === 'FINANCE'): ?>
        <!-- Section 3.1: 9 Metrics for Finance -->
        <div class="col-sm-6 col-md-4">
            <div class="metric-card">
                <div class="metric-label">Laporan Disetujui</div>
                <div class="metric-value text-info"><?= (int) ($metrics['approved_reports_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Dari Manager Expense</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="metric-card">
                <div class="metric-label">Menunggu Pembukuan (Posting)</div>
                <div class="metric-value text-primary"><?= (int) ($metrics['awaiting_post_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Siap dijurnal</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="metric-card">
                <div class="metric-label">Sudah Dibukukan (Posted)</div>
                <div class="metric-value"><?= (int) ($metrics['posted_reports_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Laporan selesai dibukukan</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="metric-card">
                <div class="metric-label">Reimbursement Pending</div>
                <div class="metric-value text-warning"><?= (int) ($metrics['reimb_pending_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Antrean siap dibayar</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="metric-card">
                <div class="metric-label">Reimbursement Sedang Diproses</div>
                <div class="metric-value text-secondary"><?= (int) ($metrics['reimb_processing_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Dalam proses transfer</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="metric-card">
                <div class="metric-label">Reimbursement Paid (Lunas)</div>
                <div class="metric-value text-success"><?= (int) ($metrics['reimb_paid_count'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Telah dicairkan penuh</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="metric-card">
                <div class="metric-label">Total Harus Dibayar</div>
                <div class="metric-value text-primary"><?= View::formatRupiah($metrics['total_must_pay'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Kewajiban aktif di antrean</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="metric-card">
                <div class="metric-label">Total Sudah Dibayar</div>
                <div class="metric-value text-success"><?= View::formatRupiah($metrics['total_paid'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Akumulasi pencairan lunas</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="metric-card">
                <div class="metric-label">Total Belum Dibayar</div>
                <div class="metric-value text-danger"><?= View::formatRupiah($metrics['total_unpaid'] ?? 0) ?></div>
                <div class="text-muted small mt-1">Sisa kewajiban laporan berjalan</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Charts Section -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-gt h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title h6 fw-bold mb-0">Tren Pengeluaran Perusahaan (6 Bulan Terakhir)</h5>
                    <small class="text-secondary">Statistik agregat nilai biaya laporan per bulan</small>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">IDR</span>
            </div>
            <div class="card-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="dashboardTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-gt h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title h6 fw-bold mb-0">Distribusi Kategori</h5>
                    <small class="text-secondary">Komposisi alokasi pengeluaran</small>
                </div>
                <span class="badge bg-secondary-subtle text-secondary border">Kategori</span>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <?php if (empty($metrics['category_breakdown'])): ?>
                    <div class="text-center py-5 text-muted small">
                        <svg class="mb-2 text-secondary opacity-50" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <div>Belum ada transaksi pengeluaran.</div>
                    </div>
                <?php else: ?>
                    <div style="height: 280px; position: relative;">
                        <canvas id="dashboardCategoryChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Operational Lists Section -->
<div class="row g-4">
    <?php if ($role === 'MANAGER' && !empty($metrics['pending_approval_reports'])): ?>
        <div class="col-12">
            <div class="card card-gt">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title h6 fw-bold mb-0">Antrean Persetujuan Laporan (SUBMITTED)</h5>
                    <a href="<?= Url::to('/approvals') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua Antrean</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-gt mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>No. Laporan</th>
                                <th>Judul Laporan</th>
                                <th>Karyawan Terlibat</th>
                                <th>Diajukan</th>
                                <th>Total Nominal</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metrics['pending_approval_reports'] as $item): ?>
                                <tr>
                                    <td>
                                        <a href="<?= Url::to('/reports/' . (int) $item['report_id']) ?>" class="fw-semibold text-primary text-decoration-none">
                                            <?= View::e($item['report_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= View::e($item['title']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary rounded-pill me-1"><?= (int) ($item['employee_count'] ?? 1) ?> Karyawan</span>
                                        <small class="text-muted"><?= View::e($item['employees_summary'] ?? '-') ?></small>
                                    </td>
                                    <td><?= View::formatDate($item['submitted_at']) ?></td>
                                    <td><strong><?= View::formatRupiah($item['total_amount']) ?></strong></td>
                                    <td class="text-end">
                                        <a href="<?= Url::to('/reports/' . (int) $item['report_id']) ?>" class="btn btn-sm btn-warning">Tinjau Keputusan</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role === 'FINANCE'): ?>
        <div class="col-md-6">
            <div class="card card-gt h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title h6 fw-bold mb-0">Siap Dibukukan (Posting)</h5>
                    <a href="<?= Url::to('/finance/posting') ?>" class="btn btn-sm btn-outline-primary">Lihat Antrean</a>
                </div>
                <div class="p-3">
                    <?php if (empty($metrics['posting_queue'])): ?>
                        <div class="text-center py-4 text-muted small">Tidak ada laporan yang menunggu pembukuan.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($metrics['posting_queue'] as $post): ?>
                                <a href="<?= Url::to('/reports/' . (int) $post['report_id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold"><?= View::e($post['report_number']) ?> &bull; <?= View::e($post['title']) ?></div>
                                        <div class="text-muted small">Karyawan: <?= View::e($post['employees_summary'] ?? '-') ?></div>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?= View::formatRupiah($post['total_amount']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-gt h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title h6 fw-bold mb-0">Antrean Reimbursement Satu Laporan</h5>
                    <a href="<?= Url::to('/reimbursements') ?>" class="btn btn-sm btn-outline-primary">Proses Bayar</a>
                </div>
                <div class="p-3">
                    <?php if (empty($metrics['reimbursement_queue'])): ?>
                        <div class="text-center py-4 text-muted small">Tidak ada reimbursement yang menunggu proses.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($metrics['reimbursement_queue'] as $rem): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold"><?= View::e($rem['report_number']) ?> &bull; <?= View::e($rem['title']) ?></div>
                                        <div class="text-muted small"><?= View::e($rem['recipient_name'] ?? 'Penanggung Jawab') ?> &bull; <?= View::statusBadge($rem['status']) ?></div>
                                    </div>
                                    <strong class="text-success"><?= View::formatRupiah($rem['amount']) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role === 'ADMIN'): ?>
        <div class="col-lg-6">
            <div class="card card-gt h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title h6 fw-bold mb-0">Laporan Gabungan Terbaru</h5>
                    <a href="<?= Url::to('/reports') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="p-3">
                    <?php if (empty($metrics['recent_reports'])): ?>
                        <div class="text-center py-4 text-muted small">Belum ada laporan pengeluaran.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($metrics['recent_reports'] as $rep): ?>
                                <a href="<?= Url::to('/reports/' . (int) $rep['report_id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold"><?= View::e($rep['report_number']) ?> &bull; <?= View::e($rep['title']) ?></div>
                                        <div class="text-muted small">
                                            <?= View::formatDate($rep['created_at']) ?> &bull;
                                            <?= (int) ($rep['employee_count'] ?? 1) ?> Karyawan &bull;
                                            <?= View::statusBadge($rep['status']) ?>
                                        </div>
                                    </div>
                                    <strong><?= View::formatRupiah($rep['total_amount']) ?></strong>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-gt h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title h6 fw-bold mb-0">Aktivitas Sistem Terbaru (Audit Log)</h5>
                    <a href="<?= Url::to('/settings/audit-logs') ?>" class="btn btn-sm btn-outline-primary">Audit Log Penuh</a>
                </div>
                <div class="p-3">
                    <?php if (empty($metrics['recent_activity'])): ?>
                        <div class="text-center py-4 text-muted small">Belum ada catatan aktivitas.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($metrics['recent_activity'] as $act): ?>
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-light text-dark border me-2"><?= View::e($act['action']) ?></span>
                                        <span><?= View::e($act['summary']) ?></span>
                                        <span class="text-muted small ms-2">(oleh <strong><?= View::e($act['username'] ?? 'Sistem') ?></strong>)</span>
                                    </div>
                                    <span class="text-muted small"><?= View::formatDate($act['created_at'], 'd M Y H:i') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const trendData = <?= json_encode($metrics['monthly_trend'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const categoryData = <?= json_encode($metrics['category_breakdown'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    const trendCanvas = document.getElementById('dashboardTrendChart');
    if (trendCanvas && Array.isArray(trendData) && trendData.length > 0) {
        const labels = trendData.map(function (d) { return d.label || d.month || d.ym; });
        const values = trendData.map(function (d) { return parseFloat(d.total) || 0; });
        if (window.GTCharts) {
            window.GTCharts.createTrendChart(trendCanvas, labels, values, 'Pengeluaran');
        }
    }

    const catCanvas = document.getElementById('dashboardCategoryChart');
    if (catCanvas && Array.isArray(categoryData) && categoryData.length > 0) {
        const catLabels = categoryData.map(function (c) { return c.category_name; });
        const catValues = categoryData.map(function (c) { return parseFloat(c.total) || 0; });
        if (window.GTCharts) {
            window.GTCharts.createDoughnutChart(catCanvas, catLabels, catValues);
        }
    }
});
</script>
