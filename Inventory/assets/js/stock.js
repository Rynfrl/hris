/**
 * Handler Transaksi Mutasi Stok (Stok Masuk, Stok Keluar, Riwayat Stok)
 * Proyek: Inventoria (database hris)
 */

document.addEventListener('DOMContentLoaded', function() {
  const path = window.location.pathname;

  // Validasi ditangani popup interaktif (bukan tooltip bawaan browser)
  if (path.includes('stock-in.html') || path.includes('stock-out.html')) {
    const f = document.querySelector('form');
    if (f) f.noValidate = true;
  }

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

  // Set tanggal hari ini
  const dateInput = document.getElementById('transaction_date');
  if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

  // Auto-generate No. Surat Jalan sebagai placeholder; isi value jika ada dari URL (purchasing/referensi eksternal)
  const sjInput = document.getElementById('no_surat_jalan');
  if (sjInput) {
    const now   = new Date();
    const ymd   = now.getFullYear().toString()
                + String(now.getMonth() + 1).padStart(2, '0')
                + String(now.getDate()).padStart(2, '0');
    const hex   = Math.floor(Math.random() * 0xFFFFFF).toString(16).padStart(6, '0');
    const autoSj = `SJ-${ymd}-${hex}`;
    // Cek URL param: no_surat_jalan atau reference_no (dari modul purchasing/lain)
    const extRef = urlParams.get('no_surat_jalan') || urlParams.get('reference_no') || '';
    if (extRef) {
      sjInput.value       = extRef;
      sjInput.placeholder = extRef;
    } else {
      sjInput.value       = '';
      sjInput.placeholder = autoSj;
    }
  }

  // Render kartu ringkasan awal (empty state)
  updateStockSummaryCard('in');

  // Muat dropdown produk & supplier dari database
  Promise.all([loadProductsForSelect('product_select', true), loadSuppliersForSelect('supplier')])
    .then(() => {
      const prodSelect = document.getElementById('product_select');
      const locInput = document.getElementById('location');
      const statusSelect = document.getElementById('stock_status');

      if (prodSelect) {
        prodSelect.addEventListener('change', function() {
          const selected = allProductsList.find(p => p.id == this.value);
          checkProductCompletenessUI(selected, 'in');
          if (selected) {
            if (locInput) {
              locInput.value = selected.location || '';
            }
            if (statusSelect) {
              // Jika produk saat ini berstatus non-aktif / draft, arahkan ke draft, selainnya aktif
              statusSelect.value = (selected.is_active == 0 || selected.is_active === '0') ? 'draft' : 'aktif';
            }
          } else {
            if (locInput) locInput.value = '';
          }
          updateStockSummaryCard('in');
        });
      }

      const qtyInput = document.getElementById('quantity');
      if (qtyInput) {
        qtyInput.addEventListener('input', () => updateStockSummaryCard('in'));
        qtyInput.addEventListener('change', () => updateStockSummaryCard('in'));
      }

      if (statusSelect) {
        statusSelect.addEventListener('change', () => updateStockSummaryCard('in'));
      }

      if (preSelectedId && prodSelect) {
        prodSelect.value = preSelectedId;
        const selected = allProductsList.find(p => p.id == preSelectedId);
        checkProductCompletenessUI(selected, 'in');
        if (selected) {
          if (locInput && selected.location) {
            locInput.value = selected.location;
          }
          const locHint = document.getElementById('loc_hint');
          if (locHint && selected.location) {
            locHint.innerHTML = `Lokasi rak resmi master produk: <strong style="color:var(--color-primary);">${escapeHtml(selected.location)}</strong>.`;
          }
          if (statusSelect) {
            statusSelect.value = (selected.is_active == 0 || selected.is_active === '0') ? 'draft' : 'aktif';
          }
          updateStockSummaryCard('in');
        }
      } else {
        updateStockSummaryCard('in');
      }
    });

  // Handle Form Submit & Reset
  const form = document.querySelector('form');
  if (form) {
    form.addEventListener('reset', () => setTimeout(() => updateStockSummaryCard('in'), 50));
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      submitStockTransaction('in');
    });
  }
}

