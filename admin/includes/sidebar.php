<?php
// =============================================
// ADMIN SIDEBAR - LEFT MENU
// C:\laragon\www\ptun-website\admin\includes\sidebar.php
// =============================================

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Determine base URL
$base_url = (isset($base_url)) ? $base_url : '../';
?>

<!-- SIDEBAR -->
<aside class="w-64 bg-white shadow-xl min-h-screen fixed left-0 top-16 bottom-0 overflow-y-auto">
    <div class="p-6">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Menu Admin</h3>
        
        <nav class="space-y-2">
            <!-- Dashboard -->
            <a href="<?= $base_url ?>index.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $current_dir == 'admin' && $current_page == 'index' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-blue-50' ?>">
                <i class="fas fa-home text-lg"></i>
                <span class="font-semibold">Dashboard</span>
            </a>

            <!-- Registrasi -->
            <a href="<?= $base_url ?>registrasi/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $current_dir == 'registrasi' ? 'bg-green-500 text-white' : 'text-gray-700 hover:bg-green-50' ?>">
                <i class="fas fa-user-plus text-lg"></i>
                <span class="font-semibold">Registrasi</span>
                <?php
                // Count pending registrations
                $stmt = db()->query("SELECT COUNT(*) as count FROM users WHERE role='peserta' AND status='pending'");
                $pending_count = $stmt->fetch()['count'];
                if($pending_count > 0):
                ?>
                <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">
                    <?= $pending_count ?>
                </span>
                <?php endif; ?>
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

            <!-- Settings -->
            <a href="<?= $base_url ?>setting/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $current_dir == 'setting' ? 'bg-gray-700 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-cog text-lg"></i>
                <span class="font-semibold">Settings</span>
            </a>

            <!-- Profile -->
            <a href="<?= $base_url ?>profile/" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $current_dir == 'profile' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-blue-50' ?>">
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

        <!-- Footer Info -->
        <div class="mt-8 p-4 bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl">
            <p class="text-xs text-gray-600 font-semibold mb-2">Statistik</p>
            <?php
            $stmt = db()->query("SELECT COUNT(*) as total FROM users WHERE role='peserta'");
            $total_peserta = $stmt->fetch()['total'];
            
            $stmt = db()->query("SELECT COUNT(*) as total FROM absensi WHERE approved=0");
            $pending_absensi = $stmt->fetch()['total'];
            ?>
            <div class="space-y-1 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Peserta:</span>
                    <span class="font-bold text-blue-600"><?= $total_peserta ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Pending:</span>
                    <span class="font-bold text-orange-600"><?= $pending_absensi ?></span>
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