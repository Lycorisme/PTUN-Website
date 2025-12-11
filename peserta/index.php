<?php
// =============================================
// PESERTA DASHBOARD - REALTIME STATS FIX
// =============================================

require_once '../config/database.php';

// Cek Login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$user = $_SESSION['user_data'];
$peserta_id = $user['id'];

// HANDLE MARK AS READ NOTIFICATIONS
if(isset($_GET['read_notif'])) {
    $notif_id = $_GET['read_notif'];
    if($notif_id == 'all') {
        $stmt = db()->prepare("UPDATE notifications SET dibaca=1 WHERE to_user_id=?");
        $stmt->execute([$peserta_id]);
    } else {
        $stmt = db()->prepare("UPDATE notifications SET dibaca=1 WHERE id=? AND to_user_id=?");
        $stmt->execute([$notif_id, $peserta_id]);
    }
    header('Location: index.php');
    exit;
}

// 1. HITUNG STATISTIK REALTIME (TERMASUK PENDING)
// Ambil max hari kerja dari settings (Default 22 hari jika belum diset)
$max_hari = (int)get_setting('absensi_max_hari', 22);

// UPDATE: Hitung semua status 'hadir' (abaikan approval agar realtime update)
$stmt = db()->prepare("SELECT COUNT(*) as total FROM absensi WHERE peserta_id=? AND status='hadir'");
$stmt->execute([$peserta_id]);
$total_hadir = $stmt->fetch()['total'];

// Hitung Persentase
$persentase = ($max_hari > 0) ? round(($total_hadir / $max_hari) * 100) : 0;
if($persentase > 100) $persentase = 100; // Cap di 100% jika kelebihan

// 2. GET AKTIVITAS COUNT
$stmt = db()->prepare("SELECT COUNT(*) as total FROM aktivitas WHERE peserta_id=?");
$stmt->execute([$peserta_id]);
$total_aktivitas = $stmt->fetch()['total'];

// 3. GET SERTIFIKAT STATUS
$stmt = db()->prepare("SELECT status FROM sertifikat WHERE peserta_id=? LIMIT 1");
$stmt->execute([$peserta_id]);
$sertifikat = $stmt->fetch();
$sertifikat_status = $sertifikat ? $sertifikat['status'] : 'pending';

