(function () {
  'use strict';
  const { Fmt, Layout, Util } = window.Purchasing;

  document.addEventListener('DOMContentLoaded', async () => {
    Layout.init('grn');
    const id = Util.qs('id');
    if (!id) return showError('Parameter ID GRN tidak ditemukan.');
    try {
      const grn = await window.PurchasingAPI.getGoodsReceipt(id);
      render(grn);
      document.getElementById('pur-detail-loading').classList.add('d-none');
      document.getElementById('pur-detail-content').classList.remove('d-none');
    } catch (err) {
      showError(err.message || 'GRN tidak ditemukan.');
    }
  });

  function showError(msg) {
    document.getElementById('pur-detail-loading').classList.add('d-none');
    document.getElementById('pur-detail-error').classList.remove('d-none');
    document.getElementById('pur-detail-error-msg').textContent = msg;
  }

  function render(grn) {
    document.title = `${grn.grn_no} | Purchasing ERP`;
    document.getElementById('pur-grn-no').textContent = grn.grn_no;
    document.getElementById('pur-inv-no').textContent = grn.grn_no;
    document.getElementById('pur-info-no').textContent = grn.grn_no;
    document.getElementById('pur-info-po').innerHTML = grn.purchase_transaction ? `<a href="./detail-transaction.php?id=${grn.purchase_transaction.id}">${Util.escapeHtml(grn.purchase_transaction.transaction_no)}</a>` : '-';
    document.getElementById('pur-info-date').textContent = Fmt.date(grn.received_date);
    document.getElementById('pur-info-receiver').textContent = grn.receiver ? `${grn.receiver.name} (${grn.receiver.role})` : '-';
    document.getElementById('pur-info-notes').textContent = grn.notes || '-';

    document.getElementById('pur-items-body').innerHTML = grn.details
      .map((d, idx) => `
        <tr>
          <td>${idx + 1}</td><td>${Util.escapeHtml(d.product_code)}</td><td>${Util.escapeHtml(d.product_name)}</td>
          <td>${Util.escapeHtml(d.unit)}</td><td class="text-end">${d.qty_received}</td>
          <td>${badgeForCondition(d.condition_note)}</td>
        </tr>`)
      .join('');
  }

  function badgeForCondition(c) {
    const map = { Good: 'text-bg-success', Damaged: 'text-bg-danger', Shortage: 'text-bg-warning' };
    return `<span class="badge ${map[c] || 'text-bg-secondary'}">${Util.escapeHtml(c)}</span>`;
  }
})();
