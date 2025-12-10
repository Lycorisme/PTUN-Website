<?php
// =============================================
// PDF GENERATOR - DOMPDF
// =============================================

require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Cek Login
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') exit('Access Denied');

// Parameter Filter
$tab = $_GET['tab'] ?? 'absensi';
$peserta_id = $_GET['peserta_id'] ?? 'all';
$filter_type = $_GET['filter_type'] ?? 'bulanan';
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// Helper Judul Periode
$periode_text = "";
if($filter_type == 'harian') $periode_text = "Tanggal: " . format_tanggal_id($tanggal);
elseif($filter_type == 'bulanan') $periode_text = "Bulan: $bulan / Tahun: $tahun";
else $periode_text = "Tahun: $tahun";

// Query Builder (Mirip index.php tapi disederhanakan untuk output)
$where_sql = "1=1";
if($peserta_id != 'all') $where_sql .= " AND t.peserta_id = $peserta_id";

// DATE FILTER SQL
$time_sql = "";
if($tab == 'absensi' || $tab == 'kegiatan') {
    if($filter_type == 'harian') $time_sql = " AND DATE(t.tanggal) = '$tanggal'";
    elseif($filter_type == 'bulanan') $time_sql = " AND MONTH(t.tanggal) = '$bulan' AND YEAR(t.tanggal) = '$tahun'";
    elseif($filter_type == 'tahunan') $time_sql = " AND YEAR(t.tanggal) = '$tahun'";
}

// FETCH DATA
$data = [];
$title = "LAPORAN";
$columns = [];

if($tab == 'absensi') {
    $title = "LAPORAN ABSENSI HARIAN";
    $sql = "SELECT t.*, u.nama, u.instansi FROM absensi t JOIN users u ON t.peserta_id=u.id WHERE $where_sql $time_sql ORDER BY t.tanggal ASC";
    $data = db()->query($sql)->fetchAll();
    $columns = ['Tanggal', 'Nama', 'Status', 'Catatan'];
} 
elseif($tab == 'kegiatan') {
    $title = "LAPORAN KEGIATAN HARIAN (AKTIVITAS)";
    $sql = "SELECT t.*, u.nama FROM aktivitas t JOIN users u ON t.peserta_id=u.id WHERE $where_sql $time_sql ORDER BY t.tanggal ASC";
    $data = db()->query($sql)->fetchAll();
    $columns = ['Tanggal', 'Nama', 'Jam', 'Deskripsi Aktivitas'];
}
elseif($tab == 'penilaian') {
    $title = "LAPORAN PENILAIAN KINERJA";
    $sql = "SELECT u.nama, u.instansi, p.* FROM users u JOIN penilaian p ON u.id = p.peserta_id WHERE 1=1 " . ($peserta_id != 'all' ? "AND u.id=$peserta_id" : "");
    $data = db()->query($sql)->fetchAll();
    $columns = ['Nama Peserta', 'Instansi', 'Rata-rata', 'Catatan'];
}

// START HTML BUFFER
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid black; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .meta { margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= get_site_name() ?></h1>
        <p><?= get_setting('alamat_lengkap') ?></p>
        <p>Telp: <?= get_setting('no_telepon') ?> | Email: <?= get_setting('email_kontak') ?></p>
    </div>

    <div class="meta">
        <strong>Laporan:</strong> <?= $title ?><br>
        <strong>Periode:</strong> <?= $periode_text ?><br>
        <strong>Dicetak Oleh:</strong> Administrator<br>
        <strong>Tanggal Cetak:</strong> <?= date('d F Y') ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <?php foreach($columns as $col): ?>
                    <th><?= $col ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach($data as $row): ?>
            <tr>
                <td><?= $no++ ?></td>
                
                <?php if($tab == 'absensi'): ?>
                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td><?= $row['nama'] ?></td>
                    <td style="text-transform:uppercase"><?= $row['status'] ?></td>
                    <td><?= $row['catatan'] ?></td>
                
                <?php elseif($tab == 'kegiatan'): ?>
                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td><?= $row['nama'] ?></td>
                    <td><?= $row['jam_mulai'] ?> - <?= $row['jam_selesai'] ?></td>
                    <td><?= $row['deskripsi'] ?></td>

                <?php elseif($tab == 'penilaian'): ?>
                    <td><?= $row['nama'] ?></td>
                    <td><?= $row['instansi'] ?></td>
                    <td><strong><?= $row['nilai_rata_rata'] ?></strong></td>
                    <td><?= $row['catatan'] ?></td>
                <?php endif; ?>

            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: right;">
        <p>Banjarmasin, <?= date('d F Y') ?></p>
        <br><br><br>
        <p><strong>( _______________________ )</strong></p>
        <p>Administrator</p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// DOMPDF SETUP
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Laporan_" . $tab . "_" . date('YmdHis') . ".pdf", ["Attachment" => false]);
?>