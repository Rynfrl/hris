/**
 * purchasing-detail-pr.js
 * PR detail page: renders the PR and wires up status/role-dependent
 * actions (Submit, Approve, Reject, Convert to PO).
 */
(function () {
  'use strict';
  const { Fmt, Toast, Layout, Auth, Util, Confirm } = window.Purchasing;

  let currentPr = null;

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    Layout.init('pr');
    const id = Util.qs('id');
    if (!id) return showError('Parameter ID PR tidak ditemukan.');
    await load(id);
  }

  function showError(msg) {
    document.getElementById('pur-detail-loading').classList.add('d-none');
    document.getElementById('pur-detail-content').classList.add('d-none');
    document.getElementById('pur-detail-error').classList.remove('d-none');
    document.getElementById('pur-detail-error-msg').textContent = msg;
  }

  async function load(id) {
    try {
      const pr = await window.PurchasingAPI.getRequisition(id);
      currentPr = pr;
      render(pr);
      document.getElementById('pur-detail-loading').classList.add('d-none');
      document.getElementById('pur-detail-content').classList.remove('d-none');
    } catch (err) {
      console.error(err);
      showError(err.message || 'PR tidak ditemukan.');
    }
  }

  function render(pr) {
    document.title = `${pr.pr_no} | Purchasing ERP`;
    document.getElementById('pur-trxno').textContent = pr.pr_no;
    document.getElementById('pur-status-badge').innerHTML = Fmt.statusBadge(pr.status);

    document.getElementById('pur-info-no').textContent = pr.pr_no;
    document.getElementById('pur-info-dept').textContent = pr.department || '-';
    document.getElementById('pur-info-needed').textContent = pr.needed_date ? Fmt.date(pr.needed_date) : '-';
    document.getElementById('pur-info-requester').textContent = pr.requester ? `${pr.requester.name} (${pr.requester.role})` : '-';
    document.getElementById('pur-info-notes').textContent = pr.notes || '-';

    document.getElementById('pur-info-threshold').textContent = pr.approval_threshold_snapshot != null ? Fmt.currency(pr.approval_threshold_snapshot) : '- (belum diajukan)';
    document.getElementById('pur-info-approver').textContent = pr.approver ? `${pr.approver.name} (${pr.approver.role})` : '-';
    document.getElementById('pur-info-rejection').textContent = pr.rejection_reason || '-';
    document.getElementById('pur-info-po').innerHTML = pr.converted_po ? `<a href="./detail-transaction.php?id=${pr.converted_po.id}">${Util.escapeHtml(pr.converted_po.transaction_no)}</a>` : '-';

    document.getElementById('pur-items-body').innerHTML = pr.details
      .map((d, idx) => `
        <tr>
          <td>${idx + 1}</td><td>${Util.escapeHtml(d.product_code)}</td><td>${Util.escapeHtml(d.product_name)}</td>
          <td class="text-end">${d.qty}</td><td>${Util.escapeHtml(d.unit)}</td>
          <td class="text-end">${Fmt.currency(d.estimated_unit_price)}</td><td class="text-end">${Fmt.currency(d.subtotal)}</td>
        </tr>`)
      .join('');
    document.getElementById('pur-subtotal').textContent = Fmt.currency(pr.subtotal);
    document.getElementById('pur-tax').textContent = Fmt.currency(pr.tax);
    document.getElementById('pur-grand').textContent = Fmt.currency(pr.grand_total);

    renderAlert(pr);
    renderActions(pr);
  }

  function renderAlert(pr) {
    const slot = document.getElementById('pur-status-alert-slot');
    const map = {
      Draft: ['alert-secondary', 'pencil', 'PR ini masih <strong>Draft</strong>. Ajukan untuk memulai proses approval.'],
      'Pending Approval': ['alert-warning', 'hourglass-split', 'Menunggu approval Finance Manager / Manager (nilai di atas ambang batas).'],
      Approved: ['alert-success', 'check-circle-fill', 'PR ini <strong>Approved</strong> dan siap dikonversi menjadi PO.'],
      Rejected: ['alert-danger', 'x-circle-fill', 'PR ini <strong>Rejected</strong>.'],
      Converted: ['alert-info', 'arrow-right-circle-fill', 'PR ini sudah dikonversi menjadi PO.'],
      Cancelled: ['alert-secondary', 'slash-circle', 'PR ini <strong>Cancelled</strong>.'],
    };
    const [cls, icon, msg] = map[pr.status] || map.Draft;
    slot.innerHTML = `<div class="col-12"><div class="alert ${cls} d-flex align-items-center"><i class="bi bi-${icon} me-2"></i>${msg}</div></div>`;
  }

  function renderActions(pr) {
    const role = Auth.getCurrentUser().role;
    const container = document.getElementById('pur-action-buttons');
    let html = '';

    if (pr.status === 'Draft' && ['Purchasing Staff', 'Admin'].includes(role)) {
      html += `<a href="./create-pr.php?id=${pr.id}" class="btn btn-outline-primary d-none"><i class="bi bi-pencil-square me-1"></i>Edit</a>`; // edit-in-place not wired yet, kept hidden intentionally
      html += `<button class="btn btn-primary" id="pur-btn-submit"><i class="bi bi-send me-1"></i>Ajukan untuk Approval</button>`;
    }
    if (pr.status === 'Pending Approval' && ['Finance Manager', 'Manager', 'Admin'].includes(role)) {
      html += `<button class="btn btn-success" id="pur-btn-approve"><i class="bi bi-check-lg me-1"></i>Approve</button>`;
      html += `<button class="btn btn-danger" id="pur-btn-reject"><i class="bi bi-x-lg me-1"></i>Reject</button>`;
    }
    if (pr.status === 'Approved' && ['Purchasing Staff', 'Admin'].includes(role)) {
      html += `<a href="./create-transaction.php?from_pr=${pr.id}" class="btn btn-success"><i class="bi bi-arrow-right-circle me-1"></i>Konversi ke PO</a>`;
    }

    container.innerHTML = html;
    const submitBtn = document.getElementById('pur-btn-submit');
    const approveBtn = document.getElementById('pur-btn-approve');
    const rejectBtn = document.getElementById('pur-btn-reject');
    if (submitBtn) submitBtn.addEventListener('click', onSubmit);
    if (approveBtn) approveBtn.addEventListener('click', onApprove);
    if (rejectBtn) rejectBtn.addEventListener('click', onReject);
  }

  async function onSubmit() {
    const ok = await Confirm.ask({ title: 'Ajukan PR', message: `Ajukan <strong>${Util.escapeHtml(currentPr.pr_no)}</strong> untuk approval? Jika nilai di bawah ambang batas, PR akan otomatis Approved.`, confirmText: 'Ya, Ajukan' });
    if (!ok) return;
    try {
      const pr = await window.PurchasingAPI.submitRequisition(currentPr.id);
      currentPr = pr;
      Toast.success(`Status PR sekarang: ${pr.status}`);
      render(pr);
    } catch (err) {
      Toast.error(err.message || 'Gagal mengajukan PR.');
    }
  }

  async function onApprove() {
    const ok = await Confirm.ask({ title: 'Approve PR', message: `Setujui <strong>${Util.escapeHtml(currentPr.pr_no)}</strong>?`, confirmText: 'Ya, Setujui', confirmClass: 'btn-success' });
    if (!ok) return;
    try {
      const pr = await window.PurchasingAPI.approveRequisition(currentPr.id);
      currentPr = pr;
      Toast.success('PR disetujui.');
      render(pr);
    } catch (err) {
      Toast.error(err.message || 'Gagal menyetujui PR.');
    }
  }

  async function onReject() {
    const reason = window.prompt('Alasan penolakan (wajib diisi):');
    if (reason === null) return;
    if (!reason.trim()) {
      Toast.error('Alasan penolakan wajib diisi.');
      return;
    }
    try {
      const pr = await window.PurchasingAPI.rejectRequisition(currentPr.id, reason.trim());
      currentPr = pr;
      Toast.warning('PR ditolak.');
      render(pr);
    } catch (err) {
      Toast.error(err.message || 'Gagal menolak PR.');
    }
  }
})();
