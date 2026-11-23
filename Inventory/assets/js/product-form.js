/**
 * Handler Form Produk (Tambah & Edit Produk) Terhubung ke MySQL
 * Proyek: Inventoria (database hris)
 */

document.addEventListener('DOMContentLoaded', function() {
  initProductForm();
});

function initProductForm() {
  const urlParams = new URLSearchParams(window.location.search);
  const productId = urlParams.get('id');

  // Muat opsi dropdown kategori & lokasi rak gudang dari MySQL
  Promise.all([loadCategories(), loadLocationsDropdown()])
    .then(() => {
      if (productId) {
        // Mode Edit: Ambil data produk yang ada
        loadProductDetailForEdit(productId);
      } else {
        // Mode Tambah Baru: Bersihkan nilai default jika ada
        setupNewProductForm();
      }
    });

  // Tombol Cepat Tambah Rak Baru langsung dari Form
  const btnQuickAddLoc = document.getElementById('btnQuickAddLocation');
  if (btnQuickAddLoc) {
    btnQuickAddLoc.addEventListener('click', function(e) {
      e.preventDefault();
      showAddStorageLocationModal(newLoc => {
        loadLocationsDropdown(newLoc.location_name);
      });
    });
  }

  // Sinkronisasi label satuan di field stok saat ini
  const unitSelect = document.getElementById('unit');
  if (unitSelect) {
    unitSelect.addEventListener('change', function() {
      const unitEl = document.getElementById('current_stock_unit');
      if (unitEl) unitEl.textContent = this.value || 'unit';
    });
  }

  // Handle Submit Form
  const form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      saveProduct(productId);
    });
  }
}

// 1. Muat Kategori dari Database
function loadCategories() {
  return fetch('../api/categories.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        const catSelect = document.getElementById('category');
        if (catSelect) {
          catSelect.innerHTML = '<option value="">Pilih Kategori</option>';
          res.data.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            catSelect.appendChild(opt);
          });
        }
      }
    })
    .catch(err => console.error('Gagal memuat kategori:', err));
}

// 2. Muat Supplier dari Database
function loadSuppliers() {
  return fetch('../api/suppliers.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        const supSelect = document.getElementById('supplier');
        if (supSelect) {
          supSelect.innerHTML = '<option value="">Pilih Supplier</option>';
          res.data.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            supSelect.appendChild(opt);
          });
        }
      }
    })
    .catch(err => console.error('Gagal memuat supplier:', err));
}

// 2b. Muat Lokasi Penyimpanan dari Database
function loadLocationsDropdown(selectedLocationName = '') {
  return fetch('../api/locations.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        const locSelect = document.getElementById('location');
        if (locSelect) {
          locSelect.innerHTML = '<option value="">-- Pilih Lokasi Penyimpanan --</option>';
          let matched = false;

          res.data.forEach(loc => {
            const opt = document.createElement('option');
            opt.value = loc.location_name;
            const areaLabel = loc.area ? ` [${loc.area}]` : '';
            opt.textContent = `${loc.location_name} (${loc.location_code})${areaLabel}`;
            if (selectedLocationName && loc.location_name.toLowerCase() === selectedLocationName.trim().toLowerCase()) {
              opt.selected = true;
              matched = true;
            }
            locSelect.appendChild(opt);
          });

          // Jika ada lokasi eksisting dari produk lama yang belum terdaftar di tabel master
          if (selectedLocationName && !matched) {
            const extraOpt = document.createElement('option');
            extraOpt.value = selectedLocationName;
            extraOpt.textContent = `${selectedLocationName} (Lokasi Tersimpan)`;
            extraOpt.selected = true;
            locSelect.appendChild(extraOpt);
          }
        }
      }
    })
    .catch(err => console.error('Gagal memuat lokasi penyimpanan:', err));
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

