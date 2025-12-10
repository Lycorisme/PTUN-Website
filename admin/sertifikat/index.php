<?php
// =============================================
// ADMIN SERTIFIKAT - INDEX (FIXED)
// =============================================

require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/login/');
    exit;
}

// HANDLE GENERATE SERTIFIKAT
if(isset($_POST['generate'])) {
    $peserta_id = $_POST['peserta_id'];
    $penilaian = $_POST['penilaian_final'];
    $nomor = 'SERT/' . date('Y') . '/' . str_pad($peserta_id, 4, '0', STR_PAD_LEFT);
    
    // Kita simpan path referensi, tapi cetak fisik pakai cetak.php
    $file_path = 'uploads/sertifikat/sertifikat_' . $peserta_id . '.pdf'; 
    
    // Insert or Update (issued_date diisi NOW())
    $stmt = db()->prepare("INSERT INTO sertifikat (peserta_id, nomor_sertifikat, file_path, issued_date, penilaian_final, status) VALUES (?, ?, ?, NOW(), ?, 'tersedia') ON DUPLICATE KEY UPDATE penilaian_final=?, status='tersedia', issued_date=NOW()");
    $stmt->execute([$peserta_id, $nomor, $file_path, $penilaian, $penilaian]);
    
    // Kirim notifikasi
    $stmt = db()->prepare("INSERT INTO notifications (to_user_id, title, pesan, tipe, created_at) VALUES (?, 'Sertifikat Terbit', 'Sertifikat magang Anda telah diterbitkan. Silakan unduh.', 'success', NOW())");
    $stmt->execute([$peserta_id]);

    header('Location: index.php?msg=generated');
    exit;
}

// GET DATA SERTIFIKAT
// Ambil u.id sebagai user_id untuk parameter link cetak
$stmt = db()->query("
    SELECT s.*, u.nama, u.instansi, u.id as user_id 
    FROM sertifikat s 
    JOIN users u ON s.peserta_id = u.id 
    ORDER BY s.created_at DESC
");
$sertifikat_list = $stmt->fetchAll();

// GET PESERTA LIST (Untuk Dropdown Modal)
$stmt = db()->query("SELECT id, nama, instansi FROM users WHERE role='peserta' AND status='aktif' ORDER BY nama");
$peserta_list = $stmt->fetchAll();

$page_title = 'Kelola Sertifikat';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-check-circle mr-2"></i>Sertifikat berhasil di-generate!
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Sertifikat</h2>
            <button onclick="showGenerateModal()" class="bg-orange-500 text-white px-6 py-3 rounded-xl font-bold hover:bg-orange-600 transition-all shadow-md flex items-center">
                <i class="fas fa-plus mr-2"></i> Generate Sertifikat Baru
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200 bg-gray-50">
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Nomor Sertifikat</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Nama Peserta</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Instansi</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Nilai</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Tanggal Terbit</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Status</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($sertifikat_list)): ?>
                        <tr><td colspan="7" class="p-8 text-center text-gray-500">Belum ada sertifikat</td></tr>
                    <?php else: ?>
                        <?php foreach($sertifikat_list as $s): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-4 font-mono font-semibold text-gray-900 text-sm"><?= htmlspecialchars($s['nomor_sertifikat']) ?></td>
                            <td class="py-4 px-4 font-semibold"><?= htmlspecialchars($s['nama']) ?></td>
                            <td class="py-4 px-4 text-gray-600 text-sm"><?= htmlspecialchars($s['instansi']) ?></td>
                            <td class="py-4 px-4 text-center">
                                <span class="font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                    <?= $s['penilaian_final'] ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-gray-700 text-sm">
                                <?php 
                                // FIX ERROR NULL DATE DENGAN LOGIKA EKSPLISIT
                                if (isset($s['issued_date']) && $s['issued_date'] != null && $s['issued_date'] != '0000-00-00') {
                                    echo date('d/m/Y', strtotime($s['issued_date']));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <?php
                                $badge = ['pending'=>'bg-yellow-100 text-yellow-800', 'tersedia'=>'bg-green-100 text-green-800', 'selesai'=>'bg-blue-100 text-blue-800'];
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $badge[$s['status']] ?>">
                                    <?= $s['status'] ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <a href="cetak.php?id=<?= $s['user_id'] ?>" target="_blank" class="bg-red-50 text-red-600 px-3 py-2 rounded-lg hover:bg-red-600 hover:text-white transition-all font-bold text-xs inline-flex items-center border border-red-200">
                                    <i class="fas fa-print mr-2"></i> PDF
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="generateModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 transform transition-all scale-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900">Generate Sertifikat</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST">
            <div class="space-y-6">
                <div>
                    <label class="block font-bold mb-2 text-gray-700">Pilih Peserta</label>
                    <select name="peserta_id" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 outline-none transition-all bg-gray-50">
                        <option value="">-- Pilih Peserta --</option>
                        <?php foreach($peserta_list as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> - <?= htmlspecialchars($p['instansi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-2 text-gray-700">Nilai Akhir (0-100)</label>
                    <input type="number" name="penilaian_final" required min="0" max="100" step="0.1" 
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-orange-500 outline-none transition-all"
                           placeholder="Contoh: 85.5">
                </div>
            </div>
            <div class="flex space-x-4 mt-8">
                <button type="submit" name="generate" class="flex-1 bg-gradient-to-r from-orange-500 to-orange-600 text-white py-3 rounded-xl font-bold hover:shadow-lg transition-all">
                    <i class="fas fa-certificate mr-2"></i>Generate
                </button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all">
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
document.getElementById('generateModal').addEventListener('click', function(e) {
    if(e.target === this) closeModal();
});
</script>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>