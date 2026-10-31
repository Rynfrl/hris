<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Detail Purchase Order';
$activeNavKey = 'pr';
$pageScript = 'assets/js/purchasing-detail.js';
require __DIR__ . '/partials/header.php';
?>
            <div id="pur-detail-loading" class="pur-state-box">
              <div class="spinner-border text-secondary mb-2" role="status"><span class="visually-hidden">Memuat…</span></div>
              <div>Memuat detail PO…</div>
            </div>

            <div id="pur-detail-error" class="pur-state-box text-danger d-none">
              <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
              <span id="pur-detail-error-msg">PO tidak ditemukan.</span>
              <div class="mt-2"><a href="./dashboard.php" class="btn btn-sm btn-outline-secondary">Kembali ke Dashboard</a></div>
            </div>

            <div id="pur-detail-content" class="d-none">
              <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pur-no-print gap-2">
                <div>
                  <span class="fs-5 fw-semibold" id="pur-detail-trxno">-</span>
                  <span id="pur-detail-status-badge" class="ms-2"></span>
                </div>
                <div class="d-flex gap-2" id="pur-action-buttons"></div>
              </div>

              <div class="row g-3 pur-no-print" id="pur-status-alert-slot"></div>

              <!--begin::Invoice-->
              <div class="row">
                <div class="col-12">
                  <div class="card pur-invoice-box">
                    <div class="card-body">
                      <div class="row pur-invoice-header pb-3 mb-3">
                        <div class="col-md-6">
                          <div class="pur-invoice-logo"><i class="bi bi-buildings me-1"></i> ERP System</div>
                          <div class="text-secondary small">Modul Purchasing — Purchase Order</div>
                        </div>
                        <div class="col-md-6 text-md-end">
                          <div class="fs-4 fw-bold">PURCHASE ORDER</div>
                          <div class="text-secondary">No. <span id="pur-inv-trxno">-</span></div>
                        </div>
                      </div>

                      <div class="row g-4 mb-3">
                        <div class="col-md-4">
                          <h6 class="text-uppercase text-secondary fs-7 mb-2">Informasi PO</h6>
                          <dl class="pur-invoice-meta mb-0">
                            <dt>No. PO</dt><dd id="pur-inv-info-trxno">-</dd>
                            <dt>Tanggal PO</dt><dd id="pur-inv-info-date">-</dd>
                            <dt>Perkiraan Kirim</dt><dd id="pur-inv-info-expected">-</dd>
                            <dt>Status</dt><dd id="pur-inv-info-status">-</dd>
                            <dt>Dibuat Oleh</dt><dd id="pur-inv-info-user">-</dd>
                            <dt>Sumber PR</dt><dd id="pur-inv-info-pr">-</dd>
                          </dl>
                        </div>
                        <div class="col-md-4">
                          <h6 class="text-uppercase text-secondary fs-7 mb-2">Informasi Supplier</h6>
                          <dl class="pur-invoice-meta mb-0">
                            <dt>Kode Supplier</dt><dd id="pur-inv-sup-code">-</dd>
                            <dt>Nama Supplier</dt><dd id="pur-inv-sup-name">-</dd>
                            <dt>Telepon</dt><dd id="pur-inv-sup-phone">-</dd>
                            <dt>Email</dt><dd id="pur-inv-sup-email">-</dd>
                          </dl>
                        </div>
                        <div class="col-md-4">
                          <h6 class="text-uppercase text-secondary fs-7 mb-2">Alamat Supplier</h6>
                          <p id="pur-inv-sup-address" class="mb-0">-</p>
                        </div>
                      </div>

                      <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle mb-0">
                          <thead class="table-light">
                            <tr>
                              <th style="width: 3rem">No</th><th>Kode</th><th>Nama Produk</th>
                              <th class="text-end">Qty Order</th><th class="text-end">Diterima</th><th class="text-end">Outstanding</th>
                              <th>Unit</th><th class="text-end">Harga Satuan</th><th class="text-end">Subtotal</th>
                            </tr>
                          </thead>
                          <tbody id="pur-inv-items"></tbody>
                        </table>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <h6 class="text-uppercase text-secondary fs-7 mb-2">Catatan</h6>
                          <p id="pur-inv-notes" class="text-secondary">-</p>
                        </div>
                        <div class="col-md-6">
                          <div class="pur-totals-box">
                            <div class="row"><div class="col-6">Subtotal</div><div class="col-6 text-end" id="pur-inv-subtotal">Rp 0</div></div>
                            <div class="row"><div class="col-6">PPN (<span id="pur-inv-tax-rate">11</span>%)</div><div class="col-6 text-end" id="pur-inv-tax">Rp 0</div></div>
                            <div class="row pur-grand-total border-top pt-2 mt-2"><div class="col-6">Grand Total</div><div class="col-6 text-end" id="pur-inv-grand">Rp 0</div></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!--end::Invoice-->

              <!--begin::GRN history-->
              <div class="row mt-3 pur-no-print">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header"><h3 class="card-title">Riwayat Penerimaan Barang (GRN)</h3></div>
                    <div class="card-body p-0">
                      <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                          <thead><tr><th>No. GRN</th><th>Tanggal Terima</th><th>Diterima Oleh</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead>
                          <tbody id="pur-grn-history-body"></tbody>
                        </table>
                      </div>
                      <div id="pur-grn-history-empty" class="pur-state-box d-none"><i class="bi bi-box-seam"></i>Belum ada barang yang diterima untuk PO ini.</div>
                    </div>
                  </div>
                </div>
              </div>
              <!--end::GRN history-->
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
