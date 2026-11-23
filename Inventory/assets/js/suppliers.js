/**
 * Handler Halaman Master Supplier Dinamis dari Database MySQL
 * Terhubung langsung dengan modul Purchasing (tabel ERP `suppliers`)
 * Proyek: Inventoria (database hris)
 * Versi: 2.2 (Enterprise Upgrade - Pagination Identik Riwayat Stok & Modal Riwayat Supplier Berhalaman)
 */

document.addEventListener('DOMContentLoaded', function() {
  initSuppliersPage();
});

let allSuppliersList = [];
let filteredSuppliersList = [];
let editingSupplierId = null;
let currentSupplierPage = 1;
const suppliersPerPage = 10;
let pendingDeleteSupplier = null;

// State untuk Modal Riwayat Supplier
let allDeletedSuppliersList = [];
let currentDeletedSupplierPage = 1;
const deletedSuppliersPerPage = 10;

function initSuppliersPage() {
  loadSuppliersFromDb();

  // Filter Search
  const searchInput = document.querySelector('.filter-input');
  if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', function() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        currentSupplierPage = 1;
        filterSuppliers();
      }, 250);
    });
  }

  // Filter Status
  const statusSelect = document.querySelector('select[aria-label="Filter Status Pemasok"]');
  if (statusSelect) {
    statusSelect.addEventListener('change', function() {
      currentSupplierPage = 1;
      filterSuppliers();
    });
  }

  // Tombol Tambah Supplier
  const addBtn = document.getElementById('btn-add-supplier');
  if (addBtn) {
    addBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openSupplierModal();
    });
  }

  // Tombol Lihat Riwayat Supplier
  const viewDeletedBtn = document.getElementById('btn-view-deleted-suppliers');
  if (viewDeletedBtn) {
    viewDeletedBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openDeletedSuppliersModal();
    });
  }

  // Auto-uppercase & Sanitize Kode Supplier Input
  const codeInput = document.getElementById('sup_code');
  if (codeInput) {
    codeInput.addEventListener('input', function() {
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
    });
  }

  // Modal Form Submit (Tambah / Edit)
  const supplierForm = document.getElementById('supplierModalForm');
  if (supplierForm) {
    supplierForm.addEventListener('submit', function(e) {
      e.preventDefault();
      saveSupplier();
    });
  }

  // Form Submit Hapus Supplier (Wajib Alasan)
  const deleteForm = document.getElementById('deleteSupplierForm');
  if (deleteForm) {
    deleteForm.addEventListener('submit', function(e) {
      e.preventDefault();
      confirmExecuteDeleteSupplier();
    });
  }

  // Event listener tombol Alert Modal
  const alertBtn = document.getElementById('supplierAlertBtn');
  if (alertBtn) {
    alertBtn.addEventListener('click', function() {
      closeSupplierAlert();
    });
  }

  // Tutup modal saat klik luar
  document.addEventListener('click', function(e) {
    const supModal = document.getElementById('supplierModal');
    if (supModal && e.target === supModal) {
      closeSupplierModal();
    }
    const delModal = document.getElementById('deleteSupplierModal');
    if (delModal && e.target === delModal) {
      closeDeleteSupplierModal();
    }
    const historyModal = document.getElementById('deletedSuppliersModal');
    if (historyModal && e.target === historyModal) {
      closeDeletedSuppliersModal();
    }
    const alertModal = document.getElementById('supplierAlertModal');
    if (alertModal && e.target === alertModal) {
      closeSupplierAlert();
    }
  });
}

// ==========================================================================
// 1. Ambil Data Supplier dari API
// ==========================================================================
function loadSuppliersFromDb() {
  const tbody = document.querySelector('.table-suppliers tbody');
  if (tbody) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" style="text-align: center; padding: 24px; color: var(--color-text-muted);">
          Memuat data rekanan supplier dari database MySQL...
        </td>
      </tr>
    `;
  }

  fetch('../api/suppliers.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        allSuppliersList = res.data;
        filterSuppliers();
      } else {
        if (tbody) {
          tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--color-danger); padding: 20px;">Gagal memuat: ${res.message || 'Terjadi kesalahan sistem'}</td></tr>`;
        }
      }
    })
    .catch(err => {
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--color-danger); padding: 20px;">Gagal koneksi: ${err.message}</td></tr>`;
      }
    });
}

