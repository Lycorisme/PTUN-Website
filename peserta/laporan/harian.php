<?php
// =============================================
// PESERTA LAPORAN HARIAN
// C:\laragon\www\ptun-website\peserta\laporan\harian.php
// =============================================

require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$user = protect_page('peserta');
$peserta_id = $user['id'];
$base_url = '../';
$page_title = 'Laporan Harian';

// HANDLE SUBMIT
if(isset($_POST['submit_laporan'])) {
    $tanggal = $_POST['tanggal'];
    $isi_laporan = $_POST['isi_laporan'];
    
    $stmt = db()->prepare("INSERT INTO laporan_harian (peserta_id, tanggal, isi_laporan, approved) VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE isi_laporan=?");
    $stmt->execute([$peserta_id, $tanggal, $isi_laporan, $isi_laporan]);
    
    header('Location: harian.php?msg=success');
    exit;
}

// HANDLE PDF DOWNLOAD
if(isset($_GET['download'])) {
    $id = $_GET['download'];
    $stmt = db()->prepare("SELECT lh.*, u.nama, u.instansi FROM laporan_harian lh JOIN users u ON lh.peserta_id=u.id WHERE lh.id=? AND lh.peserta_id=?");
    $stmt->execute([$id, $peserta_id]);
    $laporan = $stmt->fetch();
    
    if($laporan) {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; }
                .header { text-align: center; border-bottom: 3px solid #3b82f6; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #1e40af; margin: 10px 0; }
                .info-table { width: 100%; margin-bottom: 30px; }
                .info-table td { padding: 8px; border: 1px solid #ddd; }
                .content { line-height: 1.8; text-align: justify; }
                .footer { margin-top: 50px; text-align: right; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>LAPORAN HARIAN MAGANG</h1>
                <h2>PTUN Banjarmasin</h2>
            </div>
            
            <table class="info-table">
                <tr><td width="30%"><strong>Nama</strong></td><td>' . htmlspecialchars($laporan['nama']) . '</td></tr>
                <tr><td><strong>Instansi</strong></td><td>' . htmlspecialchars($laporan['instansi']) . '</td></tr>
                <tr><td><strong>Tanggal</strong></td><td>' . format_tanggal_id($laporan['tanggal']) . '</td></tr>
            </table>
            
            <h3>Isi Laporan:</h3>
            <div class="content">' . nl2br(htmlspecialchars($laporan['isi_laporan'])) . '</div>
            
            <div class="footer">
                <p>Banjarmasin, ' . format_tanggal_id(date('Y-m-d')) . '</p>
                <p style="margin-top: 80px;">_______________________<br><strong>' . htmlspecialchars($laporan['nama']) . '</strong></p>
            </div>
        </body>
        </html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('Laporan_Harian_' . $laporan['tanggal'] . '.pdf');
        exit;
    }
}

// GET LAPORAN
$stmt = db()->prepare("SELECT * FROM laporan_harian WHERE peserta_id=? ORDER BY tanggal DESC");
$stmt->execute([$peserta_id]);
$laporan_list = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
        <i class="fas fa-check-circle mr-2"></i>Laporan berhasil disubmit!
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8 mb-8">
        <h2 class="text-2xl font-bold mb-6">Buat Laporan Harian</h2>
        <form method="POST" class="space-y-6">
            <div>
                <label class="block font-bold mb-2">Tanggal</label>
                <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
            </div>
            <div>
                <label class="block font-bold mb-2">Isi Laporan</label>
                <textarea name="isi_laporan" required rows="10" 
                          class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl"
                          placeholder="Tuliskan kegiatan yang dilakukan hari ini..."></textarea>
            </div>
            <button type="submit" name="submit_laporan"
                    class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-4 rounded-xl font-bold hover:shadow-lg">
                <i class="fas fa-paper-plane mr-2"></i>Submit Laporan
            </button>
        </form>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6">Riwayat Laporan Harian</h2>
        <div class="space-y-4">
            <?php foreach($laporan_list as $l): ?>
            <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 hover:shadow-lg transition-all">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-calendar text-blue-600 mr-2"></i>
                            <span class="font-bold text-gray-900"><?= format_tanggal_id($l['tanggal']) ?></span>
                            <?php if($l['approved']): ?>
                            <span class="ml-3 px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold">
                                <i class="fas fa-check-circle"></i> Approved
                            </span>
                            <?php else: ?>
                            <span class="ml-3 px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-bold">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-700 line-clamp-2"><?= htmlspecialchars(substr($l['isi_laporan'], 0, 200)) ?>...</p>
                    </div>
                    <a href="?download=<?= $l['id'] ?>" target="_blank"
                       class="ml-4 bg-red-500 text-white px-4 py-2 rounded-xl hover:bg-red-600 transition-all">
                        <i class="fas fa-file-pdf mr-2"></i>PDF
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/sidebar.php'; ?>
</body>
</html>