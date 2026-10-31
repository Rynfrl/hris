<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Kategori Pengeluaran</h2>
        <p class="text-secondary small mb-0">Kelola master kategori pengeluaran dan status aktif</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
        + Tambah Kategori
    </button>
</div>

<div class="card card-gt">
    <div class="table-responsive">
        <table class="table table-hover table-gt mb-0">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Tarif Tetap (Default)</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><code><?= View::e($cat['category_code']) ?></code></td>
                        <td><strong><?= View::e($cat['category_name']) ?></strong></td>
                        <td><?= View::e($cat['description'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($cat['default_amount'])): ?>
                                <?= View::formatRupiah($cat['default_amount']) ?>
                            <?php else: ?>
                                <span class="text-muted small">- Fleksibel -</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $cat['is_active'] === 1): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCatModal<?= (int) $cat['category_id'] ?>">
                                Edit
                            </button>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editCatModal<?= (int) $cat['category_id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog text-start">
                                    <form action="/settings/categories/<?= (int) $cat['category_id'] ?>/update" method="POST">
                                        <?= Csrf::field() ?>
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title h6 fw-bold">Edit Kategori: <?= View::e($cat['category_name']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small">Kode Kategori</label>
                                                    <input type="text" name="category_code" class="form-control form-control-sm text-uppercase" required value="<?= View::e($cat['category_code']) ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Nama Kategori</label>
                                                    <input type="text" name="category_name" class="form-control form-control-sm" required value="<?= View::e($cat['category_name']) ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Deskripsi</label>
                                                    <input type="text" name="description" class="form-control form-control-sm" value="<?= View::e($cat['description'] ?? '') ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Nominal Default (Opsional)</label>
                                                    <input type="number" name="default_amount" class="form-control form-control-sm" value="<?= View::e($cat['default_amount'] ?? '') ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Status</label>
                                                    <select name="is_active" class="form-select form-select-sm">
                                                        <option value="1" <?= (int) $cat['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                                                        <option value="0" <?= (int) $cat['is_active'] === 0 ? 'selected' : '' ?>>Nonaktif</option>
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
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/settings/categories" method="POST">
            <?= Csrf::field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6 fw-bold">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Kode Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="category_code" class="form-control form-control-sm text-uppercase" required placeholder="HOTEL">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" class="form-control form-control-sm" required placeholder="Akomodasi Hotel">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Deskripsi</label>
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Biaya penginapan dinas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nominal Default (Opsional)</label>
                        <input type="number" name="default_amount" class="form-control form-control-sm" placeholder="0">
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
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Kategori</button>
                </div>
            </div>
        </form>
    </div>
</div>
