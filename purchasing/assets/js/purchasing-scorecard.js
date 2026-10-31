(function () {
  'use strict';
  const { Toast, Layout, Util } = window.Purchasing;

  document.addEventListener('DOMContentLoaded', async () => {
    Layout.init('scorecard');
    try {
      const res = await window.PurchasingAPI.getVendorScorecard();
      render(res.data);
    } catch (err) {
      Toast.error(err.message || 'Gagal memuat scorecard.');
    }
  });

  function render(rows) {
    const tbody = document.getElementById('pur-scorecard-body');
    const emptyEl = document.getElementById('pur-scorecard-empty');
    if (rows.length === 0) {
      tbody.innerHTML = '';
      emptyEl.classList.remove('d-none');
      return;
    }
    emptyEl.classList.add('d-none');
    tbody.innerHTML = rows
      .map((r) => {
        const rateLabel = r.on_time_rate_percent === null ? '<span class="text-secondary">- (belum ada data)</span>' : rateBadge(r.on_time_rate_percent);
        return `
        <tr>
          <td><strong>${Util.escapeHtml(r.code)}</strong> - ${Util.escapeHtml(r.name)}</td>
          <td class="text-end">${r.total_po}</td>
          <td class="text-end">${r.po_with_delivery_data}</td>
          <td class="text-end">${r.on_time_count}</td>
          <td class="text-end">${rateLabel}</td>
          <td class="text-end">${r.return_count > 0 ? `<span class="text-danger fw-semibold">${r.return_count}</span>` : '0'}</td>
          <td class="text-end">${r.total_qty_returned}</td>
        </tr>`;
      })
      .join('');
  }

  function rateBadge(rate) {
    const cls = rate >= 90 ? 'text-bg-success' : rate >= 70 ? 'text-bg-warning' : 'text-bg-danger';
    return `<span class="badge ${cls}">${rate}%</span>`;
  }
})();
