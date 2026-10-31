<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Dashboard';
$activeNavKey = 'dashboard';
$useChartJs = true;
$pageScript = 'assets/js/purchasing-dashboard.js';
require __DIR__ . '/partials/header.php';
?>
            <!--begin::Metric cards-->
            <div class="row g-3" id="pur-metrics-row">
              <!-- filled by purchasing-dashboard.js; skeletons shown while loading -->
            </div>
            <!--end::Metric cards-->

            <div class="row g-3 mt-1">
              <!--begin::Chart-->
              <div class="col-lg-5">
                <div class="card h-100">
                  <div class="card-header">
                    <h3 class="card-title">Pengeluaran Purchasing (6 Bulan Terakhir)</h3>
                  </div>
                  <div class="card-body">
                    <canvas id="pur-spend-chart" height="220" aria-label="Grafik pengeluaran purchasing per bulan" role="img"></canvas>
                    <div id="pur-chart-empty" class="pur-state-box d-none">
                      <i class="bi bi-bar-chart" aria-hidden="true"></i>
                      Belum ada data pengeluaran untuk ditampilkan.
                    </div>
                  </div>
                </div>
              </div>
              <!--end::Chart-->

              <!--begin::Recent transactions-->
              <div class="col-lg-7">
                <div class="card h-100">
                  <div class="card-header">
                    <h3 class="card-title">Purchase Order</h3>
                    <div class="card-tools">
                      <?php if (in_array($currentUser['role'], ['Purchasing Staff', 'Admin'], true)): ?>
                      <a href="./create-transaction.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>PO Langsung
                      </a>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="card-body border-bottom">
                    <div class="row g-2">
                      <div class="col-sm-7">
                        <label for="pur-tx-search" class="visually-hidden">Cari transaksi</label>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text"><i class="bi bi-search"></i></span>
                          <input type="search" id="pur-tx-search" class="form-control" placeholder="Cari no. PO atau nama supplier…" />
                        </div>
                      </div>
                      <div class="col-sm-5">
                        <label for="pur-tx-status-filter" class="visually-hidden">Filter status</label>
                        <select id="pur-tx-status-filter" class="form-select form-select-sm">
                          <option value="">Semua Status</option>
                          <option value="Pending">Pending</option>
                          <option value="Partially Received">Partially Received</option>
                          <option value="Completed">Completed</option>
                          <option value="Cancelled">Cancelled</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table table-hover align-middle mb-0">
                        <thead>
                          <tr>
                            <th>No. PO</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th class="text-end">Grand Total</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                          </tr>
                        </thead>
                        <tbody id="pur-tx-table-body"></tbody>
                      </table>
                    </div>
                    <div id="pur-tx-empty" class="pur-state-box d-none">
                      <i class="bi bi-inbox" aria-hidden="true"></i>
                      Tidak ada PO yang cocok dengan pencarian/filter Anda.
                    </div>
                    <div id="pur-tx-error" class="pur-state-box d-none text-danger">
                      <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                      Gagal memuat data. <button class="btn btn-sm btn-outline-danger mt-2" id="pur-tx-retry">Coba Lagi</button>
                    </div>
                  </div>
                  <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-secondary" id="pur-tx-pagination-info">-</small>
                    <nav aria-label="Navigasi halaman"><ul class="pagination pagination-sm mb-0" id="pur-tx-pagination"></ul></nav>
                  </div>
                </div>
              </div>
              <!--end::Recent transactions-->
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
