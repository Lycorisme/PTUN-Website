<?php
// =============================================
// ADMIN LAPORAN - REKAPITULASI (FIXED: NO CATATAN, PAGINATION, SEARCH)
// =============================================

require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/login/');
    exit;
}

// 1. PARAMETER FILTER & PAGINATION
$tab         = $_GET['tab'] ?? 'absensi';
$peserta_id  = $_GET['peserta_id'] ?? 'all';
$search      = isset($_GET['q']) ? trim($_GET['q']) : '';

// Pagination Config
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// HELPER: GET SETTINGS
function get_db_setting($key, $default) {
    global $pdo; 
    $conn = function_exists('db') ? db() : $GLOBALS['pdo'];
    
    $stmt = $conn->prepare("SELECT value FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetch();
    return $res ? $res['value'] : $default;
}

// AMBIL BOBOT NILAI
$bobot_hadir = (int)get_db_setting('sertifikat_bobot_hadir', 60);
$bobot_laporan = (int)get_db_setting('sertifikat_bobot_laporan', 40);

// 2. BUILD QUERY CONDITION
$where_sql = "1=1";
$params = [];

// Filter by Peserta ID
if($peserta_id != 'all') {
    $where_sql .= " AND u.id = ?";
    $params[] = $peserta_id;
}

// Filter by Search Query
if($search) {
    $where_sql .= " AND (u.nama LIKE ? OR u.instansi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// EKSEKUSI DATA
$data = [];
$total_pages = 1; // Default

try {
    // --- QUERY BUILDER BERDASARKAN TAB ---
    if($tab == 'absensi') {
        // Count Total
        $countSql = "SELECT COUNT(*) as total FROM absensi t JOIN users u ON t.peserta_id = u.id WHERE $where_sql";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total_data = $stmt->fetch()['total'];
        
        // Get Data
        $sql = "SELECT t.*, u.nama, u.instansi FROM absensi t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE $where_sql 
                ORDER BY t.tanggal DESC LIMIT $limit OFFSET $start";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    } 
    elseif($tab == 'kegiatan') {
        // Count Total
        $countSql = "SELECT COUNT(*) as total FROM aktivitas t JOIN users u ON t.peserta_id = u.id WHERE $where_sql";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total_data = $stmt->fetch()['total'];

        // Get Data
        $sql = "SELECT t.*, u.nama, u.instansi FROM aktivitas t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE $where_sql 
                ORDER BY t.tanggal DESC LIMIT $limit OFFSET $start";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    } 
    elseif($tab == 'penilaian') {
        // Count Total
        $countSql = "SELECT COUNT(*) as total FROM users u LEFT JOIN penilaian p ON u.id = p.peserta_id WHERE u.role='peserta' AND u.status='aktif' AND $where_sql";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total_data = $stmt->fetch()['total'];

        // Get Data (CATATAN SUDAH DIHAPUS DARI SELECT)
        $sql = "SELECT 
                    u.id as user_id, u.nama, u.instansi, 
                    p.disiplin, p.kerjasama, p.inisiatif, p.kerajinan, p.kualitas_kerja, p.nilai_rata_rata,
                    (SELECT COUNT(*) FROM absensi WHERE peserta_id = u.id AND status = 'hadir') as total_hadir,
                    (SELECT COUNT(*) FROM absensi WHERE peserta_id = u.id) as total_hari
                FROM users u 
                LEFT JOIN penilaian p ON u.id = p.peserta_id 
                WHERE u.role='peserta' AND u.status='aktif' AND $where_sql
                LIMIT $limit OFFSET $start";
        
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    }
    elseif($tab == 'akhir') {
        // Count Total
        $countSql = "SELECT COUNT(*) as total FROM laporan_ringkasan t JOIN users u ON t.peserta_id = u.id WHERE $where_sql";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total_data = $stmt->fetch()['total'];

        // Get Data
        $sql = "SELECT t.*, u.nama, u.instansi FROM laporan_ringkasan t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE $where_sql 
                ORDER BY t.id DESC LIMIT $limit OFFSET $start";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    }
    elseif($tab == 'sertifikat') {
        // Count Total
        $countSql = "SELECT COUNT(*) as total FROM sertifikat t JOIN users u ON t.peserta_id = u.id WHERE $where_sql";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total_data = $stmt->fetch()['total'];

        // Get Data
        $sql = "SELECT t.*, u.nama, u.instansi FROM sertifikat t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE $where_sql 
                ORDER BY t.id DESC LIMIT $limit OFFSET $start";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    }

    // Hitung Total Pages
    $total_pages = ceil($total_data / $limit);

} catch (PDOException $e) {
    $error_msg = "Database Error: " . $e->getMessage();
}

// GET LIST PESERTA UNTUK DROPDOWN FILTER
$all_peserta = db()->query("SELECT id, nama FROM users WHERE role='peserta' ORDER BY nama ASC")->fetchAll();

$page_title = 'Pusat Laporan';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Pusat Laporan</h1>
        </div>
        
        <a href="cetak.php?<?= http_build_query($_GET) ?>" target="_blank" 
           class="bg-red-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-red-700 hover:shadow-xl transition-all flex items-center">
            <i class="fas fa-file-pdf text-xl mr-2"></i>
            Unduh Laporan PDF
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
        <div class="flex w-full overflow-x-auto">
            <?php 
            $tabs = [
                'absensi' => 'Absensi',
                'kegiatan' => 'Kegiatan',
                'penilaian' => 'Hasil Penilaian',
                'akhir' => 'Laporan Akhir',
                'sertifikat' => 'Sertifikat'
            ];
            foreach($tabs as $k => $v): 
                $active = ($tab == $k) ? 'bg-blue-50 text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50';
            ?>
            <a href="?tab=<?= $k ?>" class="flex-1 text-center px-6 py-4 font-bold whitespace-nowrap transition-all <?= $active ?>">
                <?= $v ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 mb-8">
        <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            
            <div class="w-full md:w-1/3">
                <label class="block text-xs font-bold text-gray-500 mb-1">Filter Peserta</label>
                <select name="peserta_id" class="w-full border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="all">Semua Peserta</option>
                    <?php foreach($all_peserta as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $peserta_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="w-full md:flex-1">
                <label class="block text-xs font-bold text-gray-500 mb-1">Cari Data</label>
                <div class="relative">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama atau instansi..." 
                           class="w-full pl-10 pr-4 py-2 border rounded-lg text-sm focus:border-blue-500 outline-none">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
            </div>

            <div class="w-full md:w-auto">
                <button type="submit" class="w-full bg-blue-600 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-blue-700 transition-all">
                    Terapkan
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
        
        <?php if($tab == 'absensi'): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-4">Tanggal</th>
                        <th class="p-4">Nama Peserta</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if(empty($data)): ?>
                        <tr><td colspan="4" class="p-6 text-center text-gray-500">Tidak ada data.</td></tr>
                    <?php else: ?>
                        <?php foreach($data as $d): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4"><?= date('d/m/Y', strtotime($d['tanggal'])) ?></td>
                            <td class="p-4 font-bold"><?= htmlspecialchars($d['nama']) ?></td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded text-xs font-bold uppercase 
                                    <?= $d['status']=='hadir'?'bg-green-100 text-green-700':($d['status']=='izin'?'bg-yellow-100 text-yellow-700':'bg-red-100 text-red-700') ?>">
                                    <?= $d['status'] ?>
                                </span>
                            </td>
                            <td class="p-4 text-sm text-gray-600"><?= htmlspecialchars($d['catatan'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($tab == 'kegiatan'): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-4">Tanggal</th>
                        <th class="p-4">Nama Peserta</th>
                        <th class="p-4">Deskripsi Kegiatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if(empty($data)): ?>
                        <tr><td colspan="3" class="p-6 text-center text-gray-500">Tidak ada data.</td></tr>
                    <?php else: ?>
                        <?php foreach($data as $d): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4 text-sm">
                                <?= date('d/m/Y', strtotime($d['tanggal'])) ?><br>
                                <span class="text-xs text-gray-500"><?= $d['jam_mulai'] ?> - <?= $d['jam_selesai'] ?></span>
                            </td>
                            <td class="p-4 font-bold"><?= htmlspecialchars($d['nama']) ?></td>
                            <td class="p-4 text-sm text-gray-700"><?= nl2br(htmlspecialchars($d['deskripsi'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($tab == 'penilaian'): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-4">Nama Peserta</th>
                        <th class="p-4">Asal Instansi</th>
                        <th class="p-4 text-center">Nilai Pembimbing<br><span class="text-xs text-gray-500">(Bobot <?= $bobot_laporan ?>%)</span></th>
                        <th class="p-4 text-center">Kehadiran<br><span class="text-xs text-gray-500">(Bobot <?= $bobot_hadir ?>%)</span></th>
                        <th class="p-4 text-center">Nilai Akhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if(empty($data)): ?>
                        <tr><td colspan="5" class="p-6 text-center text-gray-500">Belum ada data penilaian.</td></tr>
                    <?php else: ?>
                        <?php foreach($data as $d): ?>
                        <?php 
                            // Kalkulasi Nilai Otomatis
                            $nilai_pembimbing = floatval($d['nilai_rata_rata'] ?? 0);
                            
                            // Hitung % Kehadiran
                            $total_hari = intval($d['total_hari'] ?? 0);
                            $hadir = intval($d['total_hadir'] ?? 0);
                            $persen_hadir = ($total_hari > 0) ? ($hadir / $total_hari) * 100 : 0;
                            
                            // Hitung Nilai Akhir
                            $nilai_akhir = ($nilai_pembimbing * $bobot_laporan / 100) + ($persen_hadir * $bobot_hadir / 100);
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4 font-bold"><?= htmlspecialchars($d['nama']) ?></td>
                            <td class="p-4 text-sm text-gray-600"><?= htmlspecialchars($d['instansi']) ?></td>
                            
                            <td class="p-4 text-center">
                                <span class="font-bold text-gray-700"><?= number_format($nilai_pembimbing, 1) ?></span>
                            </td>
                            
                            <td class="p-4 text-center">
                                <span class="block font-bold text-gray-700"><?= number_format($persen_hadir, 1) ?>%</span>
                                <span class="text-xs text-gray-500"><?= $hadir ?> dari <?= $total_hari ?> hari</span>
                            </td>
                            
                            <td class="p-4 text-center">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-lg font-bold text-lg">
                                    <?= number_format($nilai_akhir, 1) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($tab == 'akhir'): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-4">Nama Peserta</th>
                        <th class="p-4">Periode Magang</th>
                        <th class="p-4">Ringkasan Kegiatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if(empty($data)): ?>
                        <tr><td colspan="3" class="p-6 text-center text-gray-500">Belum ada laporan akhir.</td></tr>
                    <?php else: ?>
                        <?php foreach($data as $d): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4 font-bold"><?= htmlspecialchars($d['nama']) ?></td>
                            <td class="p-4 text-sm">
                                <?= date('d M Y', strtotime($d['periode_start'])) ?> s.d <?= date('d M Y', strtotime($d['periode_end'])) ?>
                            </td>
                            <td class="p-4 text-sm text-gray-600 max-w-xl"><?= nl2br(htmlspecialchars($d['ringkasan'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($tab == 'sertifikat'): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-4">No. Sertifikat</th>
                        <th class="p-4">Nama Peserta</th>
                        <th class="p-4">Nilai Akhir</th>
                        <th class="p-4">Terbit</th>
                        <th class="p-4">File</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if(empty($data)): ?>
                        <tr><td colspan="5" class="p-6 text-center text-gray-500">Belum ada sertifikat.</td></tr>
                    <?php else: ?>
                        <?php foreach($data as $d): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4 font-mono text-sm"><?= $d['nomor_sertifikat'] ?></td>
                            <td class="p-4 font-bold"><?= htmlspecialchars($d['nama']) ?></td>
                            <td class="p-4 font-bold text-blue-600"><?= $d['penilaian_final'] ?></td>
                            <td class="p-4 text-sm"><?= date('d/m/Y', strtotime($d['issued_date'])) ?></td>
                            <td class="p-4">
                                <a href="../sertifikat/cetak.php?id=<?= $d['peserta_id'] ?>" target="_blank" 
                                   class="text-red-600 font-bold hover:underline inline-flex items-center gap-1">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-center items-center">
            <div class="flex space-x-1">
                <?php if($page > 1): ?>
                    <a href="?tab=<?= $tab ?>&peserta_id=<?= $peserta_id ?>&q=<?= $search ?>&page=<?= $page-1 ?>" 
                       class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm">Prev</a>
                <?php endif; ?>
                
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?tab=<?= $tab ?>&peserta_id=<?= $peserta_id ?>&q=<?= $search ?>&page=<?= $i ?>" 
                       class="px-3 py-1 rounded-md border text-sm <?= $page == $i ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-100' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="?tab=<?= $tab ?>&peserta_id=<?= $peserta_id ?>&q=<?= $search ?>&page=<?= $page+1 ?>" 
                       class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/sidebar.php'; ?>