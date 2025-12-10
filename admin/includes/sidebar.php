<?php
// =============================================
// ADMIN SIDEBAR - WITH NOTIFIKASI MENU
// =============================================

$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden glass-effect transition-opacity"></div>

<aside id="main-sidebar" class="w-64 bg-white shadow-2xl h-[calc(100vh-4rem)] fixed left-0 top-16 overflow-y-auto fixed-sidebar border-r border-gray-200 transform -translate-x-full md:translate-x-0 sidebar-transition">
    <div class="p-6">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 px-4">Menu Admin</h3>
        
        <nav class="space-y-1">
            <a href="<?= BASE_URL ?>/admin/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'admin' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' ?>">
                <i class="fas fa-home w-6 text-center <?= $current_dir == 'admin' ? '' : 'text-gray-400 group-hover:text-blue-600' ?>"></i>
                <span class="font-semibold">Dashboard</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/notifikasi/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'notifikasi' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' ?>">
                <i class="fas fa-paper-plane w-6 text-center <?= $current_dir == 'notifikasi' ? '' : 'text-gray-400 group-hover:text-blue-600' ?>"></i>
                <span class="font-semibold">Kirim Notifikasi</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/registrasi/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'registrasi' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' ?>">
                <i class="fas fa-user-plus w-6 text-center <?= $current_dir == 'registrasi' ? '' : 'text-gray-400 group-hover:text-blue-600' ?>"></i>
                <span class="font-semibold">Registrasi</span>
                <?php
                $stmt = db()->query("SELECT COUNT(*) as count FROM users WHERE role='peserta' AND status='pending'");
                $pending = $stmt->fetch()['count'];
                if($pending > 0):
                ?>
                <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse"><?= $pending ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= BASE_URL ?>/admin/absensi/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'absensi' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' ?>">
                <i class="fas fa-calendar-check w-6 text-center <?= $current_dir == 'absensi' ? '' : 'text-gray-400 group-hover:text-blue-600' ?>"></i>
                <span class="font-semibold">Absensi</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/aktivitas/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'aktivitas' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' ?>">
                <i class="fas fa-tasks w-6 text-center <?= $current_dir == 'aktivitas' ? '' : 'text-gray-400 group-hover:text-blue-600' ?>"></i>
                <span class="font-semibold">Aktivitas</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/laporan/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'laporan' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' ?>">
                <i class="fas fa-file-alt w-6 text-center <?= $current_dir == 'laporan' ? '' : 'text-gray-400 group-hover:text-blue-600' ?>"></i>
                <span class="font-semibold">Laporan</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/sertifikat/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'sertifikat' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' ?>">
                <i class="fas fa-certificate w-6 text-center <?= $current_dir == 'sertifikat' ? '' : 'text-gray-400 group-hover:text-blue-600' ?>"></i>
                <span class="font-semibold">Sertifikat</span>
            </a>

            <div class="my-4 border-t border-gray-100 mx-4"></div>

            <a href="<?= BASE_URL ?>/admin/setting/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group <?= $current_dir == 'setting' ? 'bg-gray-800 text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100' ?>">
                <i class="fas fa-cog w-6 text-center <?= $current_dir == 'setting' ? '' : 'text-gray-400 group-hover:text-gray-800' ?>"></i>
                <span class="font-semibold">Settings</span>
            </a>
            
            <a href="<?= BASE_URL ?>/login/logout.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 hover:text-red-700 transition-all mt-2 group">
                <i class="fas fa-sign-out-alt w-6 text-center group-hover:rotate-180 transition-transform duration-300"></i>
                <span class="font-semibold">Logout</span>
            </a>
        </nav>

        <div class="mt-8 p-4 bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl border border-blue-100">
            <p class="text-[10px] text-gray-500 font-bold mb-2 uppercase">Statistik</p>
            <?php
            $stmt = db()->query("SELECT COUNT(*) as total FROM users WHERE role='peserta'");
            $total_peserta = $stmt->fetch()['total'];
            ?>
            <div class="flex justify-between items-center text-xs">
                <span class="text-gray-600">Peserta:</span>
                <span class="font-bold text-blue-700 bg-blue-200 px-2 py-0.5 rounded-md"><?= $total_peserta ?></span>
            </div>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('main-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    // Toggle Sidebar
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    });

    // Close when clicking overlay
    overlay.addEventListener('click', function() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });
});
</script>