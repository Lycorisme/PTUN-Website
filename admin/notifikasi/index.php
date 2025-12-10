<?php
// =============================================
// ADMIN NOTIFIKASI - KIRIM PESAN
// =============================================

require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/login/');
    exit;
}

// HANDLE KIRIM NOTIFIKASI
if(isset($_POST['send_notification'])) {
    $target = $_POST['target_user'];
    $title = $_POST['title'];
    $pesan = $_POST['pesan'];
    $tipe = $_POST['tipe']; 
    
    // Validasi tipe agar sesuai database (ENUM: info, success, warning)
    $valid_types = ['info', 'success', 'warning'];
    if(!in_array($tipe, $valid_types)) {
        $tipe = 'info'; // Default fallback
    }
    
    $targets = [];
    
    if($target == 'all') {
        // Ambil semua peserta aktif
        $stmt = db()->query("SELECT id FROM users WHERE role='peserta' AND status='aktif'");
        $targets = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // Peserta spesifik
        $targets[] = $target;
    }
    
    // Batch Insert
    $sql = "INSERT INTO notifications (to_user_id, title, pesan, tipe, dibaca, created_at) VALUES (?, ?, ?, ?, 0, NOW())";
    $stmt = db()->prepare($sql);
    
    $count = 0;
    try {
        foreach($targets as $uid) {
            $stmt->execute([$uid, $title, $pesan, $tipe]);
            $count++;
        }
        header("Location: index.php?msg=sent&count=$count");
        exit;
    } catch(PDOException $e) {
        $error = "Gagal mengirim notifikasi: " . $e->getMessage();
    }
}

// AMBIL DATA PESERTA UNTUK DROPDOWN
$stmt = db()->query("SELECT id, nama, instansi FROM users WHERE role='peserta' AND status='aktif' ORDER BY nama ASC");
$peserta_list = $stmt->fetchAll();

// AMBIL RIWAYAT NOTIFIKASI KELUAR (Terakhir 20)
$stmt = db()->query("
    SELECT n.*, u.nama as penerima 
    FROM notifications n 
    LEFT JOIN users u ON n.to_user_id = u.id 
    ORDER BY n.created_at DESC 
    LIMIT 20
");
$history = $stmt->fetchAll();

$page_title = 'Kirim Notifikasi';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">

    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm flex items-center">
        <i class="fas fa-check-circle mr-3 text-xl"></i>
        <div>
            <p class="font-bold">Berhasil!</p>
            <p class="text-sm">Notifikasi berhasil dikirim ke <?= htmlspecialchars($_GET['count']) ?> peserta.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8 shadow-sm flex items-center">
        <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
        <div>
            <p class="font-bold">Error!</p>
            <p class="text-sm"><?= htmlspecialchars($error) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-3xl shadow-xl p-8 sticky top-24">
                <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-4 flex items-center">
                    <i class="fas fa-paper-plane text-blue-600 mr-3"></i> Buat Notifikasi
                </h2>
                
                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Penerima</label>
                        <select name="target_user" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 bg-gray-50">
                            <option value="" disabled selected>-- Pilih Penerima --</option>
                            <option value="all" class="font-bold text-blue-600">📢 KIRIM KE SEMUA PESERTA</option>
                            <optgroup label="Peserta Individu">
                                <?php foreach($peserta_list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['instansi']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Judul Notifikasi</label>
                        <input type="text" name="title" required placeholder="Contoh: Pengumuman Penting"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tipe Pesan</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="tipe" value="info" class="peer sr-only" checked>
                                <div class="p-3 text-center border-2 border-gray-200 rounded-xl peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 transition-all">
                                    <i class="fas fa-info-circle text-blue-500 mb-1"></i>
                                    <div class="text-xs font-bold text-gray-600">Info</div>
                                </div>
                            </label>
                            
                            <label class="cursor-pointer">
                                <input type="radio" name="tipe" value="warning" class="peer sr-only">
                                <div class="p-3 text-center border-2 border-gray-200 rounded-xl peer-checked:border-yellow-500 peer-checked:bg-yellow-50 hover:bg-gray-50 transition-all">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mb-1"></i>
                                    <div class="text-xs font-bold text-gray-600">Peringatan</div>
                                </div>
                            </label>
                            
                            <label class="cursor-pointer">
                                <input type="radio" name="tipe" value="success" class="peer sr-only">
                                <div class="p-3 text-center border-2 border-gray-200 rounded-xl peer-checked:border-green-500 peer-checked:bg-green-50 hover:bg-gray-50 transition-all">
                                    <i class="fas fa-check-circle text-green-500 mb-1"></i>
                                    <div class="text-xs font-bold text-gray-600">Sukses</div>
                                </div>
                            </label>
                            
                            <label class="cursor-pointer">
                                <input type="radio" name="tipe" value="warning" class="peer sr-only">
                                <div class="p-3 text-center border-2 border-gray-200 rounded-xl peer-checked:border-red-500 peer-checked:bg-red-50 hover:bg-gray-50 transition-all">
                                    <i class="fas fa-bolt text-red-500 mb-1"></i>
                                    <div class="text-xs font-bold text-gray-600">Penting</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Isi Pesan</label>
                        <textarea name="pesan" required rows="4" placeholder="Tuliskan pesan Anda..."
                                  class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500"></textarea>
                    </div>

                    <button type="submit" name="send_notification" onclick="return confirm('Kirim notifikasi ini?')"
                            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg hover:-translate-y-1 transition-all">
                        <i class="fas fa-paper-plane mr-2"></i>Kirim Sekarang
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center">
                    <i class="fas fa-history text-gray-500 mr-3"></i> Riwayat Pengiriman
                </h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-100">
                                <th class="text-left py-4 px-4 text-gray-500 font-bold">Waktu</th>
                                <th class="text-left py-4 px-4 text-gray-500 font-bold">Kepada</th>
                                <th class="text-left py-4 px-4 text-gray-500 font-bold">Pesan</th>
                                <th class="text-center py-4 px-4 text-gray-500 font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($history as $h): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-4 text-sm text-gray-500 whitespace-nowrap">
                                    <?= date('d/m H:i', strtotime($h['created_at'])) ?>
                                </td>
                                <td class="py-4 px-4 font-semibold text-gray-700">
                                    <?= $h['penerima'] ? htmlspecialchars($h['penerima']) : '<span class="text-red-400 italic">User Dihapus</span>' ?>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="font-bold text-gray-800 text-sm">
                                        <?php if($h['tipe'] == 'warning'): ?>
                                            <i class="fas fa-exclamation-circle text-orange-500 mr-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($h['title']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500 truncate max-w-xs"><?= htmlspecialchars($h['pesan']) ?></p>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <?php if($h['dibaca']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-double mr-1"></i> Dibaca
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <i class="fas fa-check mr-1"></i> Terkirim
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

    </div>
</div>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>