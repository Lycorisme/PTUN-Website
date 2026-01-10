<?php
// =============================================
// ADMIN SERTIFIKAT - FIXED BULK DELETE & VALIDATION
// =============================================

require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/login/');
    exit;
}

// --- 1. HANDLE POST ACTIONS ---

// A. GENERATE SERTIFIKAT
if(isset($_POST['action']) && $_POST['action'] == 'generate') {
    try {
        $peserta_id = $_POST['peserta_id'];
        $penilaian = $_POST['penilaian_final'];
        $nomor = 'SERT/' . date('Y') . '/' . str_pad($peserta_id, 4, '0', STR_PAD_LEFT);
        $file_path = 'uploads/sertifikat/sertifikat_' . $peserta_id . '.pdf'; 
        
        // Cek apakah user sudah punya sertifikat (Double Protection)
        $check = db()->prepare("SELECT id FROM sertifikat WHERE peserta_id = ?");
        $check->execute([$peserta_id]);
        if($check->rowCount() > 0) {
            header('Location: index.php?msg=exists');
            exit;
        }

        $stmt = db()->prepare("INSERT INTO sertifikat (peserta_id, nomor_sertifikat, file_path, issued_date, penilaian_final, status) VALUES (?, ?, ?, CURDATE(), ?, 'tersedia')");
        $stmt->execute([$peserta_id, $nomor, $file_path, $penilaian]);
        
        // Notifikasi
        db()->prepare("INSERT INTO notifications (to_user_id, title, pesan, tipe) VALUES (?, 'Sertifikat Terbit', 'Sertifikat magang Anda telah terbit.', 'success')")->execute([$peserta_id]);

        header('Location: index.php?msg=generated');
        exit;
    } catch (Exception $e) {
        header('Location: index.php?msg=error');
        exit;
    }
}

// B. SINGLE DELETE
if(isset($_POST['action']) && $_POST['action'] == 'delete_single') {
    $id = $_POST['peserta_id']; // ID User/Peserta
    $stmt = db()->prepare("DELETE FROM sertifikat WHERE peserta_id = ?");
    $stmt->execute([$id]);
    header('Location: index.php?msg=deleted');
    exit;
}

// C. BULK DELETE (HAPUS BANYAK)
if(isset($_POST['action']) && $_POST['action'] == 'delete_bulk') {
    $ids_string = $_POST['bulk_ids']; // String "1,2,3"
    
    if(!empty($ids_string)) {
        $ids = explode(',', $ids_string);
        // Validasi array agar aman
        $ids = array_map('intval', $ids);
        
        // Buat placeholder (?,?,?)
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        $stmt = db()->prepare("DELETE FROM sertifikat WHERE peserta_id IN ($placeholders)");
        $stmt->execute($ids);
        
        header('Location: index.php?msg=bulk_deleted');
        exit;
    }
}

// --- 2. CONFIG PAGINATION & SEARCH ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Query Builder
$where = "WHERE 1=1";
$params = [];

if($search) {
    $where .= " AND (u.nama LIKE ? OR u.instansi LIKE ? OR s.nomor_sertifikat LIKE ?)";
    $params = array_fill(0, 3, "%$search%");
}

// Hitung Total
$stmt = db()->prepare("SELECT COUNT(*) as total FROM sertifikat s JOIN users u ON s.peserta_id = u.id $where");
$stmt->execute($params);
$total_data = $stmt->fetch()['total'];
$total_pages = ceil($total_data / $limit);

// Get Data Sertifikat
$sql = "SELECT s.*, u.nama, u.instansi, u.id as user_id 
        FROM sertifikat s 
        JOIN users u ON s.peserta_id = u.id 
        $where 
        ORDER BY s.created_at DESC 
        LIMIT $limit OFFSET $start";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$sertifikat_list = $stmt->fetchAll();

