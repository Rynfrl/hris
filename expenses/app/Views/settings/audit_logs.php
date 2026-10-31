<?php

declare(strict_types=1);

use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Audit Log Sistem</h2>
        <p class="text-secondary small mb-0">Catatan jejak perubahan data dan aktivitas penting pada modul Expenses</p>
    </div>
</div>

<div class="card card-gt mb-4">
    <div class="card-body p-3">
        <form action="/settings/audit-logs" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="module" class="form-select form-select-sm">
                    <option value="">Semua Modul</option>
                    <option value="expenses" <?= ($module ?? '') === 'expenses' ? 'selected' : '' ?>>expenses (Pengeluaran)</option>
                    <option value="expense_reports" <?= ($module ?? '') === 'expense_reports' ? 'selected' : '' ?>>expense_reports (Laporan)</option>
                    <option value="reimbursements" <?= ($module ?? '') === 'reimbursements' ? 'selected' : '' ?>>reimbursements (Pembayaran)</option>
                    <option value="users" <?= ($module ?? '') === 'users' ? 'selected' : '' ?>>users (Pengguna)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-gt">
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aksi</th>
                    <th>Modul</th>
                    <th>Record ID</th>
                    <th>Perubahan (Diff / Nilai Baru)</th>
                    <th>Alamat IP</th>
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
                            <td><?= View::formatDate($log['created_at'], 'd M Y H:i:s') ?></td>
                            <td>
                                <strong><?= View::e($log['username'] ?? 'Sistem') ?></strong>
                                <?php if (!empty($log['employee_name'])): ?>
                                    <div class="text-muted small"><?= View::e($log['employee_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= View::e($log['action']) ?></span></td>
                            <td><code><?= View::e($log['module']) ?></code></td>
                            <td>#<?= (int) $log['record_id'] ?></td>
                            <td>
                                <div style="max-width: 280px; font-size: 0.75rem;" class="text-truncate font-monospace" title="<?= View::e($log['new_value'] ?? '') ?>">
                                    <?= View::e($log['new_value'] ?? '-') ?>
                                </div>
                            </td>
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
                Halaman <?= $page ?> dari <?= $totalPages ?> (Total <?= $total ?> record)
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(['module' => $module, 'page' => $page - 1]) ?>">Sebelumnya</a></li>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(['module' => $module, 'page' => $page + 1]) ?>">Selanjutnya</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
