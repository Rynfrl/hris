<?php

declare(strict_types=1);

use App\Core\View;

$role = $metrics['role'] ?? 'EMPLOYEE';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Dashboard</h2>
        <p class="text-secondary small mb-0">Ringkasan aktivitas dan status pengeluaran Anda</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($role === 'MANAGER'): ?>
            <a href="/approvals" class="btn btn-warning btn-sm d-flex align-items-center gap-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                <span>Tinjau Persetujuan Tim</span>
            </a>
        <?php elseif ($role !== 'ADMIN' && $role !== 'FINANCE'): ?>
            <a href="/expenses/create" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Catat Pengeluaran</span>
            </a>
            <a href="/reports/create" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Buat Laporan</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Metric Cards -->
<div class="row g-3 mb-4">
    <?php if ($role === 'EMPLOYEE'): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Pengeluaran Draft</div>
                <div class="metric-value"><?= (int) $metrics['draft_expenses_count'] ?></div>
                <div class="text-muted small mt-1"><?= View::formatRupiah($metrics['draft_expenses_total']) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Laporan Menunggu Persetujuan</div>
                <div class="metric-value"><?= (int) $metrics['pending_reports_count'] ?></div>
                <div class="text-muted small mt-1"><?= View::formatRupiah($metrics['pending_reports_total']) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Laporan Disetujui / Berjalan</div>
                <div class="metric-value"><?= (int) $metrics['approved_reports_count'] ?></div>
                <div class="text-muted small mt-1">Dalam proses keuangan</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Selesai Diganti (Paid)</div>
                <div class="metric-value text-success"><?= View::formatRupiah($metrics['paid_reports_total']) ?></div>
                <div class="text-muted small mt-1">Reimbursement selesai</div>
            </div>
        </div>
    <?php elseif ($role === 'MANAGER'): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Menunggu Persetujuan Anda</div>
                <div class="metric-value text-warning"><?= (int) $metrics['team_pending_count'] ?></div>
                <div class="text-muted small mt-1">Laporan dari tim Anda</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Nilai Menunggu</div>
                <div class="metric-value"><?= View::formatRupiah($metrics['team_pending_total']) ?></div>
                <div class="text-muted small mt-1">Perlu peninjauan</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Disetujui Bulan Ini</div>
                <div class="metric-value text-success"><?= (int) $metrics['team_approved_month'] ?></div>
                <div class="text-muted small mt-1">Laporan disetujui</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Anggota Tim Langsung</div>
                <div class="metric-value"><?= (int) $metrics['direct_reports_count'] ?></div>
                <div class="text-muted small mt-1">Direct reports</div>
            </div>
        </div>
    <?php elseif ($role === 'FINANCE'): ?>
        <div class="col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="metric-label">Menunggu Pembukuan (Posting)</div>
                <div class="metric-value text-primary"><?= (int) $metrics['awaiting_post_count'] ?></div>
                <div class="text-muted small mt-1">Total: <?= View::formatRupiah($metrics['awaiting_post_total']) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="metric-label">Antrean Reimbursement</div>
                <div class="metric-value text-warning"><?= (int) $metrics['awaiting_reimb_count'] ?></div>
                <div class="text-muted small mt-1">Total: <?= View::formatRupiah($metrics['awaiting_reimb_total']) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="metric-label">Reimbursement Dibayar Bulan Ini</div>
                <div class="metric-value text-success"><?= View::formatRupiah($metrics['paid_month_total']) ?></div>
                <div class="text-muted small mt-1">Telah dicatat lunas</div>
            </div>
        </div>
    <?php elseif ($role === 'ADMIN'): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Pengguna Aktif</div>
                <div class="metric-value"><?= (int) $metrics['users_count'] ?></div>
                <div class="text-muted small mt-1">Akun sistem</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Kategori Aktif</div>
                <div class="metric-value"><?= (int) $metrics['categories_count'] ?></div>
                <div class="text-muted small mt-1">Kategori biaya</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Pengeluaran</div>
                <div class="metric-value"><?= (int) $metrics['expenses_count'] ?></div>
                <div class="text-muted small mt-1">Seluruh record</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total Terbayar</div>
                <div class="metric-value text-success"><?= View::formatRupiah($metrics['total_paid_expenses']) ?></div>
                <div class="text-muted small mt-1">Status PAID</div>
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
                    <h5 class="card-title h6 fw-bold mb-0">Tren Pengeluaran 6 Bulan Terakhir</h5>
                    <small class="text-secondary">Statistik agregat nilai biaya per bulan</small>
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

