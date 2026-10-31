<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h4 fw-bold mb-1">Catat Pengeluaran Baru</h2>
                <p class="text-secondary small mb-0">Masukkan rincian biaya transaksi dan unggah bukti kuitansi</p>
            </div>
            <a href="/expenses" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>

        <div class="card card-gt">
            <div class="card-body p-4">
                <form action="/expenses" method="POST" enctype="multipart/form-data">
                    <?= Csrf::field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Kategori Pengeluaran <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int) $cat['category_id'] ?>" data-default-amount="<?= View::e($cat['default_amount'] ?? '') ?>">
                                        <?= View::e($cat['category_name']) ?> (<?= View::e($cat['category_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="expense_date" class="form-label">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" id="expense_date" class="form-control" required max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Deskripsi / Keperluan <span class="text-danger">*</span></label>
                            <input type="text" name="description" id="description" class="form-control" required placeholder="Contoh: Makan siang bersama klien PT Maju Jaya" minlength="3" maxlength="255">
                        </div>

                        <div class="col-md-6">
                            <label for="amount" class="form-label">Nominal (IDR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="amount" id="amount" class="form-control" required min="1" max="1000000000" step="1" placeholder="150000">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Metode Pembayaran</label>
                            <select name="payment_method" id="payment_method" class="form-select">
                                <option value="CASH">Tunai (Cash)</option>
                                <option value="BANK_TRANSFER">Transfer Bank</option>
                                <option value="DEBIT_CARD">Kartu Debit</option>
                                <option value="CREDIT_CARD">Kartu Kredit Perusahaan</option>
                                <option value="OTHER">Lainnya</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="bank_name" class="form-label">Pilihan Bank</label>
                            <select name="bank_name" id="bank_name" class="form-select">
                                <option value="">-- Pilih Bank (Opsional) --</option>
                                <?php foreach ($banks as $bKey => $bLabel): ?>
                                    <option value="<?= View::e($bKey) ?>" <?= (strtoupper($defaultBank ?? '') === $bKey || strtoupper($defaultBank ?? '') === strtoupper($bLabel)) ? 'selected' : '' ?>>
                                        <?= View::e($bLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($defaultBank)): ?>
                                <div class="form-text small text-muted">Bank Terdaftar: <strong><?= View::e($defaultBank) ?></strong><?= !empty($bankAccount) ? ' (' . View::e($bankAccount) . ')' : '' ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="merchant" class="form-label">Nama Merchant / Toko</label>
                            <input type="text" name="merchant" id="merchant" class="form-control" placeholder="Contoh: Rumah Makan Sederhana" maxlength="150">
                        </div>

                        <div class="col-md-6">
                            <label for="receipt" class="form-label">Unggah Bukti Kuitansi (Opsional)</label>
                            <input type="file" name="receipt" id="receipt" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" data-max-size="5242880">
                            <div class="form-text small">JPG, PNG, WEBP, atau PDF (maks. 5 MB)</div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Catatan opsional..."></textarea>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="/expenses" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">Simpan sebagai Draft</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