// ==========================================================================
// 2. HALAMAN STOK KELUAR (Outbound)
// ==========================================================================
function initStockOutPage() {
  const pageSub = document.querySelector('.page-title-group p');
  if (pageSub) {
    pageSub.textContent = 'Dokumentasi resmi pengeluaran persediaan gudang untuk kebutuhan operasional perusahaan, pemenuhan pesanan penjualan, maupun alokasi distribusi barang.';
  }

  const urlParams = new URLSearchParams(window.location.search);
  const preSelectedId = urlParams.get('product_id');

  const dateInput = document.getElementById('transaction_date');
  if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

  // Auto-generate No. SPK sebagai placeholder; isi value jika ada dari URL (sales/referensi eksternal)
  const spkInput = document.getElementById('no_spk');
  if (spkInput) {
    const now   = new Date();
    const ymd   = now.getFullYear().toString()
                + String(now.getMonth() + 1).padStart(2, '0')
                + String(now.getDate()).padStart(2, '0');
    const hex   = Math.floor(Math.random() * 0xFFFFFF).toString(16).padStart(6, '0');
    const autoSpk = `SPK-${ymd}-${hex}`;
    // Cek URL param: no_spk atau reference_no (dari modul sales/lain)
    const spkParams = new URLSearchParams(window.location.search);
    const extRef = spkParams.get('no_spk') || spkParams.get('reference_no') || '';
    if (extRef) {
      spkInput.value       = extRef;
      spkInput.placeholder = extRef;
    } else {
      spkInput.value       = '';
      spkInput.placeholder = autoSpk;
    }
  }

  // Render kartu ringkasan awal (empty state)
  updateStockSummaryCard('out');

  // Muat dropdown produk untuk stok keluar
  loadProductsForSelect('product_select', false, true).then(() => {
    const prodSelect = document.getElementById('product_select');
    const qtyInput = document.getElementById('quantity');
    const locInput = document.getElementById('location');

    if (prodSelect && qtyInput) {
      prodSelect.addEventListener('change', function() {
        const selected = allProductsList.find(p => p.id == this.value);
        checkProductCompletenessUI(selected, 'out');
        if (selected) {
          const activeStock = parseFloat(selected.stock_active ?? selected.current_stock) || 0;
          const draftStock = parseFloat(selected.stock_draft ?? 0) || 0;
          const quaranStock = parseFloat(selected.stock_quarantine ?? 0) || 0;

          qtyInput.max = activeStock;
          const hint = qtyInput.parentElement.querySelector('.form-hint');
          if (hint) {
            let holdMsg = '';
            if (draftStock > 0 || quaranStock > 0) {
              holdMsg = ` <span style="color:var(--color-text-muted);">(Catatan: ${formatQty(draftStock)} unit Draft dan ${formatQty(quaranStock)} unit Karantina tertahan).</span>`;
            }
            hint.innerHTML = `Stok aktif tersedia untuk dikeluarkan: <strong style="color:var(--color-success); font-weight:700;">${formatQty(activeStock)} ${selected.unit}</strong>. Maksimal pengeluaran: ${formatQty(activeStock)} ${selected.unit}.${holdMsg}`;
          }
          if (locInput) {
            locInput.value = selected.location || '';
          }
        } else {
          if (locInput) locInput.value = '';
          const hint = qtyInput.parentElement.querySelector('.form-hint');
          if (hint) {
            hint.innerHTML = 'Jumlah barang yang akan dikeluarkan dari gudang.';
          }
        }
        updateStockSummaryCard('out');
      });

      qtyInput.addEventListener('input', () => updateStockSummaryCard('out'));
      qtyInput.addEventListener('change', () => updateStockSummaryCard('out'));

      if (preSelectedId && prodSelect) {
        prodSelect.value = preSelectedId;
        const selected = allProductsList.find(p => p.id == preSelectedId);
        checkProductCompletenessUI(selected, 'out');
        if (selected) {
          const activeStock = parseFloat(selected.stock_active ?? selected.current_stock) || 0;
          qtyInput.max = activeStock;
          if (locInput) locInput.value = selected.location || '';
          updateStockSummaryCard('out');
        }
      } else {
        updateStockSummaryCard('out');
      }
    }
  });

  // Handle Form Submit & Reset
  const form = document.querySelector('form');
  if (form) {
    form.addEventListener('reset', () => setTimeout(() => updateStockSummaryCard('out'), 50));
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

  const productId    = document.getElementById('product_select')?.value;
  const quantity     = document.getElementById('quantity')?.value;
  const date         = document.getElementById('transaction_date')?.value;
  const notes        = document.getElementById('notes')?.value;
  const supplierId   = document.getElementById('supplier')?.value || null;
  const noSuratJalan = document.getElementById('no_surat_jalan')?.value || '';
  const noSpk        = document.getElementById('no_spk')?.value || '';
  const location     = document.getElementById('location')?.value?.trim() || '';
  const statusInput  = document.getElementById('stock_status');
  const stockStatus  = statusInput ? statusInput.value.trim() : 'aktif';

  // Validasi Wajib Isi di Frontend
  const fail = (fieldId, msg) => {
    showStockAlert({ type: 'warning', title: 'Data Belum Lengkap', message: msg });
    highlightField(fieldId);
  };
  if (!productId) return fail('product_select', 'Silakan pilih produk terlebih dahulu.');

  // Validasi Standar Kelengkapan Data Produk (Product Readiness Check)
  const selectedProd = allProductsList.find(p => p.id == productId);
  if (selectedProd && !selectedProd.is_complete) {
    const missingList = (selectedProd.missing_fields || []).map(m => `&bull; <strong>${escapeHtml(m)}</strong>`).join('<br>');
    showStockAlert({
      type: 'warning',
      title: 'Data Produk Belum Lengkap!',
      message: `Produk <strong>${escapeHtml(selectedProd.name)}</strong> belum dapat diproses untuk mutasi stok ${type === 'in' ? 'masuk' : 'keluar'} karena konfigurasi data master belum lengkap:<br><div style="text-align:left; margin:10px 0; padding:10px; background:#fffbeb; border:1px solid #fde68a; border-radius:6px; font-size:12px;">${missingList}</div>Silakan lengkapi spesifikasi produk terlebih dahulu melalui menu Edit Produk.<br><br><a href="product-form.html?id=${selectedProd.id}" class="btn btn-primary btn-sm" style="display:inline-block; margin-top:4px;">Lengkapi Data Produk Sekarang &rarr;</a>`,
      buttonText: 'Tutup'
    });
    highlightField('product_select');
    return;
  }

  if (!quantity || parseFloat(quantity) <= 0) return fail('quantity', 'Jumlah barang harus diisi dengan angka lebih dari 0.');
  if (!date) return fail('transaction_date', 'Tanggal transaksi wajib diisi.');
  if (type === 'in' && !stockStatus) return fail('stock_status', 'Status stok / produk wajib dipilih.');
  if (type === 'in' && !supplierId) return fail('supplier', 'Pemasok (Supplier) wajib dipilih untuk stok masuk.');
  if (!location) return fail('location', 'Lokasi / Rak Penyimpanan wajib diisi.');

  // Validasi Kesesuaian Lokasi Rak dengan Master Data Produk
  if (selectedProd && selectedProd.location) {
    const expectedLoc = selectedProd.location.trim();
    if (location.trim().toLowerCase() !== expectedLoc.toLowerCase()) {
      return fail('location', `Lokasi rak tidak sesuai! Produk "${selectedProd.name}" terdaftar di master lokasi rak "${expectedLoc}", bukan "${location}". Transaksi wajib dilakukan pada lokasi rak resmi produk.`);
    }
  }

  // Validasi Stok Aktif untuk Stok Keluar
  if (type === 'out') {
    const selected = allProductsList.find(p => p.id == productId);
    if (selected) {
      const activeStock = parseFloat(selected.stock_active ?? selected.current_stock) || 0;
      const draftStock  = parseFloat(selected.stock_draft ?? 0) || 0;
      const quaranStock = parseFloat(selected.stock_quarantine ?? 0) || 0;
      if (activeStock <= 0) {
        let tertahan = [];
        if (draftStock > 0) tertahan.push(`${formatQty(draftStock)} unit Draft`);
        if (quaranStock > 0) tertahan.push(`${formatQty(quaranStock)} unit Karantina`);
        const holdMsg = tertahan.length > 0 ? ` (Terdapat ${tertahan.join(' dan ')} yang tertahan).` : '';
        return fail('quantity', `Tidak ada stok aktif yang tersedia untuk dikeluarkan!${holdMsg}`);
      }
      if (parseFloat(quantity) > activeStock) {
        let tertahan = [];
        if (draftStock > 0) tertahan.push(`${formatQty(draftStock)} unit Draft`);
        if (quaranStock > 0) tertahan.push(`${formatQty(quaranStock)} unit Karantina`);
        const holdMsg = tertahan.length > 0 ? ` (Terdapat ${tertahan.join(' dan ')} tertahan yang belum dapat dikeluarkan).` : '';
        return fail('quantity', `Jumlah pengeluaran (${formatQty(quantity)} ${selected.unit}) melebihi stok aktif yang tersedia (${formatQty(activeStock)} ${selected.unit}).${holdMsg}`);
      }
    }
  }

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span>Menyimpan...</span>';

  const payload = {
    type:           type,
    product_id:     productId,
    quantity:       quantity,
    date:           date,
    notes:          notes,
    supplier_id:    supplierId || undefined,
    no_surat_jalan: noSuratJalan || undefined,
    no_spk:         noSpk || undefined,
    location:       location,
    stock_status:   type === 'in' ? stockStatus : undefined,
  };

  fetch('../api/stock.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(res => {
    if (res.status === 'success') {
      showStockAlert({
        type: 'success',
        title: type === 'in' ? 'Stok Masuk Tersimpan' : 'Stok Keluar Tersimpan',
        message: escapeHtml(res.message) + (res.ref_number ? `<div class="stk-ref">No. Referensi: <b>${escapeHtml(res.ref_number)}</b></div>` : ''),
        buttonText: 'Lihat Riwayat Stok',
        onClose: () => { window.location.href = 'stock-history.html'; }
      });
    } else {
      let extraAction = '';
      if (res.error_code === 'PRODUCT_INCOMPLETE' && res.product_id) {
        extraAction = `<br><br><a href="product-form.html?id=${res.product_id}" class="btn btn-primary btn-sm" style="display:inline-block; margin-top:6px;">Lengkapi Data Produk Sekarang &rarr;</a>`;
      }
      showStockAlert({
        type: (res.error_code === 'PRODUCT_INCOMPLETE' || res.error_code === 'LOCATION_MISMATCH') ? 'warning' : 'danger',
        title: res.error_code === 'PRODUCT_INCOMPLETE' ? 'Transaksi Dicekal' : (res.error_code === 'LOCATION_MISMATCH' ? 'Lokasi Rak Tidak Sesuai' : 'Gagal Menyimpan'),
        message: escapeHtml(res.message || 'Terjadi kesalahan sistem.') + extraAction
      });
      if (res.error_code === 'LOCATION_MISMATCH') {
        highlightField('location');
      }
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalHtml;
    }
  })
  .catch(err => {
    showStockAlert({ type: 'danger', title: 'Kesalahan Jaringan', message: escapeHtml(err.message) });
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalHtml;
  });
}

