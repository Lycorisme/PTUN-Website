<?php
require_once '../../config/database.php';
// session_start(); // DIHAPUS

// Check User Login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$user = $_SESSION['user_data'];
$success = '';
$error = '';

// HANDLE UPDATE PROFILE
if(isset($_POST['update_profile'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $instansi = $_POST['instansi'];
    $jurusan = $_POST['jurusan'];
    $bio = $_POST['bio'] ?? '';
    
    // Cek email ganda
    $stmt = db()->prepare("SELECT id FROM users WHERE email=? AND id != ?");
    $stmt->execute([$email, $user['id']]);
    
    if($stmt->fetch()) {
        $error = "Email sudah digunakan oleh user lain!";
    } else {
        $stmt = db()->prepare("UPDATE users SET nama=?, email=?, instansi=?, jurusan=?, bio=? WHERE id=?");
        if($stmt->execute([$nama, $email, $instansi, $jurusan, $bio, $user['id']])) {
            $success = "Profile berhasil diperbarui!";
            // Update session data
            $_SESSION['user_data']['nama'] = $nama;
            $_SESSION['user_data']['email'] = $email;
            $_SESSION['user_data']['instansi'] = $instansi;
            $_SESSION['user_data']['jurusan'] = $jurusan;
            $_SESSION['user_data']['bio'] = $bio;
            $user = $_SESSION['user_data'];
        } else {
            $error = "Gagal mengupdate profile!";
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
        $error = "Konfirmasi password baru tidak cocok!";
    } elseif(strlen($new_password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $stmt = db()->prepare("UPDATE users SET password=? WHERE id=?");
        if($stmt->execute([$new_password, $user['id']])) {
            $success = "Password berhasil diubah!";
        } else {
            $error = "Gagal mengubah password!";
        }
    }
}

// DATA STATISTIK
$stats = get_absensi_stats($user['id']);
$percentage = absensi_percentage($user['id']);
$max_hari = absensi_max_hari();

$page_title = 'Profile Saya';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">

    <?php if($success): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl shadow-xl p-8 text-center sticky top-24">
                <div class="w-32 h-32 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg border-4 border-white">
                    <i class="fas fa-user-graduate text-5xl text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($user['nama']) ?></h2>
                <p class="text-green-600 font-semibold mb-2"><?= htmlspecialchars($user['instansi']) ?></p>
                <p class="text-sm text-gray-500 mb-6"><?= htmlspecialchars($user['email']) ?></p>
                
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 mb-6">
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Status Magang</p>
                    <span class="inline-block px-4 py-1 rounded-full text-sm font-bold 
                        <?= $user['status']=='aktif' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' ?>">
                        <?= strtoupper($user['status']) ?>
                    </span>
                </div>

                <div class="border-t pt-6">
                    <p class="text-gray-600 font-bold mb-2">Kehadiran</p>
                    <div class="flex justify-between text-sm mb-1 text-gray-500">
                        <span>Progress</span>
                        <span><?= $percentage ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 rounded-full h-2 transition-all duration-1000" style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-8">
            
            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center border-b pb-4">
                    <i class="fas fa-user-edit text-green-600 mr-3"></i>
                    Edit Biodata
                </h3>
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap</label>
                            <input type="text" name="nama" required value="<?= htmlspecialchars($user['nama']) ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-green-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-green-500 transition-all">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Instansi / Kampus</label>
                            <input type="text" name="instansi" required value="<?= htmlspecialchars($user['instansi']) ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-green-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Jurusan</label>
                            <input type="text" name="jurusan" required value="<?= htmlspecialchars($user['jurusan']) ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-green-500 transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Bio / Tentang Saya</label>
                        <textarea name="bio" rows="3" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-green-500 transition-all"
                                  placeholder="Deskripsi singkat..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="update_profile"
                            class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 rounded-xl font-bold hover:shadow-lg transition-all">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center border-b pb-4">
                    <i class="fas fa-shield-alt text-orange-500 mr-3"></i>
                    Keamanan Akun
                </h3>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Password Lama</label>
                        <input type="password" name="old_password" required placeholder="••••••••"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 transition-all">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Password Baru</label>
                            <input type="password" name="new_password" required placeholder="Minimal 6 karakter"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Ulangi Password Baru</label>
                            <input type="password" name="confirm_password" required placeholder="••••••••"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 transition-all">
                        </div>
                    </div>
                    <button type="submit" name="change_password"
                            class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-3 rounded-xl font-bold hover:shadow-lg transition-all">
                        <i class="fas fa-key mr-2"></i>Update Password
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
    confirmButtonColor: '#10b981',
    timer: 2000
});
<?php endif; ?>
</script>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>