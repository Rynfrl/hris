/**
 * purchasing-create.js
 * Logic for create-transaction.php. Three modes, chosen by URL query:
 *   (none)          -> create a brand-new PO directly
 *   ?id=<id>        -> edit an existing Pending PO
 *   ?from_pr=<id>   -> convert an Approved PR into a PO (items prefilled
 *                      from the PR; supplier + final price chosen here)
 */
(function () {
  'use strict';

  const { Fmt, Toast, Layout, Util, CONFIG } = window.Purchasing;

  let products = [];
  let suppliers = [];
  let rowCounter = 0;
  let editingId = null;
  let fromPrId = null;

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    editingId = Util.qs('id');
    fromPrId = Util.qs('from_pr');
    Layout.init('pr');

    document.getElementById('pur-tax-rate-label').textContent = Math.round(CONFIG.TAX_RATE * 100);
    document.getElementById('pur-f-date').value = Fmt.dateInput(new Date());
    document.getElementById('pur-f-trxno').value = 'Akan dibuat otomatis saat disimpan';

    bindStaticEvents();

    try {
      const [productList, supplierList] = await Promise.all([window.PurchasingAPI.getProducts({ pageSize: 100 }), window.PurchasingAPI.getSuppliers({ pageSize: 100 })]);
      products = productList.data;
      suppliers = supplierList.data;
      populateSupplierSelect();

      if (editingId) {
        await loadForEdit(editingId);
      } else if (fromPrId) {
        await loadFromPr(fromPrId);
      } else {
        addRow();
      }
    } catch (err) {
      console.error(err);
      Toast.error('Gagal memuat data master produk/supplier.');
    }
  }

  function populateSupplierSelect() {
    const sel = document.getElementById('pur-f-supplier');
    sel.innerHTML = '<option value="">-- Pilih Supplier --</option>' + suppliers.map((s) => `<option value="${s.id}">${Util.escapeHtml(s.code)} — ${Util.escapeHtml(s.name)}</option>`).join('');
  }

  function productOptionsHtml(selectedId) {
    return (
      '<option value="">-- Pilih Produk --</option>' +
      products
        .map((p) => `<option value="${p.id}" data-unit="${Util.escapeHtml(p.unit)}" data-price="${p.unit_price}" ${String(p.id) === String(selectedId) ? 'selected' : ''}>${Util.escapeHtml(p.code)} — ${Util.escapeHtml(p.name)}</option>`)
        .join('')
    );
  }

  function bindStaticEvents() {
    document.getElementById('pur-add-row-btn').addEventListener('click', () => addRow());
    document.getElementById('pur-create-form').addEventListener('submit', onSubmit);
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

  function addRow(prefill) {
    const tpl = document.getElementById('pur-row-template');
    const frag = tpl.content.cloneNode(true);
    const tr = frag.querySelector('tr');
    rowCounter += 1;
    tr.dataset.rowId = 'row_' + rowCounter;

    const productSelect = tr.querySelector('.pur-f-product');
    productSelect.innerHTML = productOptionsHtml(prefill && prefill.product_id);

    const priceInput = tr.querySelector('.pur-f-price');
    const qtyInput = tr.querySelector('.pur-f-qty');
    const unitCell = tr.querySelector('.pur-f-unit');

    if (prefill) {
      const product = products.find((p) => p.id === Number(prefill.product_id));
      if (product) unitCell.textContent = product.unit;
      priceInput.value = prefill.unit_price;
      qtyInput.value = prefill.qty;
      priceInput.dataset.userEdited = '1'; // don't let a later product-select overwrite a prefilled negotiated price
    }

    productSelect.addEventListener('change', () => {
      const opt = productSelect.selectedOptions[0];
      unitCell.textContent = opt && opt.value ? opt.dataset.unit : '-';
      if (opt && opt.value && !priceInput.dataset.userEdited) {
        priceInput.value = opt.dataset.price;
      }
      clearRowError(tr);
      recalculate();
    });
    priceInput.addEventListener('input', () => {
      priceInput.dataset.userEdited = '1';
      recalculate();
    });
    qtyInput.addEventListener('input', recalculate);
    tr.querySelector('.pur-remove-row-btn').addEventListener('click', () => {
      tr.remove();
      toggleEmptyState();
      renumberRows();
      recalculate();
    });

    document.getElementById('pur-item-rows').appendChild(tr);
    renumberRows();
    toggleEmptyState();
    recalculate();
    return tr;
  }

  function renumberRows() {
    getRows().forEach((tr, idx) => {
      tr.querySelector('.pur-row-index').textContent = String(idx + 1);
    });
  }

  function clearRowError(tr) {
    const el = tr.querySelector('.pur-f-product');
    if (el) el.classList.remove('is-invalid');
  }

  /* Calculation helpers (reusable) ---------------------------------------- */
  function calculateItemSubtotal(qty, unitPrice) {
    return (Number(qty) || 0) * (Number(unitPrice) || 0);
  }
  function calculateSubtotal(rows) {
    return rows.reduce((sum, r) => sum + calculateItemSubtotal(r.qty, r.unitPrice), 0);
  }
  function calculateTax(subtotal) {
    return subtotal * CONFIG.TAX_RATE;
  }
  function calculateGrandTotal(subtotal, tax) {
    return subtotal + tax;
  }
  function calculateTotals(rows) {
    const subtotal = calculateSubtotal(rows);
    const tax = calculateTax(subtotal);
    return { subtotal, tax, grandTotal: calculateGrandTotal(subtotal, tax) };
  }

  function collectRowData() {
    return getRows().map((tr) => ({
      tr,
      product_id: tr.querySelector('.pur-f-product').value,
      qty: tr.querySelector('.pur-f-qty').value,
      unitPrice: tr.querySelector('.pur-f-price').value,
    }));
  }

  function recalculate() {
    const rowsData = collectRowData();
    rowsData.forEach((r) => {
      r.tr.querySelector('.pur-line-subtotal').textContent = Fmt.currency(calculateItemSubtotal(r.qty, r.unitPrice));
    });
    const totals = calculateTotals(rowsData.map((r) => ({ qty: r.qty, unitPrice: r.unitPrice })));
    document.getElementById('pur-total-subtotal').textContent = Fmt.currency(totals.subtotal);
    document.getElementById('pur-total-tax').textContent = Fmt.currency(totals.tax);
    document.getElementById('pur-total-grand').textContent = Fmt.currency(totals.grandTotal);
    return totals;
  }

  /* Validation -------------------------------------------------------------- */
  function validateForm() {
    let valid = true;
    const supplierSel = document.getElementById('pur-f-supplier');
    const dateInput = document.getElementById('pur-f-date');
    supplierSel.classList.remove('is-invalid');
    dateInput.classList.remove('is-invalid');

    if (!supplierSel.value) {
      supplierSel.classList.add('is-invalid');
      valid = false;
    }
    if (!dateInput.value) {
      dateInput.classList.add('is-invalid');
      valid = false;
    }

    const rows = getRows();
    if (rows.length === 0) {
      Toast.error('Minimal harus ada satu item produk.');
      valid = false;
    }

    const seenProducts = new Set();
    const DEFAULT_ERR = 'Pilih produk, dan pastikan belum dipakai di baris lain.';
    rows.forEach((tr) => {
      const productSelect = tr.querySelector('.pur-f-product');
      const priceInput = tr.querySelector('.pur-f-price');
      const qtyInput = tr.querySelector('.pur-f-qty');
      productSelect.classList.remove('is-invalid');
      priceInput.classList.remove('is-invalid');
      qtyInput.classList.remove('is-invalid');
      productSelect.nextElementSibling.textContent = DEFAULT_ERR;

      if (!productSelect.value) {
        productSelect.classList.add('is-invalid');
        valid = false;
      } else if (seenProducts.has(productSelect.value)) {
        productSelect.classList.add('is-invalid');
        productSelect.nextElementSibling.textContent = 'Produk ini sudah dipilih di baris lain.';
        Toast.error('Produk yang sama tidak boleh dipilih lebih dari satu kali.');
        valid = false;
      } else {
        seenProducts.add(productSelect.value);
      }

      const qty = Number(qtyInput.value);
      if (!qty || qty <= 0) {
        qtyInput.classList.add('is-invalid');
        valid = false;
      }
      const price = Number(priceInput.value);
      if (priceInput.value === '' || isNaN(price) || price < 0) {
        priceInput.classList.add('is-invalid');
        valid = false;
      }
    });

    return valid;
  }

  /* Edit mode ---------------------------------------------------------------- */
  async function loadForEdit(id) {
    try {
      const tx = await window.PurchasingAPI.getTransaction(id);
      if (tx.status !== 'Pending') {
        Toast.warning('Hanya PO berstatus Pending yang dapat diubah. Anda diarahkan ke halaman detail.');
        window.location.href = `./detail-transaction.php?id=${id}`;
        return;
      }
      document.title = `Ubah PO ${tx.transaction_no} | Purchasing ERP`;
      document.getElementById('pur-submit-btn-label').innerHTML = '<i class="bi bi-save me-1"></i>Simpan Perubahan';

      document.getElementById('pur-f-supplier').value = String(tx.supplier_id);
      document.getElementById('pur-f-date').value = tx.transaction_date;
      document.getElementById('pur-f-expected').value = tx.expected_delivery_date || '';
      document.getElementById('pur-f-trxno').value = tx.transaction_no;
      document.getElementById('pur-f-notes').value = tx.notes || '';

      tx.details.forEach((d) => addRow({ product_id: d.product_id, qty: d.qty, unit_price: d.unit_price }));
      recalculate();
    } catch (err) {
      console.error(err);
      Toast.error('Gagal memuat PO untuk diubah.');
      window.location.href = './dashboard.php';
    }
  }

  /* PR-conversion mode ---------------------------------------------------------- */
  async function loadFromPr(prId) {
    try {
      const pr = await window.PurchasingAPI.getRequisition(prId);
      if (pr.status !== 'Approved') {
        Toast.warning('Hanya PR berstatus Approved yang dapat dikonversi menjadi PO.');
        window.location.href = `./detail-pr.php?id=${prId}`;
        return;
      }
      document.title = `Konversi ${pr.pr_no} ke PO | Purchasing ERP`;
      document.getElementById('pur-submit-btn-label').innerHTML = '<i class="bi bi-save me-1"></i>Buat PO dari PR ini';
      document.getElementById('pur-pr-source-info').innerHTML = `
        <div class="alert alert-info py-2 mb-0">
          <i class="bi bi-info-circle me-1"></i>
          Dikonversi dari <strong>${Util.escapeHtml(pr.pr_no)}</strong> (${Util.escapeHtml(pr.department || '-')}).
          Pilih supplier dan sesuaikan harga final di bawah.
        </div>`;
      document.getElementById('pur-f-notes').value = pr.notes || '';

      pr.details.forEach((d) => addRow({ product_id: d.product_id, qty: d.qty, unit_price: d.estimated_unit_price }));
      recalculate();
    } catch (err) {
      console.error(err);
      Toast.error('Gagal memuat PR sumber.');
      window.location.href = './pr-list.php';
    }
  }

  /* Submit ---------------------------------------------------------------------- */
  function setSubmitting(isSubmitting) {
    const btn = document.getElementById('pur-submit-btn');
    document.getElementById('pur-submit-btn-label').classList.toggle('d-none', isSubmitting);
    document.getElementById('pur-submit-btn-spinner').classList.toggle('d-none', !isSubmitting);
    btn.disabled = isSubmitting;
    document.getElementById('pur-add-row-btn').disabled = isSubmitting;
  }

  async function onSubmit(e) {
    e.preventDefault();
    recalculate();
    if (!validateForm()) {
      Toast.error('Periksa kembali isian yang ditandai merah.');
      return;
    }

    const payload = {
      supplier_id: Number(document.getElementById('pur-f-supplier').value),
      transaction_date: document.getElementById('pur-f-date').value,
      expected_delivery_date: document.getElementById('pur-f-expected').value || null,
      notes: document.getElementById('pur-f-notes').value.trim(),
      items: collectRowData().map((r) => ({ product_id: Number(r.product_id), qty: Number(r.qty), unit_price: Number(r.unitPrice) })),
    };

    setSubmitting(true);
    try {
      let tx;
      if (editingId) {
        tx = await window.PurchasingAPI.updateTransaction(editingId, payload);
        Toast.success('Perubahan PO berhasil disimpan.');
      } else if (fromPrId) {
        tx = await window.PurchasingAPI.convertRequisition(fromPrId, payload);
        Toast.success(`PO ${tx.transaction_no} berhasil dibuat dari PR.`);
      } else {
        tx = await window.PurchasingAPI.createTransaction(payload);
        Toast.success(`PO ${tx.transaction_no} berhasil dibuat.`);
      }
      window.location.href = `./detail-transaction.php?id=${tx.id}`;
    } catch (err) {
      console.error(err);
      Toast.error(err.message || 'Gagal menyimpan PO. Silakan coba lagi.');
      setSubmitting(false);
    }
  }
})();
