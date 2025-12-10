<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') exit;

$user = $_SESSION['user_data'];
$peserta_id = $user['id'];

if(isset($_POST['submit_laporan'])) {
    $jenis = $_POST['jenis'];
    $isi_laporan = $_POST['isi_laporan'];
    
    if($jenis == 'harian') {
        $tanggal = $_POST['tanggal'];
        $stmt = db()->prepare("INSERT INTO laporan_harian (peserta_id, tanggal, isi_laporan) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE isi_laporan=?");
        $stmt->execute([$peserta_id, $tanggal, $isi_laporan, $isi_laporan]);
    } elseif($jenis == 'mingguan') {
        $minggu_ke = $_POST['minggu_ke'];
        $bulan = $_POST['bulan'];
        $tahun = $_POST['tahun'];
        $stmt = db()->prepare("INSERT INTO laporan_mingguan (peserta_id, minggu_ke, bulan, tahun, isi_laporan) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE isi_laporan=?");
        $stmt->execute([$peserta_id, $minggu_ke, $bulan, $tahun, $isi_laporan, $isi_laporan]);
    } elseif($jenis == 'bulanan') {
        $bulan = $_POST['bulan'];
        $tahun = $_POST['tahun'];
        $stmt = db()->prepare("INSERT INTO laporan_bulanan (peserta_id, bulan, tahun, isi_laporan) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE isi_laporan=?");
        $stmt->execute([$peserta_id, $bulan, $tahun, $isi_laporan, $isi_laporan]);
    } elseif($jenis == 'ringkasan') {
        $periode_start = $_POST['periode_start'];
        $periode_end = $_POST['periode_end'];
        $stmt = db()->prepare("INSERT INTO laporan_ringkasan (peserta_id, periode_start, periode_end, ringkasan) VALUES (?, ?, ?, ?)");
        $stmt->execute([$peserta_id, $periode_start, $periode_end, $isi_laporan]);
    }
    
    header('Location: index.php?msg=success');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Laporan - Peserta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
<nav class="bg-gradient-to-r from-pink-600 to-pink-700 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="../" class="text-white"><i class="fas fa-arrow-left text-xl"></i></a>
            <h1 class="text-2xl font-bold">Buat Laporan</h1>
        </div>
        <a href="../" class="bg-white/20 px-6 py-2 rounded-xl">Dashboard</a>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
        <i class="fas fa-check-circle mr-2"></i>Laporan berhasil disubmit!
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6">Form Generate Laporan</h2>
        <form method="POST" class="space-y-6">
            <div>
                <label class="block font-bold mb-2">Jenis Laporan</label>
                <select name="jenis" id="jenisLaporan" required onchange="updateForm()"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-pink-500">
                    <option value="">-- Pilih Jenis --</option>
                    <option value="harian">Laporan Harian</option>
                    <option value="mingguan">Laporan Mingguan</option>
                    <option value="bulanan">Laporan Bulanan</option>
                    <option value="ringkasan">Laporan Ringkasan</option>
                </select>
            </div>

            <div id="formHarian" class="hidden">
                <label class="block font-bold mb-2">Tanggal</label>
                <input type="date" name="tanggal" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
            </div>

            <div id="formMingguan" class="hidden">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold mb-2">Minggu Ke-</label>
                        <input type="number" name="minggu_ke" min="1" max="5" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Bulan</label>
                        <input type="number" name="bulan" min="1" max="12" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Tahun</label>
                        <input type="number" name="tahun" value="<?= date('Y') ?>" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
                    </div>
                </div>
            </div>

            <div id="formBulanan" class="hidden">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-2">Bulan</label>
                        <input type="number" name="bulan" min="1" max="12" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Tahun</label>
                        <input type="number" name="tahun" value="<?= date('Y') ?>" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
                    </div>
                </div>
            </div>

            <div id="formRingkasan" class="hidden">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-2">Periode Mulai</label>
                        <input type="date" name="periode_start" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Periode Selesai</label>
                        <input type="date" name="periode_end" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-2">Isi Laporan</label>
                <textarea name="isi_laporan" required rows="10" placeholder="Tulis isi laporan..."
                          class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-pink-500"></textarea>
            </div>

            <button type="submit" name="submit_laporan"
                    class="w-full bg-gradient-to-r from-pink-500 to-pink-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg">
                <i class="fas fa-paper-plane mr-2"></i>Submit Laporan
            </button>
        </form>
    </div>
</div>

<script>
function updateForm() {
    const jenis = document.getElementById('jenisLaporan').value;
    document.querySelectorAll('[id^="form"]').forEach(el => el.classList.add('hidden'));
    if(jenis) {
        document.getElementById('form' + jenis.charAt(0).toUpperCase() + jenis.slice(1)).classList.remove('hidden');
    }
}
</script>
</body>
</html>