// ==========================================================================
// Popup Notifikasi Interaktif (pengganti alert bawaan browser)
// ==========================================================================
function showStockAlert({ type = 'success', title = '', message = '', buttonText = 'OK', onClose = null }) {
  injectStockAlertStyles();
  document.getElementById('stk-alert')?.remove();

  const icons = {
    success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    danger:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
    warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="8" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
  };

  const overlay = document.createElement('div');
  overlay.id = 'stk-alert';
  overlay.className = 'stk-overlay';
  overlay.innerHTML = `
    <div class="stk-box stk-${type}" role="alertdialog" aria-modal="true">
      <div class="stk-icon">${icons[type] || icons.success}</div>
      <div class="stk-title">${title}</div>
      <div class="stk-msg">${message}</div>
      <button type="button" class="stk-btn">${buttonText}</button>
    </div>`;
  document.body.appendChild(overlay);

  const close = () => {
    overlay.classList.add('stk-hide');
    setTimeout(() => { overlay.remove(); if (onClose) onClose(); }, 200);
    document.removeEventListener('keydown', onKey);
  };
  const onKey = (e) => { if (e.key === 'Enter' || e.key === 'Escape') close(); };
  overlay.querySelector('.stk-btn').addEventListener('click', close);
  overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
  document.addEventListener('keydown', onKey);
  setTimeout(() => overlay.querySelector('.stk-btn')?.focus(), 50);
}

function highlightField(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.remove('stk-field-error');
  void el.offsetWidth; // restart animasi
  el.classList.add('stk-field-error');
  el.focus({ preventScroll: true });
  el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  const clear = () => el.classList.remove('stk-field-error');
  el.addEventListener('input', clear, { once: true });
  el.addEventListener('change', clear, { once: true });
}

function injectStockAlertStyles() {
  if (document.getElementById('stk-alert-style')) return;
  const s = document.createElement('style');
  s.id = 'stk-alert-style';
  s.textContent = `
    .stk-overlay{position:fixed;inset:0;z-index:10050;display:flex;align-items:center;justify-content:center;
      background:rgba(15,23,42,.45);backdrop-filter:blur(2px);padding:16px;animation:stkFade .2s ease}
    .stk-overlay.stk-hide{animation:stkFadeOut .2s ease forwards}
    .stk-box{background:#fff;border-radius:16px;width:100%;max-width:380px;padding:28px 24px 22px;text-align:center;
      box-shadow:0 20px 50px rgba(0,0,0,.25);animation:stkPop .3s cubic-bezier(.34,1.56,.64,1)}
    .stk-icon{width:64px;height:64px;border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center}
    .stk-icon svg{width:32px;height:32px}
    .stk-success .stk-icon{background:#dcfce7;color:#16a34a}
    .stk-danger  .stk-icon{background:#fee2e2;color:#dc2626}
    .stk-warning .stk-icon{background:#fef3c7;color:#d97706}
    .stk-title{font-size:18px;font-weight:700;color:#0f172a;margin-bottom:6px}
    .stk-msg{font-size:14px;color:#475569;line-height:1.5}
    .stk-ref{margin-top:10px;padding:8px 10px;background:#f1f5f9;border-radius:8px;font-family:var(--font-mono,monospace);font-size:12px}
    .stk-btn{margin-top:20px;width:100%;padding:10px 16px;border:none;border-radius:10px;font-weight:600;font-size:14px;
      cursor:pointer;color:#fff;transition:transform .1s, filter .15s}
    .stk-btn:hover{filter:brightness(1.08)} .stk-btn:active{transform:scale(.98)}
    .stk-success .stk-btn{background:#16a34a} .stk-danger .stk-btn{background:#dc2626} .stk-warning .stk-btn{background:#d97706}
    .stk-field-error{border-color:#dc2626 !important;box-shadow:0 0 0 3px rgba(220,38,38,.15) !important;animation:stkShake .35s}
    @keyframes stkFade{from{opacity:0}to{opacity:1}}
    @keyframes stkFadeOut{to{opacity:0}}
    @keyframes stkPop{from{opacity:0;transform:scale(.85) translateY(10px)}to{opacity:1;transform:none}}
    @keyframes stkShake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
  `;
  document.head.appendChild(s);
}

