<?php
require __DIR__ . '/guard.php';
$pageTitle = 'Vendor Scorecard';
$activeNavKey = 'scorecard';
$pageScript = 'assets/js/purchasing-scorecard.js';
require __DIR__ . '/partials/header.php';
?>
            <div class="card">
              <div class="card-header"><h3 class="card-title">Performa Supplier</h3></div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Supplier</th>
                        <th class="text-end">Total PO</th>
                        <th class="text-end">PO dgn Data Kirim</th>
                        <th class="text-end">Tepat Waktu</th>
                        <th class="text-end">On-Time Rate</th>
                        <th class="text-end">Jumlah Retur</th>
                        <th class="text-end">Qty Diretur</th>
                      </tr>
                    </thead>
                    <tbody id="pur-scorecard-body"></tbody>
                  </table>
                </div>
                <div id="pur-scorecard-empty" class="pur-state-box d-none"><i class="bi bi-bar-chart"></i>Belum ada data untuk dihitung.</div>
              </div>
              <div class="card-footer text-secondary small">
                <i class="bi bi-info-circle me-1"></i>
                On-Time Rate dihitung dari PO yang punya "Perkiraan Tanggal Kirim" dan sudah ada GRN - dibandingkan tanggal GRN pertama terhadap tanggal yang dijanjikan.
              </div>
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
