<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Detail Purchase Requisition';
$activeNavKey = 'pr';
$pageScript = 'assets/js/purchasing-detail-pr.js';
require __DIR__ . '/partials/header.php';
?>
            <div id="pur-detail-loading" class="pur-state-box">
              <div class="spinner-border text-secondary mb-2" role="status"></div>
              <div>Memuat detail PR…</div>
            </div>
            <div id="pur-detail-error" class="pur-state-box text-danger d-none">
              <i class="bi bi-exclamation-triangle"></i>
              <span id="pur-detail-error-msg">PR tidak ditemukan.</span>
              <div class="mt-2"><a href="./pr-list.php" class="btn btn-sm btn-outline-secondary">Kembali ke Daftar PR</a></div>
            </div>

            <div id="pur-detail-content" class="d-none">
              <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div><span class="fs-5 fw-semibold" id="pur-trxno">-</span><span id="pur-status-badge" class="ms-2"></span></div>
                <div class="d-flex gap-2" id="pur-action-buttons"></div>
              </div>

              <div class="row g-3" id="pur-status-alert-slot"></div>

              <div class="row g-3">
                <div class="col-md-4">
                  <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Informasi PR</h3></div>
                    <div class="card-body">
                      <dl class="pur-invoice-meta mb-0">
                        <dt>No. PR</dt><dd id="pur-info-no">-</dd>
                        <dt>Departemen</dt><dd id="pur-info-dept">-</dd>
                        <dt>Tanggal Dibutuhkan</dt><dd id="pur-info-needed">-</dd>
                        <dt>Diajukan Oleh</dt><dd id="pur-info-requester">-</dd>
                        <dt>Catatan</dt><dd id="pur-info-notes">-</dd>
                      </dl>
                    </div>
                  </div>
                </div>
                <div class="col-md-8">
                  <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Approval</h3></div>
                    <div class="card-body">
                      <dl class="pur-invoice-meta mb-0">
                        <dt>Ambang Batas Saat Diajukan</dt><dd id="pur-info-threshold">-</dd>
                        <dt>Disetujui/Ditolak Oleh</dt><dd id="pur-info-approver">-</dd>
                        <dt>Alasan Penolakan</dt><dd id="pur-info-rejection">-</dd>
                        <dt>PO Hasil Konversi</dt><dd id="pur-info-po">-</dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>

              <div class="row mt-3">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header"><h3 class="card-title">Detail Item (Estimasi)</h3></div>
                    <div class="card-body p-0">
                      <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                          <thead class="table-light"><tr><th style="width:3rem">No</th><th>Kode</th><th>Nama Produk</th><th class="text-end">Qty</th><th>Unit</th><th class="text-end">Estimasi Harga</th><th class="text-end">Subtotal</th></tr></thead>
                          <tbody id="pur-items-body"></tbody>
                        </table>
                      </div>
                    </div>
                    <div class="card-footer">
                      <div class="pur-totals-box">
                        <div class="row"><div class="col-6">Subtotal</div><div class="col-6 text-end" id="pur-subtotal">Rp 0</div></div>
                        <div class="row"><div class="col-6">PPN</div><div class="col-6 text-end" id="pur-tax">Rp 0</div></div>
                        <div class="row pur-grand-total border-top pt-2 mt-2"><div class="col-6">Grand Total</div><div class="col-6 text-end" id="pur-grand">Rp 0</div></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
