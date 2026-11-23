/**
 * Handler Dinamis Daftar Produk Terhubung ke Database MySQL
 * Proyek: Inventoria (database hris)
 */

document.addEventListener('DOMContentLoaded', function() {
  initProductPage();
});

let currentProducts = [];
const ITEMS_PER_PAGE = 10;
let currentPage = 1;

function initProductPage() {
  loadCategoriesDropdown();
  loadLocationsDropdown();
  loadProductsFromDb();

  const tbody = document.querySelector('.table tbody');
  if (tbody) {
    tbody.addEventListener('click', event => {
      // 1. Klik badge lokasi di tabel -> langsung filter lokasi tersebut
      const locBadge = event.target.closest('.product-loc-badge');
      if (locBadge) {
        const targetLoc = locBadge.dataset.loc;
        const locSelect = document.getElementById('filterLocation');
        if (locSelect && targetLoc) {
          locSelect.value = targetLoc;
          loadProductsFromDb();
        }
        return;
      }

      // 2. Klik tombol hapus
      const button = event.target.closest('.js-delete-product');
      if (button) {
        window.deleteProduct(
          button.dataset.productId, 
          button.dataset.productName,
          button.dataset.productSku,
          parseFloat(button.dataset.productStock) || 0,
          button.dataset.productUnit
        );
      }
    });
  }

  // Pasang event listener untuk search input
  const searchInput = document.querySelector('.filter-input');
  if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', function() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        loadProductsFromDb();
      }, 300);
    });
  }

  // Pasang event listener untuk category select
  const catSelect = document.querySelector('select[aria-label="Filter Kategori"]');
  if (catSelect) {
    catSelect.addEventListener('change', function() {
      loadProductsFromDb();
    });
  }

  // Pasang event listener untuk status select
  const statusSelect = document.querySelector('select[aria-label="Filter Status Stok"]');
  if (statusSelect) {
    statusSelect.addEventListener('change', function() {
      loadProductsFromDb();
    });
  }

  // Pasang event listener untuk filter lokasi penyimpanan
  const locSelect = document.getElementById('filterLocation');
  if (locSelect) {
    locSelect.addEventListener('change', function() {
      loadProductsFromDb();
    });
  }

  // Pasang event listener untuk tombol reset filter
  const btnReset = document.getElementById('btnResetFilters');
  if (btnReset) {
    btnReset.addEventListener('click', function(e) {
      e.preventDefault();
      if (searchInput) searchInput.value = '';
      if (catSelect) catSelect.value = '';
      if (statusSelect) statusSelect.value = '';
      if (locSelect) locSelect.value = '';
      loadProductsFromDb();
    });
  }

  // Pasang event listener tombol Kelola Lokasi Penyimpanan
  const btnManageLoc = document.getElementById('btnOpenManageLocationsModal') || document.getElementById('btnOpenAddLocationModal');
  if (btnManageLoc) {
    btnManageLoc.addEventListener('click', function(e) {
      e.preventDefault();
      showManageLocationsModal();
    });
  }
}

// 1. Muat Opsi Kategori dari Database
function loadCategoriesDropdown() {
  const catSelect = document.querySelector('select[aria-label="Filter Kategori"]');
  if (!catSelect) return;

  fetch('../api/categories.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        catSelect.innerHTML = '<option value="">Semua Kategori</option>';
        res.data.forEach(cat => {
          const opt = document.createElement('option');
          opt.value = cat.id;
          opt.textContent = `${cat.name} (${cat.product_count})`;
          catSelect.appendChild(opt);
        });
      }
    })
    .catch(err => console.error('Gagal memuat kategori:', err));
}

// 1b. Muat Opsi Lokasi Penyimpanan dari Database
function loadLocationsDropdown() {
  const locSelect = document.getElementById('filterLocation');
  if (!locSelect) return;

  const currentVal = locSelect.value;

  fetch('../api/locations.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        locSelect.innerHTML = `
          <option value="">Semua Lokasi</option>
          <option value="__none__">⚠️ Tanpa Lokasi</option>
        `;
        res.data.forEach(loc => {
          const opt = document.createElement('option');
          opt.value = loc.location_name;
          const countStr = loc.product_count > 0 ? ` (${loc.product_count})` : '';
          opt.textContent = `${loc.location_code} - ${loc.location_name}${countStr}`;
          locSelect.appendChild(opt);
        });
        if (currentVal) {
          locSelect.value = currentVal;
        }
      }
    })
    .catch(err => console.error('Gagal memuat lokasi penyimpanan:', err));
}

// 2. Ambil Produk dari MySQL Database
function loadProductsFromDb() {
  const searchInput = document.querySelector('.filter-input');
  const catSelect = document.querySelector('select[aria-label="Filter Kategori"]');
  const statusSelect = document.querySelector('select[aria-label="Filter Status Stok"]');
  const locSelect = document.getElementById('filterLocation');
  const btnReset = document.getElementById('btnResetFilters');
  const tbody = document.querySelector('.table tbody');

  const search = searchInput ? encodeURIComponent(searchInput.value.trim()) : '';
  const category = catSelect ? encodeURIComponent(catSelect.value) : '';
  const status = statusSelect ? encodeURIComponent(statusSelect.value) : '';
  const location = locSelect ? encodeURIComponent(locSelect.value) : '';

  const hasFilter = Boolean(search || category || status || location);
  if (btnReset) {
    btnReset.style.display = hasFilter ? 'inline-block' : 'none';
  }

  let url = `../api/products.php?search=${search}&category=${category}&status=${status}&location=${location}`;

  if (tbody) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" style="text-align: center; padding: 24px; color: var(--color-text-muted);">
          <div style="display: inline-block; animation: spin 1s infinite linear; margin-right: 8px;">🔄</div>
          Memuat data produk dari database MySQL...
        </td>
      </tr>
    `;
  }

  fetch(url)
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success') {
        currentProducts = res.data || [];
        currentPage = 1;
        renderCurrentPage();
      } else {
        showErrorInTable(res.message || 'Gagal memuat data produk');
      }
    })
    .catch(err => {
      showErrorInTable('Koneksi ke backend database gagal: ' + err.message);
    });
}

