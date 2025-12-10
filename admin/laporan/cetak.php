<?php
/**
 * ==============================================================================================
 * MODUL CETAK LAPORAN (PDF) - FINAL CLEAN (NO PERIOD FILTER)
 * ==============================================================================================
 */

// 1. SETUP ENVIRONMENT
// ----------------------------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 0); // Wajib 0 agar PDF tergenerate

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses Ditolak.");
}

$root = dirname(dirname(__DIR__));
require_once $root . '/config/database.php';
require_once $root . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 2. HELPER FUNCTIONS
// ----------------------------------------------------------------------------------------------
function get_setting_val($key, $default = '') {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res['value'] ?? $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Helper untuk mengambil gambar (Logo/TTD) agar muncul di PDF
function get_image_base64($filename, $type = 'logo') {
    if (empty($filename)) return '';
    
    // Tentukan path berdasarkan tipe
    $paths = [];
    if ($type == 'ttd') {
        $paths[] = "../../uploads/sertifikat/" . basename($filename);
        $paths[] = "../../uploads/sertifikat/" . $filename;
    } else {
        $paths[] = "../../" . $filename;
        $paths[] = "../../uploads/logos/" . $filename;
        $paths[] = "../../assets/img/" . $filename;
    }

    foreach ($paths as $path) {
        if (file_exists($path) && !is_dir($path)) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            return 'data:image/' . $ext . ';base64,' . base64_encode($data);
        }
    }
    return '';
}

function tgl_indo($tgl) {
    if (empty($tgl) || $tgl == '0000-00-00') return '-';
    $bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $d = date('d', strtotime($tgl));
    $m = date('m', strtotime($tgl));
    $y = date('Y', strtotime($tgl));
    return "$d {$bulan[$m]} $y";
}

// 3. PARAMETER (TANPA FILTER PERIODE)
// ----------------------------------------------------------------------------------------------
$tab        = $_GET['tab'] ?? 'absensi';
$peserta_id = $_GET['peserta_id'] ?? 'all';

// 4. BUILD QUERY
// ----------------------------------------------------------------------------------------------
$where = "1=1";
$params = [];

if ($peserta_id != 'all') {
    $where .= " AND u.id = ?";
    $params[] = $peserta_id;
}

// Data Container
$data = [];
$judul_laporan = "";
$subtitle = "Rekapitulasi Seluruh Data";

