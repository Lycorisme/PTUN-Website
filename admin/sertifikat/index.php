<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') exit;

if(isset($_POST['generate'])) {
    $peserta_id = $_POST['peserta_id'];
    $penilaian = $_POST['penilaian_final'];
    $nomor = 'SERT/' . date('Y') . '/' . str_pad($peserta_id, 4, '0', STR_PAD_LEFT);
    $file_path = 'uploads/sertifikat/sertifikat_' . $peserta_id . '_' . time() . '.pdf';
    
    $stmt = db()->prepare("INSERT INTO sertifikat (peserta_id, nomor_sertifikat, file_path, issued_date, penilaian_final, status) VALUES (?, ?, ?, NOW(), ?, 'tersedia') ON DUPLICATE KEY UPDATE penilaian_final=?, status='tersedia', file_path=?");
    $stmt->execute([$peserta_id, $nomor, $file_path, $penilaian, $penilaian, $file_path]);
    
    header('Location: index.php?msg=generated');
    exit;
}

$stmt = db()->query("SELECT s.*, u.nama, u.instansi FROM sertifikat s JOIN users u ON s.peserta_id=u.id ORDER BY s.created_at DESC");
$sertifikat_list = $stmt->fetchAll();

$stmt = db()->query("SELECT id, nama, instansi FROM users WHERE role='peserta' AND status='aktif' ORDER BY nama");
$peserta_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Sertifikat - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
<nav class="bg-gradient-to-r from-orange-600 to-orange-700 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="../" class="text-white"><i class="fas fa-arrow-left text-xl"></i></a>
            <h1 class="text-2xl font-bold">Kelola Sertifikat</h1>
        </div>
        <button onclick="showGenerateModal()" class="bg-white/20 px-6 py-2 rounded-xl font-semibold hover:bg-white/30">
            <i class="fas fa-plus mr-2"></i>Generate Sertifikat
        </button>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
        <i class="fas fa-check-circle mr-2"></i>Sertifikat berhasil di-generate!
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6">Daftar Sertifikat</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2">
                        <th class="text-left py-4 px-4 font-bold">Nomor Sertifikat</th>
                        <th class="text-left py-4 px-4 font-bold">Nama Peserta</th>
                        <th class="text-left py-4 px-4 font-bold">Instansi</th>
                        <th class="text-left py-4 px-4 font-bold">Penilaian</th>
                        <th class="text-left py-4 px-4 font-bold">Tanggal Terbit</th>
                        <th class="text-center py-4 px-4 font-bold">Status</th>
                        <th class="text-center py-4 px-4 font-bold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sertifikat_list as $s): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-4 px-4 font-semibold"><?= htmlspecialchars($s['nomor_sertifikat']) ?></td>
                        <td class="py-4 px-4"><?= htmlspecialchars($s['nama']) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($s['instansi']) ?></td>
                        <td class="py-4 px-4 font-bold text-blue-600"><?= $s['penilaian_final'] ?> / 10</td>
                        <td class="py-4 px-4"><?= date('d/m/Y', strtotime($s['issued_date'])) ?></td>
                        <td class="py-4 px-4 text-center">
                            <?php
                            $badge = ['pending'=>'bg-yellow-100 text-yellow-800', 'tersedia'=>'bg-green-100 text-green-800', 'selesai'=>'bg-blue-100 text-blue-800'];
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $badge[$s['status']] ?>">
                                <?= strtoupper($s['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <a href="../../<?= $s['file_path'] ?>" target="_blank" class="bg-orange-500 text-white px-4 py-2 rounded-xl hover:bg-orange-600">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL GENERATE -->
<div id="generateModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8">
        <h3 class="text-2xl font-bold mb-6">Generate Sertifikat</h3>
        <form method="POST">
            <div class="space-y-6">
                <div>
                    <label class="block font-bold mb-2">Pilih Peserta</label>
                    <select name="peserta_id" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
                        <option value="">-- Pilih Peserta --</option>
                        <?php foreach($peserta_list as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> - <?= htmlspecialchars($p['instansi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-2">Penilaian Final (0-10)</label>
                    <input type="number" name="penilaian_final" required min="0" max="10" step="0.1" 
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
                </div>
            </div>
            <div class="flex space-x-4 mt-6">
                <button type="submit" name="generate" class="flex-1 bg-gradient-to-r from-orange-500 to-orange-600 text-white py-3 rounded-xl font-bold hover:shadow-lg">
                    <i class="fas fa-certificate mr-2"></i>Generate
                </button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-300">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showGenerateModal() {
    document.getElementById('generateModal').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('generateModal').classList.add('hidden');
}
</script>
</body>
</html>