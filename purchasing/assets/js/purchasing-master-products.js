(function () {
  'use strict';
  const { Fmt, Toast, Layout, Auth, Util, Confirm, CONFIG } = window.Purchasing;
  let currentPage = 1;
  const pageSize = CONFIG.PAGE_SIZE;
  let modal = null;

  document.addEventListener('DOMContentLoaded', init);

  function init() {
    Layout.init('master_products');
    document.getElementById('pur-search').addEventListener('input', Util.debounce(() => { currentPage = 1; load(); }, 300));

    const modalEl = document.getElementById('pur-form-modal');
    if (modalEl) {
      modal = new bootstrap.Modal(modalEl);
      document.getElementById('pur-add-btn').addEventListener('click', () => openModal(null));
      document.getElementById('pur-form').addEventListener('submit', onSubmit);
    }
    load();
  }

  async function load() {
    const search = document.getElementById('pur-search').value.trim();
    try {
      const res = await window.PurchasingAPI.getProducts({ search, page: currentPage, pageSize });
      renderTable(res);
    } catch (err) {
      Toast.error(err.message || 'Gagal memuat produk.');
    }
  }

  function renderTable(res) {
    const tbody = document.getElementById('pur-table-body');
    const emptyEl = document.getElementById('pur-empty');
    const role = Auth.getCurrentUser().role;
    const canManage = ['Admin', 'Purchasing Staff'].includes(role);
    const canDelete = role === 'Admin';

    if (res.data.length === 0) {
      tbody.innerHTML = '';
      emptyEl.classList.remove('d-none');
    } else {
      emptyEl.classList.add('d-none');
      tbody.innerHTML = res.data
        .map(
          (p) => `
        <tr>
          <td class="fw-semibold">${Util.escapeHtml(p.code)}</td>
          <td>${Util.escapeHtml(p.name)}</td>
          <td>${Util.escapeHtml(p.unit)}</td>
          <td class="text-end">${Fmt.currency(p.unit_price)}</td>
          <td class="text-end">${Number(p.stock).toLocaleString('id-ID')}${p.is_low_stock ? ' <span class="badge text-bg-warning" title="Stok di bawah/sama dengan minimum">Menipis</span>' : ''}</td>
          <td class="text-end">
            ${canManage ? `<button class="btn btn-sm btn-outline-secondary pur-edit-btn" data-id="${p.id}"><i class="bi bi-pencil"></i></button>` : ''}
            ${canDelete ? `<button class="btn btn-sm btn-outline-danger ms-1 pur-delete-btn" data-id="${p.id}" data-name="${Util.escapeHtml(p.name)}"><i class="bi bi-trash"></i></button>` : ''}
          </td>
        </tr>`
        )
        .join('');
      tbody.querySelectorAll('.pur-edit-btn').forEach((btn) => btn.addEventListener('click', () => openModal(res.data.find((p) => String(p.id) === btn.dataset.id))));
      tbody.querySelectorAll('.pur-delete-btn').forEach((btn) => btn.addEventListener('click', () => onDelete(btn.dataset.id, btn.dataset.name)));
    }

    const totalPages = Math.max(1, Math.ceil(res.total / res.pageSize));
    const start = (res.page - 1) * res.pageSize;
    document.getElementById('pur-pagination-info').textContent = res.total === 0 ? 'Tidak ada data' : `Menampilkan ${start + 1}-${Math.min(start + res.data.length, res.total)} dari ${res.total} produk`;
    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    const nav = document.getElementById('pur-pagination');
    if (totalPages <= 1) { nav.innerHTML = ''; return; }
    let html = pageItem(currentPage - 1, '&laquo;', currentPage === 1);
    for (let p = 1; p <= totalPages; p++) html += `<li class="page-item ${p === currentPage ? 'active' : ''}"><a class="page-link pur-pl" href="#" data-page="${p}">${p}</a></li>`;
    html += pageItem(currentPage + 1, '&raquo;', currentPage === totalPages);
    nav.innerHTML = html;
    nav.querySelectorAll('.pur-pl').forEach((a) => a.addEventListener('click', (e) => {
      e.preventDefault();
      const p = parseInt(a.getAttribute('data-page'), 10);
      if (!isNaN(p) && p >= 1 && p <= totalPages) { currentPage = p; load(); }
    }));
  }
  function pageItem(page, label, disabled) {
    return `<li class="page-item ${disabled ? 'disabled' : ''}"><a class="page-link pur-pl" href="#" data-page="${page}">${label}</a></li>`;
  }

  function openModal(product) {
    document.getElementById('pur-form-error').classList.add('d-none');
    document.getElementById('pur-modal-title').textContent = product ? 'Ubah Produk' : 'Tambah Produk';
    document.getElementById('pur-f-id').value = product ? product.id : '';
    document.getElementById('pur-f-code').value = product ? product.code : '';
    document.getElementById('pur-f-name').value = product ? product.name : '';
    document.getElementById('pur-f-unit').value = product ? product.unit : '';
    document.getElementById('pur-f-price').value = product ? product.unit_price : '';
    modal.show();
  }

  async function onSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('pur-f-id').value;
    const payload = {
      code: document.getElementById('pur-f-code').value.trim(),
      name: document.getElementById('pur-f-name').value.trim(),
      unit: document.getElementById('pur-f-unit').value.trim(),
      unit_price: Number(document.getElementById('pur-f-price').value),
    };
    try {
      if (id) {
        await window.PurchasingAPI.updateProduct(id, payload);
        Toast.success('Produk berhasil diubah.');
      } else {
        await window.PurchasingAPI.createProduct(payload);
        Toast.success('Produk berhasil ditambahkan.');
      }
      modal.hide();
      load();
    } catch (err) {
      const errEl = document.getElementById('pur-form-error');
      errEl.textContent = err.message || 'Gagal menyimpan produk.';
      errEl.classList.remove('d-none');
    }
  }

  async function onDelete(id, name) {
    const ok = await Confirm.ask({ title: 'Hapus Produk', message: `Hapus produk <strong>${Util.escapeHtml(name)}</strong>? Data histori transaksi tetap tersimpan.`, confirmText: 'Ya, Hapus', confirmClass: 'btn-danger' });
    if (!ok) return;
    try {
      await window.PurchasingAPI.deleteProduct(id);
      Toast.success('Produk berhasil dihapus.');
      load();
    } catch (err) {
      Toast.error(err.message || 'Gagal menghapus produk.');
    }
  }
})();