// 3. Mode Tambah Baru
function setupNewProductForm() {
  document.title = 'Tambah Produk Baru - Inventoria';
  const pageTitle = document.getElementById('page-main-title') || document.querySelector('.page-title-group h1');
  const pageSubtitle = document.getElementById('page-subtitle') || document.querySelector('.page-title-group p');
  const skuBadge = document.getElementById('page-sku-badge');
  const breadcrumbCurrent = document.getElementById('breadcrumb-current') || document.querySelector('.breadcrumb span:last-child');

  if (pageTitle) pageTitle.textContent = 'Tambah Produk Baru';
  if (pageSubtitle) pageSubtitle.textContent = 'Daftarkan barang atau aset baru ke dalam katalog inventaris gudang.';
  if (skuBadge) skuBadge.style.display = 'none';
  if (breadcrumbCurrent) breadcrumbCurrent.textContent = 'Tambah Produk Baru';

  // Generate SKU acak rekomendasi
  const skuInput = document.getElementById('sku');
  const nameInput = document.getElementById('product_name');
  const purPrice = document.getElementById('purchase_price');
  const selPrice = document.getElementById('selling_price');
  const curStock = document.getElementById('current_stock');
  const minStock = document.getElementById('min_stock');
  const locInput = document.getElementById('location');
  const descInput = document.getElementById('description');

  // Bersihkan dari contoh dummy jika ini form tambah baru
  if (nameInput) nameInput.value = '';
  if (skuInput) skuInput.value = 'PRD-' + Math.floor(100 + Math.random() * 900);
  if (purPrice) purPrice.value = '';
  if (selPrice) selPrice.value = '';
  if (curStock) curStock.value = '0';
  const unitEl = document.getElementById('current_stock_unit');
  if (unitEl) unitEl.textContent = 'unit';
  
  if (minStock) minStock.value = '';

  if (locInput) locInput.value = '';
  if (descInput) descInput.value = '';
}

// 4. Mode Edit: Isi Form dengan Data dari Database
function loadProductDetailForEdit(id) {
  const pageTitle = document.getElementById('page-main-title') || document.querySelector('.page-title-group h1');
  const pageSubtitle = document.getElementById('page-subtitle') || document.querySelector('.page-title-group p');
  const skuBadge = document.getElementById('page-sku-badge');
  const breadcrumbCurrent = document.getElementById('breadcrumb-current') || document.querySelector('.breadcrumb span:last-child');
  const headerActions = document.getElementById('page-header-actions') || document.querySelector('.page-actions');

  if (pageTitle) pageTitle.textContent = 'Memuat Data Produk...';
  if (pageSubtitle) pageSubtitle.textContent = 'Mengambil data spesifikasi dan konfigurasi produk...';

  fetch(`../api/products.php?id=${id}`)
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && res.data) {
        const p = res.data;

        // Tampilan Header & Breadcrumb Profesional
        document.title = `Edit: ${p.name} - Inventoria`;
        if (pageTitle) pageTitle.textContent = `Edit Produk: ${p.name}`;
        if (pageSubtitle) {
          pageSubtitle.textContent = 'Perbarui spesifikasi katalog, harga jual/modal, lokasi penyimpanan, dan batas minimum stok.';
        }
        if (skuBadge) {
          skuBadge.textContent = p.sku;
          skuBadge.style.display = 'inline-block';
        }
        if (breadcrumbCurrent) {
          breadcrumbCurrent.innerHTML = `<a href="product-detail.html?id=${id}" style="color: var(--color-text-muted); text-decoration: none;">${escapeHtml(p.name)}</a> <span class="breadcrumb-separator" style="margin: 0 4px;">/</span> <span style="color: var(--color-text); font-weight: 600;">Edit</span>`;
        }

        if (headerActions) {
          headerActions.innerHTML = `
            <a href="products.html" class="btn btn-secondary">
              <svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
              <span>Kembali ke Daftar</span>
            </a>
          `;
        }

        setValue('product_name', p.name);
        setValue('sku', p.sku);
        setValue('category', p.category_id);
        setValue('unit', p.unit);
        setValue('purchase_price', Math.round(p.purchase_price));
        setValue('selling_price', Math.round(p.selling_price));
        setValue('current_stock', p.current_stock);
        const unitEl = document.getElementById('current_stock_unit');
        if (unitEl) unitEl.textContent = p.unit || 'unit';
        setValue('min_stock', p.minimum_stock);
        loadLocationsDropdown(p.location);
        setValue('description', p.description);

        // Banner Status Kelengkapan Produk
        const banner = document.getElementById('incomplete-alert-banner');
        const missingList = document.getElementById('incomplete-missing-list');
        if (banner && missingList) {
          if (!p.is_complete && p.missing_fields && p.missing_fields.length > 0) {
            missingList.textContent = p.missing_fields.join(', ');
            banner.style.display = 'block';

            // Beri highlight visual kuning pada field yang masih kosong
            if (Array.isArray(p.missing_keys)) {
              p.missing_keys.forEach(k => {
                let el = null;
                if (k === 'category_id') el = document.getElementById('category');
                else if (k === 'minimum_stock') el = document.getElementById('min_stock');
                else el = document.getElementById(k);

                if (el) {
                  el.style.borderColor = '#f59e0b';
                  el.style.backgroundColor = '#fffdfa';
                  el.addEventListener('input', () => {
                    el.style.borderColor = '';
                    el.style.backgroundColor = '';
                  }, { once: true });
                  el.addEventListener('change', () => {
                    el.style.borderColor = '';
                    el.style.backgroundColor = '';
                  }, { once: true });
                }
              });
            }
          } else {
            banner.style.display = 'none';
          }
        }

        const submitBtnText = document.querySelector('button[type="submit"] span');
        if (submitBtnText) submitBtnText.textContent = 'Simpan Perubahan';
      } else {
        showProductFormAlert({
          type: 'danger',
          title: 'Produk Tidak Ditemukan',
          message: 'Data produk tidak ditemukan di database.',
          buttonText: 'Kembali ke Daftar',
          onClose: () => { window.location.href = 'products.html'; }
        });
      }
    })
    .catch(err => {
      showProductFormAlert({
        type: 'danger',
        title: 'Gagal Memuat Data',
        message: `Gagal mengambil data produk: ${escapeHtml(err.message)}`,
        buttonText: 'Tutup'
      });
    });
}

