/**
 * Handler Halaman Manajemen Kategori Barang Inventoria
 * Terintegrasi langsung dengan database MySQL (tabel `categories` dan relasi `products`)
 * Versi: 2.0 (Enterprise Table View, Pagination, Product Viewer, & Audit Trail Soft-Delete)
 */

document.addEventListener('DOMContentLoaded', function() {
  initCategoriesPage();
});

let allCategoriesList = [];
let editingCategoryId = null;
let currentCategoryPage = 1;
const categoriesPerPage = 10;
let pendingDeleteCategory = null;

// State untuk Modal Riwayat Kategori
let allDeletedCategoriesList = [];
let currentDeletedCategoryPage = 1;
const deletedCategoriesPerPage = 10;

function initCategoriesPage() {
  loadCategoriesFromDb();

  // Tombol Tambah Kategori
  const addBtn = document.getElementById('btn-add-category');
  if (addBtn) {
    addBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openCategoryFormModal();
    });
  }

  // Tombol Lihat Riwayat Kategori
  const viewDeletedBtn = document.getElementById('btn-view-deleted-categories');
  if (viewDeletedBtn) {
    viewDeletedBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openDeletedCategoriesModal();
    });
  }

  // Form Submit Tambah / Edit Kategori
  const form = document.getElementById('categoryModalForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      saveCategory();
    });
  }

  // Form Submit Hapus Kategori (Wajib Alasan)
  const deleteForm = document.getElementById('deleteCategoryForm');
  if (deleteForm) {
    deleteForm.addEventListener('submit', function(e) {
      e.preventDefault();
      confirmExecuteDeleteCategory();
    });
  }

  // Auto-uppercase Kode Kategori
  const codeInput = document.getElementById('cat_code');
  if (codeInput) {
    codeInput.addEventListener('input', function() {
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
    });
  }

  // Tombol Alert Modal
  const alertBtn = document.getElementById('categoryAlertBtn');
  if (alertBtn) {
    alertBtn.addEventListener('click', function() {
      closeCategoryAlert();
    });
  }

  // Tutup modal saat klik luar overlay
  document.addEventListener('click', function(e) {
    const formModal = document.getElementById('categoryFormModal');
    if (formModal && e.target === formModal) {
      closeCategoryFormModal();
    }
    const prodModal = document.getElementById('categoryProductsModal');
    if (prodModal && e.target === prodModal) {
      closeCategoryProductsModal();
    }
    const delModal = document.getElementById('deleteCategoryModal');
    if (delModal && e.target === delModal) {
      closeDeleteCategoryModal();
    }
    const historyModal = document.getElementById('deletedCategoriesModal');
    if (historyModal && e.target === historyModal) {
      closeDeletedCategoriesModal();
    }
    const alertModal = document.getElementById('categoryAlertModal');
    if (alertModal && e.target === alertModal) {
      closeCategoryAlert();
    }
  });
}

// ==========================================================================
// 1. Ambil Data Kategori dari Database
// ==========================================================================
function loadCategoriesFromDb() {
  const tableBody = document.querySelector('#categoriesTable tbody');

  if (tableBody) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="6" style="text-align: center; padding: 28px; color: var(--color-text-muted);">
          Memuat data rincian kategori dari database...
        </td>
      </tr>
    `;
  }

  fetch('../api/categories.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        allCategoriesList = res.data;
        renderCategoriesTable();
      } else {
        const errMsg = res.message || 'Terjadi kesalahan saat memuat data.';
        if (tableBody) {
          tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--color-danger); padding: 24px;">Gagal: ${escapeHtml(errMsg)}</td></tr>`;
        }
      }
    })
    .catch(err => {
      if (tableBody) {
        tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--color-danger); padding: 24px;">Gagal koneksi: ${escapeHtml(err.message)}</td></tr>`;
      }
    });
}

