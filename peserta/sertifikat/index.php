<?php
// =============================================
// PESERTA SERTIFIKAT - VIEW & DOWNLOAD
// =============================================

require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$user = $_SESSION['user_data'];

// Ambil data sertifikat
$stmt = db()->prepare("SELECT * FROM sertifikat WHERE peserta_id = ?");
$stmt->execute([$user['id']]);
$sertifikat = $stmt->fetch();

// Ambil data penilaian untuk preview
$stmt_nilai = db()->prepare("SELECT * FROM penilaian WHERE peserta_id = ?");
$stmt_nilai->execute([$user['id']]);
$penilaian = $stmt_nilai->fetch();

$page_title = 'Sertifikat Magang';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <?php if(!$sertifikat): ?>
        <!-- SERTIFIKAT BELUM TERSEDIA -->
        <div class="bg-white rounded-3xl shadow-xl p-12 min-h-[500px] flex flex-col items-center justify-center text-center border-2 border-dashed border-gray-200">
            <div class="w-32 h-32 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mb-6 shadow-inner">
                <i class="fas fa-certificate text-5xl text-gray-400"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-3">Sertifikat Belum Tersedia</h2>
            <p class="text-gray-500 max-w-md mb-8">
                Admin belum menerbitkan sertifikat untuk Anda. Pastikan Anda telah menyelesaikan semua tugas dan memenuhi persyaratan magang.
            </p>
            
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 max-w-lg">
                <h3 class="font-bold text-blue-900 mb-3 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    Persyaratan Sertifikat
                </h3>
                <ul class="text-sm text-blue-800 text-left space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                        <span>Menyelesaikan masa magang sesuai durasi yang ditentukan</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                        <span>Kehadiran minimal <?= get_setting('sertifikat_min_hadir', 80) ?>%</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                        <span>Nilai akhir minimal <?= get_setting('sertifikat_min_score', 75) ?></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                        <span>Menyelesaikan semua tugas dan laporan</span>
                    </li>
                </ul>
            </div>
        </div>
    
    <?php else: ?>
        <!-- SERTIFIKAT TERSEDIA -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- MAIN CARD -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-3xl shadow-2xl p-12 text-white text-center relative overflow-hidden">
                    <!-- Decorative circles -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>
                    
                    <div class="relative z-10">
                        <div class="w-28 h-28 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mb-6 mx-auto shadow-xl animate-bounce">
                            <i class="fas fa-award text-6xl text-white"></i>
                        </div>
                        
                        <h2 class="text-4xl font-bold mb-3">Selamat! 🎉</h2>
                        <p class="text-xl mb-2 text-orange-100">Sertifikat Anda Tersedia</p>
                        
                        <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-6 my-8 max-w-md mx-auto">
                            <div class="text-5xl font-bold mb-2">
                                <?= number_format($sertifikat['penilaian_final'], 2) ?>
                            </div>
                            <div class="text-sm uppercase tracking-wider text-orange-100">
                                Nilai Akhir Anda
                            </div>
                        </div>
                        
                        <p class="text-orange-100 mb-8 max-w-lg mx-auto">
                            Anda telah berhasil menyelesaikan program magang dengan hasil yang memuaskan. 
                            Unduh sertifikat Anda sekarang!
                        </p>
                        
                        <a href="../../admin/sertifikat/cetak.php?id=<?= $user['id'] ?>" target="_blank" 
                           class="inline-flex items-center gap-3 bg-white text-orange-600 px-8 py-4 rounded-2xl font-bold text-lg hover:shadow-2xl hover:scale-105 transition-all">
                            <i class="fas fa-download text-2xl"></i>
                            Download E-Sertifikat
                        </a>
                        
                        <p class="text-xs text-orange-100 mt-4">
                            <i class="fas fa-file-pdf mr-1"></i>
                            Format: PDF • 2 Halaman
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- SIDE INFO -->
            <div class="space-y-6">
                <!-- Info Card -->
                <div class="bg-white rounded-3xl shadow-xl p-6">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Detail Sertifikat
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="pb-3 border-b border-gray-100">
                            <div class="text-xs text-gray-500 mb-1">Nomor Sertifikat</div>
                            <div class="font-mono font-bold text-sm bg-blue-50 text-blue-700 px-3 py-1 rounded-lg inline-block">
                                <?= htmlspecialchars($sertifikat['nomor_sertifikat']) ?>
                            </div>
                        </div>
                        
                        <div class="pb-3 border-b border-gray-100">
                            <div class="text-xs text-gray-500 mb-1">Tanggal Terbit</div>
                            <div class="font-bold text-gray-800">
                                <i class="fas fa-calendar-alt text-green-500 mr-2"></i>
                                <?php 
                                if(!empty($sertifikat['issued_date']) && $sertifikat['issued_date'] !== '0000-00-00') {
                                    echo format_tanggal_id($sertifikat['issued_date']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="pb-3 border-b border-gray-100">
                            <div class="text-xs text-gray-500 mb-1">Status</div>
                            <div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase bg-green-100 text-green-800 border border-green-300">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    <?= htmlspecialchars($sertifikat['status']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Nilai Akhir</div>
                            <div class="text-3xl font-bold text-green-600">
                                <?= number_format($sertifikat['penilaian_final'], 2) ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Penilaian Preview -->
                <?php if($penilaian): ?>
                <div class="bg-white rounded-3xl shadow-xl p-6">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-chart-bar text-purple-500 mr-2"></i>
                        Ringkasan Nilai
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Disiplin</span>
                            <span class="font-bold text-blue-600"><?= $penilaian['disiplin'] ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Kerjasama</span>
                            <span class="font-bold text-blue-600"><?= $penilaian['kerjasama'] ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Inisiatif</span>
                            <span class="font-bold text-blue-600"><?= $penilaian['inisiatif'] ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Kualitas Kerja</span>
                            <span class="font-bold text-blue-600"><?= $penilaian['kualitas_kerja'] ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <span class="text-sm font-bold text-gray-800">Rata-rata</span>
                            <span class="font-bold text-lg text-green-600"><?= number_format($penilaian['nilai_rata_rata'], 2) ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Help Card -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-3xl p-6">
                    <h3 class="font-bold text-blue-900 mb-3 flex items-center">
                        <i class="fas fa-question-circle mr-2"></i>
                        Butuh Bantuan?
                    </h3>
                    <p class="text-sm text-blue-800 mb-4">
                        Jika mengalami kendala saat mengunduh sertifikat, silakan hubungi admin.
                    </p>
                    <a href="mailto:<?= get_setting('email_kontak', 'info@instansi.go.id') ?>" 
                       class="text-sm text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center">
                        <i class="fas fa-envelope mr-2"></i>
                        Hubungi Admin
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/sidebar.php'; ?>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}
</style>

</body>
</html>