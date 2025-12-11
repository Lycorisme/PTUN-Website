<?php
// =============================================
// PESERTA SIDEBAR - PROFESSIONAL LAYOUT
// =============================================

$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$peserta_id = $_SESSION['user_id'];

// 1. KALKULASI STATISTIK REALTIME
// Ambil max hari dari settings (Default 22 hari kerja)
$max_hari = (int)get_setting('absensi_max_hari', 22);

// Hitung kehadiran valid (Hadir)
$stmt = db()->prepare("SELECT COUNT(*) as total FROM absensi WHERE peserta_id=? AND status='hadir'");
$stmt->execute([$peserta_id]);
$hadir_valid = $stmt->fetch()['total'];

// Hitung persentase progres
$percentage = ($max_hari > 0) ? round(($hadir_valid / $max_hari) * 100) : 0;
if($percentage > 100) $percentage = 100;
?>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden glass-effect transition-opacity"></div>

<aside id="main-sidebar" class="w-64 bg-white shadow-2xl h-[calc(100vh-4rem)] fixed left-0 top-16 overflow-hidden fixed-sidebar border-r border-gray-200 transform -translate-x-full md:translate-x-0 sidebar-transition flex flex-col">
    
    <div class="flex-1 overflow-y-auto p-4 space-y-6 custom-scrollbar">
        
        <div>
            <nav class="space-y-1">
                <a href="<?= BASE_URL ?>/peserta/" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'peserta' ? 'bg-green-600 text-white shadow-lg shadow-green-200' : 'text-gray-600 hover:bg-green-50 hover:text-green-600' ?>">
                    <i class="fas fa-th-large w-5 text-center <?= $current_dir == 'peserta' ? '' : 'text-gray-400 group-hover:text-green-600' ?>"></i>
                    <span class="font-semibold text-sm">Dashboard</span>
                </a>
            </nav>
        </div>

        <div>
            <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-4">Kegiatan Harian</h3>
            <nav class="space-y-1">
                <a href="<?= BASE_URL ?>/peserta/absensi/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'absensi' ? 'bg-green-50 text-green-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-calendar-check w-5 text-center <?= $current_dir == 'absensi' ? 'text-green-600' : 'text-gray-400 group-hover:text-green-600' ?>"></i>
                    <span class="text-sm font-medium">Isi Absensi</span>
                </a>

                <a href="<?= BASE_URL ?>/peserta/aktivitas/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'aktivitas' ? 'bg-green-50 text-green-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-tasks w-5 text-center <?= $current_dir == 'aktivitas' ? 'text-green-600' : 'text-gray-400 group-hover:text-green-600' ?>"></i>
                    <span class="text-sm font-medium">Logbook Aktivitas</span>
                </a>
            </nav>
        </div>

        <div>
            <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-4">Laporan & Hasil</h3>
            <nav class="space-y-1">
                <a href="<?= BASE_URL ?>/peserta/laporan/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'laporan' ? 'bg-green-50 text-green-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-file-alt w-5 text-center <?= $current_dir == 'laporan' ? 'text-green-600' : 'text-gray-400 group-hover:text-green-600' ?>"></i>
                    <span class="text-sm font-medium">Laporan Akhir</span>
                </a>

                <a href="<?= BASE_URL ?>/peserta/sertifikat/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'sertifikat' ? 'bg-green-50 text-green-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-certificate w-5 text-center <?= $current_dir == 'sertifikat' ? 'text-green-600' : 'text-gray-400 group-hover:text-green-600' ?>"></i>
                    <span class="text-sm font-medium">Sertifikat Saya</span>
                </a>
            </nav>
        </div>

        <div>
            <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-4">Pengaturan Akun</h3>
            <nav class="space-y-1">
                <a href="<?= BASE_URL ?>/peserta/profile/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'profile' ? 'bg-green-50 text-green-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-user-cog w-5 text-center <?= $current_dir == 'profile' ? 'text-green-600' : 'text-gray-400 group-hover:text-green-600' ?>"></i>
                    <span class="text-sm font-medium">Profil Saya</span>
                </a>
            </nav>
        </div>

        <div class="mt-4 p-4 bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl border border-green-100 mx-1">
            <div class="flex justify-between items-center mb-2">
                <p class="text-[10px] text-green-800 font-bold uppercase">Target Magang</p>
                <span class="text-xs font-bold text-green-700"><?= $percentage ?>%</span>
            </div>
            <div class="space-y-2">
                <div class="w-full bg-white/60 rounded-full h-2 overflow-hidden shadow-sm">
                    <div class="bg-green-500 h-2 transition-all duration-1000 rounded-full" style="width: <?= $percentage ?>%"></div>
                </div>
                <p class="text-[10px] text-green-600 font-medium text-center">
                    Telah hadir <strong><?= $hadir_valid ?></strong> dari <strong><?= $max_hari ?></strong> hari
                </p>
            </div>
        </div>

    </div>

    <div class="p-4 border-t border-gray-100 bg-gray-50/50">
        <a href="<?= BASE_URL ?>/login/logout.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 hover:text-red-700 transition-all group">
            <i class="fas fa-sign-out-alt w-5 text-center group-hover:-translate-x-1 transition-transform duration-300"></i>
            <span class="font-semibold text-sm">Keluar Aplikasi</span>
        </a>
    </div>

</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('main-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    if(btn){
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });
    }

    if(overlay){
        overlay.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    }
});
</script>