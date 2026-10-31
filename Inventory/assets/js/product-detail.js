/**
 * Handler Halaman Detail Produk Dinamis dari Database MySQL
 * Proyek: Inventoria (database hris)
 */

document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const productId = urlParams.get('id') || '1'; // Default 1 jika tidak ada parameter

  loadProductDetail(productId);
});

function loadProductDetail(id) {
  fetch(`../api/products.php?id=${id}`)
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && res.data) {
        renderProductData(res.data);
      } else {
        alert('Produk tidak ditemukan');
        window.location.href = 'products.html';
      }
    })
    .catch(err => {
      console.error('Gagal mengambil detail produk:', err);
    });
}

function renderProductData(p) {
  // Breadcrumb
  const breadcrumbLast = document.querySelector('.breadcrumb span:last-child');
  if (breadcrumbLast) breadcrumbLast.textContent = p.name;

  // Header
  const titleEl = document.querySelector('.page-title-group h1');
  if (titleEl) titleEl.textContent = p.name;

  const skuBadge = document.querySelector('.page-title-group .table-sku');
  if (skuBadge) skuBadge.textContent = `SKU: ${p.sku}`;

  const statusBadge = document.querySelector('.page-title-group .badge');
  if (statusBadge) {
    let bClass = 'badge-available';
    let bText = 'Tersedia';
    if (p.status === 'low') {
      bClass = 'badge-low';
      bText = 'Hampir habis';
    } else if (p.status === 'out' || parseInt(p.current_stock) <= 0) {
      bClass = 'badge-out';
      bText = 'Habis';
    }
    statusBadge.className = `badge ${bClass}`;
    statusBadge.innerHTML = `<span class="badge-dot"></span>${bText}`;
  }

  // Update action buttons links to include id
  const editBtn = document.querySelector('.page-actions a[href*="product-form.html"]');
  if (editBtn) editBtn.href = `product-form.html?id=${p.id}`;

  const stockInBtn = document.querySelector('.page-actions a[href*="stock-in.html"]');
  if (stockInBtn) stockInBtn.href = `stock-in.html?product_id=${p.id}`;

  const stockOutBtn = document.querySelector('.page-actions a[href*="stock-out.html"]');
  if (stockOutBtn) stockOutBtn.href = `stock-out.html?product_id=${p.id}`;

  // Specs
  const specValues = document.querySelectorAll('.detail-specs-grid .spec-value');
  if (specValues.length >= 7) {
    specValues[0].textContent = p.sku;
    specValues[1].textContent = p.category_name || '-';
    // Supplier
    specValues[3].textContent = p.location || 'Gudang Utama';
    specValues[4].textContent = formatRupiah(p.purchase_price) + ' / ' + p.unit;
    specValues[5].textContent = formatRupiah(p.selling_price) + ' / ' + p.unit;
    
    // Margin profit
    const margin = (parseFloat(p.selling_price) - parseFloat(p.purchase_price));
    const marginPct = (parseFloat(p.purchase_price) > 0) ? Math.round((margin / parseFloat(p.purchase_price)) * 100) : 0;
    specValues[6].textContent = `${formatRupiah(margin)} (+${marginPct}%)`;
  }

  // Deskripsi
  const descP = document.querySelector('.card-body p');
  if (descP) descP.textContent = p.description || 'Tidak ada deskripsi tambahan.';

  // Riwayat mutasi produk ini
  const tbody = document.querySelector('.table-container .table tbody');
  if (tbody && Array.isArray(p.stock_history)) {
    if (p.stock_history.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--color-text-muted);">Belum ada riwayat transaksi untuk produk ini.</td></tr>`;
    } else {
      tbody.innerHTML = '';
      p.stock_history.forEach(h => {
        const isIn = h.type === 'IN';
        const sign = isIn ? '+' : '-';
        const color = isIn ? 'var(--color-success)' : 'var(--color-danger)';
        const badgeClass = isIn ? 'badge-available' : 'badge-low';
        const badgeText = isIn ? 'Stok Masuk' : 'Stok Keluar';

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${h.date}</td>
          <td><span class="badge ${badgeClass}"><span class="badge-dot"></span>${badgeText}</span></td>
          <td style="text-align: right; color: ${color}; font-weight: bold;">${sign}${h.quantity} ${p.unit}</td>
          <td style="text-align: right;">${formatRupiah(h.price)}</td>
          <td><span style="font-family: var(--font-mono);">${escapeHtml(h.ref_no)}</span></td>
          <td>${escapeHtml(h.partner_name || 'Petugas Gudang')}</td>
        `;
        tbody.appendChild(tr);
      });
    }
  }
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
