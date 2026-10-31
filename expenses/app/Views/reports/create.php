<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h4 fw-bold mb-1">Buat Laporan Pengeluaran Baru</h2>
                <p class="text-secondary small mb-0">Kelompokkan pengeluaran Anda untuk diajukan ke manajer</p>
            </div>
            <a href="/reports" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>

        <div class="card card-gt">
            <div class="card-body p-4">
                <form action="/reports" method="POST">
                    <?= Csrf::field() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="title" class="form-label">Judul Laporan <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" required placeholder="Contoh: Perjalanan Dinas Jakarta - Surabaya Oktober 2026" minlength="3" maxlength="150">
                        </div>

                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Periode Mulai (Opsional)</label>
                            <input type="date" name="start_date" id="start_date" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">Periode Selesai (Opsional)</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Deskripsi / Keterangan</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Tujuan perjalanan atau ringkasan pengeluaran..."></textarea>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="/reports" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">Buat Laporan Draft</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