function updateStockSummaryCard(type) {
  const prodSelect = document.getElementById('product_select');
  const qtyInput   = document.getElementById('quantity');
  const cardBody   = document.getElementById('stockSummaryBody')
                  || document.querySelector('#stockSummaryCard .card-body')
                  || document.querySelector('.grid-dashboard-main .card .card-body');
  if (!cardBody) return;

  const isIn = (type === 'in');
  const selectedId = prodSelect ? prodSelect.value : '';
  const selected = allProductsList.find(p => p.id == selectedId);

  // 1. Kondisi Belum Ada Produk Dipilih (Initial / Empty Template)
  if (!selected) {
    cardBody.innerHTML = `
      <div style="padding: 24px 16px; text-align: center;">
        <div style="width: 48px; height: 48px; margin: 0 auto 12px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b;">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
        </div>
        <h3 style="margin: 0 0 4px; font-size: 14px; font-weight: 700; color: var(--color-text);">Belum Ada Produk Dipilih</h3>
        <p style="margin: 0 0 18px; font-size: 12px; color: var(--color-text-muted); line-height: 1.4;">
          Silakan pilih produk dari formulir untuk melihat informasi stok dan kalkulasi mutasi secara real-time.
        </p>

        <div style="background: var(--color-bg, #f8fafc); border: 1px dashed var(--color-border); border-radius: 8px; padding: 14px; text-align: left; display: flex; flex-direction: column; gap: 8px; font-size: 12px;">
          <div style="display: flex; justify-content: space-between; color: var(--color-text-muted);">
            <span>Produk Terpilih:</span>
            <span style="font-weight: 500; color: var(--color-text);">&mdash;</span>
          </div>
          <div style="display: flex; justify-content: space-between; color: var(--color-text-muted);">
            <span>Lokasi Penyimpanan:</span>
            <span style="font-weight: 500; color: var(--color-text);">&mdash;</span>
          </div>
          <div style="display: flex; justify-content: space-between; color: var(--color-text-muted);">
            <span>Stok Fisik Saat Ini:</span>
            <span style="font-weight: 500; color: var(--color-text);">&mdash;</span>
          </div>
          <div style="display: flex; justify-content: space-between; color: var(--color-text-muted);">
            <span>${isIn ? 'Rencana Masuk' : 'Jumlah Keluar'}:</span>
            <span style="font-weight: 500; color: var(--color-text);">&mdash;</span>
          </div>
          <div style="display: flex; justify-content: space-between; padding-top: 6px; border-top: 1px dashed var(--color-border); color: var(--color-text-muted);">
            <span>${isIn ? 'Estimasi Total Fisik' : 'Estimasi Sisa Stok Aktif'}:</span>
            <span style="font-weight: 700; color: var(--color-text);">&mdash;</span>
          </div>
        </div>

        <div class="alert alert-info" style="margin-top: var(--spacing-lg); margin-bottom: 0; text-align: left;">
          <div style="font-size: var(--font-size-xs); line-height: 1.4;">
            ${isIn 
              ? 'Pastikan kondisi fisik barang dan dokumen surat jalan diverifikasi sebelum menyimpan transaksi penerimaan.' 
              : 'Pastikan nomor SPK dan bukti serah terima barang telah siap sebelum mencatat pengeluaran stok.'}
          </div>
        </div>
      </div>
    `;

    const descBody = document.getElementById('productDescBody');
    if (descBody) {
      descBody.innerHTML = `
        <span class="text-muted" style="font-size: var(--font-size-xs); font-style: italic;">Pilih produk terlebih dahulu untuk melihat catatan teknis, spesifikasi, atau garansi barang.</span>
      `;
    }
    return;
  }

  // 2. Kondisi Produk Sudah Terpilih
  const curStock    = parseFloat(selected.current_stock) || 0;
  const activeStock = parseFloat(selected.stock_active ?? selected.current_stock) || 0;
  const draftStock  = parseFloat(selected.stock_draft ?? 0) || 0;
  const quaranStock = parseFloat(selected.stock_quarantine ?? 0) || 0;
  const minStock    = parseFloat(selected.minimum_stock) || 0;
  const qtyVal      = Math.max(0, parseFloat(qtyInput?.value) || 0);
  const unit        = selected.unit || 'unit';
  const locName     = selected.location || 'Belum diatur';

  // Perbarui Box Spesifikasi & Catatan Produk
  const descBody = document.getElementById('productDescBody');
  if (descBody) {
    const descText = (selected.description || '').trim();
    if (descText) {
      descBody.innerHTML = `
        <div style="font-size: var(--font-size-xs); color: var(--color-text); line-height: 1.6; white-space: pre-line;">
          ${escapeHtml(descText)}
        </div>
      `;
    } else {
      descBody.innerHTML = `
        <span class="text-muted" style="font-size: var(--font-size-xs); font-style: italic;">Tidak ada catatan spesifikasi atau garansi khusus untuk produk ini.</span>
      `;
    }
  }

  if (!isIn) {
    // ----------------------------------------------------
    // RINGKASAN STOK KELUAR
    // ----------------------------------------------------
    const endActive = Math.max(0, activeStock - qtyVal);
    const endTotal  = Math.max(0, curStock - qtyVal);
    const isOver    = qtyVal > activeStock;
    const isUnderMin = endActive > 0 && endActive <= minStock;
    const isZero    = endActive <= 0;

    let statusMinBadge = '';
    if (isZero) {
      statusMinBadge = `<span class="badge badge-out" style="font-size:10px;"><span class="badge-dot"></span>Stok Habis</span>`;
    } else if (isUnderMin) {
      statusMinBadge = `<span class="badge badge-low" style="font-size:10px;"><span class="badge-dot"></span>Hampir Habis (&le; ${formatQty(minStock)})</span>`;
    } else {
      statusMinBadge = `<span class="badge badge-available" style="font-size:10px;"><span class="badge-dot"></span>Stok Aman</span>`;
    }

    cardBody.innerHTML = `
      <div style="margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-md); border-bottom: 1px solid var(--color-border-subtle);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
          <div>
            <span class="text-muted" style="font-size: var(--font-size-xs);">Produk Terpilih:</span>
            <div class="text-bold" style="font-size: var(--font-size-base); color: var(--color-text); margin-top: 2px;">${escapeHtml(selected.name)}</div>
          </div>
          <span class="table-sku" style="margin-top: 2px;">${escapeHtml(selected.sku)}</span>
        </div>
        <div style="margin-top: 6px; display: flex; align-items: center; gap: 6px;">
          <div style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #0284c7; background: #f0f9ff; border: 1px solid #bae6fd; padding: 2px 7px; border-radius: 4px;">
            <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            <span style="font-weight: 600;">${escapeHtml(locName)}</span>
          </div>
        </div>
      </div>

      <div style="display: flex; flex-direction: column; gap: 10px; font-size: var(--font-size-sm);">
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
          <span class="text-muted">Stok Aktif (Siap Keluar):</span>
          <strong style="color: var(--color-success); font-size: 15px;">${formatQty(activeStock)} ${escapeHtml(unit)}</strong>
        </div>

        ${(draftStock > 0 || quaranStock > 0) ? `
          <div style="display: flex; flex-direction: column; gap: 4px; font-size: var(--font-size-xs); background: var(--color-bg); padding: 8px 10px; border-radius: 6px; border: 1px solid var(--color-border-subtle);">
            <div style="display:flex; justify-content:space-between;">
              <span class="text-muted">• Stok Draft (Tertahan):</span>
              <strong style="color:#0369a1;">${formatQty(draftStock)} ${escapeHtml(unit)}</strong>
            </div>
            <div style="display:flex; justify-content:space-between;">
              <span class="text-muted">• Stok Karantina (QC):</span>
              <strong style="color:#b45309;">${formatQty(quaranStock)} ${escapeHtml(unit)}</strong>
            </div>
          </div>
        ` : ''}

        <div style="display: flex; justify-content: space-between; align-items: baseline;">
          <span class="text-muted">Jumlah Keluar:</span>
          <strong style="color: var(--color-danger); font-size: 15px;">${qtyVal > 0 ? '-' : ''}${formatQty(qtyVal)} ${escapeHtml(unit)}</strong>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 8px; border-top: 1px dashed var(--color-border);">
          <div>
            <span class="text-muted" style="display:block;">Sisa Stok Aktif:</span>
            <div style="margin-top: 3px;">${statusMinBadge}</div>
          </div>
          <strong style="font-size: 17px; color: ${isOver ? 'var(--color-danger)' : 'var(--color-primary)'};">
            ${formatQty(endActive)} ${escapeHtml(unit)}
          </strong>
        </div>

        <div style="display: flex; justify-content: space-between; font-size: var(--font-size-xs); color: var(--color-text-muted); padding-top: 2px;">
          <span>Total Fisik Seluruh Gudang:</span>
          <span>${formatQty(endTotal)} ${escapeHtml(unit)}</span>
        </div>
      </div>

      <div class="alert ${isOver ? 'alert-danger' : 'alert-warning'}" style="margin-top: var(--spacing-lg); margin-bottom: 0;">
        <div style="font-size: var(--font-size-xs); line-height: 1.4;">
          ${isOver 
            ? `<strong>Peringatan!</strong> Pengeluaran ${formatQty(qtyVal)} ${escapeHtml(unit)} melebihi stok aktif (${formatQty(activeStock)} ${escapeHtml(unit)}). Silakan kurangi jumlah barang yang dikeluarkan.` 
            : 'Pastikan bukti serah terima fisik dan nomor SPK telah diverifikasi sebelum barang dikeluarkan dari gudang.'}
        </div>
      </div>
    `;
  } else {
    // ----------------------------------------------------
    // RINGKASAN STOK MASUK
    // ----------------------------------------------------
    const statusSelect = document.getElementById('stock_status');
    const stVal = statusSelect ? statusSelect.value : 'aktif';
    let stBadge = '<span class="badge" style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:600;"><span class="badge-dot" style="background:#16a34a;"></span>Aktif (Siap Edar)</span>';
    let targetLabel = 'Estimasi Stok Aktif:';
    let targetEst = activeStock + qtyVal;
    let targetColor = 'var(--color-success)';

    if (stVal === 'draft') {
      stBadge = '<span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:600;"><span class="badge-dot" style="background:#0284c7;"></span>Draft (Belum Rilis)</span>';
      targetLabel = 'Estimasi Stok Draft:';
      targetEst = draftStock + qtyVal;
      targetColor = '#0369a1';
    } else if (stVal === 'karantina') {
      stBadge = '<span class="badge" style="background:#fef3c7; color:#b45309; font-size:11px; font-weight:600;"><span class="badge-dot" style="background:#d97706;"></span>Karantina (QC)</span>';
      targetLabel = 'Estimasi Stok Karantina:';
      targetEst = quaranStock + qtyVal;
      targetColor = '#b45309';
    }

    const newTotalStock  = curStock + qtyVal;

    cardBody.innerHTML = `
      <div style="margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-md); border-bottom: 1px solid var(--color-border-subtle);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
          <div>
            <span class="text-muted" style="font-size: var(--font-size-xs);">Produk Terpilih:</span>
            <div class="text-bold" style="font-size: var(--font-size-base); color: var(--color-text); margin-top: 2px;">${escapeHtml(selected.name)}</div>
          </div>
          <span class="table-sku" style="margin-top: 2px;">${escapeHtml(selected.sku)}</span>
        </div>
        <div style="margin-top: 6px; display: flex; align-items: center; gap: 6px;">
          <div style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #0284c7; background: #f0f9ff; border: 1px solid #bae6fd; padding: 2px 7px; border-radius: 4px;">
            <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            <span style="font-weight: 600;">${escapeHtml(locName)}</span>
          </div>
        </div>
      </div>

      <div style="display: flex; flex-direction: column; gap: 10px; font-size: var(--font-size-sm);">
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
          <span class="text-muted">Total Fisik Saat Ini:</span>
          <strong style="font-size: 15px; color: var(--color-text);">${formatQty(curStock)} ${escapeHtml(unit)}</strong>
        </div>

        <div style="display: flex; flex-direction: column; gap: 4px; font-size: var(--font-size-xs); background: var(--color-bg); padding: 8px 10px; border-radius: 6px; border: 1px solid var(--color-border-subtle);">
          <div style="display:flex; justify-content:space-between;">
            <span class="text-muted">• Stok Aktif (Ready):</span>
            <strong style="color:var(--color-success);">${formatQty(activeStock)} ${escapeHtml(unit)}</strong>
          </div>
          <div style="display:flex; justify-content:space-between;">
            <span class="text-muted">• Stok Draft (Belum Rilis):</span>
            <strong style="color:#0369a1;">${formatQty(draftStock)} ${escapeHtml(unit)}</strong>
          </div>
          <div style="display:flex; justify-content:space-between;">
            <span class="text-muted">• Stok Karantina (QC):</span>
            <strong style="color:#b45309;">${formatQty(quaranStock)} ${escapeHtml(unit)}</strong>
          </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <span class="text-muted" style="display:block;">Rencana Masuk:</span>
            <div style="margin-top: 3px;">${stBadge}</div>
          </div>
          <strong style="color: var(--color-success); font-size: 16px;">+${formatQty(qtyVal)} ${escapeHtml(unit)}</strong>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: var(--font-size-sm); margin-top: -2px;">
          <span class="text-muted">${targetLabel}</span>
          <strong style="color: ${targetColor}; font-size: 14px;">${formatQty(targetEst)} ${escapeHtml(unit)}</strong>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 8px; border-top: 1px dashed var(--color-border);">
          <span class="text-muted">Estimasi Total Fisik:</span>
          <strong style="font-size: 17px; color: var(--color-primary);">${formatQty(newTotalStock)} ${escapeHtml(unit)}</strong>
        </div>
      </div>

      <div class="alert alert-info" style="margin-top: var(--spacing-lg); margin-bottom: 0;">
        <div style="font-size: var(--font-size-xs); line-height: 1.4;">
          Pastikan kondisi fisik barang dan dokumen surat jalan diverifikasi sebelum menyimpan transaksi penerimaan.
        </div>
      </div>
    `;
  }
}

