/**
 * reports.js - Logika Laporan & Analisis Inventory
 * Inventoria - Modern Inventory Management & Bookkeeping
 */

(function () {
  'use strict';

  // State Manajemen Laporan
  const state = {
    startDate: '',
    endDate: '',
    categoryId: 'all',
    scope: 'all',
    sortBy: 'out_desc',
    search: '',
    activeTab: 'ledger',
    currentPage: 1,
    pageSize: 10,
    loading: false,
    reportData: null,
    categoriesLoaded: false,
    chartInstance: null
  };

  // Helper Formatter
  function getTodayDateString() {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  function formatRupiah(amount) {
    const num = Math.round(Number(amount) || 0);
    return 'Rp' + num.toLocaleString('id-ID');
  }

  function formatNumber(num) {
    const val = Number(num) || 0;
    return val.toLocaleString('id-ID');
  }

  function formatDateId(dateStr) {
    if (!dateStr) return '-';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
  }

  // DOM Elements Cache
  const elements = {
    // Filter Controls
    startDate: document.getElementById('filter-start-date'),
    endDate: document.getElementById('filter-end-date'),
    category: document.getElementById('filter-category'),
    scope: document.getElementById('filter-scope'),
    sortBy: document.getElementById('filter-sort-by'),
    search: document.getElementById('filter-search'),
    btnApply: document.getElementById('btn-apply-filter'),
    btnReset: document.getElementById('btn-reset-filter'),
    activePeriodBadge: document.getElementById('active-period-badge'),
    infoStartDate: document.getElementById('info-start-date'),
    infoEndDate: document.getElementById('info-end-date'),
    btnPrint: document.getElementById('btn-print-report'),
    btnDownloadPdf: document.getElementById('btn-download-pdf'),

    // Tabs
    tabButtons: document.querySelectorAll('.report-tab-btn'),
    tabContents: document.querySelectorAll('.tab-content'),

    // KPI Tab 1
    kpiInQty: document.getElementById('kpi-in-qty'),
    kpiInVal: document.getElementById('kpi-in-val'),
    kpiOutQty: document.getElementById('kpi-out-qty'),
    kpiOutVal: document.getElementById('kpi-out-val'),
    kpiFinalQty: document.getElementById('kpi-final-qty'),
    kpiFinalVal: document.getElementById('kpi-final-val'),
    kpiCriticalCount: document.getElementById('kpi-critical-count'),

    // Reconciliation Bar
    reconInitial: document.getElementById('recon-initial-qty'),
    reconIn: document.getElementById('recon-in-qty'),
    reconOut: document.getElementById('recon-out-qty'),
    reconFinal: document.getElementById('recon-final-qty'),
    reconStatus: document.getElementById('recon-status-badge'),

    // Trend & Side Panels
    chartCanvas: document.getElementById('stockTrendChart'),
    topProductsList: document.getElementById('top-products-list'),
    criticalProductsList: document.getElementById('critical-products-list'),

    // Ledger Table Tab 1
    tableBody: document.getElementById('ledger-table-body'),
    tableFoot: document.getElementById('ledger-table-foot'),
    tableInfo: document.getElementById('ledger-table-info'),
    tablePagination: document.getElementById('ledger-pagination'),
    emptyState: document.getElementById('ledger-empty-state'),
    pageSizeSelect: document.getElementById('page-size-select'),

    // Journal Table Tab 2
    journalTableBody: document.getElementById('journal-table-body'),
    journalCountBadge: document.getElementById('journal-count-badge'),

    // Valuation Tab 3
    valActiveQty: document.getElementById('val-active-qty'),
    valQuarantineQty: document.getElementById('val-quarantine-qty'),
    valDraftQty: document.getElementById('val-draft-qty'),
    categoryValuationTableBody: document.getElementById('category-valuation-table-body'),

    // Modal Kartu Stok
    stockCardModal: document.getElementById('stock-card-modal'),
    scTitle: document.getElementById('sc-modal-title'),
    scSku: document.getElementById('sc-sku'),
    scCategoryLoc: document.getElementById('sc-category-loc'),
    scPrice: document.getElementById('sc-price'),
    scMinStock: document.getElementById('sc-min-stock'),
    scPeriodRange: document.getElementById('sc-period-range'),
    scInitialQty: document.getElementById('sc-initial-qty'),
    scInQty: document.getElementById('sc-in-qty'),
    scOutQty: document.getElementById('sc-out-qty'),
    scFinalQty: document.getElementById('sc-final-qty'),
    scMovementsTbody: document.getElementById('sc-movements-tbody'),
    btnCloseModal: document.getElementById('btn-close-modal'),
    btnModalDone: document.getElementById('btn-modal-done'),

    // Print Container
    printContainer: document.getElementById('official-print-document')
  };

  // Inisialisasi
  document.addEventListener('DOMContentLoaded', init);

  function init() {
    if (elements.startDate) elements.startDate.value = state.startDate;
    if (elements.endDate) elements.endDate.value = state.endDate;

    setupEventListeners();
    fetchReport();
  }

  function setupEventListeners() {
    // 1. Terapkan Filter
    if (elements.btnApply) {
      elements.btnApply.addEventListener('click', applyFilters);
    }

    // 2. Reset Filter
    if (elements.btnReset) {
      elements.btnReset.addEventListener('click', resetFilters);
    }

    // 3. Enter pada Search Input
    if (elements.search) {
      elements.search.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') applyFilters();
      });
    }

    // 4. Perubahan Page Size
    if (elements.pageSizeSelect) {
      elements.pageSizeSelect.addEventListener('change', (e) => {
        state.pageSize = parseInt(e.target.value, 10) || 10;
        state.currentPage = 1;
        renderLedgerTable();
      });
    }

    // 5. Navigasi Tab Laporan
    elements.tabButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetTab = btn.getAttribute('data-tab');
        switchTab(targetTab);
      });
    });

    // 6. Cetak Laporan & Unduh PDF
    if (elements.btnPrint) {
      elements.btnPrint.addEventListener('click', triggerOfficialPrint);
    }
    if (elements.btnDownloadPdf) {
      elements.btnDownloadPdf.addEventListener('click', triggerOfficialPrint);
    }

    // 7. Tutup Modal Kartu Stok
    if (elements.btnCloseModal) elements.btnCloseModal.addEventListener('click', closeModal);
    if (elements.btnModalDone) elements.btnModalDone.addEventListener('click', closeModal);
    if (elements.stockCardModal) {
      elements.stockCardModal.addEventListener('click', (e) => {
        if (e.target === elements.stockCardModal) closeModal();
      });
    }
  }

  function switchTab(tabName) {
    state.activeTab = tabName;

    elements.tabButtons.forEach((b) => {
      b.classList.toggle('active', b.getAttribute('data-tab') === tabName);
    });

    elements.tabContents.forEach((c) => {
      c.classList.toggle('active', c.id === `tab-${tabName}`);
    });

    // Jika masuk ke tab ledger, pastikan chart ter-resize dengan baik
    if (tabName === 'ledger' && state.chartInstance) {
      setTimeout(() => state.chartInstance.resize(), 50);
    }
  }

  function applyFilters() {
    if (elements.startDate) state.startDate = elements.startDate.value;
    if (elements.endDate) state.endDate = elements.endDate.value;
    if (elements.category) state.categoryId = elements.category.value;
    if (elements.scope) state.scope = elements.scope.value;
    if (elements.sortBy) state.sortBy = elements.sortBy.value;
    if (elements.search) state.search = elements.search.value.trim();

    state.currentPage = 1;
    fetchReport();
  }

  function resetFilters() {
    state.startDate = '';
    state.endDate = '';
    state.categoryId = 'all';
    state.scope = 'all';
    state.sortBy = 'out_desc';
    state.search = '';
    state.currentPage = 1;

    if (elements.startDate) elements.startDate.value = '';
    if (elements.endDate) elements.endDate.value = '';
    if (elements.category) elements.category.value = 'all';
    if (elements.scope) elements.scope.value = 'all';
    if (elements.sortBy) elements.sortBy.value = 'out_desc';
    if (elements.search) elements.search.value = '';

    fetchReport();
  }

  async function fetchReport() {
    setLoading(true);

    try {
      const params = new URLSearchParams({
        start_date: state.startDate,
        end_date: state.endDate,
        category_id: state.categoryId,
        scope: state.scope,
        sort_by: state.sortBy,
        search: state.search
      });

      const response = await fetch(`../api/reports.php?${params.toString()}`);
      if (!response.ok) {
        throw new Error(`HTTP error ${response.status}`);
      }

      const res = await response.json();
      if (res.status !== 'success') {
        throw new Error(res.message || 'Gagal memuat data laporan');
      }

      state.reportData = res.data;

      // 1. Sinkronkan Kategori Dropdown
      populateCategories(res.data.categories);

      // 2. Sinkronkan Label & Badge Periode Aktif
      updatePeriodDisplay(res.data.period);

      // 3. Render Tab 1: KPI, Rekonsiliasi, Grafik, Top & Kritis, Tabel Ledger
      renderKPI(res.data.kpi);
      renderChart(res.data.chart_trend);
      renderTopProducts(res.data.top_products);
      renderCriticalProducts(res.data.critical_products);
      renderLedgerTable();

      // 4. Render Tab 2: Jurnal Transaksi
      renderJournalTable(res.data.journal_movements);

      // 5. Render Tab 3: Valuasi & Kategori
      renderValuationTab(res.data);

      // 6. Siapkan Dokumen Cetak / PDF Resmi
      preparePrintDocument(res.data);

    } catch (err) {
      console.error('Error fetching report:', err);
      showErrorMessage(err.message || 'Gagal memuat data laporan.');
    } finally {
      setLoading(false);
    }
  }

  function setLoading(isLoading) {
    state.loading = isLoading;
    if (elements.btnApply) {
      elements.btnApply.disabled = isLoading;
      elements.btnApply.innerHTML = isLoading
        ? '<span class="spinner-small"></span> Memuat...'
        : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg> Terapkan Filter';
    }
  }

  function populateCategories(categories) {
    if (state.categoriesLoaded || !elements.category || !Array.isArray(categories)) return;

    elements.category.innerHTML = '<option value="all">Semua Kategori</option>';
    categories.forEach((cat) => {
      const opt = document.createElement('option');
      opt.value = cat.id;
      opt.textContent = cat.name;
      elements.category.appendChild(opt);
    });

    elements.category.value = state.categoryId;
    state.categoriesLoaded = true;
  }

  function updatePeriodDisplay(period) {
    if (!period) return;
    const startFormatted = period.start_date ? formatDateId(period.start_date) : 'Awal Pembukuan';
    const endFormatted = period.end_date ? formatDateId(period.end_date) : 'Hari Ini';
    const formatted = period.formatted_range || (period.start_date && period.end_date ? `${startFormatted} – ${endFormatted}` : 'Keseluruhan Periode');

    if (elements.infoStartDate) elements.infoStartDate.textContent = startFormatted;
    if (elements.infoEndDate) elements.infoEndDate.textContent = endFormatted;

    if (elements.activePeriodBadge) {
      elements.activePeriodBadge.innerHTML = `
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <span>Periode Aktif: <strong>${escapeHtml(formatted)}</strong></span>
      `;
    }
  }

  function renderKPI(kpi) {
    if (!kpi) return;

    if (elements.kpiInQty) elements.kpiInQty.textContent = `+${formatNumber(kpi.total_in_qty)} unit`;
    if (elements.kpiInVal) elements.kpiInVal.textContent = `Nilai: ${formatRupiah(kpi.total_in_val)}`;

    if (elements.kpiOutQty) elements.kpiOutQty.textContent = `-${formatNumber(kpi.total_out_qty)} unit`;
    if (elements.kpiOutVal) elements.kpiOutVal.textContent = `Nilai: ${formatRupiah(kpi.total_out_val)}`;

    if (elements.kpiFinalQty) elements.kpiFinalQty.textContent = `${formatNumber(kpi.final_stock_qty)} unit`;
    if (elements.kpiFinalVal) elements.kpiFinalVal.textContent = `Valuasi: ${formatRupiah(kpi.final_stock_val)}`;

    if (elements.kpiCriticalCount) elements.kpiCriticalCount.textContent = `${formatNumber(kpi.critical_count)} SKU`;

    // Persamaan Rekonsiliasi
    if (elements.reconInitial) elements.reconInitial.textContent = `${formatNumber(kpi.total_initial_stock)} unit`;
    if (elements.reconIn) elements.reconIn.textContent = `+${formatNumber(kpi.total_in_qty)} unit`;
    if (elements.reconOut) elements.reconOut.textContent = `-${formatNumber(kpi.total_out_qty)} unit`;
    if (elements.reconFinal) elements.reconFinal.textContent = `${formatNumber(kpi.final_stock_qty)} unit`;

    // Verifikasi Integritas
    const expected = Number(kpi.total_initial_stock) + Number(kpi.total_in_qty) - Number(kpi.total_out_qty);
    const isBalanced = Math.abs(expected - Number(kpi.final_stock_qty)) < 0.001;

    if (elements.reconStatus) {
      if (isBalanced) {
        elements.reconStatus.className = 'recon-status-badge balanced';
        elements.reconStatus.innerHTML = `
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
          Buku Besar Seimbang (Balance Matched)
        `;
      } else {
        elements.reconStatus.className = 'recon-status-badge discrepancy';
        elements.reconStatus.innerHTML = `
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          Selisih Terdeteksi
        `;
      }
    }
  }

  function renderChart(chartTrend) {
    if (!elements.chartCanvas) return;

    const labels = (chartTrend && chartTrend.labels) || [];
    const inData = (chartTrend && chartTrend.in_data) || [];
    const outData = (chartTrend && chartTrend.out_data) || [];

    if (typeof Chart !== 'undefined') {
      if (state.chartInstance) {
        state.chartInstance.destroy();
      }

      const ctx = elements.chartCanvas.getContext('2d');
      state.chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels.length ? labels : ['Tidak Ada Transaksi'],
          datasets: [
            {
              label: 'Stok Masuk (Penerimaan PO)',
              data: inData.length ? inData : [0],
              backgroundColor: 'rgba(34, 197, 94, 0.75)',
              borderColor: '#16a34a',
              borderWidth: 1.5,
              borderRadius: 4,
              barPercentage: 0.7,
              categoryPercentage: 0.6
            },
            {
              label: 'Stok Keluar (Penjualan / Pemakaian)',
              data: outData.length ? outData : [0],
              backgroundColor: 'rgba(239, 68, 68, 0.75)',
              borderColor: '#dc2626',
              borderWidth: 1.5,
              borderRadius: 4,
              barPercentage: 0.7,
              categoryPercentage: 0.6
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: {
              position: 'top',
              labels: { boxWidth: 12, font: { family: 'Inter, system-ui, sans-serif', size: 12 } }
            },
            tooltip: {
              callbacks: {
                label: function (ctx) {
                  return ` ${ctx.dataset.label}: ${formatNumber(ctx.raw)} unit`;
                }
              }
            }
          },
          scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: {
              beginAtZero: true,
              grid: { color: 'rgba(226, 232, 240, 0.6)' },
              ticks: { font: { size: 11 }, precision: 0 }
            }
          }
        }
      });
    }
  }

  function renderTopProducts(topList) {
    if (!elements.topProductsList) return;

    if (!Array.isArray(topList) || topList.length === 0) {
      elements.topProductsList.innerHTML = `
        <div class="empty-panel-text">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
          <span>Tidak ada mutasi keluar pada periode ini.</span>
        </div>
      `;
      return;
    }

    let html = '';
    topList.forEach((item, index) => {
      const rank = index + 1;
      const pct = item.percentage || 0;

      html += `
        <div class="top-product-item">
          <div class="top-product-rank rank-${rank}">${rank}</div>
          <div class="top-product-meta">
            <div class="top-product-header">
              <span class="top-product-name" title="${escapeHtml(item.product_name)}">${escapeHtml(item.product_name)}</span>
              <span class="top-product-qty">-${formatNumber(item.out_qty)} ${escapeHtml(item.unit)}</span>
            </div>
            <div class="top-product-sub">
              <span class="sku-tag">${escapeHtml(item.sku)}</span>
              <span class="category-tag">${escapeHtml(item.category_name)}</span>
              <span class="pct-tag">${pct}% kontribusi</span>
            </div>
            <div class="top-product-track">
              <div class="top-product-fill" style="width: ${Math.min(100, Math.max(5, pct))}%;"></div>
            </div>
          </div>
        </div>
      `;
    });

    elements.topProductsList.innerHTML = html;
  }

  function renderCriticalProducts(criticalList) {
    if (!elements.criticalProductsList) return;

    if (!Array.isArray(criticalList) || criticalList.length === 0) {
      elements.criticalProductsList.innerHTML = `
        <div class="empty-panel-text text-success" style="color: #16a34a;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          <span>Seluruh persediaan aman di atas batas minimum.</span>
        </div>
      `;
      return;
    }

    let html = '';
    criticalList.slice(0, 5).forEach((item) => {
      const isOut = item.status_code === 'out' || Number(item.final_stock) <= 0;
      const badgeClass = isOut ? 'badge-danger' : 'badge-warning';
      const badgeLabel = isOut ? 'Habis Total' : `Sisa ${formatNumber(item.final_stock)} ${escapeHtml(item.unit)}`;

      html += `
        <div class="critical-item">
          <div class="critical-meta">
            <div class="critical-name" title="${escapeHtml(item.product_name)}">${escapeHtml(item.product_name)}</div>
            <div class="critical-sub">
              <span class="sku-tag">${escapeHtml(item.sku)}</span>
              <span class="category-tag">${escapeHtml(item.category_name)}</span>
              <span class="min-stock-info">Batas Min: ${formatNumber(item.minimum_stock)} ${escapeHtml(item.unit)}</span>
            </div>
          </div>
          <div>
            <span class="badge ${badgeClass}">${escapeHtml(badgeLabel)}</span>
          </div>
        </div>
      `;
    });

    elements.criticalProductsList.innerHTML = html;
  }

  // Render Tabel Tab 1 (Buku Besar Mutasi)
  function renderLedgerTable() {
    if (!elements.tableBody) return;

    const data = state.reportData;
    if (!data || !Array.isArray(data.items) || data.items.length === 0) {
      elements.tableBody.innerHTML = '';
      if (elements.tableFoot) elements.tableFoot.innerHTML = '';
      if (elements.emptyState) elements.emptyState.style.display = 'block';
      if (elements.tablePagination) elements.tablePagination.innerHTML = '';
      if (elements.tableInfo) elements.tableInfo.textContent = 'Menampilkan 0 produk';
      return;
    }

    if (elements.emptyState) elements.emptyState.style.display = 'none';

    const items = data.items;
    const totalItems = items.length;
    const totalPages = Math.ceil(totalItems / state.pageSize) || 1;
    if (state.currentPage > totalPages) state.currentPage = totalPages;

    const startIndex = (state.currentPage - 1) * state.pageSize;
    const endIndex = Math.min(startIndex + state.pageSize, totalItems);
    const pageItems = items.slice(startIndex, endIndex);

    let rowsHtml = '';
    pageItems.forEach((item, index) => {
      const rowNo = startIndex + index + 1;
      const statusClass = item.status_code === 'out' ? 'badge-danger' : (item.status_code === 'low' ? 'badge-warning' : 'badge-success');
      const inDisplay = Number(item.in_qty) > 0 ? `+${formatNumber(item.in_qty)}` : '-';
      const inColor = Number(item.in_qty) > 0 ? 'text-success font-semibold' : 'text-muted';
      const outDisplay = Number(item.out_qty) > 0 ? `-${formatNumber(item.out_qty)}` : '-';
      const outColor = Number(item.out_qty) > 0 ? 'text-danger font-semibold' : 'text-muted';

      rowsHtml += `
        <tr>
          <td class="text-center text-muted" style="width: 44px;">${rowNo}</td>
          <td><span class="table-sku">${escapeHtml(item.sku)}</span></td>
          <td>
            <div class="product-cell-name font-semibold">${escapeHtml(item.product_name)}</div>
            <div class="product-cell-unit text-muted" style="font-size: 11px;">${escapeHtml(item.unit)} &bull; ${escapeHtml(item.location)}</div>
          </td>
          <td><span class="category-pill">${escapeHtml(item.category_name)}</span></td>
          <td class="text-right">${formatNumber(item.initial_stock)}</td>
          <td class="text-right ${inColor}">${inDisplay}</td>
          <td class="text-right ${outColor}">${outDisplay}</td>
          <td class="text-right font-bold" style="font-size: 14px;">${formatNumber(item.final_stock)}</td>
          <td class="text-right text-muted">${formatNumber(item.minimum_stock)}</td>
          <td class="text-center">
            <span class="badge ${statusClass}">${escapeHtml(item.status)}</span>
          </td>
          <td class="text-right font-mono">${formatRupiah(item.purchase_price)}</td>
          <td class="text-right font-mono font-bold text-primary">${formatRupiah(item.final_val)}</td>
          <td class="text-center">
            <button type="button" class="btn-stock-card" data-product-id="${item.product_id}" title="Lihat Kartu Stok Produk">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              Kartu
            </button>
          </td>
        </tr>
      `;
    });

    elements.tableBody.innerHTML = rowsHtml;

    // Pasang listener tombol Kartu Stok
    elements.tableBody.querySelectorAll('.btn-stock-card').forEach((btn) => {
      btn.addEventListener('click', () => {
        const pId = btn.getAttribute('data-product-id');
        openStockCard(pId);
      });
    });

    // Render Footer Total Keseluruhan
    renderTableFooter(data.totals);

    if (elements.tableInfo) {
      elements.tableInfo.textContent = `Menampilkan ${startIndex + 1}–${endIndex} dari ${totalItems} produk (Filter: ${getSelectedSortName()})`;
    }

    renderPagination(totalPages);
  }

  function renderTableFooter(totals) {
    if (!elements.tableFoot || !totals) return;

    elements.tableFoot.innerHTML = `
      <tr class="ledger-total-row">
        <td colspan="4" class="text-left font-bold text-uppercase" style="letter-spacing: 0.5px;">
          TOTAL KESELURUHAN (${state.reportData.items.length} PRODUK)
        </td>
        <td class="text-right font-bold">${formatNumber(totals.initial_stock)}</td>
        <td class="text-right font-bold text-success">+${formatNumber(totals.in_qty)}</td>
        <td class="text-right font-bold text-danger">-${formatNumber(totals.out_qty)}</td>
        <td class="text-right font-bold" style="font-size: 15px;">${formatNumber(totals.final_stock)}</td>
        <td colspan="2" class="text-center text-muted">-</td>
        <td class="text-right font-mono text-muted">-</td>
        <td class="text-right font-mono font-bold text-primary" style="font-size: 14px;">${formatRupiah(totals.final_val)}</td>
        <td class="text-center text-muted">-</td>
      </tr>
    `;
  }

  function renderPagination(totalPages) {
    if (!elements.tablePagination) return;
    if (totalPages <= 1) {
      elements.tablePagination.innerHTML = '';
      return;
    }

    let html = `
      <button class="pagination-btn" ${state.currentPage === 1 ? 'disabled' : ''} data-page="${state.currentPage - 1}">
        &laquo; Prev
      </button>
    `;

    for (let p = 1; p <= totalPages; p++) {
      if (p === 1 || p === totalPages || (p >= state.currentPage - 2 && p <= state.currentPage + 2)) {
        html += `
          <button class="pagination-btn ${p === state.currentPage ? 'active' : ''}" data-page="${p}">
            ${p}
          </button>
        `;
      } else if (p === state.currentPage - 3 || p === state.currentPage + 3) {
        html += `<span class="pagination-ellipsis">...</span>`;
      }
    }

    html += `
      <button class="pagination-btn" ${state.currentPage === totalPages ? 'disabled' : ''} data-page="${state.currentPage + 1}">
        Next &raquo;
      </button>
    `;

    elements.tablePagination.innerHTML = html;

    elements.tablePagination.querySelectorAll('button[data-page]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetPage = parseInt(btn.getAttribute('data-page'), 10);
        if (targetPage >= 1 && targetPage <= totalPages && targetPage !== state.currentPage) {
          state.currentPage = targetPage;
          renderLedgerTable();
          const tableElem = document.querySelector('.ledger-card');
          if (tableElem) tableElem.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  }

  // Render Tabel Tab 2: Jurnal Transaksi
  function renderJournalTable(movements) {
    if (!elements.journalTableBody) return;

    if (!Array.isArray(movements) || movements.length === 0) {
      elements.journalTableBody.innerHTML = `
        <tr>
          <td colspan="9" style="text-align: center; padding: 36px; color: var(--color-text-muted);">
            Tidak ada transaksi mutasi tercatat pada rentang periode ini.
          </td>
        </tr>
      `;
      if (elements.journalCountBadge) elements.journalCountBadge.textContent = '0 Transaksi';
      return;
    }

    if (elements.journalCountBadge) {
      elements.journalCountBadge.textContent = `${movements.length} Transaksi Tercatat`;
    }

    let html = '';
    movements.forEach((m) => {
      const isEntry = m.type === 'IN';
      const typeBadgeClass = isEntry ? 'badge-success' : (m.type === 'OUT' ? 'badge-danger' : 'badge-primary');
      const qtyPrefix = isEntry ? '+' : (m.type === 'OUT' ? '-' : '');
      const qtyClass = isEntry ? 'text-success font-semibold' : 'text-danger font-semibold';

      html += `
        <tr>
          <td style="font-size: 12px; color: #475569;">${escapeHtml(m.date)}</td>
          <td><span class="table-sku" style="font-size: 11px;">${escapeHtml(m.ref_no)}</span></td>
          <td class="text-center">
            <span class="badge ${typeBadgeClass}">${escapeHtml(m.type)} (${escapeHtml(m.raw_type)})</span>
          </td>
          <td>
            <div class="font-semibold" style="font-size: 13px;">${escapeHtml(m.product_name)}</div>
            <div class="text-muted font-mono" style="font-size: 11px;">SKU: ${escapeHtml(m.sku)}</div>
          </td>
          <td class="text-right ${qtyClass}" style="font-size: 13px;">
            ${qtyPrefix}${formatNumber(m.qty)} ${escapeHtml(m.unit)}
          </td>
          <td class="text-right font-mono" style="font-size: 12px;">${formatRupiah(m.value)}</td>
          <td style="font-size: 12px;">${escapeHtml(m.partner_name)}</td>
          <td style="font-size: 12px; color: #475569;">${escapeHtml(m.operator_name)}</td>
          <td style="font-size: 11px; color: #64748b; max-width: 180px;">${escapeHtml(m.notes)}</td>
        </tr>
      `;
    });

    elements.journalTableBody.innerHTML = html;
  }

  // Render Tab 3: Valuasi & Status Fisik
  function renderValuationTab(data) {
    if (!data) return;

    if (elements.valActiveQty) elements.valActiveQty.textContent = `${formatNumber(data.kpi.cur_active)} unit`;
    if (elements.valQuarantineQty) elements.valQuarantineQty.textContent = `${formatNumber(data.kpi.cur_quarantine)} unit`;
    if (elements.valDraftQty) elements.valDraftQty.textContent = `${formatNumber(data.kpi.cur_draft)} unit`;

    if (!elements.categoryValuationTableBody) return;
    const catAnalysis = data.category_analysis || [];

    if (catAnalysis.length === 0) {
      elements.categoryValuationTableBody.innerHTML = `
        <tr><td colspan="7" style="text-align: center; padding: 24px;">Tidak ada data kategori.</td></tr>
      `;
      return;
    }

    let html = '';
    catAnalysis.forEach((cat) => {
      const margin = cat.potential_sale - cat.valuation;
      const marginPct = cat.valuation > 0 ? Math.round((margin / cat.valuation) * 100) : 0;
      const marginColor = margin >= 0 ? '#16a34a' : '#dc2626';

      html += `
        <tr>
          <td><strong style="color: #0f172a;">${escapeHtml(cat.name)}</strong></td>
          <td class="text-center">${formatNumber(cat.count)} SKU</td>
          <td class="text-right font-bold">${formatNumber(cat.stock_qty)} unit</td>
          <td class="text-right font-mono font-semibold" style="color: #1e40af;">${formatRupiah(cat.valuation)}</td>
          <td class="text-right font-mono" style="color: #475569;">${formatRupiah(cat.potential_sale)}</td>
          <td class="text-right" style="color: ${marginColor}; font-weight: 600;">
            ${formatRupiah(margin)} (${marginPct}%)
          </td>
          <td>
            <div style="display: flex; align-items: center; gap: 8px;">
              <div style="flex: 1; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                <div style="width: ${Math.min(100, cat.percentage)}%; height: 100%; background: #2563eb; border-radius: 3px;"></div>
              </div>
              <span style="font-size: 11px; font-weight: 600; color: #475569; width: 42px; text-align: right;">${cat.percentage}%</span>
            </div>
          </td>
        </tr>
      `;
    });

    elements.categoryValuationTableBody.innerHTML = html;
  }

  // Modal Kartu Stok Detail
  async function openStockCard(productId) {
    if (!elements.stockCardModal) return;

    elements.stockCardModal.style.display = 'flex';
    elements.scTitle.textContent = 'Memuat Kartu Stok...';
    elements.scMovementsTbody.innerHTML = `
      <tr><td colspan="8" style="text-align:center; padding: 24px;">Mengambil riwayat mutasi produk...</td></tr>
    `;

    try {
      const params = new URLSearchParams({
        product_id: productId,
        start_date: state.startDate,
        end_date: state.endDate
      });

      const res = await fetch(`../api/reports.php?${params.toString()}`);
      const json = await res.json();
      if (json.status !== 'success') throw new Error(json.message);

      const d = json.data;
      elements.scTitle.textContent = `Kartu Riwayat Mutasi - ${d.product.product_name}`;
      elements.scSku.textContent = d.product.sku;
      elements.scCategoryLoc.textContent = `${d.product.category_name} (${d.product.location})`;
      elements.scPrice.textContent = formatRupiah(d.product.purchase_price);
      elements.scMinStock.textContent = `${formatNumber(d.product.minimum_stock)} ${d.product.unit}`;

      elements.scPeriodRange.textContent = d.period.formatted_range;
      elements.scInitialQty.textContent = `${formatNumber(d.initial_stock)} ${d.product.unit}`;
      elements.scInQty.textContent = `+${formatNumber(d.total_in)} ${d.product.unit}`;
      elements.scOutQty.textContent = `-${formatNumber(d.total_out)} ${d.product.unit}`;
      elements.scFinalQty.textContent = `${formatNumber(d.final_stock)} ${d.product.unit}`;

      if (!d.movements || d.movements.length === 0) {
        elements.scMovementsTbody.innerHTML = `
          <tr><td colspan="8" style="text-align:center; padding: 20px; color: var(--color-text-muted);">
            Tidak ada pergerakan mutasi fisik pada periode ini.
          </td></tr>
        `;
        return;
      }

      let mHtml = '';
      d.movements.forEach((mv) => {
        const isEntry = mv.type === 'IN';
        const badgeClass = isEntry ? 'badge-success' : 'badge-danger';
        const qtyPrefix = isEntry ? '+' : '-';
        const qtyColor = isEntry ? 'text-success font-semibold' : 'text-danger font-semibold';

        mHtml += `
          <tr>
            <td style="color: #475569;">${escapeHtml(mv.date)}</td>
            <td><span class="table-sku" style="font-size: 11px;">${escapeHtml(mv.ref_no)}</span></td>
            <td class="text-center"><span class="badge ${badgeClass}">${escapeHtml(mv.type)}</span></td>
            <td class="text-right ${qtyColor}">${qtyPrefix}${formatNumber(mv.qty)}</td>
            <td class="text-right font-bold">${formatNumber(mv.balance)}</td>
            <td>${escapeHtml(mv.partner_name)}</td>
            <td>${escapeHtml(mv.operator_name)}</td>
            <td style="font-size: 11px; color: #64748b;">${escapeHtml(mv.notes)}</td>
          </tr>
        `;
      });

      elements.scMovementsTbody.innerHTML = mHtml;

    } catch (e) {
      console.error(e);
      elements.scMovementsTbody.innerHTML = `
        <tr><td colspan="8" style="text-align:center; padding: 20px; color: #dc2626;">
          Gagal mengambil data: ${escapeHtml(e.message)}
        </td></tr>
      `;
    }
  }

  function closeModal() {
    if (elements.stockCardModal) elements.stockCardModal.style.display = 'none';
  }

  // Dokumen Cetak / Unduh PDF Resmi
  function preparePrintDocument(data) {
    if (!elements.printContainer || !data) return;

    const now = new Date();
    const printDateStr = now.toLocaleDateString('id-ID', {
      day: '2-digit', month: 'long', year: 'numeric'
    }) + ' ' + now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';

    const userName = document.querySelector('.user-name')?.textContent || 'Admin Gudang';
    const userRole = document.querySelector('.user-role')?.textContent || 'Pengelola Inventaris';

    const comp = data.company || {
      name: 'PT Inventoria Solusi Nusantara',
      npwp: '01.234.567.8-901.000',
      email: 'inventory@inventoria.id',
      phone: '(021) 555-0192',
      address: 'Kawasan Industri Surya Cipta Kav. 18, Karawang Timur, Jawa Barat 41361'
    };

    let tableRows = '';
    data.items.forEach((item, index) => {
      tableRows += `
        <tr>
          <td style="text-align: center;">${index + 1}</td>
          <td style="font-family: monospace; font-weight: bold;">${escapeHtml(item.sku)}</td>
          <td>${escapeHtml(item.product_name)}</td>
          <td>${escapeHtml(item.category_name)}</td>
          <td style="text-align: right;">${formatNumber(item.initial_stock)} ${escapeHtml(item.unit)}</td>
          <td style="text-align: right; color: #15803d; font-weight: bold;">+${formatNumber(item.in_qty)}</td>
          <td style="text-align: right; color: #b91c1c; font-weight: bold;">-${formatNumber(item.out_qty)}</td>
          <td style="text-align: right; font-weight: bold;">${formatNumber(item.final_stock)} ${escapeHtml(item.unit)}</td>
          <td style="text-align: center;">
            <span class="print-badge ${item.status_code}">${escapeHtml(item.status)}</span>
          </td>
          <td style="text-align: right; font-family: monospace;">${formatRupiah(item.purchase_price)}</td>
          <td style="text-align: right; font-family: monospace; font-weight: bold;">${formatRupiah(item.final_val)}</td>
        </tr>
      `;
    });

    elements.printContainer.innerHTML = `
      <div class="print-official-wrapper">
        <div class="print-header">
          <h1 class="print-company-name">${escapeHtml(comp.name)}</h1>
          <p class="print-company-sub">Sistem Manajemen Persediaan & Pembukuan Mutasi Inventaris | NPWP: ${escapeHtml(comp.npwp || '-')}</p>
          <p class="print-company-contact">${escapeHtml(comp.address || '')} | Telp: ${escapeHtml(comp.phone || '-')} | Email: ${escapeHtml(comp.email || '-')}</p>
          <div class="print-header-divider"></div>
          <h2 class="print-document-title">LAPORAN BUKU BESAR MUTASI & VALUASI INVENTARIS</h2>
        </div>

        <div class="print-meta-grid">
          <div>
            <table class="print-meta-table">
              <tr><td style="width: 140px;"><strong>Periode Laporan</strong></td><td>: ${escapeHtml(data.period.formatted_range)}</td></tr>
              <tr><td><strong>Kategori Terpilih</strong></td><td>: ${escapeHtml(getSelectedCategoryName())}</td></tr>
              <tr><td><strong>Urutan Tampilan</strong></td><td>: ${escapeHtml(getSelectedSortName())}</td></tr>
            </table>
          </div>
          <div>
            <table class="print-meta-table">
              <tr><td style="width: 140px;"><strong>Waktu Cetak</strong></td><td>: ${escapeHtml(printDateStr)}</td></tr>
              <tr><td><strong>Petugas Cetak</strong></td><td>: ${escapeHtml(userName)} (${escapeHtml(userRole)})</td></tr>
              <tr><td><strong>Status Pembukuan</strong></td><td>: <span style="color: #15803d; font-weight: bold;">Lengkap & Terverifikasi</span></td></tr>
            </table>
          </div>
        </div>

        <div class="print-summary-box">
          <div class="print-summary-col">
            <span class="print-summary-label">Total Mutasi Masuk</span>
            <span class="print-summary-val" style="color: #15803d;">+${formatNumber(data.kpi.total_in_qty)} unit</span>
            <span class="print-summary-sub">${formatRupiah(data.kpi.total_in_val)}</span>
          </div>
          <div class="print-summary-col">
            <span class="print-summary-label">Total Mutasi Keluar</span>
            <span class="print-summary-val" style="color: #b91c1c;">-${formatNumber(data.kpi.total_out_qty)} unit</span>
            <span class="print-summary-sub">${formatRupiah(data.kpi.total_out_val)}</span>
          </div>
          <div class="print-summary-col">
            <span class="print-summary-label">Saldo Stok Akhir</span>
            <span class="print-summary-val" style="color: #1e40af;">${formatNumber(data.kpi.final_stock_qty)} unit</span>
            <span class="print-summary-sub">Dari ${data.items.length} SKU Produk</span>
          </div>
          <div class="print-summary-col">
            <span class="print-summary-label">Total Valuasi Aset</span>
            <span class="print-summary-val" style="color: #0f172a;">${formatRupiah(data.kpi.final_stock_val)}</span>
            <span class="print-summary-sub">Kritis: ${data.kpi.critical_count} SKU</span>
          </div>
        </div>

        <table class="print-ledger-table">
          <thead>
            <tr>
              <th style="width: 30px; text-align: center;">No</th>
              <th style="width: 80px;">SKU</th>
              <th>Nama Produk</th>
              <th>Kategori</th>
              <th style="text-align: right;">Stok Awal</th>
              <th style="text-align: right;">Masuk (+)</th>
              <th style="text-align: right;">Keluar (-)</th>
              <th style="text-align: right;">Stok Akhir</th>
              <th style="text-align: center;">Kondisi</th>
              <th style="text-align: right;">Harga Beli</th>
              <th style="text-align: right;">Valuasi Akhir</th>
            </tr>
          </thead>
          <tbody>
            ${tableRows}
          </tbody>
          <tfoot>
            <tr style="background: #f1f5f9; font-weight: bold;">
              <td colspan="4" style="text-align: left; padding-left: 8px;">TOTAL KESELURUHAN</td>
              <td style="text-align: right;">${formatNumber(data.totals.initial_stock)}</td>
              <td style="text-align: right; color: #15803d;">+${formatNumber(data.totals.in_qty)}</td>
              <td style="text-align: right; color: #b91c1c;">-${formatNumber(data.totals.out_qty)}</td>
              <td style="text-align: right;">${formatNumber(data.totals.final_stock)}</td>
              <td style="text-align: center;">-</td>
              <td style="text-align: right;">-</td>
              <td style="text-align: right; font-family: monospace;">${formatRupiah(data.totals.final_val)}</td>
            </tr>
          </tfoot>
        </table>

        <div class="print-signature-section">
          <div class="print-signature-box">
            <p class="print-sign-title">Dibuat & Diverifikasi Oleh:</p>
            <div class="print-sign-space"></div>
            <p class="print-sign-name">${escapeHtml(userName)}</p>
            <p class="print-sign-role">${escapeHtml(userRole)} / Bagian Gudang</p>
            <p class="print-sign-date">Tanggal: ${escapeHtml(printDateStr.split(' ')[0] + ' ' + printDateStr.split(' ')[1] + ' ' + printDateStr.split(' ')[2])}</p>
          </div>
          <div class="print-signature-box">
            <p class="print-sign-title">Mengetahui & Menyetujui:</p>
            <div class="print-sign-space"></div>
            <p class="print-sign-name">( .................................................... )</p>
            <p class="print-sign-role">Manajer Operasional & Logistik</p>
            <p class="print-sign-date">Tanggal: ............................................</p>
          </div>
        </div>
      </div>
    `;
  }

  function getSelectedCategoryName() {
    if (!elements.category) return 'Semua Kategori';
    return elements.category.options[elements.category.selectedIndex]?.textContent || 'Semua Kategori';
  }

  function getSelectedSortName() {
    if (!elements.sortBy) return 'Default';
    return elements.sortBy.options[elements.sortBy.selectedIndex]?.textContent || 'Default';
  }

  function triggerOfficialPrint() {
    const oldTitle = document.title;
    const dateRangeStr = state.reportData?.period?.formatted_range?.replace(/[\/\s]/g, '-') || 'Periode';
    document.title = `Laporan_Pembukuan_Inventory_${dateRangeStr}`;

    if (state.reportData) {
      preparePrintDocument(state.reportData);
    }

    window.print();

    setTimeout(() => {
      document.title = oldTitle;
    }, 1000);
  }

  function showErrorMessage(msg) {
    if (elements.tableBody) {
      elements.tableBody.innerHTML = `
        <tr>
          <td colspan="13" style="text-align: center; padding: 36px; color: #dc2626;">
            <strong>Gagal Memuat Laporan:</strong> ${escapeHtml(msg)}
          </td>
        </tr>
      `;
    }
  }

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

})();
