<?php
require_once '../auth/check.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}
verifyCsrf();

$pdo = getConnection();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php?success=' . urlencode('Customer tidak valid.')); exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM sales_orders WHERE customer_id = ?");
$stmt->execute([$id]);
$hasOrders = (int)$stmt->fetch()['c'] > 0;

if ($hasOrders) {
    header('Location: index.php?success=' . urlencode('Customer tidak bisa dihapus karena sudah memiliki order.')); exit;
}

$stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
$stmt->execute([$id]);
header('Location: index.php?success=' . urlencode($stmt->rowCount() ? 'Customer berhasil dihapus' : 'Customer tidak ditemukan'));
exit;
