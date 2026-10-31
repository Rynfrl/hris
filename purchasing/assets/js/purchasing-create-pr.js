/**
 * purchasing-create-pr.js
 * Create a Purchase Requisition (always as Draft - submission for
 * approval happens as a separate explicit action on detail-pr.php).
 */
(function () {
  'use strict';
  const { Fmt, Toast, Layout, Util, CONFIG } = window.Purchasing;

  let products = [];
  let rowCounter = 0;

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    Layout.init('pr');
    document.getElementById('pur-tax-rate-label').textContent = Math.round(CONFIG.TAX_RATE * 100);
    document.getElementById('pur-add-row-btn').addEventListener('click', () => addRow());
    document.getElementById('pur-pr-form').addEventListener('submit', onSubmit);

    try {
      const [res, depts] = await Promise.all([
        window.PurchasingAPI.getProducts({ pageSize: 100 }),
        window.PurchasingAPI.getDepartments(),
      ]);
      products = res.data;
      const deptSelect = document.getElementById('pur-f-department');
      deptSelect.insertAdjacentHTML(
        'beforeend',
        depts.data.map((d) => `<option value="${d.id}">${Util.escapeHtml(d.name)}</option>`).join('')
      );
      addRow();
    } catch (err) {
      console.error(err);
      Toast.error('Gagal memuat daftar produk.');
    }
  }

  function productOptionsHtml(selectedId) {
    return '<option value="">-- Pilih Produk --</option>' + products.map((p) => `<option value="${p.id}" data-unit="${Util.escapeHtml(p.unit)}" data-price="${p.unit_price}" ${String(p.id) === String(selectedId) ? 'selected' : ''}>${Util.escapeHtml(p.code)} — ${Util.escapeHtml(p.name)}</option>`).join('');
  }

  function getRows() {
    return Array.from(document.querySelectorAll('#pur-item-rows .pur-item-row'));
  }
  function toggleEmptyState() {
    const empty = document.getElementById('pur-items-empty');
    const hasRows = getRows().length > 0;
    empty.classList.toggle('d-none', hasRows);
    document.querySelector('.pur-item-table').closest('.table-responsive').classList.toggle('d-none', !hasRows);
  }

  function addRow() {
    const tpl = document.getElementById('pur-row-template');
    const frag = tpl.content.cloneNode(true);
    const tr = frag.querySelector('tr');
    rowCounter += 1;
    tr.dataset.rowId = 'row_' + rowCounter;

    const productSelect = tr.querySelector('.pur-f-product');
    productSelect.innerHTML = productOptionsHtml();
    const priceInput = tr.querySelector('.pur-f-price');
    const qtyInput = tr.querySelector('.pur-f-qty');
    const unitCell = tr.querySelector('.pur-f-unit');

    productSelect.addEventListener('change', () => {
      const opt = productSelect.selectedOptions[0];
      unitCell.textContent = opt && opt.value ? opt.dataset.unit : '-';
      if (opt && opt.value && !priceInput.dataset.userEdited) priceInput.value = opt.dataset.price;
      productSelect.classList.remove('is-invalid');
      recalculate();
    });
    priceInput.addEventListener('input', () => { priceInput.dataset.userEdited = '1'; recalculate(); });
    qtyInput.addEventListener('input', recalculate);
    tr.querySelector('.pur-remove-row-btn').addEventListener('click', () => { tr.remove(); toggleEmptyState(); renumber(); recalculate(); });

    document.getElementById('pur-item-rows').appendChild(tr);
    renumber();
    toggleEmptyState();
    recalculate();
  }

  function renumber() {
    getRows().forEach((tr, idx) => { tr.querySelector('.pur-row-index').textContent = String(idx + 1); });
  }

  function collectRowData() {
    return getRows().map((tr) => ({ tr, product_id: tr.querySelector('.pur-f-product').value, qty: tr.querySelector('.pur-f-qty').value, price: tr.querySelector('.pur-f-price').value }));
  }

  function recalculate() {
    const rows = collectRowData();
    let subtotal = 0;
    rows.forEach((r) => {
      const s = (Number(r.qty) || 0) * (Number(r.price) || 0);
      subtotal += s;
      r.tr.querySelector('.pur-line-subtotal').textContent = Fmt.currency(s);
    });
    const tax = subtotal * CONFIG.TAX_RATE;
    document.getElementById('pur-total-subtotal').textContent = Fmt.currency(subtotal);
    document.getElementById('pur-total-tax').textContent = Fmt.currency(tax);
    document.getElementById('pur-total-grand').textContent = Fmt.currency(subtotal + tax);
  }

  function validateForm() {
    let valid = true;
    const rows = getRows();
    if (rows.length === 0) {
      Toast.error('Minimal harus ada satu item.');
      valid = false;
    }
    const seen = new Set();
    rows.forEach((tr) => {
      const productSelect = tr.querySelector('.pur-f-product');
      const priceInput = tr.querySelector('.pur-f-price');
      const qtyInput = tr.querySelector('.pur-f-qty');
      productSelect.classList.remove('is-invalid');
      priceInput.classList.remove('is-invalid');
      qtyInput.classList.remove('is-invalid');

      if (!productSelect.value) { productSelect.classList.add('is-invalid'); valid = false; }
      else if (seen.has(productSelect.value)) { productSelect.classList.add('is-invalid'); Toast.error('Produk yang sama tidak boleh dipilih dua kali.'); valid = false; }
      else seen.add(productSelect.value);

      if (!Number(qtyInput.value) || Number(qtyInput.value) <= 0) { qtyInput.classList.add('is-invalid'); valid = false; }
      if (priceInput.value === '' || Number(priceInput.value) < 0) { priceInput.classList.add('is-invalid'); valid = false; }
    });
    return valid;
  }

  async function onSubmit(e) {
    e.preventDefault();
    recalculate();
    if (!validateForm()) {
      Toast.error('Periksa kembali isian yang ditandai merah.');
      return;
    }

    const payload = {
      department_id: document.getElementById('pur-f-department').value ? Number(document.getElementById('pur-f-department').value) : null,
      needed_date: document.getElementById('pur-f-needed').value || null,
      notes: document.getElementById('pur-f-notes').value.trim(),
      items: collectRowData().map((r) => ({ product_id: Number(r.product_id), qty: Number(r.qty), estimated_unit_price: Number(r.price) })),
    };

    const btn = document.getElementById('pur-submit-btn');
    btn.disabled = true;
    document.getElementById('pur-submit-btn-label').classList.add('d-none');
    document.getElementById('pur-submit-btn-spinner').classList.remove('d-none');
    try {
      const pr = await window.PurchasingAPI.createRequisition(payload);
      Toast.success(`${pr.pr_no} berhasil disimpan sebagai Draft.`);
      window.location.href = `./detail-pr.php?id=${pr.id}`;
    } catch (err) {
      console.error(err);
      Toast.error(err.message || 'Gagal menyimpan PR.');
      btn.disabled = false;
      document.getElementById('pur-submit-btn-label').classList.remove('d-none');
      document.getElementById('pur-submit-btn-spinner').classList.add('d-none');
    }
  }
})();
