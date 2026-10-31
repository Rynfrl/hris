<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Manajemen Pengguna</h2>
        <p class="text-secondary small mb-0">Kelola akun pengguna, peran sistem (role), dan relasi karyawan</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
        + Tambah Pengguna
    </button>
</div>

<!-- Table -->
<div class="card card-gt">
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Karyawan Terkait</th>
                    <th>Departemen</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong><?= View::e($u['username']) ?></strong></td>
                        <td><?= View::e($u['email']) ?></td>
                        <td><span class="badge bg-light text-dark border fw-bold"><?= View::e($u['role_name']) ?></span></td>
                        <td>
                            <?php if (!empty($u['employee_name'])): ?>
                                <?= View::e($u['employee_name']) ?> <span class="text-muted small">(<?= View::e($u['employee_number'] ?? '') ?>)</span>
                            <?php else: ?>
                                <span class="text-muted small">- Belum dipetakan -</span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::e($u['department_name'] ?? '-') ?></td>
                        <td>
                            <?php if ((int) $u['is_active'] === 1): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::formatDate($u['last_login'], 'd M Y H:i') ?></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= (int) $u['user_id'] ?>">
                                Edit
                            </button>

                            <!-- Edit User Modal -->
                            <div class="modal fade" id="editUserModal<?= (int) $u['user_id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog text-start">
                                    <form action="/settings/users/<?= (int) $u['user_id'] ?>/update" method="POST">
                                        <?= Csrf::field() ?>
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title h6 fw-bold">Edit Pengguna: <?= View::e($u['username']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small">Username</label>
                                                    <input type="text" name="username" class="form-control form-control-sm" required value="<?= View::e($u['username']) ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Email</label>
                                                    <input type="email" name="email" class="form-control form-control-sm" required value="<?= View::e($u['email']) ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Role Database</label>
                                                    <select name="role_id" class="form-select form-select-sm" required>
                                                        <?php foreach ($roles as $r): ?>
                                                            <option value="<?= (int) $r['role_id'] ?>" <?= (int) $u['role_id'] === (int) $r['role_id'] ? 'selected' : '' ?>>
                                                                <?= View::e($r['role_name']) ?> (<?= View::e($r['description']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Pemetaan Karyawan</label>
                                                    <select name="employee_id" class="form-select form-select-sm">
                                                        <option value="">-- Tidak Terhubung ke Karyawan --</option>
                                                        <?php foreach ($employees as $emp): ?>
                                                            <option value="<?= (int) $emp['employee_id'] ?>" <?= (int) ($u['employee_id'] ?? 0) === (int) $emp['employee_id'] ? 'selected' : '' ?>>
                                                                <?= View::e($emp['full_name']) ?> (<?= View::e($emp['employee_number']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Ganti Kata Sandi (Kosongkan jika tidak diubah)</label>
                                                    <input type="password" name="password" class="form-control form-control-sm" placeholder="••••••••">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Status Akun</label>
                                                    <select name="is_active" class="form-select form-select-sm">
                                                        <option value="1" <?= (int) $u['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                                                        <option value="0" <?= (int) $u['is_active'] === 0 ? 'selected' : '' ?>>Nonaktif</option>
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

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/settings/users" method="POST">
            <?= Csrf::field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6 fw-bold">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control form-control-sm" required placeholder="budi_santoso">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-sm" required placeholder="budi@perusahaan.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Kata Sandi <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control form-control-sm" required placeholder="Minimal 8 karakter">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Role Database <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select form-select-sm" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= (int) $r['role_id'] ?>" <?= $r['role_name'] === 'EMPLOYEE' ? 'selected' : '' ?>>
                                    <?= View::e($r['role_name']) ?> (<?= View::e($r['description']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Pemetaan Karyawan</label>
                        <select name="employee_id" class="form-select form-select-sm">
                            <option value="">-- Tidak Terhubung ke Karyawan --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= (int) $emp['employee_id'] ?>">
                                    <?= View::e($emp['full_name']) ?> (<?= View::e($emp['employee_number']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Status Akun</label>
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Pengguna</button>
                </div>
            </div>
        </form>
    </div>
</div>