// ==========================================================================
// 2. Filter di Frontend
// ==========================================================================
function filterSuppliers() {
  const searchInput = document.querySelector('.filter-input');
  const statusSelect = document.querySelector('select[aria-label="Filter Status Pemasok"]');

  const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
  const statusFilter = statusSelect ? statusSelect.value.toLowerCase() : '';

  filteredSuppliersList = allSuppliersList.filter(s => {
    const matchQuery = !query ||
      (s.name && s.name.toLowerCase().includes(query)) ||
      (s.code && s.code.toLowerCase().includes(query)) ||
      (s.phone && s.phone.toLowerCase().includes(query)) ||
      (s.email && s.email.toLowerCase().includes(query)) ||
      (s.address && s.address.toLowerCase().includes(query));

    let matchStatus = true;
    if (statusFilter === 'aktif') {
      matchStatus = (s.active == 1);
    } else if (statusFilter === 'nonaktif') {
      matchStatus = (s.active == 0);
    }

    return matchQuery && matchStatus;
  });

  const totalPages = Math.ceil(filteredSuppliersList.length / suppliersPerPage) || 1;
  if (currentSupplierPage > totalPages) {
    currentSupplierPage = totalPages;
  }

  renderSuppliersTable();
  renderSuppliersPagination(totalPages, currentSupplierPage);
}