// ==========================================================================
// 3. HALAMAN RIWAYAT STOK (Stock History)
// ==========================================================================
let rawStockMovements = [];
let allStockMovements = [];
let currentStockHistoryPage = 1;
const STOCK_HISTORY_PER_PAGE = 10;

function initStockHistoryPage() {
  loadStockHistoryFromDb();

  const startDateInput = document.getElementById('filterStartDate');
  const endDateInput   = document.getElementById('filterEndDate');
  const typeFilter     = document.getElementById('filterType') || document.querySelector('select[aria-label="Filter Jenis Transaksi"]');
  const refInput       = document.getElementById('filterRefNo');
  const clearRefBtn    = document.getElementById('btnClearRefNo');
  const resetBtn       = document.getElementById('btnResetFilters');

  if (startDateInput) {
    startDateInput.addEventListener('change', function() {
      if (endDateInput) {
        endDateInput.min = this.value;
        if (endDateInput.value && endDateInput.value < this.value) {
          endDateInput.value = this.value;
        }
      }
      applyStockHistoryFilters();
    });
    startDateInput.addEventListener('input', applyStockHistoryFilters);
  }
  if (endDateInput) {
    endDateInput.addEventListener('change', applyStockHistoryFilters);
    endDateInput.addEventListener('input', applyStockHistoryFilters);
  }
  if (typeFilter) {
    typeFilter.addEventListener('change', applyStockHistoryFilters);
  }
  if (refInput) {
    refInput.addEventListener('input', applyStockHistoryFilters);
    refInput.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        this.value = '';
        applyStockHistoryFilters();
      }
    });
  }
  if (clearRefBtn) {
    clearRefBtn.addEventListener('click', function() {
      if (refInput) {
        refInput.value = '';
        refInput.focus();
      }
      applyStockHistoryFilters();
    });
  }
  if (resetBtn) {
    resetBtn.addEventListener('click', function() {
      if (startDateInput) startDateInput.value = '';
      if (endDateInput) endDateInput.value = '';
      if (typeFilter) typeFilter.value = '';
      if (refInput) refInput.value = '';
      applyStockHistoryFilters();
    });
  }
}

