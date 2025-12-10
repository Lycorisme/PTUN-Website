<?php
// =============================================
// LOGIN/INDEX.PHP - PTUN WEBSITE
// Login & Registration in One Page
// =============================================

require_once '../config/database.php';

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // 'login' or 'register'

// HANDLE LOGIN
if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    
    $stmt = db()->prepare("SELECT * FROM users WHERE email=? AND password=? AND status='aktif'");
    $stmt->execute([$email, $pass]);
    $user = $stmt->fetch();
    
    if($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_data'] = $user;
        
        if($user['role'] == 'admin') {
            header('Location: ../admin/');
        } else {
            header('Location: ../peserta/');
        }
        exit;
    } else {
        // Check if pending
        $stmt = db()->prepare("SELECT * FROM users WHERE email=? AND status='pending'");
        $stmt->execute([$email]);
        if($stmt->fetch()) {
            $error = "Akun Anda sedang menunggu approval admin!";
        } else {
            $error = "Email atau password salah!";
        }
    }
}

// HANDLE REGISTRATION
if(isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $instansi = $_POST['instansi'];
    $jurusan = $_POST['jurusan'];
    
    // Validasi
    if($password !== $confirm_password) {
        $error = "Password tidak cocok!";
    } else {
        // Check email sudah ada
        $stmt = db()->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        
        if($stmt->fetch()) {
            $error = "Email sudah terdaftar!";
        } else {
            // Insert new user dengan status pending
            $stmt = db()->prepare("INSERT INTO users (nama, email, password, role, instansi, jurusan, status, created_at) VALUES (?, ?, ?, 'peserta', ?, ?, 'pending', NOW())");
            
            if($stmt->execute([$nama, $email, $password, $instansi, $jurusan])) {
                $success = "Registrasi berhasil! Menunggu approval admin.";
                $mode = 'login';
            } else {
                $error = "Gagal registrasi, coba lagi!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $mode == 'login' ? 'Login' : 'Registrasi' ?> - <?= get_site_name() ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl p-8">
        
        <!-- LOGO -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-r from-blue-600 to-blue-700 rounded-3xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-balance-scale text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900"><?= get_site_name() ?></h1>
            <p class="text-gray-600 mt-2">Sistem Informasi Magang</p>
        </div>

        <!-- TABS -->
        <div class="flex mb-8 bg-gray-100 rounded-2xl p-2">
            <a href="?mode=login" class="flex-1 text-center py-3 rounded-xl font-bold transition-all <?= $mode=='login' ? 'bg-white text-blue-600 shadow-md' : 'text-gray-600' ?>">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </a>
            <a href="?mode=register" class="flex-1 text-center py-3 rounded-xl font-bold transition-all <?= $mode=='register' ? 'bg-white text-blue-600 shadow-md' : 'text-gray-600' ?>">
                <i class="fas fa-user-plus mr-2"></i>Registrasi
            </a>
        </div>

        <!-- MESSAGES -->
        <?php if($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
            <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <?php if($mode == 'login'): ?>
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                <input type="email" name="email" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all" 
                       placeholder="email@example.com">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all" 
                       placeholder="••••••••">
            </div>
            <button type="submit" name="login" 
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-4 px-8 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                <i class="fas fa-sign-in-alt mr-2"></i>MASUK
            </button>
        </form>

        <!-- AKUN TESTING -->
        <div class="mt-8 p-6 bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl border-2 border-dashed border-gray-300">
            <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">🔥 Akun Testing</h3>
            <div class="space-y-3 text-sm">
                <div class="p-3 bg-blue-50 rounded-xl border border-blue-200">
                    <div class="font-semibold text-blue-900">Admin</div>
                    <div class="font-mono text-xs text-gray-600">admin@ptun-bjm.go.id</div>
                    <div class="font-bold text-blue-600">admin123</div>
                </div>
                <div class="p-3 bg-green-50 rounded-xl border border-green-200">
                    <div class="font-semibold text-green-900">Peserta (Dian)</div>
                    <div class="font-mono text-xs text-gray-600">dian@smk1.sch.id</div>
                    <div class="font-bold text-green-600">dian123</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- REGISTRATION FORM -->
        <?php if($mode == 'register'): ?>
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap</label>
                <input type="text" name="nama" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all" 
                       placeholder="John Doe">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                <input type="email" name="email" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all" 
                       placeholder="email@example.com">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Instansi / Sekolah</label>
                <input type="text" name="instansi" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all" 
                       placeholder="SMK Negara 1 Banjarmasin">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Jurusan</label>
                <input type="text" name="jurusan" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all" 
                       placeholder="Rekayasa Perangkat Lunak">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all" 
                       placeholder="••••••••">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Konfirmasi Password</label>
                <input type="password" name="confirm_password" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all" 
                       placeholder="••••••••">
            </div>
            <button type="submit" name="register" 
                    class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-4 px-8 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                <i class="fas fa-user-plus mr-2"></i>DAFTAR SEKARANG
            </button>
        </form>
        <?php endif; ?>

        <!-- BACK TO HOME -->
        <div class="mt-8 text-center">
            <a href="../index.html" class="text-blue-600 hover:text-blue-700 font-semibold">
                <i class="fas fa-home mr-2"></i>Kembali ke Beranda
            </a>
        </div>
    </div>

</body>
</html>