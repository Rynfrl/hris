<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Vendor Invoice';
$activeNavKey = 'invoices';
$pageScript = 'assets/js/purchasing-vendor-invoices.js';
require __DIR__ . '/partials/header.php';
$canCreate = in_array($currentUser['role'], ['Finance Manager', 'Admin'], true);
?>
            <div class="row g-3">
              <?php if ($canCreate): ?>
              <div class="col-lg-4">
                <div class="card">
                  <div class="card-header"><h3 class="card-title">Input Invoice Vendor</h3></div>
                  <div class="card-body">
                    <form id="pur-invoice-form" novalidate>
                      <div class="mb-3">
                        <label for="pur-f-po" class="form-label">Purchase Order <span class="text-danger">*</span></label>
                        <select id="pur-f-po" class="form-select" required><option value="">Memuat PO…</option></select>
                      </div>
                      <div class="mb-3">
                        <label for="pur-f-invoiceno" class="form-label">No. Invoice Vendor <span class="text-danger">*</span></label>
                        <input type="text" id="pur-f-invoiceno" class="form-control" required />
                      </div>
                      <div class="mb-3">
                        <label for="pur-f-amount" class="form-label">Nominal Invoice <span class="text-danger">*</span></label>
                        <div class="input-group"><span class="input-group-text">Rp</span><input type="number" id="pur-f-amount" class="form-control" min="0" step="1" required /></div>
                      </div>
                      <div class="mb-3">
                        <label for="pur-f-date" class="form-label">Tanggal Invoice <span class="text-danger">*</span></label>
                        <input type="date" id="pur-f-date" class="form-control" required />
                      </div>
                      <div id="pur-form-error" class="alert alert-danger py-2 small d-none"></div>
                      <button type="submit" class="btn btn-primary w-100" id="pur-submit-btn">
                        <span id="pur-submit-btn-label"><i class="bi bi-save me-1"></i>Simpan &amp; Cocokkan (3-Way Match)</span>
                        <span id="pur-submit-btn-spinner" class="d-none"><span class="spinner-border spinner-border-sm me-1"></span>Memproses…</span>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <div class="col-lg-<?= $canCreate ? '8' : '12' ?>">
                <div class="card">
                  <div class="card-header">
                    <h3 class="card-title">Daftar Invoice</h3>
                    <div class="card-tools">
                      <select id="pur-status-filter" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="Pending Review">Pending Review</option>
                        <option value="Approved for Payment">Approved for Payment</option>
                        <option value="Rejected">Rejected</option>
                      </select>
                    </div>
                  </div>
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>No. Invoice</th><th>PO</th><th class="text-end">Nominal</th><th>3-Way Match</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody id="pur-invoice-table-body"></tbody>
                      </table>
                    </div>
                    <div id="pur-invoice-empty" class="pur-state-box d-none"><i class="bi bi-inbox"></i>Belum ada invoice.</div>
                  </div>
                  <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-secondary" id="pur-pagination-info">-</small>
                    <nav><ul class="pagination pagination-sm mb-0" id="pur-pagination"></ul></nav>
                  </div>
                </div>
              </div>
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
