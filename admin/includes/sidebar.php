<?php
// =============================================
// ADMIN SIDEBAR - PROFESSIONAL LAYOUT
// =============================================

$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden glass-effect transition-opacity"></div>

<aside id="main-sidebar" class="w-64 bg-white shadow-2xl h-[calc(100vh-4rem)] fixed left-0 top-16 overflow-hidden fixed-sidebar border-r border-gray-200 transform -translate-x-full md:translate-x-0 sidebar-transition flex flex-col">
    
    <div class="flex-1 overflow-y-auto p-4 space-y-6 custom-scrollbar">
        
        <div>
            <nav class="space-y-1">
                <a href="<?= BASE_URL ?>/admin/" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'admin' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' ?>">
                    <i class="fas fa-th-large w-5 text-center <?= $current_dir == 'admin' ? '' : 'text-gray-400 group-hover:text-blue-600' ?>"></i>
                    <span class="font-semibold text-sm">Dashboard</span>
                </a>
            </nav>
        </div>

        <div>
            <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-4">Manajemen Peserta</h3>
            <nav class="space-y-1">
                <a href="<?= BASE_URL ?>/admin/registrasi/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'registrasi' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-users w-5 text-center <?= $current_dir == 'registrasi' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' ?>"></i>
                    <span class="text-sm font-medium">Data Peserta</span>
                    <?php
                    $stmt = db()->query("SELECT COUNT(*) as count FROM users WHERE role='peserta' AND status='pending'");
                    $pending = $stmt->fetch()['count'];
                    if($pending > 0):
                    ?>
                    <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $pending ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>/admin/absensi/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'absensi' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-calendar-check w-5 text-center <?= $current_dir == 'absensi' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' ?>"></i>
                    <span class="text-sm font-medium">Rekap Absensi</span>
                </a>

                <a href="<?= BASE_URL ?>/admin/aktivitas/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'aktivitas' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-book-open w-5 text-center <?= $current_dir == 'aktivitas' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' ?>"></i>
                    <span class="text-sm font-medium">Logbook Harian</span>
                </a>
            </nav>
        </div>

        <div>
            <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-4">Evaluasi & Hasil</h3>
            <nav class="space-y-1">
                <a href="<?= BASE_URL ?>/admin/penilaian/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'penilaian' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-star w-5 text-center <?= $current_dir == 'penilaian' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' ?>"></i>
                    <span class="text-sm font-medium">Input Penilaian</span>
                </a>

                <a href="<?= BASE_URL ?>/admin/laporan/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'laporan' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-clipboard-list w-5 text-center <?= $current_dir == 'laporan' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' ?>"></i>
                    <span class="text-sm font-medium">Laporan Akhir</span>
                </a>

                <a href="<?= BASE_URL ?>/admin/sertifikat/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'sertifikat' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-award w-5 text-center <?= $current_dir == 'sertifikat' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' ?>"></i>
                    <span class="text-sm font-medium">E-Sertifikat</span>
                </a>
            </nav>
        </div>

        <div>
            <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-4">Sistem</h3>
            <nav class="space-y-1">
                <a href="<?= BASE_URL ?>/admin/notifikasi/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'notifikasi' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-bell w-5 text-center <?= $current_dir == 'notifikasi' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' ?>"></i>
                    <span class="text-sm font-medium">Broadcast Pesan</span>
                </a>

                <a href="<?= BASE_URL ?>/admin/setting/" 
                   class="flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all group <?= $current_dir == 'setting' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas fa-cog w-5 text-center <?= $current_dir == 'setting' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' ?>"></i>
                    <span class="text-sm font-medium">Pengaturan</span>
                </a>
            </nav>
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