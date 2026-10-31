<?php
require __DIR__ . '/guard.php';
pur_page_require_role(['Purchasing Staff', 'Admin', 'Finance Manager']);
$pageTitle = 'Purchase Requisition';
$activeNavKey = 'pr';
$pageScript = 'assets/js/purchasing-pr-list.js';
require __DIR__ . '/partials/header.php';
?>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Daftar Purchase Requisition</h3>
                <div class="card-tools">
                  <?php if (in_array($currentUser['role'], ['Purchasing Staff', 'Admin'], true)): ?>
                  <a href="./create-pr.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat PR</a>
                  <?php endif; ?>
                </div>
              </div>
              <div class="card-body border-bottom">
                <div class="row g-2">
                  <div class="col-sm-7">
                    <div class="input-group input-group-sm">
                      <span class="input-group-text"><i class="bi bi-search"></i></span>
                      <input type="search" id="pur-pr-search" class="form-control" placeholder="Cari no. PR…" />
                    </div>
                  </div>
                  <div class="col-sm-5">
                    <select id="pur-pr-status-filter" class="form-select form-select-sm">
                      <option value="">Semua Status</option>
                      <option value="Draft">Draft</option>
                      <option value="Pending Approval">Pending Approval</option>
                      <option value="Approved">Approved</option>
                      <option value="Rejected">Rejected</option>
                      <option value="Converted">Converted</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>No. PR</th><th>Departemen</th><th>Diajukan Oleh</th><th class="text-end">Grand Total</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody id="pur-pr-table-body"></tbody>
                  </table>
                </div>
                <div id="pur-pr-empty" class="pur-state-box d-none"><i class="bi bi-inbox"></i>Tidak ada PR yang cocok.</div>
                <div id="pur-pr-error" class="pur-state-box d-none text-danger"><i class="bi bi-exclamation-triangle"></i>Gagal memuat data. <button class="btn btn-sm btn-outline-danger mt-2" id="pur-pr-retry">Coba Lagi</button></div>
              </div>
              <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-secondary" id="pur-pr-pagination-info">-</small>
                <nav><ul class="pagination pagination-sm mb-0" id="pur-pr-pagination"></ul></nav>
              </div>
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
