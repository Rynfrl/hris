/**
 * Handler Transaksi Mutasi Stok (Stok Masuk, Stok Keluar, Riwayat Stok)
 * Proyek: Inventoria (database hris)
 */

document.addEventListener('DOMContentLoaded', function() {
  const path = window.location.pathname;

  if (path.includes('stock-in.html')) {
    initStockInPage();
  } else if (path.includes('stock-out.html')) {
    initStockOutPage();
  } else if (path.includes('stock-history.html')) {
    initStockHistoryPage();
  }
});

let allProductsList = [];

// ==========================================================================
// 1. HALAMAN STOK MASUK (Inbound / Purchasing)
// ==========================================================================
function initStockInPage() {
  const urlParams = new URLSearchParams(window.location.search);
  const preSelectedId = urlParams.get('product_id');

  // Set tanggal hari ini & nomor referensi acak
  const dateInput = document.getElementById('transaction_date');
  if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

  const refInput = document.getElementById('reference_number');
  if (refInput) refInput.value = 'PO-' + new Date().getFullYear() + '-' + Math.floor(100 + Math.random() * 900);

  // Muat dropdown produk & supplier dari database
  Promise.all([loadProductsForSelect('product_select'), loadSuppliersForSelect('supplier')])
    .then(() => {
      if (preSelectedId) {
        const prodSelect = document.getElementById('product_select');
        if (prodSelect) prodSelect.value = preSelectedId;
      }
    });

  // Handle Form Submit
  const form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      submitStockTransaction('in');
    });
  }
}

// ==========================================================================
// 2. HALAMAN STOK KELUAR (Outbound / Sales)
// ==========================================================================
function initStockOutPage() {
  const dateInput = document.getElementById('transaction_date');
  if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

  const destInput = document.getElementById('destination');
  if (destInput) destInput.value = 'REQ-' + new Date().getFullYear() + '-' + Math.floor(100 + Math.random() * 900);

  // Muat dropdown produk
  loadProductsForSelect('product_select').then(() => {
    const prodSelect = document.getElementById('product_select');
    const qtyInput = document.getElementById('quantity');

    if (prodSelect && qtyInput) {
      prodSelect.addEventListener('change', function() {
        const selected = allProductsList.find(p => p.id == this.value);
        if (selected) {
          qtyInput.max = selected.current_stock;
          const hint = qtyInput.parentElement.querySelector('.form-hint');
          if (hint) {
            hint.textContent = `Sisa stok tersedia di gudang: ${selected.current_stock} ${selected.unit}. Maksimal pengeluaran: ${selected.current_stock}`;
          }
        }
      });
    }
  });

  // Handle Form Submit
  const form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      submitStockTransaction('out');
    });
  }
}

// Helper Submit Transaksi Stok (In / Out)
function submitStockTransaction(type) {
  const submitBtn = document.querySelector('button[type="submit"]');
  const originalHtml = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span>Menyimpan ke MySQL...</span>';

  const productId = document.getElementById('product_select')?.value;
  const quantity = document.getElementById('quantity')?.value;
  const date = document.getElementById('transaction_date')?.value;
  const notes = document.getElementById('notes')?.value;
  const supplierId = document.getElementById('supplier')?.value;

  const payload = {
    type: type,
    product_id: productId,
    quantity: quantity,
    date: date,
    notes: notes,
    supplier_id: supplierId
  };

  fetch('../api/stock.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(res => {
    if (res.status === 'success') {
      alert(res.message);
      window.location.href = 'stock-history.html';
    } else {
      alert('Gagal: ' + (res.message || 'Terjadi kesalahan sistem'));
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalHtml;
    }
  })
  .catch(err => {
    alert('Kesalahan jaringan: ' + err.message);
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalHtml;
  });
}

// ==========================================================================
// 3. HALAMAN RIWAYAT STOK (Stock History)
// ==========================================================================
function initStockHistoryPage() {
  loadStockHistoryFromDb();

  const typeFilter = document.querySelector('select[aria-label="Filter Jenis Transaksi"]');
  if (typeFilter) {
    typeFilter.addEventListener('change', function() {
      loadStockHistoryFromDb(this.value);
    });
  }
}

function loadStockHistoryFromDb(typeFilter = '') {
  const tbody = document.querySelector('.table tbody');
  const counter = document.querySelector('.toolbar-filters + div');

  if (tbody) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align: center; padding: 24px; color: var(--color-text-muted);">
          Memuat riwayat transaksi dari database...
        </td>
      </tr>
    `;
  }

  let url = '../api/stock.php?limit=50';
  if (typeFilter) url += `&type=${typeFilter}`;

  fetch(url)
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success') {
        renderStockHistoryTable(res.data);
        if (counter) {
          counter.innerHTML = `Total <strong>${res.count} Transaksi di MySQL</strong>`;
        }
      }
    })
    .catch(err => {
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--color-danger); padding: 20px;">Gagal memuat: ${err.message}</td></tr>`;
      }
    });
}

function renderStockHistoryTable(movements) {
  const tbody = document.querySelector('.table tbody');
  if (!tbody) return;

  if (!movements || movements.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align: center; padding: 24px; color: var(--color-text-muted);">
          Belum ada data pergerakan stok.
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = '';
  movements.forEach(m => {
    const isIn = m.movement_type === 'IN';
    const sign = isIn ? '+' : '-';
    const color = isIn ? 'var(--color-success)' : 'var(--color-danger)';
    const badgeClass = isIn ? 'badge-available' : 'badge-low';
    const badgeText = isIn ? 'Stok Masuk' : 'Stok Keluar';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${m.movement_date}</td>
      <td>
        <strong>${escapeHtml(m.product_name)}</strong>
        <br><span class="table-sku">${escapeHtml(m.sku)}</span>
      </td>
      <td><span class="badge ${badgeClass}"><span class="badge-dot"></span>${badgeText}</span></td>
      <td style="text-align: right; font-weight: bold; color: ${color};">
        ${sign}${m.quantity} ${escapeHtml(m.unit || 'unit')}
      </td>
      <td style="text-align: right; font-size: 13px;">
        ${formatRupiah(m.price)}
      </td>
      <td>
        <span style="font-family: var(--font-mono); font-weight: 600;">${escapeHtml(m.ref_number)}</span>
        ${m.partner_name ? `<div style="font-size: 11px; color: var(--color-text-muted);">${escapeHtml(m.partner_name)}</div>` : ''}
      </td>
      <td>Petugas Sistem</td>
    `;
    tbody.appendChild(tr);
  });
}

// --------------------------------------------------------------------------
// Util Helpers
// --------------------------------------------------------------------------
function loadProductsForSelect(selectId) {
  return fetch('../api/products.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        allProductsList = res.data;
        const select = document.getElementById(selectId);
        if (select) {
          select.innerHTML = '<option value="">-- Pilih Barang dari Katalog --</option>';
          res.data.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `${p.sku} - ${p.name} (Sisa Stok: ${p.current_stock} ${p.unit})`;
            select.appendChild(opt);
          });
        }
      }
    });
}

function loadSuppliersForSelect(selectId) {
  return fetch('../api/suppliers.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        const select = document.getElementById(selectId);
        if (select) {
          select.innerHTML = '<option value="">-- Pilih Supplier --</option>';
          res.data.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = `${s.code} - ${s.name}`;
            select.appendChild(opt);
          });
        }
      }
    });
}

function formatRupiah(val) {
  const num = parseFloat(val) || 0;
  return 'Rp' + num.toLocaleString('id-ID');
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
