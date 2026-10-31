<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Master Produk';
$activeNavKey = 'master_products';
$pageScript = 'assets/js/purchasing-master-products.js';
require __DIR__ . '/partials/header.php';
$canManage = in_array($currentUser['role'], ['Admin', 'Purchasing Staff'], true);
$canDelete = $currentUser['role'] === 'Admin';
?>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Daftar Produk</h3>
                <div class="card-tools d-flex gap-2">
                  <input type="search" id="pur-search" class="form-control form-control-sm" placeholder="Cari kode/nama…" style="width:220px" />
                  <?php if ($canManage): ?>
                  <button class="btn btn-sm btn-primary" id="pur-add-btn"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Unit</th><th class="text-end">Harga Beli</th><th class="text-end">Stok Gudang</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody id="pur-table-body"></tbody>
                  </table>
                </div>
                <div id="pur-empty" class="pur-state-box d-none"><i class="bi bi-inbox"></i>Tidak ada produk.</div>
              </div>
              <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-secondary" id="pur-pagination-info">-</small>
                <nav><ul class="pagination pagination-sm mb-0" id="pur-pagination"></ul></nav>
              </div>
            </div>

            <?php if ($canManage): ?>
            <div class="modal fade" id="pur-form-modal" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="pur-modal-title">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form id="pur-form">
                    <div class="modal-body">
                      <input type="hidden" id="pur-f-id" />
                      <div class="mb-3"><label class="form-label">Kode <span class="text-danger">*</span></label><input type="text" class="form-control" id="pur-f-code" required maxlength="30" /></div>
                      <div class="mb-3"><label class="form-label">Nama <span class="text-danger">*</span></label><input type="text" class="form-control" id="pur-f-name" required /></div>
                      <div class="mb-3"><label class="form-label">Unit <span class="text-danger">*</span></label><input type="text" class="form-control" id="pur-f-unit" required placeholder="mis. Unit, Pack, Rim" /></div>
                      <div class="mb-3"><label class="form-label">Harga Satuan <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-text">Rp</span><input type="number" class="form-control" id="pur-f-price" min="0" step="1" required /></div></div>
                      <div id="pur-form-error" class="alert alert-danger py-2 small d-none"></div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-primary" id="pur-form-submit">Simpan</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
