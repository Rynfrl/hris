<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;

$activeEmployees = $activeEmployees ?? [];
$reportEmployees = $reportEmployees ?? [];
$currentEmpIds = array_column($reportEmployees, 'employee_id');
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h4 fw-bold mb-1">Edit Laporan <?= View::e($report['report_number']) ?></h2>
                <p class="text-secondary small mb-0">Perbarui informasi dan daftar karyawan yang terdaftar dalam laporan ini</p>
            </div>
            <a href="/reports/<?= (int) $report['report_id'] ?>" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>

        <div class="card card-gt">
            <div class="card-body p-4">
                <form action="/reports/<?= (int) $report['report_id'] ?>/update" method="POST">
                    <?= Csrf::field() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="title" class="form-label fw-semibold">Judul Laporan <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" required value="<?= View::e($report['title']) ?>" minlength="3" maxlength="150">
                        </div>

                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Periode Mulai</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= View::e($report['start_date'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">Periode Selesai</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= View::e($report['end_date'] ?? '') ?>">
                        </div>

                        <!-- Multi-Employee Selector -->
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">
                                    Daftar Karyawan yang Terlibat <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="employeeSearchInput" class="form-control form-control-sm" placeholder="Cari nama / divisi..." style="width: 200px;">
                            </div>

                            <div class="border rounded p-3 bg-light" style="max-height: 250px; overflow-y: auto;" id="employeeListContainer">
                                <div class="row g-2">
                                    <?php foreach ($activeEmployees as $emp): ?>
                                        <?php
                                            $eId = (int) $emp['employee_id'];
                                            $isChecked = in_array($eId, $currentEmpIds, true);
                                            // Check if employee has items attached in this report
                                            $hasItems = false;
                                            foreach ($reportEmployees as $re) {
                                                if ((int) $re['employee_id'] === $eId && (int) $re['item_count'] > 0) {
                                                    $hasItems = true;
                                                    break;
                                                }
                                            }
                                        ?>
                                        <div class="col-md-6 employee-item">
                                            <div class="form-check p-2 border rounded bg-white h-100 d-flex align-items-center">
                                                <input class="form-check-input ms-1 me-2 emp-checkbox" type="checkbox" name="employee_ids[]" value="<?= $eId ?>" id="emp_<?= $eId ?>" <?= $isChecked ? 'checked' : '' ?> <?= $hasItems ? 'onclick="return false;" style="opacity: 0.8;" title="Karyawan ini memiliki item transaksi di laporan dan tidak dapat dihapus langsung dari sini"' : '' ?>>
                                                <label class="form-check-label w-100 cursor-pointer mb-0" for="emp_<?= $eId ?>">
                                                    <div class="fw-semibold text-dark">
                                                        <?= View::e($emp['full_name']) ?>
                                                        <?php if ($hasItems): ?>
                                                            <span class="badge bg-info text-dark ms-1" style="font-size: 0.7rem;">Ada Transaksi</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-muted small d-flex justify-content-between pe-2">
                                                        <span><?= View::e($emp['employee_number'] ?? ('EMP-' . $eId)) ?></span>
                                                        <span class="badge bg-secondary"><?= View::e($emp['department_name'] ?? 'Umum') ?></span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Deskripsi / Keterangan</label>
                            <textarea name="description" id="description" class="form-control" rows="3"><?= View::e($report['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="/reports/<?= (int) $report['report_id'] ?>" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
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
