<?php
require_once '../auth/check.php';
salesRequireAdmin();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
verifyCsrf();
$pdo = getConnection();
$id = (int)($_POST['id'] ?? 0);

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT status FROM sales_orders WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order || $order['status'] !== 'draft') throw new RuntimeException('Order tidak ditemukan atau bukan status draft.');
    $pdo->prepare("DELETE FROM sales_orders WHERE id = ?")->execute([$id]);
    $pdo->commit();
    header('Location: index.php?success=' . urlencode('Order berhasil dihapus')); exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: index.php?success=' . urlencode($e->getMessage())); exit;
}
