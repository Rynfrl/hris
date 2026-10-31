/**
 * purchasing-pr-list.js
 * Server-side paginated PR list with search + status filter.
 */
(function () {
  'use strict';
  const { Fmt, Toast, Layout, Util, CONFIG } = window.Purchasing;

  let currentPage = 1;
  const pageSize = CONFIG.PAGE_SIZE;

  document.addEventListener('DOMContentLoaded', init);

  function init() {
    Layout.init('pr');
    document.getElementById('pur-pr-search').addEventListener('input', Util.debounce(() => { currentPage = 1; load(); }, 300));
    document.getElementById('pur-pr-status-filter').addEventListener('change', () => { currentPage = 1; load(); });
    document.getElementById('pur-pr-retry').addEventListener('click', load);
    load();
  }

  async function load() {
    const search = document.getElementById('pur-pr-search').value.trim();
    const status = document.getElementById('pur-pr-status-filter').value;
    document.getElementById('pur-pr-error').classList.add('d-none');
    try {
      const res = await window.PurchasingAPI.getRequisitions({ search, status, page: currentPage, pageSize });
      renderTable(res);
    } catch (err) {
      console.error(err);
      document.getElementById('pur-pr-error').classList.remove('d-none');
      document.getElementById('pur-pr-table-body').innerHTML = '';
      Toast.error(err.message || 'Gagal memuat PR.');
    }
  }

  function renderTable(res) {
    const tbody = document.getElementById('pur-pr-table-body');
    const emptyEl = document.getElementById('pur-pr-empty');
    if (res.data.length === 0) {
      tbody.innerHTML = '';
      emptyEl.classList.remove('d-none');
    } else {
      emptyEl.classList.add('d-none');
      tbody.innerHTML = res.data
        .map(
          (pr) => `
        <tr>
          <td><a href="./detail-pr.php?id=${pr.id}" class="fw-semibold text-decoration-none">${Util.escapeHtml(pr.pr_no)}</a></td>
          <td>${Util.escapeHtml(pr.department || '-')}</td>
          <td>${pr.requester ? Util.escapeHtml(pr.requester.name) : '-'}</td>
          <td class="text-end">${Fmt.currency(pr.grand_total)}</td>
          <td>${Fmt.statusBadge(pr.status)}</td>
          <td class="text-end"><a href="./detail-pr.php?id=${pr.id}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>Detail</a></td>
        </tr>`
        )
        .join('');
    }

    const totalPages = Math.max(1, Math.ceil(res.total / res.pageSize));
    const start = (res.page - 1) * res.pageSize;
    document.getElementById('pur-pr-pagination-info').textContent = res.total === 0 ? 'Tidak ada data' : `Menampilkan ${start + 1}-${Math.min(start + res.data.length, res.total)} dari ${res.total} PR`;
    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    const nav = document.getElementById('pur-pr-pagination');
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
