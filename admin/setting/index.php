<?php
// =============================================
// ADMIN SETTINGS - PTUN WEBSITE
// C:\laragon\www\ptun-website\admin\setting\index.php
// =============================================

require_once '../../config/database.php';
session_start();

// Protect admin page
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../index.php');
    exit;
}

// HANDLE UPDATE SETTINGS
if(isset($_POST['update_settings'])) {
    $group = $_POST['group'];
    
    // Handle logo upload
    if(isset($_FILES['logo_url']) && $_FILES['logo_url']['error'] == 0) {
        $target_dir = "../../uploads/logos/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        $file_ext = pathinfo($_FILES['logo_url']['name'], PATHINFO_EXTENSION);
        $new_filename = 'logo_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if(move_uploaded_file($_FILES['logo_url']['tmp_name'], $target_file)) {
            $stmt = db()->prepare("UPDATE settings SET value=? WHERE `key`='logo_url'");
            $stmt->execute(['/uploads/logos/' . $new_filename]);
        }
    }
    
    // Update other settings
    foreach($_POST as $key => $value) {
        if($key != 'update_settings' && $key != 'group') {
            $stmt = db()->prepare("UPDATE settings SET value=? WHERE `key`=?");
            $stmt->execute([$value, $key]);
        }
    }
    
    header('Location: index.php?msg=updated&tab=' . $group);
    exit;
}

// GET ALL SETTINGS GROUPED
$stmt = db()->query("SELECT * FROM settings ORDER BY `group`, `order`");
$all_settings = $stmt->fetchAll(PDO::FETCH_GROUP);

$active_tab = $_GET['tab'] ?? 'institusi';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?= get_site_name() ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