// 2b. Render Halaman Produk Saat Ini (10 Produk per Halaman)
function renderCurrentPage() {
  const totalItems = currentProducts.length;
  const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE) || 1;

  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  const start = (currentPage - 1) * ITEMS_PER_PAGE;
  const end   = start + ITEMS_PER_PAGE;
  const pageProducts = currentProducts.slice(start, end);

  renderProductsTable(pageProducts);
  renderPaginationControls(totalPages, currentPage);
}

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

// 2c. Render Kontrol Tombol Pagination Dinamis
function renderPaginationControls(totalPages, page) {
  const container = document.getElementById('paginationControls');
  if (!container) return;

  if (totalPages <= 1) {
    container.innerHTML = '';
    return;
  }

  let html = '';

  // Tombol Sebelumnya «
  const prevDisabled = page <= 1 ? 'disabled' : '';
  html += `<a href="#" class="page-btn ${prevDisabled}" data-page="${page - 1}" aria-label="Sebelumnya" title="Halaman Sebelumnya">&laquo;</a>`;

  const items = getPaginationRange(totalPages, page);
  items.forEach(item => {
    if (item === '...') {
      html += `<span class="page-ellipsis" style="display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:34px; color:var(--color-text-muted); font-size:12px; font-weight:600; user-select:none;">...</span>`;
    } else {
      const active = item === page ? 'active' : '';
      html += `<a href="#" class="page-btn ${active}" data-page="${item}" title="Halaman ${item}">${item}</a>`;
    }
  });

  // Tombol Berikutnya »
  const nextDisabled = page >= totalPages ? 'disabled' : '';
  html += `<a href="#" class="page-btn ${nextDisabled}" data-page="${page + 1}" aria-label="Berikutnya" title="Halaman Berikutnya">&raquo;</a>`;

  container.innerHTML = html;

  // Pasang event listener pada setiap tombol halaman
  container.querySelectorAll('.page-btn:not(.disabled)').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const targetPage = parseInt(this.dataset.page, 10);
      if (!isNaN(targetPage) && targetPage !== currentPage && targetPage >= 1 && targetPage <= totalPages) {
        currentPage = targetPage;
        renderCurrentPage();
        document.querySelector('.table-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
}

// 3. Render Baris Tabel Produk
function renderProductsTable(products) {
  const tbody = document.querySelector('.table tbody');
  if (!tbody) return;

  if (products.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" style="text-align: center; padding: 32px; color: var(--color-text-muted);">
          Tidak ada produk yang cocok dengan kriteria filter.
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = '';
  products.forEach(p => {
    const tr = document.createElement('tr');

    const activeStock = parseFloat(p.stock_active ?? p.current_stock) || 0;
    const draftStock  = parseFloat(p.stock_draft ?? 0) || 0;
    const quaranStock = parseFloat(p.stock_quarantine ?? 0) || 0;
    const totalStock  = parseFloat(p.current_stock ?? p.stock) || 0;
    const minStock    = parseFloat(p.minimum_stock) || 0;

    // Badge status format berbasis Stok Aktif
    let badgeClass = 'badge-available';
    let badgeLabel = 'Tersedia';
    let badgeCustom = '';

    if (!p.is_complete) {
      const missingStr = (p.missing_fields || []).join(', ');
      badgeCustom = `<span class="badge" style="background:#fffbeb; color:#92400e; border:1px solid #fde68a; font-weight:600;" title="Konfigurasi belum lengkap: ${escapeHtml(missingStr)}"><span class="badge-dot" style="background:#d97706;"></span>Perlu Dilengkapi</span>`;
    } else if (activeStock <= 0) {
      badgeClass = 'badge-out';
      badgeLabel = 'Habis';
    } else if (activeStock <= minStock) {
      badgeClass = 'badge-low';
      badgeLabel = 'Hampir habis';
    }

    const badgeHtml = badgeCustom || `<span class="badge ${badgeClass}"><span class="badge-dot"></span>${badgeLabel}</span>`;
    const buyPrice = formatRupiah(p.purchase_price);
    const sellPrice = formatRupiah(p.selling_price);

    let editActionBtn = `<a href="product-form.html?id=${p.id}" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px; min-height: 28px; line-height: 1.3;" title="Edit Data">Edit</a>`;
    if (!p.is_complete) {
      editActionBtn = `<a href="product-form.html?id=${p.id}" class="btn btn-secondary btn-sm" style="background:#fffbeb; color:#92400e; border-color:#fde68a; padding: 4px 8px; font-size: 11px; min-height: 28px; line-height: 1.3; font-weight: 600;" title="Lengkapi Data Produk">Lengkapi</a>`;
    }

    let subNameNote = '';
    if (!p.is_complete && p.missing_fields && p.missing_fields.length > 0) {
      const sampleMissing = p.missing_fields.slice(0, 2).join(', ') + (p.missing_fields.length > 2 ? '...' : '');
      subNameNote = `<div style="font-size: 11px; color: #b45309; font-weight: 500;">⚠️ Perlu: ${escapeHtml(sampleMissing)}</div>`;
    } else if (p.location) {
      subNameNote = `
        <div class="product-loc-badge" data-loc="${escapeHtml(p.location)}" style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #0284c7; background: #f0f9ff; border: 1px solid #bae6fd; padding: 2px 7px; border-radius: 4px; margin-top: 4px; cursor: pointer; user-select: none;" title="Klik untuk memfilter barang di lokasi: ${escapeHtml(p.location)}">
          <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
          <span style="font-weight: 600;">${escapeHtml(p.location)}</span>
        </div>`;
    } else {
      subNameNote = `<div style="font-size: 11px; color: var(--color-text-muted); font-style: italic; margin-top: 2px;">Belum ada lokasi</div>`;
    }

    tr.innerHTML = `
      <td><span class="table-sku">${escapeHtml(p.sku)}</span></td>
      <td>
        <strong>${escapeHtml(p.name)}</strong>
        ${subNameNote}
      </td>
      <td>${escapeHtml(p.category_name || '-')}</td>
      <td style="text-align: right;">${buyPrice}</td>
      <td style="text-align: right;">${sellPrice}</td>
      <td style="text-align: center;">
        <div style="font-weight: bold; font-size: 14px; ${activeStock <= 0 ? 'color: var(--color-danger);' : (activeStock <= minStock ? 'color: var(--color-warning);' : 'color: var(--color-text);')}">
          ${formatQty(activeStock)} ${escapeHtml(p.unit || 'unit')}
        </div>
        ${totalStock !== activeStock ? `<div style="font-size: 11px; color: var(--color-text-muted); margin-top: 2px;" title="Total Fisik di Gudang: ${formatQty(totalStock)} ${escapeHtml(p.unit || 'unit')} (termasuk Draft & QC)">Total: ${formatQty(totalStock)} ${escapeHtml(p.unit || 'unit')}</div>` : ''}
      </td>
      <td style="text-align: center;">
        <div style="display: inline-flex; flex-direction: column; align-items: center; justify-content: center;">
          <div>${badgeHtml}</div>
          <div style="display: flex; gap: 3px; margin-top: 4px; align-items: center; justify-content: center; flex-wrap: nowrap;">
            <span style="font-size: 10px; font-weight: 600; padding: 1px 5px; border-radius: 3px; background: #dcfce7; color: #15803d; border: 1px solid rgba(21, 128, 61, 0.2); white-space: nowrap;" title="Stok Aktif (Siap Edar / Siap Keluar)">Aktif: ${formatQty(activeStock)}</span>
            <span style="font-size: 10px; font-weight: 600; padding: 1px 5px; border-radius: 3px; background: #e0f2fe; color: #0369a1; border: 1px solid rgba(3, 105, 161, 0.2); white-space: nowrap;" title="Stok Draft (Belum Dipublikasikan)">Draft: ${formatQty(draftStock)}</span>
            <span style="font-size: 10px; font-weight: 600; padding: 1px 5px; border-radius: 3px; background: #fef3c7; color: #b45309; border: 1px solid rgba(180, 83, 9, 0.2); white-space: nowrap;" title="Stok Karantina / Inspeksi QC">QC: ${formatQty(quaranStock)}</span>
          </div>
        </div>
      </td>
      <td style="text-align: center;">
        <div class="table-actions" style="justify-content: center; gap: 4px;">
          <a href="product-detail.html?id=${p.id}" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px; min-height: 28px; line-height: 1.3;" title="Lihat Detail Produk">Lihat</a>
          ${editActionBtn}
          <button type="button" class="btn btn-secondary btn-sm js-delete-product" 
            data-product-id="${p.id}" 
            data-product-name="${escapeHtml(p.name)}" 
            data-product-sku="${escapeHtml(p.sku)}" 
            data-product-stock="${totalStock}" 
            data-product-unit="${escapeHtml(p.unit || 'unit')}" 
            title="Nonaktifkan Produk" 
            style="color: var(--color-danger); padding: 4px 8px; font-size: 11px; min-height: 28px; line-height: 1.3;">
            Hapus
          </button>
        </div>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

// 4. Hapus Produk dari Database (Sesuai Opsi A: Blokir jika stok > 0, Popup Alasan jika stok = 0)
window.deleteProduct = function(id, name, sku, stock, unit) {
  const stockNum = parseFloat(stock) || 0;
  const unitLabel = unit || 'unit';

  // Validasi Opsi A: Jika produk masih memiliki stok fisik, tolak penghapusan!
  if (stockNum > 0) {
    showProductAlert({
      type: 'warning',
      title: 'Produk Tidak Dapat Dihapus',
      message: `
        <div style="text-align: left;">
          Produk <strong>${escapeHtml(name)}</strong> (${escapeHtml(sku || '-')}) saat ini masih memiliki sisa stok fisik sebanyak:
          <div style="margin: 12px 0; padding: 12px 14px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; text-align: center;">
            <div style="font-size: 20px; font-weight: 700; color: #e11d48;">${formatQty(stockNum)} ${escapeHtml(unitLabel)}</div>
            <div style="font-size: 11px; color: #9f1239; margin-top: 2px;">Tersimpan di gudang</div>
          </div>
          Sesuai SOP keamanan inventaris (Opsi A), produk <strong>hanya dapat dihapus jika stok fisik sudah bernilai 0</strong>.<br><br>
          Silakan lakukan transaksi <strong>Stok Keluar</strong> terlebih dahulu untuk mengeluarkan sisa barang.
          <div style="margin-top: 14px; text-align: center;">
            <a href="stock-out.html?product_id=${id}" class="btn btn-primary btn-sm" style="display: inline-block; padding: 6px 14px; font-size: 12px; text-decoration: none;">
              Catat Stok Keluar Sekarang &rarr;
            </a>
          </div>
        </div>
      `,
      buttonText: 'Tutup'
    });
    return;
  }

  // Jika kondisi stok sudah = 0: Munculkan popup input alasan dihapus
  showDeleteReasonModal({
    id,
    name,
    sku,
    unit: unitLabel,
    onSuccess: () => loadProductsFromDb()
  });
};

function showDeleteReasonModal({ id, name, sku, unit, onSuccess }) {
  injectProductAlertStyles();
  document.getElementById('prd-delete-reason-modal')?.remove();

  const userObj = (window.currentUserSession && window.currentUserSession.user) 
    ? window.currentUserSession.user 
    : (window.currentUserSession || {});

  const headerName = document.querySelector('.header-user-name, .user-name')?.textContent?.trim();
  const currentAdminName = userObj.name || headerName || 'Sabiq Hibatul';
  const currentUsername = userObj.username || '';
  const currentAdminRole = userObj.role_label || userObj.role || 'Admin Warehouse';

  // Format identitas terstandar: Nama (Role Akun) tanpa @username
  const roleDisplay = (userObj.role || currentAdminRole || 'Admin_Warehouse').replace(/\s+/g, '_');
  const fullAccountDisplay = `${currentAdminName} (${roleDisplay})`;

  const modalHtml = `
    <div id="prd-delete-reason-modal" class="prd-overlay" style="z-index: 10050;">
      <div class="prd-box" style="max-width: 480px; text-align: left; padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--color-border-subtle); padding-bottom: 12px; margin-bottom: 16px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 38px; height: 38px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line>
              </svg>
            </div>
            <div>
              <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Konfirmasi Hapus Produk</h3>
              <div style="font-size: 11px; color: var(--color-text-muted);">Pencatatan Audit Trail Penghapusan Produk</div>
            </div>
          </div>
          <button type="button" class="js-close-del-reason" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--color-text-muted); line-height: 1;">&times;</button>
        </div>

        <div style="background: #f8fafc; border: 1px solid var(--color-border-subtle); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px;">
          <div style="font-size: 12px; color: var(--color-text-muted);">Produk yang akan dinonaktifkan:</div>
          <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px;">${escapeHtml(name)}</div>
          <div style="display: flex; gap: 14px; font-size: 11px; color: var(--color-text-muted); margin-top: 4px;">
            <span>Kode SKU: <strong style="color: var(--color-text);">${escapeHtml(sku || '-')}</strong></span>
            <span>Sisa Stok: <strong style="color: #16a34a;">0 ${escapeHtml(unit || 'unit')}</strong> (Aman)</span>
          </div>
        </div>

        <form id="formDeleteProductReason" novalidate>
          <div style="margin-bottom: 14px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
              Alasan Penghapusan <span style="color: #dc2626;">*</span>
            </label>
            <select id="delInputReason" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px; background: #fff;" required>
              <option value="">-- Pilih Alasan Penghapusan --</option>
              <option value="Barang Tidak Diproduksi Lagi / Discontinue">Barang Tidak Diproduksi Lagi / Discontinue</option>
              <option value="Barang Rusak / Kadaluwarsa / Musnah">Barang Rusak / Kadaluwarsa / Musnah</option>
              <option value="Kesalahan Input Master Data">Kesalahan Input Master Data</option>
              <option value="Permintaan Manajemen / Nonaktif Katalog">Permintaan Manajemen / Nonaktif Katalog</option>
              <option value="Lainnya">Lainnya (Tuliskan di catatan bawah)</option>
            </select>
          </div>

          <div style="margin-bottom: 14px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
              Catatan / Rincian Alasan Penghapusan <span style="color: #dc2626;">*</span>
            </label>
            <textarea id="delInputNotes" class="form-control" placeholder="Wajib tuliskan rincian penjelasan mengapa produk ini dihapus (cth: keputusan manajemen per tgl..., discontinue, dsb)..." rows="3" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px; resize: vertical;" required></textarea>
            <div style="font-size: 11px; color: #64748b; margin-top: 4px;">* Catatan wajib diisi agar manajer atau auditor mengetahui rincian jelas alasan produk dihapus.</div>
          </div>

          <div style="background: #f1f5f9; border-radius: 6px; padding: 10px 12px; margin-bottom: 18px; font-size: 11px; color: #475569; line-height: 1.6;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span>Dicatat oleh:</span>
              <strong style="color: #0f172a;">${escapeHtml(fullAccountDisplay)}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 3px;">
              <span>Waktu Penghapusan:</span>
              <strong style="color: #0f172a;">Tercatat Otomatis di Riwayat Stok</strong>
            </div>
          </div>

          <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary js-close-del-reason" style="padding: 8px 16px; font-size: 13px;">Batal</button>
            <button type="submit" id="btnConfirmDeleteProduct" class="btn btn-danger" style="padding: 8px 16px; font-size: 13px; background: #dc2626; border-color: #dc2626; color: #fff; font-weight: 600;">
              Hapus Produk
            </button>
          </div>
        </form>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHtml);

  const modal = document.getElementById('prd-delete-reason-modal');
  modal.querySelectorAll('.js-close-del-reason').forEach(b => {
    b.addEventListener('click', () => modal.remove());
  });
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.remove();
  });

  const form = document.getElementById('formDeleteProductReason');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const reason = document.getElementById('delInputReason').value.trim();
    const notes = document.getElementById('delInputNotes').value.trim();

    if (!reason) {
      showProductAlert({
        type: 'warning',
        title: 'Pilih Alasan',
        message: 'Silakan pilih alasan penghapusan produk terlebih dahulu.',
        buttonText: 'Mengerti'
      });
      document.getElementById('delInputReason')?.focus();
      return;
    }

    if (!notes) {
      showProductAlert({
        type: 'warning',
        title: 'Catatan Wajib Diisi',
        message: 'Catatan rincian alasan penghapusan wajib diisi dengan jelas agar manajer atau auditor mengetahui pertanggungjawaban produk ini dihapus.',
        buttonText: 'Mengerti'
      });
      document.getElementById('delInputNotes')?.focus();
      return;
    }

    const btnSubmit = document.getElementById('btnConfirmDeleteProduct');
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Menyimpan...';

    fetch(`../api/products.php?id=${id}`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, reason, notes })
    })
    .then(res => res.json())
    .then(res => {
      modal.remove();
      if (res.status === 'success') {
        const d = res.data || {};
        showProductAlert({
          type: 'success',
          title: 'Produk Berhasil Dihapus',
          message: `
            <div style="text-align: left;">
              Produk <strong>${escapeHtml(name)}</strong> telah dinonaktifkan dari sistem.
              <div style="margin-top: 10px; padding: 10px 14px; background: #f8fafc; border: 1px solid var(--color-border-subtle); border-radius: 8px; font-size: 12px; line-height: 1.6;">
                <div><strong>Alasan:</strong> ${escapeHtml(d.deletion_reason || reason)}</div>
                ${d.deletion_notes ? `<div><strong>Catatan:</strong> ${escapeHtml(d.deletion_notes)}</div>` : ''}
                <div style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed var(--color-border-subtle); color: var(--color-text-muted);">
                  Tercatat oleh: <strong>${escapeHtml(fullAccountDisplay)}</strong> pada <strong>${escapeHtml(d.deleted_at || 'Baru saja')}</strong>
                  <div style="margin-top: 4px; color: #15803d; font-weight: 600;">✓ Berhasil dibukukan ke Riwayat Stok untuk transparansi seluruh tim admin.</div>
                </div>
              </div>
            </div>
          `,
          buttonText: 'Selesai',
          onClose: () => {
            if (typeof onSuccess === 'function') onSuccess();
          }
        });
      } else {
        const detailMsg = (res.message && res.message.toLowerCase().includes('admin_warehouse'))
          ? 'Anda tidak memiliki hak akses untuk menghapus produk. Fitur ini hanya dapat diakses oleh Admin Gudang.'
          : (res.message || 'Terjadi kesalahan saat menghapus produk.');
        showProductAlert({
          type: 'danger',
          title: 'Gagal Menghapus',
          message: `Gagal menghapus: ${escapeHtml(detailMsg)}`,
          buttonText: 'Tutup'
        });
      }
    })
    .catch(err => {
      modal.remove();
      showProductAlert({
        type: 'danger',
        title: 'Gagal Menghapus',
        message: `Gagal menghapus: Kesalahan jaringan atau server (${escapeHtml(err.message)})`,
        buttonText: 'Tutup'
      });
    });
  });
}