function applyStockHistoryFilters() {
  const startDate = document.getElementById('filterStartDate')?.value || '';
  const endDate   = document.getElementById('filterEndDate')?.value || '';
  const typeVal   = document.getElementById('filterType')?.value || '';
  const refQuery  = (document.getElementById('filterRefNo')?.value || '').trim().toLowerCase();
  const clearRefBtn = document.getElementById('btnClearRefNo');
  const resetBtn   = document.getElementById('btnResetFilters');

  if (clearRefBtn) {
    clearRefBtn.style.display = refQuery ? 'block' : 'none';
  }

  const hasFilter = startDate || endDate || typeVal || refQuery;
  if (resetBtn) {
    resetBtn.style.display = hasFilter ? 'inline-block' : 'none';
  }

  allStockMovements = (rawStockMovements || []).filter(m => {
    // 1. Filter Rentang Waktu / Tanggal
    if (startDate || endDate) {
      const mDateStr = (m.movement_date || '').substring(0, 10);
      if (startDate && mDateStr < startDate) return false;
      if (endDate && mDateStr > endDate) return false;
    }

    // 2. Filter Jenis Transaksi
    if (typeVal) {
      const isIn = m.movement_type === 'IN';
      const refNo = m.ref_number || m.ref_no || '';
      const isDeleted = m.stock_status === 'deleted' || (m.notes && m.notes.includes('[PRODUK DIHAPUS]')) || refNo.startsWith('DEL-');
      const isTransfer = m.movement_type === 'TRANSFER' || m.stock_status === 'transfer' || (m.notes && m.notes.includes('[TRANSFER STATUS')) || refNo.startsWith('TRF-');

      if (typeVal === 'in') {
        if (!isIn || isDeleted || isTransfer) return false;
      } else if (typeVal === 'out') {
        if (isIn || isDeleted || isTransfer) return false;
      } else if (typeVal === 'transfer') {
        if (!isTransfer) return false;
      }
    }

    // 3. Filter No. Referensi atau Nama Produk / SKU
    if (refQuery) {
      const refNo    = (m.ref_number || m.ref_no || '').toLowerCase();
      const notes    = (m.notes || '').toLowerCase();
      const poNo     = (m.po_no || '').toLowerCase();
      const grnNo    = (m.grn_no || '').toLowerCase();
      const soNo     = (m.so_no || '').toLowerCase();
      const prodName = (m.product_name || '').toLowerCase();
      const sku      = (m.sku || '').toLowerCase();
      const match = refNo.includes(refQuery) || notes.includes(refQuery) || poNo.includes(refQuery) || grnNo.includes(refQuery) || soNo.includes(refQuery) || prodName.includes(refQuery) || sku.includes(refQuery);
      if (!match) return false;
    }

    return true;
  });

  currentStockHistoryPage = 1;
  renderCurrentStockHistoryPage();
}

function loadStockHistoryFromDb() {
  const tbody = document.querySelector('.table tbody');

  if (tbody) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" style="text-align: center; padding: 24px; color: var(--color-text-muted);">
          Memuat riwayat transaksi dari database...
        </td>
      </tr>
    `;
  }

  const url = '../api/stock.php?limit=500';

  fetch(url)
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success') {
        rawStockMovements = res.data || [];
        allStockMovements = [...rawStockMovements];
        window.stockHistoryData = allStockMovements;
        applyStockHistoryFilters();
      }
    })
    .catch(err => {
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--color-danger); padding: 20px;">Gagal memuat: ${escapeHtml(err.message)}</td></tr>`;
      }
    });
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

