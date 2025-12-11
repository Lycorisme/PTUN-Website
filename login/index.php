<?php
// =============================================
// LOGIN & REGISTER - DYNAMIC & MODERN UI
// =============================================

require_once '../config/database.php';

// Helper: Ambil setting database dengan aman
if (!function_exists('get_setting_safe')) {
    function get_setting_safe($key, $default = '') {
        try {
            $stmt = db()->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
            $stmt->execute([$key]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res['value'] ?? $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

// Ambil Data Dinamis dari Database
$site_name = get_setting_safe('nama_website', 'PTUN Website');
$site_long = get_setting_safe('nama_panjang', 'Pengadilan Tata Usaha Negara');
$tagline   = get_setting_safe('tagline', 'Sistem Informasi Manajemen Magang');
$logo_url  = get_setting_safe('logo_url', '');

// Logic Login & Register
$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login';

// 1. HANDLE LOGIN
if(isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    
    try {
        $stmt = db()->prepare("SELECT * FROM users WHERE email=? AND password=? AND status='aktif'");
        $stmt->execute([$email, $pass]);
        $user = $stmt->fetch();
        
        if($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_data'] = $user;
            
            // Redirect sesuai role
            header('Location: ' . ($user['role'] == 'admin' ? '../admin/' : '../peserta/'));
            exit;
        } else {
            // Cek apakah status pending
            $stmt = db()->prepare("SELECT id FROM users WHERE email=? AND status='pending'");
            $stmt->execute([$email]);
            if($stmt->fetch()) {
                $error = "Akun Anda masih dalam antrean persetujuan admin.";
            } else {
                $error = "Email atau password tidak valid.";
            }
        }
    } catch(PDOException $e) {
        $error = "Terjadi kesalahan sistem.";
    }
}

// 2. HANDLE REGISTER
if(isset($_POST['register'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $instansi = trim($_POST['instansi']);
    $jurusan = trim($_POST['jurusan']);
    
    if($password !== $confirm) {
        $error = "Konfirmasi password tidak sesuai.";
    } else {
        try {
            // Cek Email
            $stmt = db()->prepare("SELECT id FROM users WHERE email=?");
            $stmt->execute([$email]);
            
            if($stmt->rowCount() > 0) {
                $error = "Email tersebut sudah terdaftar.";
            } else {
                // Auto active untuk demo/testing (bisa diubah ke 'pending' untuk production)
                $stmt = db()->prepare("INSERT INTO users (nama, email, password, role, instansi, jurusan, status, created_at) VALUES (?, ?, ?, 'peserta', ?, ?, 'pending', NOW())");
                
                if($stmt->execute([$nama, $email, $password, $instansi, $jurusan])) {
                    $success = "Pendaftaran berhasil! Silakan tunggu persetujuan admin.";
                    $mode = 'login';
                } else {
                    $error = "Gagal mendaftar, silakan coba lagi.";
                }
            }
        } catch(PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($mode == 'login' ? 'Masuk' : 'Daftar') . ' - ' . htmlspecialchars($site_name) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: '#1e40af', // Blue 800
                        secondary: '#1e3a8a', // Blue 900
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-5xl bg-white/10 backdrop-blur-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-white/20">
        
        <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-blue-600/90 to-blue-800/90 p-12 flex-col justify-between text-white relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                <i class="fas fa-balance-scale absolute -top-20 -left-20 text-[20rem]"></i>
            </div>

            <div class="relative z-10">
                <?php if(!empty($logo_url)): ?>
                    <img src="<?= BASE_URL . $logo_url ?>" alt="Logo" class="h-20 mb-6 object-contain bg-white/20 p-2 rounded-xl backdrop-blur-sm">
                <?php else: ?>
                    <div class="h-20 w-20 bg-white/20 rounded-2xl flex items-center justify-center mb-6 backdrop-blur-sm">
                        <i class="fas fa-university text-4xl"></i>
                    </div>
                <?php endif; ?>

                <h2 class="text-4xl font-bold mb-2"><?= htmlspecialchars($site_name) ?></h2>
                <p class="text-blue-100 text-lg font-light"><?= htmlspecialchars($site_long) ?></p>
            </div>

            <div class="relative z-10 space-y-6">
                <div class="space-y-4">
                    <div class="flex items-center gap-4 bg-white/10 p-4 rounded-xl border border-white/10">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <h4 class="font-bold">Manajemen Kegiatan</h4>
                            <p class="text-sm text-blue-100">Catat aktivitas harian dengan mudah.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 p-4 rounded-xl border border-white/10">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div>
                            <h4 class="font-bold">Sertifikat Digital</h4>
                            <p class="text-sm text-blue-100">Dapatkan sertifikat resmi setelah lulus.</p>
                        </div>
                    </div>
                </div>
                
                <p class="text-xs text-blue-200 mt-8">&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. All rights reserved.</p>
            </div>
        </div>

        <div class="w-full md:w-1/2 bg-white p-8 md:p-12 relative">
            
            <div class="md:hidden text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($site_name) ?></h2>
                <p class="text-gray-500 text-sm"><?= htmlspecialchars($tagline) ?></p>
            </div>

            <div class="flex p-1 bg-gray-100 rounded-xl mb-8">
                <a href="?mode=login" class="flex-1 text-center py-2.5 text-sm font-bold rounded-lg transition-all <?= $mode=='login' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                    Login
                </a>
                <a href="?mode=register" class="flex-1 text-center py-2.5 text-sm font-bold rounded-lg transition-all <?= $mode=='register' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                    Daftar Baru
                </a>
            </div>

            <?php if($error): ?>
                <div class="mb-6 p-4 rounded-xl bg-red-50 border-l-4 border-red-500 text-red-700 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle mt-0.5"></i>
                    <span class="text-sm font-medium"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="mb-6 p-4 rounded-xl bg-green-50 border-l-4 border-green-500 text-green-700 flex items-start gap-3">
                    <i class="fas fa-check-circle mt-0.5"></i>
                    <span class="text-sm font-medium"><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if($mode == 'login'): ?>
            <div class="space-y-6 animate-fade-in">
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">Selamat Datang Kembali!</h3>
                    <p class="text-gray-500 text-sm">Silakan masuk ke akun Anda</p>
                </div>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5 ml-1">Alamat Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" name="email" required placeholder="nama@email.com"
                                class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all font-medium text-gray-700 bg-gray-50 focus:bg-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5 ml-1">Kata Sandi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" required placeholder="••••••••"
                                class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all font-medium text-gray-700 bg-gray-50 focus:bg-white">
                        </div>
                    </div>

                    <button type="submit" name="login" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-blue-500/30 hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2">
                        <span>Masuk Sekarang</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="text-center mt-8">
                    <a href="../index.php" class="inline-flex items-center text-sm font-semibold text-gray-400 hover:text-blue-600 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if($mode == 'register'): ?>
            <div class="animate-fade-in">
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">Buat Akun Baru</h3>
                    <p class="text-gray-500 text-sm">Bergabunglah sebagai peserta magang</p>
                </div>

                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1 uppercase">Nama Lengkap</label>
                            <input type="text" name="nama" required class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1 uppercase">Email</label>
                            <input type="email" name="email" required class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none bg-gray-50 focus:bg-white transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1 uppercase">Asal Instansi</label>
                            <input type="text" name="instansi" placeholder="Kampus/Sekolah" required class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1 uppercase">Jurusan</label>
                            <input type="text" name="jurusan" required class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none bg-gray-50 focus:bg-white transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1 uppercase">Password</label>
                            <input type="password" name="password" required class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1 uppercase">Konfirmasi</label>
                            <input type="password" name="confirm_password" required class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none bg-gray-50 focus:bg-white transition-all">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" name="register" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-green-500/30 hover:-translate-y-0.5 transition-all duration-200">
                            Daftar Sekarang
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-6">
                    <a href="../index.php" class="inline-flex items-center text-sm font-semibold text-gray-400 hover:text-blue-600 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Batal
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php if($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1'): ?>
    <div class="fixed bottom-4 right-4 bg-white/90 backdrop-blur p-4 rounded-xl shadow-2xl border border-gray-200 text-xs hidden md:block opacity-50 hover:opacity-100 transition-opacity">
        <h4 class="font-bold text-gray-800 mb-2">🧑‍💻 Dev Login</h4>
        <div class="grid gap-2">
            <div class="flex justify-between gap-4 cursor-pointer hover:bg-blue-50 p-1 rounded" onclick="document.querySelector('[name=email]').value='admin@ptun-bjm.go.id';document.querySelector('[name=password]').value='admin123';">
                <span class="font-bold text-blue-600">Admin</span>
                <span class="text-gray-500">admin123</span>
            </div>
            <div class="flex justify-between gap-4 cursor-pointer hover:bg-green-50 p-1 rounded" onclick="document.querySelector('[name=email]').value='dian@smk1.sch.id';document.querySelector('[name=password]').value='dian123';">
                <span class="font-bold text-green-600">Peserta</span>
                <span class="text-gray-500">dian123</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>