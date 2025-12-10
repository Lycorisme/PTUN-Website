<?php
// =============================================
// ADMIN LAPORAN - FIX ERROR & NULL SAFETY
// =============================================

require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/login/');
    exit;
}

// PARAMETER FILTER
$tab = $_GET['tab'] ?? 'absensi';
$peserta_id = $_GET['peserta_id'] ?? 'all';
$filter_type = $_GET['filter_type'] ?? 'bulanan'; // harian, bulanan, tahunan
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// BUILD QUERY CONDITION
$where_sql = "1=1";
$params = [];

if($peserta_id != 'all') {
    $where_sql .= " AND t.peserta_id = ?";
    $params[] = $peserta_id;
}

// Filter Waktu Logic
$time_sql = ""; 
if($filter_type == 'harian') {
    $time_sql = " AND DATE(t.tanggal) = ?";
    $params[] = $tanggal;
} elseif($filter_type == 'bulanan') {
    $time_sql = " AND MONTH(t.tanggal) = ? AND YEAR(t.tanggal) = ?";
    $params[] = $bulan;
    $params[] = $tahun;
} elseif($filter_type == 'tahunan') {
    $time_sql = " AND YEAR(t.tanggal) = ?";
    $params[] = $tahun;
}

// LOGIKA DATA PER TAB
$data = [];
try {
    if($tab == 'absensi') {
        // Fix: pastikan alias t digunakan konsisten
        $sql = "SELECT t.*, u.nama, u.instansi FROM absensi t JOIN users u ON t.peserta_id=u.id WHERE $where_sql $time_sql ORDER BY t.tanggal DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    } 
    elseif($tab == 'kegiatan') {
        // Fix: Aktivitas menggunakan t.tanggal
        $sql = "SELECT t.*, u.nama, u.instansi FROM aktivitas t JOIN users u ON t.peserta_id=u.id WHERE $where_sql $time_sql ORDER BY t.tanggal DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    } 
    elseif($tab == 'penilaian') {
        // Penilaian tidak butuh filter waktu yang kompleks
        $sql = "SELECT u.id as user_id, u.nama, u.instansi, p.* FROM users u 
                LEFT JOIN penilaian p ON u.id = p.peserta_id 
                WHERE u.role='peserta' AND u.status='aktif'";
        if($peserta_id != 'all') {
            $sql .= " AND u.id = " . intval($peserta_id);
        }
        $stmt = db()->query($sql);
        $data = $stmt->fetchAll();
    }
    elseif($tab == 'akhir') {
        // FIX FATAL ERROR: Ganti 'ORDER BY t.created_at' menjadi 'ORDER BY t.id' 
        // karena kolom created_at mungkin tidak ada di tabel laporan_ringkasan
        $sql = "SELECT t.*, u.nama, u.instansi FROM laporan_ringkasan t JOIN users u ON t.peserta_id=u.id WHERE 1=1";
        
        $akhir_params = [];
        if($peserta_id != 'all') {
            $sql .= " AND t.peserta_id = ?";
            $akhir_params[] = $peserta_id;
        }
        
        $sql .= " ORDER BY t.id DESC"; // Safe order
        
        $stmt = db()->prepare($sql);
        $stmt->execute($akhir_params);
        $data = $stmt->fetchAll();
    }
    elseif($tab == 'sertifikat') {
        // Fix: Gunakan t.id DESC untuk aman
        $sql = "SELECT t.*, u.nama, u.instansi FROM sertifikat t JOIN users u ON t.peserta_id=u.id WHERE 1=1 ORDER BY t.id DESC";
        $stmt = db()->query($sql);
        $data = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Tangkap error agar tidak fatal blank page
    $error_msg = "Database Error: " . $e->getMessage();
}

// GET DATA PESERTA UNTUK FILTER
$all_peserta = db()->query("SELECT id, nama FROM users WHERE role='peserta'")->fetchAll();

// HANDLE SAVE PENILAIAN
if(isset($_POST['save_nilai'])) {
    $pid = $_POST['peserta_id'];
    $disiplin = $_POST['disiplin'];
    $kerjasama = $_POST['kerjasama'];
    $inisiatif = $_POST['inisiatif'];
    $kerajinan = $_POST['kerajinan'];
    $kualitas = $_POST['kualitas'];
    $catatan = $_POST['catatan'];
    $rata = ($disiplin + $kerjasama + $inisiatif + $kerajinan + $kualitas) / 5;

    $stmt = db()->prepare("INSERT INTO penilaian (peserta_id, disiplin, kerjasama, inisiatif, kerajinan, kualitas_kerja, nilai_rata_rata, catatan) 
                          VALUES (?,?,?,?,?,?,?,?) 
                          ON DUPLICATE KEY UPDATE disiplin=?, kerjasama=?, inisiatif=?, kerajinan=?, kualitas_kerja=?, nilai_rata_rata=?, catatan=?");
    $stmt->execute([$pid, $disiplin, $kerjasama, $inisiatif, $kerajinan, $kualitas, $rata, $catatan, 
                    $disiplin, $kerjasama, $inisiatif, $kerajinan, $kualitas, $rata, $catatan]);
    header("Location: index.php?tab=penilaian&msg=saved");
    exit;
}

$page_title = 'Pusat Laporan';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <?php if(isset($error_msg)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-exclamation-triangle mr-2"></i><?= $error_msg ?>
    </div>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Pusat Laporan</h1>
            <p class="text-gray-500">Rekapitulasi data, aktivitas, dan penilaian peserta</p>
        </div>
        
        <a href="cetak.php?<?= http_build_query($_GET) ?>" target="_blank" 
           class="bg-red-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-red-700 hover:shadow-xl transition-all flex items-center">
            <i class="fas fa-file-pdf text-xl mr-2"></i>
            Unduh Laporan PDF
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
        <div class="flex overflow-x-auto">
            <?php 
            $tabs = [
                'absensi' => 'Absensi Harian',
                'kegiatan' => 'Kegiatan Harian',
                'penilaian' => 'Penilaian Kinerja',
                'akhir' => 'Laporan Akhir',
                'sertifikat' => 'Sertifikat'
            ];
            foreach($tabs as $k => $v): 
                $active = ($tab == $k) ? 'bg-blue-50 text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50';
            ?>
            <a href="?tab=<?= $k ?>" class="px-6 py-4 font-bold whitespace-nowrap transition-all <?= $active ?>">
                <?= $v ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if(in_array($tab, ['absensi', 'kegiatan'])): ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            
            <div class="col-span-1">
                <label class="block text-xs font-bold text-gray-500 mb-1">Peserta</label>
                <select name="peserta_id" class="w-full border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="all">Semua Peserta</option>
                    <?php foreach($all_peserta as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $peserta_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-span-1">
                <label class="block text-xs font-bold text-gray-500 mb-1">Periode</label>
                <select name="filter_type" class="w-full border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="harian" <?= $filter_type=='harian'?'selected':'' ?>>Harian</option>
                    <option value="bulanan" <?= $filter_type=='bulanan'?'selected':'' ?>>Bulanan</option>
                    <option value="tahunan" <?= $filter_type=='tahunan'?'selected':'' ?>>Tahunan</option>
                </select>
            </div>

            <div class="col-span-2">
                <label class="block text-xs font-bold text-gray-500 mb-1">Pilih Tanggal/Bulan</label>
                <div class="flex gap-2">
                    <?php if($filter_type == 'harian'): ?>
                        <input type="date" name="tanggal" value="<?= $tanggal ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <?php elseif($filter_type == 'bulanan'): ?>
                        <select name="bulan" class="w-1/2 border rounded-lg px-3 py-2 text-sm">
                            <?php for($i=1; $i<=12; $i++) echo "<option value='$i' ".($bulan==$i?'selected':'').">".date('F', mktime(0,0,0,$i,1))."</option>"; ?>
                        </select>
                        <select name="tahun" class="w-1/2 border rounded-lg px-3 py-2 text-sm">
                            <?php for($i=date('Y'); $i>=2023; $i--) echo "<option value='$i' ".($tahun==$i?'selected':'').">$i</option>"; ?>
                        </select>
                    <?php else: ?>
                        <select name="tahun" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <?php for($i=date('Y'); $i>=2023; $i--) echo "<option value='$i' ".($tahun==$i?'selected':'').">$i</option>"; ?>
                        </select>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-span-1">
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-all">
                    Filter Data
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
        
        <?php if($tab == 'absensi'): ?>
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="p-4 font-bold text-gray-600">Tanggal</th>
                    <th class="p-4 font-bold text-gray-600">Nama Peserta</th>
                    <th class="p-4 font-bold text-gray-600">Status</th>
                    <th class="p-4 font-bold text-gray-600">Waktu</th>
                    <th class="p-4 font-bold text-gray-600">Catatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if(empty($data)): ?>
                    <tr><td colspan="5" class="p-4 text-center text-gray-500">Tidak ada data ditemukan</td></tr>
                <?php else: ?>
                    <?php foreach($data as $d): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-4"><?= format_tanggal_id($d['tanggal']) ?></td>
                        <td class="p-4 font-semibold"><?= htmlspecialchars($d['nama'] ?? '') ?></td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded text-xs font-bold uppercase 
                                <?= $d['status']=='hadir'?'bg-green-100 text-green-700':($d['status']=='izin'?'bg-yellow-100 text-yellow-700':'bg-red-100 text-red-700') ?>">
                                <?= htmlspecialchars($d['status'] ?? '') ?>
                            </span>
                        </td>
                        <td class="p-4 text-sm text-gray-500">
                            <?= isset($d['created_at']) ? date('H:i', strtotime($d['created_at'])) : '-' ?>
                        </td>
                        <td class="p-4 text-gray-600 text-sm"><?= htmlspecialchars($d['catatan'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php elseif($tab == 'kegiatan'): ?>
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="p-4 font-bold text-gray-600">Tanggal</th>
                    <th class="p-4 font-bold text-gray-600">Nama</th>
                    <th class="p-4 font-bold text-gray-600">Aktivitas</th>
                    <th class="p-4 font-bold text-gray-600">Jam</th>
                    <th class="p-4 font-bold text-gray-600">Bukti</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if(empty($data)): ?>
                    <tr><td colspan="5" class="p-4 text-center text-gray-500">Tidak ada data ditemukan</td></tr>
                <?php else: ?>
                    <?php foreach($data as $d): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-4"><?= format_tanggal_id($d['tanggal']) ?></td>
                        <td class="p-4 font-semibold"><?= htmlspecialchars($d['nama'] ?? '') ?></td>
                        <td class="p-4 text-gray-700"><?= htmlspecialchars($d['deskripsi'] ?? '-') ?></td>
                        <td class="p-4 text-sm font-mono"><?= htmlspecialchars(($d['jam_mulai']??'') . ' - ' . ($d['jam_selesai']??'')) ?></td>
                        <td class="p-4">
                            <?php if(!empty($d['bukti_file'])): ?>
                                <a href="<?= BASE_URL . '/' . $d['bukti_file'] ?>" target="_blank" class="text-blue-600 hover:underline text-sm"><i class="fas fa-link"></i> Lihat</a>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php elseif($tab == 'penilaian'): ?>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($data as $d): ?>
            <div class="border rounded-2xl p-6 hover:shadow-lg transition-all <?= ($d['nilai_rata_rata'] ?? 0) > 0 ? 'bg-white' : 'bg-gray-50' ?>">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-bold text-lg"><?= htmlspecialchars($d['nama']) ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($d['instansi']) ?></p>
                    </div>
                    <?php if(($d['nilai_rata_rata'] ?? 0) > 0): ?>
                        <div class="bg-green-100 text-green-700 px-3 py-1 rounded-lg font-bold text-xl">
                            <?= $d['nilai_rata_rata'] ?>
                        </div>
                    <?php else: ?>
                        <span class="text-xs bg-gray-200 text-gray-500 px-2 py-1 rounded">Belum Dinilai</span>
                    <?php endif; ?>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="peserta_id" value="<?= $d['user_id'] ?>">
                    <div class="grid grid-cols-2 gap-2 mb-4 text-sm">
                        <div>
                            <label>Disiplin</label>
                            <input type="number" name="disiplin" value="<?= $d['disiplin'] ?? 0 ?>" min="0" max="100" class="w-full border rounded px-2 py-1">
                        </div>
                        <div>
                            <label>Kerjasama</label>
                            <input type="number" name="kerjasama" value="<?= $d['kerjasama'] ?? 0 ?>" min="0" max="100" class="w-full border rounded px-2 py-1">
                        </div>
                        <div>
                            <label>Inisiatif</label>
                            <input type="number" name="inisiatif" value="<?= $d['inisiatif'] ?? 0 ?>" min="0" max="100" class="w-full border rounded px-2 py-1">
                        </div>
                        <div>
                            <label>Kerajinan</label>
                            <input type="number" name="kerajinan" value="<?= $d['kerajinan'] ?? 0 ?>" min="0" max="100" class="w-full border rounded px-2 py-1">
                        </div>
                        <div class="col-span-2">
                            <label>Kualitas Kerja</label>
                            <input type="number" name="kualitas" value="<?= $d['kualitas_kerja'] ?? 0 ?>" min="0" max="100" class="w-full border rounded px-2 py-1">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Catatan Evaluasi</label>
                        <textarea name="catatan" rows="2" class="w-full border rounded px-2 py-1 text-sm"><?= htmlspecialchars($d['catatan'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="save_nilai" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 text-sm font-bold">
                        Simpan Nilai
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <?php elseif($tab == 'akhir'): ?>
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="p-4">Peserta</th>
                    <th class="p-4">Periode Magang</th>
                    <th class="p-4">Ringkasan</th>
                    <th class="p-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data)): ?>
                    <tr><td colspan="4" class="p-4 text-center text-gray-500">Belum ada laporan akhir disubmit</td></tr>
                <?php else: ?>
                    <?php foreach($data as $d): ?>
                    <tr class="hover:bg-gray-50 border-b">
                        <td class="p-4 font-bold"><?= htmlspecialchars($d['nama'] ?? '') ?></td>
                        <td class="p-4 text-sm">
                            <?php 
                                $start = !empty($d['periode_start']) ? date('d M Y', strtotime($d['periode_start'])) : '-';
                                $end = !empty($d['periode_end']) ? date('d M Y', strtotime($d['periode_end'])) : '-';
                                echo "$start - $end";
                            ?>
                        </td>
                        <td class="p-4 text-sm text-gray-600 max-w-md truncate"><?= htmlspecialchars($d['ringkasan'] ?? '') ?></td>
                        <td class="p-4">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">FINAL</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php elseif($tab == 'sertifikat'): ?>
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="p-4">No. Sertifikat</th>
                    <th class="p-4">Peserta</th>
                    <th class="p-4">Nilai Akhir</th>
                    <th class="p-4">Tgl Terbit</th>
                    <th class="p-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data)): ?>
                    <tr><td colspan="5" class="p-4 text-center text-gray-500">Belum ada sertifikat diterbitkan</td></tr>
                <?php else: ?>
                    <?php foreach($data as $d): ?>
                    <tr class="hover:bg-gray-50 border-b">
                        <td class="p-4 font-mono text-sm"><?= htmlspecialchars($d['nomor_sertifikat'] ?? '-') ?></td>
                        <td class="p-4 font-bold"><?= htmlspecialchars($d['nama'] ?? '') ?></td>
                        <td class="p-4 font-bold text-blue-600"><?= htmlspecialchars($d['penilaian_final'] ?? '0') ?></td>
                        <td class="p-4 text-sm">
                            <?= !empty($d['issued_date']) ? date('d/m/Y', strtotime($d['issued_date'])) : '-' ?>
                        </td>
                        <td class="p-4">
                            <a href="<?= BASE_URL . '/' . ($d['file_path'] ?? '#') ?>" target="_blank" class="text-red-600 hover:text-red-800 font-bold text-sm">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/sidebar.php'; ?>