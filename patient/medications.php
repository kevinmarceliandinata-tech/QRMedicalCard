<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('patient');

$pdo = getDB();
$patientId = getPatientIdByUser($pdo, (int) $_SESSION['user_id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['medication_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM medications WHERE medication_id = ? AND patient_id = ?');
        $stmt->execute([$id, $patientId]);
        setFlash('success', 'Data obat dihapus.');
        redirect(BASE_URL . '/patient/medications.php');
    }

    if ($action === 'save') {
        $id = (int) ($_POST['medication_id'] ?? 0);
        $nama = trim($_POST['nama_obat'] ?? '');
        $dosis = trim($_POST['dosis'] ?? '');
        $frekuensi = trim($_POST['frekuensi'] ?? '');
        $ket = trim($_POST['keterangan'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($nama === '') $errors[] = 'Nama obat wajib diisi.';

        if (empty($errors)) {
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE medications SET nama_obat=?, dosis=?, frekuensi=?, keterangan=?, is_active=?
                     WHERE medication_id=? AND patient_id=?'
                );
                $stmt->execute([$nama, $dosis ?: null, $frekuensi ?: null, $ket ?: null, $isActive, $id, $patientId]);
                setFlash('success', 'Data obat diperbarui.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO medications (patient_id, nama_obat, dosis, frekuensi, keterangan, is_active)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$patientId, $nama, $dosis ?: null, $frekuensi ?: null, $ket ?: null, $isActive]);
                setFlash('success', 'Obat baru berhasil ditambahkan.');
            }
            redirect(BASE_URL . '/patient/medications.php');
        }
    }
}

$editData = ['medication_id' => 0, 'nama_obat' => '', 'dosis' => '', 'frekuensi' => '', 'keterangan' => '', 'is_active' => 1];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM medications WHERE medication_id = ? AND patient_id = ?');
    $stmt->execute([(int) $_GET['edit'], $patientId]);
    $found = $stmt->fetch();
    if ($found) $editData = $found;
}

$stmt = $pdo->prepare('SELECT * FROM medications WHERE patient_id = ? ORDER BY is_active DESC, created_at DESC');
$stmt->execute([$patientId]);
$medications = $stmt->fetchAll();

$pageTitle = 'Obat';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title"><h2><?= $editData['medication_id'] ? 'Ubah Obat' : '+ Tambah Obat' ?></h2></div>

    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= clean($err) ?></div><?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="medication_id" value="<?= (int) $editData['medication_id'] ?>">
        <div class="form-row">
            <div class="field" style="flex:2;">
                <label for="nama_obat">Nama Obat</label>
                <input type="text" id="nama_obat" name="nama_obat" value="<?= clean($editData['nama_obat']) ?>" required>
            </div>
            <div class="field">
                <label for="dosis">Dosis</label>
                <input type="text" id="dosis" name="dosis" value="<?= clean($editData['dosis'] ?? '') ?>" placeholder="mis. 500mg">
            </div>
            <div class="field">
                <label for="frekuensi">Frekuensi</label>
                <input type="text" id="frekuensi" name="frekuensi" value="<?= clean($editData['frekuensi'] ?? '') ?>" placeholder="mis. 2x sehari">
            </div>
        </div>
        <div class="field">
            <label for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan"><?= clean($editData['keterangan'] ?? '') ?></textarea>
        </div>
        <div class="field" style="display:flex;align-items:center;gap:8px;">
            <input type="checkbox" id="is_active" name="is_active" value="1" style="width:auto;" <?= $editData['is_active'] ? 'checked' : '' ?>>
            <label for="is_active" style="margin:0;">Sedang digunakan saat ini</label>
        </div>
        <button type="submit" class="btn btn-primary"><?= $editData['medication_id'] ? 'Simpan Perubahan' : 'Tambah Obat' ?></button>
        <?php if ($editData['medication_id']): ?>
            <a href="<?= BASE_URL ?>/patient/medications.php" class="btn btn-outline">Batal</a>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <div class="panel-title"><h2>Daftar Obat</h2></div>
    <?php if (empty($medications)): ?>
        <div class="empty-state">Belum ada obat yang tercatat.</div>
    <?php else: ?>
        <?php foreach ($medications as $m): ?>
            <div class="list-item">
                <div>
                    <h4><?= clean($m['nama_obat']) ?>
                        <span class="badge <?= $m['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $m['is_active'] ? 'Digunakan' : 'Dihentikan' ?></span>
                    </h4>
                    <div class="meta">
                        <?= clean($m['dosis'] ?? '-') ?> · <?= clean($m['frekuensi'] ?? '-') ?>
                        <?= $m['keterangan'] ? ' · ' . clean($m['keterangan']) : '' ?>
                    </div>
                </div>
                <div class="list-actions">
                    <a href="?edit=<?= $m['medication_id'] ?>" class="btn btn-ghost btn-sm">Ubah</a>
                    <form method="post" onsubmit="return confirm('Hapus data obat ini?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="medication_id" value="<?= $m['medication_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
