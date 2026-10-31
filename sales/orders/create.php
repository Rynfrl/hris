<?php
require_once '../auth/check.php';
require_once '../config/database.php';

$pdo = getConnection();
$user = currentUser();
$errors = [];

$customers = $pdo->query("SELECT id, code, name FROM customers WHERE is_active = 1 ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT product_id AS id, sku, product_name AS name, selling_price AS price, stock, unit FROM products WHERE deleted_at IS NULL AND is_active = 1 ORDER BY product_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $orderDate = trim($_POST['order_date'] ?? date('Y-m-d'));
    $discount = (float)($_POST['discount'] ?? 0);
    $taxPercent = (float)($_POST['tax_percent'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $productIds = is_array($_POST['product_id'] ?? null) ? $_POST['product_id'] : [];
    $quantities = is_array($_POST['quantity'] ?? null) ? $_POST['quantity'] : [];

    if ($customerId <= 0) $errors[] = 'Customer wajib dipilih.';
    if (!validDate($orderDate)) $errors[] = 'Tanggal order tidak valid.';
    if ($discount < 0) $errors[] = 'Diskon tidak boleh negatif.';
    if ($taxPercent < 0 || $taxPercent > 100) $errors[] = 'Pajak harus antara 0 dan 100 persen.';
    if (empty($productIds)) $errors[] = 'Minimal harus ada 1 item produk.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $customerStmt = $pdo->prepare("SELECT id FROM customers WHERE id = ? AND is_active = 1 FOR UPDATE");
            $customerStmt->execute([$customerId]);
            if (!$customerStmt->fetch()) throw new RuntimeException('Customer tidak ditemukan atau sudah nonaktif.');

            // Gabungkan produk yang sama agar kuantitas divalidasi secara total.
            $requested = [];
            foreach ($productIds as $i => $rawPid) {
                $pid = (int)$rawPid;
                $qty = (int)($quantities[$i] ?? 0);
                if ($pid <= 0 || $qty <= 0) throw new RuntimeException('Produk dan quantity harus valid.');
                $requested[$pid] = ($requested[$pid] ?? 0) + $qty;
            }
            if (!$requested) throw new RuntimeException('Minimal harus ada satu item valid.');

            $items = []; $subtotal = 0.0;
            $productStmt = $pdo->prepare("SELECT product_id AS id, sku, product_name AS name, selling_price AS price, stock FROM products WHERE product_id = ? AND deleted_at IS NULL AND is_active = 1 FOR UPDATE");
            foreach ($requested as $pid => $qty) {
                $productStmt->execute([$pid]);
                $prod = $productStmt->fetch();
                if (!$prod) throw new RuntimeException('Ada produk yang tidak ditemukan atau nonaktif.');
                $price = (float)$prod['price'];
                $lineTotal = $price * $qty;
                $subtotal += $lineTotal;
                $items[] = ['product_id'=>$pid, 'qty'=>$qty, 'price'=>$price, 'subtotal'=>$lineTotal];
            }

            if ($discount > $subtotal) throw new RuntimeException('Diskon tidak boleh lebih besar dari subtotal.');
            $tax = $subtotal * ($taxPercent / 100);
            $total = $subtotal - $discount + $tax;
            if ($total < 0) throw new RuntimeException('Total order tidak boleh negatif.');

            $orderLock = null;
            $orderNo = nextDocumentNo($pdo, 'SO');
            // Keep the numbering lock until the insert is committed.
            $stmt = $pdo->prepare("INSERT INTO sales_orders (order_no, customer_id, order_date, status, subtotal, discount, tax, total_amount, notes, created_by) VALUES (?, ?, ?, 'draft', ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$orderNo, $customerId, $orderDate, $subtotal, $discount, $tax, $total, $notes, $user['id']]);
            // nextDocumentNo acquires the lock; recover its name deterministically.
            $orderLock = 'hris_sales_so_' . date('Ymd');
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO sales_order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $it) $itemStmt->execute([$orderId, $it['product_id'], $it['qty'], $it['price'], $it['subtotal']]);

            $pdo->commit();
            if ($orderLock) releaseDocumentLock($pdo, $orderLock);
            header('Location: view.php?id=' . $orderId . '&success=' . urlencode('Sales Order berhasil dibuat')); exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if (!empty($orderLock)) { try { releaseDocumentLock($pdo, $orderLock); } catch (Throwable $ignore) {} }
            error_log('Create order failed: ' . $e->getMessage());
            $errors[] = 'Gagal menyimpan order: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Order Baru'; $currentMenu = 'orders'; $basePath = '../';
require '../includes/header.php'; require '../includes/sidebar.php';
?>
<div class="card card-primary">
<div class="card-header"><h3 class="card-title">Form Sales Order Baru</h3></div>
<form method="POST" id="orderForm">
<input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
<div class="card-body">
<?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= h($e) ?></div><?php endforeach; ?>
<div class="form-row"><div class="form-group col-md-6"><label class="required">Customer</label><select name="customer_id" class="form-control" required><option value="">-- Pilih Customer --</option><?php foreach ($customers as $c): ?><option value="<?= (int)$c['id'] ?>" <?= ((int)($_POST['customer_id'] ?? 0)===(int)$c['id'])?'selected':'' ?>><?= h($c['name']) ?> (<?= h($c['code']) ?>)</option><?php endforeach; ?></select></div>
<div class="form-group col-md-6"><label class="required">Tanggal Order</label><input type="date" name="order_date" class="form-control" required value="<?= h($_POST['order_date'] ?? date('Y-m-d')) ?>"></div></div>
<hr><h5>Item Produk</h5>
<table class="table table-bordered" id="itemsTable"><thead><tr><th style="width:40%">Produk</th><th style="width:15%">Harga</th><th style="width:15%">Qty</th><th style="width:20%">Subtotal</th><th style="width:10%"></th></tr></thead><tbody id="itemsBody"></tbody></table>
<button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn"><i class="fas fa-plus"></i> Tambah Item</button>
<hr><div class="row justify-content-end"><div class="col-md-5">
<div class="form-group row"><label class="col-6 col-form-label text-right">Subtotal</label><div class="col-6"><input type="text" id="subtotalDisplay" class="form-control text-right" readonly value="0"></div></div>
<div class="form-group row"><label class="col-6 col-form-label text-right">Diskon (Rp)</label><div class="col-6"><input type="number" name="discount" id="discountInput" class="form-control text-right" value="<?= h($_POST['discount'] ?? 0) ?>" min="0" step="0.01"></div></div>
<div class="form-group row"><label class="col-6 col-form-label text-right">Pajak (%)</label><div class="col-6"><input type="number" name="tax_percent" id="taxInput" class="form-control text-right" value="<?= h($_POST['tax_percent'] ?? 11) ?>" min="0" max="100" step="0.01"></div></div>
<div class="form-group row"><label class="col-6 col-form-label text-right"><strong>Total</strong></label><div class="col-6"><input type="text" id="totalDisplay" class="form-control text-right font-weight-bold" readonly value="0"></div></div>
</div></div>
<div class="form-group"><label>Catatan</label><textarea name="notes" class="form-control" rows="2"><?= h($_POST['notes'] ?? '') ?></textarea></div>
</div><div class="card-footer"><button type="submit" class="btn btn-primary">Simpan Order</button> <a href="index.php" class="btn btn-secondary">Batal</a></div>
</form></div>
<script>
const products = <?= json_encode($products, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
function productOptions(){let html='<option value="">-- Pilih Produk --</option>';products.forEach(p=>html+=`<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock}">${escapeHtml(p.name)} (${escapeHtml(p.sku)}) - stok ${p.stock}</option>`);return html;}
function escapeHtml(v){return String(v).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));}
function addItemRow(){const row=document.createElement('tr');row.innerHTML=`<td><select name="product_id[]" class="form-control product-select" required>${productOptions()}</select></td><td><input type="text" class="form-control price-display" readonly value="0"></td><td><input type="number" name="quantity[]" class="form-control qty-input" value="1" min="1" required></td><td><input type="text" class="form-control line-subtotal" readonly value="0"></td><td><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i></button></td>`;document.getElementById('itemsBody').appendChild(row);bindRowEvents(row);recalcTotals();}
function bindRowEvents(row){const select=row.querySelector('.product-select'),qty=row.querySelector('.qty-input'),price=row.querySelector('.price-display'),line=row.querySelector('.line-subtotal');function recalc(){const opt=select.options[select.selectedIndex],p=parseFloat(opt?.dataset.price||0),q=parseInt(qty.value||0,10);price.value=formatRupiah(p);line.value=formatRupiah(p*q);recalcTotals();}select.addEventListener('change',recalc);qty.addEventListener('input',recalc);row.querySelector('.remove-item').addEventListener('click',()=>{row.remove();recalcTotals();});}
function formatRupiah(n){return new Intl.NumberFormat('id-ID').format(Math.round(n));}
function recalcTotals(){let subtotal=0;document.querySelectorAll('#itemsBody tr').forEach(row=>{const opt=row.querySelector('.product-select').options[row.querySelector('.product-select').selectedIndex],p=parseFloat(opt?.dataset.price||0),q=parseInt(row.querySelector('.qty-input').value||0,10);subtotal+=p*q;});const discount=parseFloat(document.getElementById('discountInput').value||0),taxPct=parseFloat(document.getElementById('taxInput').value||0),tax=subtotal*(taxPct/100),total=subtotal-discount+tax;document.getElementById('subtotalDisplay').value=formatRupiah(subtotal);document.getElementById('totalDisplay').value=formatRupiah(Math.max(0,total));}
document.getElementById('addItemBtn').addEventListener('click',addItemRow);document.getElementById('discountInput').addEventListener('input',recalcTotals);document.getElementById('taxInput').addEventListener('input',recalcTotals);addItemRow();
</script>
<?php require '../includes/footer.php'; ?>