// ==========================================================================
// Modal Interaktif Kelola Lokasi Penyimpanan (Lihat, Tambah, Edit, Hapus)
// ==========================================================================
function showManageLocationsModal() {
  injectProductAlertStyles();
  document.getElementById('prd-manage-locations-modal')?.remove();

  const modalHtml = `
    <div id="prd-manage-locations-modal" class="prd-overlay" style="z-index: 10040;">
      <div class="prd-box" style="max-width: 720px; width: 94%; text-align: left; padding: 24px; max-height: 88vh; display: flex; flex-direction: column;">
        
        <!-- Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--color-border-subtle); padding-bottom: 14px; margin-bottom: 16px; flex-shrink: 0;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
              </svg>
            </div>
            <div>
              <h3 style="margin: 0; font-size: 17px; font-weight: 700; color: #0f172a;">Kelola Lokasi Penyimpanan</h3>
              <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 2px;">Daftar master lokasi penyimpanan gudang, status kapasitas produk, edit &amp; hapus lokasi kosong.</div>
            </div>
          </div>
          <button type="button" class="js-close-manage-loc" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--color-text-muted); line-height: 1;" aria-label="Tutup">&times;</button>
        </div>

        <!-- Toolbar: Pencarian & Tombol Tambah Baru -->
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; flex-shrink: 0;">
          <div style="position: relative; flex: 1; max-width: 340px;">
            <input type="text" id="manageLocSearch" class="form-control" placeholder="Cari kode, nama lokasi, atau area..." style="width: 100%; padding: 7px 12px 7px 32px; font-size: 12px; border: 1px solid var(--color-border); border-radius: 6px;">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--color-text-muted);">
              <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </div>
          <button type="button" id="btnManageAddNewLoc" class="btn btn-primary btn-sm" style="display: flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: 12px; font-weight: 600;">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Tambah Lokasi Baru</span>
          </button>
        </div>

        <!-- Table Container -->
        <div style="flex: 1; overflow-y: auto; border: 1px solid var(--color-border-subtle); border-radius: 8px; margin-bottom: 14px;">
          <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
            <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 2; border-bottom: 1px solid var(--color-border);">
              <tr>
                <th style="padding: 10px 12px; width: 40px; text-align: center; color: var(--color-text-muted);">No</th>
                <th style="padding: 10px 12px; color: var(--color-text-muted); width: 130px;">Kode Lokasi</th>
                <th style="padding: 10px 12px; color: var(--color-text-muted);">Nama Lokasi</th>
                <th style="padding: 10px 12px; color: var(--color-text-muted);">Area / Zona</th>
                <th style="padding: 10px 12px; text-align: center; color: var(--color-text-muted); width: 110px;">Produk</th>
                <th style="padding: 10px 12px; width: 130px; text-align: center; color: var(--color-text-muted);">Aksi</th>
              </tr>
            </thead>
            <tbody id="manageLocTableBody">
              <tr>
                <td colspan="6" style="padding: 28px; text-align: center; color: var(--color-text-muted);">Memuat daftar lokasi penyimpanan...</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; padding-top: 6px;">
          <div id="manageLocFooterCount" style="font-size: 12px; color: var(--color-text-muted);">Total: 0 lokasi penyimpanan</div>
          <button type="button" class="btn btn-secondary js-close-manage-loc" style="padding: 7px 18px; font-size: 12px;">Tutup</button>
        </div>

      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHtml);

  const modal = document.getElementById('prd-manage-locations-modal');
  modal.querySelectorAll('.js-close-manage-loc').forEach(b => {
    b.addEventListener('click', () => modal.remove());
  });
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.remove();
  });

  let allLocsData = [];

  function loadAndRenderLocations() {
    const tbody = document.getElementById('manageLocTableBody');
    if (!tbody) return;

    fetch('../api/locations.php')
      .then(res => res.json())
      .then(res => {
        if (res.status === 'success' && Array.isArray(res.data)) {
          allLocsData = res.data;
          renderLocationsTable();
        } else {
          tbody.innerHTML = `<tr><td colspan="6" style="padding: 24px; text-align: center; color: var(--color-danger);">Gagal memuat: ${escapeHtml(res.message || 'Error')}</td></tr>`;
        }
      })
      .catch(err => {
        tbody.innerHTML = `<tr><td colspan="6" style="padding: 24px; text-align: center; color: var(--color-danger);">Gagal memuat: ${escapeHtml(err.message)}</td></tr>`;
      });
  }

  function renderLocationsTable() {
    const tbody = document.getElementById('manageLocTableBody');
    const footerCount = document.getElementById('manageLocFooterCount');
    const searchVal = (document.getElementById('manageLocSearch')?.value || '').toLowerCase().trim();

    if (!tbody) return;

    const filtered = allLocsData.filter(loc => {
      if (!searchVal) return true;
      return (
        (loc.location_code || '').toLowerCase().includes(searchVal) ||
        (loc.location_name || '').toLowerCase().includes(searchVal) ||
        (loc.area || '').toLowerCase().includes(searchVal)
      );
    });

    if (footerCount) {
      footerCount.innerHTML = `Menampilkan <strong>${filtered.length}</strong> dari total <strong>${allLocsData.length}</strong> lokasi penyimpanan`;
    }

    if (filtered.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" style="padding: 32px; text-align: center; color: var(--color-text-muted);">
            ${searchVal ? 'Tidak ada lokasi penyimpanan yang sesuai dengan pencarian.' : 'Belum ada lokasi penyimpanan terdaftar.'}
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = '';
    filtered.forEach((loc, idx) => {
      const pCount = parseInt(loc.product_count, 10) || 0;
      const isZero = pCount === 0;
      const countBadge = isZero
        ? `<span class="badge" style="background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; font-weight:600; padding:2px 8px; font-size:11px;">0 (Kosong)</span>`
        : `<span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #7dd3fc; font-weight:700; padding:2px 8px; font-size:11px;">${pCount} Produk</span>`;

      const tr = document.createElement('tr');
      tr.style.borderBottom = '1px solid var(--color-border-subtle)';
      tr.innerHTML = `
        <td style="padding: 10px 12px; text-align: center; color: var(--color-text-muted);">${idx + 1}</td>
        <td style="padding: 10px 12px;"><span style="font-family: var(--font-mono); font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 2px 7px; border-radius: 4px; font-size: 11px;">${escapeHtml(loc.location_code)}</span></td>
        <td style="padding: 10px 12px;">
          <strong style="color: #0f172a;">${escapeHtml(loc.location_name)}</strong>
        </td>
        <td style="padding: 10px 12px; color: var(--color-text);">${escapeHtml(loc.area || '-')}</td>
        <td style="padding: 10px 12px; text-align: center;">${countBadge}</td>
        <td style="padding: 10px 12px; text-align: center;">
          <div style="display: flex; gap: 6px; justify-content: center;">
            <button type="button" class="btn btn-secondary btn-sm js-btn-edit-loc" style="padding: 3px 8px; font-size: 11px; display: flex; align-items: center; gap: 4px;" title="Edit Lokasi">
              <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
              <span>Edit</span>
            </button>
            <button type="button" class="btn btn-sm js-btn-del-loc" style="padding: 3px 8px; font-size: 11px; display: flex; align-items: center; gap: 4px; background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5;" title="Hapus Lokasi">
              <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
              <span>Hapus</span>
            </button>
          </div>
        </td>
      `;

      // Event Edit
      tr.querySelector('.js-btn-edit-loc').addEventListener('click', () => {
        showEditStorageLocationModal(loc, () => {
          loadAndRenderLocations();
          loadProductsFromDb();
          loadLocationsDropdown();
        });
      });

      // Event Hapus
      tr.querySelector('.js-btn-del-loc').addEventListener('click', () => {
        if (pCount > 0) {
          showProductAlert({
            type: 'warning',
            title: 'Lokasi Masih Menampung Produk',
            message: `Lokasi penyimpanan "${escapeHtml(loc.location_name)}" tidak dapat dihapus karena saat ini masih menampung <strong>${pCount} produk aktif</strong>.<br><br>Harap pindahkan produk-produk tersebut ke lokasi lain terlebih dahulu agar tidak terjadi kehilangan jejak fisik barang.`,
            buttonText: 'Mengerti'
          });
          return;
        }

        showProductConfirm({
          title: 'Konfirmasi Hapus Lokasi',
          message: `Apakah Anda yakin ingin menghapus lokasi penyimpanan <strong>"${escapeHtml(loc.location_name)}" (${escapeHtml(loc.location_code)})</strong> dari master data gudang?`,
          confirmText: 'Hapus Lokasi',
          cancelText: 'Batal',
          onConfirm: () => {
            fetch('../api/locations.php', {
              method: 'DELETE',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ location_id: loc.location_id })
            })
            .then(res => res.json())
            .then(delRes => {
              if (delRes.status === 'success') {
                showProductAlert({
                  type: 'success',
                  title: 'Lokasi Terhapus',
                  message: delRes.message || `Lokasi penyimpanan "${escapeHtml(loc.location_name)}" berhasil dihapus.`,
                  buttonText: 'Selesai'
                });
                loadAndRenderLocations();
                loadProductsFromDb();
                loadLocationsDropdown();
              } else {
                showProductAlert({
                  type: 'danger',
                  title: 'Gagal Menghapus',
                  message: delRes.message || 'Terjadi kesalahan saat menghapus lokasi penyimpanan.',
                  buttonText: 'Tutup'
                });
              }
            })
            .catch(err => {
              showProductAlert({
                type: 'danger',
                title: 'Kesalahan Sistem',
                message: `Gagal menghubungi server: ${escapeHtml(err.message)}`,
                buttonText: 'Tutup'
              });
            });
          }
        });
      });

      tbody.appendChild(tr);
    });
  }

  // Search input handler
  const searchInput = document.getElementById('manageLocSearch');
  if (searchInput) {
    searchInput.addEventListener('input', renderLocationsTable);
  }

  // Button Tambah Baru dari dalam Modal Kelola
  const btnAddNew = document.getElementById('btnManageAddNewLoc');
  if (btnAddNew) {
    btnAddNew.addEventListener('click', () => {
      showAddStorageLocationModal(() => {
        loadAndRenderLocations();
        loadLocationsDropdown();
      });
    });
  }

  loadAndRenderLocations();
}

