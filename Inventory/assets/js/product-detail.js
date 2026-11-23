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
        if (window.InvToast) window.InvToast.show({ type: 'danger', title: 'Produk Tidak Ditemukan', message: 'Data produk yang diminta tidak tersedia di sistem.' });
        setTimeout(() => { window.location.href = 'products.html'; }, 1500);
      }
    })
    .catch(err => {
      console.error('Gagal mengambil detail produk:', err);
    });
}

let currentProductData = null;

function renderProductData(p) {
  currentProductData = p;

  document.title = `Detail: ${p.name} - Inventoria`;

  // Breadcrumb
  const breadcrumbLast = document.getElementById('breadcrumb-current') || document.querySelector('.breadcrumb span:last-child');
  if (breadcrumbLast) breadcrumbLast.textContent = p.name;

  // Header
  const titleEl = document.getElementById('page-product-title') || document.querySelector('.page-title-group h1');
  if (titleEl) titleEl.textContent = p.name;

  const skuBadge = document.querySelector('.page-title-group .table-sku');
  if (skuBadge) skuBadge.textContent = `SKU: ${p.sku}`;

  const statusBadge = document.querySelector('.page-title-group .badge');
  if (statusBadge) {
    if (!p.is_complete) {
      statusBadge.className = 'badge';
      statusBadge.style.background = '#fffbeb';
      statusBadge.style.color = '#92400e';
      statusBadge.style.border = '1px solid #fde68a';
      statusBadge.style.fontWeight = '600';
      statusBadge.innerHTML = '<span class="badge-dot" style="background:#d97706;"></span>Perlu Dilengkapi';
    } else {
      let bClass = 'badge-available';
      let bText = 'Tersedia';
      const activeStk = parseFloat(p.stock_active ?? p.current_stock) || 0;
      const minStk = parseFloat(p.minimum_stock) || 0;
      if (activeStk <= 0) {
        bClass = 'badge-out';
        bText = 'Habis';
      } else if (p.status === 'low' || activeStk <= minStk) {
        bClass = 'badge-low';
        bText = 'Hampir habis';
      }
      statusBadge.className = `badge ${bClass}`;
      statusBadge.innerHTML = `<span class="badge-dot"></span>${bText}`;
    }
  }

  // Banner Peringatan Kelengkapan Konfigurasi Data Produk
  const detailBanner = document.getElementById('detail-incomplete-banner');
  const missingText = document.getElementById('detail-missing-fields-text');
  const completeBtn = document.getElementById('btn-complete-product-now');
  if (detailBanner && missingText) {
    if (!p.is_complete && p.missing_fields && p.missing_fields.length > 0) {
      missingText.textContent = p.missing_fields.join(', ');
      if (completeBtn) completeBtn.href = `product-form.html?id=${p.id}`;
      detailBanner.style.display = 'block';
    } else {
      detailBanner.style.display = 'none';
    }
  }

  // Update action buttons links to include id
  const editBtn = document.querySelector('.page-actions a[href*="product-form.html"]');
  if (editBtn) {
    editBtn.href = `product-form.html?id=${p.id}`;
    if (!p.is_complete) {
      editBtn.innerHTML = `
        <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
        <span>Lengkapi Produk</span>
      `;
      editBtn.style.background = '#fef3c7';
      editBtn.style.color = '#92400e';
      editBtn.style.borderColor = '#fcd34d';
    }
  }

  const stockInBtn = document.querySelector('.page-actions a[href*="stock-in.html"]');
  if (stockInBtn) {
    stockInBtn.href = `stock-in.html?product_id=${p.id}`;
    if (!p.is_complete) {
      stockInBtn.classList.remove('btn-primary');
      stockInBtn.classList.add('btn-secondary');
      stockInBtn.title = 'Produk belum lengkap: ' + (p.missing_fields || []).join(', ');
    }
  }

  const stockOutBtn = document.querySelector('.page-actions a[href*="stock-out.html"]');
  if (stockOutBtn) stockOutBtn.href = `stock-out.html?product_id=${p.id}`;

  // Subtitle Kategori
  const catTitleEl = document.getElementById('detail-category-name');
  if (catTitleEl) catTitleEl.textContent = p.category_name || '-';

  // Specs
  const skuEl = document.getElementById('spec-sku');
  if (skuEl) skuEl.textContent = p.sku;

  const catEl = document.getElementById('spec-category');
  if (catEl) catEl.textContent = p.category_name || '-';

  const locEl = document.getElementById('spec-location');
  if (locEl) locEl.textContent = p.location || 'Gudang Utama';

  const buyEl = document.getElementById('spec-purchase-price');
  if (buyEl) buyEl.textContent = formatRupiah(p.purchase_price) + ' / ' + (p.unit || 'unit');

  const sellEl = document.getElementById('spec-selling-price');
  if (sellEl) sellEl.textContent = formatRupiah(p.selling_price) + ' / ' + (p.unit || 'unit');

  const marginEl = document.getElementById('spec-margin');
  if (marginEl) {
    const buyPrice = parseFloat(p.purchase_price) || 0;
    const sellPrice = parseFloat(p.selling_price) || 0;
    
    if (buyPrice === 0 && sellPrice === 0) {
      marginEl.textContent = '-';
      marginEl.style.color = 'inherit';
      marginEl.style.fontWeight = 'normal';
    } else {
      const margin = sellPrice - buyPrice;
      const marginPct = buyPrice > 0 ? Math.round((margin / buyPrice) * 100) : 0;

      if (margin > 0) {
        marginEl.textContent = `+${formatRupiah(margin)} (+${marginPct}%)`;
        marginEl.style.color = 'var(--color-success, #16a34a)';
        marginEl.style.fontWeight = '600';
      } else if (margin < 0) {
        marginEl.textContent = `-${formatRupiah(Math.abs(margin))} (${marginPct}%)`;
        marginEl.style.color = 'var(--color-danger, #dc2626)';
        marginEl.style.fontWeight = '600';
      } else {
        marginEl.textContent = `Rp0 (0%)`;
        marginEl.style.color = 'var(--color-text-muted, #64748b)';
        marginEl.style.fontWeight = '600';
      }
    }
  }

  const unitEl = document.getElementById('spec-unit');
  if (unitEl) unitEl.textContent = p.unit || '-';

  // Deskripsi
  const descP = document.getElementById('spec-description') || document.querySelector('.card-body p');
  if (descP) descP.textContent = p.description || 'Tidak ada deskripsi tambahan.';

  // === Kondisi Stok Gudang (card kanan) ===
  const stockQty = formatQty(p.current_stock);
  const minStock = formatQty(p.minimum_stock);

  // Angka besar stok
  const stockBigEl = document.getElementById('stat-stock-qty') || document.querySelector('.card-body [style*="3rem"]');
  if (stockBigEl) stockBigEl.textContent = stockQty;

  // Label "Unit Tersedia Saat Ini" & Rincian Stok per Status
  const stockUnitEl = document.getElementById('stat-stock-unit-label') || stockBigEl?.nextElementSibling;
  if (stockUnitEl) {
    const act = formatQty(p.stock_active ?? p.current_stock);
    const drf = formatQty(p.stock_draft ?? 0);
    const qrn = formatQty(p.stock_quarantine ?? 0);
    const breakdownHtml = `
      <div style="margin-top: 8px; font-size: 12px; font-weight: normal; color: var(--color-text-muted);">
        <div style="margin-top: 4px; display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
          <span style="color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 4px; font-weight: 600;">Aktif: ${act}</span>
          <span style="color: #0369a1; background: #e0f2fe; padding: 2px 8px; border-radius: 4px; font-weight: 600;">Draft: ${drf}</span>
          <span style="color: #b45309; background: #fef3c7; padding: 2px 8px; border-radius: 4px; font-weight: 600;">QC: ${qrn}</span>
        </div>
      </div>`;
    stockUnitEl.innerHTML = `${escapeHtml(p.unit || 'unit')} Total Fisik di Gudang` + breakdownHtml;
  }

  // Valuasi & Potensi Omset
  const curStockNum = parseFloat(p.current_stock) || 0;
  const buyPriceNum = parseFloat(p.purchase_price) || 0;
  const sellPriceNum = parseFloat(p.selling_price) || 0;

  const totalNilaiBeli = buyPriceNum * curStockNum;
  const totalPotensiOmset = sellPriceNum * curStockNum;

  const nilaiBeliEl = document.getElementById('stat-total-valuation') || document.querySelector('[style*="font-mono"]:not([style*="color"])');
  if (nilaiBeliEl) {
    nilaiBeliEl.textContent = formatRupiah(totalNilaiBeli);
  }

  const omsetEl = document.getElementById('stat-potential-revenue') || document.querySelector('[style*="font-mono"][style*="color"]');
  if (omsetEl) {
    omsetEl.textContent = formatRupiah(totalPotensiOmset);
  }

  // Terakhir Diperbarui (Waktu terakhir produk diedit atau mutasi terakhir)
  const lastUpdatedEl = document.getElementById('stat-last-updated') || document.querySelector('#stat-last-updated');
  if (lastUpdatedEl) {
    const rawLastDate = p.last_updated_at || p.updated_at || p.stock_history?.[0]?.date || p.created_at;
    lastUpdatedEl.textContent = rawLastDate ? formatDate(rawLastDate) : '-';
  }

  // Riwayat mutasi produk ini (dengan pagination & 9 kolom lengkap)
  currentProductData = p;
  currentHistoryPage = 1;
  renderProductHistoryTable();

  // Tombol Buka Modal Atur Status Stok
  const btnOpenStatus = document.getElementById('btnOpenStatusModal');
  if (btnOpenStatus) {
    btnOpenStatus.onclick = () => openStatusTransferModal(p);
  }

  // Quick Stock In Button
  const quickStockInBtn = document.querySelector('.card-footer a[href*="stock-in.html"]');
  if (quickStockInBtn) {
    quickStockInBtn.href = `stock-in.html?product_id=${p.id}`;
    if (!p.is_complete) {
      quickStockInBtn.classList.remove('btn-primary');
      quickStockInBtn.classList.add('btn-secondary');
      quickStockInBtn.title = 'Produk belum lengkap: ' + (p.missing_fields || []).join(', ');
    }
  }
}

