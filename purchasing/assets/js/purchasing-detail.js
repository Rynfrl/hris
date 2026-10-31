/**
 * purchasing-detail.js
 * Logic for detail-transaction.php: render the PO as a printable document,
 * show GRN receiving history, and wire up status/role-dependent actions.
 *
 * v2: the old manual "Complete Transaction" button is GONE - completion is
 * now driven entirely by Goods Receipts (see purchasing-create-grn.js).
 * This page instead offers "Buat GRN" (Warehouse/Admin, while Pending or
 * Partially Received) and keeps Cancel (Purchasing Staff/Admin, only
 * while still Pending).
 */
(function () {
  'use strict';

  const { Fmt, Toast, Layout, Auth, Util, CONFIG, Confirm } = window.Purchasing;

  let currentTx = null;

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    Layout.init('pr');
    const id = Util.qs('id');
    if (!id) {
      showError('Parameter ID PO tidak ditemukan pada URL.');
      return;
    }
    await loadTransaction(id);
  }

  function showError(message) {
    document.getElementById('pur-detail-loading').classList.add('d-none');
    document.getElementById('pur-detail-content').classList.add('d-none');
    document.getElementById('pur-detail-error').classList.remove('d-none');
    document.getElementById('pur-detail-error-msg').textContent = message;
  }

  async function loadTransaction(id) {
    try {
      const tx = await window.PurchasingAPI.getTransaction(id);
      currentTx = tx;
      renderInvoice(tx);
      renderActions(tx);
      renderStatusAlert(tx);
      await loadGrnHistory(id);
      document.getElementById('pur-detail-loading').classList.add('d-none');
      document.getElementById('pur-detail-content').classList.remove('d-none');
    } catch (err) {
      console.error(err);
      showError(err.message || 'PO tidak ditemukan.');
    }
  }

  function renderInvoice(tx) {
    document.title = `PO ${tx.transaction_no} | Purchasing ERP`;
    document.getElementById('pur-detail-trxno').textContent = tx.transaction_no;
    document.getElementById('pur-detail-status-badge').innerHTML = Fmt.statusBadge(tx.status);

    document.getElementById('pur-inv-trxno').textContent = tx.transaction_no;
    document.getElementById('pur-inv-info-trxno').textContent = tx.transaction_no;
    document.getElementById('pur-inv-info-date').textContent = Fmt.date(tx.transaction_date);
    document.getElementById('pur-inv-info-expected').textContent = tx.expected_delivery_date ? Fmt.date(tx.expected_delivery_date) : '-';
    document.getElementById('pur-inv-info-status').innerHTML = Fmt.statusBadge(tx.status);
    document.getElementById('pur-inv-info-user').textContent = tx.user ? tx.user.name : '-';
    document.getElementById('pur-inv-info-pr').innerHTML = tx.purchase_requisition
      ? `<a href="./detail-pr.php?id=${tx.purchase_requisition.id}">${Util.escapeHtml(tx.purchase_requisition.pr_no)}</a>`
      : '<span class="text-secondary">Langsung (bukan dari PR)</span>';

    document.getElementById('pur-inv-sup-code').textContent = tx.supplier ? tx.supplier.code : '-';
    document.getElementById('pur-inv-sup-name').textContent = tx.supplier ? tx.supplier.name : '-';
    document.getElementById('pur-inv-sup-phone').textContent = tx.supplier ? tx.supplier.phone : '-';
    document.getElementById('pur-inv-sup-email').textContent = tx.supplier ? tx.supplier.email : '-';
    document.getElementById('pur-inv-sup-address').textContent = tx.supplier ? tx.supplier.address : '-';

    document.getElementById('pur-inv-items').innerHTML = tx.details
      .map(
        (d, idx) => `
      <tr>
        <td>${idx + 1}</td>
        <td>${Util.escapeHtml(d.product_code)}</td>
        <td>${Util.escapeHtml(d.product_name)}</td>
        <td class="text-end">${d.qty}</td>
        <td class="text-end">${d.qty_received}</td>
        <td class="text-end">${d.qty_outstanding > 0 ? `<span class="text-warning fw-semibold">${d.qty_outstanding}</span>` : '0'}</td>
        <td>${Util.escapeHtml(d.unit)}</td>
        <td class="text-end">${Fmt.currency(d.unit_price)}</td>
        <td class="text-end">${Fmt.currency(d.subtotal)}</td>
      </tr>`
      )
      .join('');

    document.getElementById('pur-inv-notes').textContent = tx.notes || '(tidak ada catatan)';
    document.getElementById('pur-inv-tax-rate').textContent = Math.round(CONFIG.TAX_RATE * 100);
    document.getElementById('pur-inv-subtotal').textContent = Fmt.currency(tx.subtotal);
    document.getElementById('pur-inv-tax').textContent = Fmt.currency(tx.tax);
    document.getElementById('pur-inv-grand').textContent = Fmt.currency(tx.grand_total);
  }

  function renderStatusAlert(tx) {
    const slot = document.getElementById('pur-status-alert-slot');
    const map = {
      Completed: ['alert-success', 'check-circle-fill', `PO ini sudah <strong>Completed</strong> - seluruh item telah diterima penuh lewat GRN.`],
      Cancelled: ['alert-danger', 'x-circle-fill', 'PO ini telah <strong>Cancelled</strong> dan tidak dapat diproses lebih lanjut.'],
      'Partially Received': ['alert-info', 'truck', 'Sebagian item sudah diterima. Buat GRN lagi begitu sisa barang datang.'],
      Pending: ['alert-warning', 'hourglass-split', 'PO ini masih <strong>Pending</strong> - belum ada barang diterima.'],
    };
    const [cls, icon, msg] = map[tx.status] || map.Pending;
    slot.innerHTML = `<div class="col-12"><div class="alert ${cls} d-flex align-items-center" role="status"><i class="bi bi-${icon} me-2"></i>${msg}</div></div>`;
  }

  function renderActions(tx) {
    const container = document.getElementById('pur-action-buttons');
    const role = Auth.getCurrentUser().role;
    let html = `<button type="button" class="btn btn-outline-secondary" id="pur-btn-print"><i class="bi bi-printer me-1"></i>Print</button>`;

    if (tx.status === 'Pending') {
      if (['Purchasing Staff', 'Admin'].includes(role)) {
        html += `<a href="./create-transaction.php?id=${tx.id}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>`;
        html += `<button type="button" class="btn btn-danger" id="pur-btn-cancel"><i class="bi bi-x-lg me-1"></i>Cancel PO</button>`;
      }
    }
    if (['Pending', 'Partially Received'].includes(tx.status) && ['Warehouse Staff', 'Admin'].includes(role)) {
      html += `<a href="./create-grn.php?po_id=${tx.id}" class="btn btn-success"><i class="bi bi-box-arrow-in-down me-1"></i>Buat GRN</a>`;
    }

    container.innerHTML = html;
    document.getElementById('pur-btn-print').addEventListener('click', () => window.print());
    const cancelBtn = document.getElementById('pur-btn-cancel');
    if (cancelBtn) cancelBtn.addEventListener('click', onCancel);
  }

  async function loadGrnHistory(poId) {
    const tbody = document.getElementById('pur-grn-history-body');
    const emptyEl = document.getElementById('pur-grn-history-empty');
    try {
      const res = await window.PurchasingAPI.getGoodsReceipts({ purchase_transaction_id: poId, pageSize: 50 });
      if (res.data.length === 0) {
        tbody.innerHTML = '';
        emptyEl.classList.remove('d-none');
        return;
      }
      emptyEl.classList.add('d-none');
      tbody.innerHTML = res.data
        .map(
          (g) => `
        <tr>
          <td class="fw-semibold">${Util.escapeHtml(g.grn_no)}</td>
          <td>${Fmt.date(g.received_date)}</td>
          <td>${g.receiver ? Util.escapeHtml(g.receiver.name) : '-'}</td>
          <td>${Util.escapeHtml(g.notes || '-')}</td>
          <td class="text-end"><a href="./detail-grn.php?id=${g.id}" class="btn btn-sm btn-outline-secondary">Detail</a></td>
        </tr>`
        )
        .join('');
    } catch (err) {
      console.error(err);
    }
  }

  async function onCancel() {
    const ok = await Confirm.ask({
      title: 'Cancel PO',
      message: `Batalkan PO <strong>${Util.escapeHtml(currentTx.transaction_no)}</strong>? Tindakan ini tidak dapat dibatalkan kembali.`,
      confirmText: 'Ya, Batalkan',
      confirmClass: 'btn-danger',
    });
    if (!ok) return;
    try {
      const tx = await window.PurchasingAPI.cancelTransaction(currentTx.id);
      currentTx = tx;
      Toast.warning(`PO ${tx.transaction_no} telah dibatalkan.`);
      renderInvoice(tx);
      renderActions(tx);
      renderStatusAlert(tx);
    } catch (err) {
      console.error(err);
      Toast.error(err.message || 'Gagal membatalkan PO.');
    }
  }
})();
