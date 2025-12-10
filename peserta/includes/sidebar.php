<?php
// =============================================
// PESERTA SIDEBAR - RESPONSIVE TOGGLE
// =============================================

$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$peserta_id = $_SESSION['user_id'];

// Get Stats
$stats = get_absensi_stats($peserta_id);
$percentage = absensi_percentage($peserta_id);
?>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity"></div>

<aside id="main-sidebar" class="w-64 bg-white shadow-2xl h-[calc(100vh-4rem)] fixed left-0 top-16 overflow-y-auto fixed-sidebar border-r border-gray-200 transform -translate-x-full md:translate-x-0 sidebar-transition">
    <div class="p-6">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 px-4">Menu Peserta</h3>
        
        <nav class="space-y-1">
            <a href="<?= BASE_URL ?>/peserta/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'peserta' ? 'bg-green-600 text-white shadow-lg shadow-green-200' : 'text-gray-600 hover:bg-green-50 hover:text-green-600' ?>">
                <i class="fas fa-home w-6 text-center"></i>
                <span class="font-semibold">Dashboard</span>
            </a>

            <a href="<?= BASE_URL ?>/peserta/absensi/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'absensi' ? 'bg-green-600 text-white shadow-lg shadow-green-200' : 'text-gray-600 hover:bg-green-50 hover:text-green-600' ?>">
                <i class="fas fa-calendar-check w-6 text-center"></i>
                <span class="font-semibold">Absensi</span>
            </a>

            <a href="<?= BASE_URL ?>/peserta/aktivitas/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'aktivitas' ? 'bg-green-600 text-white shadow-lg shadow-green-200' : 'text-gray-600 hover:bg-green-50 hover:text-green-600' ?>">
                <i class="fas fa-tasks w-6 text-center"></i>
                <span class="font-semibold">Aktivitas</span>
            </a>

            <a href="<?= BASE_URL ?>/peserta/laporan/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'laporan' ? 'bg-green-600 text-white shadow-lg shadow-green-200' : 'text-gray-600 hover:bg-green-50 hover:text-green-600' ?>">
                <i class="fas fa-chart-line w-6 text-center"></i>
                <span class="font-semibold">Laporan</span>
            </a>

            <a href="<?= BASE_URL ?>/peserta/sertifikat/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'sertifikat' ? 'bg-green-600 text-white shadow-lg shadow-green-200' : 'text-gray-600 hover:bg-green-50 hover:text-green-600' ?>">
                <i class="fas fa-certificate w-6 text-center"></i>
                <span class="font-semibold">Sertifikat</span>
            </a>

            <div class="my-4 border-t border-gray-100 mx-4"></div>

            <a href="<?= BASE_URL ?>/peserta/profile/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'profile' ? 'bg-green-600 text-white shadow-lg shadow-green-200' : 'text-gray-600 hover:bg-green-50 hover:text-green-600' ?>">
                <i class="fas fa-user-circle w-6 text-center"></i>
                <span class="font-semibold">Profile</span>
            </a>

            <a href="<?= BASE_URL ?>/login/logout.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 hover:text-red-700 transition-all mt-2 group">
                <i class="fas fa-sign-out-alt w-6 text-center group-hover:rotate-180 transition-transform duration-300"></i>
                <span class="font-semibold">Logout</span>
            </a>
        </nav>

        <div class="mt-8 p-4 bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl border border-green-100">
            <p class="text-[10px] text-green-800 font-bold mb-3 uppercase">Kehadiran</p>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600">Total</span>
                        <span class="font-bold text-green-700"><?= $stats['hadir'] ?>/<?= absensi_max_hari() ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 rounded-full h-2 transition-all duration-1000" style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>
                <div class="text-center pt-2 border-t border-green-200">
                    <span class="text-2xl font-bold text-green-600"><?= $percentage ?>%</span>
                </div>
            </div>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('main-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    // Toggle
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    });

    // Close on overlay click
    overlay.addEventListener('click', function() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });
});
</script>