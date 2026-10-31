<?php
require_once '../auth/check.php';
require_once '../config/database.php';

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '') $errors[] = 'Nama wajib diisi.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=?, city=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $email, $phone, $address, $city, $is_active, $id]);
        header('Location: index.php?success=Customer berhasil diperbarui');
        exit;
    }
}

$pageTitle = 'Edit Customer';
$currentMenu = 'customers';
$basePath = '../';
require '../includes/header.php';
require '../includes/sidebar.php';
?>

<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Form Edit Customer (<?= htmlspecialchars($customer['code']) ?>)</h3></div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <div class="card-body">
            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>

            <div class="form-group">
                <label class="required">Nama Customer</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? $customer['name']) ?>">
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? $customer['email']) ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Telepon</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? $customer['phone']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($_POST['address'] ?? $customer['address']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Kota</label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($_POST['city'] ?? $customer['city']) ?>">
            </div>
            <div class="form-group form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= $customer['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Aktif</label>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require '../includes/footer.php'; ?>