/* ==========================================================
   Modal Atur Status Stok Fisik Gudang (Aktif, Draft, QC)
   ========================================================== */
function openStatusTransferModal(p) {
  if (!p) return;

  // Pengecekan Hak Akses: Diizinkan untuk Admin Gudang dan Staff Gudang
  const userRole = (window.currentUserSession?.user?.role || '').toUpperCase();
  const canManageStatus = window.isInventoryAdmin || 
                          window.currentUserSession?.permissions?.manage_stock_status ||
                          userRole.includes('WAREHOUSE') || 
                          userRole.includes('GUDANG');
  if (!canManageStatus) {
      if (window.InvToast) {
        window.InvToast.show({
          type: 'warning',
          title: 'Akses Dibatasi',
          message: 'Anda tidak memiliki wewenang untuk mengatur status stok (Aktif / Draft / QC). Fitur ini hanya dapat dilakukan oleh Petugas Gudang.',
          duration: 4500,
        });
      }
    return;
  }

  const old = document.getElementById('modal-status-transfer');
  if (old) old.remove();

  const stockActive = parseFloat(p.stock_active ?? p.current_stock) || 0;
  const stockDraft  = parseFloat(p.stock_draft ?? 0) || 0;
  const stockQuaran = parseFloat(p.stock_quarantine ?? 0) || 0;
  const unit = p.unit || 'unit';

  const modalHtml = `
    <div id="modal-status-transfer" role="dialog" aria-modal="true" aria-label="Atur Status Stok"
         style="position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;
                background:rgba(0,0,0,0.5);padding:16px;">
      <div style="background:#fff;border-radius:12px;width:100%;max-width:560px;
                  box-shadow:0 12px 48px rgba(0,0,0,0.22);overflow:hidden;animation:inv-toast-in .25s ease;">
        <!-- Header -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--color-border-subtle);
                    display:flex;align-items:center;justify-content:space-between;background:var(--color-surface);">
          <div>
            <div style="font-weight:700;font-size:16px;color:var(--color-text);">
              Atur Status Stok Fisik Gudang
            </div>
            <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px;">
              ${escapeHtml(p.name)} <span style="font-family:var(--font-mono);">(${escapeHtml(p.sku)})</span>
            </div>
          </div>
          <button type="button" onclick="document.getElementById('modal-status-transfer').remove()"
                  style="background:none;border:none;cursor:pointer;font-size:22px;
                         color:var(--color-text-muted);line-height:1;padding:4px;" aria-label="Tutup">&times;</button>
        </div>

        <!-- Body -->
        <div style="padding:20px;max-height:75vh;overflow-y:auto;">
          <!-- 1. Kondisi Saldo Fisik Saat Ini -->
          <div style="font-size:12px;font-weight:600;color:var(--color-text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">
            Kondisi Stok Saat Ini
          </div>
          <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;margin-bottom:20px;">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px;text-align:center;">
              <div style="font-size:11px;font-weight:600;color:#15803d;margin-bottom:4px;">🟢 Aktif (Siap Edar)</div>
              <div style="font-size:18px;font-weight:700;color:#166534;">${formatQty(stockActive)}</div>
              <div style="font-size:11px;color:#166534;">${escapeHtml(unit)}</div>
            </div>
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:10px;text-align:center;">
              <div style="font-size:11px;font-weight:600;color:#0369a1;margin-bottom:4px;">📦 Draft (Tertahan)</div>
              <div style="font-size:18px;font-weight:700;color:#075985;">${formatQty(stockDraft)}</div>
              <div style="font-size:11px;color:#075985;">${escapeHtml(unit)}</div>
            </div>
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px;text-align:center;">
              <div style="font-size:11px;font-weight:600;color:#b45309;margin-bottom:4px;">🧪 Karantina (QC)</div>
              <div style="font-size:18px;font-weight:700;color:#92400e;">${formatQty(stockQuaran)}</div>
              <div style="font-size:11px;color:#92400e;">${escapeHtml(unit)}</div>
            </div>
          </div>

          <!-- 2. Pilihan Pindahkan Dari (Sumber) -->
          <div style="margin-bottom:18px;">
            <label style="display:block;font-size:13px;font-weight:600;color:var(--color-text);margin-bottom:6px;">
              1. Pilih Status Asal (Pindahkan Dari Mana):
            </label>
            <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:8px;" id="groupFromStatus">
              <!-- Opsi Karantina -->
              <label class="trf-card-label" data-status="karantina" style="border:1.5px solid var(--color-border);border-radius:8px;padding:10px;cursor:pointer;display:flex;flex-direction:column;gap:3px;font-size:12px;transition:all .15s;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <input type="radio" name="trfFrom" value="karantina" style="margin:0;">
                  <strong style="color:#b45309;">Karantina</strong>
                </div>
                <div style="font-size:11px;color:var(--color-text-muted);">${formatQty(stockQuaran)} ${escapeHtml(unit)}</div>
              </label>

              <!-- Opsi Draft -->
              <label class="trf-card-label" data-status="draft" style="border:1.5px solid var(--color-border);border-radius:8px;padding:10px;cursor:pointer;display:flex;flex-direction:column;gap:3px;font-size:12px;transition:all .15s;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <input type="radio" name="trfFrom" value="draft" style="margin:0;">
                  <strong style="color:#0369a1;">Draft</strong>
                </div>
                <div style="font-size:11px;color:var(--color-text-muted);">${formatQty(stockDraft)} ${escapeHtml(unit)}</div>
              </label>

              <!-- Opsi Aktif -->
              <label class="trf-card-label" data-status="aktif" style="border:1.5px solid var(--color-border);border-radius:8px;padding:10px;cursor:pointer;display:flex;flex-direction:column;gap:3px;font-size:12px;transition:all .15s;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <input type="radio" name="trfFrom" value="aktif" style="margin:0;">
                  <strong style="color:#15803d;">Aktif</strong>
                </div>
                <div style="font-size:11px;color:var(--color-text-muted);">${formatQty(stockActive)} ${escapeHtml(unit)}</div>
              </label>
            </div>
          </div>

          <!-- 3. Pilihan Pindahkan Ke (Tujuan) -->
          <div style="margin-bottom:18px;">
            <label style="display:block;font-size:13px;font-weight:600;color:var(--color-text);margin-bottom:6px;">
              2. Pilih Status Tujuan (Pindahkan Ke Mana):
            </label>
            <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:8px;" id="groupToStatus">
              <!-- Opsi Ke Aktif -->
              <label class="trf-card-label" data-status="aktif" style="border:1.5px solid var(--color-border);border-radius:8px;padding:10px;cursor:pointer;display:flex;flex-direction:column;gap:3px;font-size:12px;transition:all .15s;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <input type="radio" name="trfTo" value="aktif" style="margin:0;">
                  <strong style="color:#15803d;">🟢 Aktif</strong>
                </div>
                <div style="font-size:11px;color:var(--color-text-muted);">Siap edar &amp; keluar</div>
              </label>

              <!-- Opsi Ke Draft -->
              <label class="trf-card-label" data-status="draft" style="border:1.5px solid var(--color-border);border-radius:8px;padding:10px;cursor:pointer;display:flex;flex-direction:column;gap:3px;font-size:12px;transition:all .15s;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <input type="radio" name="trfTo" value="draft" style="margin:0;">
                  <strong style="color:#0369a1;">📦 Draft</strong>
                </div>
                <div style="font-size:11px;color:var(--color-text-muted);">Tertahan / simpan</div>
              </label>

              <!-- Opsi Ke Karantina -->
              <label class="trf-card-label" data-status="karantina" style="border:1.5px solid var(--color-border);border-radius:8px;padding:10px;cursor:pointer;display:flex;flex-direction:column;gap:3px;font-size:12px;transition:all .15s;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <input type="radio" name="trfTo" value="karantina" style="margin:0;">
                  <strong style="color:#b45309;">🧪 Karantina</strong>
                </div>
                <div style="font-size:11px;color:var(--color-text-muted);">Pemeriksaan QC</div>
              </label>
            </div>
          </div>

          <!-- 4. Jumlah yang Ingin Dipindahkan (Input Ketikan + Tombol -/+) -->
          <div style="margin-bottom:18px;background:var(--color-surface);border:1px solid var(--color-border-subtle);border-radius:10px;padding:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:var(--color-text);margin-bottom:8px;">
              3. Jumlah Stok yang Ingin Dipindahkan (${escapeHtml(unit)}):
            </label>
            <div style="display:flex;align-items:center;gap:8px;">
              <button type="button" id="btnTrfMinus" class="btn btn-secondary"
                      style="width:44px;height:44px;font-size:22px;font-weight:bold;display:flex;align-items:center;justify-content:center;border-radius:8px;user-select:none;">
                &minus;
              </button>
              <input type="number" id="inputTrfQty" min="1" step="any" placeholder="Ketik jumlah..."
                     style="flex:1;height:44px;text-align:center;font-size:18px;font-weight:700;border:1.5px solid var(--color-border);border-radius:8px;outline:none;" />
              <button type="button" id="btnTrfPlus" class="btn btn-secondary"
                      style="width:44px;height:44px;font-size:22px;font-weight:bold;display:flex;align-items:center;justify-content:center;border-radius:8px;user-select:none;">
                +
              </button>
              <button type="button" id="btnTrfMax" class="btn btn-secondary"
                      style="height:44px;padding:0 14px;font-size:13px;font-weight:600;border-radius:8px;white-space:nowrap;">
                Maksimal
              </button>
            </div>
            <div id="txtTrfAvailableNote" style="font-size:12px;color:var(--color-text-muted);margin-top:6px;">
              Silakan pilih status asal dan status tujuan terlebih dahulu.
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div style="padding:14px 20px;background:var(--color-surface-hover);
                    border-top:1px solid var(--color-border-subtle);display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="document.getElementById('modal-status-transfer').remove()"
                  class="btn btn-secondary btn-sm" style="min-width:80px;">Batal</button>
          <button type="button" id="btnSubmitStatusTransfer"
                  class="btn btn-primary btn-sm" style="min-width:140px;display:flex;align-items:center;justify-content:center;gap:6px;">
            Simpan Perubahan
          </button>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHtml);

  const modal = document.getElementById('modal-status-transfer');
  modal.addEventListener('click', function(e) {
    if (e.target === this) this.remove();
  });

  const getAvailable = (status) => {
    if (status === 'draft') return stockDraft;
    if (status === 'karantina') return stockQuaran;
    if (status === 'aktif') return stockActive;
    return 0;
  };

  const inputQty   = document.getElementById('inputTrfQty');
  const btnMinus   = document.getElementById('btnTrfMinus');
  const btnPlus    = document.getElementById('btnTrfPlus');
  const btnMax     = document.getElementById('btnTrfMax');
  const noteEl     = document.getElementById('txtTrfAvailableNote');
  const btnSubmit  = document.getElementById('btnSubmitStatusTransfer');

  // Pilih status awal secara cerdas jika user belum memilih (tapi tidak mengunci)
  // Pengguna bebas mengklik kartu mana saja
  let initialFrom = null;
  if (stockQuaran > 0) initialFrom = 'karantina';
  else if (stockDraft > 0) initialFrom = 'draft';
  else if (stockActive > 0) initialFrom = 'aktif';

  if (initialFrom) {
    const rFrom = modal.querySelector(`input[name="trfFrom"][value="${initialFrom}"]`);
    if (rFrom) rFrom.checked = true;

    // Tujuan awal: jika asal karantina, tujuan bisa draft atau aktif
    const defaultTo = initialFrom === 'karantina' ? 'draft' : (initialFrom === 'draft' ? 'aktif' : 'karantina');
    const rTo = modal.querySelector(`input[name="trfTo"][value="${defaultTo}"]`);
    if (rTo) rTo.checked = true;
  }

  function updateCardsVisual() {
    const fromVal = modal.querySelector('input[name="trfFrom"]:checked')?.value || null;
    const toVal   = modal.querySelector('input[name="trfTo"]:checked')?.value || null;

    // 1. Update kartu Sumber (Pindahkan Dari)
    modal.querySelectorAll('#groupFromStatus label').forEach(lbl => {
      const radio = lbl.querySelector('input');
      const st = radio.value;
      const avail = getAvailable(st);
      const isChecked = radio.checked;

      if (avail <= 0) {
        radio.disabled = true;
        lbl.style.opacity = '0.5';
        lbl.style.cursor = 'not-allowed';
        lbl.style.borderColor = 'var(--color-border-subtle)';
        lbl.style.background = '#f9fafb';
      } else {
        radio.disabled = false;
        lbl.style.opacity = '1';
        lbl.style.cursor = 'pointer';
        lbl.style.borderColor = isChecked ? 'var(--color-primary)' : 'var(--color-border)';
        lbl.style.background = isChecked ? '#f0fdf4' : '#fff';
        lbl.style.boxShadow = isChecked ? '0 0 0 1px var(--color-primary)' : 'none';
      }
    });

    // 2. Update kartu Tujuan (Pindahkan Ke)
    modal.querySelectorAll('#groupToStatus label').forEach(lbl => {
      const radio = lbl.querySelector('input');
      const st = radio.value;
      const isSameAsFrom = (fromVal && st === fromVal);
      const isChecked = radio.checked;

      if (isSameAsFrom) {
        radio.disabled = true;
        if (radio.checked) radio.checked = false;
        lbl.style.opacity = '0.4';
        lbl.style.cursor = 'not-allowed';
        lbl.style.borderColor = 'var(--color-border-subtle)';
        lbl.style.background = '#f9fafb';
        lbl.style.boxShadow = 'none';
      } else {
        radio.disabled = false;
        lbl.style.opacity = '1';
        lbl.style.cursor = 'pointer';
        lbl.style.borderColor = isChecked ? 'var(--color-primary)' : 'var(--color-border)';
        lbl.style.background = isChecked ? '#f0fdf4' : '#fff';
        lbl.style.boxShadow = isChecked ? '0 0 0 1px var(--color-primary)' : 'none';
      }
    });

    // 3. Update Stepper Input & Keterangan Maksimal
    if (!fromVal) {
      inputQty.disabled = true;
      btnMinus.disabled = true;
      btnPlus.disabled = true;
      btnMax.disabled = true;
      btnSubmit.disabled = true;
      noteEl.innerHTML = 'Silakan pilih status asal terlebih dahulu.';
      noteEl.style.color = 'var(--color-text-muted)';
      return;
    }

    const currentTo = modal.querySelector('input[name="trfTo"]:checked')?.value || null;
    const maxAvail = getAvailable(fromVal);

    if (maxAvail <= 0) {
      inputQty.value = 0;
      inputQty.disabled = true;
      btnMinus.disabled = true;
      btnPlus.disabled = true;
      btnMax.disabled = true;
      btnSubmit.disabled = true;
      noteEl.innerHTML = `<span style="color:var(--color-danger);font-weight:600;">Tidak ada stok pada status ${fromVal.toUpperCase()} untuk dipindahkan.</span>`;
      return;
    }

    inputQty.disabled = false;
    btnMinus.disabled = false;
    btnPlus.disabled = false;
    btnMax.disabled = false;
    btnSubmit.disabled = !currentTo;

    const fromLabelName = (fromVal === 'draft') ? 'Draft (Tertahan)' : (fromVal === 'karantina' ? 'Karantina (QC)' : 'Aktif (Siap Edar)');
    const toLabelName   = currentTo ? ((currentTo === 'draft') ? 'Draft' : (currentTo === 'karantina' ? 'Karantina' : 'Aktif')) : '(Belum dipilih)';

    noteEl.innerHTML = `Tersedia maksimal: <strong style="color:var(--color-text);">${formatQty(maxAvail)}</strong> ${escapeHtml(unit)} pada status <strong>${fromLabelName}</strong> &rarr; Tujuan: <strong>${toLabelName}</strong>`;
    noteEl.style.color = 'var(--color-text-muted)';

    let curVal = parseFloat(inputQty.value) || 0;
    if (curVal <= 0 || curVal > maxAvail) {
      inputQty.value = maxAvail;
    }
  }

  // Listener Pilihan Sumber (Pindahkan Dari)
  modal.querySelectorAll('input[name="trfFrom"]').forEach(r => {
    r.addEventListener('change', () => {
      const fromVal = r.value;
      const activeTo = modal.querySelector('input[name="trfTo"]:checked')?.value;
      // Jika status tujuan sama dengan status sumber, pindahkan tujuan ke opsi lain yang bukan sama
      if (activeTo === fromVal) {
        const availableOptions = ['aktif', 'draft', 'karantina'].filter(opt => opt !== fromVal);
        const newTo = modal.querySelector(`input[name="trfTo"][value="${availableOptions[0]}"]`);
        if (newTo) newTo.checked = true;
      }
      updateCardsVisual();
    });
  });

  // Listener Pilihan Tujuan (Pindahkan Ke)
  modal.querySelectorAll('input[name="trfTo"]').forEach(r => {
    r.addEventListener('change', () => {
      updateCardsVisual();
    });
  });

  // Tombol Stepper Minus [-]
  btnMinus.addEventListener('click', () => {
    const fromVal = modal.querySelector('input[name="trfFrom"]:checked')?.value;
    if (!fromVal) return;
    const maxAvail = getAvailable(fromVal);
    let val = parseFloat(inputQty.value) || 1;
    val = Math.max(1, val - 1);
    if (val > maxAvail) val = maxAvail;
    inputQty.value = val;
  });

  // Tombol Stepper Plus [+]
  btnPlus.addEventListener('click', () => {
    const fromVal = modal.querySelector('input[name="trfFrom"]:checked')?.value;
    if (!fromVal) return;
    const maxAvail = getAvailable(fromVal);
    let val = parseFloat(inputQty.value) || 0;
    val = Math.min(maxAvail, val + 1);
    if (val < 1 && maxAvail >= 1) val = 1;
    inputQty.value = val;
  });

  // Tombol Stepper [Maksimal]
  btnMax.addEventListener('click', () => {
    const fromVal = modal.querySelector('input[name="trfFrom"]:checked')?.value;
    if (!fromVal) return;
    const maxAvail = getAvailable(fromVal);
    inputQty.value = maxAvail;
  });

  // Input Langsung (Ketikan)
  inputQty.addEventListener('input', () => {
    const fromVal = modal.querySelector('input[name="trfFrom"]:checked')?.value;
    if (!fromVal) return;
    const maxAvail = getAvailable(fromVal);
    let val = parseFloat(inputQty.value);
    if (!isNaN(val)) {
      if (val > maxAvail) inputQty.value = maxAvail;
      if (val < 0) inputQty.value = 0;
    }
  });

  // Sinkronisasi status awal
  updateCardsVisual();

  // Handler Simpan
  btnSubmit.addEventListener('click', () => {
    const fromVal  = modal.querySelector('input[name="trfFrom"]:checked')?.value;
    const toVal    = modal.querySelector('input[name="trfTo"]:checked')?.value;
    const qtyVal   = parseFloat(inputQty.value);
    const notesVal = '';

    if (!fromVal) {
      if (window.InvToast) window.InvToast.show({ type: 'warning', title: 'Periksa Input', message: 'Pilih status asal (pindahkan dari mana).' });
      return;
    }
    if (!toVal) {
      if (window.InvToast) window.InvToast.show({ type: 'warning', title: 'Periksa Input', message: 'Pilih status tujuan (pindahkan ke mana).' });
      return;
    }
    if (fromVal === toVal) {
      if (window.InvToast) window.InvToast.show({ type: 'warning', title: 'Periksa Input', message: 'Status asal dan status tujuan tidak boleh sama.' });
      return;
    }

    const maxAvail = getAvailable(fromVal);
    if (isNaN(qtyVal) || qtyVal <= 0) {
      if (window.InvToast) window.InvToast.show({ type: 'warning', title: 'Jumlah Tidak Valid', message: 'Jumlah barang yang dipindahkan harus lebih dari 0.' });
      inputQty.focus();
      return;
    }
    if (qtyVal > maxAvail) {
      const msg = `Jumlah melebihi stok yang tersedia pada status ${fromVal.toUpperCase()} (${formatQty(maxAvail)} ${unit}).`;
      if (window.InvToast) window.InvToast.show({ type: 'warning', title: 'Stok Tidak Mencukupi', message: msg });
      inputQty.value = maxAvail;
      return;
    }

    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Memproses...';

    fetch('../api/products.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'transfer_status',
        product_id: p.id,
        from_status: fromVal,
        to_status: toVal,
        quantity: qtyVal,
        notes: notesVal
      })
    })
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success') {
        modal.remove();
        if (window.InvToast) {
          window.InvToast.show({
            type: 'success',
            title: 'Status Stok Berhasil Diubah',
            message: res.message
          });
        }
        // Muat ulang data produk agar seluruh angka di halaman langsung terupdate
        loadProductDetail(p.id);
      } else {
        const errMsg = res.message || 'Gagal mengubah status stok.';
        if (window.InvToast) window.InvToast.show({ type: 'danger', title: 'Operasi Gagal', message: errMsg });
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Simpan Perubahan';
      }
    })
    .catch(err => {
      const netMsg = 'Terjadi kesalahan jaringan: ' + err.message;
      if (window.InvToast) window.InvToast.show({ type: 'danger', title: 'Kesalahan Sistem', message: netMsg });
      btnSubmit.disabled = false;
      btnSubmit.textContent = 'Simpan Perubahan';
    });
  });
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

    const retReason = data.notes || 'Pengembalian barang bermasalah ke supplier';

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

const HISTORY_ITEMS_PER_PAGE = 10;
let currentHistoryPage = 1;

function renderProductHistoryTable() {
  if (!currentProductData) return;
  const p = currentProductData;
  const historyList = p.stock_history || [];
  const totalItems = historyList.length;
  const totalPages = Math.ceil(totalItems / HISTORY_ITEMS_PER_PAGE) || 1;

  if (currentHistoryPage > totalPages) currentHistoryPage = totalPages;
  if (currentHistoryPage < 1) currentHistoryPage = 1;

  const start = (currentHistoryPage - 1) * HISTORY_ITEMS_PER_PAGE;
  const end = start + HISTORY_ITEMS_PER_PAGE;
  const pageItems = historyList.slice(start, end);

  const tbody = document.querySelector('.table-container .table tbody');
  if (!tbody) return;

  if (totalItems === 0) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 24px; color: var(--color-text-muted);">Belum ada riwayat transaksi mutasi untuk produk ini.</td></tr>`;
  } else {
    tbody.innerHTML = '';
    pageItems.forEach((h, idx) => {
      const rowNo = start + idx + 1;
      const isIn = h.type === 'IN';
      const refNo = h.ref_no || h.ref_number || '';
      const isDeleted = h.stock_status === 'deleted' || (h.notes && h.notes.includes('[PRODUK DIHAPUS]')) || refNo.startsWith('DEL-');
      const isTransfer = h.type === 'TRANSFER' || h.stock_status === 'transfer' || (h.notes && h.notes.includes('[TRANSFER STATUS')) || refNo.startsWith('TRF-');
      const sign = isIn ? '+' : '-';
      const color = isIn ? 'var(--color-success)' : 'var(--color-danger)';
      const mutasiLabel = isIn ? 'Stok Masuk' : 'Stok Keluar';

      let transferLabel = 'Pergeseran Status';
      let transferQty = h.transfer_qty || 0;
      if (h.notes) {
        const matchLabel = h.notes.match(/\[TRANSFER STATUS:\s*([^\]]+)\]/i);
        if (matchLabel && matchLabel[1]) {
          transferLabel = matchLabel[1].replace(/\s*\([^)]*\)/g, '').replace(/â†’/g, '→').trim();
        }
        if (!transferQty) {
          const matchQty = h.notes.match(/Sebesar\s+([\d\.\,]+)/i);
          if (matchQty && matchQty[1]) transferQty = parseFloat(matchQty[1].replace(',', '.'));
        }
      }
      if (!transferQty && h.quantity > 0) transferQty = h.quantity;

      let badgeHtml = '';
      if (isDeleted) {
        badgeHtml = `<span class="badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-weight:700;"><span class="badge-dot" style="background:#dc2626;"></span>Produk Dihapus</span>`;
      } else if (isTransfer) {
        badgeHtml = `<span class="badge" style="background:#f3e8ff; color:#6b21a8; border:1px solid #d8b4fe; font-weight:700;"><span class="badge-dot" style="background:#9333ea;"></span>🔄 Transfer: ${escapeHtml(transferLabel)}</span>`;
      } else if (!isIn) {
        badgeHtml = `<span class="badge" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; font-weight:600;"><span class="badge-dot" style="background:#dc2626;"></span>Stok Keluar</span>`;
      } else {
        const st = (h.stock_status || '').toLowerCase();
        if (st === 'draft' || (h.notes && h.notes.includes('Status: Draft'))) {
          badgeHtml = `<span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #7dd3fc; font-weight:600;"><span class="badge-dot" style="background:#0284c7;"></span>Masuk: Draft</span>`;
        } else if (st === 'karantina' || (h.notes && h.notes.includes('Status: Karantina'))) {
          badgeHtml = `<span class="badge" style="background:#fef3c7; color:#b45309; border:1px solid #fcd34d; font-weight:600;"><span class="badge-dot" style="background:#d97706;"></span>Masuk: Karantina</span>`;
        } else {
          badgeHtml = `<span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; font-weight:600;"><span class="badge-dot" style="background:#16a34a;"></span>Masuk: Aktif</span>`;
        }
      }

      const petugasDisplay = escapeHtml(sanitizeOfficerName(h.performed_by_name || 'Petugas Gudang'));
      const unitDisplay = escapeHtml(p.unit || 'unit');

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
          <span style="font-weight: bold; color: ${color};">${sign}${formatQty(h.quantity)} ${unitDisplay}</span>
          <div style="font-size: 11px; font-weight: normal; color: var(--color-text-muted);">${mutasiLabel}</div>
        `;
      }

      // Hitung Stok Awal dan Stok Akhir secara matematis
      const balStock = (h.balance_stock !== undefined && h.balance_stock !== null) ? parseFloat(h.balance_stock) : null;
      let stokAwal = null;
      let stokAkhir = balStock;

      if (isDeleted) {
        stokAwal = 0;
        stokAkhir = 0;
      } else if (isTransfer) {
        stokAwal = balStock;
        stokAkhir = balStock;
      } else if (isIn) {
        stokAwal = balStock !== null ? Math.max(0, balStock - parseFloat(h.quantity || 0)) : null;
      } else {
        stokAwal = balStock !== null ? balStock + parseFloat(h.quantity || 0) : null;
      }

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="text-align: center; color: var(--color-text-muted); font-weight: 500;">${rowNo}</td>
        <td>${formatDate(h.date)}</td>
        <td>
          <strong>${escapeHtml(p.name)}</strong> ${isDeleted ? `<span class="badge" style="font-size: 10px; padding: 1px 5px; background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">Nonaktif</span>` : ''}
          <br><span class="table-sku">${escapeHtml(p.sku)}</span>
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
          <span style="font-family: var(--font-mono); font-weight: 600;">${escapeHtml((h.ref_no || '-').replace(/^SO-SO-/, 'SO-'))}</span>
          ${h.partner_name ? `<div style="font-size: 11px; color: var(--color-text-muted);">${escapeHtml(h.partner_name)}</div>` : ''}
        </td>
        <td>${petugasDisplay}</td>
        <td style="text-align: center;">
          <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 12px;">Detail</button>
        </td>
      `;

      tr.querySelector('button').addEventListener('click', () => {
        showHistoryDetailModal(h, p);
      });

      tbody.appendChild(tr);
    });
  }

  // Counter informasi jumlah data
  const countEl = document.getElementById('product-history-count');
  if (countEl) {
    if (totalItems === 0) {
      countEl.innerHTML = 'Tidak ada riwayat mutasi';
    } else {
      const from = start + 1;
      const to = Math.min(end, totalItems);
      countEl.innerHTML = `Menampilkan <strong>${from} - ${to}</strong> dari <strong>${totalItems}</strong> total mutasi`;
    }
  }

  // Render kontrol pagination
  renderHistoryPaginationControls(totalPages, currentHistoryPage);
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

function renderHistoryPaginationControls(totalPages, page) {
  const container = document.getElementById('productHistoryPagination');
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
      if (!isNaN(targetPage) && targetPage !== currentHistoryPage && targetPage >= 1 && targetPage <= totalPages) {
        currentHistoryPage = targetPage;
        renderProductHistoryTable();
      }
    });
  });
}

function showHistoryDetailModal(h, p) {
  const data = {
    ...h,
    product_name: p.name,
    sku: p.sku,
    unit: p.unit,
  };
  showUnifiedMovementDetail(data);
}

function formatRupiah(val) {
  const num = parseFloat(val) || 0;
  return 'Rp' + num.toLocaleString('id-ID');
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