// ==========================================================================
// Modal Interaktif Edit Lokasi Penyimpanan
// ==========================================================================
function showEditStorageLocationModal(locData, onUpdated = null) {
  injectProductAlertStyles();
  document.getElementById('prd-edit-location-modal')?.remove();

  const modalHtml = `
    <div id="prd-edit-location-modal" class="prd-overlay" style="z-index: 10060;">
      <div class="prd-box" style="max-width: 480px; text-align: left; padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--color-border-subtle); padding-bottom: 12px; margin-bottom: 16px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 38px; height: 38px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </div>
            <div>
              <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Edit Lokasi Penyimpanan</h3>
              <div style="font-size: 11px; color: var(--color-text-muted);">Perbarui kode, nama lokasi, dan area penempatan</div>
            </div>
          </div>
          <button type="button" class="js-close-edit-loc-modal" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--color-text-muted); line-height: 1;">&times;</button>
        </div>

        <form id="formEditStorageLocation">
          <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: 12px; margin-bottom: 14px;">
            <div>
              <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                Kode Lokasi <span style="color: #dc2626;">*</span>
              </label>
              <input type="text" id="editLocInputCode" class="form-control" value="${escapeHtml(locData.location_code)}" placeholder="Contoh: RAK-A1" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px; text-transform: uppercase;" required>
            </div>
            <div>
              <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                Nama Lokasi <span style="color: #dc2626;">*</span>
              </label>
              <input type="text" id="editLocInputName" class="form-control" value="${escapeHtml(locData.location_name)}" placeholder="Contoh: Rak Elektronik A1" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px;" required>
            </div>
          </div>

          <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
              Area / Zona Gudang (Opsional)
            </label>
            <input type="text" id="editLocInputArea" class="form-control" value="${escapeHtml(locData.area || '')}" placeholder="Contoh: Gudang Utama, Lantai 1, Area Karantina" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px;">
          </div>

          <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary js-close-edit-loc-modal" style="padding: 8px 16px; font-size: 13px;">Batal</button>
            <button type="submit" id="btnSubmitEditLocation" class="btn btn-primary" style="padding: 8px 18px; font-size: 13px; font-weight: 600;">
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHtml);

  const modal = document.getElementById('prd-edit-location-modal');
  modal.querySelectorAll('.js-close-edit-loc-modal').forEach(b => {
    b.addEventListener('click', () => modal.remove());
  });
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.remove();
  });

  const form = document.getElementById('formEditStorageLocation');
  const codeInput = document.getElementById('editLocInputCode');
  const nameInput = document.getElementById('editLocInputName');
  const areaInput = document.getElementById('editLocInputArea');
  const submitBtn = document.getElementById('btnSubmitEditLocation');

  codeInput.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const code = codeInput.value.trim().toUpperCase();
    const name = nameInput.value.trim();
    const area = areaInput.value.trim();

    if (!code || !name) {
      showProductAlert({
        type: 'warning',
        title: 'Data Belum Lengkap',
        message: 'Kode Lokasi dan Nama Lokasi wajib diisi.',
        buttonText: 'Periksa Kembali'
      });
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan...';

    fetch('../api/locations.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        location_id: locData.location_id,
        location_code: code,
        location_name: name,
        area: area
      })
    })
    .then(async res => {
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.status === 'success') {
        modal.remove();
        showProductAlert({
          type: 'success',
          title: 'Perubahan Tersimpan',
          message: data.message || `Data lokasi "${name}" berhasil diperbarui.`,
          buttonText: 'Selesai'
        });
        if (typeof onUpdated === 'function') {
          onUpdated(data.data);
        }
      } else {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Simpan Perubahan';
        showProductAlert({
          type: 'warning',
          title: 'Gagal Menyimpan',
          message: data.message || 'Terjadi kesalahan saat memperbarui lokasi penyimpanan.',
          buttonText: 'Coba Lagi'
        });
      }
    })
    .catch(err => {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Simpan Perubahan';
      showProductAlert({
        type: 'danger',
        title: 'Kesalahan Sistem',
        message: `Gagal menghubungi server: ${escapeHtml(err.message)}`,
        buttonText: 'Tutup'
      });
    });
  });

  setTimeout(() => nameInput.focus(), 100);
}

// ==========================================================================
// Modal Interaktif Tambah Lokasi Penyimpanan Baru
// ==========================================================================
function showAddStorageLocationModal(onSuccess = null) {
  injectProductAlertStyles();
  document.getElementById('prd-add-location-modal')?.remove();

  const modalHtml = `
    <div id="prd-add-location-modal" class="prd-overlay" style="z-index: 10050;">
      <div class="prd-box" style="max-width: 480px; text-align: left; padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--color-border-subtle); padding-bottom: 12px; margin-bottom: 16px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 38px; height: 38px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
              </svg>
            </div>
            <div>
              <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Tambah Lokasi Baru</h3>
              <div style="font-size: 11px; color: var(--color-text-muted);">Pendaftaran Master Lokasi Penyimpanan Gudang</div>
            </div>
          </div>
          <button type="button" class="js-close-loc-modal" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--color-text-muted); line-height: 1;">&times;</button>
        </div>

        <form id="formAddStorageLocation">
          <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: 12px; margin-bottom: 14px;">
            <div>
              <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                Kode Lokasi <span style="color: #dc2626;">*</span>
              </label>
              <input type="text" id="locInputCode" class="form-control" placeholder="Contoh: RAK-A1" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px; text-transform: uppercase;" required>
              <div style="font-size: 10px; color: var(--color-text-muted); margin-top: 3px;">Kode singkat (RAK-A1, GUD-01, dll)</div>
            </div>
            <div>
              <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                Nama Lokasi <span style="color: #dc2626;">*</span>
              </label>
              <input type="text" id="locInputName" class="form-control" placeholder="Contoh: Rak Elektronik A1" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px;" required>
              <div style="font-size: 10px; color: var(--color-text-muted); margin-top: 3px;">Nama yang muncul di dropdown</div>
            </div>
          </div>

          <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
              Area / Zona Gudang (Opsional)
            </label>
            <input type="text" id="locInputArea" class="form-control" placeholder="Contoh: Gudang Utama, Lantai 1, Area Karantina" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px;">
          </div>

          <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary js-close-loc-modal" style="padding: 8px 16px; font-size: 13px;">Batal</button>
            <button type="submit" id="btnSubmitAddLocation" class="btn btn-primary" style="padding: 8px 18px; font-size: 13px; font-weight: 600;">
              Simpan Lokasi
            </button>
          </div>
        </form>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHtml);

  const modal = document.getElementById('prd-add-location-modal');
  modal.querySelectorAll('.js-close-loc-modal').forEach(b => {
    b.addEventListener('click', () => modal.remove());
  });
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.remove();
  });

  const form = document.getElementById('formAddStorageLocation');
  const codeInput = document.getElementById('locInputCode');
  const nameInput = document.getElementById('locInputName');
  const areaInput = document.getElementById('locInputArea');
  const submitBtn = document.getElementById('btnSubmitAddLocation');

  codeInput.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const code = codeInput.value.trim().toUpperCase();
    const name = nameInput.value.trim();
    const area = areaInput.value.trim();

    if (!code || !name) {
      showProductAlert({
        type: 'warning',
        title: 'Data Belum Lengkap',
        message: 'Kode Lokasi dan Nama Lokasi wajib diisi.',
        buttonText: 'Periksa Kembali'
      });
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan...';

    fetch('../api/locations.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        location_code: code,
        location_name: name,
        area: area
      })
    })
    .then(async res => {
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.status === 'success') {
        modal.remove();
        showProductAlert({
          type: 'success',
          title: 'Lokasi Tersimpan',
          message: data.message || `Lokasi penyimpanan "${name}" (${code}) berhasil didaftarkan ke gudang.`,
          buttonText: 'Selesai'
        });
        if (typeof onSuccess === 'function') {
          onSuccess(data.data);
        }
      } else {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Simpan Lokasi';
        showProductAlert({
          type: 'warning',
          title: 'Gagal Menyimpan',
          message: data.message || 'Terjadi kesalahan saat menyimpan lokasi penyimpanan.',
          buttonText: 'Coba Lagi'
        });
      }
    })
    .catch(err => {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Simpan Lokasi';
      showProductAlert({
        type: 'danger',
        title: 'Kesalahan Sistem',
        message: `Gagal menghubungi server: ${escapeHtml(err.message)}`,
        buttonText: 'Tutup'
      });
    });
  });

  setTimeout(() => codeInput.focus(), 100);
}

