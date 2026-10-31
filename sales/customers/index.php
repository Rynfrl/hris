<?php
require_once '../auth/check.php';
require_once '../config/database.php';

$pdo = getConnection();

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE name LIKE ? OR code LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC");
}
$customers = $stmt->fetchAll();

$pageTitle = 'Customers';
$currentMenu = 'customers';
$basePath = '../';
require '../includes/header.php';
require '../includes/sidebar.php';

if (isset($_GET['success'])) {
    echo '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>' . htmlspecialchars($_GET['success']) . '</div>';
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Customer</h3>
        <div class="card-tools">
            <a href="create.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Tambah Customer</a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3 form-inline">
            <input type="text" name="q" class="form-control mr-2" placeholder="Cari nama/kode/email..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-secondary"><i class="fas fa-search"></i> Cari</button>
        </form>

        <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Telepon</th>
                <th>Kota</th>
                <th>Status</th>
                <th width="140">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($customers)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">Belum ada data customer</td></tr>
            <?php endif; ?>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['code']) ?></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= htmlspecialchars($c['phone']) ?></td>
                    <td><?= htmlspecialchars($c['city']) ?></td>
                    <td>
                        <?php if ($c['is_active']): ?>
                            <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Hapus customer ini?')">
                            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
