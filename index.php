<?php
// NO AUTH - DIRECT DB LOGIN
require_once 'config/database.php';

if(isset($_POST['email']) && isset($_POST['pass'])) {
    $email = $_POST['email'];
    $pass = $_POST['pass'];
    
    $stmt = db()->prepare("SELECT * FROM users WHERE email=? AND password=? AND status='aktif'");
    $stmt->execute([$email, $pass]);
    $user = $stmt->fetch();
    
    if($user) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_data'] = $user;
        
        if($user['role']=='admin') {
            header('Location: admin/');
        } else {
            header('Location: peserta/');
        }
        exit;
    } else {
        $error = "Email/password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTUN Banjarmasin - SIM Magang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-white rounded-3xl shadow-2xl p-12 text-center">
        
        <!-- LOGO -->
        <div class="w-24 h-24 bg-gradient-to-r from-blue-600 to-blue-700 rounded-3xl flex items-center justify-center mx-auto mb-8 text-white text-3xl">
            <i class="fas fa-balance-scale"></i>
        </div>
        
        <h1 class="text-4xl font-bold text-gray-900 mb-4"><?= get_setting('nama_website', 'PTUN Banjarmasin') ?></h1>
        <p class="text-xl text-gray-600 mb-12">Sistem Informasi Magang PTUN</p>
        
        <?php if(isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl mb-8">
            <i class="fas fa-exclamation-triangle mr-2"></i><?= $error ?>
        </div>
        <?php endif; ?>
        
        <!-- LOGIN FORM -->
        <form method="POST" class="space-y-6">
            <div>
                <input type="email" name="email" required 
                       class="w-full px-6 py-5 border-2 border-gray-200 rounded-2xl text-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all" 
                       placeholder="admin@ptun-bjm.go.id">
            </div>
            <div>
                <input type="password" name="pass" required 
                       class="w-full px-6 py-5 border-2 border-gray-200 rounded-2xl text-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all" 
                       placeholder="admin123">
            </div>
            <button type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-6 px-8 rounded-2xl font-bold text-xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                <i class="fas fa-sign-in-alt mr-3"></i>MASUK DASHBOARD
            </button>
        </form>
        
        <!-- AKUN TESTING -->
        <div class="mt-12 p-8 bg-gradient-to-r from-gray-50 to-gray-100 rounded-3xl border-2 border-dashed border-gray-300">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">🔥 Akun Testing</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-center">
                <div class="p-6 bg-gradient-to-r from-blue-500/10 to-blue-600/10 rounded-2xl border-2 border-blue-200">
                    <div class="text-sm text-gray-600 mb-2">Admin</div>
                    <div class="font-mono text-lg mb-1">admin@ptun-bjm.go.id</div>
                    <div class="font-bold text-xl text-blue-800">admin123</div>
                    <div class="text-xs text-blue-600 mt-2">→ Admin Dashboard</div>
                </div>
                <div class="p-6 bg-gradient-to-r from-emerald-500/10 to-emerald-600/10 rounded-2xl border-2 border-emerald-200">
                    <div class="text-sm text-gray-600 mb-2">Peserta (Dian)</div>
                    <div class="font-mono text-lg mb-1">dian@smk1.sch.id</div>
                    <div class="font-bold text-xl text-emerald-800">dian123</div>
                    <div class="text-xs text-emerald-600 mt-2">→ Peserta Dashboard</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
