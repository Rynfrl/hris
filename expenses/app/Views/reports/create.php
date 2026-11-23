<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$activeEmployees = $activeEmployees ?? [];
$userEmpId = !empty($user['employee_id']) ? (int) $user['employee_id'] : 0;
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h4 fw-bold mb-1">Buat Laporan Pengeluaran Gabungan</h2>
                <p class="text-secondary small mb-0">Kelola pengeluaran perusahaan dengan memilih satu atau beberapa karyawan dalam satu laporan</p>
            </div>
            <a href="<?= Url::to('/reports') ?>" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>

        <div class="card card-gt">
            <div class="card-body p-4">
                <form action="<?= Url::to('/reports') ?>" method="POST">
                    <?= Csrf::field() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="title" class="form-label fw-semibold">Judul Laporan <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" required placeholder="Contoh: Operasional Proyek & Dinas Luar Kota - Oktober 2026" minlength="3" maxlength="150">
                        </div>

                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Periode Mulai</label>
                            <input type="date" name="start_date" id="start_date" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">Periode Selesai</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>

                        <!-- Approver Manager Selector (Prioritas 1) -->
                        <div class="col-12">
                            <label for="approver_id" class="form-label fw-semibold">Pilih Manajer Penyetuju (Manager Expense) <span class="text-danger">*</span></label>
                            <select name="approver_id" id="approver_id" class="form-select" required>
                                <?php if (!empty($approvers)): ?>
                                    <?php foreach ($approvers as $appr): ?>
                                        <option value="<?= (int) $appr['user_id'] ?>">
                                            <?= View::e($appr['full_name'] ?? $appr['username']) ?> (<?= View::e($appr['role_name'] ?? 'MANAGER') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="2">Rina Manajer (Manajer Operasional)</option>
                                <?php endif; ?>
                            </select>
                            <div class="form-text small text-muted">Hanya manajer yang dipilih yang memiliki wewenang untuk meninjau, menyetujui, dan melihat rincian laporan ini.</div>
                        </div>

                        <!-- Multi-Employee Selector -->
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">
                                    Pilih Karyawan yang Terlibat <span class="text-danger">*</span>
                                    <small class="text-muted fw-normal">(Minimal 1 karyawan; dapat lintas divisi)</small>
                                </label>
                                <div class="d-flex gap-2">
                                    <input type="text" id="employeeSearchInput" class="form-control form-control-sm" placeholder="Cari nama / divisi..." style="width: 200px;">
                                </div>
                            </div>

                            <div class="border rounded p-3 bg-light" style="max-height: 250px; overflow-y: auto;" id="employeeListContainer">
                                <?php if (empty($activeEmployees)): ?>
                                    <p class="text-muted small mb-0">Tidak ada karyawan aktif yang ditemukan di database.</p>
                                <?php else: ?>
                                    <div class="row g-2">
                                        <?php foreach ($activeEmployees as $emp): ?>
                                            <?php
                                                $eId = (int) $emp['employee_id'];
                                                $isSelected = ($eId === $userEmpId);
                                            ?>
                                            <div class="col-md-6 employee-item">
                                                <div class="form-check p-2 border rounded bg-white h-100 d-flex align-items-center">
                                                    <input class="form-check-input ms-1 me-2 emp-checkbox" type="checkbox" name="employee_ids[]" value="<?= $eId ?>" id="emp_<?= $eId ?>" <?= $isSelected ? 'checked' : '' ?>>
                                                    <label class="form-check-label w-100 cursor-pointer mb-0" for="emp_<?= $eId ?>">
                                                        <div class="fw-semibold text-dark"><?= View::e($emp['full_name']) ?></div>
                                                        <div class="text-muted small d-flex justify-content-between pe-2">
                                                            <span><?= View::e($emp['employee_number'] ?? ('EMP-' . $eId)) ?></span>
                                                            <span class="badge bg-secondary"><?= View::e($emp['department_name'] ?? 'Umum') ?></span>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="form-text small text-secondary">
                                Karyawan yang Anda pilih di sini akan dimasukkan ke laporan. Anda juga dapat menambahkan atau menghapus karyawan nanti pada halaman detail laporan.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Deskripsi / Keterangan Laporan</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Tujuan pengeluaran, nomor surat tugas, atau rincian kegiatan gabungan..."></textarea>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="<?= Url::to('/reports') ?>" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">Buat Laporan Draft</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('employeeSearchInput');
    const items = document.querySelectorAll('.employee-item');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            items.forEach(function(item) {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});
</script>
