(function () {
  'use strict';
  const { Fmt, Toast, Layout, Util, CONFIG } = window.Purchasing;
  let currentPage = 1;
  const pageSize = CONFIG.PAGE_SIZE;
  let eligiblePOs = [];

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    Layout.init('invoices');
    document.getElementById('pur-status-filter').addEventListener('change', () => { currentPage = 1; loadList(); });

    const form = document.getElementById('pur-invoice-form');
    if (form) {
      document.getElementById('pur-f-date').value = Fmt.dateInput(new Date());
      form.addEventListener('submit', onSubmit);
      try {
        const res = await window.PurchasingAPI.getTransactions({ pageSize: 100 });
        eligiblePOs = res.data.filter((t) => t.status !== 'Cancelled');
        const sel = document.getElementById('pur-f-po');
        sel.innerHTML = '<option value="">-- Pilih PO --</option>' + eligiblePOs.map((p) => `<option value="${p.id}">${Util.escapeHtml(p.transaction_no)} — ${p.supplier ? Util.escapeHtml(p.supplier.name) : '-'} (${Fmt.currency(p.grand_total)})</option>`).join('');
      } catch (err) {
        console.error(err);
      }
    }

    loadList();
  }

  async function loadList() {
    const status = document.getElementById('pur-status-filter').value;
    try {
      const res = await window.PurchasingAPI.getInvoices({ status, page: currentPage, pageSize });
      renderTable(res);
    } catch (err) {
      Toast.error(err.message || 'Gagal memuat invoice.');
    }
  }

  function renderTable(res) {
    const tbody = document.getElementById('pur-invoice-table-body');
    const emptyEl = document.getElementById('pur-invoice-empty');
    const statusBadge = { 'Pending Review': 'text-bg-warning', 'Approved for Payment': 'text-bg-success', Rejected: 'text-bg-danger' };
    const matchBadge = { Matched: 'text-bg-success', Mismatched: 'text-bg-danger' };

    if (res.data.length === 0) {
      tbody.innerHTML = '';
      emptyEl.classList.remove('d-none');
    } else {
      emptyEl.classList.add('d-none');
      tbody.innerHTML = res.data
        .map(
          (inv) => `
        <tr>
          <td class="fw-semibold">${Util.escapeHtml(inv.invoice_no)}</td>
          <td>${inv.purchase_transaction ? Util.escapeHtml(inv.purchase_transaction.transaction_no) : '-'}</td>
          <td class="text-end">${Fmt.currency(inv.amount)}</td>
          <td><span class="badge ${matchBadge[inv.match_status] || 'text-bg-secondary'}">${Util.escapeHtml(inv.match_status)}</span></td>
          <td><span class="badge ${statusBadge[inv.status] || 'text-bg-secondary'}">${Util.escapeHtml(inv.status)}</span></td>
          <td class="text-end"><a href="./invoice-detail.php?id=${inv.id}" class="btn btn-sm btn-outline-secondary">Detail</a></td>
        </tr>`
        )
        .join('');
    }

    const totalPages = Math.max(1, Math.ceil(res.total / res.pageSize));
    const start = (res.page - 1) * res.pageSize;
    document.getElementById('pur-pagination-info').textContent = res.total === 0 ? 'Tidak ada data' : `Menampilkan ${start + 1}-${Math.min(start + res.data.length, res.total)} dari ${res.total} invoice`;
    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    const nav = document.getElementById('pur-pagination');
    if (totalPages <= 1) { nav.innerHTML = ''; return; }
    let html = pageItem(currentPage - 1, '&laquo;', currentPage === 1);
    for (let p = 1; p <= totalPages; p++) html += `<li class="page-item ${p === currentPage ? 'active' : ''}"><a class="page-link pur-pl" href="#" data-page="${p}">${p}</a></li>`;
    html += pageItem(currentPage + 1, '&raquo;', currentPage === totalPages);
    nav.innerHTML = html;
    nav.querySelectorAll('.pur-pl').forEach((a) => a.addEventListener('click', (e) => {
      e.preventDefault();
      const p = parseInt(a.getAttribute('data-page'), 10);
      if (!isNaN(p) && p >= 1 && p <= totalPages) { currentPage = p; loadList(); }
    }));
  }
  function pageItem(page, label, disabled) {
    return `<li class="page-item ${disabled ? 'disabled' : ''}"><a class="page-link pur-pl" href="#" data-page="${page}">${label}</a></li>`;
  }

  async function onSubmit(e) {
    e.preventDefault();
    const errEl = document.getElementById('pur-form-error');
    errEl.classList.add('d-none');

    const payload = {
      purchase_transaction_id: Number(document.getElementById('pur-f-po').value),
      invoice_no: document.getElementById('pur-f-invoiceno').value.trim(),
      amount: Number(document.getElementById('pur-f-amount').value),
      invoice_date: document.getElementById('pur-f-date').value,
    };
    if (!payload.purchase_transaction_id || !payload.invoice_no || !payload.amount || !payload.invoice_date) {
      errEl.textContent = 'Semua field wajib diisi.';
      errEl.classList.remove('d-none');
      return;
    }

    const btn = document.getElementById('pur-submit-btn');
    btn.disabled = true;
    document.getElementById('pur-submit-btn-label').classList.add('d-none');
    document.getElementById('pur-submit-btn-spinner').classList.remove('d-none');
    try {
      const inv = await window.PurchasingAPI.createInvoice(payload);
      Toast.success(`Invoice tersimpan. Hasil 3-way match: ${inv.match_status}.`);
      window.location.href = `./invoice-detail.php?id=${inv.id}`;
    } catch (err) {
      errEl.textContent = err.message || 'Gagal menyimpan invoice.';
      errEl.classList.remove('d-none');
      btn.disabled = false;
      document.getElementById('pur-submit-btn-label').classList.remove('d-none');
      document.getElementById('pur-submit-btn-spinner').classList.add('d-none');
    }
  }
})();
