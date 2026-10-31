<?php
require_once '../auth/check.php';
salesRequireAdmin();
require_once '../config/database.php';

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT inv.*, so.order_no, so.id AS order_id, so.status AS order_status, c.name AS customer_name, c.email, c.phone FROM invoices inv JOIN sales_orders so ON so.id=inv.order_id JOIN customers c ON c.id=so.customer_id WHERE inv.id=?");
$stmt->execute([$id]); $invoice=$stmt->fetch();
if (!$invoice) { header('Location: index.php'); exit; }
$errors=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $amount=(float)($_POST['amount']??0); $method=$_POST['method']??'transfer'; $payDate=trim($_POST['payment_date']??date('Y-m-d'));
    if ($amount<=0) $errors[]='Jumlah pembayaran harus lebih dari 0.';
    if (!in_array($method,['cash','transfer','card'],true)) $errors[]='Metode pembayaran tidak valid.';
    if (!validDate($payDate)) $errors[]='Tanggal pembayaran tidak valid.';
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $lock=$pdo->prepare("SELECT * FROM invoices WHERE id=? FOR UPDATE"); $lock->execute([$id]); $lockedInvoice=$lock->fetch();
            if (!$lockedInvoice) throw new RuntimeException('Invoice tidak ditemukan.');
            if ($lockedInvoice['status']==='paid') throw new RuntimeException('Invoice sudah lunas.');
            if (!in_array($lockedInvoice['status'],['unpaid','partial'],true)) throw new RuntimeException('Status invoice tidak dapat menerima pembayaran.');
            $sum=$pdo->prepare("SELECT COALESCE(SUM(amount),0) AS paid FROM payments WHERE invoice_id=?"); $sum->execute([$id]); $paid=(float)$sum->fetch()['paid'];
            $remaining=(float)$lockedInvoice['amount']-$paid;
            if ($remaining <= 0) throw new RuntimeException('Tidak ada sisa tagihan.');
            if ($amount > $remaining + 0.000001) throw new RuntimeException('Pembayaran melebihi sisa tagihan Rp'.number_format($remaining,2,',','.').'.');
            $pdo->prepare("INSERT INTO payments (invoice_id,payment_date,amount,method) VALUES (?,?,?,?)")->execute([$id,$payDate,$amount,$method]);
            $newPaid=$paid+$amount; $newStatus=($newPaid >= (float)$lockedInvoice['amount']-0.000001)?'paid':'partial';
            $pdo->prepare("UPDATE invoices SET status=? WHERE id=?")->execute([$newStatus,$id]);
            if ($newStatus==='paid') {
                $updated=$pdo->prepare("UPDATE sales_orders SET status='paid' WHERE id=? AND status='invoiced'"); $updated->execute([(int)$lockedInvoice['order_id']]);
                if ($updated->rowCount()!==1) throw new RuntimeException('Status order tidak sesuai untuk pelunasan.');
            }
            $pdo->commit(); header('Location: view.php?id='.$id.'&success='.urlencode('Pembayaran berhasil dicatat')); exit;
        } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); error_log('Payment failed: '.$e->getMessage()); $errors[]=$e->getMessage(); }
    }
}

$stmt=$pdo->prepare("SELECT * FROM payments WHERE invoice_id=? ORDER BY payment_date DESC,id DESC"); $stmt->execute([$id]); $payments=$stmt->fetchAll();
$totalPaid=(float)array_sum(array_map('floatval',array_column($payments,'amount'))); $remaining=max(0,(float)$invoice['amount']-$totalPaid);
$statusColors=['unpaid'=>'danger','partial'=>'warning','paid'=>'success'];
$pageTitle='Detail Invoice'; $currentMenu='invoices'; $basePath='../'; require '../includes/header.php'; require '../includes/sidebar.php';
?>
<div class="row"><div class="col-md-7"><div class="card"><div class="card-header"><h3 class="card-title">Invoice <?=h($invoice['invoice_no'])?></h3><div class="card-tools"><span class="badge badge-<?=h($statusColors[$invoice['status']]??'secondary')?> p-2"><?=h(ucfirst($invoice['status']))?></span></div></div><div class="card-body">
<p><strong>Order:</strong> <a href="../orders/view.php?id=<?= (int)$invoice['order_id'] ?>"><?=h($invoice['order_no'])?></a><br><strong>Customer:</strong> <?=h($invoice['customer_name'])?><br><strong>Tanggal Invoice:</strong> <?=date('d M Y',strtotime($invoice['invoice_date']))?><br><strong>Jatuh Tempo:</strong> <?=$invoice['due_date']?date('d M Y',strtotime($invoice['due_date'])):'-'?></p>
<table class="table"><tr><td>Total Tagihan</td><td class="text-right">Rp<?=number_format($invoice['amount'],0,',','.')?></td></tr><tr><td>Sudah Dibayar</td><td class="text-right">Rp<?=number_format($totalPaid,0,',','.')?></td></tr><tr class="font-weight-bold"><td>Sisa</td><td class="text-right">Rp<?=number_format($remaining,0,',','.')?></td></tr></table>
</div></div>
<div class="card"><div class="card-header"><h3 class="card-title">Riwayat Pembayaran</h3></div><div class="card-body table-responsive"><table class="table table-bordered"><thead><tr><th>Tanggal</th><th>Metode</th><th class="text-right">Jumlah</th></tr></thead><tbody><?php if(!$payments):?><tr><td colspan="3" class="text-center text-muted">Belum ada pembayaran</td></tr><?php endif;?><?php foreach($payments as $p):?><tr><td><?=date('d M Y',strtotime($p['payment_date']))?></td><td><?=h(ucfirst($p['method']))?></td><td class="text-right">Rp<?=number_format($p['amount'],0,',','.')?></td></tr><?php endforeach;?></tbody></table></div></div></div>
<div class="col-md-5"><?php if($invoice['status']!=='paid'):?><div class="card card-success"><div class="card-header"><h3 class="card-title">Catat Pembayaran</h3></div><form method="POST"><input type="hidden" name="csrf_token" value="<?=h(csrfToken())?>"><div class="card-body"><?php foreach($errors as $e):?><div class="alert alert-danger"><?=h($e)?></div><?php endforeach;?><div class="form-group"><label class="required">Jumlah</label><input type="number" name="amount" class="form-control" required min="0.01" max="<?=h($remaining)?>" step="0.01" value="<?=h($_POST['amount']??$remaining)?>"></div><div class="form-group"><label>Tanggal Bayar</label><input type="date" name="payment_date" class="form-control" required value="<?=h($_POST['payment_date']??date('Y-m-d'))?>"></div><div class="form-group"><label>Metode</label><select name="method" class="form-control"><option value="transfer">Transfer</option><option value="cash">Cash</option><option value="card">Kartu</option></select></div></div><div class="card-footer"><button type="submit" class="btn btn-success btn-block">Simpan Pembayaran</button></div></form></div><?php endif;?><a href="index.php" class="btn btn-secondary btn-block">Kembali ke Daftar Invoice</a></div></div>
<?php require '../includes/footer.php'; ?>
