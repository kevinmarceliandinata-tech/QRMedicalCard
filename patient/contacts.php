<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('patient');

$pdo = getDB();
$patientId = getPatientIdByUser($pdo, (int) $_SESSION['user_id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['contact_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM emergency_contacts WHERE contact_id = ? AND patient_id = ?');
        $stmt->execute([$id, $patientId]);
        setFlash('success', 'Kontak darurat dihapus.');
        redirect(BASE_URL . '/patient/contacts.php');
    }

    if ($action === 'make_primary') {
        $id = (int) ($_POST['contact_id'] ?? 0);
        $pdo->prepare('UPDATE emergency_contacts SET is_primary = 0 WHERE patient_id = ?')->execute([$patientId]);
        $pdo->prepare('UPDATE emergency_contacts SET is_primary = 1 WHERE contact_id = ? AND patient_id = ?')->execute([$id, $patientId]);
        setFlash('success', 'Kontak utama diperbarui.');
        redirect(BASE_URL . '/patient/contacts.php');
    }

    if ($action === 'save') {
        $id = (int) ($_POST['contact_id'] ?? 0);
        $nama = trim($_POST['nama_kontak'] ?? '');
        $hubungan = trim($_POST['hubungan'] ?? '');
        $telepon = trim($_POST['no_telepon'] ?? '');

        if ($nama === '') $errors[] = 'Nama kontak wajib diisi.';
        if ($telepon === '') $errors[] = 'No. telepon wajib diisi.';

        if (empty($errors)) {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE emergency_contacts SET nama_kontak=?, hubungan=?, no_telepon=? WHERE contact_id=? AND patient_id=?');
                $stmt->execute([$nama, $hubungan ?: null, $telepon, $id, $patientId]);
                setFlash('success', 'Kontak darurat diperbarui.');
            } else {
                $countStmt = $pdo->prepare('SELECT COUNT(*) c FROM emergency_contacts WHERE patient_id = ?');
                $countStmt->execute([$patientId]);
                $isFirst = (int) $countStmt->fetch()['c'] === 0;

                $stmt = $pdo->prepare('INSERT INTO emergency_contacts (patient_id, nama_kontak, hubungan, no_telepon, is_primary) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$patientId, $nama, $hubungan ?: null, $telepon, $isFirst ? 1 : 0]);
                setFlash('success', 'Kontak darurat baru berhasil ditambahkan.');
            }
            redirect(BASE_URL . '/patient/contacts.php');
        }
    }
}

$editData = ['contact_id' => 0, 'nama_kontak' => '', 'hubungan' => '', 'no_telepon' => ''];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM emergency_contacts WHERE contact_id = ? AND patient_id = ?');
    $stmt->execute([(int) $_GET['edit'], $patientId]);
    $found = $stmt->fetch();
    if ($found) $editData = $found;
}

$stmt = $pdo->prepare('SELECT * FROM emergency_contacts WHERE patient_id = ? ORDER BY is_primary DESC, contact_id ASC');
$stmt->execute([$patientId]);
$contacts = $stmt->fetchAll();

$pageTitle = 'Kontak Darurat';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title"><h2><?= $editData['contact_id'] ? 'Ubah Kontak' : '+ Tambah Kontak' ?></h2></div>

    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= clean($err) ?></div><?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="contact_id" value="<?= (int) $editData['contact_id'] ?>">
        <div class="form-row">
            <div class="field" style="flex:2;">
                <label for="nama_kontak">Nama Kontak</label>
                <input type="text" id="nama_kontak" name="nama_kontak" value="<?= clean($editData['nama_kontak']) ?>" required>
            </div>
            <div class="field">
                <label for="hubungan">Hubungan</label>
                <input type="text" id="hubungan" name="hubungan" value="<?= clean($editData['hubungan'] ?? '') ?>" placeholder="mis. Ibu, Suami">
            </div>
            <div class="field">
                <label for="no_telepon">No. Telepon</label>
                <input type="tel" id="no_telepon" name="no_telepon" value="<?= clean($editData['no_telepon'] ?? '') ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= $editData['contact_id'] ? 'Simpan Perubahan' : 'Tambah Kontak' ?></button>
        <?php if ($editData['contact_id']): ?>
            <a href="<?= BASE_URL ?>/patient/contacts.php" class="btn btn-outline">Batal</a>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <div class="panel-title"><h2>Daftar Kontak Darurat</h2></div>
    <?php if (empty($contacts)): ?>
        <div class="empty-state">Belum ada kontak darurat.</div>
    <?php else: ?>
        <?php foreach ($contacts as $c): ?>
            <div class="list-item">
                <div>
                    <h4><?= clean($c['nama_kontak']) ?>
                        <?php if ($c['is_primary']): ?><span class="badge badge-active">Utama</span><?php endif; ?>
                    </h4>
                    <div class="meta"><?= clean($c['hubungan'] ?? '-') ?> · <?= clean($c['no_telepon']) ?></div>
                </div>
                <div class="list-actions">
                    <?php if (!$c['is_primary']): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="make_primary">
                            <input type="hidden" name="contact_id" value="<?= $c['contact_id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm">Jadikan Utama</button>
                        </form>
                    <?php endif; ?>
                    <a href="?edit=<?= $c['contact_id'] ?>" class="btn btn-ghost btn-sm">Ubah</a>
                    <form method="post" onsubmit="return confirm('Hapus kontak ini?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="contact_id" value="<?= $c['contact_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
