(function () {
  'use strict';
  const { Fmt, Toast, Layout, Util, CONFIG } = window.Purchasing;
  let currentPage = 1;
  const pageSize = CONFIG.PAGE_SIZE;
  let grnList = [];
  let selectedGrn = null;

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    Layout.init('returns');
    const form = document.getElementById('pur-return-form');
    if (form) {
      form.addEventListener('submit', onSubmit);
      document.getElementById('pur-f-grn').addEventListener('change', onGrnChange);
      try {
        const res = await window.PurchasingAPI.getGoodsReceipts({ pageSize: 100 });
        grnList = res.data;
        const sel = document.getElementById('pur-f-grn');
        sel.innerHTML = '<option value="">-- Pilih GRN --</option>' + grnList.map((g) => `<option value="${g.id}">${Util.escapeHtml(g.grn_no)} (${g.purchase_transaction ? Util.escapeHtml(g.purchase_transaction.transaction_no) : '-'})</option>`).join('');
      } catch (err) {
        console.error(err);
      }
    }
    loadList();
  }

  async function onGrnChange() {
    const id = document.getElementById('pur-f-grn').value;
    const productSel = document.getElementById('pur-f-product');
    if (!id) {
      productSel.innerHTML = '<option value="">-- Pilih GRN dahulu --</option>';
      selectedGrn = null;
      return;
    }
    try {
      selectedGrn = await window.PurchasingAPI.getGoodsReceipt(id);
      productSel.innerHTML = selectedGrn.details.map((d) => `<option value="${d.product_id}">${Util.escapeHtml(d.product_code)} — ${Util.escapeHtml(d.product_name)} (diterima: ${d.qty_received})</option>`).join('');
    } catch (err) {
      Toast.error('Gagal memuat detail GRN.');
    }
  }

  async function loadList() {
    try {
      const res = await window.PurchasingAPI.getReturns({ page: currentPage, pageSize });
      renderTable(res);
    } catch (err) {
      Toast.error(err.message || 'Gagal memuat data retur.');
    }
  }

  function renderTable(res) {
    const tbody = document.getElementById('pur-return-table-body');
    const emptyEl = document.getElementById('pur-return-empty');
    const condBadge = { Defective: 'text-bg-danger', Shortage: 'text-bg-warning', 'Wrong Item': 'text-bg-secondary', Other: 'text-bg-light' };
    if (res.data.length === 0) {
      tbody.innerHTML = '';
      emptyEl.classList.remove('d-none');
    } else {
      emptyEl.classList.add('d-none');
      tbody.innerHTML = res.data
        .map(
          (r) => `
        <tr>
          <td class="fw-semibold">${Util.escapeHtml(r.return_no)}</td>
          <td>${r.goods_receipt ? `<a href="./detail-grn.php?id=${r.goods_receipt.id}">${Util.escapeHtml(r.goods_receipt.grn_no)}</a>` : '-'}</td>
          <td>${r.product ? Util.escapeHtml(r.product.name) : '-'}</td>
          <td class="text-end">${r.qty_returned}</td>
          <td><span class="badge ${condBadge[r.condition] || 'text-bg-secondary'}">${Util.escapeHtml(r.condition)}</span></td>
          <td>${r.created_by_user ? Util.escapeHtml(r.created_by_user.name) : '-'}</td>
        </tr>`
        )
        .join('');
    }
    const totalPages = Math.max(1, Math.ceil(res.total / res.pageSize));
    const start = (res.page - 1) * res.pageSize;
    document.getElementById('pur-pagination-info').textContent = res.total === 0 ? 'Tidak ada data' : `Menampilkan ${start + 1}-${Math.min(start + res.data.length, res.total)} dari ${res.total} retur`;
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
      if (!isNaN(p) && p >= 1 && p <= totalPages) { currentPage = p; loadList(); }
    }));
  }
  function pageItem(page, label, disabled) {
    return `<li class="page-item ${disabled ? 'disabled' : ''}"><a class="page-link pur-pl" href="#" data-page="${page}">${label}</a></li>`;
  }

  async function onSubmit(e) {
    e.preventDefault();
    const errEl = document.getElementById('pur-form-error');
    errEl.classList.add('d-none');

    const payload = {
      goods_receipt_id: Number(document.getElementById('pur-f-grn').value),
      product_id: Number(document.getElementById('pur-f-product').value),
      qty_returned: Number(document.getElementById('pur-f-qty').value),
      condition: document.getElementById('pur-f-condition').value,
      reason: document.getElementById('pur-f-reason').value.trim(),
    };
    if (!payload.goods_receipt_id || !payload.product_id || !payload.qty_returned) {
      errEl.textContent = 'Semua field wajib diisi.';
      errEl.classList.remove('d-none');
      return;
    }

    const btn = document.getElementById('pur-submit-btn');
    btn.disabled = true;
    document.getElementById('pur-submit-btn-label').classList.add('d-none');
    document.getElementById('pur-submit-btn-spinner').classList.remove('d-none');
    try {
      const ret = await window.PurchasingAPI.createReturn(payload);
      Toast.success(`${ret.return_no} berhasil disimpan. Stok telah dikurangi.`);
      document.getElementById('pur-return-form').reset();
      document.getElementById('pur-f-product').innerHTML = '<option value="">-- Pilih GRN dahulu --</option>';
      currentPage = 1;
      loadList();
    } catch (err) {
      errEl.textContent = err.message || 'Gagal menyimpan retur.';
      errEl.classList.remove('d-none');
    } finally {
      btn.disabled = false;
      document.getElementById('pur-submit-btn-label').classList.remove('d-none');
      document.getElementById('pur-submit-btn-spinner').classList.add('d-none');
    }
  }
})();