// ==========================================================================
// Popup Notifikasi & Konfirmasi Interaktif (pengganti alert & confirm bawaan browser)
// ==========================================================================
function showProductConfirm({ title = 'Konfirmasi Hapus', message = '', confirmText = 'Iya', cancelText = 'Batal', onConfirm = null, onCancel = null }) {
  injectProductAlertStyles();
  document.getElementById('prd-confirm')?.remove();

  const iconTrash = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>';

  const overlay = document.createElement('div');
  overlay.id = 'prd-confirm';
  overlay.className = 'prd-overlay';
  overlay.innerHTML = `
    <div class="prd-box prd-danger" role="alertdialog" aria-modal="true">
      <div class="prd-icon">${iconTrash}</div>
      <div class="prd-title">${title}</div>
      <div class="prd-msg">${message}</div>
      <div class="prd-actions" style="display: flex; gap: 12px; margin-top: 22px;">
        <button type="button" class="prd-btn prd-btn-cancel" style="flex: 1; background: #f1f5f9 !important; color: #475569 !important; border: 1px solid #cbd5e1 !important; padding: 10px 16px; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-block !important; visibility: visible !important;">${cancelText}</button>
        <button type="button" class="prd-btn prd-btn-confirm" style="flex: 1; background: #dc2626 !important; color: #ffffff !important; border: none !important; padding: 10px 16px; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-block !important; visibility: visible !important;">${confirmText}</button>
      </div>
    </div>`;
  document.body.appendChild(overlay);

  const close = () => {
    overlay.classList.add('prd-hide');
    setTimeout(() => overlay.remove(), 200);
    document.removeEventListener('keydown', onKey);
  };
  const onKey = (e) => {
    if (e.key === 'Escape') {
      close();
      if (onCancel) onCancel();
    }
  };

  overlay.querySelector('.prd-btn-cancel').addEventListener('click', () => {
    close();
    if (onCancel) onCancel();
  });
  overlay.querySelector('.prd-btn-confirm').addEventListener('click', () => {
    close();
    if (onConfirm) onConfirm();
  });
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) {
      close();
      if (onCancel) onCancel();
    }
  });
  document.addEventListener('keydown', onKey);
  setTimeout(() => overlay.querySelector('.prd-btn-cancel')?.focus(), 50);
}