// ==========================================================================
// 3. Render Tabel Supplier & Paginasi (Persis Seperti Riwayat Stok)
// ==========================================================================
function renderSuppliersTable() {
  const tbody = document.querySelector('.table-suppliers tbody');
  if (!tbody) return;

  if (filteredSuppliersList.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" style="text-align: center; padding: 28px; color: var(--color-text-muted);">
          Tidak ada data rekanan pemasok yang sesuai dengan kriteria pencarian.
        </td>
      </tr>
    `;
    return;
  }

  const startIndex = (currentSupplierPage - 1) * suppliersPerPage;
  const pageItems = filteredSuppliersList.slice(startIndex, startIndex + suppliersPerPage);

  tbody.innerHTML = '';
  pageItems.forEach((s, idx) => {
    const rowNumber = startIndex + idx + 1;
    const isActive = (s.active == 1);
    const badgeClass = isActive ? 'badge-available' : 'badge-out';
    const badgeText = isActive ? 'Aktif' : 'Non-Aktif';

    const phoneDisplay = s.phone
      ? `<a href="tel:${escapeHtml(s.phone)}" style="color: var(--color-primary); text-decoration: none;">${escapeHtml(s.phone)}</a>`
      : '<span class="text-muted">-</span>';

    const emailDisplay = s.email
      ? `<a href="mailto:${escapeHtml(s.email)}" style="color: var(--color-primary); text-decoration: none;">${escapeHtml(s.email)}</a>`
      : '<span class="text-muted">-</span>';

    const rawAddress = s.address ? s.address.trim() : '';
    const addressDisplay = rawAddress
      ? `<div class="table-cell-address" title="${escapeHtml(rawAddress)}">${escapeHtml(rawAddress)}</div>`
      : '<span class="text-muted">-</span>';

    const txCount = s.total_transactions ? `${s.total_transactions} transaksi` : '0 transaksi';

    // Tombol Toggle Status (Aktifkan / Nonaktifkan)
    const toggleBtnText = isActive ? 'Nonaktifkan' : 'Aktifkan';
    const toggleBtnClass = isActive ? 'btn-toggle-active' : 'btn-toggle-inactive';

    const isStaff = (window.isInventoryAdmin === false);
    const actionsHtml = isStaff
      ? `<span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 500; padding: 3px 8px; border: 1px solid #e2e8f0;">Hanya Baca</span>`
      : `<div class="table-cell-actions">
          <button type="button" class="btn btn-secondary btn-sm ${toggleBtnClass}" onclick="toggleSupplierStatus(${s.id}, '${escapeJs(s.name)}', ${s.active})" title="Ubah Status Kemitraan">
            ${toggleBtnText}
          </button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="editSupplier(${s.id})" title="Edit Data Supplier">
            Edit
          </button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="openDeleteSupplierModal(${s.id}, '${escapeJs(s.name)}', '${escapeJs(s.code)}')" title="Hapus Supplier" style="color: var(--color-danger);">
            Hapus
          </button>
        </div>`;

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="text-align: center; font-size: 12px; color: var(--color-text-muted); font-weight: 500;">${rowNumber}</td>
      <td>
        <span class="table-sku" style="font-family: var(--font-mono); font-weight: 600;">${escapeHtml(s.code)}</span>
      </td>
      <td>
        <div class="text-bold" style="font-size: var(--font-size-sm); color: var(--color-text-emphasis);">${escapeHtml(s.name)}</div>
      </td>
      <td>${phoneDisplay}</td>
      <td>${emailDisplay}</td>
      <td>${addressDisplay}</td>
      <td style="text-align: center; font-weight: 500; font-size: 12px; color: var(--color-text-main);">${txCount}</td>
      <td style="text-align: center;"><span class="badge ${badgeClass}"><span class="badge-dot"></span>${badgeText}</span></td>
      <td style="text-align: right;">
        ${actionsHtml}
      </td>
    `;
    tbody.appendChild(tr);
  });
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

function renderSuppliersPagination(totalPages, page) {
  const container = document.getElementById('suppliersPaginationControls');
  const wrapper = document.getElementById('suppliersPaginationWrapper');
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
      if (!isNaN(targetPage) && targetPage !== currentSupplierPage && targetPage >= 1 && targetPage <= totalPages) {
        currentSupplierPage = targetPage;
        renderSuppliersTable();
        renderSuppliersPagination(totalPages, currentSupplierPage);
        document.querySelector('.table-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
}

// ==========================================================================
// 4. Toggle Status Supplier (Aktif / Non-Aktif)
// ==========================================================================
function toggleSupplierStatus(id, name, currentActive) {
  if (window.isInventoryAdmin === false) {
    if (window.InvToast) {
      window.InvToast.show({
        type: 'warning',
        title: 'Akses Dibatasi',
        message: 'Pengubahan status rekanan supplier hanya dapat dilakukan oleh Admin Gudang.',
        duration: 4000
      });
    }
    return;
  }
  fetch('../api/suppliers.php?action=toggle_status', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'toggle_status', id: id })
  })
  .then(res => res.json())
  .then(res => {
    if (res.status === 'success') {
      showSupplierAlert({
        type: 'success',
        title: 'Status Diperbarui',
        message: res.message || `Status rekanan supplier '${name}' berhasil diperbarui.`
      });
      loadSuppliersFromDb();
    } else {
      showSupplierAlert({
        type: 'danger',
        title: 'Gagal Mengubah Status',
        message: res.message || 'Terjadi kesalahan sistem saat memperbarui status.'
      });
    }
  })
  .catch(err => {
    showSupplierAlert({
      type: 'danger',
      title: 'Kesalahan Jaringan',
      message: err.message
    });
  });
}

// ==========================================================================
// 5. Modal Tambah / Edit Supplier
// ==========================================================================
function openSupplierModal(supplier = null) {
  if (window.isInventoryAdmin === false) {
    if (window.InvToast) {
      window.InvToast.show({
        type: 'warning',
        title: 'Akses Dibatasi',
        message: 'Pengelolaan data rekanan supplier hanya dapat dilakukan oleh Admin Gudang.',
        duration: 4000
      });
    }
    return;
  }
  const modal = document.getElementById('supplierModal');
  if (!modal) return;

  editingSupplierId = supplier ? supplier.id : null;
  const modalTitle = document.getElementById('supplierModalTitle');
  if (modalTitle) {
    modalTitle.textContent = supplier ? 'Edit Data Rekanan Pemasok' : 'Tambah Rekanan Pemasok Baru';
  }

  document.getElementById('sup_code').value = supplier ? (supplier.code || '') : '';
  document.getElementById('sup_name').value = supplier ? (supplier.name || '') : '';
  document.getElementById('sup_phone').value = supplier ? (supplier.phone || '') : '';
  document.getElementById('sup_email').value = supplier ? (supplier.email || '') : '';
  document.getElementById('sup_address').value = supplier ? (supplier.address || '') : '';

  const reasonGroup = document.getElementById('sup_reason')?.closest('.form-group');
  if (reasonGroup) {
    reasonGroup.style.display = supplier ? 'none' : 'block';
  }
  const reasonInput = document.getElementById('sup_reason');
  if (reasonInput) reasonInput.value = '';

  const codeHint = document.getElementById('sup_code_hint');
  if (codeHint) {
    codeHint.textContent = supplier ? 'Kode unik rekanan di sistem.' : 'Wajib diisi sesuai kode unik mitra (contoh: SUP-005).';
  }

  modal.style.display = 'flex';
  setTimeout(() => {
    const focusEl = supplier ? document.getElementById('sup_name') : document.getElementById('sup_code');
    if (focusEl) focusEl.focus();
  }, 100);
}

function closeSupplierModal() {
  const modal = document.getElementById('supplierModal');
  if (modal) modal.style.display = 'none';
  editingSupplierId = null;
}

function editSupplier(id) {
  const supplier = allSuppliersList.find(s => s.id == id);
  if (supplier) {
    openSupplierModal(supplier);
  } else {
    fetch(`../api/suppliers.php?id=${id}`)
      .then(res => res.json())
      .then(res => {
        if (res.status === 'success' && res.data) {
          openSupplierModal(res.data);
        } else {
          showSupplierAlert({
            type: 'warning',
            title: 'Data Tidak Ditemukan',
            message: 'Data supplier tidak ditemukan di database.'
          });
        }
      });
  }
}

// ==========================================================================
// 6. Validasi Input Lengkap & Simpan Supplier (Semua Wajib Diisi)
// ==========================================================================
function saveSupplier() {
  const codeVal = document.getElementById('sup_code')?.value.trim().toUpperCase() || '';
  const nameVal = document.getElementById('sup_name')?.value.trim() || '';
  const phoneVal = document.getElementById('sup_phone')?.value.trim() || '';
  const emailVal = document.getElementById('sup_email')?.value.trim() || '';
  const addressVal = document.getElementById('sup_address')?.value.trim() || '';

  // 1. Validasi Kode Wajib Diisi
  if (!codeVal) {
    showSupplierAlert({
      type: 'warning',
      title: 'Validasi Input',
      message: 'Kode supplier wajib diisi (contoh: SUP-005).'
    });
    document.getElementById('sup_code')?.focus();
    return;
  }
  const codeRegex = /^[A-Z0-9\-]{3,30}$/;
  if (!codeRegex.test(codeVal)) {
    showSupplierAlert({
      type: 'warning',
      title: 'Format Kode Tidak Valid',
      message: 'Kode supplier hanya boleh terdiri dari huruf kapital, angka, dan tanda hubung (-) minimal 3 karakter (contoh: SUP-005).'
    });
    document.getElementById('sup_code')?.focus();
    return;
  }

  // 2. Validasi Nama Wajib Diisi
  if (!nameVal) {
    showSupplierAlert({
      type: 'warning',
      title: 'Validasi Input',
      message: 'Nama rekanan supplier wajib diisi.'
    });
    document.getElementById('sup_name')?.focus();
    return;
  }

  // 3. Validasi Nomor Telepon Wajib Diisi
  if (!phoneVal) {
    showSupplierAlert({
      type: 'warning',
      title: 'Validasi Input',
      message: 'Nomor telepon rekanan supplier wajib diisi.'
    });
    document.getElementById('sup_phone')?.focus();
    return;
  }
  const digitsOnly = phoneVal.replace(/[^\d]/g, '');
  const phoneRegex = /^[\+]?[(]?[0-9]{2,4}[)]?[-\s\.]?[0-9]{3,4}[-\s\.]?[0-9]{3,6}$/;
  if (digitsOnly.length < 8 || digitsOnly.length > 16 || !phoneRegex.test(phoneVal)) {
    showSupplierAlert({
      type: 'warning',
      title: 'Nomor Telepon Tidak Valid',
      message: 'Nomor telepon harus berisikan 8 hingga 16 digit angka (contoh: 021-5551234 atau 081234567890).'
    });
    document.getElementById('sup_phone')?.focus();
    return;
  }

  // 4. Validasi Email Wajib Diisi
  if (!emailVal) {
    showSupplierAlert({
      type: 'warning',
      title: 'Validasi Input',
      message: 'Alamat email resmi rekanan supplier wajib diisi.'
    });
    document.getElementById('sup_email')?.focus();
    return;
  }
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  if (!emailRegex.test(emailVal)) {
    showSupplierAlert({
      type: 'warning',
      title: 'Format Email Tidak Valid',
      message: 'Masukkan format email resmi yang valid (contoh: sales@perusahaan.co.id).'
    });
    document.getElementById('sup_email')?.focus();
    return;
  }

  // 5. Validasi Alamat Wajib Diisi
  if (!addressVal || addressVal.length < 4) {
    showSupplierAlert({
      type: 'warning',
      title: 'Validasi Input',
      message: 'Alamat lengkap kantor atau operasional rekanan supplier wajib diisi lengkap.'
    });
    document.getElementById('sup_address')?.focus();
    return;
  }

  const submitBtn = document.getElementById('btn-submit-supplier');
  const originalHtml = submitBtn ? submitBtn.innerHTML : '<span>Simpan Data</span>';
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>Menyimpan...</span>';
  }

  const reasonVal = document.getElementById('sup_reason')?.value.trim() || '';

  const payload = {
    code:    codeVal,
    name:    nameVal,
    phone:   phoneVal,
    email:   emailVal,
    address: addressVal,
    reason:  reasonVal,
  };

  const isEdit = !!editingSupplierId;
  const url = isEdit ? `../api/suppliers.php?id=${editingSupplierId}` : '../api/suppliers.php';
  const method = isEdit ? 'PUT' : 'POST';

  fetch(url, {
    method: method,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(res => {
    if (res.status === 'success') {
      closeSupplierModal();
      showSupplierAlert({
        type: 'success',
        title: isEdit ? 'Data Diperbarui' : 'Supplier Ditambahkan',
        message: res.message || 'Data rekanan supplier berhasil disimpan.'
      });
      loadSuppliersFromDb();
    } else {
      showSupplierAlert({
        type: 'danger',
        title: 'Gagal Menyimpan Data',
        message: res.message || 'Terjadi kesalahan sistem saat menyimpan data.'
      });
    }
  })
  .catch(err => {
    showSupplierAlert({
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
// 7. Modal Konfirmasi Hapus Supplier (Wajib Alasan)
// ==========================================================================
function openDeleteSupplierModal(id, name, code) {
  if (window.isInventoryAdmin === false) {
    if (window.InvToast) {
      window.InvToast.show({
        type: 'warning',
        title: 'Akses Dibatasi',
        message: 'Penghapusan rekanan supplier hanya dapat dilakukan oleh Admin Gudang.',
        duration: 4000
      });
    }
    return;
  }
  pendingDeleteSupplier = { id, name, code };

  const modal = document.getElementById('deleteSupplierModal');
  const nameEl = document.getElementById('deleteSupplierTargetName');
  const codeEl = document.getElementById('deleteSupplierTargetCode');
  const reasonInput = document.getElementById('deleteSupplierReasonInput');

  if (nameEl) nameEl.textContent = name;
  if (codeEl) codeEl.textContent = code;
  if (reasonInput) reasonInput.value = '';

  if (modal) {
    modal.style.display = 'flex';
    setTimeout(() => {
      if (reasonInput) reasonInput.focus();
    }, 100);
  }
}

function closeDeleteSupplierModal() {
  const modal = document.getElementById('deleteSupplierModal');
  if (modal) modal.style.display = 'none';
  pendingDeleteSupplier = null;
}

function confirmExecuteDeleteSupplier() {
  if (!pendingDeleteSupplier) return;

  const reasonInput = document.getElementById('deleteSupplierReasonInput');
  const reason = reasonInput ? reasonInput.value.trim() : '';

  if (!reason || reason.length < 4) {
    showSupplierAlert({
      type: 'warning',
      title: 'Alasan Wajib Diisi',
      message: 'Mohon tuliskan alasan penghapusan rekanan supplier secara jelas (minimal 4 karakter) untuk kebutuhan audit trail.'
    });
    if (reasonInput) reasonInput.focus();
    return;
  }

  const confirmBtn = document.getElementById('btn-confirm-delete-supplier');
  const originalHtml = confirmBtn ? confirmBtn.innerHTML : '<span>Ya, Hapus Supplier</span>';
  if (confirmBtn) {
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span>Menghapus...</span>';
  }

  fetch(`../api/suppliers.php?id=${pendingDeleteSupplier.id}`, {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ reason: reason })
  })
  .then(res => res.json())
  .then(res => {
    if (res.status === 'success') {
      closeDeleteSupplierModal();
      showSupplierAlert({
        type: 'success',
        title: 'Supplier Berhasil Dihapus',
        message: res.message || 'Rekanan supplier berhasil dihapus dan tercatat dalam audit log.'
      });
      loadSuppliersFromDb();
    } else {
      showSupplierAlert({
        type: 'danger',
        title: 'Gagal Menghapus',
        message: res.message || 'Terjadi kesalahan sistem saat menghapus supplier.'
      });
    }
  })
  .catch(err => {
    showSupplierAlert({
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
// 8. Modal Riwayat Supplier (Audit Trail Berhalaman)
// ==========================================================================
function openDeletedSuppliersModal() {
  const modal = document.getElementById('deletedSuppliersModal');
  const tbody = document.getElementById('deletedSuppliersTableBody');
  if (!modal || !tbody) return;

  modal.style.display = 'flex';
  tbody.innerHTML = `
    <tr>
      <td colspan="7" style="text-align: center; padding: 24px; color: var(--color-text-muted);">
        Memuat data riwayat supplier...
      </td>
    </tr>
  `;

  currentDeletedSupplierPage = 1;

  fetch('../api/suppliers.php?include_deleted=1')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
        allDeletedSuppliersList = res.data;
        renderDeletedSuppliersTablePage();
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

function closeDeletedSuppliersModal() {
  const modal = document.getElementById('deletedSuppliersModal');
  if (modal) modal.style.display = 'none';
}

function renderDeletedSuppliersTablePage() {
  const tbody = document.getElementById('deletedSuppliersTableBody');
  if (!tbody) return;

  const totalPages = Math.ceil(allDeletedSuppliersList.length / deletedSuppliersPerPage) || 1;
  if (currentDeletedSupplierPage > totalPages) {
    currentDeletedSupplierPage = totalPages;
  }

  if (allDeletedSuppliersList.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align: center; padding: 28px; color: var(--color-text-muted);">
          Belum ada riwayat aktivitas rekanan supplier.
        </td>
      </tr>
    `;
    renderDeletedSuppliersPaginationControls(1, 1);
    return;
  }

  const startIndex = (currentDeletedSupplierPage - 1) * deletedSuppliersPerPage;
  const pageItems = allDeletedSuppliersList.slice(startIndex, startIndex + deletedSuppliersPerPage);

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
      ? `<span class="badge" style="font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; gap: 4px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Tambah Supplier</span>`
      : `<span class="badge" style="font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; display: inline-flex; align-items: center; gap: 4px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line></svg> Hapus Supplier</span>`;

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

  renderDeletedSuppliersPaginationControls(totalPages, currentDeletedSupplierPage);
}