// 5. Simpan Produk ke MySQL
function saveProduct(productId) {
  const isEdit = !!productId;
  const name = getValue('product_name');
  const sku = getValue('sku');
  const categoryId = getValue('category');
  const unit = getValue('unit');
  const purchasePrice = parseFloat(getValue('purchase_price')) || 0;
  const sellingPrice = parseFloat(getValue('selling_price')) || 0;
  const minStock = parseFloat(getValue('min_stock'));
  const location = getValue('location');

  // Validasi Standar Kelengkapan Produk dengan Alert Interaktif & Highlight Field
  if (!name) {
    showProductFormAlert({
      type: 'warning',
      title: 'Data Belum Lengkap',
      message: 'Nama Produk wajib diisi.',
      buttonText: 'Periksa Kembali',
      fieldId: 'product_name'
    });
    return;
  }
  if (!sku) {
    showProductFormAlert({
      type: 'warning',
      title: 'Data Belum Lengkap',
      message: 'SKU / Kode Barang wajib diisi.',
      buttonText: 'Periksa Kembali',
      fieldId: 'sku'
    });
    return;
  }
  if (!categoryId || parseInt(categoryId, 10) <= 0) {
    showProductFormAlert({
      type: 'warning',
      title: 'Data Belum Lengkap',
      message: 'Kategori Produk wajib dipilih.',
      buttonText: 'Periksa Kembali',
      fieldId: 'category'
    });
    return;
  }
  if (!unit) {
    showProductFormAlert({
      type: 'warning',
      title: 'Data Belum Lengkap',
      message: 'Satuan Barang wajib dipilih.',
      buttonText: 'Periksa Kembali',
      fieldId: 'unit'
    });
    return;
  }
  if (purchasePrice <= 0) {
    showProductFormAlert({
      type: 'warning',
      title: 'Format Harga Tidak Valid',
      message: 'Harga Beli / Modal harus diisi dengan angka lebih dari 0.',
      buttonText: 'Periksa Kembali',
      fieldId: 'purchase_price'
    });
    return;
  }
  if (sellingPrice <= 0) {
    showProductFormAlert({
      type: 'warning',
      title: 'Format Harga Tidak Valid',
      message: 'Harga Jual Patokan harus diisi dengan angka lebih dari 0.',
      buttonText: 'Periksa Kembali',
      fieldId: 'selling_price'
    });
    return;
  }
  if (isNaN(minStock) || minStock <= 0) {
    showProductFormAlert({
      type: 'warning',
      title: 'Batas Stok Tidak Valid',
      message: 'Batas Minimum Stok harus diisi dengan angka lebih dari 0.',
      buttonText: 'Periksa Kembali',
      fieldId: 'min_stock'
    });
    return;
  }
  if (!location) {
    showProductFormAlert({
      type: 'warning',
      title: 'Pilih Lokasi Penyimpanan',
      message: 'Lokasi Penyimpanan wajib dipilih dari daftar yang tersedia.',
      buttonText: 'Pilih Lokasi',
      fieldId: 'location'
    });
    return;
  }

  const submitBtn = document.querySelector('button[type="submit"]');
  const originalBtnHtml = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span>Menyimpan ke MySQL...</span>';

  const payload = {
    id: productId,
    name: name,
    sku: sku,
    category_id: categoryId,
    unit: unit,
    purchase_price: purchasePrice,
    selling_price: sellingPrice,
    minimum_stock: minStock,
    location: location,
    description: getValue('description')
  };

  const method = isEdit ? 'PUT' : 'POST';
  const url = isEdit ? `../api/products.php?id=${productId}` : '../api/products.php';

  fetch(url, {
    method: method,
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(res => {
    if (res.status === 'success') {
      showProductFormAlert({
        type: 'success',
        title: isEdit ? 'Perubahan Berhasil Disimpan!' : 'Produk Berhasil Ditambahkan!',
        message: isEdit
          ? `Data produk <strong>${escapeHtml(name)}</strong> (${escapeHtml(sku)}) berhasil diperbarui di database MySQL.`
          : `Produk <strong>${escapeHtml(name)}</strong> (${escapeHtml(sku)}) berhasil ditambahkan ke katalog master inventory.`,
        buttonText: 'Lihat Daftar Produk',
        onClose: () => { window.location.href = 'products.html'; }
      });
    } else {
      showProductFormAlert({
        type: 'danger',
        title: isEdit ? 'Gagal Memperbarui Produk' : 'Gagal Menambahkan Produk',
        message: escapeHtml(res.message || 'Terjadi kesalahan saat menyimpan data ke database.'),
        buttonText: 'Tutup'
      });
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnHtml;
    }
  })
  .catch(err => {
    showProductFormAlert({
      type: 'danger',
      title: 'Kesalahan Jaringan',
      message: `Gagal terhubung ke server: ${escapeHtml(err.message)}`,
      buttonText: 'Tutup'
    });
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalBtnHtml;
  });
}

// ==========================================================================
// Popup Notifikasi Interaktif (pengganti alert bawaan browser)
// ==========================================================================
function showProductFormAlert({ type = 'success', title = '', message = '', buttonText = 'OK', onClose = null, fieldId = null }) {
  injectProductFormAlertStyles();
  document.getElementById('prd-form-alert')?.remove();

  const icons = {
    success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    danger:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
    warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="8" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
  };

  const overlay = document.createElement('div');
  overlay.id = 'prd-form-alert';
  overlay.className = 'prd-form-overlay';
  overlay.innerHTML = `
    <div class="prd-form-box prd-form-${type}" role="alertdialog" aria-modal="true">
      <div class="prd-form-icon">${icons[type] || icons.success}</div>
      <div class="prd-form-title">${title}</div>
      <div class="prd-form-msg">${message}</div>
      <button type="button" class="prd-form-btn">${buttonText}</button>
    </div>`;
  document.body.appendChild(overlay);

  if (fieldId) {
    highlightField(fieldId);
  }

  const close = () => {
    overlay.classList.add('prd-form-hide');
    setTimeout(() => {
      overlay.remove();
      if (onClose) onClose();
    }, 200);
    document.removeEventListener('keydown', onKey);
  };
  const onKey = (e) => { if (e.key === 'Enter' || e.key === 'Escape') close(); };
  overlay.querySelector('.prd-form-btn').addEventListener('click', close);
  overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
  document.addEventListener('keydown', onKey);
  setTimeout(() => overlay.querySelector('.prd-form-btn')?.focus(), 50);
}

function highlightField(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.remove('prd-form-field-error');
  void el.offsetWidth; // restart animasi
  el.classList.add('prd-form-field-error');
  el.focus({ preventScroll: true });
  el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  const clear = () => el.classList.remove('prd-form-field-error');
  el.addEventListener('input', clear, { once: true });
  el.addEventListener('change', clear, { once: true });
}

function injectProductFormAlertStyles() {
  if (document.getElementById('prd-form-alert-style')) return;
  const s = document.createElement('style');
  s.id = 'prd-form-alert-style';
  s.textContent = `
    .prd-form-overlay {
      position: fixed; inset: 0; z-index: 10050; display: flex; align-items: center; justify-content: center;
      background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); padding: 16px; animation: prdFade .2s ease;
    }
    .prd-form-overlay.prd-form-hide { animation: prdFadeOut .2s ease forwards; }
    .prd-form-box {
      background: #fff; border-radius: 16px; width: 100%; max-width: 400px; padding: 28px 24px 22px; text-align: center;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25); animation: prdPop .3s cubic-bezier(.34, 1.56, .64, 1);
    }
    .prd-form-icon {
      width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 14px; display: flex; align-items: center; justify-content: center;
    }
    .prd-form-icon svg { width: 32px; height: 32px; }
    .prd-form-success .prd-form-icon { background: #dcfce7; color: #16a34a; }
    .prd-form-danger .prd-form-icon  { background: #fee2e2; color: #dc2626; }
    .prd-form-warning .prd-form-icon { background: #fef3c7; color: #d97706; }
    .prd-form-title { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
    .prd-form-msg { font-size: 14px; color: #475569; line-height: 1.5; }
    .prd-form-btn {
      margin-top: 20px; width: 100%; padding: 11px 16px; border: none; border-radius: 10px; font-weight: 600; font-size: 14px;
      cursor: pointer; color: #fff; transition: transform .1s, filter .15s;
    }
    .prd-form-btn:hover { filter: brightness(1.08); }
    .prd-form-btn:active { transform: scale(.98); }
    .prd-form-success .prd-form-btn { background: #16a34a; }
    .prd-form-danger .prd-form-btn  { background: #dc2626; }
    .prd-form-warning .prd-form-btn { background: #d97706; }
    .prd-form-field-error {
      border-color: #dc2626 !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15) !important; animation: prdShake .35s;
    }
    @keyframes prdFade { from { opacity: 0; } to { opacity: 1; } }
    @keyframes prdFadeOut { to { opacity: 0; } }
    @keyframes prdPop { from { opacity: 0; transform: scale(.85) translateY(10px); } to { opacity: 1; transform: none; } }
    @keyframes prdShake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
  `;
  document.head.appendChild(s);
}

function getValue(id) {
  const el = document.getElementById(id);
  return el ? el.value.trim() : '';
}

function setValue(id, val) {
  const el = document.getElementById(id);
  if (el && val !== null && val !== undefined) {
    el.value = val;
  }
}

// ==========================================================================
// Modal Interaktif Tambah Lokasi Penyimpanan Baru dari Form Produk
// ==========================================================================
function showAddStorageLocationModal(onSuccess = null) {
  injectProductFormAlertStyles();
  document.getElementById('prd-add-location-modal')?.remove();

  const modalHtml = `
    <div id="prd-add-location-modal" class="prd-form-overlay" style="z-index: 10050;">
      <div class="prd-form-box" style="max-width: 480px; text-align: left; padding: 24px;">
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
              <div style="font-size: 11px; color: var(--color-text-muted);">Pendaftaran Master Lokasi Penyimpanan</div>
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
              <input type="text" id="locInputCode" class="form-input" placeholder="Contoh: RAK-A1, GUD-01" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px; text-transform: uppercase;" required>
              <div style="font-size: 10px; color: var(--color-text-muted); margin-top: 3px;">Kode singkat unik lokasi</div>
            </div>
            <div>
              <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                Nama Lokasi <span style="color: #dc2626;">*</span>
              </label>
              <input type="text" id="locInputName" class="form-input" placeholder="Contoh: Rak Elektronik A1" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px;" required>
              <div style="font-size: 10px; color: var(--color-text-muted); margin-top: 3px;">Nama lokasi penyimpanan</div>
            </div>
          </div>

          <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
              Area / Zona Gudang (Opsional)
            </label>
            <input type="text" id="locInputArea" class="form-input" placeholder="Contoh: Gudang Utama, Lantai 1, Area Karantina" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px;">
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

  // Auto uppercase untuk kode lokasi
  codeInput.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const code = codeInput.value.trim().toUpperCase();
    const name = nameInput.value.trim();
    const area = areaInput.value.trim();

    if (!code || !name) {
      showProductFormAlert({
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
        showProductFormAlert({
          type: 'success',
          title: 'Lokasi Tersimpan',
          message: data.message || `Lokasi penyimpanan "${name}" (${code}) berhasil didaftarkan.`,
          buttonText: 'Selesai'
        });
        if (typeof onSuccess === 'function') {
          onSuccess(data.data);
        }
      } else {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Simpan Lokasi';
        showProductFormAlert({
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
      showProductFormAlert({
        type: 'danger',
        title: 'Kesalahan Sistem',
        message: `Gagal menghubungi server: ${escapeHtml(err.message)}`,
        buttonText: 'Tutup'
      });
    });
  });

  setTimeout(() => codeInput.focus(), 100);
}
