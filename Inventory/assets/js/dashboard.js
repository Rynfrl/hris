/**
 * Handler Dashboard Dinamis Terhubung ke Database MySQL
 * Proyek: Inventoria (database hris)
 */

let allLowStockItems = [];
let allActivities = [];
let lowStockCurrentPage = 1;
let activitiesCurrentPage = 1;
const LOW_STOCK_PER_PAGE = 10;
const ACTIVITIES_PER_PAGE = 10;

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function () {
    loadDashboardData();
    window.addEventListener('resize', syncDashboardCardsHeight);
  });
} else {
  loadDashboardData();
  window.addEventListener('resize', syncDashboardCardsHeight);
}

function loadDashboardData() {
  // 1. Metrik Utama + Low Stock + Recent Activities
  fetch('../api/dashboard.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success') {
        renderStatCards(res.metrics);
        allLowStockItems = res.low_stock_items || [];
        allActivities = res.recent_activities || [];
        lowStockCurrentPage = 1;
        activitiesCurrentPage = 1;
        renderLowStockTablePage();
        renderActivitiesPage();
      }
    })
    .catch(err => console.error('Gagal memuat metrik dashboard:', err));

  // 2. Distribusi Kategori
  fetch('../api/categories.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        renderCategoryDistribution(res.data);
      }
    })
    .catch(err => console.error('Gagal memuat kategori:', err));
}

/* ==========================================================
   1. Stat Cards
   ========================================================== */
function renderStatCards(m) {
  const statValues = document.querySelectorAll('.stat-card .stat-value');
  const statTrends = document.querySelectorAll('.stat-card .stat-trend');

  if (statValues.length >= 4) {
    statValues[0].textContent = m.total_products;
    if (statTrends[0]) statTrends[0].innerHTML = `<span style="color:var(--color-success);font-weight:600;">Data MySQL Aktif</span>`;

    statValues[1].textContent = m.total_valuation_formatted || 'Rp 0';

    statValues[2].textContent = `${m.stock_in_month_qty} unit`;
    if (statTrends[2]) statTrends[2].textContent = `${m.stock_in_month_trx} transaksi bulan ini`;

    statValues[3].textContent = `${m.low_stock_count} item`;
  }
}

/* ==========================================================
   2. Tabel Stok Kritis (10 produk per halaman)
   ========================================================== */
function renderLowStockTable(items) {
  allLowStockItems = items || [];
  lowStockCurrentPage = 1;
  renderLowStockTablePage();
}

function renderLowStockTablePage() {
  const tbody = document.querySelector('.table-container tbody');
  if (!tbody) return;

  if (!allLowStockItems || allLowStockItems.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center;padding:32px;color:var(--color-text-muted);">
          ✅ Semua stok barang saat ini dalam kondisi aman dan mencukupi.
        </td>
      </tr>`;
    renderDashboardPagination('lowStockPagination', 0, 1, () => {});
    return;
  }

  const totalPages = Math.ceil(allLowStockItems.length / LOW_STOCK_PER_PAGE) || 1;
  if (lowStockCurrentPage > totalPages) lowStockCurrentPage = totalPages;
  if (lowStockCurrentPage < 1) lowStockCurrentPage = 1;

  const start = (lowStockCurrentPage - 1) * LOW_STOCK_PER_PAGE;
  const end = start + LOW_STOCK_PER_PAGE;
  const pageItems = allLowStockItems.slice(start, end);

  // Update text info pagination stok kritis
  const infoEl = document.getElementById('lowStockPaginationInfo');
  if (infoEl) {
    if (allLowStockItems.length === 0) {
      infoEl.innerHTML = '0 produk kritis';
    } else {
      const from = start + 1;
      const to = Math.min(end, allLowStockItems.length);
      infoEl.innerHTML = `Menampilkan <strong>${from} - ${to}</strong> dari <strong>${allLowStockItems.length}</strong> produk kritis`;
    }
  }

  tbody.innerHTML = '';
  pageItems.forEach(p => {
    const curStock   = Math.max(0, parseFloat(p.current_stock) || 0);
    const isOut      = p.status === 'out' || curStock <= 0;
    const badgeClass = isOut ? 'badge-out' : 'badge-low';
    const badgeText  = isOut ? 'Habis' : 'Hampir habis';
    const colorStyle = isOut ? 'color:var(--color-danger);' : 'color:var(--color-warning);';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><span class="table-sku">${escapeHtml(p.sku)}</span></td>
      <td><strong>${escapeHtml(p.name)}</strong></td>
      <td>${escapeHtml(p.category_name || '-')}</td>
      <td style="text-align:right;${colorStyle}font-weight:bold;">${formatQty(curStock)}</td>
      <td style="text-align:right;">${formatQty(p.minimum_stock)}</td>
      <td><span class="badge ${badgeClass}"><span class="badge-dot"></span>${badgeText}</span></td>
      <td><a href="stock-in.html?product_id=${p.id}" class="btn btn-primary btn-sm">Restock</a></td>
    `;
    tbody.appendChild(tr);
  });

  renderDashboardPagination('lowStockPagination', totalPages, lowStockCurrentPage, (newPage) => {
    lowStockCurrentPage = newPage;
    renderLowStockTablePage();
  }, false);

  setTimeout(syncDashboardCardsHeight, 50);
}

/* ==========================================================
   3. Distribusi Kategori — list produk per kategori
   ========================================================== */
