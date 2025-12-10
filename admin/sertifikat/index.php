<?php
// =============================================
// ADMIN SERTIFIKAT - MANAGEMENT SYSTEM
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
    $file_path = 'uploads/sertifikat/sertifikat_' . $peserta_id . '.pdf'; 
    
    // Insert/Update dengan tanggal sekarang
    $stmt = db()->prepare("
        INSERT INTO sertifikat (peserta_id, nomor_sertifikat, file_path, issued_date, penilaian_final, status) 
        VALUES (?, ?, ?, CURDATE(), ?, 'tersedia') 
        ON DUPLICATE KEY UPDATE 
            penilaian_final = VALUES(penilaian_final), 
            status = 'tersedia', 
            issued_date = CURDATE(),
            nomor_sertifikat = VALUES(nomor_sertifikat)
    ");
    $stmt->execute([$peserta_id, $nomor, $file_path, $penilaian]);
    
    // Kirim Notifikasi ke Peserta
    $stmt = db()->prepare("
        INSERT INTO notifications (to_user_id, title, pesan, tipe, created_at) 
        VALUES (?, 'Sertifikat Terbit', 'Selamat! Sertifikat magang Anda telah diterbitkan dan dapat diunduh.', 'success', NOW())
    ");
    $stmt->execute([$peserta_id]);

    header('Location: index.php?msg=generated');
    exit;
}

// HANDLE DELETE SERTIFIKAT
if(isset($_POST['delete'])) {
    $peserta_id = $_POST['peserta_id'];
    
    // Hapus dari database
    $stmt = db()->prepare("DELETE FROM sertifikat WHERE peserta_id = ?");
    $stmt->execute([$peserta_id]);
    
    header('Location: index.php?msg=deleted');
    exit;
}

// GET DATA SERTIFIKAT
$stmt = db()->query("
    SELECT s.*, u.nama, u.instansi, u.id as user_id 
    FROM sertifikat s 
    JOIN users u ON s.peserta_id = u.id 
    ORDER BY s.created_at DESC
");
$sertifikat_list = $stmt->fetchAll();

// GET PESERTA LIST (Yang belum punya sertifikat)
$stmt = db()->query("
    SELECT u.id, u.nama, u.instansi 
    FROM users u 
    WHERE u.role = 'peserta' 
    AND u.status = 'aktif' 
    ORDER BY u.nama
");
$peserta_list = $stmt->fetchAll();

$page_title = 'Kelola Sertifikat';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="mb-8 p-4 rounded-xl shadow-lg animate-fade-in <?= $_GET['msg']=='deleted' ? 'bg-red-100 border-l-4 border-red-500 text-red-700' : 'bg-green-100 border-l-4 border-green-500 text-green-700' ?>">
        <div class="flex items-center">
            <i class="fas <?= $_GET['msg']=='deleted' ? 'fa-trash' : 'fa-check-circle' ?> text-2xl mr-3"></i>
            <span class="font-semibold">
                <?php 
                if($_GET['msg'] == 'generated') echo 'Sertifikat berhasil di-generate!';
                elseif($_GET['msg'] == 'deleted') echo 'Sertifikat berhasil dihapus!';
                ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-orange-500 to-red-600 p-8">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">Kelola Sertifikat</h2>
                    <p class="text-orange-100">Buat dan kelola sertifikat magang peserta</p>
                </div>
                <button onclick="showGenerateModal()" class="bg-white text-orange-600 px-6 py-3 rounded-xl font-bold hover:bg-orange-50 transition-all shadow-lg flex items-center gap-2">
                    <i class="fas fa-plus-circle text-xl"></i> Generate Baru
                </button>
            </div>
        </div>

        <div class="p-8">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-200 bg-gray-50">
                            <th class="text-left py-4 px-4 font-bold text-gray-700">Nomor Sertifikat</th>
                            <th class="text-left py-4 px-4 font-bold text-gray-700">Nama Peserta</th>
                            <th class="text-left py-4 px-4 font-bold text-gray-700">Instansi</th>
                            <th class="text-center py-4 px-4 font-bold text-gray-700">Nilai Akhir</th>
                            <th class="text-center py-4 px-4 font-bold text-gray-700">Tanggal Terbit</th>
                            <th class="text-center py-4 px-4 font-bold text-gray-700">Status</th>
                            <th class="text-center py-4 px-4 font-bold text-gray-700">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($sertifikat_list)): ?>
                            <tr>
                                <td colspan="7" class="p-12 text-center">
                                    <div class="text-gray-400">
                                        <i class="fas fa-certificate text-6xl mb-4"></i>
                                        <p class="text-lg font-semibold">Belum ada sertifikat yang diterbitkan</p>
                                        <p class="text-sm mt-2">Klik "Generate Baru" untuk membuat sertifikat</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($sertifikat_list as $s): ?>
                            <tr class="border-b hover:bg-blue-50 transition-colors">
                                <td class="py-4 px-4">
                                    <span class="font-mono text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-lg font-bold">
                                        <?= htmlspecialchars($s['nomor_sertifikat']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-semibold text-gray-800">
                                    <?= htmlspecialchars($s['nama']) ?>
                                </td>
                                <td class="py-4 px-4 text-sm text-gray-600">
                                    <?= htmlspecialchars($s['instansi']) ?>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="font-bold text-lg text-green-600">
                                        <?= number_format($s['penilaian_final'], 2) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center text-sm text-gray-700">
                                    <?php 
                                    if(!empty($s['issued_date']) && $s['issued_date'] !== '0000-00-00') {
                                        echo '<i class="fas fa-calendar-alt text-blue-500 mr-2"></i>';
                                        echo date('d/m/Y', strtotime($s['issued_date']));
                                    } else {
                                        echo '<span class="text-gray-400">-</span>';
                                    }
                                    ?>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="px-4 py-1.5 rounded-full text-xs font-bold uppercase bg-green-100 text-green-800 border border-green-300">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <?= htmlspecialchars($s['status']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="cetak.php?id=<?= $s['user_id'] ?>" target="_blank" 
                                           class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-all font-bold text-sm inline-flex items-center shadow-md">
                                            <i class="fas fa-file-pdf mr-2"></i> PDF
                                        </a>
                                        <button onclick="confirmDelete(<?= $s['user_id'] ?>)" 
                                                class="bg-gray-100 text-red-600 px-4 py-2 rounded-lg hover:bg-red-600 hover:text-white transition-all font-bold text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- INFO BOX -->
    <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 p-6 rounded-xl">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-500 text-2xl mr-4 mt-1"></i>
            <div>
                <h4 class="font-bold text-blue-900 mb-2">Informasi Template Sertifikat</h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• Template sertifikat dapat dikustomisasi di menu <strong>Pengaturan > Sistem</strong></li>
                    <li>• Upload background sertifikat untuk tampilan yang lebih profesional</li>
                    <li>• Nilai akhir akan otomatis muncul di sertifikat</li>
                    <li>• QR Code validasi otomatis dibuat untuk setiap sertifikat</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- MODAL GENERATE -->
<div id="generateModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-8 transform scale-100 transition-transform">
        <div class="flex items-center mb-6">
            <div class="bg-orange-100 p-3 rounded-full mr-4">
                <i class="fas fa-certificate text-orange-600 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Generate Sertifikat</h3>
                <p class="text-sm text-gray-500">Buat sertifikat baru untuk peserta</p>
            </div>
        </div>
        
        <form method="POST">
            <div class="space-y-6">
                <div>
                    <label class="block font-bold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-blue-500"></i>Pilih Peserta
                    </label>
                    <select name="peserta_id" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:outline-none">
                        <option value="">-- Pilih Peserta --</option>
                        <?php foreach($peserta_list as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['nama']) ?> - <?= htmlspecialchars($p['instansi']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block font-bold text-gray-700 mb-2">
                        <i class="fas fa-star mr-2 text-yellow-500"></i>Nilai Akhir
                    </label>
                    <input type="number" name="penilaian_final" required min="0" max="100" step="0.01" 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:outline-none" 
                           placeholder="Contoh: 85.50">
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Nilai skala 0-100. Gunakan titik (.) untuk desimal.
                    </p>
                </div>
            </div>
            
            <div class="flex space-x-4 mt-8">
                <button type="submit" name="generate" 
                        class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white py-3.5 rounded-xl font-bold hover:shadow-xl transition-all">
                    <i class="fas fa-check mr-2"></i>Generate Sertifikat
                </button>
                <button type="button" onclick="closeModal()" 
                        class="flex-1 bg-gray-100 text-gray-700 py-3.5 rounded-xl font-bold hover:bg-gray-200 transition-all">
                    <i class="fas fa-times mr-2"></i>Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- FORM DELETE (HIDDEN) -->
<form id="deleteForm" method="POST" class="hidden">
    <input type="hidden" name="peserta_id" id="deletePesertaId">
    <input type="hidden" name="delete" value="1">
</form>

<script>
function showGenerateModal() {
    document.getElementById('generateModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('generateModal').classList.add('hidden');
}

function confirmDelete(pesertaId) {
    if(confirm('Apakah Anda yakin ingin menghapus sertifikat ini?\n\nTindakan ini tidak dapat dibatalkan.')) {
        document.getElementById('deletePesertaId').value = pesertaId;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal on outside click
document.getElementById('generateModal').addEventListener('click', function(e) {
    if(e.target === this) {
        closeModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') {
        closeModal();
    }
});
</script>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>