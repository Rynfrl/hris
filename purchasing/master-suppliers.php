<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Master Supplier';
$activeNavKey = 'master_suppliers';
$pageScript = 'assets/js/purchasing-master-suppliers.js';
require __DIR__ . '/partials/header.php';
$canManage = in_array($currentUser['role'], ['Admin', 'Purchasing Staff'], true);
?>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Daftar Supplier</h3>
                <div class="card-tools d-flex gap-2">
                  <input type="search" id="pur-search" class="form-control form-control-sm" placeholder="Cari kode/nama…" style="width:220px" />
                  <?php if ($canManage): ?>
                  <button class="btn btn-sm btn-primary" id="pur-add-btn"><i class="bi bi-plus-lg me-1"></i>Tambah Supplier</button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Telepon</th><th>Email</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody id="pur-table-body"></tbody>
                  </table>
                </div>
                <div id="pur-empty" class="pur-state-box d-none"><i class="bi bi-inbox"></i>Tidak ada supplier.</div>
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
                    <h5 class="modal-title" id="pur-modal-title">Tambah Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form id="pur-form">
                    <div class="modal-body">
                      <input type="hidden" id="pur-f-id" />
                      <div class="mb-3"><label class="form-label">Kode <span class="text-danger">*</span></label><input type="text" class="form-control" id="pur-f-code" required maxlength="30" /></div>
                      <div class="mb-3"><label class="form-label">Nama <span class="text-danger">*</span></label><input type="text" class="form-control" id="pur-f-name" required /></div>
                      <div class="mb-3"><label class="form-label">Telepon</label><input type="text" class="form-control" id="pur-f-phone" /></div>
                      <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="pur-f-email" /></div>
                      <div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" id="pur-f-address" rows="2"></textarea></div>
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
