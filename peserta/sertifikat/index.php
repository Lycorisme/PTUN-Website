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

// =============================================
// HITUNG NILAI AKHIR (SAMA DENGAN ADMIN SERTIFIKAT)
// =============================================
// Ambil settings bobot
$bobot_hadir = intval(get_setting('sertifikat_bobot_hadir', 60));
$bobot_laporan = intval(get_setting('sertifikat_bobot_laporan', 40));

// VALIDASI: Pastikan total bobot = 100%
// Jika tidak valid, gunakan default (60% kehadiran + 40% kinerja)
if (($bobot_hadir + $bobot_laporan) != 100) {
    $bobot_hadir = 60;
    $bobot_laporan = 40;
}

$total_hari = intval(get_setting('absensi_max_hari', 30));

// Hitung kehadiran
$stmt_hadir = db()->prepare("SELECT COUNT(*) as total FROM absensi WHERE peserta_id = ? AND status = 'hadir'");
$stmt_hadir->execute([$user['id']]);
$kehadiran_data = $stmt_hadir->fetch();
$total_hadir = intval($kehadiran_data['total'] ?? 0);

// Nilai Kehadiran (skala 0-100)
if ($total_hari > 0) {
    $nilai_kehadiran = ($total_hadir / $total_hari) * 100;
    $nilai_kehadiran = min($nilai_kehadiran, 100); // Batasi max 100%
} else {
    $nilai_kehadiran = 0;
}

// Hitung rata-rata kinerja (5 aspek)
$aspek_kinerja = [
    floatval($penilaian['disiplin'] ?? 0),
    floatval($penilaian['kerjasama'] ?? 0),
    floatval($penilaian['inisiatif'] ?? 0),
    floatval($penilaian['kerajinan'] ?? 0),
    floatval($penilaian['kualitas_kerja'] ?? 0)
];
$rata_kinerja = array_sum($aspek_kinerja) / count($aspek_kinerja);

// Bobot per aspek kinerja
$bobot_per_aspek = $bobot_laporan / 5;

// Hitung kontribusi per aspek
$kontribusi_kinerja = 0;
foreach ($aspek_kinerja as $nilai) {
    $kontribusi_kinerja += $nilai * $bobot_per_aspek / 100;
}

// Kontribusi kehadiran
$kontribusi_hadir = $nilai_kehadiran * $bobot_hadir / 100;

// NILAI AKHIR = total kontribusi semua komponen
$nilai_akhir_hitung = $kontribusi_kinerja + $kontribusi_hadir;

// Helper untuk predikat
function getPredikatPeserta($nilai) {
    $n = floatval($nilai);
    if ($n >= 90) return ['A', 'Sangat Memuaskan'];
    if ($n >= 80) return ['B', 'Memuaskan'];
    if ($n >= 70) return ['C', 'Cukup'];
    if ($n >= 60) return ['D', 'Kurang'];
    return ['E', 'Tidak Lulus'];
}
list($predikat, $ket_predikat) = getPredikatPeserta($nilai_akhir_hitung);

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
                                <?= number_format($nilai_akhir_hitung, 2) ?>
                            </div>
                            <div class="text-sm uppercase tracking-wider text-orange-100">
                                Nilai Akhir Anda
                            </div>
                            <div class="text-xl font-semibold mt-2 bg-white/30 rounded-lg px-3 py-1 inline-block">
                                Predikat: <?= $predikat ?> (<?= $ket_predikat ?>)
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
                                <?= number_format($nilai_akhir_hitung, 2) ?>
                            </div>
                            <div class="text-sm font-semibold text-gray-600 mt-1">
                                Predikat: <span class="text-blue-600"><?= $predikat ?></span>
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
                            <span class="text-sm text-gray-600">Kedisiplinan <span class="text-xs text-gray-400">(<?= number_format($bobot_per_aspek, 1) ?>%)</span></span>
                            <span class="font-bold text-blue-600"><?= $penilaian['disiplin'] ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Kerjasama <span class="text-xs text-gray-400">(<?= number_format($bobot_per_aspek, 1) ?>%)</span></span>
                            <span class="font-bold text-blue-600"><?= $penilaian['kerjasama'] ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Inisiatif <span class="text-xs text-gray-400">(<?= number_format($bobot_per_aspek, 1) ?>%)</span></span>
                            <span class="font-bold text-blue-600"><?= $penilaian['inisiatif'] ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Kerajinan <span class="text-xs text-gray-400">(<?= number_format($bobot_per_aspek, 1) ?>%)</span></span>
                            <span class="font-bold text-blue-600"><?= $penilaian['kerajinan'] ?? 0 ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Kualitas Kerja <span class="text-xs text-gray-400">(<?= number_format($bobot_per_aspek, 1) ?>%)</span></span>
                            <span class="font-bold text-blue-600"><?= $penilaian['kualitas_kerja'] ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100 bg-blue-50 -mx-4 px-4 py-2 rounded-lg">
                            <span class="text-sm text-gray-600">Kehadiran <span class="text-xs text-gray-400">(<?= $total_hadir ?>/<?= $total_hari ?> hari - <?= $bobot_hadir ?>%)</span></span>
                            <span class="font-bold text-blue-600"><?= number_format($nilai_kehadiran, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-3 bg-gradient-to-r from-green-50 to-emerald-50 -mx-4 px-4 py-3 rounded-lg border border-green-200">
                            <span class="text-sm font-bold text-gray-800">Nilai Akhir</span>
                            <span class="font-bold text-xl text-green-600"><?= number_format($nilai_akhir_hitung, 2) ?> <span class="text-sm">(<?= $predikat ?>)</span></span>
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