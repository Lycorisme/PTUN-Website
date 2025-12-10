<?php
require_once '../../config/database.php';
// session_start(); // DIHAPUS

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$user = $_SESSION['user_data'];
$peserta_id = $user['id'];

// HANDLE CEK-IN
if(isset($_POST['checkin'])) {
    $tanggal = date('Y-m-d');
    $catatan = $_POST['catatan'] ?? '';
    
    $stmt = db()->prepare("SELECT id FROM absensi WHERE peserta_id=? AND tanggal=?");
    $stmt->execute([$peserta_id, $tanggal]);
    
    if($stmt->fetch()) {
        $error = "Anda sudah absen hari ini!";
    } else {
        $stmt = db()->prepare("INSERT INTO absensi (peserta_id, tanggal, status, catatan, approved) VALUES (?, ?, 'hadir', ?, 0)");
        $stmt->execute([$peserta_id, $tanggal, $catatan]);
        header('Location: index.php?msg=success');
        exit;
    }
}

// GET DATA
$stmt = db()->prepare("SELECT * FROM absensi WHERE peserta_id=? ORDER BY tanggal DESC LIMIT 30");
$stmt->execute([$peserta_id]);
$riwayat = $stmt->fetchAll();

$today = date('Y-m-d');
$stmt = db()->prepare("SELECT id FROM absensi WHERE peserta_id=? AND tanggal=?");
$stmt->execute([$peserta_id, $today]);
$already_checked_in = $stmt->fetch() ? true : false;

$page_title = 'Absensi Harian';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">

    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-check-circle mr-2"></i>Absensi berhasil! Menunggu approval admin.
    </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-exclamation-triangle mr-2"></i><?= $error ?>
    </div>
    <?php endif; ?>

    <div class="bg-gradient-to-br from-green-600 to-emerald-700 text-white rounded-3xl p-8 mb-8 shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10">
            <i class="fas fa-clock text-9xl"></i>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-3xl font-bold mb-1">Cek-In Kehadiran</h2>
                    <p class="text-green-100 text-lg"><?= format_tanggal_id($today) ?></p>
                </div>
            </div>
            
            <?php if($already_checked_in): ?>
                <div class="bg-white/20 backdrop-blur-md rounded-2xl p-8 text-center border border-white/30">
                    <div class="inline-flex bg-white text-green-600 rounded-full p-4 mb-4 shadow-lg">
                        <i class="fas fa-check text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold">Terima Kasih!</h3>
                    <p class="text-green-50 mt-1">Anda sudah melakukan absensi hari ini.</p>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-4 bg-white/10 backdrop-blur-sm p-6 rounded-2xl border border-white/20">
                    <div>
                        <label class="block text-sm font-bold mb-2 text-green-50">Catatan Harian (Opsional)</label>
                        <textarea name="catatan" rows="2" placeholder="Sedang mengerjakan apa hari ini..."
                                  class="w-full px-4 py-3 border-0 rounded-xl text-gray-900 focus:ring-4 focus:ring-green-400 placeholder-gray-400 bg-white/90"></textarea>
                    </div>
                    <button type="submit" name="checkin" 
                            class="w-full bg-white text-green-700 py-4 rounded-xl font-bold text-lg hover:shadow-lg hover:bg-green-50 transition-all transform hover:-translate-y-1">
                        <i class="fas fa-fingerprint mr-2"></i>KLIK UNTUK ABSEN
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Riwayat 30 Hari Terakhir</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Tanggal</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Status</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Catatan</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($riwayat as $r): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4 font-semibold text-gray-900"><?= format_tanggal_id($r['tanggal']) ?></td>
                        <td class="py-4 px-4">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                <?= strtoupper($r['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-gray-600 italic"><?= htmlspecialchars($r['catatan'] ?? '-') ?></td>
                        <td class="py-4 px-4 text-center">
                            <?php if($r['approved']): ?>
                                <span class="text-green-600 font-bold text-sm"><i class="fas fa-check-circle"></i> Approved</span>
                            <?php else: ?>
                                <span class="text-orange-500 font-bold text-sm"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
<?php if(isset($_GET['msg'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: 'Absensi tercatat.',
    timer: 2000,
    showConfirmButton: false
});
<?php endif; ?>
</script>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>