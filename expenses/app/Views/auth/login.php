<?php

declare(strict_types=1);

use App\Core\Csrf;
?>
<form action="<?= \App\Core\Url::to('/login') ?>" method="POST">
    <?= Csrf::field() ?>

    <div class="mb-3">
        <label for="identifier" class="form-label">Email atau Username</label>
        <input type="text" class="form-control" id="identifier" name="identifier" required autofocus placeholder="nama@perusahaan.com">
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">Kata Sandi</label>
        <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        Masuk
    </button>
</form>

<div class="mt-4 pt-3 border-top text-center">
    <span class="text-muted small">Versi MVP &bull; Dilindungi Otorisasi &amp; Audit</span>
</div>
