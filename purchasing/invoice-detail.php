<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Detail Vendor Invoice';
$activeNavKey = 'invoices';
$pageScript = 'assets/js/purchasing-invoice-detail.js';
require __DIR__ . '/partials/header.php';
?>
            <div id="pur-detail-loading" class="pur-state-box">
              <div class="spinner-border text-secondary mb-2" role="status"></div>
              <div>Memuat detail invoice…</div>
            </div>
            <div id="pur-detail-error" class="pur-state-box text-danger d-none">
              <i class="bi bi-exclamation-triangle"></i>
              <span id="pur-detail-error-msg">Invoice tidak ditemukan.</span>
              <div class="mt-2"><a href="./vendor-invoices.php" class="btn btn-sm btn-outline-secondary">Kembali</a></div>
            </div>

            <div id="pur-detail-content" class="d-none">
              <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div><span class="fs-5 fw-semibold" id="pur-invno">-</span><span id="pur-status-badge" class="ms-2"></span></div>
                <div class="d-flex gap-2" id="pur-action-buttons"></div>
              </div>

              <div class="row g-3" id="pur-match-alert-slot"></div>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Informasi Invoice</h3></div>
                    <div class="card-body">
                      <dl class="pur-invoice-meta mb-0">
                        <dt>No. Invoice Vendor</dt><dd id="pur-info-invno">-</dd>
                        <dt>PO Terkait</dt><dd id="pur-info-po">-</dd>
                        <dt>Nominal</dt><dd id="pur-info-amount">-</dd>
                        <dt>Tanggal Invoice</dt><dd id="pur-info-date">-</dd>
                        <dt>Dicatat Oleh</dt><dd id="pur-info-entered">-</dd>
                      </dl>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Hasil 3-Way Matching</h3></div>
                    <div class="card-body">
                      <p id="pur-match-notes" class="mb-2">-</p>
                      <dl class="pur-invoice-meta mb-0">
                        <dt>Diproses Oleh</dt><dd id="pur-info-approver">-</dd>
                        <dt>Catatan Approval</dt><dd id="pur-info-approval-notes">-</dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