// ==========================================================================
// 2. Render Tabel Master Rincian Kategori & Paginasi
// ==========================================================================
function renderCategoriesTable() {
  const tbody = document.querySelector('#categoriesTable tbody');
  if (!tbody) return;

  if (allCategoriesList.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" style="text-align: center; padding: 28px; color: var(--color-text-muted);">
          Belum ada master kategori tersimpan. Klik <strong>Tambah Kategori</strong> untuk membuat klasifikasi barang baru.
        </td>
      </tr>
    `;
    renderCategoriesPagination(1, 1);
    return;
  }

  const totalPages = Math.ceil(allCategoriesList.length / categoriesPerPage) || 1;
  if (currentCategoryPage > totalPages) {
    currentCategoryPage = totalPages;
  }

  const startIndex = (currentCategoryPage - 1) * categoriesPerPage;
  const pageItems = allCategoriesList.slice(startIndex, startIndex + categoriesPerPage);

  tbody.innerHTML = '';
  pageItems.forEach((cat, index) => {
    const rowNo = startIndex + index + 1;
    const prodCount = cat.product_count || (cat.products ? cat.products.length : 0);

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="text-align: center; font-size: 12px; color: var(--color-text-muted); font-weight: 500;">${rowNo}</td>
      <td>
        <span class="table-sku" style="font-family: var(--font-mono); font-weight: 600;">${escapeHtml(cat.code)}</span>
      </td>
      <td>
        <div class="text-bold" style="font-size: var(--font-size-sm); color: var(--color-text-emphasis);">${escapeHtml(cat.name)}</div>
      </td>
      <td style="font-size: 13px; color: var(--color-text-muted); line-height: 1.4;">
        ${escapeHtml(cat.description || '-')}
      </td>
      <td style="text-align: center;">
        <span class="badge" style="font-size: 12px; font-weight: 600; padding: 3px 9px; background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1;">
          ${prodCount} item
        </span>
      </td>
      <td style="text-align: right;">
        <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
          <button type="button" class="btn btn-secondary btn-sm" onclick="viewCategoryProducts(${cat.id})" title="Lihat daftar produk dalam kategori ini" style="display: inline-flex; align-items: center; gap: 4px;">
            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            <span>Produk</span>
          </button>
          ${window.isInventoryAdmin === false ? '' : `
          <button type="button" class="btn btn-secondary btn-sm" onclick="editCategory(${cat.id})" title="Edit Data Kategori">
            Edit
          </button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="openDeleteCategoryModal(${cat.id})" title="Hapus Kategori" style="color: var(--color-danger);">
            Hapus
          </button>
          `}
        </div>
      </td>
    `;
    tbody.appendChild(tr);
  });

  renderCategoriesPagination(totalPages, currentCategoryPage);
}

