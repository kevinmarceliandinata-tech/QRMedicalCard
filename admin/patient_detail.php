<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/qr_helper.php';
requireRole('admin');

$pdo = getDB();
$patientId = (int) ($_GET['id'] ?? 0);
$patient = getPatientById($pdo, $patientId);

if (!$patient) {
    setFlash('error', 'Pasien tidak ditemukan.');
    redirect(BASE_URL . '/admin/patients.php');
}

$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([$patient['user_id']]);
$user = $userStmt->fetch();

$qr = getQrByPatient($pdo, $patientId);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $tglLahir = trim($_POST['tanggal_lahir'] ?? '');
        $gender = $_POST['jenis_kelamin'] ?? 'L';
        $golDarah = trim($_POST['golongan_darah'] ?? '');
        $verified = isset($_POST['gol_darah_verified']) ? 1 : 0;
        $catatan = trim($_POST['catatan_khusus'] ?? '');

        if ($nama === '') $errors[] = 'Nama lengkap wajib diisi.';
        if (!strtotime($tglLahir)) $errors[] = 'Tanggal lahir tidak valid.';

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'UPDATE patients SET nama_lengkap=?, tanggal_lahir=?, jenis_kelamin=?, golongan_darah=?, gol_darah_verified=?, catatan_khusus=?
                 WHERE patient_id=?'
            );
            $stmt->execute([$nama, $tglLahir, $gender, $golDarah ?: null, $verified, $catatan ?: null, $patientId]);
            setFlash('success', 'Data pasien diperbarui oleh admin.');
            redirect(BASE_URL . '/admin/patient_detail.php?id=' . $patientId);
        }
    }

    if ($action === 'toggle_account') {
        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $user['id']]);
        setFlash('success', 'Status akun diubah menjadi "' . $newStatus . '".');
        redirect(BASE_URL . '/admin/patient_detail.php?id=' . $patientId);
    }

    if ($action === 'deactivate_qr') {
        deactivateQr($pdo, $patientId);
        setFlash('success', 'QR Code dinonaktifkan.');
        redirect(BASE_URL . '/admin/patient_detail.php?id=' . $patientId);
    }

    if ($action === 'activate_qr') {
        activateQr($pdo, $patientId);
        setFlash('success', 'QR Code diaktifkan kembali.');
        redirect(BASE_URL . '/admin/patient_detail.php?id=' . $patientId);
    }

    if ($action === 'reissue_qr') {
        reissueQr($pdo, $patientId);
        setFlash('success', 'Kartu baru diterbitkan dengan QR Code baru (kartu lama otomatis tidak berlaku).');
        redirect(BASE_URL . '/admin/patient_detail.php?id=' . $patientId);
    }

    if ($action === 'delete_disease') {
        $pdo->prepare('DELETE FROM disease_history WHERE disease_id = ? AND patient_id = ?')->execute([(int) $_POST['item_id'], $patientId]);
        redirect(BASE_URL . '/admin/patient_detail.php?id=' . $patientId);
    }
    if ($action === 'delete_allergy') {
        $pdo->prepare('DELETE FROM allergies WHERE allergy_id = ? AND patient_id = ?')->execute([(int) $_POST['item_id'], $patientId]);
        redirect(BASE_URL . '/admin/patient_detail.php?id=' . $patientId);
    }
    if ($action === 'delete_medication') {
        $pdo->prepare('DELETE FROM medications WHERE medication_id = ? AND patient_id = ?')->execute([(int) $_POST['item_id'], $patientId]);
        redirect(BASE_URL . '/admin/patient_detail.php?id=' . $patientId);
    }
    if ($action === 'delete_contact') {
        $pdo->prepare('DELETE FROM emergency_contacts WHERE contact_id = ? AND patient_id = ?')->execute([(int) $_POST['item_id'], $patientId]);
        redirect(BASE_URL . '/admin/patient_detail.php?id=' . $patientId);
    }

    // refresh in-memory patient data after any inline update above (except redirects already happened)
    $patient = getPatientById($pdo, $patientId);
}