function renderCurrentStockHistoryPage() {
  const tbody = document.querySelector('.table tbody');
  if (!tbody) return;

  const totalItems = allStockMovements.length;
  const totalPages = Math.ceil(totalItems / STOCK_HISTORY_PER_PAGE) || 1;

  if (currentStockHistoryPage > totalPages) currentStockHistoryPage = totalPages;
  if (currentStockHistoryPage < 1) currentStockHistoryPage = 1;

  const start = (currentStockHistoryPage - 1) * STOCK_HISTORY_PER_PAGE;
  const end = start + STOCK_HISTORY_PER_PAGE;
  const pageItems = allStockMovements.slice(start, end);

  if (totalItems === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="10" style="text-align: center; padding: 24px; color: var(--color-text-muted);">
          Belum ada data pergerakan stok.
        </td>
      </tr>
    `;
  } else {
    tbody.innerHTML = '';
    pageItems.forEach((m, idx) => {
      const globalIdx = start + idx;
      const rowNo = globalIdx + 1;
      const isIn = m.movement_type === 'IN';
      const refNo = m.ref_number || m.ref_no || '';
      const isDeleted = m.stock_status === 'deleted' || (m.notes && m.notes.includes('[PRODUK DIHAPUS]')) || refNo.startsWith('DEL-');
      const isTransfer = m.movement_type === 'TRANSFER' || m.stock_status === 'transfer' || (m.notes && m.notes.includes('[TRANSFER STATUS')) || refNo.startsWith('TRF-');
      const sign = isIn ? '+' : '-';
      const color = isIn ? 'var(--color-success)' : 'var(--color-danger)';
      const mutasiLabel = isIn ? 'Stok Masuk' : 'Stok Keluar';

      let transferLabel = 'Pergeseran Status';
      let transferQty = m.transfer_qty || 0;
      if (m.notes) {
        const matchLabel = m.notes.match(/\[TRANSFER STATUS:\s*([^\]]+)\]/i);
        if (matchLabel && matchLabel[1]) {
          transferLabel = matchLabel[1].replace(/\s*\([^)]*\)/g, '').replace(/â†’/g, '→').trim();
        }
        if (!transferQty) {
          const matchQty = m.notes.match(/Sebesar\s+([\d\.\,]+)/i);
          if (matchQty && matchQty[1]) transferQty = parseFloat(matchQty[1].replace(',', '.'));
        }
      }
      if (!transferQty && m.quantity > 0) transferQty = m.quantity;

      // Status Badge: Bedakan antara Produk Dihapus, Transfer Status, Stok Keluar, dan Stok Masuk (Aktif, Draft, Karantina)
      let badgeHtml = '';
      if (isDeleted) {
        badgeHtml = `<span class="badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-weight:700;"><span class="badge-dot" style="background:#dc2626;"></span>Produk Dihapus</span>`;
      } else if (isTransfer) {
        badgeHtml = `<span class="badge" style="background:#f3e8ff; color:#6b21a8; border:1px solid #d8b4fe; font-weight:700;"><span class="badge-dot" style="background:#9333ea;"></span>🔄 Transfer: ${escapeHtml(transferLabel)}</span>`;
      } else if (!isIn) {
        badgeHtml = `<span class="badge" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; font-weight:600;"><span class="badge-dot" style="background:#dc2626;"></span>Stok Keluar</span>`;
      } else {
        const st = (m.stock_status || '').toLowerCase();
        if (st === 'draft' || (m.notes && m.notes.includes('Status: Draft'))) {
          badgeHtml = `<span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #7dd3fc; font-weight:600;"><span class="badge-dot" style="background:#0284c7;"></span>Masuk: Draft</span>`;
        } else if (st === 'karantina' || (m.notes && m.notes.includes('Status: Karantina'))) {
          badgeHtml = `<span class="badge" style="background:#fef3c7; color:#b45309; border:1px solid #fcd34d; font-weight:600;"><span class="badge-dot" style="background:#d97706;"></span>Masuk: Karantina</span>`;
        } else {
          badgeHtml = `<span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; font-weight:600;"><span class="badge-dot" style="background:#16a34a;"></span>Masuk: Aktif</span>`;
        }
      }

      const petugasDisplay = escapeHtml(sanitizeOfficerName(m.performed_by_name || m.created_by_name || 'Petugas Gudang'));
      const unitDisplay = escapeHtml(m.unit || 'unit');

      let qtyDisplayHtml = '';
      if (isDeleted) {
        qtyDisplayHtml = `
          <span style="color: #b91c1c; font-weight: bold;">0 ${unitDisplay}</span>
          <div style="font-size: 11px; font-weight: 600; color: #dc2626;">Penonaktifan</div>
        `;
      } else if (isTransfer) {
        qtyDisplayHtml = `
          <span style="font-weight: bold; color: #7e22ce;">${formatQty(transferQty)} ${unitDisplay}</span>
          <div style="font-size: 11px; font-weight: 600; color: #9333ea;">Pergeseran Status</div>
        `;
      } else {
        qtyDisplayHtml = `
          <span style="font-weight: bold; color: ${color};">${sign}${formatQty(m.quantity)} ${unitDisplay}</span>
          <div style="font-size: 11px; font-weight: normal; color: var(--color-text-muted);">${mutasiLabel}</div>
        `;
      }

      // Hitung Stok Awal dan Stok Akhir secara matematis
      const balStock = (m.balance_stock !== undefined && m.balance_stock !== null) ? parseFloat(m.balance_stock) : null;
      let stokAwal = null;
      let stokAkhir = balStock;

      if (isDeleted) {
        stokAwal = 0;
        stokAkhir = 0;
      } else if (isTransfer) {
        // Pada pergeseran status stok, total saldo fisik di gudang tetap sama
        stokAwal = balStock;
        stokAkhir = balStock;
      } else if (isIn) {
        // Masuk (+qty): Saldo akhir = Awal + Qty => Saldo awal = Akhir - Qty
        stokAwal = balStock !== null ? Math.max(0, balStock - parseFloat(m.quantity || 0)) : null;
      } else {
        // Keluar (-qty): Saldo akhir = Awal - Qty => Saldo awal = Akhir + Qty
        stokAwal = balStock !== null ? balStock + parseFloat(m.quantity || 0) : null;
      }

      const refTableDisplay = (m.ref_number || '-').replace(/^SO-SO-/, 'SO-');
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="text-align: center; color: var(--color-text-muted); font-weight: 500;">${rowNo}</td>
        <td>${formatDate(m.movement_date)}</td>
        <td>
          <strong>${escapeHtml(m.product_name)}</strong> ${isDeleted ? `<span class="badge" style="font-size: 10px; padding: 1px 5px; background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">Nonaktif</span>` : ''}
          <br><span class="table-sku">${escapeHtml(m.sku)}</span>
        </td>
        <td style="text-align: center;">${badgeHtml}</td>
        <td style="text-align: right; font-weight: 600; color: var(--color-text-muted);">
          ${stokAwal !== null ? `${formatQty(stokAwal)} ${unitDisplay}` : '-'}
        </td>
        <td style="text-align: right;">
          ${qtyDisplayHtml}
        </td>
        <td style="text-align: right; font-weight: bold; color: var(--color-text);">
          ${stokAkhir !== null ? `${formatQty(stokAkhir)} ${unitDisplay}` : '-'}
        </td>
        <td>
          <span style="font-family: var(--font-mono); font-weight: 600;">${escapeHtml(refTableDisplay)}</span>
          ${m.partner_name ? `<div style="font-size: 11px; color: var(--color-text-muted);">${escapeHtml(m.partner_name)}</div>` : ''}
        </td>
        <td>${petugasDisplay}</td>
        <td style="text-align: center;">
          <button type="button" class="btn btn-secondary btn-sm js-btn-detail" style="padding: 4px 10px; font-size: 12px;">Detail</button>
        </td>
      `;

      tr.querySelector('.js-btn-detail').addEventListener('click', (e) => {
        e.stopPropagation();
        showUnifiedMovementDetail(m);
      });

      tbody.appendChild(tr);
    });
  }

  // Update pagination (keterangan jumlah disembunyikan sesuai permintaan)
  const infoEl = document.getElementById('stockHistoryPaginationInfo');
  if (infoEl) {
    infoEl.innerHTML = '';
  }

  renderStockHistoryPaginationControls(totalPages, currentStockHistoryPage);
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

function renderStockHistoryPaginationControls(totalPages, page) {
  const container = document.getElementById('stockHistoryPaginationControls');
  if (!container) return;

  if (totalPages <= 1) {
    container.innerHTML = '';
    return;
  }

  const prevDisabled = page <= 1 ? 'disabled' : '';
  const nextDisabled = page >= totalPages ? 'disabled' : '';

  let html = `<a href="#" class="page-btn ${prevDisabled}" data-page="${page - 1}" aria-label="Sebelumnya" title="Halaman Sebelumnya">&laquo;</a>`;

  const items = getPaginationRange(totalPages, page);
  items.forEach(item => {
    if (item === '...') {
      html += `<span class="page-ellipsis" style="display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:34px; color:var(--color-text-muted); font-size:12px; font-weight:600; user-select:none;">...</span>`;
    } else {
      const active = item === page ? 'active' : '';
      html += `<a href="#" class="page-btn ${active}" data-page="${item}" title="Halaman ${item}">${item}</a>`;
    }
  });

  html += `<a href="#" class="page-btn ${nextDisabled}" data-page="${page + 1}" aria-label="Berikutnya" title="Halaman Berikutnya">&raquo;</a>`;

  container.innerHTML = html;

  container.querySelectorAll('.page-btn:not(.disabled)').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const targetPage = parseInt(this.dataset.page, 10);
      if (!isNaN(targetPage) && targetPage !== currentStockHistoryPage && targetPage >= 1 && targetPage <= totalPages) {
        currentStockHistoryPage = targetPage;
        renderCurrentStockHistoryPage();
        document.querySelector('.table-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
}

// ==========================================================================
// 4. MODAL DETAIL AKTIVITAS & MUTASI STOK (TERSTANDAR)
// ==========================================================================

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

function showUnifiedMovementDetail(data) {
  if (!data) return;
  const old = document.getElementById('inv-movement-detail-modal');
  if (old) old.remove();

  const isIn = data.type === 'IN' || data.movement_type === 'IN';
  const typeColor = isIn ? 'var(--color-success)' : 'var(--color-danger)';
  const st = (data.stock_status || '').toLowerCase();
  const rawType = data.raw_type || '';
  const refNo = data.ref_no || data.ref_number || '';

  // Deteksi status penerimaan (khusus Stok Masuk), Penghapusan Produk, atau Transfer Status
  const isDeleted = st === 'deleted' || (data.notes && data.notes.includes('[PRODUK DIHAPUS]')) || refNo.startsWith('DEL-');
  const isTransfer = st === 'transfer' || data.movement_type === 'TRANSFER' || (data.notes && data.notes.includes('[TRANSFER STATUS')) || refNo.startsWith('TRF-');
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
  const dateDisplay = formatDateTime(data.date || data.movement_date);

  // Satuan & Qty
  const unit = data.unit || 'unit';
  const qtyFormatted = formatQty(data.quantity);
  const qtyColor = isIn ? 'var(--color-success)' : 'var(--color-danger)';
  const qtySign = isIn ? '+' : '-';

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

function openStockDetailModal(event, m) {
  if (event && event.stopPropagation) event.stopPropagation();
  showUnifiedMovementDetail(m);
}

function closeStockDetailModal() {
  const modal = document.getElementById('inv-movement-detail-modal');
  if (modal) modal.remove();
}

window.openStockDetailModal = openStockDetailModal;
window.closeStockDetailModal = closeStockDetailModal;
window.showUnifiedMovementDetail = showUnifiedMovementDetail;

// --------------------------------------------------------------------------
// Util Helpers
// --------------------------------------------------------------------------
function checkProductCompletenessUI(selected, mode) {
  const alertEl = document.getElementById('product_incomplete_alert');
  const alertText = document.getElementById('product_incomplete_text');
  const editBtn = document.getElementById('btn_edit_incomplete_product');
  const submitBtn = document.querySelector('button[type="submit"]');

  if (!alertEl) return;

  if (selected && !selected.is_complete) {
    const missingStr = (selected.missing_fields || []).join(', ');
    if (alertText) {
      alertText.innerHTML = `Data produk <strong>${escapeHtml(selected.name)}</strong> belum lengkap (<strong>${escapeHtml(missingStr)}</strong> belum diisi). Sesuai aturan, barang <strong>tidak dapat ${mode === 'in' ? 'dimasukkan' : 'dikeluarkan'}</strong> sebelum data master dilengkapi.`;
    }
    if (editBtn) {
      editBtn.href = `product-form.html?id=${selected.id}`;
    }
    alertEl.style.display = 'block';

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.title = `Data produk belum lengkap (${missingStr})`;
    }
  } else {
    alertEl.style.display = 'none';
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.title = '';
    }
  }
}

function renderProductSelectOptions(select, products, isOutbound, placeholderText) {
  if (!select) return;
  const currentVal = select.value;
  select.innerHTML = '';
  const defOpt = document.createElement('option');
  defOpt.value = '';
  defOpt.textContent = placeholderText || '-- Pilih Barang dari Katalog --';
  select.appendChild(defOpt);

  (products || []).forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    const activeStock = parseFloat(p.stock_active ?? p.current_stock) || 0;
    const draftStock  = parseFloat(p.stock_draft ?? 0) || 0;
    const quaranStock = parseFloat(p.stock_quarantine ?? 0) || 0;
    const totalStock  = parseFloat(p.current_stock ?? 0) || 0;

    if (!p.is_complete) {
      const missingStr = (p.missing_fields || []).join(', ');
      opt.textContent = `⚠️ [BELUM LENGKAP] ${p.sku} - ${p.name} (Perlu: ${missingStr})`;
      opt.style.color = '#b45309';
      opt.style.backgroundColor = '#fffbeb';
    } else if (isOutbound) {
      let holdNote = '';
      if (draftStock > 0 || quaranStock > 0) {
        holdNote = ` (Tertahan: ${draftStock > 0 ? draftStock + ' draft' : ''}${draftStock > 0 && quaranStock > 0 ? ', ' : ''}${quaranStock > 0 ? quaranStock + ' QC' : ''})`;
      }
      opt.textContent = `${p.sku} - ${p.name} [Stok Siap Keluar: ${formatQty(activeStock)} ${p.unit}${holdNote}]`;
    } else {
      let breakdown = `Total: ${formatQty(totalStock)} ${p.unit}`;
      if (draftStock > 0 || quaranStock > 0) {
        breakdown += ` (Aktif: ${formatQty(activeStock)}, Draft: ${formatQty(draftStock)}, QC: ${formatQty(quaranStock)})`;
      }
      const inactiveBadge = (p.is_active == 0 || p.is_active === '0') ? ' [Draft]' : '';
      opt.textContent = `${p.sku} - ${p.name}${inactiveBadge} (${breakdown})`;
    }
    select.appendChild(opt);
  });

  if (currentVal && (products || []).some(p => p.id == currentVal)) {
    select.value = currentVal;
  }
}

function setupProductSearchFilter(selectId, isOutbound) {
  const select = document.getElementById(selectId);
  const searchInput = document.getElementById('product_search_filter');
  const clearBtn = document.getElementById('btn_clear_product_search');
  const infoEl = document.getElementById('product_search_info');
  if (!select || !searchInput) return;

  function doFilter() {
    const q = (searchInput.value || '').trim().toLowerCase();
    if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

    if (!q) {
      renderProductSelectOptions(select, allProductsList, isOutbound, '-- Pilih Barang dari Katalog --');
      if (infoEl) infoEl.style.display = 'none';
      return;
    }

    const filtered = (allProductsList || []).filter(p => {
      const name = (p.name || p.product_name || '').toLowerCase();
      const sku = (p.sku || '').toLowerCase();
      const cat = (p.category || '').toLowerCase();
      const barcode = (p.barcode || '').toLowerCase();
      return name.includes(q) || sku.includes(q) || cat.includes(q) || barcode.includes(q);
    });

    const placeholder = filtered.length > 0
      ? `-- Ditemukan ${filtered.length} produk cocok (Silakan pilih di dropdown) --`
      : '-- Tidak ada produk yang cocok dengan pencarian --';

    renderProductSelectOptions(select, filtered, isOutbound, placeholder);

    if (infoEl) {
      infoEl.style.display = 'block';
      if (filtered.length > 0) {
        infoEl.innerHTML = `🔍 Ditemukan <strong>${filtered.length}</strong> produk yang cocok dari ${allProductsList.length} master barang.`;
        infoEl.style.color = 'var(--color-primary)';
      } else {
        infoEl.innerHTML = `⚠️ Tidak ada produk yang cocok dengan kata kunci "<strong>${escapeHtml(searchInput.value)}</strong>".`;
        infoEl.style.color = '#dc2626';
      }
    }

    // Auto-select if exactly 1 match found
    if (filtered.length === 1) {
      if (select.value != filtered[0].id) {
        select.value = filtered[0].id;
        select.dispatchEvent(new Event('change'));
      }
    }
  }

  // Hindari multiple binding
  if (!searchInput._searchBound) {
    searchInput._searchBound = true;
    searchInput.addEventListener('input', doFilter);

    searchInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        const q = (this.value || '').trim().toLowerCase();
        if (!q) return;
        const filtered = (allProductsList || []).filter(p => {
          const name = (p.name || p.product_name || '').toLowerCase();
          const sku = (p.sku || '').toLowerCase();
          const cat = (p.category || '').toLowerCase();
          return name.includes(q) || sku.includes(q) || cat.includes(q);
        });
        if (filtered.length > 0) {
          select.value = filtered[0].id;
          select.dispatchEvent(new Event('change'));
          const qtyInput = document.getElementById('quantity');
          if (qtyInput) qtyInput.focus();
        }
      }
    });

    if (clearBtn) {
      clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        doFilter();
        searchInput.focus();
      });
    }

    const form = select.closest('form');
    if (form) {
      form.addEventListener('reset', function() {
        setTimeout(() => {
          searchInput.value = '';
          if (clearBtn) clearBtn.style.display = 'none';
          if (infoEl) infoEl.style.display = 'none';
          renderProductSelectOptions(select, allProductsList, isOutbound, '-- Pilih Barang dari Katalog --');
        }, 50);
      });
    }
  }
}

function loadProductsForSelect(selectId, includeInactive = false, isOutbound = false) {
  const url = includeInactive ? '../api/products.php?include_inactive=1' : '../api/products.php';
  return fetch(url)
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        allProductsList = res.data;
        const select = document.getElementById(selectId);
        if (select) {
          renderProductSelectOptions(select, allProductsList, isOutbound, '-- Pilih Barang dari Katalog --');
          setupProductSearchFilter(selectId, isOutbound);
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

function formatQty(val) {
  const num = parseFloat(val);
  if (isNaN(num)) return val;
  // Hilangkan desimal jika bilangan bulat (10.00 → 10, 10.50 → 10.5)
  return num % 1 === 0 ? num.toFixed(0) : num.toString();
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) +
    ', ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
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
