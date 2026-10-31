<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Goods Receipt Note (GRN)';
$activeNavKey = 'grn';
$pageScript = 'assets/js/purchasing-grn-list.js';
require __DIR__ . '/partials/header.php';
?>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Daftar Goods Receipt Note</h3>
                <div class="card-tools">
                  <?php if (in_array($currentUser['role'], ['Warehouse Staff', 'Admin'], true)): ?>
                  <a href="./create-grn.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat GRN</a>
                  <?php endif; ?>
                </div>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>No. GRN</th><th>PO Terkait</th><th>Tanggal Terima</th><th>Diterima Oleh</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody id="pur-grn-table-body"></tbody>
                  </table>
                </div>
                <div id="pur-grn-empty" class="pur-state-box d-none"><i class="bi bi-inbox"></i>Belum ada GRN.</div>
                <div id="pur-grn-error" class="pur-state-box d-none text-danger"><i class="bi bi-exclamation-triangle"></i>Gagal memuat data. <button class="btn btn-sm btn-outline-danger mt-2" id="pur-grn-retry">Coba Lagi</button></div>
              </div>
              <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-secondary" id="pur-grn-pagination-info">-</small>
                <nav><ul class="pagination pagination-sm mb-0" id="pur-grn-pagination"></ul></nav>
              </div>
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
