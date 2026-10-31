<?php
require __DIR__ . '/guard.php';
pur_page_require_role(['Admin']);
$pageTitle = 'Activity Log';
$activeNavKey = 'activity_log';
$pageScript = 'assets/js/purchasing-activity-log.js';
require __DIR__ . '/partials/header.php';
?>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Audit Trail</h3>
                <div class="card-tools d-flex gap-2">
                  <select id="pur-action-filter" class="form-select form-select-sm">
                    <option value="">Semua Aksi</option>
                    <option value="CREATE">CREATE</option>
                    <option value="UPDATE">UPDATE</option>
                    <option value="DELETE">DELETE</option>
                    <option value="APPROVE">APPROVE</option>
                    <option value="REJECT">REJECT</option>
                    <option value="CANCEL">CANCEL</option>
                    <option value="RECEIVE">RECEIVE</option>
                    <option value="RETURN">RETURN</option>
                    <option value="LOGIN">LOGIN</option>
                    <option value="LOGIN_FAILED">LOGIN_FAILED</option>
                  </select>
                  <select id="pur-entity-filter" class="form-select form-select-sm">
                    <option value="">Semua Entitas</option>
                    <option value="purchase_requisition">Purchase Requisition</option>
                    <option value="purchase_transaction">Purchase Order</option>
                    <option value="goods_receipt">GRN</option>
                    <option value="vendor_invoice">Vendor Invoice</option>
                    <option value="purchase_return">Purchase Return</option>
                    <option value="product">Produk</option>
                    <option value="supplier">Supplier</option>
                    <option value="auth">Auth</option>
                  </select>
                </div>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0 small">
                    <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Entitas</th><th>Ringkasan</th><th>IP</th></tr></thead>
                    <tbody id="pur-log-table-body"></tbody>
                  </table>
                </div>
                <div id="pur-log-empty" class="pur-state-box d-none"><i class="bi bi-inbox"></i>Tidak ada log yang cocok.</div>
              </div>
              <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-secondary" id="pur-pagination-info">-</small>
                <nav><ul class="pagination pagination-sm mb-0" id="pur-pagination"></ul></nav>
              </div>
            </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
