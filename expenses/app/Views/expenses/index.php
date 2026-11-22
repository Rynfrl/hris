<?php

declare(strict_types=1);

use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1"><?= ($user['role_name'] ?? '') === 'MANAGER' ? 'Pengeluaran Tim' : 'Daftar Seluruh Pengeluaran' ?></h2>
        <p class="text-secondary small mb-0"><?= ($user['role_name'] ?? '') === 'MANAGER' ? 'Pantau catatan biaya dan bukti transaksi dalam laporan pengeluaran' : 'Seluruh pengeluaran dicatat terpusat melalui Laporan Gabungan' ?></p>
    </div>
    <?php if (in_array($user['role_name'] ?? '', ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || in_array('expenses.manage', $user['permissions'] ?? [])): ?>
        <div>
            <a href="/reports/create" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Buat Laporan Baru</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Filters Toolbar -->
<div class="card card-gt mb-4">
    <div class="card-body p-3">
        <form action="/expenses" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nomor/deskripsi/merchant..." value="<?= View::e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
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
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['category_id'] ?>" <?= ((int) ($filters['category_id'] ?? 0) === (int) $cat['category_id']) ? 'selected' : '' ?>>
                            <?= View::e($cat['category_name']) ?>
                        </option>
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
                <a href="/expenses" class="btn btn-sm btn-outline-secondary" title="Reset">✕</a>
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
                    <th>No. Pengeluaran</th>
                    <th>Tanggal</th>
                    <th>Karyawan</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Nominal</th>
                    <th>Bukti</th>
                    <th>Status</th>
                    <th>Laporan</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            Belum ada pengeluaran yang ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenses as $item): ?>
                        <tr>
                            <td>
                                <a href="/expenses/<?= (int) $item['expense_id'] ?>" class="fw-semibold text-primary text-decoration-none">
                                    <?= View::e($item['expense_number']) ?>
                                </a>
                            </td>
                            <td><?= View::formatDate($item['expense_date'] ?? null) ?></td>
                            <td><?= View::e($item['employee_name'] ?? '-') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= View::e($item['category_name'] ?? '-') ?></span></td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?= View::e($item['description'] ?? '') ?>">
                                    <?= View::e($item['description'] ?? '-') ?>
                                </div>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <?php if (!empty($item['merchant'])): ?>
                                        <span class="text-muted small"><?= View::e($item['merchant']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['bank_name'])): ?>
                                        <span class="badge bg-light text-primary border" style="font-size: 0.7rem;"><?= View::e($item['bank_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><strong><?= View::formatRupiah($item['amount'] ?? 0) ?></strong></td>
                            <td>
                                <?php if (!empty($item['receipt_stored_name'])): ?>
                                    <a href="/expenses/<?= (int) $item['expense_id'] ?>/receipt" target="_blank" class="badge bg-info text-white text-decoration-none" title="Lihat Bukti">
                                        Ada Bukti
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= View::statusBadge($item['status'] ?? 'DRAFT') ?></td>
                            <td>
                                <?php if (!empty($item['report_id'])): ?>
                                    <a href="/reports/<?= (int) $item['report_id'] ?>" class="text-decoration-none small">
                                        <?= View::e($item['report_number']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">Belum terikat</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="/expenses/<?= (int) $item['expense_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    Detail
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
        <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Menampilkan halaman <?= $page ?> dari <?= $totalPages ?> (Total <?= $total ?> item)
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>">Sebelumnya</a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])) ?>">Selanjutnya</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
