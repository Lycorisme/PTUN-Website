<?php
// =============================================
// PESERTA SIDEBAR - LEFT MENU
// C:\laragon\www\ptun-website\peserta\includes\sidebar.php
// =============================================

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

$base_url = (isset($base_url)) ? $base_url : '../';

// Get stats
$peserta_id = $_SESSION['user_id'];
$stats = get_absensi_stats($peserta_id);
$percentage = absensi_percentage($peserta_id);
?>

<!-- SIDEBAR -->
<aside class="w-64 bg-white shadow-xl min-h-screen fixed left-0 top-16 bottom-0 overflow-y-auto">
    <div class="p-6">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Menu Peserta</h3>
        
        <nav class="space-y-2">
            <!-- Dashboard -->
            <a href="<?= $base_url ?>index.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $current_dir == 'peserta' && $current_page == 'index' ? 'bg-green-500 text-white' : 'text-gray-700 hover:bg-green-50' ?>">
                <i class="fas fa-home text-lg"></i>
                <span class="font-semibold">Dashboard</span>
            </a>

            <!-- Absensi -->
            <a href="<?= $base_url ?>absensi/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $current_dir == 'absensi' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-blue-50' ?>">
                <i class="fas fa-calendar-check text-lg"></i>
                <span class="font-semibold">Absensi</span>
            </a>

            <!-- Perkara -->
            <a href="<?= $base_url ?>perkara/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $current_dir == 'perkara' ? 'bg-purple-500 text-white' : 'text-gray-700 hover:bg-purple-50' ?>">
                <i class="fas fa-briefcase text-lg"></i>
                <span class="font-semibold">Perkara</span>
            </a>

            <!-- Laporan -->
            <a href="<?= $base_url ?>laporan/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $current_dir == 'laporan' ? 'bg-pink-500 text-white' : 'text-gray-700 hover:bg-pink-50' ?>">
                <i class="fas fa-chart-line text-lg"></i>
                <span class="font-semibold">Laporan</span>
            </a>

            <!-- Sertifikat -->
            <a href="<?= $base_url ?>sertifikat/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $current_dir == 'sertifikat' ? 'bg-orange-500 text-white' : 'text-gray-700 hover:bg-orange-50' ?>">
                <i class="fas fa-certificate text-lg"></i>
                <span class="font-semibold">Sertifikat</span>
            </a>

            <hr class="my-4">

            <!-- Profile -->
            <a href="<?= $base_url ?>profile/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $current_dir == 'profile' ? 'bg-green-500 text-white' : 'text-gray-700 hover:bg-green-50' ?>">
                <i class="fas fa-user-circle text-lg"></i>
                <span class="font-semibold">Profile</span>
            </a>

            <!-- Logout -->
            <a href="<?= $base_url ?>../login/logout.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 transition-all">
                <i class="fas fa-sign-out-alt text-lg"></i>
                <span class="font-semibold">Logout</span>
            </a>
        </nav>

        <!-- Stats Card -->
        <div class="mt-8 p-4 bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl">
            <p class="text-xs text-gray-600 font-semibold mb-3">Statistik Absensi</p>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600">Kehadiran</span>
                        <span class="font-bold text-green-600"><?= $stats['hadir'] ?>/<?= absensi_max_hari() ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 rounded-full h-2" style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>
                <div class="text-center pt-2 border-t border-green-200">
                    <span class="text-2xl font-bold text-green-600"><?= $percentage ?>%</span>
                    <p class="text-xs text-gray-600">Persentase Hadir</p>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<div class="ml-64 pt-16">
    <!-- Content will be placed here by each page -->
</div>

<style>
/* Adjust body to account for sidebar */
body {
    padding-left: 16rem; /* 64 = w-64 */
}
</style>