try {
    $pdo = db();

    if ($tab == 'absensi') {
        $judul_laporan = "LAPORAN REKAPITULASI ABSENSI";
        $sql = "SELECT t.*, u.nama, u.instansi 
                FROM absensi t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE $where 
                ORDER BY t.tanggal DESC, u.nama ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tab == 'kegiatan') {
        $judul_laporan = "LAPORAN REKAPITULASI KEGIATAN";
        $sql = "SELECT t.*, u.nama, u.instansi 
                FROM aktivitas t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE $where 
                ORDER BY t.tanggal DESC, u.nama ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tab == 'penilaian') {
        $judul_laporan = "LAPORAN PENILAIAN KINERJA";
        $sql = "SELECT u.nama, u.instansi, p.* FROM users u 
                LEFT JOIN penilaian p ON u.id = p.peserta_id 
                WHERE u.role='peserta' AND u.status='aktif'";
        if ($peserta_id != 'all') {
            $sql .= " AND u.id = " . intval($peserta_id);
        }
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tab == 'akhir') {
        $judul_laporan = "LAPORAN RINGKASAN AKHIR";
        $sql = "SELECT t.*, u.nama, u.instansi 
                FROM laporan_ringkasan t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE $where ORDER BY t.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tab == 'sertifikat') {
        $judul_laporan = "DATA SERTIFIKAT TERBIT";
        $sql = "SELECT t.*, u.nama, u.instansi 
                FROM sertifikat t 
                JOIN users u ON t.peserta_id = u.id 
                WHERE $where ORDER BY t.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// 5. SETTINGS KOP SURAT & TTD (GAMBAR DINAMIS)
// ----------------------------------------------------------------------------------------------
$instansi_nama   = get_setting_val('nama_panjang', 'Instansi XYZ');
$instansi_alamat = get_setting_val('alamat_lengkap', 'Alamat Belum Diatur');
$instansi_telp   = get_setting_val('no_telepon', '-');
$instansi_email  = get_setting_val('email_kontak', '-');
$instansi_kota   = get_setting_val('kota_instansi', 'Kota');

// Load Logo
$logo_src = get_image_base64(get_setting_val('logo_url'), 'logo');

// Load TTD Pejabat
$ttd_nama    = get_setting_val('kepala_nama', '....................');
$ttd_nip     = get_setting_val('kepala_nip', '');
$ttd_jabatan = get_setting_val('kepala_jabatan', 'Pimpinan');
$ttd_img_src = get_image_base64(get_setting_val('ttd_img_kepala'), 'ttd');

// 6. RENDER PDF HTML
// ----------------------------------------------------------------------------------------------
if (ob_get_length()) ob_clean();
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $judul_laporan ?></title>
    <style>
        @page { margin: 2cm; size: A4 portrait; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.3; }
        
        /* KOP SURAT DESIGN */
        .header { width: 100%; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 25px; }
        .header table { width: 100%; }
        .header td { vertical-align: middle; }
        
        .logo-img { width: 80px; height: auto; }
        
        .instansi-name { font-size: 14pt; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .instansi-addr { font-size: 10pt; }
        
        /* JUDUL */
        .judul { text-align: center; font-weight: bold; font-size: 12pt; text-transform: uppercase; margin-bottom: 5px; }
        .subjudul { text-align: center; font-size: 11pt; margin-bottom: 20px; color: #333; }
        
        /* TABLE DESIGN */
        .data-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
        .data-table th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
        
        /* TTD DESIGN */
        .ttd-wrapper { width: 100%; margin-top: 40px; }
        .ttd-box { float: right; width: 40%; text-align: center; }
        
        .ttd-img { height: 70px; display: block; margin: 0 auto; position: relative; z-index: 1; }
        .ttd-spacer { height: 70px; }
        
        .ttd-name { font-weight: bold; text-decoration: underline; margin-top: 5px; position: relative; z-index: 2; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td width="15%" align="center">
                    <?php if($logo_src): ?>
                        <img src="<?= $logo_src ?>" class="logo-img">
                    <?php endif; ?>
                </td>
                <td align="center">
                    <div class="instansi-name"><?= $instansi_nama ?></div>
                    <div class="instansi-addr">
                        <?= $instansi_alamat ?><br>
                        Telp: <?= $instansi_telp ?> | Email: <?= $instansi_email ?>
                    </div>
                </td>
                <td width="15%"></td>
            </tr>
        </table>
    </div>

    <div class="judul"><?= $judul_laporan ?></div>
    <div class="subjudul"><?= $subtitle ?></div>

    <table class="data-table">
        <thead>
            <?php if($tab == 'absensi'): ?>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Tanggal</th>
                <th>Nama Peserta</th>
                <th width="15%">Status</th>
                <th>Keterangan</th>
            </tr>
            <?php elseif($tab == 'kegiatan'): ?>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Tanggal / Jam</th>
                <th width="25%">Nama Peserta</th>
                <th>Deskripsi Kegiatan</th>
            </tr>
            <?php elseif($tab == 'penilaian'): ?>
            <tr>
                <th width="5%">No</th>
                <th>Nama Peserta</th>
                <th>Asal Instansi</th>
                <th width="15%">Nilai Akhir</th>
                <th width="30%">Catatan</th>
            </tr>
            <?php elseif($tab == 'akhir'): ?>
            <tr>
                <th width="5%">No</th>
                <th>Nama Peserta</th>
                <th width="30%">Periode Magang</th>
                <th>Ringkasan</th>
            </tr>
            <?php elseif($tab == 'sertifikat'): ?>
            <tr>
                <th width="5%">No</th>
                <th>No. Sertifikat</th>
                <th>Nama Peserta</th>
                <th>Nilai</th>
                <th>Tanggal Terbit</th>
            </tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php if(empty($data)): ?>
            <tr>
                <td colspan="5" align="center" style="padding: 20px;">Data tidak ditemukan.</td>
            </tr>
            <?php else: ?>
                <?php $no = 1; foreach($data as $row): ?>
                <tr>
                    <td align="center"><?= $no++ ?></td>
                    
                    <?php if($tab == 'absensi'): ?>
                        <td align="center"><?= tgl_indo($row['tanggal']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama']) ?></strong><br>
                            <small><?= htmlspecialchars($row['instansi']) ?></small>
                        </td>
                        <td align="center" style="text-transform: uppercase;">
                            <?= htmlspecialchars($row['status']) ?>
                        </td>
                        <td><?= htmlspecialchars($row['catatan'] ?? '-') ?></td>

                    <?php elseif($tab == 'kegiatan'): ?>
                        <td align="center">
                            <?= tgl_indo($row['tanggal']) ?><br>
                            <small><?= $row['jam_mulai'] ?> - <?= $row['jam_selesai'] ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['deskripsi'])) ?></td>

                    <?php elseif($tab == 'penilaian'): ?>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['instansi']) ?></td>
                        <td align="center"><strong><?= number_format($row['nilai_rata_rata'] ?? 0, 2) ?></strong></td>
                        <td><?= htmlspecialchars($row['catatan'] ?? '-') ?></td>

                    <?php elseif($tab == 'akhir'): ?>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td align="center">
                            <?= tgl_indo($row['periode_start']) ?> s.d<br>
                            <?= tgl_indo($row['periode_end']) ?>
                        </td>
                        <td><?= htmlspecialchars($row['ringkasan'] ?? '-') ?></td>

                    <?php elseif($tab == 'sertifikat'): ?>
                        <td><?= htmlspecialchars($row['nomor_sertifikat'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td align="center"><?= htmlspecialchars($row['penilaian_final'] ?? '0') ?></td>
                        <td align="center"><?= tgl_indo($row['issued_date']) ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="ttd-wrapper">
        <div class="ttd-box">
            <div><?= $instansi_kota ?>, <?= tgl_indo(date('Y-m-d')) ?></div>
            <div style="margin-bottom: 10px;">Mengetahui,<br><?= $ttd_jabatan ?></div>
            
            <?php if($ttd_img_src): ?>
                <img src="<?= $ttd_img_src ?>" class="ttd-img">
            <?php else: ?>
                <div class="ttd-spacer"></div>
            <?php endif; ?>
            
            <div class="ttd-name"><?= $ttd_nama ?></div>
            <?php if(!empty($ttd_nip)): ?>
                <div>NIP. <?= $ttd_nip ?></div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
<?php
// OUTPUT PDF
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Times');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Laporan_{$tab}_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);
?>