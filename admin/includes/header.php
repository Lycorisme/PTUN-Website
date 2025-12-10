<?php
// =============================================
// ADMIN HEADER - FULL DYNAMIC
// =============================================

// Pastikan fungsi get_setting tersedia
$site_name = get_site_name();
$user_name = $_SESSION['user_data']['nama'] ?? 'Admin';

// 1. LOGIKA NOTIFIKASI (MONITORING PESAN KELUAR)
// Menampilkan pesan terakhir yang dikirim admin ke peserta
$stmt = db()->query("
    SELECT n.*, u.nama as penerima 
    FROM notifications n 
    LEFT JOIN users u ON n.to_user_id = u.id 
    ORDER BY n.created_at DESC 
    LIMIT 8
");
$sent_notifs = $stmt->fetchAll();

// Hitung pesan hari ini (untuk badge indikator aktivitas)
$stmt = db()->query("SELECT COUNT(*) as total FROM notifications WHERE DATE(created_at) = CURDATE()");
$today_count = $stmt->fetch()['total'];

// 2. GET FAVICON & LOGO
$favicon_url = get_setting('favicon');
if($favicon_url && !str_starts_with($favicon_url, 'http')) {
    $favicon_url = BASE_URL . $favicon_url;
}

$logo_url = get_logo_url();
if($logo_url && !str_starts_with($logo_url, 'http')) {
    $logo_url = BASE_URL . $logo_url;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?><?= htmlspecialchars($site_name) ?></title>
    
    <?php if($favicon_url): ?>
        <link rel="shortcut icon" href="<?= $favicon_url ?>" type="image/x-icon">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); }
        .fixed-header { z-index: 50; }
        .fixed-sidebar { z-index: 40; }
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        
        /* Scrollbar Halus */
        .notif-scroll::-webkit-scrollbar { width: 5px; }
        .notif-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .notif-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .notif-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-gray-50 pt-16 md:pl-64 min-h-screen transition-all duration-300">