$diseases = $pdo->prepare('SELECT * FROM disease_history WHERE patient_id = ? ORDER BY status = "aktif" DESC, tanggal_diagnosis DESC');
$diseases->execute([$patientId]); $diseases = $diseases->fetchAll();

$allergies = $pdo->prepare('SELECT * FROM allergies WHERE patient_id = ? ORDER BY created_at DESC');
$allergies->execute([$patientId]); $allergies = $allergies->fetchAll();

$medications = $pdo->prepare('SELECT * FROM medications WHERE patient_id = ? ORDER BY is_active DESC, created_at DESC');
$medications->execute([$patientId]); $medications = $medications->fetchAll();

$contacts = $pdo->prepare('SELECT * FROM emergency_contacts WHERE patient_id = ? ORDER BY is_primary DESC');
$contacts->execute([$patientId]); $contacts = $contacts->fetchAll();

$pageTitle = 'Kelola Pasien';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title">
        <h2><?= clean($patient['nama_lengkap']) ?> <span class="text-muted" style="font-weight:500;">(<?= clean($patient['patient_code']) ?>)</span></h2>
        <div class="list-actions">
            <span class="badge <?= $user['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">Akun <?= clean($user['status']) ?></span>
            <span class="badge <?= $qr['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">QR <?= clean($qr['status']) ?></span>
        </div>
    </div>

    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= clean($err) ?></div><?php endforeach; ?>

    <div class="grid-2">
        <div>
            <h4>Ubah Data Identitas (Bantuan Admin)</h4>
            <form method="post">
                <input type="hidden" name="action" value="update_profile">
                <div class="field"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" value="<?= clean($patient['nama_lengkap']) ?>" required></div>
                <div class="form-row">
                    <div class="field"><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?= clean($patient['tanggal_lahir']) ?>" required></div>
                    <div class="field">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin">
                            <option value="L" <?= $patient['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $patient['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Golongan Darah</label>
                        <select name="golongan_darah">
                            <option value="">— Belum diisi —</option>
                            <?php foreach (['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $g): ?>
                                <option value="<?= $g ?>" <?= $patient['golongan_darah'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field" style="display:flex;align-items:center;gap:8px;padding-top:26px;">
                        <input type="checkbox" name="gol_darah_verified" value="1" style="width:auto;" <?= $patient['gol_darah_verified'] ? 'checked' : '' ?>>
                        <label style="margin:0;">Verifikasi golongan darah</label>
                    </div>
                </div>
                <div class="field"><label>Kondisi Khusus</label><textarea name="catatan_khusus"><?= clean($patient['catatan_khusus'] ?? '') ?></textarea></div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </form>
        </div>

        <div>
            <h4>Status Akun &amp; Kartu</h4>
            <div class="panel" style="box-shadow:none;padding:18px;">
                <p><strong>Email:</strong> <?= clean($user['email']) ?><br>
                   <strong>Username:</strong> <?= clean($user['username']) ?></p>

                <form method="post" style="margin-bottom:10px;">
                    <input type="hidden" name="action" value="toggle_account">
                    <button type="submit" class="btn <?= $user['status'] === 'active' ? 'btn-danger' : 'btn-primary' ?> btn-sm">
                        <?= $user['status'] === 'active' ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>
                    </button>
                </form>

                <hr style="border:none;border-top:1px solid var(--line);margin:14px 0;">

                <p class="field-hint">Status QR saat ini: <strong><?= clean($qr['status']) ?></strong></p>
                <div class="list-actions" style="flex-wrap:wrap;">
                    <?php if ($qr['status'] === 'active'): ?>
                        <form method="post"><input type="hidden" name="action" value="deactivate_qr">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Nonaktifkan QR Code pasien ini? Biasanya dilakukan jika kartu hilang.');">Nonaktifkan QR (Kartu Hilang)</button>
                        </form>
                    <?php else: ?>
                        <form method="post"><input type="hidden" name="action" value="activate_qr">
                            <button type="submit" class="btn btn-primary btn-sm">Aktifkan Kembali QR</button>
                        </form>
                    <?php endif; ?>
                    <form method="post"><input type="hidden" name="action" value="reissue_qr">
                        <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Terbitkan QR Code BARU untuk pasien ini? QR/kartu lama tidak akan berlaku lagi.');">Terbitkan Kartu Baru</button>
                    </form>
                </div>

                <p style="margin-top:16px;">
                    <a href="<?= BASE_URL ?>/admin/card_preview.php?id=<?= $patientId ?>" target="_blank" class="btn btn-ghost btn-sm">Lihat Kartu Digital</a>
                    <a href="<?= emergencyUrl($qr['unique_token']) ?>" target="_blank" class="btn btn-ghost btn-sm">Buka Halaman Darurat</a>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-title"><h2>Riwayat Penyakit</h2></div>
        <?php if (empty($diseases)): ?><div class="empty-state">Tidak ada data.</div><?php endif; ?>
        <?php foreach ($diseases as $d): ?>
            <div class="list-item">
                <div>
                    <h4><?= clean($d['nama_penyakit']) ?> <span class="badge <?= $d['status'] === 'aktif' ? 'badge-active' : 'badge-inactive' ?>"><?= clean($d['status']) ?></span></h4>
                    <div class="meta"><?= $d['tanggal_diagnosis'] ? formatTanggalIndo($d['tanggal_diagnosis']) : '-' ?></div>
                </div>
                <form method="post" onsubmit="return confirm('Hapus data ini?');">
                    <input type="hidden" name="action" value="delete_disease">
                    <input type="hidden" name="item_id" value="<?= $d['disease_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                </form>
            </div>
        <?php endforeach; ?>

        <div class="panel-title"><h2>Alergi</h2></div>
        <?php if (empty($allergies)): ?><div class="empty-state">Tidak ada data.</div><?php endif; ?>
        <?php foreach ($allergies as $a): ?>
            <div class="list-item">
                <div><h4><?= clean($a['jenis_alergi']) ?></h4><div class="meta"><?= clean($a['keterangan'] ?? '-') ?></div></div>
                <form method="post" onsubmit="return confirm('Hapus data ini?');">
                    <input type="hidden" name="action" value="delete_allergy">
                    <input type="hidden" name="item_id" value="<?= $a['allergy_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="panel">
        <div class="panel-title"><h2>Obat</h2></div>
        <?php if (empty($medications)): ?><div class="empty-state">Tidak ada data.</div><?php endif; ?>
        <?php foreach ($medications as $m): ?>
            <div class="list-item">
                <div>
                    <h4><?= clean($m['nama_obat']) ?> <span class="badge <?= $m['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $m['is_active'] ? 'Digunakan' : 'Berhenti' ?></span></h4>
                    <div class="meta"><?= clean($m['dosis'] ?? '-') ?> · <?= clean($m['frekuensi'] ?? '-') ?></div>
                </div>
                <form method="post" onsubmit="return confirm('Hapus data ini?');">
                    <input type="hidden" name="action" value="delete_medication">
                    <input type="hidden" name="item_id" value="<?= $m['medication_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                </form>
            </div>
        <?php endforeach; ?>

        <div class="panel-title"><h2>Kontak Darurat</h2></div>
        <?php if (empty($contacts)): ?><div class="empty-state">Tidak ada data.</div><?php endif; ?>
        <?php foreach ($contacts as $c): ?>
            <div class="list-item">
                <div><h4><?= clean($c['nama_kontak']) ?> <?= $c['is_primary'] ? '<span class="badge badge-active">Utama</span>' : '' ?></h4><div class="meta"><?= clean($c['hubungan'] ?? '-') ?> · <?= clean($c['no_telepon']) ?></div></div>
                <form method="post" onsubmit="return confirm('Hapus kontak ini?');">
                    <input type="hidden" name="action" value="delete_contact">
                    <input type="hidden" name="item_id" value="<?= $c['contact_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<p class="text-muted">Untuk menambah entri baru (penyakit/alergi/obat/kontak), gunakan tombol "Lihat Halaman Darurat" bersama pasien, atau minta pasien menambahkannya sendiri melalui dashboard mereka. Admin dapat menghapus entri yang keliru dari sini.</p>

<?php include __DIR__ . '/../includes/footer.php'; ?>
