<?php
require __DIR__ . '/guard.php';
pur_page_require_role(['Purchasing Staff', 'Admin']);
$pageTitle = 'Buat Purchase Requisition';
$activeNavKey = 'pr';
$pageScript = 'assets/js/purchasing-create-pr.js';
require __DIR__ . '/partials/header.php';
?>
            <form id="pur-pr-form" novalidate>
              <div class="row g-3">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header"><h3 class="card-title">Informasi PR</h3></div>
                    <div class="card-body">
                      <div class="row g-3">
                        <div class="col-md-4">
                          <label for="pur-f-department" class="form-label">Departemen</label>
                          <select id="pur-f-department" class="form-select">
                            <option value="">-- Departemen saya (otomatis) --</option>
                          </select>
                          <div class="form-text">Daftar departemen berasal dari data HR.</div>
                        </div>
                        <div class="col-md-4">
                          <label for="pur-f-needed" class="form-label">Tanggal Dibutuhkan</label>
                          <input type="date" id="pur-f-needed" class="form-control" />
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">Ambang Batas Approval</label>
                          <input type="text" class="form-control" value="Rp <?= number_format((float) pur_config('pr_approval_threshold'), 0, ',', '.') ?>" disabled />
                          <div class="form-text">PR di atas nilai ini memerlukan approval Finance Manager.</div>
                        </div>
                        <div class="col-12">
                          <label for="pur-f-notes" class="form-label">Catatan</label>
                          <textarea id="pur-f-notes" class="form-control" rows="2" placeholder="Tujuan/alasan pengajuan"></textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                      <h3 class="card-title">Detail Item (Estimasi)</h3>
                      <div class="card-tools"><button type="button" class="btn btn-sm btn-outline-primary" id="pur-add-row-btn"><i class="bi bi-plus-lg me-1"></i>Tambah Baris</button></div>
                    </div>
                    <div class="card-body p-0">
                      <div class="table-responsive">
                        <table class="table pur-item-table align-middle mb-0">
                          <thead>
                            <tr>
                              <th style="width: 3rem">No</th>
                              <th style="min-width: 220px">Produk <span class="text-danger">*</span></th>
                              <th style="width: 100px">Unit</th>
                              <th style="width: 160px">Estimasi Harga <span class="text-danger">*</span></th>
                              <th style="width: 110px">Qty <span class="text-danger">*</span></th>
                              <th style="width: 160px" class="text-end">Subtotal</th>
                              <th style="width: 3.5rem"></th>
                            </tr>
                          </thead>
                          <tbody id="pur-item-rows"></tbody>
                        </table>
                      </div>
                      <div id="pur-items-empty" class="pur-state-box"><i class="bi bi-clipboard-plus"></i>Belum ada item.</div>
                    </div>
                    <div class="card-footer">
                      <div class="pur-totals-box">
                        <div class="row"><div class="col-6">Subtotal</div><div class="col-6 text-end" id="pur-total-subtotal">Rp 0</div></div>
                        <div class="row"><div class="col-6">PPN (<span id="pur-tax-rate-label">11</span>%)</div><div class="col-6 text-end" id="pur-total-tax">Rp 0</div></div>
                        <div class="row pur-grand-total border-top pt-2 mt-2"><div class="col-6">Grand Total (Estimasi)</div><div class="col-6 text-end" id="pur-total-grand">Rp 0</div></div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mb-3">
                  <a href="./pr-list.php" class="btn btn-outline-secondary">Batal</a>
                  <button type="submit" class="btn btn-primary" id="pur-submit-btn">
                    <span id="pur-submit-btn-label"><i class="bi bi-save me-1"></i>Simpan sebagai Draft</span>
                    <span id="pur-submit-btn-spinner" class="d-none"><span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…</span>
                  </button>
                </div>
              </div>
            </form>

            <template id="pur-row-template">
              <tr class="pur-item-row">
                <td class="pur-row-index text-secondary">1</td>
                <td>
                  <select class="form-select form-select-sm pur-f-product" required aria-label="Pilih produk"></select>
                  <div class="invalid-feedback">Pilih produk, dan pastikan belum dipakai di baris lain.</div>
                </td>
                <td class="pur-f-unit text-secondary">-</td>
                <td>
                  <div class="input-group input-group-sm"><span class="input-group-text">Rp</span>
                    <input type="number" class="form-control pur-f-price" min="0" step="1" required aria-label="Estimasi harga" />
                  </div>
                  <div class="invalid-feedback">Estimasi harga harus &ge; 0.</div>
                </td>
                <td>
                  <input type="number" class="form-control form-control-sm pur-f-qty" min="1" step="1" value="1" required aria-label="Qty" />
                  <div class="invalid-feedback">Qty harus lebih dari 0.</div>
                </td>
                <td class="text-end pur-line-subtotal">Rp 0</td>
                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger pur-remove-row-btn" aria-label="Hapus baris ini"><i class="bi bi-trash"></i></button></td>
              </tr>
            </template>
<?php require __DIR__ . '/partials/footer.php'; ?>
