<?php

declare(strict_types=1);

use App\Core\Url;
use App\Core\View;
?>

<?php
$canCreateReport = in_array($user['role_name'] ?? '', ['ADMIN', 'ADMIN_EXPENSE', 'Admin_HR'], true) || in_array('expenses.manage', $user['permissions'] ?? []) || in_array('expense.manage', $user['permissions'] ?? []);
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1"><?= ($user['role_name'] ?? '') === 'MANAGER' ? 'Laporan Pengeluaran Tim' : 'Laporan Pengeluaran' ?></h2>
        <p class="text-secondary small mb-0">Kelola dan pantau laporan pengeluaran gabungan multi-karyawan</p>
    </div>
    <?php if ($canCreateReport): ?>
        <div>
            <a href="<?= Url::to('/reports/create') ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Buat Laporan Gabungan</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Filters Toolbar -->
<div class="card card-gt mb-4">
    <div class="card-body p-3">
        <form action="<?= Url::to('/reports') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nomor/judul/karyawan..." value="<?= View::e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <?php
                    $statuses = ['DRAFT', 'SUBMITTED', 'APPROVED', 'REFUSED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID', 'CANCELLED'];
                    foreach ($statuses as $st): ?>
                        <option value="<?= $st ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= View::statusLabel($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="start_date" class="form-control form-control-sm" title="Dari Tanggal" value="<?= View::e($filters['start_date'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="end_date" class="form-control form-control-sm" title="Sampai Tanggal" value="<?= View::e($filters['end_date'] ?? '') ?>">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="<?= Url::to('/reports') ?>" class="btn btn-sm btn-outline-secondary" title="Reset">✕</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card card-gt">
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0">
            <thead>
                <tr>
                    <th>No. Laporan</th>
                    <th>Judul Laporan</th>
                    <th>Karyawan Terdaftar</th>
                    <th>Jumlah Item</th>
                    <th>Total Nominal</th>
                    <th>Status</th>
                    <th>Tanggal Dibuat</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            Belum ada laporan yang ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $item): ?>
                        <?php
                            $empCount = (int) ($item['employee_count'] ?? 0);
                            $empSummary = !empty($item['employees_summary']) ? $item['employees_summary'] : ($item['employee_name'] ?? '-');
                        ?>
                        <tr>
                            <td>
                                <a href="<?= Url::to('/reports/' . (int) $item['report_id']) ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($item['report_number']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><?= View::e($item['title']) ?></div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <?php if ($empCount > 1): ?>
                                        <span class="badge bg-primary rounded-pill"><?= $empCount ?> Karyawan</span>
                                    <?php endif; ?>
                                    <span class="text-truncate text-secondary small" style="max-width: 250px;" title="<?= View::e($empSummary) ?>">
                                        <?= View::e($empSummary) ?>
                                    </span>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= (int) ($item['item_count'] ?? 0) ?> item</span></td>
                            <td><strong><?= View::formatRupiah($item['total_amount'] ?? 0) ?></strong></td>
                            <td><?= View::statusBadge($item['status'] ?? 'DRAFT') ?></td>
                            <td><?= View::formatDate($item['created_at'] ?? null) ?></td>
                            <td class="text-end">
                                <a href="<?= Url::to('/reports/' . (int) $item['report_id']) ?>" class="btn btn-sm btn-outline-secondary">
                                    Buka Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top py-3 d-flex align-items-center justify-content-between">
            <div class="small text-muted">
                Total: <strong><?= $total ?></strong> laporan &bull; Halaman <?= $page ?> dari <?= $totalPages ?>
            </div>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= Url::to('/reports?' . http_build_query(array_merge($filters, ['page' => $page - 1]))) ?>">«</a>
                </li>
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= Url::to('/reports?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= Url::to('/reports?' . http_build_query(array_merge($filters, ['page' => $page + 1]))) ?>">»</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>
