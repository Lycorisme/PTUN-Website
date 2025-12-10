<?php
require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$user = $_SESSION['user_data'];
$peserta_id = $user['id'];

// HANDLE SUBMIT
if(isset($_POST['submit_aktivitas'])) {
    $tanggal = $_POST['tanggal'];
    $deskripsi = $_POST['deskripsi'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $bukti_file = null;
    
    // Handle File Upload
    if(isset($_FILES['bukti']) && $_FILES['bukti']['error'] == 0) {
        $target_dir = "../../uploads/aktivitas/"; // Note folder upload juga berubah
        if(!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        $file_ext = pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION);
        $filename = 'aktivitas_' . $peserta_id . '_' . time() . '.' . $file_ext;
        
        if(move_uploaded_file($_FILES['bukti']['tmp_name'], $target_dir . $filename)) {
            $bukti_file = 'uploads/aktivitas/' . $filename;
        }
    }
    
    $stmt = db()->prepare("INSERT INTO aktivitas (peserta_id, tanggal, deskripsi, bukti_file, jam_mulai, jam_selesai) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$peserta_id, $tanggal, $deskripsi, $bukti_file, $jam_mulai, $jam_selesai]);
    
    header('Location: index.php?msg=success');
    exit;
}

// GET HISTORY
$stmt = db()->prepare("SELECT * FROM aktivitas WHERE peserta_id=? ORDER BY tanggal DESC LIMIT 30");
$stmt->execute([$peserta_id]);
$riwayat = $stmt->fetchAll();

$page_title = 'Input Aktivitas';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-check-circle mr-2"></i>Aktivitas berhasil disubmit!
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8 mb-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-4">Form Jurnal Aktivitas Harian</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-2 text-gray-700">Tanggal</label>
                    <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block font-bold mb-2 text-gray-700">Upload Bukti (Foto/PDF)</label>
                    <input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-2 text-gray-700">Jam Mulai</label>
                    <input type="time" name="jam_mulai" required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block font-bold mb-2 text-gray-700">Jam Selesai</label>
                    <input type="time" name="jam_selesai" required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 outline-none transition-all">
                </div>
            </div>
            
            <div>
                <label class="block font-bold mb-2 text-gray-700">Deskripsi Aktivitas</label>
                <textarea name="deskripsi" required rows="5" placeholder="Jelaskan detail aktivitas yang dikerjakan..."
                          class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 outline-none transition-all"></textarea>
            </div>
            
            <button type="submit" name="submit_aktivitas"
                    class="w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all transform hover:-translate-y-1">
                <i class="fas fa-paper-plane mr-2"></i>Simpan Aktivitas
            </button>
        </form>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Riwayat Aktivitas</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Tanggal</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Deskripsi</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Jam</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($riwayat as $r): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4 font-semibold text-gray-900"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars(substr($r['deskripsi'], 0, 60)) ?>...</td>
                        <td class="py-4 px-4 text-gray-700 font-mono text-sm"><?= $r['jam_mulai'] ?> - <?= $r['jam_selesai'] ?></td>
                        <td class="py-4 px-4 text-center">
                            <?php if($r['approved']): ?>
                                <span class="text-green-600 font-bold bg-green-100 px-3 py-1 rounded-full text-xs">
                                    <i class="fas fa-check-circle mr-1"></i>Approved
                                </span>
                            <?php else: ?>
                                <span class="text-orange-600 font-bold bg-orange-100 px-3 py-1 rounded-full text-xs">
                                    <i class="fas fa-clock mr-1"></i>Pending
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>