<?php
// =============================================
// PESERTA HEADER - RESPONSIVE & LOGO FIX
// =============================================

$site_name = get_site_name();
$user_name = $_SESSION['user_data']['nama'] ?? 'Peserta';
$user_instansi = $_SESSION['user_data']['instansi'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// GET HEADER NOTIFICATIONS
$header_notifs = [];
$notif_count = 0;
if($user_id) {
    $notif_count = get_notification_count($user_id);
    $stmt = db()->prepare("SELECT * FROM notifications WHERE to_user_id=? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $header_notifs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?><?= htmlspecialchars($site_name) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .gradient-bg { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .fixed-header { z-index: 50; }
        .fixed-sidebar { z-index: 40; }
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        .notif-scroll::-webkit-scrollbar { width: 6px; }
        .notif-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .notif-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
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

                <a href="<?= BASE_URL ?>/peserta/" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-white/90 rounded-xl flex items-center justify-center shadow-sm group-hover:bg-white transition-all">
                        <?php 
                        $logo = get_logo_url();
                        $logo_path = (strpos($logo, 'http') === 0) ? $logo : BASE_URL . '/' . ltrim($logo, '/');
                        ?>
                        <?php if($logo): ?>
                            <img src="<?= $logo_path ?>" class="w-8 h-8 object-contain">
                        <?php else: ?>
                            <i class="fas fa-user-graduate text-xl text-green-600"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 class="text-lg md:text-xl font-bold leading-tight"><?= htmlspecialchars($site_name) ?></h1>
                        <p class="text-[10px] md:text-xs text-green-100 hidden sm:block"><?= htmlspecialchars($user_instansi) ?></p>
                    </div>
                </a>
            </div>

            <div class="flex items-center space-x-2 md:space-x-4">
                
                <div class="relative group">
                    <button class="relative p-2 hover:bg-white/10 rounded-lg transition-all focus:outline-none">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if($notif_count > 0): ?>
                        <span class="absolute top-1 right-1 bg-red-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center animate-bounce border border-white">
                            <?= $notif_count > 9 ? '9+' : $notif_count ?>
                        </span>
                        <?php endif; ?>
                    </button>

                    <div class="hidden group-hover:block absolute right-0 mt-0 w-80 bg-white rounded-xl shadow-2xl py-2 text-gray-800 border border-gray-100 z-50 origin-top-right">
                        <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-xl">
                            <h3 class="font-bold text-gray-700 text-sm">Notifikasi</h3>
                            <a href="<?= BASE_URL ?>/peserta/index.php" class="text-xs text-green-600 hover:text-green-700 font-semibold">Lihat Semua</a>
                        </div>
                        
                        <div class="max-h-[300px] overflow-y-auto notif-scroll">
                            <?php if(count($header_notifs) > 0): ?>
                                <?php foreach($header_notifs as $hn): ?>
                                <div class="px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors <?= $hn['dibaca'] == 0 ? 'bg-blue-50/50' : '' ?>">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 mt-1">
                                            <?php if($hn['tipe'] == 'success'): ?>
                                                <i class="fas fa-check-circle text-green-500 text-xs"></i>
                                            <?php elseif($hn['tipe'] == 'warning'): ?>
                                                <i class="fas fa-exclamation-circle text-yellow-500 text-xs"></i>
                                            <?php else: ?>
                                                <i class="fas fa-info-circle text-blue-500 text-xs"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800"><?= htmlspecialchars($hn['title']) ?></p>
                                            <p class="text-[11px] text-gray-500 line-clamp-2 leading-tight"><?= htmlspecialchars($hn['pesan']) ?></p>
                                            <p class="text-[10px] text-gray-400 mt-1"><?= format_tanggal_id($hn['created_at']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="px-4 py-6 text-center text-gray-500 text-sm">
                                    <i class="fas fa-bell-slash text-2xl mb-2 text-gray-300"></i>
                                    <p>Tidak ada notifikasi</p>
                                </div>
                            <?php endif; ?>
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
                        <a href="<?= BASE_URL ?>/peserta/profile/" class="block px-4 py-2 hover:bg-green-50 transition-all text-sm">
                            <i class="fas fa-user-circle mr-2 text-green-600"></i>Profile
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