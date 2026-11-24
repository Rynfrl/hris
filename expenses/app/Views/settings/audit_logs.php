<?php

declare(strict_types=1);

use App\Core\View;
use App\Core\Url;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Audit Log Sistem</h2>
        <p class="text-secondary small mb-0">Catatan jejak perubahan data dan aktivitas penting pada modul Expenses</p>
    </div>
</div>

<div class="card card-gt mb-4">
    <div class="card-body p-3">
        <form action="<?= Url::to('/settings/audit-logs') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="module" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Modul</option>
                    <option value="expenses" <?= ($module ?? '') === 'expenses' ? 'selected' : '' ?>>expenses (Pengeluaran)</option>
                    <option value="expense_reports" <?= ($module ?? '') === 'expense_reports' ? 'selected' : '' ?>>expense_reports (Laporan)</option>
                    <option value="reimbursements" <?= ($module ?? '') === 'reimbursements' ? 'selected' : '' ?>>reimbursements (Pembayaran)</option>
                    <option value="users" <?= ($module ?? '') === 'users' ? 'selected' : '' ?>>users (Pengguna & Akses)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
            <?php if (!empty($module)): ?>
                <div class="col-md-2">
                    <a href="<?= Url::to('/settings/audit-logs') ?>" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card card-gt">
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0">
            <thead>
                <tr>
                    <th style="min-width: 150px;">Waktu</th>
                    <th style="min-width: 140px;">Pengguna</th>
                    <th style="min-width: 140px;">Aksi</th>
                    <th style="min-width: 130px;">Modul</th>
                    <th style="min-width: 90px;">Record ID</th>
                    <th style="min-width: 250px;">Perubahan / Ringkasan Aktivitas</th>
                    <th style="min-width: 120px;">Alamat IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Belum ada audit log tercatat.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-nowrap small text-muted"><?= View::formatDate($log['created_at'], 'd M Y H:i:s') ?></td>
                            <td>
                                <strong><?= View::e($log['username'] ?? 'Sistem') ?></strong>
                                <?php if (!empty($log['employee_name'])): ?>
                                    <div class="text-muted small"><?= View::e($log['employee_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= View::auditActionBadge($log['action'] ?? '') ?></td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= View::e($log['module'] ?? 'general') ?></span></td>
                            <td>
                                <?php if (!empty($log['record_id'])): ?>
                                    <span class="badge bg-light text-muted border">#<?= (int) $log['record_id'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= View::formatAuditChange($log['summary'] ?? null, $log['old_value'] ?? null, $log['new_value'] ?? null) ?></td>
                            <td><span class="text-muted small"><?= View::e($log['ip_address'] ?? '-') ?></span></td>
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
                Halaman <?= $page ?> dari <?= $totalPages ?> (Total <?= number_format($total, 0, ',', '.') ?> record)
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_filter(['module' => $module, 'page' => $page - 1])) ?>">Sebelumnya</a></li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link">Sebelumnya</span></li>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_filter(['module' => $module, 'page' => $p])) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_filter(['module' => $module, 'page' => $page + 1])) ?>">Selanjutnya</a></li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link">Selanjutnya</span></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