<nav class="gradient-bg text-white shadow-lg fixed top-0 left-0 right-0 h-16 fixed-header">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            
            <div class="flex items-center">
                <button id="mobile-menu-btn" class="md:hidden mr-4 p-2 rounded-lg hover:bg-white/10 focus:outline-none transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <a href="<?= BASE_URL ?>/admin/" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-white/90 rounded-xl flex items-center justify-center shadow-sm group-hover:bg-white transition-all overflow-hidden p-1">
                        <?php if($logo_url): ?>
                            <img src="<?= $logo_url ?>" class="w-full h-full object-contain">
                        <?php else: ?>
                            <i class="fas fa-user-shield text-xl text-blue-600"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 class="text-lg md:text-xl font-bold leading-tight"><?= htmlspecialchars($site_name) ?></h1>
                        <p class="text-[10px] md:text-xs text-blue-100 hidden sm:block">Administrator Panel</p>
                    </div>
                </a>
            </div>

            <div class="flex items-center space-x-2 md:space-x-4">
                
                <div class="relative group">
                    <button class="relative p-2 hover:bg-white/10 rounded-lg transition-all focus:outline-none">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if($today_count > 0): ?>
                        <span class="absolute top-1 right-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border border-white animate-pulse">
                            <?= $today_count > 9 ? '9+' : $today_count ?>
                        </span>
                        <?php endif; ?>
                    </button>

                    <div class="hidden group-hover:block absolute right-0 mt-0 w-80 sm:w-96 bg-white rounded-xl shadow-2xl py-2 text-gray-800 border border-gray-100 z-50 origin-top-right">
                        <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-xl">
                            <h3 class="font-bold text-gray-700 text-sm">Pesan Terkirim Terbaru</h3>
                            <a href="<?= BASE_URL ?>/admin/notifikasi/" class="text-xs text-blue-600 hover:text-blue-800 font-semibold">Lihat Semua</a>
                        </div>
                        
                        <div class="max-h-[350px] overflow-y-auto notif-scroll">
                            <?php if(count($sent_notifs) > 0): ?>
                                <?php foreach($sent_notifs as $sn): ?>
                                <a href="<?= BASE_URL ?>/admin/notifikasi/" class="block px-4 py-3 border-b border-gray-50 hover:bg-blue-50/50 transition-colors">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 mt-1">
                                            <?php 
                                            $icon = 'info-circle'; 
                                            $color = 'text-blue-500';
                                            if($sn['tipe'] == 'warning') { $icon = 'exclamation-triangle'; $color = 'text-orange-500'; }
                                            elseif($sn['tipe'] == 'success') { $icon = 'check-circle'; $color = 'text-green-500'; }
                                            elseif($sn['tipe'] == 'danger') { $icon = 'bolt'; $color = 'text-red-500'; }
                                            ?>
                                            <i class="fas fa-<?= $icon ?> <?= $color ?>"></i>
                                        </div>
                                        
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-gray-800 truncate">
                                                Ke: <?= $sn['penerima'] ? htmlspecialchars($sn['penerima']) : 'Semua Peserta' ?>
                                            </p>
                                            <p class="text-xs text-gray-600 font-medium truncate">
                                                <?= htmlspecialchars($sn['title']) ?>
                                            </p>
                                            <p class="text-[11px] text-gray-500 truncate">
                                                <?= htmlspecialchars($sn['pesan']) ?>
                                            </p>
                                            <div class="flex items-center justify-between mt-1">
                                                <span class="text-[10px] text-gray-400">
                                                    <?= format_tanggal_id($sn['created_at']) ?>
                                                </span>
                                                <?php if($sn['dibaca']): ?>
                                                    <span class="text-[10px] text-green-600 flex items-center font-semibold bg-green-50 px-1.5 rounded">
                                                        <i class="fas fa-check-double mr-1 text-[9px]"></i> Dibaca
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-gray-400 flex items-center">
                                                        <i class="fas fa-check mr-1 text-[9px]"></i> Terkirim
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="px-4 py-8 text-center text-gray-500 text-sm">
                                    <i class="fas fa-paper-plane text-2xl mb-2 text-gray-300"></i>
                                    <p>Belum ada pesan dikirim</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="px-4 py-2 bg-gray-50 rounded-b-xl text-center border-t border-gray-100">
                            <a href="<?= BASE_URL ?>/admin/notifikasi/" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                                + Kirim Pesan Baru
                            </a>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button class="flex items-center space-x-2 p-2 hover:bg-white/10 rounded-lg transition-all">
                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center border border-white/30">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <span class="font-semibold text-sm hidden md:block"><?= htmlspecialchars($user_name) ?></span>
                        <i class="fas fa-chevron-down text-xs ml-1 opacity-70"></i>
                    </button>
                    
                    <div class="hidden group-hover:block absolute right-0 mt-0 w-48 bg-white rounded-xl shadow-xl py-2 text-gray-800 border border-gray-100 z-50 origin-top-right">
                        <div class="px-4 py-2 border-b border-gray-50 md:hidden">
                            <p class="text-xs text-gray-500">Login sebagai</p>
                            <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($user_name) ?></p>
                        </div>
                        <a href="<?= BASE_URL ?>/admin/profile/" class="block px-4 py-2 hover:bg-blue-50 transition-all text-sm">
                            <i class="fas fa-user-circle mr-2 text-blue-600 w-5"></i>Profile
                        </a>
                        <a href="<?= BASE_URL ?>/admin/setting/" class="block px-4 py-2 hover:bg-blue-50 transition-all text-sm">
                            <i class="fas fa-cog mr-2 text-gray-600 w-5"></i>Settings
                        </a>
                        <hr class="my-2 border-gray-100">
                        <a href="<?= BASE_URL ?>/login/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 transition-all text-sm">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>