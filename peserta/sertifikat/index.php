<?php
// =============================================
// PESERTA SERTIFIKAT - INDEX (FIXED)
// =============================================

require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$user = $_SESSION['user_data'];
$peserta_id = $user['id'];

// CHECK SERTIFIKAT
$stmt = db()->prepare("SELECT * FROM sertifikat WHERE peserta_id = ?");
$stmt->execute([$peserta_id]);
$sertifikat = $stmt->fetch();

$page_title = 'Sertifikat Magang';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="bg-white rounded-3xl shadow-xl p-8 min-h-[400px] flex flex-col items-center justify-center text-center border border-gray-100">
        
        <?php if(!$sertifikat): ?>
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-certificate text-4xl text-gray-400"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Sertifikat Belum Tersedia</h2>
            <p class="text-gray-500 max-w-md">
                Admin belum menerbitkan sertifikat untuk Anda. Pastikan Anda telah menyelesaikan seluruh kegiatan magang dan memenuhi kriteria kehadiran.
            </p>
        
        <?php elseif($sertifikat['status'] == 'pending'): ?>
            <div class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center mb-6 animate-pulse">
                <i class="fas fa-hourglass-half text-4xl text-yellow-600"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Dalam Proses Penerbitan</h2>
            <p class="text-gray-500 max-w-md">
                Sertifikat Anda sedang diproses oleh admin. Silakan cek kembali secara berkala.
            </p>

        <?php else: // TERSEDIA / SELESAI ?>
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-6 shadow-lg shadow-green-200 animate-bounce">
                <i class="fas fa-award text-5xl text-green-600"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Selamat! Sertifikat Tersedia</h2>
            <p class="text-gray-600 mb-8 max-w-lg">
                Selamat <strong><?= htmlspecialchars($user['nama']) ?></strong>! Anda telah menyelesaikan kegiatan magang di <?= get_site_name() ?> dengan nilai akhir 
                <span class="font-bold text-green-600 text-lg"><?= htmlspecialchars($sertifikat['penilaian_final']) ?></span>.
            </p>
            
            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200 w-full max-w-xl mb-8 shadow-inner">
                <div class="flex justify-between items-center mb-2 border-b border-gray-200 pb-2">
                    <span class="text-gray-500">Nomor Sertifikat</span>
                    <span class="font-mono font-bold text-gray-800"><?= htmlspecialchars($sertifikat['nomor_sertifikat']) ?></span>
                </div>
                <div class="flex justify-between items-center mb-2 border-b border-gray-200 pb-2">
                    <span class="text-gray-500">Tanggal Terbit</span>
                    <span class="font-bold text-gray-800">
                        <?php 
                        // FIX ERROR DATE
                        if (isset($sertifikat['issued_date']) && $sertifikat['issued_date'] != null && $sertifikat['issued_date'] != '0000-00-00') {
                            echo date('d F Y', strtotime($sertifikat['issued_date']));
                        } else {
                            echo '-';
                        }
                        ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Status</span>
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold uppercase">Valid & Terverifikasi</span>
                </div>
            </div>

            <a href="<?= BASE_URL ?>/admin/sertifikat/cetak.php?id=<?= $peserta_id ?>" target="_blank" 
               class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:shadow-xl hover:-translate-y-1 transition-all flex items-center gap-3">
                <i class="fas fa-file-pdf text-2xl"></i>
                Download E-Sertifikat
            </a>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>