function showProductAlert({ type = 'success', title = '', message = '', buttonText = 'OK', onClose = null }) {
  injectProductAlertStyles();
  document.getElementById('prd-alert')?.remove();

  const icons = {
    success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    danger:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
    warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="8" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
  };

  const overlay = document.createElement('div');
  overlay.id = 'prd-alert';
  overlay.className = 'prd-overlay';
  overlay.innerHTML = `
    <div class="prd-box prd-${type}" role="alertdialog" aria-modal="true">
      <div class="prd-icon">${icons[type] || icons.success}</div>
      <div class="prd-title">${title}</div>
      <div class="prd-msg">${message}</div>
      <button type="button" class="prd-btn prd-btn-ok" style="margin-top: 22px; width: 100%;">${buttonText}</button>
    </div>`;
  document.body.appendChild(overlay);

  const close = () => {
    overlay.classList.add('prd-hide');
    setTimeout(() => { overlay.remove(); if (onClose) onClose(); }, 200);
    document.removeEventListener('keydown', onKey);
  };
  const onKey = (e) => {
    if (e.key === 'Enter' || e.key === 'Escape') close();
  };

  overlay.querySelector('.prd-btn-ok').addEventListener('click', close);
  overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
  document.addEventListener('keydown', onKey);
  setTimeout(() => overlay.querySelector('.prd-btn-ok')?.focus(), 50);
}

