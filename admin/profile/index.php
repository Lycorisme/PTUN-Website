<?php
// =============================================
// ADMIN PROFILE - PTUN WEBSITE
// C:\laragon\www\ptun-website\admin\profile\index.php
// =============================================

require_once '../../config/database.php';
// session_start(); // Session sudah distart otomatis

$user = protect_page('admin');

$success = '';
$error = '';

// HANDLE UPDATE PROFILE
if(isset($_POST['update_profile'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $bio = $_POST['bio'] ?? '';
    
    // Check if email already used by other user
    $stmt = db()->prepare("SELECT id FROM users WHERE email=? AND id != ?");
    $stmt->execute([$email, $user['id']]);
    
    if($stmt->fetch()) {
        $error = "Email sudah digunakan oleh user lain!";
    } else {
        $stmt = db()->prepare("UPDATE users SET nama=?, email=?, bio=? WHERE id=?");
        if($stmt->execute([$nama, $email, $bio, $user['id']])) {
            $success = "Profile berhasil diupdate!";
            $_SESSION['user_data']['nama'] = $nama;
            $_SESSION['user_data']['email'] = $email;
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
    
    // Verify old password
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

// Set Page Title & Header
$page_title = 'Profile Admin';
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
        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-3xl shadow-xl p-8 text-center sticky top-24">
                <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <i class="fas fa-user-shield text-5xl text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($user['nama']) ?></h2>
                <p class="text-blue-600 font-semibold mb-4">Administrator</p>
                <p class="text-gray-600 mb-6"><?= htmlspecialchars($user['email']) ?></p>
                <div class="p-4 bg-blue-50 rounded-2xl">
                    <p class="text-sm text-gray-600 mb-1">Member Since</p>
                    <p class="font-bold text-gray-900"><?= format_tanggal_id($user['created_at']) ?></p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-8">
            
            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-user-edit text-blue-600 mr-3"></i>
                    Edit Profile
                </h3>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="nama" required value="<?= htmlspecialchars($user['nama']) ?>"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Bio / Keterangan (Opsional)</label>
                        <textarea name="bio" rows="4" 
                                  class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all"
                                  placeholder="Ceritakan sedikit tentang Anda..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="update_profile"
                            class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all transform hover:-translate-y-1">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-key text-orange-600 mr-3"></i>
                    Ubah Password
                </h3>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Password Lama</label>
                        <input type="password" name="old_password" required
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-100 transition-all"
                               placeholder="••••••••">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Password Baru</label>
                        <input type="password" name="new_password" required
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-100 transition-all"
                               placeholder="••••••••">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" required
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-100 transition-all"
                               placeholder="••••••••">
                    </div>
                    <button type="submit" name="change_password"
                            class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all transform hover:-translate-y-1">
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
    confirmButtonColor: '#3b82f6'
});
<?php endif; ?>
</script>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>