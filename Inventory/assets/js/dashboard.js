/**
 * Handler Dashboard Dinamis Terhubung ke Database MySQL
 * Proyek: Inventoria (database hris)
 */

document.addEventListener('DOMContentLoaded', function() {
  loadDashboardData();
});

function loadDashboardData() {
  // 1. Ambil Metrik Utama Dashboard dari MySQL
  fetch('../api/dashboard.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success') {
        renderStatCards(res.metrics);
        renderLowStockTable(res.low_stock_items);
        renderRecentActivities(res.recent_activities);
      }
    })
    .catch(err => console.error('Gagal memuat metrik dashboard:', err));

  // 2. Ambil Distribusi Kategori Nyata dari MySQL
  fetch('../api/categories.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        renderCategoryDistribution(res.data);
      }
    })
    .catch(err => console.error('Gagal memuat kategori:', err));
}

// 1. Render Stat Cards
function renderStatCards(m) {
  const statValues = document.querySelectorAll('.stat-card .stat-value');
  const statTrends = document.querySelectorAll('.stat-card .stat-trend');

  if (statValues.length >= 4) {
    // Card 1: Total Produk
    statValues[0].textContent = m.total_products;
    if (statTrends[0]) {
      statTrends[0].innerHTML = `<span style="color: var(--color-success); font-weight: 600;">Data MySQL Aktif</span>`;
    }

    // Card 2: Total Nilai Stok
    statValues[1].textContent = m.total_valuation_formatted || 'Rp 0';

    // Card 3: Stok Masuk Bulan Ini
    statValues[2].textContent = `${m.stock_in_month_qty} unit`;
    if (statTrends[2]) {
      statTrends[2].textContent = `${m.stock_in_month_trx} transaksi bulan ini`;
    }

    // Card 4: Produk Hampir Habis
    statValues[3].textContent = `${m.low_stock_count} item`;
  }
}

// 2. Render Tabel Stok Kritis & Rendah
function renderLowStockTable(items) {
  const tbody = document.querySelector('.table-container .table tbody');
  if (!tbody) return;

  if (!items || items.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align: center; padding: 24px; color: var(--color-text-muted);">
          ✅ Semua stok barang saat ini dalam kondisi aman dan mencukupi.
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = '';
  items.forEach(p => {
    const isOut = p.status === 'out' || parseInt(p.current_stock) <= 0;
    const badgeClass = isOut ? 'badge-out' : 'badge-low';
    const badgeText = isOut ? 'Habis' : 'Hampir habis';
    const colorStyle = isOut ? 'color: var(--color-danger);' : 'color: var(--color-warning);';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><span class="table-sku">${escapeHtml(p.sku)}</span></td>
      <td><strong>${escapeHtml(p.name)}</strong></td>
      <td>${escapeHtml(p.category_name || '-')}</td>
      <td style="text-align: right; ${colorStyle} font-weight: bold;">${p.current_stock}</td>
      <td style="text-align: right;">${p.minimum_stock}</td>
      <td><span class="badge ${badgeClass}"><span class="badge-dot"></span>${badgeText}</span></td>
      <td><a href="stock-in.html?product_id=${p.id}" class="btn btn-primary btn-sm">Restock</a></td>
    `;
    tbody.appendChild(tr);
  });
}

// 3. Render Daftar Aktivitas Terkini
function renderRecentActivities(activities) {
  const container = document.querySelector('.activity-list');
  if (!container) return;

  if (!activities || activities.length === 0) {
    container.innerHTML = `
      <div style="padding: 24px; text-align: center; color: var(--color-text-muted);">
        Belum ada riwayat transaksi stok.
      </div>
    `;
    return;
  }

  container.innerHTML = '';
  activities.forEach(act => {
    const isIn = act.type === 'IN';
    const sign = isIn ? '+' : '-';
    const color = isIn ? 'var(--color-success)' : 'var(--color-danger)';
    const iconClass = isIn ? 'in' : 'out';
    const sub = isIn 
      ? `Penerimaan dari ${escapeHtml(act.partner_name || 'Supplier')}`
      : `Pengeluaran ke ${escapeHtml(act.partner_name || 'Customer')}`;

    const item = document.createElement('div');
    item.className = 'activity-item';
    item.innerHTML = `
      <div class="activity-icon-box ${iconClass}">
        <svg viewBox="0 0 24 24">
          ${isIn 
            ? '<polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>' 
            : '<polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line>'}
        </svg>
      </div>
      <div class="activity-content">
        <span class="activity-title">${escapeHtml(act.product_name)}</span>
        <span class="activity-subtitle">${sub} (${escapeHtml(act.ref_no)})</span>
      </div>
      <div class="activity-meta">
        <span class="text-bold" style="color: ${color};">${sign}${act.quantity} ${escapeHtml(act.unit || '')}</span>
        <div>${act.date}</div>
      </div>
    `;
    container.appendChild(item);
  });
}

// 4. Render Distribusi Kategori
function renderCategoryDistribution(categories) {
  const chartWrapper = document.querySelector('.category-chart-wrapper');
  if (!chartWrapper) return;

  const totalProducts = categories.reduce((sum, c) => sum + parseInt(c.product_count || 0), 0);
  if (totalProducts === 0) return;

  const fillColors = ['fill-primary', 'fill-success', 'fill-warning', 'fill-purple', 'fill-info'];

  chartWrapper.innerHTML = '';
  categories.forEach((c, idx) => {
    const count = parseInt(c.product_count || 0);
    const pct = Math.round((count / totalProducts) * 100);
    const colorClass = fillColors[idx % fillColors.length] || 'fill-primary';

    const barEl = document.createElement('div');
    barEl.className = 'chart-bar-item';
    barEl.innerHTML = `
      <div class="chart-bar-meta">
        <span class="chart-bar-name">${escapeHtml(c.name)}</span>
        <span class="chart-bar-count">${pct}% (${count} produk)</span>
      </div>
      <div class="chart-bar-track">
        <div class="chart-bar-fill ${colorClass}" style="width: ${pct}%;"></div>
      </div>
    `;
    chartWrapper.appendChild(barEl);
  });
}

function escapeHtml(str) {
  if (!str) return '';
  return str.toString()
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
