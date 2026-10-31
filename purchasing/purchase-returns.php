<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Purchase Return';
$activeNavKey = 'returns';
$pageScript = 'assets/js/purchasing-returns.js';
require __DIR__ . '/partials/header.php';
$canCreate = in_array($currentUser['role'], ['Warehouse Staff', 'Admin'], true);
?>
            <div class="row g-3">
              <?php if ($canCreate): ?>
              <div class="col-lg-4">
                <div class="card">
                  <div class="card-header"><h3 class="card-title">Buat Retur</h3></div>
                  <div class="card-body">
                    <form id="pur-return-form" novalidate>
                      <div class="mb-3">
                        <label for="pur-f-grn" class="form-label">GRN <span class="text-danger">*</span></label>
                        <select id="pur-f-grn" class="form-select" required><option value="">Memuat GRN…</option></select>
                      </div>
                      <div class="mb-3">
                        <label for="pur-f-product" class="form-label">Produk <span class="text-danger">*</span></label>
                        <select id="pur-f-product" class="form-select" required><option value="">-- Pilih GRN dahulu --</option></select>
                      </div>
                      <div class="mb-3">
                        <label for="pur-f-qty" class="form-label">Qty Retur <span class="text-danger">*</span></label>
                        <input type="number" id="pur-f-qty" class="form-control" min="1" step="1" required />
                      </div>
                      <div class="mb-3">
                        <label for="pur-f-condition" class="form-label">Kondisi <span class="text-danger">*</span></label>
                        <select id="pur-f-condition" class="form-select" required>
                          <option value="Defective">Defective (Cacat)</option>
                          <option value="Shortage">Shortage (Kurang)</option>
                          <option value="Wrong Item">Wrong Item (Salah Kirim)</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="pur-f-reason" class="form-label">Alasan</label>
                        <textarea id="pur-f-reason" class="form-control" rows="2"></textarea>
                      </div>
                      <div id="pur-form-error" class="alert alert-danger py-2 small d-none"></div>
                      <button type="submit" class="btn btn-primary w-100" id="pur-submit-btn">
                        <span id="pur-submit-btn-label"><i class="bi bi-save me-1"></i>Simpan Retur</span>
                        <span id="pur-submit-btn-spinner" class="d-none"><span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…</span>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <div class="col-lg-<?= $canCreate ? '8' : '12' ?>">
                <div class="card">
                  <div class="card-header"><h3 class="card-title">Riwayat Retur</h3></div>
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>No. Retur</th><th>GRN</th><th>Produk</th><th class="text-end">Qty</th><th>Kondisi</th><th>Dibuat Oleh</th></tr></thead>
                        <tbody id="pur-return-table-body"></tbody>
                      </table>
                    </div>
                    <div id="pur-return-empty" class="pur-state-box d-none"><i class="bi bi-inbox"></i>Belum ada retur.</div>
                  </div>
                  <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-secondary" id="pur-pagination-info">-</small>
                    <nav><ul class="pagination pagination-sm mb-0" id="pur-pagination"></ul></nav>
                  </div>
                </div>
              </div>
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
