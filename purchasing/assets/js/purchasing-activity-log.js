(function () {
  'use strict';
  const { Fmt, Toast, Layout, Util, CONFIG } = window.Purchasing;
  let currentPage = 1;
  const pageSize = 20;

  document.addEventListener('DOMContentLoaded', () => {
    Layout.init('activity_log');
    document.getElementById('pur-action-filter').addEventListener('change', () => { currentPage = 1; load(); });
    document.getElementById('pur-entity-filter').addEventListener('change', () => { currentPage = 1; load(); });
    load();
  });

  const actionBadge = {
    CREATE: 'text-bg-primary', UPDATE: 'text-bg-info', DELETE: 'text-bg-dark', APPROVE: 'text-bg-success',
    REJECT: 'text-bg-danger', CANCEL: 'text-bg-secondary', RECEIVE: 'text-bg-success', RETURN: 'text-bg-warning',
    LOGIN: 'text-bg-light', LOGIN_FAILED: 'text-bg-danger', LOGOUT: 'text-bg-light',
  };

  async function load() {
    const action = document.getElementById('pur-action-filter').value;
    const entity_type = document.getElementById('pur-entity-filter').value;
    try {
      const res = await window.PurchasingAPI.getActivityLog({ action, entity_type, page: currentPage, pageSize });
      renderTable(res);
    } catch (err) {
      Toast.error(err.message || 'Gagal memuat activity log.');
    }
  }

  function renderTable(res) {
    const tbody = document.getElementById('pur-log-table-body');
    const emptyEl = document.getElementById('pur-log-empty');
    if (res.data.length === 0) {
      tbody.innerHTML = '';
      emptyEl.classList.remove('d-none');
    } else {
      emptyEl.classList.add('d-none');
      tbody.innerHTML = res.data
        .map(
          (r) => `
        <tr>
          <td class="text-nowrap">${Fmt.date(r.created_at, true)}</td>
          <td>${r.user_name ? Util.escapeHtml(r.user_name) + ' <span class="text-secondary">(' + Util.escapeHtml(r.user_role || '') + ')</span>' : '<span class="text-secondary">-</span>'}</td>
          <td><span class="badge ${actionBadge[r.action] || 'text-bg-secondary'}">${Util.escapeHtml(r.action)}</span></td>
          <td>${Util.escapeHtml(r.entity_type)}${r.entity_id ? ' #' + r.entity_id : ''}</td>
          <td>${Util.escapeHtml(r.summary || '-')}</td>
          <td class="text-secondary">${Util.escapeHtml(r.ip_address || '-')}</td>
        </tr>`
        )
        .join('');
    }
    const totalPages = Math.max(1, Math.ceil(res.total / res.pageSize));
    const start = (res.page - 1) * res.pageSize;
    document.getElementById('pur-pagination-info').textContent = res.total === 0 ? 'Tidak ada data' : `Menampilkan ${start + 1}-${Math.min(start + res.data.length, res.total)} dari ${res.total} log`;
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
      if (!isNaN(p) && p >= 1 && p <= totalPages) { currentPage = p; load(); }
    }));
  }
  function pageItem(page, label, disabled) {
    return `<li class="page-item ${disabled ? 'disabled' : ''}"><a class="page-link pur-pl" href="#" data-page="${page}">${label}</a></li>`;
  }
})();
