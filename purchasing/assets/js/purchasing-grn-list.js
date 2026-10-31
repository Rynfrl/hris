(function () {
  'use strict';
  const { Fmt, Toast, Layout, Util, CONFIG } = window.Purchasing;
  let currentPage = 1;
  const pageSize = CONFIG.PAGE_SIZE;

  document.addEventListener('DOMContentLoaded', () => {
    Layout.init('grn');
    document.getElementById('pur-grn-retry').addEventListener('click', load);
    load();
  });

  async function load() {
    document.getElementById('pur-grn-error').classList.add('d-none');
    try {
      const res = await window.PurchasingAPI.getGoodsReceipts({ page: currentPage, pageSize });
      renderTable(res);
    } catch (err) {
      console.error(err);
      document.getElementById('pur-grn-error').classList.remove('d-none');
      document.getElementById('pur-grn-table-body').innerHTML = '';
      Toast.error(err.message || 'Gagal memuat GRN.');
    }
  }

  function renderTable(res) {
    const tbody = document.getElementById('pur-grn-table-body');
    const emptyEl = document.getElementById('pur-grn-empty');
    if (res.data.length === 0) {
      tbody.innerHTML = '';
      emptyEl.classList.remove('d-none');
    } else {
      emptyEl.classList.add('d-none');
      tbody.innerHTML = res.data
        .map(
          (g) => `
        <tr>
          <td class="fw-semibold">${Util.escapeHtml(g.grn_no)}</td>
          <td>${g.purchase_transaction ? `<a href="./detail-transaction.php?id=${g.purchase_transaction.id}">${Util.escapeHtml(g.purchase_transaction.transaction_no)}</a>` : '-'}</td>
          <td>${Fmt.date(g.received_date)}</td>
          <td>${g.receiver ? Util.escapeHtml(g.receiver.name) : '-'}</td>
          <td class="text-end"><a href="./detail-grn.php?id=${g.id}" class="btn btn-sm btn-outline-secondary">Detail</a></td>
        </tr>`
        )
        .join('');
    }
    const totalPages = Math.max(1, Math.ceil(res.total / res.pageSize));
    const start = (res.page - 1) * res.pageSize;
    document.getElementById('pur-grn-pagination-info').textContent = res.total === 0 ? 'Tidak ada data' : `Menampilkan ${start + 1}-${Math.min(start + res.data.length, res.total)} dari ${res.total} GRN`;
    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    const nav = document.getElementById('pur-grn-pagination');
    if (totalPages <= 1) { nav.innerHTML = ''; return; }
    let html = pageItem(currentPage - 1, '&laquo;', currentPage === 1);
    for (let p = 1; p <= totalPages; p++) html += `<li class="page-item ${p === currentPage ? 'active' : ''}"><a class="page-link pur-pl" href="#" data-page="${p}">${p}</a></li>`;
    html += pageItem(currentPage + 1, '&raquo;', currentPage === totalPages);
    nav.innerHTML = html;
    nav.querySelectorAll('.pur-pl').forEach((a) => a.addEventListener('click', (e) => {
      e.preventDefault();
      const p = parseInt(a.getAttribute('data-page'), 10);
      if (!isNaN(p) && p >= 1 && p <= totalPages) { currentPage = p; load(); }
    }));
  }
  function pageItem(page, label, disabled) {
    return `<li class="page-item ${disabled ? 'disabled' : ''}"><a class="page-link pur-pl" href="#" data-page="${page}">${label}</a></li>`;
  }
})();
