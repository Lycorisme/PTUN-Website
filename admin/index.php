<?php
// =============================================
// ADMIN DASHBOARD - PTUN WEBSITE
// =============================================

require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$user = $_SESSION['user_data'];

// 1. GET STATISTICS
// Total Peserta
$stmt = db()->query("SELECT COUNT(*) as total FROM users WHERE role='peserta'");
$total_peserta = $stmt->fetch()['total'];

// Absensi Pending
$stmt = db()->query("SELECT COUNT(*) as total FROM absensi WHERE approved=0");
$pending_absensi = $stmt->fetch()['total'];

// Aktivitas Pending
$stmt = db()->query("SELECT COUNT(*) as total FROM aktivitas WHERE approved=0");
$pending_aktivitas = $stmt->fetch()['total'];

// Laporan Akhir
$stmt = db()->query("SELECT COUNT(*) as total FROM laporan_ringkasan");
$total_laporan_akhir = $stmt->fetch()['total'];

// 2. GET LATEST NOTIFICATIONS (Action Center)
$stmt = db()->prepare("
    SELECT n.*, u.nama as pengirim 
    FROM notifications n 
    LEFT JOIN users u ON n.to_user_id = u.id 
    WHERE n.to_user_id = ? AND n.dibaca = 0
    ORDER BY n.created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$notifs = $stmt->fetchAll();

// 3. GET LIST PESERTA TERBARU
$stmt = db()->query("SELECT id, nama, email, instansi, jurusan, status, created_at FROM users WHERE role='peserta' ORDER BY created_at DESC LIMIT 5");
$peserta_list = $stmt->fetchAll();

// HANDLE DELETE & UPDATE (Action Buttons)
if(isset($_POST['delete_peserta'])) {
    $id = $_POST['peserta_id'];
    $stmt = db()->prepare("DELETE FROM users WHERE id=? AND role='peserta'");
    $stmt->execute([$id]);
    header('Location: index.php?msg=deleted');
    exit;
}
if(isset($_POST['update_status'])) {
    $id = $_POST['peserta_id'];
    $status = $_POST['status'];
    $stmt = db()->prepare("UPDATE users SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);
    header('Location: index.php?msg=updated');
    exit;
}

$page_title = 'Dashboard Admin';
require_once 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <div class="lg:col-span-2 bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-3xl p-8 shadow-xl flex items-center justify-between relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-3xl font-bold mb-2">Selamat Datang, Admin!</h2>
                <p class="text-blue-100 mb-6 max-w-lg">
                    Pantau aktivitas peserta, verifikasi absensi, dan kelola penilaian magang dalam satu dashboard.
                </p>
                <div class="flex space-x-4">
                    <a href="notifikasi/" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm px-6 py-3 rounded-xl font-semibold transition-all flex items-center">
                        <i class="fas fa-paper-plane mr-2"></i> Kirim Pesan
                    </a>
                    <a href="registrasi/" class="bg-white text-blue-700 px-6 py-3 rounded-xl font-bold shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all flex items-center">
                        <i class="fas fa-user-plus mr-2"></i> Cek Pendaftar
                    </a>
                </div>
            </div>
            <div class="hidden md:block relative z-10">
                <div class="w-32 h-32 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-md border border-white/20">
                    <i class="fas fa-user-shield text-5xl"></i>
                </div>
            </div>
            <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>
        </div>

        <div class="bg-white rounded-3xl shadow-xl p-6 border border-gray-100">
            <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center">
                <div class="w-2 h-6 bg-orange-500 rounded-full mr-3"></div>
                Action Center
            </h3>
            <div class="space-y-3">
                
                <a href="absensi/" class="flex items-center justify-between p-4 rounded-2xl transition-all <?= $pending_absensi > 0 ? 'bg-orange-50 border border-orange-100 hover:bg-orange-100' : 'bg-gray-50 border border-gray-100' ?>">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $pending_absensi > 0 ? 'bg-orange-100 text-orange-600' : 'bg-gray-200 text-gray-400' ?>">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-bold text-gray-800">Absensi</p>
                            <p class="text-xs text-gray-500">Menunggu Approval</p>
                        </div>
                    </div>
                    <?php if($pending_absensi > 0): ?>
                        <span class="bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-lg"><?= $pending_absensi ?></span>
                    <?php else: ?>
                        <i class="fas fa-check text-green-500"></i>
                    <?php endif; ?>
                </a>

                <a href="aktivitas/" class="flex items-center justify-between p-4 rounded-2xl transition-all <?= $pending_aktivitas > 0 ? 'bg-purple-50 border border-purple-100 hover:bg-purple-100' : 'bg-gray-50 border border-gray-100' ?>">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $pending_aktivitas > 0 ? 'bg-purple-100 text-purple-600' : 'bg-gray-200 text-gray-400' ?>">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-bold text-gray-800">Aktivitas</p>
                            <p class="text-xs text-gray-500">Perlu Verifikasi</p>
                        </div>
                    </div>
                    <?php if($pending_aktivitas > 0): ?>
                        <span class="bg-purple-500 text-white text-xs font-bold px-2 py-1 rounded-lg"><?= $pending_aktivitas ?></span>
                    <?php else: ?>
                        <i class="fas fa-check text-green-500"></i>
                    <?php endif; ?>
                </a>

                <a href="laporan/?tab=akhir" class="flex items-center justify-between p-4 rounded-2xl transition-all bg-gray-50 border border-gray-100 hover:bg-pink-50 hover:border-pink-100">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center bg-pink-100 text-pink-600">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-bold text-gray-800">Laporan Akhir</p>
                            <p class="text-xs text-gray-500">Total Masuk</p>
                        </div>
                    </div>
                    <span class="bg-pink-500 text-white text-xs font-bold px-2 py-1 rounded-lg"><?= $total_laporan_akhir ?></span>
                </a>

            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-8">
        <a href="registrasi/" class="p-4 bg-white rounded-2xl shadow-sm hover:shadow-md transition-all text-center group border border-gray-100 hover:border-green-200">
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mx-auto mb-3 text-green-600 group-hover:bg-green-600 group-hover:text-white transition-all">
                <i class="fas fa-user-plus text-xl"></i>
            </div>
            <p class="text-sm font-bold text-gray-700">Registrasi</p>
        </a>
        <a href="absensi/" class="p-4 bg-white rounded-2xl shadow-sm hover:shadow-md transition-all text-center group border border-gray-100 hover:border-orange-200">
            <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-3 text-orange-600 group-hover:bg-orange-600 group-hover:text-white transition-all">
                <i class="fas fa-calendar-day text-xl"></i>
            </div>
            <p class="text-sm font-bold text-gray-700">Absensi</p>
        </a>
        <a href="aktivitas/" class="p-4 bg-white rounded-2xl shadow-sm hover:shadow-md transition-all text-center group border border-gray-100 hover:border-purple-200">
            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mx-auto mb-3 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-all">
                <i class="fas fa-tasks text-xl"></i>
            </div>
            <p class="text-sm font-bold text-gray-700">Aktivitas</p>
        </a>
        
        <a href="penilaian/" class="p-4 bg-white rounded-2xl shadow-sm hover:shadow-md transition-all text-center group border border-gray-100 hover:border-teal-200">
            <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center mx-auto mb-3 text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-all">
                <i class="fas fa-star text-xl"></i>
            </div>
            <p class="text-sm font-bold text-gray-700">Penilaian</p>
        </a>

        <a href="laporan/" class="p-4 bg-white rounded-2xl shadow-sm hover:shadow-md transition-all text-center group border border-gray-100 hover:border-pink-200">
            <div class="w-12 h-12 bg-pink-50 rounded-xl flex items-center justify-center mx-auto mb-3 text-pink-600 group-hover:bg-pink-600 group-hover:text-white transition-all">
                <i class="fas fa-chart-bar text-xl"></i>
            </div>
            <p class="text-sm font-bold text-gray-700">Laporan</p>
        </a>
        <a href="sertifikat/" class="p-4 bg-white rounded-2xl shadow-sm hover:shadow-md transition-all text-center group border border-gray-100 hover:border-yellow-200">
            <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center mx-auto mb-3 text-yellow-600 group-hover:bg-yellow-600 group-hover:text-white transition-all">
                <i class="fas fa-certificate text-xl"></i>
            </div>
            <p class="text-sm font-bold text-gray-700">Sertifikat</p>
        </a>
        <a href="setting/" class="p-4 bg-white rounded-2xl shadow-sm hover:shadow-md transition-all text-center group border border-gray-100 hover:border-blue-200">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all">
                <i class="fas fa-cog text-xl"></i>
            </div>
            <p class="text-sm font-bold text-gray-700">Settings</p>
        </a>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Peserta Terbaru</h2>
            <a href="registrasi/" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">Lihat Semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-100">
                        <th class="text-left py-4 px-4 font-bold text-gray-600 text-sm">Nama</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-600 text-sm">Instansi</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-600 text-sm">Status</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-600 text-sm">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($peserta_list as $p): ?>
                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition-all">
                        <td class="py-4 px-4">
                            <p class="font-bold text-gray-800"><?= htmlspecialchars($p['nama']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($p['email']) ?></p>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-600"><?= htmlspecialchars($p['instansi']) ?></td>
                        <td class="py-4 px-4">
                            <?php 
                            $badge = [
                                'aktif' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'selesai' => 'bg-blue-100 text-blue-700'
                            ];
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $badge[$p['status']] ?>">
                                <?= $p['status'] ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <button onclick="editPeserta(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama']) ?>', '<?= $p['status'] ?>')" 
                                    class="text-blue-600 hover:text-blue-800 transition-colors mx-2">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div id="editModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 transform scale-100 transition-transform">
        <h3 class="text-2xl font-bold text-gray-900 mb-6">Update Status Peserta</h3>
        <form method="POST">
            <input type="hidden" name="peserta_id" id="edit_peserta_id">
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Peserta</label>
                <input type="text" id="edit_nama" readonly class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl bg-gray-50 text-gray-500">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Status Magang</label>
                <select name="status" id="edit_status" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 bg-white">
                    <option value="pending">Pending (Menunggu Persetujuan)</option>
                    <option value="aktif">Aktif (Sedang Magang)</option>
                    <option value="selesai">Selesai (Tamat)</option>
                </select>
            </div>
            <div class="flex space-x-4">
                <button type="submit" name="update_status" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition-all">
                    Simpan Perubahan
                </button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto hide success message
setTimeout(() => {
    const msg = document.getElementById('successMsg');
    if(msg) msg.style.display = 'none';
}, 3000);

function editPeserta(id, nama, status) {
    document.getElementById('edit_peserta_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_status').value = status;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('editModal').addEventListener('click', function(e) {
    if(e.target === this) closeModal();
});
</script>

<?php require_once 'includes/sidebar.php'; ?>

</body>
</html>