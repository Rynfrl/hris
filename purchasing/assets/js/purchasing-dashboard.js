/**
 * purchasing-dashboard.js
 * Logic for dashboard.php: metric cards, monthly spend chart, and the PO
 * list (client-side search/filter/pagination over the recent-transactions
 * window returned by the API).
 */
(function () {
  'use strict';

  const { Fmt, Toast, Layout, Util, CONFIG } = window.Purchasing;

  let allTransactions = [];
  let filtered = [];
  let currentPage = 1;
  const pageSize = CONFIG.PAGE_SIZE;
  let chartInstance = null;

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    Layout.init('dashboard');
    bindEvents();
    await loadDashboard();
  }

  function bindEvents() {
    document.getElementById('pur-tx-search').addEventListener(
      'input',
      Util.debounce(() => {
        currentPage = 1;
        applyFilters();
      }, 250)
    );
    document.getElementById('pur-tx-status-filter').addEventListener('change', () => {
      currentPage = 1;
      applyFilters();
    });
    document.getElementById('pur-tx-retry').addEventListener('click', loadDashboard);
  }

  function metricDefs(metrics) {
    return [
      { label: 'Total PO Bulan Ini', icon: 'bi-receipt', color: 'primary', value: metrics ? metrics.total_transactions_this_month : null },
      { label: 'PO Belum Selesai', icon: 'bi-hourglass-split', color: 'warning', value: metrics ? metrics.pending_transactions : null },
      { label: 'Pengeluaran Bulan Ini', icon: 'bi-cash-stack', color: 'success', value: metrics ? Fmt.currency(metrics.total_spend_this_month) : null },
      { label: 'Supplier Aktif', icon: 'bi-truck', color: 'info', value: metrics ? metrics.active_suppliers : null },
      { label: 'PR Menunggu Approval', icon: 'bi-file-earmark-check', color: 'danger', value: metrics ? metrics.pr_pending_approval : null },
      { label: 'Invoice Menunggu Review', icon: 'bi-receipt-cutoff', color: 'secondary', value: metrics ? metrics.invoice_pending_review : null },
    ];
  }

  function renderMetricSkeletons() {
    const row = document.getElementById('pur-metrics-row');
    row.innerHTML = metricDefs(null)
      .map(
        (c) => `
      <div class="col-sm-6 col-xl-4">
        <div class="card pur-metric-card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="pur-metric-icon bg-${c.color}-subtle text-${c.color}"><i class="bi ${c.icon}"></i></div>
            <div>
              <div class="pur-metric-value pur-skeleton" style="width:70px;height:1.6rem">&nbsp;</div>
              <div class="text-secondary small">${c.label}</div>
            </div>
          </div>
        </div>
      </div>`
      )
      .join('');
  }

  function renderMetrics(metrics) {
    const row = document.getElementById('pur-metrics-row');
    row.innerHTML = metricDefs(metrics)
      .map(
        (c) => `
      <div class="col-sm-6 col-xl-4">
        <div class="card pur-metric-card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="pur-metric-icon bg-${c.color}-subtle text-${c.color}"><i class="bi ${c.icon}"></i></div>
            <div>
              <div class="pur-metric-value">${c.value}</div>
              <div class="text-secondary small">${c.label}</div>
            </div>
          </div>
        </div>
      </div>`
      )
      .join('');
  }

  function renderChart(chart) {
    const canvas = document.getElementById('pur-spend-chart');
    const emptyEl = document.getElementById('pur-chart-empty');
    const hasData = chart.values.some((v) => v > 0);

    if (!hasData) {
      canvas.classList.add('d-none');
      emptyEl.classList.remove('d-none');
      return;
    }
    canvas.classList.remove('d-none');
    emptyEl.classList.add('d-none');

    if (chartInstance) chartInstance.destroy();
    chartInstance = new Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: chart.labels,
        datasets: [{ label: 'Pengeluaran', data: chart.values, backgroundColor: 'rgba(47, 111, 79, 0.55)', borderColor: 'rgba(47, 111, 79, 1)', borderWidth: 1, borderRadius: 4 }],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => Fmt.currency(ctx.parsed.y) } } },
        scales: { y: { beginAtZero: true, ticks: { callback: (v) => Fmt.currency(v) } } },
      },
    });
  }

  function applyFilters() {
    const search = document.getElementById('pur-tx-search').value.trim().toLowerCase();
    const status = document.getElementById('pur-tx-status-filter').value;

    filtered = allTransactions.filter((t) => {
      const matchesSearch = !search || t.transaction_no.toLowerCase().includes(search) || (t.supplier && t.supplier.name.toLowerCase().includes(search));
      const matchesStatus = !status || t.status === status;
      return matchesSearch && matchesStatus;
    });

    renderTable();
  }

  function renderTable() {
    const tbody = document.getElementById('pur-tx-table-body');
    const emptyEl = document.getElementById('pur-tx-empty');
    const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
    currentPage = Math.min(currentPage, totalPages);
    const start = (currentPage - 1) * pageSize;
    const pageItems = filtered.slice(start, start + pageSize);

    if (filtered.length === 0) {
      tbody.innerHTML = '';
      emptyEl.classList.remove('d-none');
    } else {
      emptyEl.classList.add('d-none');
      tbody.innerHTML = pageItems
        .map(
          (t) => `
        <tr>
          <td><a href="./detail-transaction.php?id=${t.id}" class="fw-semibold text-decoration-none">${Util.escapeHtml(t.transaction_no)}</a></td>
          <td>${Fmt.date(t.transaction_date)}</td>
          <td>${t.supplier ? Util.escapeHtml(t.supplier.name) : '-'}</td>
          <td class="text-end">${Fmt.currency(t.grand_total)}</td>
          <td>${Fmt.statusBadge(t.status)}</td>
          <td class="text-end"><a href="./detail-transaction.php?id=${t.id}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>Detail</a></td>
        </tr>`
        )
        .join('');
    }

    document.getElementById('pur-tx-pagination-info').textContent =
      filtered.length === 0 ? 'Tidak ada data' : `Menampilkan ${start + 1}-${Math.min(start + pageItems.length, filtered.length)} dari ${filtered.length} PO`;

    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    const nav = document.getElementById('pur-tx-pagination');
    if (totalPages <= 1) {
      nav.innerHTML = '';
      return;
    }
    let html = pageItemHtml(currentPage - 1, '&laquo;', currentPage === 1);
    for (let p = 1; p <= totalPages; p++) {
      html += `<li class="page-item ${p === currentPage ? 'active' : ''}"><a class="page-link pur-page-link" href="#" data-page="${p}">${p}</a></li>`;
    }
    html += pageItemHtml(currentPage + 1, '&raquo;', currentPage === totalPages);
    nav.innerHTML = html;

    nav.querySelectorAll('.pur-page-link').forEach((a) => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        const p = parseInt(a.getAttribute('data-page'), 10);
        if (!isNaN(p) && p >= 1 && p <= totalPages) {
          currentPage = p;
          renderTable();
        }
      });
    });
  }

  function pageItemHtml(page, label, disabled) {
    return `<li class="page-item ${disabled ? 'disabled' : ''}"><a class="page-link pur-page-link" href="#" data-page="${page}">${label}</a></li>`;
  }

  async function loadDashboard() {
    document.getElementById('pur-tx-error').classList.add('d-none');
    renderMetricSkeletons();
    try {
      const data = await window.PurchasingAPI.getDashboard();
      renderMetrics(data.metrics);
      renderChart(data.chart);
      allTransactions = data.recent_transactions;
      currentPage = 1;
      applyFilters();
    } catch (err) {
      console.error(err);
      document.getElementById('pur-tx-error').classList.remove('d-none');
      document.getElementById('pur-tx-empty').classList.add('d-none');
      document.getElementById('pur-tx-table-body').innerHTML = '';
      Toast.error(err.message || 'Gagal memuat dashboard.');
    }
  }
})();
