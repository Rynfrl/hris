(function () {
  'use strict';
  const { Fmt, Toast, Layout, Auth, Util } = window.Purchasing;
  let currentInv = null;

  document.addEventListener('DOMContentLoaded', async () => {
    Layout.init('invoices');
    const id = Util.qs('id');
    if (!id) return showError('Parameter ID tidak ditemukan.');
    try {
      const inv = await window.PurchasingAPI.getInvoice(id);
      currentInv = inv;
      render(inv);
      document.getElementById('pur-detail-loading').classList.add('d-none');
      document.getElementById('pur-detail-content').classList.remove('d-none');
    } catch (err) {
      showError(err.message || 'Invoice tidak ditemukan.');
    }
  });

  function showError(msg) {
    document.getElementById('pur-detail-loading').classList.add('d-none');
    document.getElementById('pur-detail-error').classList.remove('d-none');
    document.getElementById('pur-detail-error-msg').textContent = msg;
  }

  function render(inv) {
    document.title = `Invoice ${inv.invoice_no} | Purchasing ERP`;
    document.getElementById('pur-invno').textContent = inv.invoice_no;
    const statusBadge = { 'Pending Review': 'text-bg-warning', 'Approved for Payment': 'text-bg-success', Rejected: 'text-bg-danger' };
    document.getElementById('pur-status-badge').innerHTML = `<span class="badge ${statusBadge[inv.status] || 'text-bg-secondary'}">${Util.escapeHtml(inv.status)}</span>`;

    document.getElementById('pur-info-invno').textContent = inv.invoice_no;
    document.getElementById('pur-info-po').innerHTML = inv.purchase_transaction ? `<a href="./detail-transaction.php?id=${inv.purchase_transaction.id}">${Util.escapeHtml(inv.purchase_transaction.transaction_no)}</a>` : '-';
    document.getElementById('pur-info-amount').textContent = Fmt.currency(inv.amount);
    document.getElementById('pur-info-date').textContent = Fmt.date(inv.invoice_date);
    document.getElementById('pur-info-entered').textContent = inv.entered_by_user ? `${inv.entered_by_user.name} (${inv.entered_by_user.role})` : '-';

    document.getElementById('pur-match-notes').textContent = inv.match_notes || '-';
    document.getElementById('pur-info-approver').textContent = inv.approved_by_user ? `${inv.approved_by_user.name} (${inv.approved_by_user.role})` : '-';
    document.getElementById('pur-info-approval-notes').textContent = inv.approval_notes || '-';

    const slot = document.getElementById('pur-match-alert-slot');
    if (inv.match_status === 'Matched') {
      slot.innerHTML = `<div class="col-12"><div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><strong>Matched</strong> - PO, GRN, dan Invoice cocok.</div></div>`;
    } else {
      slot.innerHTML = `<div class="col-12"><div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Mismatched</strong> - ada selisih antara PO, GRN, dan Invoice. Approval memerlukan catatan alasan.</div></div>`;
    }

    renderActions(inv);
  }

  function renderActions(inv) {
    const role = Auth.getCurrentUser().role;
    const container = document.getElementById('pur-action-buttons');
    if (inv.status !== 'Pending Review' || !['Finance Manager', 'Admin'].includes(role)) {
      container.innerHTML = '';
      return;
    }
    container.innerHTML = `
      <button class="btn btn-success" id="pur-btn-approve"><i class="bi bi-check-lg me-1"></i>Approve for Payment</button>
      <button class="btn btn-danger" id="pur-btn-reject"><i class="bi bi-x-lg me-1"></i>Reject</button>`;
    document.getElementById('pur-btn-approve').addEventListener('click', onApprove);
    document.getElementById('pur-btn-reject').addEventListener('click', onReject);
  }

  async function onApprove() {
    let notes = '';
    if (currentInv.match_status === 'Mismatched') {
      notes = window.prompt('Invoice ini Mismatched. Masukkan alasan/justifikasi untuk tetap menyetujui:') || '';
      if (!notes.trim()) {
        Toast.error('Approval invoice mismatched wajib disertai catatan.');
        return;
      }
    }
    try {
      const inv = await window.PurchasingAPI.approveInvoice(currentInv.id, notes.trim());
      currentInv = inv;
      Toast.success('Invoice disetujui untuk pembayaran.');
      render(inv);
    } catch (err) {
      Toast.error(err.message || 'Gagal menyetujui invoice.');
    }
  }

  async function onReject() {
    const notes = window.prompt('Alasan penolakan (wajib diisi):');
    if (notes === null) return;
    if (!notes.trim()) {
      Toast.error('Alasan penolakan wajib diisi.');
      return;
    }
    try {
      const inv = await window.PurchasingAPI.rejectInvoice(currentInv.id, notes.trim());
      currentInv = inv;
      Toast.warning('Invoice ditolak.');
      render(inv);
    } catch (err) {
      Toast.error(err.message || 'Gagal menolak invoice.');
    }
  }
})();
