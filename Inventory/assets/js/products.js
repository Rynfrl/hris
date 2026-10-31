/**
 * Handler Dinamis Daftar Produk Terhubung ke Database MySQL
 * Proyek: Inventoria (database hris)
 */

document.addEventListener('DOMContentLoaded', function() {
  initProductPage();
});

let currentProducts = [];

function initProductPage() {
  loadCategoriesDropdown();
  loadProductsFromDb();

  const tbody = document.querySelector('.table tbody');
  if (tbody) {
    tbody.addEventListener('click', event => {
      const button = event.target.closest('.js-delete-product');
      if (button) {
        window.deleteProduct(button.dataset.productId, button.dataset.productName);
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

// 2. Ambil Produk dari MySQL Database
function loadProductsFromDb() {
  const searchInput = document.querySelector('.filter-input');
  const catSelect = document.querySelector('select[aria-label="Filter Kategori"]');
  const statusSelect = document.querySelector('select[aria-label="Filter Status Stok"]');
  const tbody = document.querySelector('.table tbody');
  const countDisplay = document.querySelector('.toolbar + div, .toolbar-filters + div');

  const search = searchInput ? encodeURIComponent(searchInput.value.trim()) : '';
  const category = catSelect ? encodeURIComponent(catSelect.value) : '';
  const status = statusSelect ? encodeURIComponent(statusSelect.value) : '';

  let url = `../api/products.php?search=${search}&category=${category}&status=${status}`;

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
        renderProductsTable(currentProducts);
        
        if (countDisplay) {
          countDisplay.innerHTML = `Menampilkan <strong>${currentProducts.length}</strong> produk di database MySQL <code>hris</code>`;
        }
      } else {
        showErrorInTable(res.message || 'Gagal memuat data produk');
      }
    })
    .catch(err => {
      showErrorInTable('Koneksi ke backend database gagal: ' + err.message);
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

    // Badge status format
    let badgeClass = 'badge-available';
    let badgeLabel = 'Tersedia';
    if (p.status === 'low') {
      badgeClass = 'badge-low';
      badgeLabel = 'Hampir habis';
    } else if (p.status === 'out' || parseInt(p.current_stock) <= 0) {
      badgeClass = 'badge-out';
      badgeLabel = 'Habis';
    }

    const buyPrice = formatRupiah(p.purchase_price);
    const sellPrice = formatRupiah(p.selling_price);

    tr.innerHTML = `
      <td><span class="table-sku">${escapeHtml(p.sku)}</span></td>
      <td>
        <strong>${escapeHtml(p.name)}</strong>
        ${p.location ? `<div style="font-size: 11px; color: var(--color-text-muted);">${escapeHtml(p.location)}</div>` : ''}
      </td>
      <td>${escapeHtml(p.category_name || '-')}</td>
      <td style="text-align: right;">${buyPrice}</td>
      <td style="text-align: right;">${sellPrice}</td>
      <td style="text-align: center; font-weight: bold; ${p.status === 'out' ? 'color: var(--color-danger);' : (p.status === 'low' ? 'color: var(--color-warning);' : '')}">
        ${p.current_stock} ${escapeHtml(p.unit || 'unit')}
      </td>
      <td><span class="badge ${badgeClass}"><span class="badge-dot"></span>${badgeLabel}</span></td>
      <td style="text-align: right;">
        <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
          <a href="product-detail.html?id=${p.id}" class="btn btn-secondary btn-sm" title="Lihat Detail Produk">Lihat</a>
          <a href="product-form.html?id=${p.id}" class="btn btn-secondary btn-sm" title="Edit Data">Edit</a>
          <button type="button" class="btn btn-secondary btn-sm js-delete-product" data-product-id="${p.id}" data-product-name="${escapeHtml(p.name)}" title="Nonaktifkan Produk" style="color: var(--color-danger);">
            Hapus
          </button>
        </div>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

// 4. Hapus Produk dari Database
window.deleteProduct = function(id, name) {
  if (!confirm(`Apakah Anda yakin ingin menonaktifkan produk "${name}"? Produk tetap disimpan untuk menjaga riwayat transaksi.`)) {
    return;
  }

  fetch(`../api/products.php?id=${id}`, {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json' }
  })
  .then(res => res.json())
  .then(res => {
    if (res.status === 'success') {
      alert(res.message || 'Produk berhasil dinonaktifkan.');
      loadProductsFromDb();
    } else {
      alert('Gagal menghapus: ' + (res.message || 'Terjadi kesalahan'));
    }
  })
  .catch(err => {
    alert('Kesalahan jaringan: ' + err.message);
  });
};

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

function escapeHtml(str) {
  if (!str) return '';
  return str.toString()
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