// 4. GET NOTIFICATIONS
$stmt = db()->prepare("SELECT * FROM notifications WHERE to_user_id=? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$peserta_id]);
$notifications = $stmt->fetchAll();

$page_title = 'Dashboard Peserta';
require_once 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">

    <div class="bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-3xl p-8 mb-8 shadow-xl relative overflow-hidden transition-transform hover:scale-[1.01] duration-300">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/5 skew-x-12"></div>
        <div class="flex items-center justify-between relative z-10">
            <div>
                <h2 class="text-3xl font-bold mb-2">Status: <?= strtoupper($user['status']) ?></h2>
                <p class="text-xl text-emerald-100">
                    Total Kehadiran: <strong><?= $total_hadir ?></strong> / <?= $max_hari ?> Hari
                </p>
                
                <div class="mt-4 w-full bg-black/20 rounded-full h-4 max-w-md backdrop-blur-sm overflow-hidden border border-white/10">
                    <div class="bg-white/90 h-4 rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(255,255,255,0.5)]" 
                         style="width: <?= $persentase ?>%"></div>
                </div>
                <p class="text-xs text-emerald-100 mt-2 italic">*Data mencakup absensi pending & disetujui</p>
            </div>
            
            <div class="w-28 h-28 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-md border-4 border-white/30 shadow-lg">
                <div class="text-center">
                    <span class="text-3xl font-bold block"><?= $persentase ?>%</span>
                    <span class="text-[10px] uppercase tracking-wider">Completed</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <a href="<?= BASE_URL ?>/peserta/absensi/" class="bg-white rounded-3xl p-6 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all group border border-gray-100">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg shadow-blue-200">
                <i class="fas fa-calendar-check text-3xl text-white"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Absensi</h3>
            <p class="text-gray-600 mb-4">Cek-in harian & riwayat</p>
            <div class="flex items-center text-blue-600 font-semibold group-hover:gap-2 transition-all">
                <span>Buka</span>
                <i class="fas fa-arrow-right ml-2"></i>
            </div>
        </a>

        <a href="<?= BASE_URL ?>/peserta/aktivitas/" class="bg-white rounded-3xl p-6 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all group border border-gray-100">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg shadow-purple-200">
                <i class="fas fa-tasks text-3xl text-white"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Aktivitas</h3>
            <p class="text-gray-600 mb-4"><?= $total_aktivitas ?> aktivitas tercatat</p>
            <div class="flex items-center text-purple-600 font-semibold group-hover:gap-2 transition-all">
                <span>Buka</span>
                <i class="fas fa-arrow-right ml-2"></i>
            </div>
        </a>

        <a href="<?= BASE_URL ?>/peserta/sertifikat/" class="bg-white rounded-3xl p-6 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all group border border-gray-100">
            <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg shadow-orange-200">
                <i class="fas fa-certificate text-3xl text-white"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Sertifikat</h3>
            <?php if($sertifikat_status == 'tersedia' || $sertifikat_status == 'selesai'): ?>
                <p class="text-green-600 font-bold mb-4">✓ Siap Download</p>
            <?php else: ?>
                <p class="text-gray-600 mb-4">Status: <?= ucfirst($sertifikat_status) ?></p>
            <?php endif; ?>
            <div class="flex items-center text-orange-600 font-semibold group-hover:gap-2 transition-all">
                <span>Buka</span>
                <i class="fas fa-arrow-right ml-2"></i>
            </div>
        </a>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <div class="bg-orange-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-bell text-orange-500"></i>
                </div>
                Notifikasi Terbaru
            </h2>
            <?php if(count($notifications) > 0): ?>
            <a href="?read_notif=all" class="text-sm font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 px-4 py-2 rounded-xl transition-all">
                <i class="fas fa-check-double mr-1"></i> Tandai Semua Dibaca
            </a>
            <?php endif; ?>
        </div>

        <div class="space-y-4">
            <?php if(count($notifications) == 0): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                    <p>Belum ada notifikasi baru.</p>
                </div>
            <?php else: ?>
                <?php foreach($notifications as $notif): ?>
                <div class="group relative p-6 rounded-2xl border transition-all duration-300 <?= $notif['dibaca'] == 0 ? 'bg-blue-50/50 border-blue-200 shadow-sm' : 'bg-white border-gray-100 hover:border-gray-200' ?>">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 mt-1">
                                <?php if($notif['tipe'] == 'success'): ?>
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                                        <i class="fas fa-check"></i>
                                    </div>
                                <?php elseif($notif['tipe'] == 'warning'): ?>
                                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600">
                                        <i class="fas fa-exclamation"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                                        <i class="fas fa-info"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-lg mb-1 flex items-center">
                                    <?= htmlspecialchars($notif['title']) ?>
                                    <?php if($notif['dibaca'] == 0): ?>
                                        <span class="ml-2 w-2 h-2 bg-red-500 rounded-full inline-block"></span>
                                    <?php endif; ?>
                                </h3>
                                <p class="text-gray-600 text-sm leading-relaxed"><?= htmlspecialchars($notif['pesan']) ?></p>
                                <p class="text-xs text-gray-400 mt-2 font-medium">
                                    <i class="far fa-clock mr-1"></i>
                                    <?= format_tanggal_id($notif['created_at']) ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if($notif['dibaca'] == 0): ?>
                        <div class="flex-shrink-0">
                            <a href="?read_notif=<?= $notif['id'] ?>" class="text-xs font-semibold text-blue-600 bg-white border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                <i class="fas fa-check mr-1"></i> Tandai Dibaca
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
<?php if(!isset($_SESSION['welcomed'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Selamat Datang!',
    text: 'Halo <?= htmlspecialchars($user['nama']) ?>, selamat beraktivitas!',
    confirmButtonColor: '#10b981',
    timer: 2000,
    showConfirmButton: false
});
<?php $_SESSION['welcomed'] = true; ?>
<?php endif; ?>
</script>

<?php require_once 'includes/sidebar.php'; ?>

</body>
</html>