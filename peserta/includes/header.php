<?php
// =============================================
// PESERTA HEADER - DYNAMIC FROM DATABASE
// C:\laragon\www\ptun-website\peserta\includes\header.php
// =============================================

$site_name = get_site_name();
$site_tagline = get_site_tagline();
$logo_url = get_logo_url();
$user_name = $_SESSION['user_data']['nama'] ?? 'Peserta';
$user_instansi = $_SESSION['user_data']['instansi'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?><?= htmlspecialchars($site_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    </style>
</head>
<body class="bg-gray-50">

<!-- NAVBAR -->
<nav class="gradient-bg text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Left Side -->
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-user-graduate text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold"><?= htmlspecialchars($site_name) ?></h1>
                    <p class="text-xs text-green-100"><?= htmlspecialchars($user_instansi) ?></p>
                </div>
            </div>

            <!-- Right Side -->
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <button class="relative p-2 hover:bg-white/10 rounded-lg transition-all">
                    <i class="fas fa-bell text-xl"></i>
                    <?php 
                    $notif_count = get_notification_count($_SESSION['user_id']);
                    if($notif_count > 0): 
                    ?>
                    <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                        <?= $notif_count ?>
                    </span>
                    <?php endif; ?>
                </button>

                <!-- Profile Dropdown -->
                <div class="relative group">
                    <button class="flex items-center space-x-3 p-2 hover:bg-white/10 rounded-lg transition-all">
                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="font-semibold"><?= htmlspecialchars($user_name) ?></span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="hidden group-hover:block absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-2">
                        <a href="<?= isset($base_url) ? $base_url : '../' ?>profile/" 
                           class="block px-4 py-2 text-gray-800 hover:bg-green-50 transition-all">
                            <i class="fas fa-user-circle mr-2 text-green-600"></i>Profile
                        </a>
                        <hr class="my-2">
                        <a href="<?= isset($base_url) ? $base_url : '../../' ?>login/logout.php" 
                           class="block px-4 py-2 text-red-600 hover:bg-red-50 transition-all">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>