// GET AVAILABLE PESERTA (Filter: Yang BELUM punya sertifikat)
$peserta_list = db()->query("
    SELECT u.id, u.nama, u.instansi 
    FROM users u 
    WHERE u.role = 'peserta' 
    AND u.status = 'aktif'
    AND u.id NOT IN (SELECT peserta_id FROM sertifikat)
    ORDER BY u.nama
")->fetchAll();

// =============================================
// FUNGSI HITUNG NILAI AKHIR (SAMA DENGAN CETAK.PHP)
// =============================================
function hitungNilaiAkhir($peserta_id) {
    // Ambil settings bobot
    $bobot_hadir = intval(get_setting('sertifikat_bobot_hadir', 60));
    $bobot_laporan = intval(get_setting('sertifikat_bobot_laporan', 40));
    $total_hari = intval(get_setting('absensi_max_hari', 30));
    
    // VALIDASI: Pastikan total bobot = 100%
    if (($bobot_hadir + $bobot_laporan) != 100) {
        $bobot_hadir = 60;
        $bobot_laporan = 40;
    }
    
    // Ambil data penilaian
    $stmt = db()->prepare("SELECT disiplin, kerjasama, inisiatif, kerajinan, kualitas_kerja FROM penilaian WHERE peserta_id = ?");
    $stmt->execute([$peserta_id]);
    $penilaian = $stmt->fetch();
    
    // Ambil data kehadiran
    $stmt2 = db()->prepare("SELECT COUNT(*) as total FROM absensi WHERE peserta_id = ? AND status = 'hadir'");
    $stmt2->execute([$peserta_id]);
    $kehadiran = $stmt2->fetch();
    $total_hadir = intval($kehadiran['total'] ?? 0);
    
    // Hitung nilai kehadiran (skala 0-100)
    if ($total_hari > 0) {
        $nilai_kehadiran = ($total_hadir / $total_hari) * 100;
        $nilai_kehadiran = min($nilai_kehadiran, 100);
    } else {
        $nilai_kehadiran = 0;
    }
    
    // Hitung rata-rata kinerja (5 aspek)
    if ($penilaian) {
        $aspek = [
            floatval($penilaian['disiplin'] ?? 0),
            floatval($penilaian['kerjasama'] ?? 0),
            floatval($penilaian['inisiatif'] ?? 0),
            floatval($penilaian['kerajinan'] ?? 0),
            floatval($penilaian['kualitas_kerja'] ?? 0)
        ];
        $rata_kinerja = array_sum($aspek) / count($aspek);
    } else {
        $rata_kinerja = 0;
    }
    
    // Hitung nilai akhir
    $kontribusi_kinerja = $rata_kinerja * $bobot_laporan / 100;
    $kontribusi_hadir = $nilai_kehadiran * $bobot_hadir / 100;
    
    return $kontribusi_kinerja + $kontribusi_hadir;
}

// Fungsi predikat
function getPredikatAdmin($nilai) {
    if ($nilai >= 90) return 'A';
    if ($nilai >= 80) return 'B';
    if ($nilai >= 70) return 'C';
    if ($nilai >= 60) return 'D';
    return 'E';
}

$page_title = 'Kelola Sertifikat';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">Data Sertifikat</h2>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <button onclick="confirmBulkDelete()" id="btnBulkDelete" class="hidden bg-red-600 text-white px-4 py-2.5 rounded-xl font-bold text-sm shadow hover:bg-red-700 transition-all flex items-center whitespace-nowrap">
                <i class="fas fa-trash-alt mr-2"></i> Hapus Terpilih
            </button>

            <button onclick="showGenerateModal()" class="bg-orange-600 text-white px-4 py-2.5 rounded-xl font-bold text-sm shadow hover:bg-orange-700 transition-all flex items-center whitespace-nowrap">
                <i class="fas fa-plus-circle mr-2"></i> Buat Baru
            </button>

            <form method="GET" class="relative w-full md:w-64">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari sertifikat..." 
                       class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:outline-none transition-all shadow-sm">
                <button type="submit" class="absolute left-3 top-3.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 tracking-wider">
                        <th class="p-4 w-10 text-center">
                            <input type="checkbox" id="checkAll" class="w-4 h-4 text-orange-600 rounded border-gray-300 focus:ring-orange-500 cursor-pointer">
                        </th>
                        <th class="p-4 font-bold">Nomor</th>
                        <th class="p-4 font-bold">Peserta</th>
                        <th class="p-4 font-bold text-center">Nilai</th>
                        <th class="p-4 font-bold text-center">Tanggal</th>
                        <th class="p-4 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($sertifikat_list)): ?>
                        <tr>
                            <td colspan="6" class="p-12 text-center text-gray-500">
                                <i class="fas fa-certificate text-4xl mb-3 text-gray-300"></i>
                                <p>Belum ada sertifikat <?= $search ? 'yang cocok' : '' ?>.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($sertifikat_list as $s): ?>
                        <tr class="border-b hover:bg-orange-50/30 transition-colors">
                            <td class="p-4 text-center">
                                <input type="checkbox" name="ids[]" value="<?= $s['user_id'] ?>" class="row-checkbox w-4 h-4 text-orange-600 rounded border-gray-300 focus:ring-orange-500 cursor-pointer">
                            </td>
                            <td class="p-4">
                                <span class="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded border border-gray-200">
                                    <?= htmlspecialchars($s['nomor_sertifikat']) ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-gray-800"><?= htmlspecialchars($s['nama']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($s['instansi']) ?></p>
                            </td>
                            <td class="p-4 text-center">
                                <?php 
                                $nilai_hitung = hitungNilaiAkhir($s['user_id']);
                                $predikat = getPredikatAdmin($nilai_hitung);
                                ?>
                                <span class="font-bold text-green-600 bg-green-50 px-2 py-1 rounded">
                                    <?= number_format($nilai_hitung, 2) ?>
                                </span>
                                <span class="text-xs font-bold ml-1 px-1.5 py-0.5 rounded <?= $predikat == 'A' ? 'bg-blue-100 text-blue-700' : ($predikat == 'E' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') ?>">
                                    <?= $predikat ?>
                                </span>
                            </td>
                            <td class="p-4 text-center text-sm text-gray-600">
                                <?= date('d/m/Y', strtotime($s['issued_date'])) ?>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="cetak.php?id=<?= $s['user_id'] ?>" target="_blank" class="text-blue-600 hover:text-blue-800 bg-blue-50 p-2 rounded-lg transition-colors tooltip" title="Download PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <button onclick="confirmSingleDelete(<?= $s['user_id'] ?>)" class="text-red-600 hover:text-red-800 bg-red-50 p-2 rounded-lg transition-colors tooltip" title="Hapus">
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

        <?php if($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-center">
            <div class="flex space-x-1">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&q=<?= $search ?>" 
                       class="w-8 h-8 flex items-center justify-center rounded-lg font-bold text-sm transition-all <?= $page == $i ? 'bg-orange-600 text-white shadow' : 'text-gray-500 hover:bg-gray-200' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="generateModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-8 transform scale-100 transition-transform">
        <h3 class="text-2xl font-bold text-gray-900 mb-6">Generate Sertifikat</h3>
        
        <?php if(empty($peserta_list)): ?>
            <div class="bg-yellow-50 text-yellow-800 p-4 rounded-xl mb-4 border border-yellow-200">
                <i class="fas fa-check-circle mr-2"></i> Semua peserta aktif saat ini sudah memiliki sertifikat.
            </div>
            <button type="button" onclick="closeModal()" class="w-full bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200">Tutup</button>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="generate">
                <div class="space-y-4">
                    <div>
                        <label class="block font-bold text-sm text-gray-700 mb-1">Peserta (Belum Punya Sertifikat)</label>
                        <select name="peserta_id" required class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-orange-500 outline-none">
                            <option value="">-- Pilih Peserta --</option>
                            <?php foreach($peserta_list as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> - <?= htmlspecialchars($p['instansi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-sm text-gray-700 mb-1">Nilai Akhir (0-99)</label>
                        <input type="number" name="penilaian_final" required min="0" max="100" step="0.01" class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-orange-500 outline-none">
                    </div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button type="submit" class="flex-1 bg-orange-600 text-white py-3 rounded-xl font-bold hover:bg-orange-700 transition-all">Generate</button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all">Batal</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<form id="singleDeleteForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete_single">
    <input type="hidden" name="peserta_id" id="singleDeleteId">
</form>

<form id="bulkDeleteForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete_bulk">
    <input type="hidden" name="bulk_ids" id="bulkDeleteIdsInput">
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// 1. CHECKBOX LOGIC
const checkAll = document.getElementById('checkAll');
const checkboxes = document.querySelectorAll('.row-checkbox');
const btnBulkDelete = document.getElementById('btnBulkDelete');

function toggleButtons() {
    const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
    // Show button only if checkboxes are checked
    if(checkedCount > 0) {
        btnBulkDelete.classList.remove('hidden');
    } else {
        btnBulkDelete.classList.add('hidden');
    }
}

if(checkAll) {
    checkAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        toggleButtons();
    });
}

checkboxes.forEach(cb => {
    cb.addEventListener('change', toggleButtons);
});

// 2. ACTIONS
function confirmSingleDelete(id) {
    Swal.fire({
        title: 'Hapus Sertifikat?',
        text: 'Data akan dihapus permanen dan tidak bisa dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('singleDeleteId').value = id;
            document.getElementById('singleDeleteForm').submit();
        }
    });
}

function confirmBulkDelete() {
    const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    
    if(selected.length === 0) return;

    Swal.fire({
        title: `Hapus ${selected.length} Sertifikat?`,
        text: 'Data terpilih akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Ya, Hapus Semua!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('bulkDeleteIdsInput').value = selected.join(',');
            document.getElementById('bulkDeleteForm').submit();
        }
    });
}

// 3. MODAL LOGIC
function showGenerateModal() { document.getElementById('generateModal').classList.remove('hidden'); }
function closeModal() { document.getElementById('generateModal').classList.add('hidden'); }

document.getElementById('generateModal').addEventListener('click', function(e) {
    if(e.target === this) closeModal();
});

document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') closeModal();
});

// 4. NOTIFICATION
<?php if(isset($_GET['msg'])): ?>
    let msg = "<?= $_GET['msg'] ?>";
    let title = "Berhasil";
    let text = "Data berhasil diproses";
    let icon = "success";

    if(msg === 'exists') {
        title = "Gagal";
        text = "Peserta ini sudah memiliki sertifikat!";
        icon = "error";
    } else if (msg === 'deleted' || msg === 'bulk_deleted') {
        text = "Data sertifikat berhasil dihapus.";
    }

    Swal.fire({
        icon: icon,
        title: title,
        text: text,
        timer: 2000,
        showConfirmButton: false
    });
    window.history.replaceState(null, null, window.location.pathname);
<?php endif; ?>
</script>

<?php require_once '../includes/sidebar.php'; ?>