<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$pdo = getDB();
$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT p.*, u.status AS user_status, u.email, q.status AS qr_status
         FROM patients p
         JOIN users u ON u.id = p.user_id
         LEFT JOIN qr_codes q ON q.patient_id = p.patient_id
         WHERE p.nama_lengkap LIKE ? OR p.patient_code LIKE ? OR u.email LIKE ?
         ORDER BY p.created_at DESC'
    );
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query(
        'SELECT p.*, u.status AS user_status, u.email, q.status AS qr_status
         FROM patients p
         JOIN users u ON u.id = p.user_id
         LEFT JOIN qr_codes q ON q.patient_id = p.patient_id
         ORDER BY p.created_at DESC'
    );
}
$patients = $stmt->fetchAll();

$pageTitle = 'Data Pasien';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title">
        <h2>Data Pasien</h2>
        <a href="<?= BASE_URL ?>/admin/patient_add.php" class="btn btn-primary">+ Tambah Pasien</a>
    </div>

    <form method="get" class="field" style="max-width:360px;">
        <input type="text" name="q" placeholder="Cari nama, ID pasien, atau email..." value="<?= clean($search) ?>">
    </form>

    <?php if (empty($patients)): ?>
        <div class="empty-state">Tidak ada pasien ditemukan.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>ID Pasien</th><th>Nama</th><th>Email</th><th>Status Akun</th><th>QR</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($patients as $p): ?>
                <tr>
                    <td><?= clean($p['patient_code']) ?></td>
                    <td><?= clean($p['nama_lengkap']) ?></td>
                    <td><?= clean($p['email']) ?></td>
                    <td><span class="badge <?= $p['user_status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= clean($p['user_status']) ?></span></td>
                    <td><span class="badge <?= $p['qr_status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= clean($p['qr_status'] ?? '-') ?></span></td>
                    <td><a href="<?= BASE_URL ?>/admin/patient_detail.php?id=<?= $p['patient_id'] ?>" class="btn btn-ghost btn-sm">Kelola</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
