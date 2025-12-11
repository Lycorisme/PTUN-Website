<?php
/**
 * ==============================================================================================
 * MODUL PENCETAKAN SERTIFIKAT - FINAL WITH FULL TRANSCRIPT & ATTENDANCE
 * ==============================================================================================
 */

// 1. SETUP ENVIRONMENT
// ----------------------------------------------------------------------------------------------
$root = dirname(dirname(__DIR__));
require_once $root . '/config/database.php';
require_once $root . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

error_reporting(E_ALL);
ini_set('display_errors', 0);

// 2. CLASS GENERATOR
// ----------------------------------------------------------------------------------------------
class CertificateGenerator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Ambil Setting & File TTD
    private function getSettings() {
        $defaults = [
            'nama_panjang'   => 'PENGADILAN TATA USAHA NEGARA',
            'alamat_lengkap' => 'Jl. H. Hasan Basri No. 123, Banjarmasin',
            'kota_instansi'  => 'Banjarmasin',
            'logo_url'       => 'assets/img/logo-ptun.png',
            'kepala_nama'    => '..........................',
            'kepala_nip'     => '',
            'kepala_jabatan' => 'Ketua Pengadilan',
            'pembimbing_nama'=> '..........................',
            'pembimbing_nip' => '',
            'ttd_img_kepala' => '', 
            'ttd_img_pembimbing' => '',
            // Default Bobot
            'sertifikat_bobot_hadir' => 60,
            'sertifikat_bobot_laporan' => 40
        ];
        
        try {
            $stmt = $this->pdo->prepare("SELECT `key`, `value` FROM settings");
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['value'])) {
                    $defaults[$row['key']] = $row['value'];
                }
            }
        } catch (Exception $e) { }
        
        return $defaults;
    }

    // Ambil Data Peserta Lengkap (Termasuk Count Absensi)
    public function getAllData($peserta_id) {
        $sql = "SELECT 
                    u.nama, u.instansi, u.jurusan, 
                    s.nomor_sertifikat, s.issued_date,
                    p.disiplin, p.kerjasama, p.inisiatif, p.kerajinan, p.kualitas_kerja, 
                    p.nilai_rata_rata,
                    (SELECT COUNT(*) FROM absensi WHERE peserta_id = u.id AND status = 'hadir') as total_hadir,
                    (SELECT COUNT(*) FROM absensi WHERE peserta_id = u.id) as total_hari
                FROM users u
                LEFT JOIN sertifikat s ON u.id = s.peserta_id
                LEFT JOIN penilaian p ON u.id = p.peserta_id
                WHERE u.id = ?";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$peserta_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }
    
    // Helper Tanggal
    private function formatTanggal($tgl) {
        if (empty($tgl) || $tgl == '0000-00-00') return date('d F Y');
        $bulanIndo = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
        try {
            $d = new DateTime($tgl);
            return $d->format('d') . ' ' . $bulanIndo[$d->format('m')] . ' ' . $d->format('Y');
        } catch (Exception $e) { return $tgl; }
    }

    // Helper Predikat
    private function getPredikat($nilai) {
        $n = floatval($nilai);
        if ($n >= 90) return ['A', 'Sangat Memuaskan'];
        if ($n >= 80) return ['B', 'Memuaskan'];
        if ($n >= 70) return ['C', 'Cukup'];
        if ($n >= 60) return ['D', 'Kurang'];
        return ['E', 'Tidak Lulus'];
    }

    // Load Gambar (Logo / TTD) ke Base64
    private function getImageBase64($filename, $type = 'logo') {
        if (empty($filename)) return '';
        
        $paths = [];
        if ($type == 'ttd') {
            $paths[] = "../../uploads/sertifikat/" . basename($filename);
            $paths[] = "../../uploads/sertifikat/" . $filename;
            $paths[] = "../../uploads/" . $filename;
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

    // --- GENERATE PDF ---
    public function generate($peserta_id) {
        $data = $this->getAllData($peserta_id);
        if (!$data) throw new Exception("Data peserta tidak ditemukan");
        
        $settings = $this->getSettings();
        
        // --- 1. PREPARE DATA ---
        $nama           = htmlspecialchars($data['nama'] ?? 'Peserta');
        $instansi_asal  = htmlspecialchars($data['instansi'] ?? '-');
        $nomor          = htmlspecialchars($data['nomor_sertifikat'] ?? 'DRAFT/'.date('Y'));
        
        $tgl_issue      = $data['issued_date'] ?? date('Y-m-d');
        if($tgl_issue == '0000-00-00') $tgl_issue = date('Y-m-d');
        $tgl_terbit_indo = $this->formatTanggal($tgl_issue);
        
        // Data Pejabat
        $instansi_nama   = htmlspecialchars($settings['nama_panjang']);
        $kota            = htmlspecialchars($settings['kota_instansi']);
        $kpl_jabatan     = htmlspecialchars($settings['kepala_jabatan']);
        $kpl_nama        = htmlspecialchars($settings['kepala_nama']);
        $kpl_nip         = htmlspecialchars($settings['kepala_nip']);
        $pem_nama        = htmlspecialchars($settings['pembimbing_nama'] ?? '');
        $pem_nip         = htmlspecialchars($settings['pembimbing_nip'] ?? '');

        // Images
        $logo_src        = $this->getImageBase64($settings['logo_url'], 'logo');
        $logo_html       = $logo_src ? "<img src='{$logo_src}' class='logo'>" : "";
        
        $ttd_kepala_src  = $this->getImageBase64($settings['ttd_img_kepala'], 'ttd');
        $ttd_pem_src     = $this->getImageBase64($settings['ttd_img_pembimbing'], 'ttd');
        
        $html_ttd_kepala = $ttd_kepala_src ? "<img src='{$ttd_kepala_src}' class='ttd-image'>" : "<div class='ttd-spacer'></div>";
        $html_ttd_pem    = $ttd_pem_src ? "<img src='{$ttd_pem_src}' class='ttd-image'>" : "<div class='ttd-spacer'></div>";

        // --- 2. KALKULASI NILAI (AKUMULASI) ---
        $bobot_hadir   = (int)$settings['sertifikat_bobot_hadir'];
        $bobot_laporan = (int)$settings['sertifikat_bobot_laporan'];

        // Nilai Kinerja (5 Aspek)
        $aspek_kinerja = [
            'Kedisiplinan'   => floatval($data['disiplin'] ?? 0),
            'Kerjasama'      => floatval($data['kerjasama'] ?? 0),
            'Inisiatif'      => floatval($data['inisiatif'] ?? 0),
            'Kerajinan'      => floatval($data['kerajinan'] ?? 0),
            'Kualitas Kerja' => floatval($data['kualitas_kerja'] ?? 0)
        ];
        $rata_kinerja = array_sum($aspek_kinerja) / count($aspek_kinerja);

        // Nilai Absensi (Aspek ke-6)
        $total_hari  = intval($data['total_hari'] ?? 0);
        $total_hadir = intval($data['total_hadir'] ?? 0);
        $nilai_absensi = ($total_hari > 0) ? ($total_hadir / $total_hari) * 100 : 0;

        // Gabungkan untuk Tabel Transkrip
        $all_grades = $aspek_kinerja;
        $all_grades['Kehadiran / Absensi'] = $nilai_absensi;

        // Nilai Akhir (Weighted)
        $nilai_akhir = ($rata_kinerja * $bobot_laporan / 100) + ($nilai_absensi * $bobot_hadir / 100);

        // --- 3. HTML TEMPLATE ---
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Sertifikat $nama</title>
            <style>
                @page { margin: 0px; size: A4 landscape; }
                body { margin: 0px; padding: 0px; font-family: 'Times New Roman', serif; background: transparent; }
                
                /* BORDER */
                .frame-border {
                    position: fixed; top: 15px; left: 15px; right: 15px; bottom: 15px;
                    border: 5px double #1a4d80; z-index: -999;
                }
                .frame-inner {
                    position: fixed; top: 25px; left: 25px; right: 25px; bottom: 25px;
                    border: 1px solid #c5a059; z-index: -999;
                }

                /* CONTENT */
                .page-content { width: 85%; margin: 0 auto; text-align: center; padding-top: 50px; }
                .page-break { page-break-before: always; }

                /* TYPOGRAPHY */
                .logo { height: 85px; margin-bottom: 10px; }
                .header-instansi { font-size: 24px; font-weight: bold; text-transform: uppercase; color: #1a4d80; letter-spacing: 2px; margin-bottom: 5px; }
                .separator { width: 60%; height: 2px; background: linear-gradient(to right, transparent, #999, transparent); margin: 10px auto; }
                .cert-title { font-size: 46px; font-weight: bold; color: #c5a059; margin: 15px 0 5px 0; letter-spacing: 4px; text-shadow: 1px 1px 0px #333; }
                .cert-no { font-family: 'Courier New', monospace; font-size: 16px; color: #555; font-weight: bold; margin-bottom: 25px; }
                
                .label-text { font-size: 16px; color: #444; margin-bottom: 5px; }
                .peserta-nama { font-size: 34px; font-weight: bold; color: #1a4d80; border-bottom: 2px solid #c5a059; display: inline-block; padding-bottom: 5px; margin-bottom: 10px; font-style: italic; }
                .peserta-instansi { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 25px; }
                
                .description { font-size: 16px; line-height: 1.5; color: #333; margin: 0 auto 30px auto; width: 85%; }
                
                /* TTD */
                .ttd-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                .ttd-cell { width: 50%; text-align: center; vertical-align: bottom; padding: 0 10px; }
                .ttd-image { height: 80px; width: auto; display: block; margin: 0 auto -15px auto; position: relative; z-index: 10; }
                .ttd-spacer { height: 80px; }
                .ttd-name { font-weight: bold; text-decoration: underline; font-size: 16px; margin-top: 5px; position: relative; z-index: 11; }
                .ttd-nip { font-size: 14px; color: #555; }

                /* TRANSKRIP */
                .transkrip-box { padding-top: 60px; width: 85%; margin: 0 auto; font-family: Arial, sans-serif; }
                .transkrip-title { font-size: 22px; font-weight: bold; text-transform: uppercase; border-bottom: 3px double #333; display: inline-block; margin-bottom: 30px; text-align: center; }
                
                .table-info { width: 70%; margin: 0 auto 25px auto; border-collapse: collapse; font-size: 14px; }
                .table-info td { padding: 5px; vertical-align: top; }
                
                .table-nilai { width: 80%; margin: 0 auto; border-collapse: collapse; font-size: 14px; }
                .table-nilai th, .table-nilai td { border: 1px solid #000; padding: 8px; text-align: center; }
                .table-nilai th { background: #f4f4f4; font-weight: bold; }
                .text-left { text-align: left !important; padding-left: 15px !important; }
                
                .footer-note { font-size: 11px; font-style: italic; color: #666; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            
            <div class='frame-border'></div>
            <div class='frame-inner'></div>

            <div class='page-content'>
                $logo_html
                <div class='header-instansi'>$instansi_nama</div>
                <div class='separator'></div>
                
                <div class='cert-title'>SERTIFIKAT</div>
                <div class='cert-no'>No: $nomor</div>
                
                <div class='label-text'>Diberikan kepada:</div>
                <div class='peserta-nama'>$nama</div>
                <div class='peserta-instansi'>$instansi_asal</div>
                
                <div class='description'>
                    Telah menyelesaikan <strong>Program Praktik Kerja Lapangan (PKL)</strong><br>
                    dengan sangat baik dan telah memenuhi segala persyaratan administrasi<br>
                    serta akademik yang berlaku di lingkungan $instansi_nama.
                </div>
                
                <table class='ttd-table'>
                    <tr>
                        <td class='ttd-cell'>
                            <div style='margin-bottom:10px;'>Mengetahui,<br>Pembimbing Lapangan</div>
                            $html_ttd_pem
                            <div class='ttd-name'>$pem_nama</div>
                            ". ($pem_nip ? "<div class='ttd-nip'>NIP. $pem_nip</div>" : "") ."
                        </td>
                        <td class='ttd-cell'>
                            <div style='margin-bottom:10px;'>$kota, $tgl_terbit_indo<br>$kpl_jabatan</div>
                            $html_ttd_kepala
                            <div class='ttd-name'>$kpl_nama</div>
                            ". ($kpl_nip ? "<div class='ttd-nip'>NIP. $kpl_nip</div>" : "") ."
                        </td>
                    </tr>
                </table>
            </div>

            <div class='page-break'></div>

            <div class='transkrip-box'>
                <div style='text-align: center;'>
                    <div class='transkrip-title'>TRANSKRIP NILAI MAGANG</div>
                </div>
                
                <table class='table-info'>
                    <tr><td width='150'><strong>Nama Peserta</strong></td><td width='10'>:</td><td>$nama</td></tr>
                    <tr><td><strong>Instansi Asal</strong></td><td>:</td><td>$instansi_asal</td></tr>
                    <tr><td><strong>Nomor Sertifikat</strong></td><td>:</td><td>$nomor</td></tr>
                </table>
                
                <table class='table-nilai'>
                    <thead>
                        <tr>
                            <th width='10%'>No</th>
                            <th width='50%'>Aspek Penilaian</th>
                            <th width='20%'>Nilai</th>
                            <th width='20%'>Predikat</th>
                        </tr>
                    </thead>
                    <tbody>";
                    
                    $no = 1;
                    foreach ($all_grades as $key => $val) {
                        $v_float = floatval($val);
                        list($grade, $ket) = $this->getPredikat($v_float);
                        $html .= "
                        <tr>
                            <td>$no</td>
                            <td class='text-left'>$key</td>
                            <td>" . number_format($v_float, 2) . "</td>
                            <td>$grade</td>
                        </tr>";
                        $no++;
                    }
                    
                    list($finalGrade, $finalKet) = $this->getPredikat($nilai_akhir);

        $html .= "
                        <tr style='background:#f4f4f4; font-weight:bold;'>
                            <td colspan='2' style='text-align:right; padding-right:15px;'>NILAI AKHIR</td>
                            <td>" . number_format($nilai_akhir, 2) . "</td>
                            <td>$finalGrade</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class='footer-note'>
                    * Nilai Akhir dihitung berdasarkan bobot $bobot_laporan% Nilai Kinerja dan $bobot_hadir% Kehadiran.
                </div>
            </div>

        </body>
        </html>";

        // Render PDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Times');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $filename = "Sertifikat_" . preg_replace('/[^a-zA-Z0-9]/', '_', $nama) . ".pdf";
        $dompdf->stream($filename, ["Attachment" => false]);
    }
}

// 3. EXECUTION
try {
    session_start();
    // Koneksi Database
    $pdo = null;
    if (function_exists('db')) {
        $pdo = db();
    } elseif (isset($GLOBALS['pdo'])) {
        $pdo = $GLOBALS['pdo'];
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=ptun_website;charset=utf8mb4", "root", "");
        } catch (PDOException $e) {
            $pdo = new PDO("mysql:host=localhost;dbname=ptunwebsite;charset=utf8mb4", "root", "");
        }
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id > 0) {
        $pdf = new CertificateGenerator($pdo);
        $pdf->generate($id);
    } else {
        die("ID Peserta tidak valid.");
    }
} catch (Exception $e) {
    die("Error System: " . $e->getMessage());
}
?>