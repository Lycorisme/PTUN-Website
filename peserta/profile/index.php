<?php
// =============================================
// PESERTA PROFILE - PTUN WEBSITE
// C:\laragon\www\ptun-website\peserta\profile\index.php
// =============================================

require_once '../../config/database.php';
$user = protect_page('peserta');

$success = '';
$error = '';

// HANDLE UPDATE PROFILE
if(isset($_POST['update_profile'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $instansi = $_POST['instansi'];
    $jurusan = $_POST['jurusan'];
    $bio = $_POST['bio'] ?? '';
    
    // Check if email already used
    $stmt = db()->prepare("SELECT id FROM users WHERE email=? AND id != ?");
    $stmt->execute([$email, $user['id']]);
    
    if($stmt->fetch()) {
        $error = "Email sudah digunakan!";
    } else {
        $stmt = db()->prepare("UPDATE users SET nama=?, email=?, instansi=?, jurusan=?, bio=? WHERE id=?");
        if($stmt->execute([$nama, $email, $instansi, $jurusan, $bio, $user['id']])) {
            $success = "Profile berhasil diupdate!";
            $_SESSION['user_data']['nama'] = $nama;
            $_SESSION['user_data']['email'] = $email;
            $_SESSION['user_data']['instansi'] = $instansi;
            $_SESSION['user_data']['jurusan'] = $jurusan;
            $_SESSION['user_data']['bio'] = $bio;
            $user = $_SESSION['user_data'];
        } else {
            $error = "Gagal update profile!";
        }
    }
}

// HANDLE CHANGE PASSWORD
if(isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $stmt = db()->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$user['id']]);
    $current_pass = $stmt->fetch()['password'];
    
    if($old_password !== $current_pass) {
        $error = "Password lama salah!";
    } elseif($new_password !== $confirm_password) {
        $error = "Password baru tidak cocok!";
    } elseif(strlen($new_password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $stmt = db()->prepare("UPDATE users SET password=? WHERE id=?");
        if($stmt->execute([$new_password, $user['id']])) {
            $success = "Password berhasil diubah!";
        } else {
            $error = "Gagal ubah password!";
        }
    }
}

// GET STATS
$stats = absensi_stats($user['id']);
$percentage = absensi_percentage($user['id']);
$max_hari = absensi_max_hari();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Peserta - <?= get_site_name() ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

<!-- NAVBAR -->
<nav class="bg-gradient-to-r from-green-600 to-emerald-700 text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="../" class="text-white hover:text-green-100">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-2xl font-bold">Profile Saya</h1>
        </div>
        <a href="../" class="bg-white/20 hover:bg-white/30 px-6 py-2 rounded-xl font-semibold transition-all">
            <i class="fas fa-home mr-2"></i>Dashboard
        </a>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-6 py-8">

    <!-- MESSAGES -->
    <?php if($success): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
        <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8">
        <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- PROFILE CARD -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl shadow-xl p-8 text-center">
                <div class="w-32 h-32 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-user-graduate text-5xl text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($user['nama']) ?></h2>
                <p class="text-green-600 font-semibold mb-1"><?= htmlspecialchars($user['instansi']) ?></p>
                <p class="text-gray-600 mb-4"><?= htmlspecialchars($user['jurusan']) ?></p>
                <p class="text-sm text-gray-500 mb-6"><?= htmlspecialchars($user['email']) ?></p>
                
                <div class="p-4 bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl">
                    <p class="text-sm text-gray-600 mb-1">Status Magang</p>
                    <p class="font-bold text-lg <?= 
                        $user['status']=='aktif' ? 'text-green-600' : 
                        ($user['status']=='pending' ? 'text-orange-600' : 'text-blue-600') 
                    ?>">
                        <?= strtoupper($user['status']) ?>
                    </p>
                </div>
            </div>

            <!-- STATS CARD -->
            <div class="bg-white rounded-3xl shadow-xl p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Statistik Absensi</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Kehadiran</span>
                        <span class="font-bold text-green-600"><?= $stats['hadir'] ?>/<?= $max_hari ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-green-600 rounded-full h-3" style="width: <?= $percentage ?>%"></div>
                    </div>
                    <div class="text-center">
                        <span class="text-3xl font-bold text-green-600"><?= $percentage ?>%</span>
                        <p class="text-sm text-gray-600">Persentase Hadir</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- EDIT PROFILE & CHANGE PASSWORD -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- EDIT PROFILE FORM -->
            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-user-edit text-green-600 mr-3"></i>
                    Edit Profile
                </h3>
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap</label>
                            <input type="text" name="nama" required value="<?= htmlspecialchars($user['nama']) ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-100">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-100">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Instansi / Sekolah</label>
                            <input type="text" name="instansi" required value="<?= htmlspecialchars($user['instansi']) ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-100">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Jurusan</label>
                            <input type="text" name="jurusan" required value="<?= htmlspecialchars($user['jurusan']) ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-100">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Bio / Tentang Saya (Opsional)</label>
                        <textarea name="bio" rows="4" 
                                  class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-100"
                                  placeholder="Ceritakan sedikit tentang diri Anda..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="update_profile"
                            class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- CHANGE PASSWORD FORM -->
            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-key text-orange-600 mr-3"></i>
                    Ubah Password
                </h3>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Password Lama</label>
                        <input type="password" name="old_password" required
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                               placeholder="••••••••">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Password Baru</label>
                        <input type="password" name="new_password" required
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                               placeholder="••••••••">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" required
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                               placeholder="••••••••">
                    </div>
                    <button type="submit" name="change_password"
                            class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all">
                        <i class="fas fa-lock mr-2"></i>Ubah Password
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
<?php if($success): ?>
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: '<?= $success ?>',
    confirmButtonColor: '#10b981'
});
<?php endif; ?>
</script>

</body>
</html>