function renderCategoryDistribution(categories) {
  const wrapper = document.querySelector('.category-chart-wrapper');
  if (!wrapper) return;

  wrapper.innerHTML = '';

  if (!categories || categories.length === 0) {
    wrapper.innerHTML = `<p style="color:var(--color-text-muted);font-size:var(--font-size-sm);">Belum ada kategori.</p>`;
    return;
  }

  const colors = ['var(--color-primary)', 'var(--color-success)', 'var(--color-warning)', 'var(--color-info)', '#7C3AED'];

  categories.forEach((cat, idx) => {
    const count    = parseInt(cat.product_count || 0);
    const products = Array.isArray(cat.products) ? cat.products : [];
    const color    = colors[idx % colors.length];
    const catId    = `cat-products-${cat.id}`;

    const item = document.createElement('div');
    item.className = 'cat-dist-item';

    // Header kategori (klik untuk expand/collapse)
    const header = document.createElement('div');
    header.className = 'cat-dist-header';
    header.setAttribute('role', 'button');
    header.setAttribute('aria-expanded', 'false');
    header.setAttribute('aria-controls', catId);
    header.style.cssText = `
      display:flex;align-items:center;justify-content:space-between;
      cursor:pointer;padding:8px 0;border-bottom:1px solid var(--color-border-subtle);
      user-select:none;
    `;
    header.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px;">
        <span style="width:10px;height:10px;border-radius:50%;background:${color};flex-shrink:0;"></span>
        <span style="font-size:var(--font-size-sm);font-weight:var(--font-weight-bold);color:var(--color-text);">
          ${escapeHtml(cat.name)}
        </span>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);">${count} produk</span>
        <svg class="cat-chevron" viewBox="0 0 24 24" width="14" height="14"
             fill="none" stroke="var(--color-text-muted)" stroke-width="2"
             style="transition:transform .2s;">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </div>
    `;

    // Daftar produk (tersembunyi awalnya)
    const productList = document.createElement('div');
    productList.id = catId;
    productList.style.cssText = 'display:none;padding:6px 0 2px 20px;';

    if (products.length === 0) {
      productList.innerHTML = `
        <span style="font-size:var(--font-size-xs);color:var(--color-text-subtle);font-style:italic;">
          Belum ada produk dalam kategori ini.
        </span>`;
    } else {
      products.forEach(prod => {
        const stockColor = parseFloat(prod.stock) <= 0
          ? 'var(--color-danger)'
          : 'var(--color-text-muted)';
        const row = document.createElement('div');
        row.style.cssText = `
          display:flex;align-items:center;justify-content:space-between;
          padding:5px 0;border-bottom:1px solid var(--color-border-subtle);font-size:var(--font-size-xs);
        `;
        row.innerHTML = `
          <div style="display:flex;align-items:center;gap:8px;">
            <span style="color:var(--color-text-subtle);">•</span>
            <span style="color:var(--color-text);">${escapeHtml(prod.name)}</span>
            <span style="color:var(--color-text-subtle);font-size:11px;">${escapeHtml(prod.sku)}</span>
          </div>
          <span style="color:${stockColor};font-weight:600;white-space:nowrap;">
            ${formatQty(prod.stock)} ${escapeHtml(prod.unit || '')}
          </span>
        `;
        productList.appendChild(row);
      });
    }

    // Toggle expand/collapse
    header.addEventListener('click', () => {
      const isOpen = productList.style.display !== 'none';
      productList.style.display = isOpen ? 'none' : 'block';
      header.setAttribute('aria-expanded', String(!isOpen));
      const chevron = header.querySelector('.cat-chevron');
      if (chevron) chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
      setTimeout(syncDashboardCardsHeight, 50);
    });

    item.appendChild(header);
    item.appendChild(productList);
    wrapper.appendChild(item);
  });
  setTimeout(syncDashboardCardsHeight, 50);
}

/* ==========================================================
   4. Aktivitas Stok Terbaru (10 transaksi terbaru) + tombol Detail
   ========================================================== */
function renderRecentActivities(activities) {
  allActivities = activities || [];
  renderActivitiesPage();
}

function renderActivitiesPage() {
  const container = document.querySelector('.activity-list');
  if (!container) return;

  if (!allActivities || allActivities.length === 0) {
    container.innerHTML = `
      <div style="padding:24px;text-align:center;color:var(--color-text-muted);">
        Belum ada riwayat transaksi stok.
      </div>`;
    return;
  }

  // Tampilkan maksimal 10 aktivitas transaksi paling mutakhir
  const sorted = [...allActivities].sort((a, b) => new Date(b.date) - new Date(a.date)).slice(0, 10);

  container.innerHTML = '';
  sorted.forEach(act => {
    const isIn       = act.type === 'IN';
    const refNo      = act.ref_no || act.ref_number || '';
    const isDeleted  = act.stock_status === 'deleted' || (act.notes && act.notes.includes('[PRODUK DIHAPUS]')) || refNo.startsWith('DEL-');
    const isTransfer = act.type === 'TRANSFER' || act.stock_status === 'transfer' || (act.notes && act.notes.includes('[TRANSFER STATUS')) || refNo.startsWith('TRF-');

    let transferLabel = 'Pergeseran Status';
    let transferQty = act.transfer_qty || 0;
    if (act.notes) {
      const matchLabel = act.notes.match(/\[TRANSFER STATUS:\s*([^\]]+)\]/i);
      if (matchLabel && matchLabel[1]) {
        transferLabel = matchLabel[1].replace(/\s*\([^)]*\)/g, '').replace(/â†’/g, '→').trim();
      }
      if (!transferQty) {
        const matchQty = act.notes.match(/Sebesar\s+([\d\.\,]+)/i);
        if (matchQty && matchQty[1]) transferQty = parseFloat(matchQty[1].replace(',', '.'));
      }
    }
    if (!transferQty && act.quantity > 0) transferQty = act.quantity;

    const cleanActRef = refNo.replace(/^SO-SO-/, 'SO-');

    let iconBoxHtml = '';
    let partnerLabel = '';
    let qtyHtml = '';

    if (isDeleted) {
      iconBoxHtml = `
        <div class="activity-icon-box" style="background:#fee2e2;color:#dc2626;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
        </div>`;
      partnerLabel = `Penonaktifan / Dihapus (${escapeHtml(cleanActRef)})`;
      qtyHtml = `
        <span class="text-bold" style="color:#b91c1c;">0 ${escapeHtml(act.unit || 'unit')}</span>
        <div style="font-size:10px;font-weight:600;color:#dc2626;">Penonaktifan</div>
      `;
    } else if (isTransfer) {
      iconBoxHtml = `
        <div class="activity-icon-box" style="background:#f3e8ff;color:#7e22ce;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M7 16V4M7 4L3 8M7 4L11 8M17 8V20M17 20L21 16M17 20L13 16"></path>
          </svg>
        </div>`;
      partnerLabel = `Pergeseran: ${escapeHtml(transferLabel)} (${escapeHtml(cleanActRef)})`;
      qtyHtml = `
        <span class="text-bold" style="color:#7e22ce;">${formatQty(transferQty)} ${escapeHtml(act.unit || 'unit')}</span>
        <div style="font-size:10px;font-weight:600;color:#9333ea;">Pergeseran Status</div>
      `;
    } else {
      const sign      = isIn ? '+' : '-';
      const color     = isIn ? 'var(--color-success)' : 'var(--color-danger)';
      const iconClass = isIn ? 'in' : 'out';
      iconBoxHtml = `
        <div class="activity-icon-box ${iconClass}">
          <svg viewBox="0 0 24 24">
            ${isIn
              ? '<polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>'
              : '<polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line>'}
          </svg>
        </div>`;
      partnerLabel = isIn
        ? `Penerimaan dari ${escapeHtml(act.partner_name || 'Supplier')} (${escapeHtml(cleanActRef)})`
        : `Pengeluaran ke ${escapeHtml(act.partner_name || 'Customer')} (${escapeHtml(cleanActRef)})`;
      qtyHtml = `
        <span class="text-bold" style="color:${color};">${sign}${formatQty(act.quantity)} ${escapeHtml(act.unit || '')}</span>
      `;
    }

    const item = document.createElement('div');
    item.className = 'activity-item';
    item.innerHTML = `
      ${iconBoxHtml}
      <div class="activity-content" style="flex:1;min-width:0;">
        <span class="activity-title">
          ${escapeHtml(act.product_name)}
          ${isTransfer ? `<span class="badge" style="background:#f3e8ff;color:#6b21a8;border:1px solid #d8b4fe;font-size:10px;padding:1px 6px;font-weight:700;margin-left:4px;">🔄 Transfer</span>` : ''}
          ${isDeleted ? `<span class="badge" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;font-size:10px;padding:1px 6px;font-weight:700;margin-left:4px;">Nonaktif</span>` : ''}
        </span>
        <span class="activity-subtitle">${partnerLabel}</span>
      </div>
      <div class="activity-meta">
        ${qtyHtml}
        <div style="font-size:11px;color:var(--color-text-muted);">${formatDate(act.date)}</div>
        <button class="btn-activity-detail">Detail</button>
      </div>
    `;

    item.querySelector('.btn-activity-detail').addEventListener('click', () => {
      showActivityDetail(act);
    });

    container.appendChild(item);
  });

  setTimeout(syncDashboardCardsHeight, 50);
}

/* ==========================================================
   4b. Kontrol Pagination Dashboard Dinamis
   ========================================================== */
function getPaginationRange(totalPages, page) {
  if (totalPages <= 5) {
    const range = [];
    for (let i = 1; i <= totalPages; i++) range.push(i);
    return range;
  }

  // Jika di awal halaman (halaman 1 atau 2):
  // Menghasilkan: 1, 2, 3, '...', totalPages
  if (page <= 2) {
    return [1, 2, 3, '...', totalPages];
  }

  // Jika di halaman 3:
  // Menghasilkan: 1, 2, 3, 4, '...', totalPages
  if (page === 3) {
    return [1, 2, 3, 4, '...', totalPages];
  }

  // Jika di halaman akhir (halaman totalPages atau totalPages - 1):
  // Menghasilkan: 1, '...', totalPages - 2, totalPages - 1, totalPages
  if (page >= totalPages - 1) {
    return [1, '...', totalPages - 2, totalPages - 1, totalPages];
  }

  // Jika di halaman totalPages - 2:
  // Menghasilkan: 1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages
  if (page === totalPages - 2) {
    return [1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
  }

  // Jika di tengah-tengah:
  // Menghasilkan: 1, '...', page - 1, page, page + 1, '...', totalPages
  return [1, '...', page - 1, page, page + 1, '...', totalPages];
}

function renderDashboardPagination(containerId, totalPages, currentPage, onPageChange, isCompact = false) {
  const container = document.getElementById(containerId);
  if (!container) return;

  if (totalPages <= 1) {
    container.innerHTML = '';
    return;
  }

  let html = '';
  const prevDisabled = currentPage <= 1 ? 'disabled' : '';
  html += `<a href="#" class="page-btn ${prevDisabled}" data-page="${currentPage - 1}" aria-label="Sebelumnya" title="Halaman Sebelumnya">&laquo;</a>`;

  const items = getPaginationRange(totalPages, currentPage);
  items.forEach(item => {
    if (item === '...') {
      html += `<span class="page-ellipsis" style="display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:34px; color:var(--color-text-muted); font-size:12px; font-weight:600; user-select:none;">...</span>`;
    } else {
      const active = item === currentPage ? 'active' : '';
      html += `<a href="#" class="page-btn ${active}" data-page="${item}" title="Halaman ${item}">${item}</a>`;
    }
  });

  const nextDisabled = currentPage >= totalPages ? 'disabled' : '';
  html += `<a href="#" class="page-btn ${nextDisabled}" data-page="${currentPage + 1}" aria-label="Berikutnya" title="Halaman Berikutnya">&raquo;</a>`;

  container.innerHTML = html;

  container.querySelectorAll('.page-btn:not(.disabled)').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const targetPage = parseInt(this.dataset.page, 10);
      if (!isNaN(targetPage) && targetPage !== currentPage && targetPage >= 1 && targetPage <= totalPages) {
        onPageChange(targetPage);
      }
    });
  });
}

/* ==========================================================
   4c. Sinkronisasi Tinggi Kartu Stok Kritis & Aktivitas Stok
   (Membuat bagian bawah kedua kolom sejajar & aktivitas scrollable)
   ========================================================== */
let standard10ProductCardHeight = 565;

function syncDashboardCardsHeight() {
  const leftCard = document.getElementById('lowStockCard');
  const rightCol = document.querySelector('.dashboard-right-col');
  const catCard  = document.getElementById('categoryDistCard');
  const actCard  = document.getElementById('recentActivitiesCard');
  const actBody  = document.querySelector('.activity-card-body');

  if (!leftCard || !rightCol || !catCard || !actCard || !actBody) return;

  if (window.innerWidth > 1024) {
    // Reset inline height sementara untuk membaca tinggi konten aktual
    leftCard.style.height = 'auto';
    rightCol.style.height = 'auto';
    actCard.style.height = 'auto';
    actBody.style.maxHeight = 'none';

    // Periksa jumlah baris produk yang tampil di tabel saat ini
    const rowCount = leftCard.querySelectorAll('tbody tr').length;
    const currentMeasuredHeight = leftCard.offsetHeight;

    // Jika sedang menampilkan 10 produk (halaman penuh), simpan tingginya sebagai patokan standar 10 produk
    if (rowCount >= 10 && currentMeasuredHeight > 450) {
      standard10ProductCardHeight = currentMeasuredHeight;
    }

    // Patokan tinggi kartu grid dashboard selalu mengacu pada tinggi produk ke-10 (halaman penuh)
    // sehingga di halaman terakhir (meskipun produk < 10), kartu tidak menciut dan aktivitas tetap tampil sempurna
    const targetHeight = Math.max(standard10ProductCardHeight, 565);

    leftCard.style.height = `${targetHeight}px`;
    rightCol.style.height = `${targetHeight}px`;

    const catHeight = catCard.offsetHeight;
    const gap = 24; // var(--spacing-lg)
    const remainingHeight = targetHeight - catHeight - gap;

    if (remainingHeight > 160) {
      actCard.style.height = `${remainingHeight}px`;
      const actHeader = actCard.querySelector('.card-header')?.offsetHeight || 50;
      const actFooter = actCard.querySelector('.card-footer, .pagination-wrapper')?.offsetHeight || 0;
      const bodyHeight = remainingHeight - actHeader - actFooter;
      actBody.style.maxHeight = `${Math.max(100, bodyHeight)}px`;
      actBody.style.overflowY = 'auto';
    }
  } else {
    // Mode layar kecil / mobile
    leftCard.style.height = 'auto';
    rightCol.style.height = 'auto';
    actCard.style.height = 'auto';
    actBody.style.maxHeight = '320px';
    actBody.style.overflowY = 'auto';
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr.replace(' ', 'T'));
    return d.toLocaleString('id-ID', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    });
  } catch { return dateStr; }
}

function sanitizeOfficerName(raw) {
  if (!raw) return 'Petugas Gudang';
  let s = String(raw);
  s = s.replace(/\s*\(@[a-zA-Z0-9_\-.]+\)/g, '');
  s = s.replace(/@[a-zA-Z0-9_\-.]+/g, '');
  s = s.replace(/\s*\(Admin Gudang\)/i, ' (Admin_Warehouse)');
  s = s.replace(/\s*\(Staff Gudang\)/i, ' (Warehouse_Staff)');
  s = s.replace(/\s*\(Purchasing\)/i, ' (Admin_Purchase)');
  s = s.replace(/\s*\(Admin_Purchasing\)/i, ' (Admin_Purchase)');
  s = s.replace(/\s*-\s*Admin Gudang/i, ' (Admin_Warehouse)');
  s = s.replace(/\s*-\s*Staff Gudang/i, ' (Warehouse_Staff)');
  s = s.replace(/\s*-\s*Purchasing/i, ' (Admin_Purchase)');
  s = s.replace(/\s*-\s*Admin Sales/i, ' (Admin_Sales)');
  s = s.replace(/\s*\(Sales\)/i, ' (Admin_Sales)');
  return s.trim() || 'Petugas Gudang';
}

/**
 * Format & Standardisasi Catatan Transaksi agar Ramah Dibaca
 * Menghilangkan tag-tag teknis internal (bracket, boiler-plate sistem)
 * dan menonjolkan catatan manusia/alasan asli dari purchasing/sales/petugas.
 */
function formatTransactionNote(rawNotes, data = {}) {
  if (!rawNotes || typeof rawNotes !== 'string') {
    return '<span style="color:var(--color-text-subtle);font-style:italic;">Tidak ada catatan khusus</span>';
  }

  let text = rawNotes.trim();
  if (text === '' || text === 'NULL' || text === '-' || text.toLowerCase() === 'ga ada') {
    return '<span style="color:var(--color-text-subtle);font-style:italic;">Tidak ada catatan khusus</span>';
  }

  // 1. Catatan GRN dari Penerimaan Purchasing (misal: "... Catatan GRN: Sesuai Catatan2" atau "blongo2")
  const matchGrn = text.match(/Catatan GRN:\s*(.*)$/i);
  if (matchGrn && matchGrn[1]) {
    const grnNote = matchGrn[1].trim();
    if (grnNote && grnNote !== '-' && grnNote !== 'NULL') {
      return `<strong>${escapeHtml(grnNote)}</strong>`;
    }
  }

  // 2. Keterangan "Alasan: ..." eksplisit (pada pergeseran status, retur, dsb.)
  if (text.includes('Alasan:') && !text.includes('[PRODUK DIHAPUS]')) {
    const m = text.match(/Alasan:\s*([^|\]\n]+)/i);
    if (m && m[1]) {
      const reasonVal = m[1].trim();
      if (reasonVal && reasonVal !== '-' && reasonVal !== 'NULL') {
        return `<strong>${escapeHtml(reasonVal)}</strong>`;
      }
    }
  }

  // 3. Format [Dialihkan dari ...]
  // Contoh: [Dialihkan dari Draft (Tertahan) (5 rim, ref asal: SJ-20261120-9a7a5f) oleh Sabiq Hibatul pada 21 Nov 2026, 05:28]
  if (text.includes('[Dialihkan dari')) {
    const mReason = text.match(/-\s*Alasan:\s*([^\]]+)/i);
    const customReason = mReason && mReason[1] ? mReason[1].trim() : '';

    const matchDialihkan = text.match(/\[Dialihkan dari\s*([^(,\]]+)(?:\s*\(([^)]+)\))?(?:,\s*ref asal:\s*([^)]+)\))?/i);
    let statusAsal = matchDialihkan && matchDialihkan[1] ? matchDialihkan[1].trim() : 'Status Sebelumnya';
    let refAsal = matchDialihkan && matchDialihkan[3] ? matchDialihkan[3].trim() : '';

    if (customReason && customReason !== '-' && customReason !== 'NULL') {
      return `<strong>${escapeHtml(customReason)}</strong> <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;">(Dialihkan dari status ${escapeHtml(statusAsal)}${refAsal ? ` • Ref Asal: ${escapeHtml(refAsal)}` : ''})</div>`;
    }

    return `Dialihkan dari status <strong>${escapeHtml(statusAsal)}</strong>${refAsal ? ` <span style="font-size:11px;color:var(--color-text-muted);">(Ref Asal: ${escapeHtml(refAsal)})</span>` : ''}`;
  }

  // 4. Format [TRANSFER STATUS: ...]
  if (text.includes('[TRANSFER STATUS:')) {
    const mReasonTrf = text.match(/Alasan:\s*([^|\]]+)/i);
    if (mReasonTrf && mReasonTrf[1] && mReasonTrf[1].trim() !== '-' && mReasonTrf[1].trim() !== 'NULL') {
      return `<strong>${escapeHtml(mReasonTrf[1].trim())}</strong>`;
    }
    return '<span style="color:var(--color-text-subtle);font-style:italic;">Pergeseran status stok internal gudang (tanpa catatan tambahan)</span>';
  }

  // 5. Pembersihan tag-tag teknis sistem internal
  text = text.replace(/\[Kondisi Fisik:\s*[^\]]+\]/gi, '');
  text = text.replace(/\[Lokasi:\s*[^\]]+\]/gi, '');
  text = text.replace(/\[Status:\s*[^\]]+\]/gi, '');
  text = text.replace(/\[Sebagian stok[^\]]+\]/gi, '');
  text = text.replace(/\[Status dialihkan:[^\]]+\]/gi, '');
  text = text.replace(/Penerimaan PO\s*#[^\.]+\./gi, '');
  text = text.replace(/Masuk\s*(?:Stok\s*)?(?:Aktif|Karantina|Draft)[^\.]*\./gi, '');
  text = text.replace(/Catatan GRN:\s*/gi, '');

  // 6. Bersihkan simbol pembatas (| , - spasi tab) di tepi string
  text = text.replace(/^[\s\|\,\-\–\—\:]+/, '').replace(/[\s\|\,\-\–\—\:]+$/, '').trim();

  // Jika setelah dibersihkan teksnya kosong
  if (!text || text === '-' || text === 'NULL') {
    const isIn = data.type === 'IN' || data.movement_type === 'IN';
    const rawType = data.raw_type || '';
    const refNo = data.ref_no || data.ref_number || '';
    if (rawType === 'PURCHASE' || refNo.startsWith('GRN')) {
      return '<span style="color:var(--color-text-subtle);font-style:italic;">Penerimaan barang dari supplier (tanpa catatan tambahan)</span>';
    }
    if (rawType === 'SALE' || refNo.includes('-SALE')) {
      return '<span style="color:var(--color-text-subtle);font-style:italic;">Penjualan ke pelanggan (tanpa catatan tambahan)</span>';
    }
    if (rawType === 'PURCHASE_RETURN' || refNo.startsWith('RET-')) {
      return '<span style="color:var(--color-text-subtle);font-style:italic;">Pengembalian barang bermasalah ke supplier</span>';
    }
    return '<span style="color:var(--color-text-subtle);font-style:italic;">Tidak ada catatan khusus</span>';
  }

  return `<strong>${escapeHtml(text)}</strong>`;
}