function injectProductAlertStyles() {
  if (document.getElementById('prd-alert-style')) return;
  const s = document.createElement('style');
  s.id = 'prd-alert-style';
  s.textContent = `
    .prd-overlay{position:fixed;inset:0;z-index:10050;display:flex;align-items:center;justify-content:center;
      background:rgba(15,23,42,.45);backdrop-filter:blur(2px);padding:16px;animation:prdFade .2s ease}
    .prd-overlay.prd-hide{animation:prdFadeOut .2s ease forwards}
    .prd-box{background:#fff;border-radius:16px;width:100%;max-width:390px;padding:28px 24px 22px;text-align:center;
      box-shadow:0 20px 50px rgba(0,0,0,.25);animation:prdPop .3s cubic-bezier(.34,1.56,.64,1)}
    .prd-icon{width:64px;height:64px;border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center}
    .prd-icon svg{width:32px;height:32px}
    .prd-success .prd-icon{background:#dcfce7;color:#16a34a}
    .prd-danger  .prd-icon{background:#fee2e2;color:#dc2626}
    .prd-warning .prd-icon{background:#fef3c7;color:#d97706}
    .prd-title{font-size:18px;font-weight:700;color:#0f172a;margin-bottom:8px}
    .prd-msg{font-size:14px;color:#475569;line-height:1.55}
    .prd-btn{padding:10px 16px;border:none;border-radius:10px;font-weight:600;font-size:14px;
      cursor:pointer;transition:transform .1s, filter .15s}
    .prd-btn:hover{filter:brightness(1.08)} .prd-btn:active{transform:scale(.98)}
    .prd-success .prd-btn-ok{background:#16a34a;color:#fff}
    .prd-danger  .prd-btn-ok{background:#dc2626;color:#fff}
    .prd-warning .prd-btn-ok{background:#d97706;color:#fff}
    @keyframes prdFade{from{opacity:0}to{opacity:1}}
    @keyframes prdFadeOut{to{opacity:0}}
    @keyframes prdPop{from{opacity:0;transform:scale(.85) translateY(10px)}to{opacity:1;transform:none}}
  `;
  document.head.appendChild(s);
}

function showErrorInTable(msg) {
  const tbody = document.querySelector('.table tbody');
  if (tbody) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" style="text-align: center; padding: 24px; color: var(--color-danger);">
          ⚠️ ${escapeHtml(msg)}
        </td>
      </tr>
    `;
  }
}

function formatRupiah(number) {
  const val = parseFloat(number) || 0;
  return 'Rp' + val.toLocaleString('id-ID');
}

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
