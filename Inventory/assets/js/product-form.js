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

  // Muat opsi dropdown kategori & supplier dari MySQL
  Promise.all([loadCategories(), loadSuppliers()])
    .then(() => {
      if (productId) {
        // Mode Edit: Ambil data produk yang ada
        loadProductDetailForEdit(productId);
      } else {
        // Mode Tambah Baru: Bersihkan nilai default jika ada
        setupNewProductForm();
      }
    });

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

// 3. Mode Tambah Baru
function setupNewProductForm() {
  const pageTitle = document.querySelector('.page-title-group h1');
  if (pageTitle) pageTitle.textContent = 'Tambah Produk Baru';

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
  if (minStock) minStock.value = '5';
  if (locInput) locInput.value = 'Rak Utama';
  if (descInput) descInput.value = '';
}

// 4. Mode Edit: Isi Form dengan Data dari Database
function loadProductDetailForEdit(id) {
  const pageTitle = document.querySelector('.page-title-group h1');
  if (pageTitle) pageTitle.textContent = 'Edit Data Produk #' + id;

  fetch(`../api/products.php?id=${id}`)
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success' && res.data) {
        const p = res.data;
        setValue('product_name', p.name);
        setValue('sku', p.sku);
        setValue('category', p.category_id);
        setValue('unit', p.unit);
        setValue('purchase_price', Math.round(p.purchase_price));
        setValue('selling_price', Math.round(p.selling_price));
        setValue('current_stock', p.current_stock);
        setValue('min_stock', p.minimum_stock);
        setValue('location', p.location);
        setValue('description', p.description);

        const submitBtnText = document.querySelector('button[type="submit"] span');
        if (submitBtnText) submitBtnText.textContent = 'Simpan Perubahan';
      } else {
        alert('Produk tidak ditemukan di database.');
        window.location.href = 'products.html';
      }
    })
    .catch(err => {
      alert('Gagal mengambil data produk: ' + err.message);
    });
}

// 5. Simpan Produk ke MySQL
function saveProduct(productId) {
  const isEdit = !!productId;
  const submitBtn = document.querySelector('button[type="submit"]');
  const originalBtnHtml = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span>Menyimpan ke MySQL...</span>';

  const payload = {
    id: productId,
    name: getValue('product_name'),
    sku: getValue('sku'),
    category_id: getValue('category'),
    unit: getValue('unit'),
    purchase_price: getValue('purchase_price'),
    selling_price: getValue('selling_price'),
    minimum_stock: getValue('min_stock'),
    location: getValue('location'),
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
      alert(isEdit ? 'Data produk berhasil diperbarui!' : 'Produk baru berhasil disimpan ke database MySQL!');
      window.location.href = 'products.html';
    } else {
      alert('Gagal menyimpan: ' + (res.message || 'Terjadi kesalahan'));
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnHtml;
    }
  })
  .catch(err => {
    alert('Kesalahan jaringan: ' + err.message);
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalBtnHtml;
  });
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
