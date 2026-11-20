<?php
require_once '../auth/check.php';
require_once '../config/database.php';

$pdo = getConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');

    if ($name === '') $errors[] = 'Nama wajib diisi.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            [$code, $customerLock] = nextCustomerCode($pdo);
            $stmt = $pdo->prepare("INSERT INTO customers (code, name, email, phone, address, city) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $name, $email, $phone, $address, $city]);
            $newCustomerId = (int)$pdo->lastInsertId();
            if ($newCustomerId > 0) {
                $pdo->prepare("UPDATE customers SET customer_id = id WHERE id = ? AND customer_id IS NULL")->execute([$newCustomerId]);
            }
            $pdo->commit();
            releaseDocumentLock($pdo, $customerLock);
            header('Location: index.php?success=Customer berhasil ditambahkan');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if (!empty($customerLock)) { try { releaseDocumentLock($pdo, $customerLock); } catch (Throwable $ignore) {} }
            error_log('Create customer failed: ' . $e->getMessage());
            $errors[] = 'Gagal menyimpan customer: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Tambah Customer';
$currentMenu = 'customers';
$basePath = '../';
require '../includes/header.php';
require '../includes/sidebar.php';
?>

<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Form Tambah Customer</h3></div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <div class="card-body">
            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>

            <div class="form-group">
                <label class="required">Nama Customer</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Telepon</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Kota</label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require '../includes/footer.php'; ?>
