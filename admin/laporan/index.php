<?php
// =============================================
// ADMIN LAPORAN - CLEAN INTERFACE (NO PERIOD FILTER)
// =============================================

require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/login/');
    exit;
}

// PARAMETER FILTER
$tab         = $_GET['tab'] ?? 'absensi';
$peserta_id  = $_GET['peserta_id'] ?? 'all';

// BUILD QUERY CONDITION (FILTER PESERTA SAJA)
$where_sql = "1=1";
$params = [];

if($peserta_id != 'all') {
    $where_sql .= " AND u.id = ?";
    $params[] = $peserta_id;
}

// EKSEKUSI DATA
$data = [];
try {
    if($tab == 'absensi') {
        $sql = "SELECT t.*, u.nama, u.instansi FROM absensi t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE $where_sql 
                ORDER BY t.tanggal DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    } 
    elseif($tab == 'kegiatan') {
        $sql = "SELECT t.*, u.nama, u.instansi FROM aktivitas t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE $where_sql 
                ORDER BY t.tanggal DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    } 
    elseif($tab == 'penilaian') {
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
        $sql = "SELECT t.*, u.nama, u.instansi FROM laporan_ringkasan t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE 1=1";
        $akhir_params = [];
        if($peserta_id != 'all') {
            $sql .= " AND t.peserta_id = ?";
            $akhir_params[] = $peserta_id;
        }
        $sql .= " ORDER BY t.id DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($akhir_params);
        $data = $stmt->fetchAll();
    }
    elseif($tab == 'sertifikat') {
        $sql = "SELECT t.*, u.nama, u.instansi FROM sertifikat t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE 1=1";
        $cert_params = [];
        if($peserta_id != 'all') {
            $sql .= " AND t.peserta_id = ?";
            $cert_params[] = $peserta_id;
        }
        $sql .= " ORDER BY t.id DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($cert_params);
        $data = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $error_msg = "Database Error: " . $e->getMessage();
}

// GET LIST PESERTA UNTUK FILTER
$all_peserta = db()->query("SELECT id, nama FROM users WHERE role='peserta' ORDER BY nama ASC")->fetchAll();

$page_title = 'Pusat Laporan';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Pusat Laporan</h1>
            <p class="text-gray-500">Rekapitulasi data peserta</p>
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
                'penilaian' => 'Penilaian',
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
        <form method="GET" class="flex gap-4 items-end">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-500 mb-1">Filter Berdasarkan Peserta</label>
                <select name="peserta_id" class="w-full border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="all">Semua Peserta</option>
                    <?php foreach($all_peserta as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $peserta_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="w-auto">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-blue-700">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
        
        <?php if($tab == 'absensi'): ?>
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

        <?php elseif($tab == 'kegiatan'): ?>
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

        <?php elseif($tab == 'penilaian'): ?>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($data as $d): ?>
            <div class="border rounded-2xl p-6 hover:shadow-lg transition-all bg-white">
                <div class="flex justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-lg"><?= htmlspecialchars($d['nama']) ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($d['instansi']) ?></p>
                    </div>
                    <div class="text-right">
                        <span class="block text-2xl font-bold text-blue-600"><?= number_format($d['nilai_rata_rata']??0, 1) ?></span>
                        <span class="text-xs text-gray-400">Rata-rata</span>
                    </div>
                </div>
                <div class="text-sm text-gray-600 border-t pt-2">
                    <p class="mb-1"><strong>Catatan:</strong></p>
                    <p><?= htmlspecialchars($d['catatan'] ?? 'Belum ada catatan') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php elseif($tab == 'akhir'): ?>
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
                        <td class="p-4 text-sm text-gray-600"><?= htmlspecialchars($d['ringkasan']) ?></td>
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
                            <a href="<?= BASE_URL . $d['file_path'] ?>" target="_blank" class="text-red-600 font-bold hover:underline"><i class="fas fa-file-pdf"></i> PDF</a>
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