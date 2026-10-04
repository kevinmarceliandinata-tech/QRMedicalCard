<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('patient');

$pdo = getDB();
$patientId = getPatientIdByUser($pdo, (int) $_SESSION['user_id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['disease_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM disease_history WHERE disease_id = ? AND patient_id = ?');
        $stmt->execute([$id, $patientId]);
        setFlash('success', 'Data penyakit dihapus.');
        redirect(BASE_URL . '/patient/diseases.php');
    }

    if ($action === 'save') {
        $id = (int) ($_POST['disease_id'] ?? 0);
        $nama = trim($_POST['nama_penyakit'] ?? '');
        $tanggal = trim($_POST['tanggal_diagnosis'] ?? '');
        $status = $_POST['status'] ?? 'aktif';
        $catatan = trim($_POST['catatan'] ?? '');

        if ($nama === '') $errors[] = 'Nama penyakit wajib diisi.';
        if (!in_array($status, ['aktif', 'tidak aktif'], true)) $errors[] = 'Status tidak valid.';

        if (empty($errors)) {
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE disease_history SET nama_penyakit=?, tanggal_diagnosis=?, status=?, catatan=?
                     WHERE disease_id=? AND patient_id=?'
                );
                $stmt->execute([$nama, $tanggal ?: null, $status, $catatan ?: null, $id, $patientId]);
                setFlash('success', 'Data penyakit diperbarui.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO disease_history (patient_id, nama_penyakit, tanggal_diagnosis, status, catatan)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$patientId, $nama, $tanggal ?: null, $status, $catatan ?: null]);
                setFlash('success', 'Penyakit baru berhasil ditambahkan.');
            }
            redirect(BASE_URL . '/patient/diseases.php');
        }
    }
}

$editData = ['disease_id' => 0, 'nama_penyakit' => '', 'tanggal_diagnosis' => '', 'status' => 'aktif', 'catatan' => ''];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM disease_history WHERE disease_id = ? AND patient_id = ?');
    $stmt->execute([(int) $_GET['edit'], $patientId]);
    $found = $stmt->fetch();
    if ($found) $editData = $found;
}

$stmt = $pdo->prepare('SELECT * FROM disease_history WHERE patient_id = ? ORDER BY status = "aktif" DESC, tanggal_diagnosis DESC');
$stmt->execute([$patientId]);
$diseases = $stmt->fetchAll();

$pageTitle = 'Riwayat Penyakit';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title"><h2><?= $editData['disease_id'] ? 'Ubah Penyakit' : '+ Tambah Penyakit' ?></h2></div>

    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= clean($err) ?></div><?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="disease_id" value="<?= (int) $editData['disease_id'] ?>">
        <div class="form-row">
            <div class="field" style="flex:2;">
                <label for="nama_penyakit">Nama Penyakit</label>
                <input type="text" id="nama_penyakit" name="nama_penyakit" value="<?= clean($editData['nama_penyakit']) ?>" required>
            </div>
            <div class="field">
                <label for="tanggal_diagnosis">Tanggal/Tahun Diagnosis</label>
                <input type="date" id="tanggal_diagnosis" name="tanggal_diagnosis" value="<?= clean($editData['tanggal_diagnosis'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="aktif" <?= $editData['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="tidak aktif" <?= $editData['status'] === 'tidak aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
        </div>
        <div class="field">
            <label for="catatan">Catatan Tambahan</label>
            <textarea id="catatan" name="catatan"><?= clean($editData['catatan'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?= $editData['disease_id'] ? 'Simpan Perubahan' : 'Tambah Penyakit' ?></button>
        <?php if ($editData['disease_id']): ?>
            <a href="<?= BASE_URL ?>/patient/diseases.php" class="btn btn-outline">Batal</a>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <div class="panel-title"><h2>Daftar Riwayat Penyakit</h2></div>
    <?php if (empty($diseases)): ?>
        <div class="empty-state">Belum ada riwayat penyakit yang tercatat.</div>
    <?php else: ?>
        <?php foreach ($diseases as $d): ?>
            <div class="list-item">
                <div>
                    <h4><?= clean($d['nama_penyakit']) ?>
                        <span class="badge <?= $d['status'] === 'aktif' ? 'badge-active' : 'badge-inactive' ?>"><?= clean($d['status']) ?></span>
                    </h4>
                    <div class="meta">
                        <?= $d['tanggal_diagnosis'] ? 'Didiagnosis: ' . formatTanggalIndo($d['tanggal_diagnosis']) : 'Tanggal diagnosis tidak dicatat' ?>
                        <?= $d['catatan'] ? ' · ' . clean($d['catatan']) : '' ?>
                    </div>
                </div>
                <div class="list-actions">
                    <a href="?edit=<?= $d['disease_id'] ?>" class="btn btn-ghost btn-sm">Ubah</a>
                    <form method="post" onsubmit="return confirm('Hapus data penyakit ini?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="disease_id" value="<?= $d['disease_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