function getPaginationRange(totalPages, page) {
  if (totalPages <= 5) {
    const range = [];
    for (let i = 1; i <= totalPages; i++) range.push(i);
    return range;
  }
  if (page <= 2) {
    return [1, 2, 3, '...', totalPages];
  }
  if (page === 3) {
    return [1, 2, 3, 4, '...', totalPages];
  }
  if (page >= totalPages - 1) {
    return [1, '...', totalPages - 2, totalPages - 1, totalPages];
  }
  if (page === totalPages - 2) {
    return [1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
  }
  return [1, '...', page - 1, page, page + 1, '...', totalPages];
}

function renderCategoriesPagination(totalPages, page) {
  const container = document.getElementById('categoriesPaginationControls');
  const wrapper = document.getElementById('categoriesPaginationWrapper');
  if (!container || !wrapper) return;

  if (totalPages < 1) {
    wrapper.style.display = 'none';
    container.innerHTML = '';
    return;
  }
  wrapper.style.display = 'flex';

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
      if (!isNaN(targetPage) && targetPage !== currentCategoryPage && targetPage >= 1 && targetPage <= totalPages) {
        currentCategoryPage = targetPage;
        renderCategoriesTable();
        document.querySelector('.table-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
}

// ==========================================================================
// 3. Modal Daftar Produk Kategori (Menjawab: "Mau lihat produknya apa aja")
// ==========================================================================
function viewCategoryProducts(catId) {
  const cat = allCategoriesList.find(c => c.id == catId);
  const modal = document.getElementById('categoryProductsModal');
  const titleEl = document.getElementById('categoryProductsModalTitle');
  const subEl = document.getElementById('categoryProductsModalSubtitle');
  const tbody = document.getElementById('categoryProductsTableBody');
  const summaryEl = document.getElementById('categoryProductsSummary');

  if (!modal || !tbody) return;

  const catName = cat ? cat.name : 'Kategori';
  const catCode = cat ? cat.code : '';
  const products = (cat && Array.isArray(cat.products)) ? cat.products : [];

  if (titleEl) titleEl.textContent = `Daftar Produk: ${catName}`;
  if (subEl) subEl.textContent = `Kode Kategori: ${catCode} • Total terdata: ${products.length} produk aktif di gudang.`;
  if (summaryEl) summaryEl.textContent = `Menampilkan ${products.length} produk`;

  if (products.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align: center; padding: 32px; color: var(--color-text-muted);">
          Belum ada produk yang terdaftar dalam kategori ini.
        </td>
      </tr>
    `;
  } else {
    tbody.innerHTML = '';
    products.forEach((prod, index) => {
      const stockVal = parseFloat(prod.stock || 0);
      let stockBadgeColor = '#15803d';
      let stockBadgeBg = '#dcfce7';
      if (stockVal <= 0) {
        stockBadgeColor = '#dc2626';
        stockBadgeBg = '#fee2e2';
      } else if (stockVal <= 5) {
        stockBadgeColor = '#b45309';
        stockBadgeBg = '#fef3c7';
      }

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="text-align: center; font-size: 12px; color: var(--color-text-muted);">${index + 1}</td>
        <td>
          <span class="table-sku" style="font-family: var(--font-mono); font-weight: 600;">${escapeHtml(prod.sku)}</span>
        </td>
        <td>
          <div class="text-bold" style="color: var(--color-text-emphasis);">${escapeHtml(prod.name)}</div>
        </td>
        <td style="font-size: 12px; color: var(--color-text-muted);">${escapeHtml(prod.location || '-')}</td>
        <td style="text-align: right;">
          <span style="font-weight: 700; color: ${stockBadgeColor}; background: ${stockBadgeBg}; padding: 2px 7px; border-radius: 4px; font-size: 12px;">
            ${formatStockQty(stockVal)}
          </span>
        </td>
        <td style="text-align: center; font-size: 12px; color: var(--color-text-muted);">${escapeHtml(prod.unit || 'unit')}</td>
        <td style="text-align: center;">
          <a href="product-detail.html?id=${prod.id}" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 11px;" title="Lihat detail spesifikasi produk">
            Detail
          </a>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  modal.style.display = 'flex';
}

function closeCategoryProductsModal() {
  const modal = document.getElementById('categoryProductsModal');
  if (modal) modal.style.display = 'none';
}

// ==========================================================================
// 4. Modal Tambah / Edit Kategori
// ==========================================================================
function openCategoryFormModal(category = null) {
  if (window.isInventoryAdmin === false) {
    if (window.InvToast) {
      window.InvToast.show({
        type: 'warning',
        title: 'Akses Dibatasi',
        message: 'Pengelolaan kategori barang hanya dapat dilakukan oleh Admin Gudang.',
        duration: 4000
      });
    }
    return;
  }
  const modal = document.getElementById('categoryFormModal');
  if (!modal) return;

  editingCategoryId = category ? category.id : null;
  const titleEl = document.getElementById('categoryFormModalTitle');
  if (titleEl) {
    titleEl.textContent = category ? 'Edit Data Kategori Barang' : 'Tambah Kategori Barang Baru';
  }

  document.getElementById('cat_code').value = category ? (category.code || '') : '';
  document.getElementById('cat_name').value = category ? (category.name || '') : '';
  document.getElementById('cat_desc').value = category ? (category.description || '') : '';

  const reasonGroup = document.getElementById('cat_reason')?.closest('.form-group');
  if (reasonGroup) {
    reasonGroup.style.display = category ? 'none' : 'block';
  }
  const reasonInput = document.getElementById('cat_reason');
  if (reasonInput) reasonInput.value = '';

  modal.style.display = 'flex';
  setTimeout(() => {
    const focusEl = category ? document.getElementById('cat_name') : document.getElementById('cat_code');
    if (focusEl) focusEl.focus();
  }, 100);
}

function closeCategoryFormModal() {
  const modal = document.getElementById('categoryFormModal');
  if (modal) modal.style.display = 'none';
  editingCategoryId = null;
}

function editCategory(id) {
  const cat = allCategoriesList.find(c => c.id == id);
  if (cat) {
    openCategoryFormModal(cat);
  } else {
    fetch(`../api/categories.php?id=${id}`)
      .then(res => res.json())
      .then(res => {
        if (res.status === 'success' && res.data) {
          openCategoryFormModal(res.data);
        } else {
          showCategoryAlert({
            type: 'warning',
            title: 'Kategori Tidak Ditemukan',
            message: 'Data kategori tidak ditemukan di database.'
          });
        }
      });
  }
}

function saveCategory() {
  const codeVal = document.getElementById('cat_code')?.value.trim().toUpperCase() || '';
  const nameVal = document.getElementById('cat_name')?.value.trim() || '';
  const descVal = document.getElementById('cat_desc')?.value.trim() || '';

  // Validasi Kode
  if (!codeVal) {
    showCategoryAlert({
      type: 'warning',
      title: 'Validasi Input',
      message: 'Kode kategori wajib diisi (contoh: CAT-EL).'
    });
    document.getElementById('cat_code')?.focus();
    return;
  }
  const codeRegex = /^[A-Z0-9\-]{2,20}$/;
  if (!codeRegex.test(codeVal)) {
    showCategoryAlert({
      type: 'warning',
      title: 'Format Kode Tidak Valid',
      message: 'Kode kategori harus berisikan 2-20 karakter huruf kapital, angka, atau tanda hubung (-) (contoh: CAT-EL).'
    });
    document.getElementById('cat_code')?.focus();
    return;
  }

  // Validasi Nama
  if (!nameVal) {
    showCategoryAlert({
      type: 'warning',
      title: 'Validasi Input',
      message: 'Nama kategori komoditas wajib diisi.'
    });
    document.getElementById('cat_name')?.focus();
    return;
  }

  // Validasi Deskripsi
  if (!descVal || descVal.length < 4) {
    showCategoryAlert({
      type: 'warning',
      title: 'Validasi Input',
      message: 'Deskripsi pengelompokan kategori wajib diisi lengkap.'
    });
    document.getElementById('cat_desc')?.focus();
    return;
  }

  const submitBtn = document.getElementById('btn-submit-category');
  const originalHtml = submitBtn ? submitBtn.innerHTML : '<span>Simpan Data</span>';
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>Menyimpan...</span>';
  }

  const reasonVal = document.getElementById('cat_reason')?.value.trim() || '';

  const isEdit = !!editingCategoryId;
  const url = isEdit ? `../api/categories.php?id=${editingCategoryId}` : '../api/categories.php';
  const method = isEdit ? 'PUT' : 'POST';

  fetch(url, {
    method: method,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      code:        codeVal,
      name:        nameVal,
      description: descVal,
      reason:      reasonVal,
    })
  })
  .then(res => res.json())
  .then(res => {
    if (res.status === 'success') {
      closeCategoryFormModal();
      showCategoryAlert({
        type: 'success',
        title: isEdit ? 'Kategori Diperbarui' : 'Kategori Ditambahkan',
        message: res.message || 'Data kategori berhasil disimpan.'
      });
      loadCategoriesFromDb();
    } else {
      showCategoryAlert({
        type: 'danger',
        title: 'Gagal Menyimpan',
        message: res.message || 'Terjadi kesalahan sistem saat menyimpan kategori.'
      });
    }
  })
  .catch(err => {
    showCategoryAlert({
      type: 'danger',
      title: 'Kesalahan Jaringan',
      message: err.message
    });
  })
  .finally(() => {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalHtml;
    }
  });
}

// ==========================================================================
// 5. Modal Konfirmasi Hapus Kategori (Wajib Alasan & Proteksi Produk)
// ==========================================================================
function openDeleteCategoryModal(id) {
  if (window.isInventoryAdmin === false) {
    if (window.InvToast) {
      window.InvToast.show({
        type: 'warning',
        title: 'Akses Dibatasi',
        message: 'Penghapusan kategori barang hanya dapat dilakukan oleh Admin Gudang.',
        duration: 4000
      });
    }
    return;
  }
  const cat = allCategoriesList.find(c => c.id == id);
  if (!cat) return;

  pendingDeleteCategory = cat;

  const modal = document.getElementById('deleteCategoryModal');
  const nameEl = document.getElementById('deleteCategoryTargetName');
  const codeEl = document.getElementById('deleteCategoryTargetCode');
  const warnBox = document.getElementById('deleteCategoryWarningBox');
  const reasonGroup = document.getElementById('deleteCategoryReasonGroup');
  const reasonInput = document.getElementById('deleteCategoryReasonInput');
  const confirmBtn = document.getElementById('btn-confirm-delete-category');

  if (nameEl) nameEl.textContent = cat.name;
  if (codeEl) codeEl.textContent = cat.code;
  if (reasonInput) reasonInput.value = '';

  const prodCount = cat.product_count || (cat.products ? cat.products.length : 0);

  if (warnBox && confirmBtn) {
    if (prodCount > 0) {
      warnBox.style.background = '#fef2f2';
      warnBox.style.borderColor = '#fee2e2';
      warnBox.style.color = '#991b1b';
      warnBox.innerHTML = `
        <strong>⚠️ Peringatan Integritas Data:</strong> Kategori ini saat ini masih memuat <strong>${prodCount} produk aktif</strong> di gudang. Untuk mencegah kerusakan katalog inventaris, kategori ini <strong>tidak dapat dihapus</strong> sebelum produk terkait dialihkan ke kategori lain atau dinonaktifkan.
      `;
      if (reasonGroup) reasonGroup.style.display = 'none';
      confirmBtn.disabled = true;
      confirmBtn.style.opacity = '0.5';
      confirmBtn.style.cursor = 'not-allowed';
      confirmBtn.title = 'Kategori masih memuat produk aktif';
    } else {
      warnBox.style.background = '#f0fdf4';
      warnBox.style.borderColor = '#bbf7d0';
      warnBox.style.color = '#166534';
      warnBox.innerHTML = `
        <strong>✓ Aman Dihapus:</strong> Kategori ini tidak memiliki produk aktif yang terdaftar (0 produk). Silakan tuliskan alasan penghapusan di bawah ini.
      `;
      if (reasonGroup) reasonGroup.style.display = 'block';
      confirmBtn.disabled = false;
      confirmBtn.style.opacity = '1';
      confirmBtn.style.cursor = 'pointer';
      confirmBtn.title = '';
      setTimeout(() => {
        if (reasonInput) reasonInput.focus();
      }, 100);
    }
  }

  if (modal) modal.style.display = 'flex';
}

function closeDeleteCategoryModal() {
  const modal = document.getElementById('deleteCategoryModal');
  if (modal) modal.style.display = 'none';
  pendingDeleteCategory = null;
}

function confirmExecuteDeleteCategory() {
  if (!pendingDeleteCategory) return;

  const prodCount = pendingDeleteCategory.product_count || (pendingDeleteCategory.products ? pendingDeleteCategory.products.length : 0);
  if (prodCount > 0) {
    showCategoryAlert({
      type: 'warning',
      title: 'Tidak Dapat Dihapus',
      message: `Kategori '${pendingDeleteCategory.name}' masih memuat ${prodCount} produk aktif. Kosongkan produk terlebih dahulu.`
    });
    return;
  }

  const reasonInput = document.getElementById('deleteCategoryReasonInput');
  const reason = reasonInput ? reasonInput.value.trim() : '';

  if (!reason || reason.length < 4) {
    showCategoryAlert({
      type: 'warning',
      title: 'Alasan Wajib Diisi',
      message: 'Mohon tuliskan alasan penghapusan kategori secara jelas (minimal 4 karakter) untuk kebutuhan audit trail.'
    });
    if (reasonInput) reasonInput.focus();
    return;
  }

  const confirmBtn = document.getElementById('btn-confirm-delete-category');
  const originalHtml = confirmBtn ? confirmBtn.innerHTML : '<span>Ya, Hapus Kategori</span>';
  if (confirmBtn) {
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span>Menghapus...</span>';
  }

  fetch(`../api/categories.php?id=${pendingDeleteCategory.id}`, {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ reason: reason })
  })
  .then(res => res.json())
  .then(res => {
    if (res.status === 'success') {
      closeDeleteCategoryModal();
      showCategoryAlert({
        type: 'success',
        title: 'Kategori Berhasil Dihapus',
        message: res.message || 'Kategori berhasil dihapus dan tercatat dalam audit log.'
      });
      loadCategoriesFromDb();
    } else {
      showCategoryAlert({
        type: 'danger',
        title: 'Gagal Menghapus',
        message: res.message || 'Terjadi kesalahan sistem saat menghapus kategori.'
      });
    }
  })
  .catch(err => {
    showCategoryAlert({
      type: 'danger',
      title: 'Kesalahan Jaringan',
      message: err.message
    });
  })
  .finally(() => {
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = originalHtml;
    }
  });
}

// ==========================================================================
// 6. Modal Riwayat Kategori (Audit Trail Berhalaman)
// ==========================================================================
function openDeletedCategoriesModal() {
  const modal = document.getElementById('deletedCategoriesModal');
  const tbody = document.getElementById('deletedCategoriesTableBody');
  if (!modal || !tbody) return;

  modal.style.display = 'flex';
  tbody.innerHTML = `
    <tr>
      <td colspan="7" style="text-align: center; padding: 24px; color: var(--color-text-muted);">
        Memuat data riwayat kategori...
      </td>
    </tr>
  `;

  currentDeletedCategoryPage = 1;

  fetch('../api/categories.php?include_deleted=1')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        allDeletedCategoriesList = res.data;
        renderDeletedCategoriesTablePage();
      } else {
        tbody.innerHTML = `
          <tr>
            <td colspan="7" style="text-align: center; color: var(--color-danger); padding: 20px;">
              Gagal memuat log: ${res.message || 'Terjadi kesalahan'}
            </td>
          </tr>
        `;
      }
    })
    .catch(err => {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" style="text-align: center; color: var(--color-danger); padding: 20px;">
            Gagal koneksi: ${err.message}
          </td>
        </tr>
      `;
    });
}

function closeDeletedCategoriesModal() {
  const modal = document.getElementById('deletedCategoriesModal');
  if (modal) modal.style.display = 'none';
}

function renderDeletedCategoriesTablePage() {
  const tbody = document.getElementById('deletedCategoriesTableBody');
  if (!tbody) return;

  const totalPages = Math.ceil(allDeletedCategoriesList.length / deletedCategoriesPerPage) || 1;
  if (currentDeletedCategoryPage > totalPages) {
    currentDeletedCategoryPage = totalPages;
  }

  if (allDeletedCategoriesList.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align: center; padding: 28px; color: var(--color-text-muted);">
          Belum ada riwayat aktivitas kategori barang.
        </td>
      </tr>
    `;
    renderDeletedCategoriesPaginationControls(1, 1);
    return;
  }

  const startIndex = (currentDeletedCategoryPage - 1) * deletedCategoriesPerPage;
  const pageItems = allDeletedCategoriesList.slice(startIndex, startIndex + deletedCategoriesPerPage);

  tbody.innerHTML = '';
  pageItems.forEach((item, index) => {
    const rowNo = startIndex + index + 1;
    const isCreate = (item.action_type || '').toUpperCase() === 'CREATE';
    const formattedDate = formatDateTimeId(item.action_date || item.deleted_at);
    const actorName = escapeHtml(item.deleted_by_name || 'Petugas');
    const actorRoleRaw = item.deleted_by_role || '';
    const actorRoleFormatted = escapeHtml(formatRoleWithDepartment(actorRoleRaw));
    const reasonText = escapeHtml(item.deletion_reason || (isCreate ? 'Tidak ada alasan khusus' : 'Dihapus dari sistem'));

    // Badge Jenis Aksi
    const actionBadge = isCreate
      ? `<span class="badge" style="font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; gap: 4px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Tambah Kategori</span>`
      : `<span class="badge" style="font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; display: inline-flex; align-items: center; gap: 4px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line></svg> Hapus Kategori</span>`;

    // Style box alasan / keterangan
    const noteStyle = isCreate
      ? `background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;`
      : `background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b;`;

    // Warna badge role
    let roleBadgeBg = '#f1f5f9';
    let roleBadgeColor = '#475569';
    let roleBadgeBorder = '#cbd5e1';

    const upperRole = actorRoleRaw.toUpperCase();
    if (upperRole.includes('WAREHOUSE')) {
      roleBadgeBg = '#e0f2fe';
      roleBadgeColor = '#0369a1';
      roleBadgeBorder = '#bae6fd';
    } else if (upperRole.includes('PURCHAS')) {
      roleBadgeBg = '#fef3c7';
      roleBadgeColor = '#92400e';
      roleBadgeBorder = '#fde68a';
    } else if (upperRole.includes('ADMIN')) {
      roleBadgeBg = '#ede9fe';
      roleBadgeColor = '#5b21b6';
      roleBadgeBorder = '#ddd6fe';
    }

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="text-align: center; font-size: 11px; color: var(--color-text-muted);">${rowNo}</td>
      <td style="text-align: center;">${actionBadge}</td>
      <td>
        <span class="table-sku" style="font-family: var(--font-mono); font-weight: 600; font-size: 11px;">${escapeHtml(item.code)}</span>
      </td>
      <td>
        <div class="text-bold" style="color: var(--color-text-emphasis);">${escapeHtml(item.name)}</div>
      </td>
      <td style="white-space: nowrap; color: var(--color-text-muted); font-size: 11px;">${formattedDate}</td>
      <td>
        <div style="font-weight: 600; color: #0f172a; font-size: 13px;">${actorName}</div>
        <div style="margin-top: 3px;">
          <span class="badge" style="font-size: 10px; font-weight: 600; padding: 2px 7px; background: ${roleBadgeBg}; color: ${roleBadgeColor}; border: 1px solid ${roleBadgeBorder};">
            ${actorRoleFormatted}
          </span>
        </div>
      </td>
      <td style="color: #334155; line-height: 1.4;">
        <span style="display: inline-block; border-radius: 4px; padding: 2px 7px; font-size: 11px; ${noteStyle}">
          ${reasonText}
        </span>
      </td>
    `;
    tbody.appendChild(tr);
  });

  renderDeletedCategoriesPaginationControls(totalPages, currentDeletedCategoryPage);
}

function renderDeletedCategoriesPaginationControls(totalPages, page) {
  const container = document.getElementById('deletedCategoriesPaginationControls');
  const wrapper = document.getElementById('deletedCategoriesPaginationWrapper');
  if (!container || !wrapper) return;

  if (totalPages < 1) {
    wrapper.style.display = 'none';
    container.innerHTML = '';
    return;
  }
  wrapper.style.display = 'flex';

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
      if (!isNaN(targetPage) && targetPage !== currentDeletedCategoryPage && targetPage >= 1 && targetPage <= totalPages) {
        currentDeletedCategoryPage = targetPage;
        renderDeletedCategoriesTablePage();
      }
    });
  });
}

// ==========================================================================
// 7. Modal Alert Universal Interaktif
// ==========================================================================
let alertCloseCallback = null;

function showCategoryAlert({ type = 'info', title = 'Notifikasi', message = '', buttonText = 'Mengerti', onClose = null }) {
  const modal = document.getElementById('categoryAlertModal');
  const iconEl = document.getElementById('categoryAlertIcon');
  const titleEl = document.getElementById('categoryAlertTitle');
  const msgEl = document.getElementById('categoryAlertMessage');
  const btnEl = document.getElementById('categoryAlertBtn');

  if (!modal || !iconEl || !titleEl || !msgEl || !btnEl) {
    if (window.InvToast) {
      window.InvToast.show({ type: type === 'danger' ? 'danger' : (type === 'success' ? 'success' : 'warning'), title, message });
    }
    return;
  }

  alertCloseCallback = onClose;

  titleEl.textContent = title;
  msgEl.textContent = message;
  btnEl.textContent = buttonText;

  if (type === 'success') {
    iconEl.style.background = '#dcfce7';
    iconEl.style.color = '#15803d';
    iconEl.innerHTML = `<svg viewBox="0 0 24 24" width="26" height="26" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
    btnEl.className = 'btn btn-primary';
    btnEl.style.backgroundColor = '#16a34a';
    btnEl.style.borderColor = '#16a34a';
  } else if (type === 'danger') {
    iconEl.style.background = '#fee2e2';
    iconEl.style.color = '#dc2626';
    iconEl.innerHTML = `<svg viewBox="0 0 24 24" width="26" height="26" stroke="currentColor" stroke-width="2.5" fill="none"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
    btnEl.className = 'btn btn-primary';
    btnEl.style.backgroundColor = '#dc2626';
    btnEl.style.borderColor = '#dc2626';
  } else if (type === 'warning') {
    iconEl.style.background = '#fef3c7';
    iconEl.style.color = '#d97706';
    iconEl.innerHTML = `<svg viewBox="0 0 24 24" width="26" height="26" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
    btnEl.className = 'btn btn-primary';
    btnEl.style.backgroundColor = '#d97706';
    btnEl.style.borderColor = '#d97706';
  } else {
    iconEl.style.background = '#e0f2fe';
    iconEl.style.color = '#0284c7';
    iconEl.innerHTML = `<svg viewBox="0 0 24 24" width="26" height="26" stroke="currentColor" stroke-width="2.5" fill="none"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
    btnEl.className = 'btn btn-primary';
    btnEl.style.backgroundColor = '#0284c7';
    btnEl.style.borderColor = '#0284c7';
  }

  modal.style.display = 'flex';
  btnEl.focus();
}

function closeCategoryAlert() {
  const modal = document.getElementById('categoryAlertModal');
  if (modal) modal.style.display = 'none';
  if (typeof alertCloseCallback === 'function') {
    const cb = alertCloseCallback;
    alertCloseCallback = null;
    cb();
  }
}

// ==========================================================================
// Helper Utilities & Role Formatter
// ==========================================================================
function formatRoleWithDepartment(roleStr) {
  if (!roleStr) return 'Petugas Sistem';
  const role = roleStr.toUpperCase().trim();
  if (role === 'ADMIN_WAREHOUSE') {
    return 'Admin Gudang (Divisi Inventory)';
  }
  if (role === 'WAREHOUSE_STAFF') {
    return 'Staff Gudang (Divisi Inventory)';
  }
  if (role === 'ADMIN_PURCHASE' || role === 'ADMIN_PURCHASING') {
    return 'Admin Purchasing (Divisi Pembelian)';
  }
  if (role === 'PURCHASING_STAFF' || role === 'PURCHASING') {
    return 'Staff Purchasing (Divisi Pembelian)';
  }
  if (role === 'ADMIN_SALES') {
    return 'Admin Sales (Divisi Penjualan)';
  }
  if (role === 'MARKETING_STAFF' || role === 'SALES') {
    return 'Staff Sales & Marketing';
  }
  if (role === 'ADMIN_HR' || role === 'HR') {
    return 'Admin HRD (Personalia)';
  }
  if (role === 'FINANCE') {
    return 'Finance (Divisi Keuangan)';
  }
  if (role === 'MANAGER') {
    return 'Manager Operasional';
  }
  if (role === 'ADMIN') {
    return 'Super Administrator';
  }
  return roleStr.replace(/_/g, ' ');
}

function formatStockQty(val) {
  if (val === null || val === undefined || isNaN(val)) return '0';
  const num = parseFloat(val);
  return Number.isInteger(num) ? num.toString() : num.toFixed(2);
}

function formatDateTimeId(dateStr) {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const month = monthNames[d.getMonth()];
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const mins = String(d.getMinutes()).padStart(2, '0');
    return `${day} ${month} ${year}, ${hours}:${mins}`;
  } catch (e) {
    return dateStr;
  }
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
