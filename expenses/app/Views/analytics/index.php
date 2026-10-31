<?php

declare(strict_types=1);

use App\Core\View;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Analitik &amp; Laporan</h2>
        <p class="text-secondary small mb-0">Statistik agregat dan ekspor data pengeluaran organisasi</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/exports/expenses.csv?<?= http_build_query(['start_date' => $startDate, 'end_date' => $endDate, 'status' => $status]) ?>" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Ekspor Expense CSV</span>
        </a>
        <a href="/exports/reports.csv?<?= http_build_query(['start_date' => $startDate, 'end_date' => $endDate, 'status' => $status]) ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Ekspor Laporan CSV</span>
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card card-gt mb-4">
    <div class="card-body p-3">
        <form action="/analytics" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= View::e($startDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= View::e($endDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Status Pengeluaran</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <?php foreach (['DRAFT', 'SUBMITTED', 'APPROVED', 'REFUSED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'] as $st): ?>
                        <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= View::statusLabel($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">Perbarui Data</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="metric-card">
            <div class="metric-label">Total Transaksi Pengeluaran</div>
            <div class="metric-value"><?= (int) ($summary['total_count'] ?? 0) ?></div>
            <div class="text-muted small mt-1">Pada rentang tanggal yang dipilih</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="metric-card">
            <div class="metric-label">Total Akumulasi Biaya</div>
            <div class="metric-value text-primary"><?= View::formatRupiah($summary['total_amount'] ?? 0) ?></div>
            <div class="text-muted small mt-1">Berdasarkan tanggal transaksi pengeluaran</div>
        </div>
    </div>
</div>

<!-- Visual Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card card-gt h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title h6 fw-bold mb-0">Grafik Pengeluaran per Bulan</h5>
                    <small class="text-secondary">Akumulasi biaya per periode bulan</small>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Bar Chart</span>
            </div>
            <div class="card-body">
                <?php if (empty($byMonth)): ?>
                    <div class="text-center py-5 text-muted small">
                        Tidak ada data grafik untuk periode ini.
                    </div>
                <?php else: ?>
                    <div style="height: 280px; position: relative;">
                        <canvas id="analyticsMonthlyChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-gt h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title h6 fw-bold mb-0">Komposisi Pengeluaran per Kategori</h5>
                    <small class="text-secondary">Proporsi pengeluaran berdasarkan kategori</small>
                </div>
                <span class="badge bg-secondary-subtle text-secondary border">Doughnut</span>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <?php if (empty($byCategory)): ?>
                    <div class="text-center py-5 text-muted small">
                        Tidak ada data grafik untuk periode ini.
                    </div>
                <?php else: ?>
                    <div style="height: 280px; position: relative;">
                        <canvas id="analyticsCategoryChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Breakdown by Category -->
    <div class="col-lg-6">
        <div class="card card-gt h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title h6 fw-bold mb-0">Rincian Berdasarkan Kategori</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-gt mb-0">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th class="text-end">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($byCategory)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted small">Tidak ada data untuk periode ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($byCategory as $cat): ?>
                                <tr>
                                    <td>
                                        <strong><?= View::e($cat['category_name']) ?></strong>
                                        <span class="badge bg-light text-dark border ms-1"><?= View::e($cat['category_code']) ?></span>
                                    </td>
                                    <td><?= (int) $cat['count'] ?> transaksi</td>
                                    <td class="text-end"><strong><?= View::formatRupiah($cat['total']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Monthly Breakdown -->
    <div class="col-lg-6">
        <div class="card card-gt h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title h6 fw-bold mb-0">Tren Pengeluaran Bulanan</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-gt mb-0">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Jumlah Transaksi</th>
                            <th class="text-end">Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($byMonth)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted small">Tidak ada data untuk periode ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($byMonth as $m): ?>
                                <tr>
                                    <td><strong><?= View::e($m['month']) ?></strong></td>
                                    <td><?= (int) $m['count'] ?> transaksi</td>
                                    <td class="text-end"><strong class="text-primary"><?= View::formatRupiah($m['total']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthlyData = <?= json_encode($byMonth ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const categoryData = <?= json_encode($byCategory ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    const monthCanvas = document.getElementById('analyticsMonthlyChart');
    if (monthCanvas && Array.isArray(monthlyData) && monthlyData.length > 0) {
        const labels = monthlyData.map(function (m) { return m.month; });
        const values = monthlyData.map(function (m) { return parseFloat(m.total) || 0; });
        window.GTCharts.createBarChart(monthCanvas, labels, values, 'Total Pengeluaran');
    }

    const catCanvas = document.getElementById('analyticsCategoryChart');
    if (catCanvas && Array.isArray(categoryData) && categoryData.length > 0) {
        const catLabels = categoryData.map(function (c) { return c.category_name; });
        const catValues = categoryData.map(function (c) { return parseFloat(c.total) || 0; });
        window.GTCharts.createDoughnutChart(catCanvas, catLabels, catValues);
    }
});
</script>
