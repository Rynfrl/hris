<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Detail GRN';
$activeNavKey = 'grn';
$pageScript = 'assets/js/purchasing-detail-grn.js';
require __DIR__ . '/partials/header.php';
?>
            <div id="pur-detail-loading" class="pur-state-box">
              <div class="spinner-border text-secondary mb-2" role="status"></div>
              <div>Memuat detail GRN…</div>
            </div>
            <div id="pur-detail-error" class="pur-state-box text-danger d-none">
              <i class="bi bi-exclamation-triangle"></i>
              <span id="pur-detail-error-msg">GRN tidak ditemukan.</span>
              <div class="mt-2"><a href="./grn-list.php" class="btn btn-sm btn-outline-secondary">Kembali</a></div>
            </div>

            <div id="pur-detail-content" class="d-none">
              <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pur-no-print gap-2">
                <span class="fs-5 fw-semibold" id="pur-grn-no">-</span>
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
              </div>

              <div class="card pur-invoice-box">
                <div class="card-body">
                  <div class="row pur-invoice-header pb-3 mb-3">
                    <div class="col-md-6"><div class="pur-invoice-logo"><i class="bi bi-buildings me-1"></i> ERP System</div><div class="text-secondary small">Modul Purchasing — Tanda Terima Barang</div></div>
                    <div class="col-md-6 text-md-end"><div class="fs-4 fw-bold">GOODS RECEIPT NOTE</div><div class="text-secondary">No. <span id="pur-inv-no">-</span></div></div>
                  </div>

                  <div class="row g-4 mb-3">
                    <div class="col-md-6">
                      <dl class="pur-invoice-meta mb-0">
                        <dt>No. GRN</dt><dd id="pur-info-no">-</dd>
                        <dt>PO Terkait</dt><dd id="pur-info-po">-</dd>
                        <dt>Tanggal Terima</dt><dd id="pur-info-date">-</dd>
                        <dt>Diterima Oleh</dt><dd id="pur-info-receiver">-</dd>
                        <dt>Catatan</dt><dd id="pur-info-notes">-</dd>
                      </dl>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                      <thead class="table-light"><tr><th style="width:3rem">No</th><th>Kode</th><th>Nama Produk</th><th>Unit</th><th class="text-end">Qty Diterima</th><th>Kondisi</th></tr></thead>
                      <tbody id="pur-items-body"></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