function renderDeletedSuppliersPaginationControls(totalPages, page) {
  const container = document.getElementById('deletedSuppliersPaginationControls');
  const wrapper = document.getElementById('deletedSuppliersPaginationWrapper');
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
      if (!isNaN(targetPage) && targetPage !== currentDeletedSupplierPage && targetPage >= 1 && targetPage <= totalPages) {
        currentDeletedSupplierPage = targetPage;
        renderDeletedSuppliersTablePage();
      }
    });
  });
}

// ==========================================================================
// 9. Modal Alert Universal Interaktif
// ==========================================================================
let alertCloseCallback = null;

function showSupplierAlert({ type = 'info', title = 'Notifikasi', message = '', buttonText = 'Mengerti', onClose = null }) {
  const modal = document.getElementById('supplierAlertModal');
  const iconEl = document.getElementById('supplierAlertIcon');
  const titleEl = document.getElementById('supplierAlertTitle');
  const msgEl = document.getElementById('supplierAlertMessage');
  const btnEl = document.getElementById('supplierAlertBtn');

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

  // Visual Styling Icon & Button berdasarkan Type
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

function closeSupplierAlert() {
  const modal = document.getElementById('supplierAlertModal');
  if (modal) modal.style.display = 'none';
  if (typeof alertCloseCallback === 'function') {
    const cb = alertCloseCallback;
    alertCloseCallback = null;
    cb();
  }
}

// ==========================================================================
// Helper Utilities
// ==========================================================================
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

function escapeJs(str) {
  if (!str) return '';
  return str.toString().replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

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
