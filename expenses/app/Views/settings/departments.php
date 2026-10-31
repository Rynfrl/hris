<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Manajemen Departemen</h2>
        <p class="text-secondary small mb-0">Kelola master departemen dan manajer terkait</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createDeptModal">
        + Tambah Departemen
    </button>
</div>

<div class="card card-gt">
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Departemen</th>
                    <th>Manajer Departemen</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td><code><?= View::e($dept['department_code']) ?></code></td>
                        <td><strong><?= View::e($dept['department_name']) ?></strong></td>
                        <td><?= View::e($dept['manager_name'] ?? '-') ?></td>
                        <td><?= View::e($dept['description'] ?? '-') ?></td>
                        <td>
                            <?php if ((int) $dept['is_active'] === 1): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editDeptModal<?= (int) $dept['department_id'] ?>">
                                Edit
                            </button>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editDeptModal<?= (int) $dept['department_id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog text-start">
                                    <form action="/settings/departments/<?= (int) $dept['department_id'] ?>/update" method="POST">
                                        <?= Csrf::field() ?>
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title h6 fw-bold">Edit Departemen: <?= View::e($dept['department_name']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small">Kode Departemen</label>
                                                    <input type="text" name="department_code" class="form-control form-control-sm text-uppercase" required value="<?= View::e($dept['department_code']) ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Nama Departemen</label>
                                                    <input type="text" name="department_name" class="form-control form-control-sm" required value="<?= View::e($dept['department_name']) ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Manajer Departemen</label>
                                                    <select name="manager_id" class="form-select form-select-sm">
                                                        <option value="">-- Tidak Ditentukan --</option>
                                                        <?php foreach ($employees as $emp): ?>
                                                            <option value="<?= (int) $emp['employee_id'] ?>" <?= (int) ($dept['manager_id'] ?? 0) === (int) $emp['employee_id'] ? 'selected' : '' ?>>
                                                                <?= View::e($emp['full_name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Deskripsi</label>
                                                    <input type="text" name="description" class="form-control form-control-sm" value="<?= View::e($dept['description'] ?? '') ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Status</label>
                                                    <select name="is_active" class="form-select form-select-sm">
                                                        <option value="1" <?= (int) $dept['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                                                        <option value="0" <?= (int) $dept['is_active'] === 0 ? 'selected' : '' ?>>Nonaktif</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/settings/departments" method="POST">
            <?= Csrf::field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6 fw-bold">Tambah Departemen Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Kode Departemen <span class="text-danger">*</span></label>
                        <input type="text" name="department_code" class="form-control form-control-sm text-uppercase" required placeholder="ENG">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nama Departemen <span class="text-danger">*</span></label>
                        <input type="text" name="department_name" class="form-control form-control-sm" required placeholder="Engineering">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Manajer Departemen</label>
                        <select name="manager_id" class="form-select form-select-sm">
                            <option value="">-- Tidak Ditentukan --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= (int) $emp['employee_id'] ?>">
                                    <?= View::e($emp['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Deskripsi</label>
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Deskripsi departemen...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Status</label>
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Departemen</button>
                </div>
            </div>
        </form>
    </div>
</div>
