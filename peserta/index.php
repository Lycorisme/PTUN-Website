<?php
// =============================================
// PESERTA DASHBOARD - PTUN WEBSITE
// C:\laragon\www\ptun-website\peserta\index.php
// =============================================

require_once '../config/database.php';
session_start();

// Protect peserta page
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') {
    header('Location: ../index.php');
    exit;
}

$user = $_SESSION['user_data'];
$peserta_id = $user['id'];

// GET ABSENSI STATS BULAN INI
$bulan_ini = date('n');
$tahun_ini = date('Y');
$stats = get_absensi_stats($peserta_id, $bulan_ini, $tahun_ini);
$total_hadir = $stats['hadir'];
$total_hari_kerja = 15; // Default hari kerja per bulan

// GET PERKARA COUNT
$stmt = db()->prepare("SELECT COUNT(*) as total FROM perkara WHERE peserta_id=?");
$stmt->execute([$peserta_id]);
$total_perkara = $stmt->fetch()['total'];

// GET SERTIFIKAT STATUS
$stmt = db()->prepare("SELECT status, file_path FROM sertifikat WHERE peserta_id=? LIMIT 1");
$stmt->execute([$peserta_id]);
$sertifikat = $stmt->fetch();
$sertifikat_status = $sertifikat ? $sertifikat['status'] : 'pending';

// GET NOTIFICATIONS
$stmt = db()->prepare("SELECT * FROM notifications WHERE to_user_id=? AND dibaca=0 ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$peserta_id]);
$notifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peserta - <?= get_site_name() ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    </style>
</head>
<body class="bg-gray-50">

<!-- NAVBAR -->
<nav class="gradient-bg text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-graduate text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold">Halo, <?= htmlspecialchars($user['nama']) ?>!</h1>
                <p class="text-sm text-emerald-100"><?= htmlspecialchars($user['instansi']) ?></p>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <a href="profile/" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl font-semibold transition-all">
                <i class="fas fa-user-circle mr-2"></i>Profile
            </a>
            <a href="../index.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl font-semibold transition-all">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="max-w-7xl mx-auto px-6 py-8">

    <!-- STATUS CARD -->
    <div class="bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-3xl p-8 mb-8 shadow-xl">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2">Status: <?= strtoupper($user['status']) ?></h2>
                <p class="text-xl text-emerald-100">Absensi Bulan Ini: <?= $total_hadir ?>/<?= $total_hari_kerja ?> Hadir</p>
                <div class="mt-4 w-full bg-white/20 rounded-full h-3">
                    <div class="bg-white rounded-full h-3" style="width: <?= ($total_hadir/$total_hari_kerja)*100 ?>%"></div>
                </div>
            </div>
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center">
                <span class="text-4xl font-bold"><?= round(($total_hadir/$total_hari_kerja)*100) ?>%</span>
            </div>
        </div>
    </div>

    <!-- QUICK CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Card Absensi -->
        <a href="absensi/" class="bg-white rounded-3xl p-6 shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-calendar-check text-3xl text-white"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Absensi</h3>
            <p class="text-gray-600 mb-4">Cek-in harian & riwayat</p>
            <div class="flex items-center text-blue-600 font-semibold">
                <span>Buka</span>
                <i class="fas fa-arrow-right ml-2"></i>
            </div>
        </a>

        <!-- Card Perkara -->
        <a href="perkara/" class="bg-white rounded-3xl p-6 shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-briefcase text-3xl text-white"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Perkara</h3>
            <p class="text-gray-600 mb-4"><?= $total_perkara ?> perkara tercatat</p>
            <div class="flex items-center text-purple-600 font-semibold">
                <span>Buka</span>
                <i class="fas fa-arrow-right ml-2"></i>
            </div>
        </a>

        <!-- Card Sertifikat -->
        <a href="sertifikat/" class="bg-white rounded-3xl p-6 shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all">
            <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-certificate text-3xl text-white"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Sertifikat</h3>
            <?php if($sertifikat_status == 'tersedia'): ?>
                <p class="text-green-600 font-bold mb-4">✓ Tersedia Download</p>
            <?php elseif($sertifikat_status == 'selesai'): ?>
                <p class="text-blue-600 font-bold mb-4">✓ Sudah Diunduh</p>
            <?php else: ?>
                <p class="text-gray-600 mb-4">Status: Pending</p>
            <?php endif; ?>
            <div class="flex items-center text-orange-600 font-semibold">
                <span>Buka</span>
                <i class="fas fa-arrow-right ml-2"></i>
            </div>
        </a>
    </div>

    <!-- QUICK MENU -->
    <div class="bg-white rounded-3xl shadow-xl p-8 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Menu Cepat</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="absensi/" class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl hover:shadow-lg transition-all text-center">
                <i class="fas fa-calendar-day text-4xl text-blue-600 mb-3"></i>
                <p class="font-bold text-gray-800">Absensi</p>
            </a>
            <a href="perkara/" class="p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl hover:shadow-lg transition-all text-center">
                <i class="fas fa-file-alt text-4xl text-purple-600 mb-3"></i>
                <p class="font-bold text-gray-800">Perkara</p>
            </a>
            <a href="laporan/" class="p-6 bg-gradient-to-br from-pink-50 to-pink-100 rounded-2xl hover:shadow-lg transition-all text-center">
                <i class="fas fa-chart-line text-4xl text-pink-600 mb-3"></i>
                <p class="font-bold text-gray-800">Laporan</p>
            </a>
            <a href="sertifikat/" class="p-6 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl hover:shadow-lg transition-all text-center">
                <i class="fas fa-award text-4xl text-orange-600 mb-3"></i>
                <p class="font-bold text-gray-800">Sertifikat</p>
            </a>
        </div>
    </div>

    <!-- NOTIFICATIONS -->
    <?php if(count($notifications) > 0): ?>
    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-bell text-orange-500 mr-3"></i>
            Notifikasi Terbaru
            <span class="ml-3 bg-red-500 text-white text-sm px-3 py-1 rounded-full"><?= count($notifications) ?></span>
        </h2>
        <div class="space-y-4">
            <?php foreach($notifications as $notif): ?>
            <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border-l-4 border-blue-500">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg mb-2"><?= htmlspecialchars($notif['title']) ?></h3>
                        <p class="text-gray-700"><?= htmlspecialchars($notif['pesan']) ?></p>
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-clock mr-1"></i>
                            <?= format_tanggal_id($notif['created_at']) ?>
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= 
                        $notif['tipe']=='success' ? 'bg-green-100 text-green-800' : 
                        ($notif['tipe']=='warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') 
                    ?>">
                        <?= strtoupper($notif['tipe']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Welcome message
<?php if(!isset($_SESSION['welcomed'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Selamat Datang!',
    text: 'Halo <?= htmlspecialchars($user['nama']) ?>, selamat beraktivitas hari ini!',
    confirmButtonColor: '#10b981'
});
<?php $_SESSION['welcomed'] = true; ?>
<?php endif; ?>
</script>

</body>
</html>