<?php
// =============================================
// ADMIN PENILAIAN - TABLE VIEW WITH MODAL
// =============================================

require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/login/');
    exit;
}

// HANDLE SAVE NILAI
if(isset($_POST['save_nilai'])) {
    try {
        $pid = $_POST['peserta_id'];
        $disiplin = (int)$_POST['disiplin'];
        $kerjasama = (int)$_POST['kerjasama'];
        $inisiatif = (int)$_POST['inisiatif'];
        $kerajinan = (int)$_POST['kerajinan'];
        $kualitas = (int)$_POST['kualitas'];
        $catatan = $_POST['catatan'];
        
        $rata = ($disiplin + $kerjasama + $inisiatif + $kerajinan + $kualitas) / 5;

        $stmt = db()->prepare("INSERT INTO penilaian (peserta_id, disiplin, kerjasama, inisiatif, kerajinan, kualitas_kerja, nilai_rata_rata, catatan) 
                              VALUES (?,?,?,?,?,?,?,?) 
                              ON DUPLICATE KEY UPDATE disiplin=?, kerjasama=?, inisiatif=?, kerajinan=?, kualitas_kerja=?, nilai_rata_rata=?, catatan=?");
        
        $stmt->execute([$pid, $disiplin, $kerjasama, $inisiatif, $kerajinan, $kualitas, $rata, $catatan, 
                        $disiplin, $kerjasama, $inisiatif, $kerajinan, $kualitas, $rata, $catatan]);
        
        // Update sertifikat jika ada
        db()->prepare("UPDATE sertifikat SET penilaian_final = ? WHERE peserta_id = ?")->execute([$rata, $pid]);

        header("Location: index.php?msg=saved");
        exit;
    } catch (Exception $e) {
        $error_msg = "Gagal menyimpan: " . $e->getMessage();
    }
}

// PAGINATION & SEARCH
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = "WHERE u.role='peserta' AND u.status='aktif'";
$params = [];

if($search) {
    $where .= " AND (u.nama LIKE ? OR u.instansi LIKE ?)";
    $params = array_fill(0, 2, "%$search%");
}

// Count Total
$stmt = db()->prepare("SELECT COUNT(*) as total FROM users u $where");
$stmt->execute($params);
$total_data = $stmt->fetch()['total'];
$total_pages = ceil($total_data / $limit);

// Get Data
$sql = "SELECT u.id as user_id, u.nama, u.instansi, u.jurusan, p.* FROM users u 
        LEFT JOIN penilaian p ON u.id = p.peserta_id 
        $where 
        ORDER BY u.nama ASC 
        LIMIT $limit OFFSET $start";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$peserta_data = $stmt->fetchAll();

$page_title = 'Input Penilaian';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm flex items-center">
        <i class="fas fa-check-circle text-xl mr-3"></i>
        <span class="font-semibold">Data penilaian berhasil disimpan!</span>
    </div>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Penilaian Kinerja</h1>
        </div>
        <form method="GET" class="relative w-full md:w-72">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari peserta..." 
                   class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:outline-none transition-all shadow-sm">
            <button type="submit" class="absolute left-3 top-3.5 text-gray-400 hover:text-teal-600">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 tracking-wider">
                        <th class="p-4 font-bold text-center w-16">No</th>
                        <th class="p-4 font-bold">Nama Peserta</th>
                        <th class="p-4 font-bold">Instansi</th>
                        <th class="p-4 font-bold text-center">Status Nilai</th>
                        <th class="p-4 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if(empty($peserta_data)): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-500">Data tidak ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($peserta_data as $i => $d): ?>
                        <?php $sudah_dinilai = ($d['nilai_rata_rata'] ?? 0) > 0; ?>
                        <tr class="hover:bg-teal-50/30 transition-colors">
                            <td class="p-4 text-center text-gray-500"><?= $start + $i + 1 ?></td>
                            <td class="p-4">
                                <p class="font-bold text-gray-800"><?= htmlspecialchars($d['nama']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($d['jurusan']) ?></p>
                            </td>
                            <td class="p-4 text-sm text-gray-600"><?= htmlspecialchars($d['instansi']) ?></td>
                            <td class="p-4 text-center">
                                <?php if($sudah_dinilai): ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                        <i class="fas fa-check-circle"></i> Selesai (<?= number_format($d['nilai_rata_rata'], 1) ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                        <i class="fas fa-clock"></i> Belum
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <button onclick='openModal(<?= json_encode($d) ?>)' 
                                        class="px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center gap-2 mx-auto
                                        <?= $sudah_dinilai ? "bg-white text-teal-600 border border-teal-200 hover:bg-teal-50" : "bg-teal-600 text-white hover:bg-teal-700" ?>">
                                    <i class="fas fa-edit"></i> <?= $sudah_dinilai ? 'Edit Nilai' : 'Input Nilai' ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-center">
            <div class="flex space-x-1">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&q=<?= $search ?>" 
                       class="w-8 h-8 flex items-center justify-center rounded-lg font-bold text-sm transition-all <?= $page == $i ? 'bg-teal-600 text-white shadow' : 'text-gray-500 hover:bg-gray-200' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="nilaiModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-8 transform scale-100 transition-transform">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Form Penilaian</h3>
                <p class="text-sm text-gray-500" id="modalNamaPeserta">Nama Peserta</p>
            </div>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="peserta_id" id="modalPesertaId">
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Disiplin (0-100)</label>
                    <input type="number" name="disiplin" id="inputDisiplin" min="0" max="100" required class="w-full border rounded-xl px-3 py-2 focus:border-teal-500 outline-none text-center font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Kerjasama (0-100)</label>
                    <input type="number" name="kerjasama" id="inputKerjasama" min="0" max="100" required class="w-full border rounded-xl px-3 py-2 focus:border-teal-500 outline-none text-center font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Inisiatif (0-100)</label>
                    <input type="number" name="inisiatif" id="inputInisiatif" min="0" max="100" required class="w-full border rounded-xl px-3 py-2 focus:border-teal-500 outline-none text-center font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Kerajinan (0-100)</label>
                    <input type="number" name="kerajinan" id="inputKerajinan" min="0" max="100" required class="w-full border rounded-xl px-3 py-2 focus:border-teal-500 outline-none text-center font-bold">
                </div>
                <div class="col-span-2 md:col-span-2">
                    <label class="block text-xs font-bold text-gray-600 mb-1">Kualitas Kerja (0-100)</label>
                    <input type="number" name="kualitas" id="inputKualitas" min="0" max="100" required class="w-full border rounded-xl px-3 py-2 focus:border-teal-500 outline-none text-center font-bold">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Catatan Evaluasi</label>
                <textarea name="catatan" id="inputCatatan" rows="3" placeholder="Tuliskan evaluasi kinerja..." class="w-full border rounded-xl px-4 py-3 text-sm focus:border-teal-500 outline-none"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all">Batal</button>
                <button type="submit" name="save_nilai" class="flex-1 bg-teal-600 text-white py-3 rounded-xl font-bold hover:bg-teal-700 transition-all shadow-lg">Simpan Penilaian</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(data) {
    document.getElementById('modalPesertaId').value = data.user_id;
    document.getElementById('modalNamaPeserta').innerText = data.nama + ' - ' + data.instansi;
    
    // Set Values (Default 0 if null)
    document.getElementById('inputDisiplin').value = data.disiplin || 0;
    document.getElementById('inputKerjasama').value = data.kerjasama || 0;
    document.getElementById('inputInisiatif').value = data.inisiatif || 0;
    document.getElementById('inputKerajinan').value = data.kerajinan || 0;
    document.getElementById('inputKualitas').value = data.kualitas_kerja || 0;
    document.getElementById('inputCatatan').value = data.catatan || '';
    
    document.getElementById('nilaiModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('nilaiModal').classList.add('hidden');
}

// Close on ESC
document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') closeModal();
});
</script>

<?php require_once '../includes/sidebar.php'; ?>