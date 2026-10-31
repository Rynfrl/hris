<?php
require __DIR__ . '/guard.php';
pur_page_require_role(['Warehouse Staff', 'Admin']);
$pageTitle = 'Buat Goods Receipt Note';
$activeNavKey = 'grn';
$pageScript = 'assets/js/purchasing-create-grn.js';
require __DIR__ . '/partials/header.php';
?>
            <form id="pur-grn-form" novalidate>
              <div class="row g-3">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header"><h3 class="card-title">Informasi Penerimaan</h3></div>
                    <div class="card-body">
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label for="pur-f-po" class="form-label">Purchase Order <span class="text-danger">*</span></label>
                          <select id="pur-f-po" class="form-select" required>
                            <option value="">Memuat PO…</option>
                          </select>
                          <div class="form-text">Hanya PO berstatus Pending / Partially Received yang ditampilkan.</div>
                        </div>
                        <div class="col-md-3">
                          <label for="pur-f-date" class="form-label">Tanggal Terima <span class="text-danger">*</span></label>
                          <input type="date" id="pur-f-date" class="form-control" required />
                        </div>
                        <div class="col-12">
                          <label for="pur-f-notes" class="form-label">Catatan</label>
                          <textarea id="pur-f-notes" class="form-control" rows="2"></textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12" id="pur-lines-card-wrap" style="display:none">
                  <div class="card">
                    <div class="card-header"><h3 class="card-title">Item yang Diterima</h3></div>
                    <div class="card-body p-0">
                      <div class="table-responsive">
                        <table class="table align-middle mb-0">
                          <thead>
                            <tr><th>Produk</th><th class="text-end">Qty Order</th><th class="text-end">Sudah Diterima</th><th class="text-end">Outstanding</th><th style="width:140px">Qty Diterima Sekarang</th><th style="width:160px">Kondisi</th></tr>
                          </thead>
                          <tbody id="pur-lines-body"></tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mb-3">
                  <a href="./grn-list.php" class="btn btn-outline-secondary">Batal</a>
                  <button type="submit" class="btn btn-primary" id="pur-submit-btn" disabled>
                    <span id="pur-submit-btn-label"><i class="bi bi-box-arrow-in-down me-1"></i>Simpan GRN</span>
                    <span id="pur-submit-btn-spinner" class="d-none"><span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…</span>
                  </button>
                </div>
              </div>
            </form>
<?php require __DIR__ . '/partials/footer.php'; ?>
