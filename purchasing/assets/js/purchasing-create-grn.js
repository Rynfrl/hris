/**
 * purchasing-create-grn.js
 * Pick a PO (Pending/Partially Received only), show its outstanding qty
 * per line, and let Warehouse enter how much actually arrived (partial
 * receipt supported - a line can be left at 0 if nothing for it arrived
 * yet). This is what actually posts stock IN and flips the PO's status.
 */
(function () {
  'use strict';
  const { Fmt, Toast, Layout, Util } = window.Purchasing;

  let eligiblePOs = [];
  let selectedPO = null;

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    Layout.init('grn');
    document.getElementById('pur-f-date').value = Fmt.dateInput(new Date());
    document.getElementById('pur-f-po').addEventListener('change', onPoChange);
    document.getElementById('pur-grn-form').addEventListener('submit', onSubmit);

    try {
      const res = await window.PurchasingAPI.getTransactions({ pageSize: 100 });
      eligiblePOs = res.data.filter((t) => ['Pending', 'Partially Received'].includes(t.status));
      populatePoSelect();

      const preselect = Util.qs('po_id');
      if (preselect && eligiblePOs.find((p) => String(p.id) === String(preselect))) {
        document.getElementById('pur-f-po').value = preselect;
        onPoChange();
      }
    } catch (err) {
      console.error(err);
      Toast.error('Gagal memuat daftar PO.');
    }
  }

  function populatePoSelect() {
    const sel = document.getElementById('pur-f-po');
    if (eligiblePOs.length === 0) {
      sel.innerHTML = '<option value="">Tidak ada PO yang menunggu penerimaan</option>';
      return;
    }
    sel.innerHTML =
      '<option value="">-- Pilih PO --</option>' +
      eligiblePOs.map((p) => `<option value="${p.id}">${Util.escapeHtml(p.transaction_no)} — ${p.supplier ? Util.escapeHtml(p.supplier.name) : '-'} (${Util.escapeHtml(p.status)})</option>`).join('');
  }

  async function onPoChange() {
    const id = document.getElementById('pur-f-po').value;
    const wrap = document.getElementById('pur-lines-card-wrap');
    const submitBtn = document.getElementById('pur-submit-btn');
    if (!id) {
      wrap.style.display = 'none';
      submitBtn.disabled = true;
      selectedPO = null;
      return;
    }
    try {
      selectedPO = await window.PurchasingAPI.getTransaction(id);
      renderLines(selectedPO);
      wrap.style.display = '';
      submitBtn.disabled = false;
    } catch (err) {
      Toast.error('Gagal memuat detail PO.');
    }
  }

  function renderLines(po) {
    const tbody = document.getElementById('pur-lines-body');
    tbody.innerHTML = po.details
      .map(
        (d) => `
      <tr data-detail-id="${d.id}">
        <td>${Util.escapeHtml(d.product_code)} — ${Util.escapeHtml(d.product_name)}</td>
        <td class="text-end">${d.qty}</td>
        <td class="text-end">${d.qty_received}</td>
        <td class="text-end fw-semibold ${d.qty_outstanding > 0 ? 'text-warning' : 'text-success'}">${d.qty_outstanding}</td>
        <td>
          <input type="number" class="form-control form-control-sm pur-qty-received" min="0" max="${d.qty_outstanding}" step="1" value="0" ${d.qty_outstanding <= 0 ? 'disabled' : ''} />
        </td>
        <td>
          <select class="form-select form-select-sm pur-condition" ${d.qty_outstanding <= 0 ? 'disabled' : ''}>
            <option value="Good">Good</option>
            <option value="Damaged">Damaged</option>
            <option value="Shortage">Shortage</option>
          </select>
        </td>
      </tr>`
      )
      .join('');
  }

  async function onSubmit(e) {
    e.preventDefault();
    if (!selectedPO) {
      Toast.error('Pilih PO terlebih dahulu.');
      return;
    }
    if (!document.getElementById('pur-f-date').value) {
      Toast.error('Tanggal terima wajib diisi.');
      return;
    }

    const lines = Array.from(document.querySelectorAll('#pur-lines-body tr')).map((tr) => ({
      purchase_transaction_detail_id: Number(tr.dataset.detailId),
      qty_received: Number(tr.querySelector('.pur-qty-received').value) || 0,
      condition_note: tr.querySelector('.pur-condition').value,
    }));

    if (lines.every((l) => l.qty_received <= 0)) {
      Toast.error('Isi qty diterima untuk minimal satu item.');
      return;
    }

    const payload = {
      purchase_transaction_id: selectedPO.id,
      received_date: document.getElementById('pur-f-date').value,
      notes: document.getElementById('pur-f-notes').value.trim(),
      lines,
    };

    const btn = document.getElementById('pur-submit-btn');
    btn.disabled = true;
    document.getElementById('pur-submit-btn-label').classList.add('d-none');
    document.getElementById('pur-submit-btn-spinner').classList.remove('d-none');
    try {
      const grn = await window.PurchasingAPI.createGoodsReceipt(payload);
      Toast.success(`${grn.grn_no} berhasil disimpan. PO sekarang: ${grn.purchase_transaction.status}.`);
      window.location.href = `./detail-grn.php?id=${grn.id}`;
    } catch (err) {
      console.error(err);
      Toast.error(err.message || 'Gagal menyimpan GRN.');
      btn.disabled = false;
      document.getElementById('pur-submit-btn-label').classList.remove('d-none');
      document.getElementById('pur-submit-btn-spinner').classList.add('d-none');
    }
  }
})();