<!-- NAVBAR -->
<nav class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="../" class="text-white hover:text-blue-100">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-2xl font-bold">Settings Website</h1>
        </div>
        <a href="../" class="bg-white/20 hover:bg-white/30 px-6 py-2 rounded-xl font-semibold transition-all">
            <i class="fas fa-home mr-2"></i>Dashboard
        </a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="max-w-7xl mx-auto px-6 py-8">

    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
        <i class="fas fa-check-circle text-xl mr-2"></i>
        <span class="font-semibold">Settings berhasil diupdate!</span>
    </div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <button onclick="showTab('institusi')" id="tab-institusi" 
                    class="tab-btn flex-1 px-6 py-4 font-bold text-gray-700 hover:bg-gray-50 transition-all <?= $active_tab=='institusi' ? 'border-b-4 border-blue-600 text-blue-600' : '' ?>">
                <i class="fas fa-building mr-2"></i>Institusi
            </button>
            <button onclick="showTab('footer')" id="tab-footer" 
                    class="tab-btn flex-1 px-6 py-4 font-bold text-gray-700 hover:bg-gray-50 transition-all <?= $active_tab=='footer' ? 'border-b-4 border-blue-600 text-blue-600' : '' ?>">
                <i class="fas fa-link mr-2"></i>Footer
            </button>
            <button onclick="showTab('menu')" id="tab-menu" 
                    class="tab-btn flex-1 px-6 py-4 font-bold text-gray-700 hover:bg-gray-50 transition-all <?= $active_tab=='menu' ? 'border-b-4 border-blue-600 text-blue-600' : '' ?>">
                <i class="fas fa-bars mr-2"></i>Menu
            </button>
            <button onclick="showTab('sistem')" id="tab-sistem" 
                    class="tab-btn flex-1 px-6 py-4 font-bold text-gray-700 hover:bg-gray-50 transition-all <?= $active_tab=='sistem' ? 'border-b-4 border-blue-600 text-blue-600' : '' ?>">
                <i class="fas fa-cog mr-2"></i>Sistem
            </button>
        </div>

        <!-- TAB CONTENT: INSTITUSI -->
        <div id="content-institusi" class="tab-content p-8 <?= $active_tab!='institusi' ? 'hidden' : '' ?>">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Informasi Institusi</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="group" value="institusi">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nama Website</label>
                        <input type="text" name="nama_website" value="<?= get_setting('nama_website') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap Institusi</label>
                        <input type="text" name="nama_panjang" value="<?= get_setting('nama_panjang') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tagline</label>
                        <input type="text" name="tagline" value="<?= get_setting('tagline') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Logo</label>
                        <input type="file" name="logo_url" accept="image/*" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                        <p class="text-sm text-gray-500 mt-2">Logo saat ini: <?= get_setting('logo_url') ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" rows="3" 
                                  class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500"><?= get_setting('alamat_lengkap') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">No. Telepon</label>
                        <input type="text" name="no_telepon" value="<?= get_setting('no_telepon') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Email Kontak</label>
                        <input type="email" name="email_kontak" value="<?= get_setting('email_kontak') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                </div>
                <button type="submit" name="update_settings" 
                        class="mt-8 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-8 py-4 rounded-xl font-bold hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- TAB CONTENT: FOOTER -->
        <div id="content-footer" class="tab-content p-8 <?= $active_tab!='footer' ? 'hidden' : '' ?>">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Footer & Social Media</h2>
            <form method="POST">
                <input type="hidden" name="group" value="footer">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Copyright Text</label>
                        <input type="text" name="copyright_text" value="<?= get_setting('copyright_text') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Facebook URL</label>
                        <input type="url" name="social_facebook" value="<?= get_setting('social_facebook') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Instagram URL</label>
                        <input type="url" name="social_instagram" value="<?= get_setting('social_instagram') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                </div>
                <button type="submit" name="update_settings" 
                        class="mt-8 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-8 py-4 rounded-xl font-bold hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- TAB CONTENT: MENU -->
        <div id="content-menu" class="tab-content p-8 <?= $active_tab!='menu' ? 'hidden' : '' ?>">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Menu Navigasi</h2>
            <p class="text-gray-600 mb-6">Format: <code class="bg-gray-100 px-2 py-1 rounded">Judul|/url</code></p>
            <form method="POST">
                <input type="hidden" name="group" value="menu">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Menu Beranda</label>
                        <input type="text" name="menu_beranda" value="<?= get_setting('menu_beranda') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Menu Tentang</label>
                        <input type="text" name="menu_tentang" value="<?= get_setting('menu_tentang') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Menu Layanan</label>
                        <input type="text" name="menu_layanan" value="<?= get_setting('menu_layanan') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                </div>
                <button type="submit" name="update_settings" 
                        class="mt-8 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-8 py-4 rounded-xl font-bold hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- TAB CONTENT: SISTEM -->
        <div id="content-sistem" class="tab-content p-8 <?= $active_tab!='sistem' ? 'hidden' : '' ?>">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Pengaturan Sistem</h2>
            <form method="POST">
                <input type="hidden" name="group" value="sistem">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Maintenance Mode</label>
                        <select name="maintenance_mode" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                            <option value="0" <?= get_setting('maintenance_mode')=='0' ? 'selected' : '' ?>>Non-Aktif</option>
                            <option value="1" <?= get_setting('maintenance_mode')=='1' ? 'selected' : '' ?>>Aktif</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal Mulai Absensi</label>
                        <input type="date" name="absensi_start_date" value="<?= get_setting('absensi_start_date') ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    </div>
                </div>
                <button type="submit" name="update_settings" 
                        class="mt-8 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-8 py-4 rounded-xl font-bold hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                </button>
            </form>
        </div>

    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('border-b-4', 'border-blue-600', 'text-blue-600');
    });
    
    // Show selected tab
    document.getElementById('content-' + tabName).classList.remove('hidden');
    document.getElementById('tab-' + tabName).classList.add('border-b-4', 'border-blue-600', 'text-blue-600');
}

// Auto show success message
<?php if(isset($_GET['msg'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: 'Settings telah diupdate',
    timer: 2000,
    showConfirmButton: false
});
<?php endif; ?>
</script>

</body>
</html>