<!-- Main Sections -->
<div class="row g-4">
    <?php if ($role === 'MANAGER' && !empty($metrics['pending_approval_reports'])): ?>
        <div class="col-12">
            <div class="card card-gt">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title h6 fw-bold mb-0">Antrean Persetujuan Tim</h5>
                    <a href="/approvals" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-gt mb-0">
                        <thead>
                            <tr>
                                <th>No. Laporan</th>
                                <th>Karyawan</th>
                                <th>Judul</th>
                                <th>Diajukan</th>
                                <th>Total</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metrics['pending_approval_reports'] as $item): ?>
                                <tr>
                                    <td><strong><?= View::e($item['report_number']) ?></strong></td>
                                    <td><?= View::e($item['employee_name']) ?></td>
                                    <td><?= View::e($item['title']) ?></td>
                                    <td><?= View::formatDate($item['submitted_at']) ?></td>
                                    <td><strong><?= View::formatRupiah($item['total_amount']) ?></strong></td>
                                    <td class="text-end">
                                        <a href="/reports/<?= (int) $item['report_id'] ?>" class="btn btn-sm btn-primary">Tinjau</a>
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
                    <a href="/finance/posting" class="btn btn-sm btn-outline-primary">Lihat Antrean</a>
                </div>
                <div class="p-3">
                    <?php if (empty($metrics['posting_queue'])): ?>
                        <div class="text-center py-4 text-muted small">Tidak ada laporan yang menunggu pembukuan.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($metrics['posting_queue'] as $post): ?>
                                <a href="/reports/<?= (int) $post['report_id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold"><?= View::e($post['report_number']) ?> &bull; <?= View::e($post['employee_name']) ?></div>
                                        <div class="text-muted small"><?= View::e($post['title']) ?></div>
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
                    <h5 class="card-title h6 fw-bold mb-0">Antrean Pembayaran Reimbursement</h5>
                    <a href="/reimbursements" class="btn btn-sm btn-outline-primary">Proses Pembayaran</a>
                </div>
                <div class="p-3">
                    <?php if (empty($metrics['reimbursement_queue'])): ?>
                        <div class="text-center py-4 text-muted small">Tidak ada reimbursement yang menunggu proses.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($metrics['reimbursement_queue'] as $rem): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold"><?= View::e($rem['report_number']) ?> &bull; <?= View::e($rem['employee_name']) ?></div>
                                        <div class="text-muted small"><?= View::e($rem['bank_name'] ?? 'Bank') ?> &bull; <?= View::statusBadge($rem['status']) ?></div>
                                    </div>
                                    <strong><?= View::formatRupiah($rem['amount']) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role === 'EMPLOYEE'): ?>
        <!-- Recent reports -->
        <div class="col-lg-6">
            <div class="card card-gt h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title h6 fw-bold mb-0">Laporan Terbaru Saya</h5>
                    <a href="/reports" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="p-3">
                    <?php if (empty($metrics['recent_reports'])): ?>
                        <div class="text-center py-4 text-muted small">Belum ada laporan biaya yang dibuat.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($metrics['recent_reports'] as $rep): ?>
                                <a href="/reports/<?= (int) $rep['report_id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold"><?= View::e($rep['report_number']) ?> &bull; <?= View::e($rep['title']) ?></div>
                                        <div class="text-muted small"><?= View::formatDate($rep['created_at']) ?> &bull; <?= View::statusBadge($rep['status']) ?></div>
                                    </div>
                                    <strong><?= View::formatRupiah($rep['total_amount']) ?></strong>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent expenses -->
        <div class="col-lg-6">
            <div class="card card-gt h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title h6 fw-bold mb-0">Pengeluaran Terbaru Saya</h5>
                    <a href="/expenses" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="p-3">
                    <?php if (empty($metrics['recent_expenses'])): ?>
                        <div class="text-center py-4 text-muted small">Belum ada transaksi pengeluaran.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($metrics['recent_expenses'] as $exp): ?>
                                <a href="/expenses/<?= (int) $exp['expense_id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold"><?= View::e($exp['description']) ?></div>
                                        <div class="text-muted small"><?= View::e($exp['category_name']) ?> &bull; <?= View::formatDate($exp['expense_date']) ?> &bull; <?= View::statusBadge($exp['status']) ?></div>
                                    </div>
                                    <strong><?= View::formatRupiah($exp['amount']) ?></strong>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role === 'ADMIN' && !empty($metrics['recent_activity'])): ?>
        <div class="col-12">
            <div class="card card-gt">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title h6 fw-bold mb-0">Aktivitas Sistem Terbaru</h5>
                    <a href="/settings/audit-logs" class="btn btn-sm btn-outline-primary">Audit Log Penuh</a>
                </div>
                <div class="p-3">
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
        const labels = trendData.map(function (d) { return d.label || d.month; });
        const values = trendData.map(function (d) { return parseFloat(d.total) || 0; });
        window.GTCharts.createTrendChart(trendCanvas, labels, values, 'Pengeluaran');
    }

    const catCanvas = document.getElementById('dashboardCategoryChart');
    if (catCanvas && Array.isArray(categoryData) && categoryData.length > 0) {
        const catLabels = categoryData.map(function (c) { return c.category_name; });
        const catValues = categoryData.map(function (c) { return parseFloat(c.total) || 0; });
        window.GTCharts.createDoughnutChart(catCanvas, catLabels, catValues);
    }
});
</script>
