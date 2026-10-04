<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('patient');

$pdo = getDB();
$patientId = getPatientIdByUser($pdo, (int) $_SESSION['user_id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['allergy_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM allergies WHERE allergy_id = ? AND patient_id = ?');
        $stmt->execute([$id, $patientId]);
        setFlash('success', 'Data alergi dihapus.');
        redirect(BASE_URL . '/patient/allergies.php');
    }

    if ($action === 'save') {
        $id = (int) ($_POST['allergy_id'] ?? 0);
        $jenis = trim($_POST['jenis_alergi'] ?? '');
        $ket = trim($_POST['keterangan'] ?? '');

        if ($jenis === '') $errors[] = 'Jenis alergi wajib diisi.';

        if (empty($errors)) {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE allergies SET jenis_alergi=?, keterangan=? WHERE allergy_id=? AND patient_id=?');
                $stmt->execute([$jenis, $ket ?: null, $id, $patientId]);
                setFlash('success', 'Data alergi diperbarui.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO allergies (patient_id, jenis_alergi, keterangan) VALUES (?, ?, ?)');
                $stmt->execute([$patientId, $jenis, $ket ?: null]);
                setFlash('success', 'Alergi baru berhasil ditambahkan.');
            }
            redirect(BASE_URL . '/patient/allergies.php');
        }
    }
}

$editData = ['allergy_id' => 0, 'jenis_alergi' => '', 'keterangan' => ''];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM allergies WHERE allergy_id = ? AND patient_id = ?');
    $stmt->execute([(int) $_GET['edit'], $patientId]);
    $found = $stmt->fetch();
    if ($found) $editData = $found;
}

$stmt = $pdo->prepare('SELECT * FROM allergies WHERE patient_id = ? ORDER BY created_at DESC');
$stmt->execute([$patientId]);
$allergies = $stmt->fetchAll();

$pageTitle = 'Alergi';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title"><h2><?= $editData['allergy_id'] ? 'Ubah Alergi' : '+ Tambah Alergi' ?></h2></div>

    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= clean($err) ?></div><?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="allergy_id" value="<?= (int) $editData['allergy_id'] ?>">
        <div class="form-row">
            <div class="field">
                <label for="jenis_alergi">Jenis Alergi (obat/makanan)</label>
                <input type="text" id="jenis_alergi" name="jenis_alergi" value="<?= clean($editData['jenis_alergi']) ?>" placeholder="mis. Penisilin, Kacang" required>
            </div>
            <div class="field" style="flex:2;">
                <label for="keterangan">Keterangan</label>
                <input type="text" id="keterangan" name="keterangan" value="<?= clean($editData['keterangan'] ?? '') ?>" placeholder="mis. Reaksi gatal-gatal & sesak napas">
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= $editData['allergy_id'] ? 'Simpan Perubahan' : 'Tambah Alergi' ?></button>
        <?php if ($editData['allergy_id']): ?>
            <a href="<?= BASE_URL ?>/patient/allergies.php" class="btn btn-outline">Batal</a>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <div class="panel-title"><h2>Daftar Alergi</h2></div>
    <?php if (empty($allergies)): ?>
        <div class="empty-state">Belum ada alergi yang tercatat.</div>
    <?php else: ?>
        <?php foreach ($allergies as $a): ?>
            <div class="list-item">
                <div>
                    <h4><?= clean($a['jenis_alergi']) ?></h4>
                    <div class="meta"><?= clean($a['keterangan'] ?? '-') ?></div>
                </div>
                <div class="list-actions">
                    <a href="?edit=<?= $a['allergy_id'] ?>" class="btn btn-ghost btn-sm">Ubah</a>
                    <form method="post" onsubmit="return confirm('Hapus data alergi ini?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="allergy_id" value="<?= $a['allergy_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