/* ==========================================================
   5. Modal Detail Aktivitas & Mutasi Stok (Terstandar)
   ========================================================== */
function showUnifiedMovementDetail(data) {
  const old = document.getElementById('inv-movement-detail-modal');
  if (old) old.remove();

  const isIn = data.type === 'IN' || data.movement_type === 'IN';
  const typeColor = isIn ? 'var(--color-success)' : 'var(--color-danger)';
  const st = (data.stock_status || '').toLowerCase();
  const refNo = data.ref_no || data.ref_number || '';
  const rawType = data.raw_type || '';
  const isDeleted = st === 'deleted' || (data.notes && data.notes.includes('[PRODUK DIHAPUS]')) || refNo.startsWith('DEL-');
  const isTransfer = st === 'transfer' || data.type === 'TRANSFER' || (data.notes && data.notes.includes('[TRANSFER STATUS')) || refNo.startsWith('TRF-');
  const isPurchaseReturn = rawType === 'PURCHASE_RETURN' || refNo.startsWith('RET-');
  const isSale = rawType === 'SALE' || refNo.includes('-SALE') || refNo.startsWith('SO-');
  const isSaleReturn = rawType === 'SALES_RETURN' || refNo.includes('-CANCEL');
  let statusBadgeHtml = '';
  let statusDetailText = 'Aktif (Siap Edar)';
  if (st === 'draft' || (data.notes && data.notes.includes('Status: Draft'))) {
    statusDetailText = 'Draft (Belum Dipublikasikan / Tertahan)';
    statusBadgeHtml = `<span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #7dd3fc; font-weight:600; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#0284c7;"></span>Masuk: Draft (Tertahan)</span>`;
  } else if (st === 'karantina' || (data.notes && data.notes.includes('Status: Karantina'))) {
    statusDetailText = 'Karantina / Hold (Inspeksi QC)';
    statusBadgeHtml = `<span class="badge" style="background:#fef3c7; color:#b45309; border:1px solid #fcd34d; font-weight:600; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#d97706;"></span>Masuk: Karantina (Inspeksi QC)</span>`;
  } else {
    statusDetailText = 'Aktif (Siap Edar)';
    statusBadgeHtml = `<span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; font-weight:600; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#16a34a;"></span>Masuk: Aktif (Siap Edar)</span>`;
  }

  // Deteksi Kondisi Fisik Barang (Good / Damaged / Shortage)
  let conditionNote = data.condition_note || '';
  if (!conditionNote && data.notes) {
    const matchCond = data.notes.match(/\[Kondisi Fisik:\s*([^\]]+)\]/i);
    if (matchCond) conditionNote = matchCond[1].trim();
  }
  let condRow = null;
  if (conditionNote) {
    const cnLower = conditionNote.toLowerCase();
    let condBadge = '';
    if (cnLower.includes('good') || cnLower.includes('baik')) {
      condBadge = `<span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; font-weight:600; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#16a34a;"></span>Good (Kondisi Baik)</span>`;
    } else if (cnLower.includes('damag') || cnLower.includes('rusak')) {
      condBadge = `<span class="badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-weight:700; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#dc2626;"></span>Damaged (Barang Rusak) &rarr; Masuk Karantina (QC)</span>`;
    } else if (cnLower.includes('short') || cnLower.includes('kurang') || cnLower.includes('cacat')) {
      condBadge = `<span class="badge" style="background:#fef3c7; color:#b45309; border:1px solid #fcd34d; font-weight:700; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#d97706;"></span>Shortage (Bermasalah / Cacat) &rarr; Masuk Karantina (QC)</span>`;
    } else {
      condBadge = `<span class="badge" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; font-weight:600; font-size:12px; padding:3px 8px;">${escapeHtml(conditionNote)}</span>`;
    }
    condRow = ['Kondisi Fisik Barang', condBadge];
  }

  // Deteksi jenis transaksi
  let jenisTransaksi = isIn ? 'Stok Masuk' : 'Stok Keluar';
  if (isDeleted) {
    jenisTransaksi = 'Penonaktifan / Penghapusan Master Produk';
  } else if (isTransfer) {
    jenisTransaksi = 'Pergeseran Status Stok (Internal Gudang)';
  } else if (rawType === 'PURCHASE' || refNo.startsWith('GRN')) {
    jenisTransaksi = 'Pembelian dari Supplier (GRN)';
  } else if (rawType === 'ADJUSTMENT_IN' || refNo.startsWith('SJ')) {
    jenisTransaksi = 'Penyesuaian Stok Masuk (Surat Jalan)';
  } else if (isSaleReturn) {
    jenisTransaksi = 'Retur / Pembatalan Penjualan Pelanggan';
  } else if (rawType === 'ADJUSTMENT_OUT' || refNo.startsWith('SPK')) {
    jenisTransaksi = 'Pengeluaran Stok (SPK)';
  } else if (isSale) {
    jenisTransaksi = 'Penjualan ke Pelanggan (Sales Order)';
  } else if (rawType === 'PURCHASE_RETURN') {
    jenisTransaksi = 'Return ke Supplier';
  }

  // Nama Partner (Supplier / Customer) + Dept
  let partnerDisplay = data.partner_name || '';
  if (data.department) {
    if (partnerDisplay && partnerDisplay !== 'Internal / Gudang') {
      partnerDisplay += ` (Dept Pemohon: ${data.department})`;
    } else {
      partnerDisplay = `Internal (Dept Pemohon: ${data.department})`;
    }
  }
  if (!partnerDisplay || partnerDisplay === 'Internal / Gudang') {
    if (isSale || isSaleReturn) {
      partnerDisplay = 'Pelanggan / Customer (Sales Order)';
    } else {
      partnerDisplay = isTransfer ? 'Internal Gudang (Pergeseran Status)' : (isIn ? 'Supplier' : (isDeleted ? 'Internal Gudang (Audit Master Produk)' : 'Customer / Internal'));
    }
  } else if (isSale || isSaleReturn) {
    if (!partnerDisplay.toLowerCase().includes('customer') && !partnerDisplay.toLowerCase().includes('pelanggan')) {
      partnerDisplay += ' (Customer)';
    }
  }

  // No Referensi + PO / SO jika ada
  let refDisplay = refNo || '-';
  if (data.po_no && !refDisplay.includes(data.po_no)) {
    refDisplay += ` (No. PO: ${data.po_no})`;
  }
  if (data.so_no) {
    if (refNo && refNo !== data.so_no) {
      refDisplay = `${data.so_no} (Ref: ${refNo})`;
    } else {
      refDisplay = data.so_no;
    }
  }

  // Petugas pencatat (Diseragamkan: Nama (Role Akun) tanpa @username)
  const rawPetugas = data.recorded_by || data.performed_by_name || data.created_by_name || 'Petugas Gudang';
  const petugasDisplay = sanitizeOfficerName(rawPetugas);

  // Format tanggal & jam
  const dateDisplay = formatDate(data.date || data.movement_date);

  // Satuan & Qty
  const unit = data.unit || 'unit';
  const qtyFormatted = formatQty(data.quantity);
  const qtyColor = isIn ? 'var(--color-success)' : 'var(--color-danger)';
  const qtySign = isIn ? '+' : '-';

  let rows = [];
  if (isDeleted) {
    let reasonText = '-';
    let extraNotes = '';
    if (data.notes) {
      const match = data.notes.match(/\[PRODUK DIHAPUS\]\s*Alasan:\s*([^|]+)(?:\|\s*Catatan:\s*(.*))?/i);
      if (match) {
        reasonText = match[1].trim();
        extraNotes = match[2] ? match[2].trim() : '';
      } else {
        reasonText = data.notes.replace('[PRODUK DIHAPUS]', '').trim();
      }
    }
    rows = [
      ['Produk & SKU', `<strong>${escapeHtml(data.product_name || '-')}</strong> <span class="badge" style="font-size:10px;padding:1px 5px;background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1;">Nonaktif</span>${data.sku ? `<br><span style="font-size:12px;color:var(--color-text-muted);">(${escapeHtml(data.sku)})</span>` : ''}`],
      ['Status Transaksi', `<strong style="color:#b91c1c;">Produk Telah Dinonaktifkan / Dihapus dari Katalog</strong>`],
      ['Alasan Penghapusan', `<strong style="color:#b91c1c; font-size:14px;">${escapeHtml(reasonText)}</strong>`],
      extraNotes ? ['Keterangan Tambahan', escapeHtml(extraNotes)] : null,
      ['Jumlah Mutasi', `<span style="font-weight:700; color:#b91c1c; font-size:15px;">0 ${escapeHtml(unit)} (Penonaktifan)</span>`],
      (data.balance_stock !== undefined && data.balance_stock !== null) ? ['Sisa Stok Akhir', `<strong>${formatQty(data.balance_stock)} ${escapeHtml(unit)}</strong>`] : null,
      ['No. Dokumen / Referensi', `<span style="font-family:var(--font-mono);font-weight:600;">${escapeHtml(refDisplay)}</span>`],
      ['Dicatat oleh', `<strong style="color:var(--color-text);">${escapeHtml(petugasDisplay)}</strong>`],
      ['Waktu Eksekusi', escapeHtml(dateDisplay)],
    ].filter(Boolean);
  } else if (isTransfer) {
    let fromLabel = 'Status Asal';
    let toLabel = 'Status Tujuan';
    let transferQtyVal = data.transfer_qty || 0;
    let reasonText = '-';
    if (data.notes) {
      const matchLabel = data.notes.match(/\[TRANSFER STATUS:\s*([^→\->â†’]+?)(?:\s*(?:→|->|â†’)\s*)([^\]]+)\]/i);
      if (matchLabel) {
        fromLabel = matchLabel[1].trim();
        toLabel = matchLabel[2].trim();
      }
      const matchQty = data.notes.match(/Sebesar\s+([\d\.\,]+)/i);
      if (matchQty && matchQty[1]) transferQtyVal = parseFloat(matchQty[1].replace(',', '.'));
      const matchReason = data.notes.match(/Alasan:\s*([^|]+)/i);
      if (matchReason && matchReason[1]) reasonText = matchReason[1].trim();
    }
    if (!transferQtyVal && data.quantity > 0) transferQtyVal = data.quantity;

    rows = [
      ['Produk & SKU', `<strong>${escapeHtml(data.product_name || '-')}</strong>${data.sku ? ` <span style="font-size:12px;color:var(--color-text-muted);">(${escapeHtml(data.sku)})</span>` : ''}`],
      ['Jenis Mutasi', `<strong style="color:#7e22ce;">Pergeseran Status Stok (Internal Gudang)</strong>`],
      ['Status Asal', `<span class="badge" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; font-weight:600;">${escapeHtml(fromLabel)}</span>`],
      ['Status Tujuan', `<span class="badge" style="background:#f3e8ff; color:#6b21a8; border:1px solid #d8b4fe; font-weight:700;">${escapeHtml(toLabel)}</span>`],
      ['Jumlah Dipindahkan', `<span style="font-weight:700; color:#7e22ce; font-size:15px;">${formatQty(transferQtyVal)} ${escapeHtml(unit)}</span> <div style="font-size:11px;color:#9333ea;font-weight:600;">Pergeseran Status</div>`],
      (data.balance_stock !== undefined && data.balance_stock !== null) ? ['Total Stok Fisik', `<strong>${formatQty(data.balance_stock)} ${escapeHtml(unit)} (Tetap)</strong>`] : null,
      ['No. Dokumen / Referensi', `<span style="font-family:var(--font-mono);font-weight:600;">${escapeHtml(refDisplay)}</span>`],
      ['Dicatat oleh', `<strong style="color:var(--color-text);">${escapeHtml(petugasDisplay)}</strong>`],
      ['Waktu Eksekusi', escapeHtml(dateDisplay)],
    ].filter(Boolean);
  } else if (isPurchaseReturn) {
    const isFromQuarantine = st === 'karantina' || (conditionNote && /damag|short|cacat|rusak|defect/i.test(conditionNote));
    const sourceStockBadge = isFromQuarantine
      ? `<span class="badge" style="background:#fef3c7; color:#b45309; border:1px solid #fcd34d; font-weight:600; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#d97706;"></span>Stok Karantina (QC / Area Rusak)</span>`
      : `<span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; font-weight:600; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#16a34a;"></span>Stok Aktif (Siap Edar)</span>`;

    let retCondBadge = `<span class="badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-weight:700; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#dc2626;"></span>${escapeHtml(conditionNote || 'Defective')} (Barang Rusak / Cacat)</span>`;
    if (conditionNote && /good|baik/i.test(conditionNote)) {
      retCondBadge = `<span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; font-weight:600; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#16a34a;"></span>Good (Kondisi Baik)</span>`;
    }

    let retRef = refNo || '-';
    if (data.grn_no || data.po_no) {
      const parts = [];
      if (data.grn_no) parts.push(`Ref GRN: ${data.grn_no}`);
      if (data.po_no) parts.push(`PO: ${data.po_no}`);
      retRef += ` (${parts.join(' | ')})`;
    }

    rows = [
      ['Produk & SKU', `<strong>${escapeHtml(data.product_name || '-')}</strong>${data.sku ? ` <span style="font-size:12px;color:var(--color-text-muted);">(${escapeHtml(data.sku)})</span>` : ''}`],
      ['Jenis Transaksi', `<strong style="color:#b91c1c;">Retur Pembelian (Pengembalian Barang ke Supplier)</strong>`],
      ['Sumber Pengeluaran Stok', sourceStockBadge],
      ['Kondisi Barang yang Diretur', retCondBadge],
      ['Jumlah Diretur', `<span style="font-weight:700; color:var(--color-danger); font-size:15px;">-${qtyFormatted} ${escapeHtml(unit)}</span> <div style="font-size:11px;color:#dc2626;font-weight:600;">Keluar ke Supplier</div>`],
      (data.balance_stock !== undefined && data.balance_stock !== null) ? ['Sisa Total Stok Fisik', `<strong>${formatQty(data.balance_stock)} ${escapeHtml(unit)}</strong>`] : null,
      ['No. Dokumen Retur', `<span style="font-family:var(--font-mono);font-weight:600;">${escapeHtml(retRef)}</span>`],
      ['Dikembalikan ke (Supplier)', `<strong>${escapeHtml(partnerDisplay || 'Supplier')}</strong>`],
      ['Dicatat oleh', `<strong style="color:var(--color-text);">${escapeHtml(petugasDisplay)}</strong>`],
      ['Waktu Transaksi', escapeHtml(dateDisplay)],
      ['Alasan Retur / Catatan', formatTransactionNote(data.notes, data)],
    ].filter(Boolean);
  } else {
    rows = [
      ['Produk & SKU', `<strong>${escapeHtml(data.product_name || '-')}</strong>${data.sku ? ` <span style="font-size:12px;color:var(--color-text-muted);">(${escapeHtml(data.sku)})</span>` : ''}`],
      ['Jenis Transaksi', escapeHtml(jenisTransaksi)],
      isIn ? ['Status Penerimaan', escapeHtml(statusDetailText)] : null,
      condRow,
      ['Jumlah Mutasi', `<span style="font-weight:700; color:${qtyColor}; font-size:15px;">${qtySign}${qtyFormatted} ${escapeHtml(unit)}</span>`],
      (data.balance_stock !== undefined && data.balance_stock !== null) ? ['Sisa Stok Setelah Transaksi', `<strong>${formatQty(data.balance_stock)} ${escapeHtml(unit)}</strong>`] : null,
      ['No. Referensi / Dokumen', `<span style="font-family:var(--font-mono);font-weight:600;">${escapeHtml(refDisplay)}</span>`],
      [isIn ? 'Diterima dari' : 'Ditujukan ke', escapeHtml(partnerDisplay)],
      ['Dicatat oleh', `<strong style="color:var(--color-text);">${escapeHtml(petugasDisplay)}</strong>`],
      ['Waktu Transaksi', escapeHtml(dateDisplay)],
      ['Catatan Transaksi', formatTransactionNote(data.notes, data)],
    ].filter(Boolean);
  }

  let headerBadge = '';
  if (isDeleted) {
    headerBadge = `<span class="badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-weight:700; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#dc2626;"></span>🗑️ Produk Dihapus / Dinonaktifkan</span>`;
  } else if (isTransfer) {
    headerBadge = `<span class="badge" style="background:#f3e8ff; color:#6b21a8; border:1px solid #d8b4fe; font-weight:700; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#9333ea;"></span>🔄 Pergeseran Status Stok</span>`;
  } else if (isPurchaseReturn) {
    headerBadge = `<span class="badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-weight:700; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#dc2626;"></span>📤 Retur Pembelian ke Supplier</span>`;
  } else if (isSaleReturn) {
    headerBadge = `<span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; font-weight:600; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#16a34a;"></span>📥 Retur Penjualan Pelanggan</span>`;
  } else if (isSale) {
    headerBadge = `<span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #7dd3fc; font-weight:600; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#0284c7;"></span>🛒 Penjualan ke Pelanggan (SO)</span>`;
  } else if (isIn) {
    headerBadge = statusBadgeHtml;
  } else {
    headerBadge = `<span class="badge" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; font-weight:600; font-size:12px; padding:3px 8px;"><span class="badge-dot" style="background:#dc2626;"></span>📤 Stok Keluar</span>`;
  }

  const modalHtml = `
    <div id="inv-movement-detail-modal" role="dialog" aria-modal="true" aria-label="Detail Mutasi Stok"
         style="position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;
                background:rgba(0,0,0,0.45);padding:16px;">
      <div style="background:#fff;border-radius:12px;width:100%;max-width:520px;
                  box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;animation:inv-toast-in .25s ease;">
        <!-- Header -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--color-border-subtle);
                    display:flex;align-items:center;justify-content:space-between;">
          <div>
            <div style="font-weight:700;font-size:var(--font-size-base);color:var(--color-text);">
              Detail Mutasi Stok
            </div>
            <div style="margin-top:4px;">
              ${headerBadge}
            </div>
          </div>
          <button type="button" onclick="document.getElementById('inv-movement-detail-modal').remove()"
                  style="background:none;border:none;cursor:pointer;font-size:22px;
                         color:var(--color-text-muted);line-height:1;padding:4px;" aria-label="Tutup">&times;</button>
        </div>
        <!-- Body -->
        <div style="padding:20px;max-height:72vh;overflow-y:auto;">
          <table style="width:100%;border-collapse:collapse;font-size:var(--font-size-sm);">
            ${rows.map(([label, val]) => `
              <tr style="border-bottom:1px solid var(--color-border-subtle);">
                <td style="padding:10px 12px 10px 0;color:var(--color-text-muted);
                           white-space:nowrap;vertical-align:top;width:42%;">${label}</td>
                <td style="padding:10px 0;color:var(--color-text);font-weight:500;
                           vertical-align:top;">${val}</td>
              </tr>
            `).join('')}
          </table>
        </div>
        <!-- Footer -->
        <div style="padding:12px 20px;background:var(--color-surface-hover);
                    border-top:1px solid var(--color-border-subtle);text-align:right;">
          <button type="button" onclick="document.getElementById('inv-movement-detail-modal').remove()"
                  class="btn btn-secondary btn-sm" style="min-width:80px;">Tutup</button>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHtml);

  document.getElementById('inv-movement-detail-modal').addEventListener('click', function(e) {
    if (e.target === this) this.remove();
  });
}

function showActivityDetail(act) {
  showUnifiedMovementDetail(act);
}

// Intercept kategori untuk staff dinonaktifkan: Warehouse_Staff diperbolehkan membaca data kategori (Read-Only)
window.interceptKelolaCategoryForStaff = function() {};

/* ==========================================================
   Utility
   ========================================================== */
function formatQty(val) {
  const num = parseFloat(val);
  if (isNaN(num)) return val;
  return num % 1 === 0 ? num.toFixed(0) : num.toString();
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
