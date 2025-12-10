<?php
// =============================================
// PESERTA LAPORAN - INPUT RINGKASAN AKHIR SAJA
// =============================================

require_once '../../config/database.php';

// Cek Sesi Peserta
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') {
    header('Location: ' . BASE_URL . '/login/');
    exit;
}

$user = $_SESSION['user_data'];
$peserta_id = $user['id'];

// HANDLE SUBMIT LAPORAN
if(isset($_POST['submit_laporan'])) {
    try {
        $periode_start = $_POST['periode_start'];
        $periode_end = $_POST['periode_end'];
        $ringkasan = $_POST['isi_laporan'];
        
        $stmt = db()->prepare("INSERT INTO laporan_ringkasan (peserta_id, periode_start, periode_end, ringkasan) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE ringkasan=VALUES(ringkasan), periode_start=VALUES(periode_start), periode_end=VALUES(periode_end)");
        $stmt->execute([$peserta_id, $periode_start, $periode_end, $ringkasan]);
        
        header('Location: index.php?msg=success');
        exit;

    } catch(PDOException $e) {
        $error = "Gagal menyimpan: " . $e->getMessage();
    }
}

$page_title = 'Laporan Akhir';
require_once '../includes/header.php';
?>

<div class="max-w-4xl mx-auto px-6 py-8">
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-check-circle mr-2"></i>Laporan Akhir berhasil disubmit!
    </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-times-circle mr-2"></i><?= $error ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <div class="border-b pb-4 mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Form Laporan Akhir</h2>
            <p class="text-gray-500 text-sm mt-1">Silakan isi ringkasan seluruh kegiatan magang Anda sebagai laporan akhir.</p>
        </div>
        
        <form method="POST" class="space-y-6">
            
            <div class="bg-blue-50 p-5 rounded-xl border border-blue-100">
                <h3 class="font-bold text-blue-800 mb-3">Periode Magang</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-1 text-sm text-gray-600">Tanggal Mulai</label>
                        <input type="date" name="periode_start" required class="w-full px-4 py-2 border rounded-lg focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold mb-1 text-sm text-gray-600">Tanggal Selesai</label>
                        <input type="date" name="periode_end" required class="w-full px-4 py-2 border rounded-lg focus:border-blue-500 outline-none">
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-2 text-gray-700">Ringkasan Kegiatan & Hasil Magang</label>
                <textarea name="isi_laporan" required rows="10" placeholder="Uraikan secara lengkap ringkasan kegiatan, ilmu yang didapat, dan hasil pekerjaan selama magang..."
                          class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 outline-none transition-all"></textarea>
            </div>

            <button type="submit" name="submit_laporan"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all transform hover:-translate-y-1">
                <i class="fas fa-save mr-2"></i> Simpan Laporan Akhir
            </button>
        </form>
    </div>
</div>

<?php require_once '../includes/sidebar.php'; ?>