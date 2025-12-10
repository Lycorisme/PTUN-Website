<?php
// =============================================
// CERTIFICATE GENERATOR - NUTRIENT.IO STYLE (SETTINGS DINAMIS)
// =============================================

require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

error_reporting(E_ALL);
ini_set('display_errors', 0);

class CertificateGenerator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // GET SETTINGS DARI DATABASE
    private function getSettings() {
        $defaults = [
            'nama_instansi' => 'BTIKP Kalimantan Selatan',
            'namapanjang' => 'Balai Teknologi Informasi dan Komunikasi Pendidikan',
            'kotainstansi' => 'Banjarmasin',
            'logourl' => '',
            'kepalanama' => 'Hj. Rusiani, S.Pd., M.Pd.',
            'kepalajabatan' => 'Kepala BTIKP',
            'kepalanip' => '',
            'pembimbingnama' => 'Juli Anshori, M.Kom.',
            'pembimbingjabatan' => 'Pembimbing PKL'
        ];
        
        try {
            $stmt = $this->pdo->prepare("SELECT `key`, value FROM settings WHERE `group` = 'institusi'");
            $stmt->execute();
            while ($row = $stmt->fetch()) {
                $defaults[$row['key']] = $row['value'];
            }
        } catch (Exception $e) {
            // Fallback to defaults
        }
        
        return $defaults;
    }
    
    // GET PESERTA DATA
    public function getPesertaData($id) {
        $sql = "SELECT 
                    u.nama, u.instansi, u.jurusan, u.email,
                    s.nomor_sertifikat, s.issued_date
                FROM users u
                JOIN sertifikat s ON u.id = s.peserta_id
                WHERE u.id = ? AND s.status IN ('tersedia', 'selesai')";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }
    
    // GENERATE CERTIFICATE
    public function generate($peserta_id) {
        $data = $this->getPesertaData($peserta_id);
        if (!$data) {
            throw new Exception('Data sertifikat tidak ditemukan');
        }
        
        $settings = $this->getSettings();
        
        // TANGGAL
        $issued_date = $data['issued_date'];
        $tgl_terbit = $this->formatTanggal($issued_date);
        $tgl_mulai = $this->formatTanggal(date('Y-m-d', strtotime($issued_date . ' -2 months')));
        $tgl_selesai = $this->formatTanggal(date('Y-m-d', strtotime($issued_date . ' -1 day')));
        
        // HTML TEMPLATE (Nutrient.io inspired)
        $html = $this->renderTemplate($data, $settings, $tgl_terbit, $tgl_mulai, $tgl_selesai);
        
        // GENERATE PDF
        return $this->htmlToPdf($html, $data);
    }
    
    private function formatTanggal($tgl) {
        $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        if (empty($tgl) || $tgl == '0000-00-00') return date('d F Y');
        $split = explode('-', $tgl);
        return $split[2] . ' ' . $bulan[(int)$split[1]-1] . ' ' . $split[0];
    }
    
    private function renderTemplate($data, $settings, $tgl_terbit, $tgl_mulai, $tgl_selesai) {
        $logo = '';
        if (!empty($settings['logourl']) && file_exists("../../{$settings['logourl']}")) {
            $logo = '<img src="../../' . htmlspecialchars($settings['logourl']) . '" style="max-width:100px; max-height:50px; vertical-align:middle;"> ';
        }
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Sertifikat ' . htmlspecialchars($data['nama']) . '</title>
            <style>
                @page { margin: 15px; size: A4 landscape; }
                body { 
                    font-family: "Times New Roman", serif; 
                    color: #000; margin: 0; padding: 20px; 
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 50%);
                    font-size: 16px; line-height: 1.4;
                }
                .certificate {
                    background: white;
                    border: 20px solid #D8B100;
                    border-radius: 15px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    position: relative;
                    min-height: 550px;
                    padding: 40px;
                    text-align: center;
                }
                .header {
                    font-size: 22px;
                    color: #CD1F00;
                    font-weight: bold;
                    margin-bottom: 20px;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                .title {
                    color: #D4A017;
                    font-size: 36px;
                    font-weight: bold;
                    margin: 25px 0;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
                    letter-spacing: 2px;
                }
                .subtitle {
                    font-size: 20px;
                    font-weight: bold;
                    margin: 25px 0;
                    color: #2C3E50;
                }
                .nama {
                    border-bottom: 4px solid #1E3A8A;
                    font-size: 32px;
                    font-style: italic;
                    margin: 30px auto;
                    width: 70%;
                    padding-bottom: 15px;
                    color: #1E3A8A;
                    font-weight: bold;
                    letter-spacing: 1px;
                }
                .detail {
                    font-size: 18px;
                    margin: 30px 60px;
                    color: #34495E;
                    line-height: 1.7;
                }
                .no-sertifikat {
                    position: absolute;
                    top: 25px;
                    right: 35px;
                    background: #FFF3CD;
                    padding: 10px 18px;
                    border: 2px solid #D8B100;
                    border-radius: 12px;
                    font-size: 14px;
                    font-weight: bold;
                    color: #1E3A8A;
                }
                .ttd-section {
                    position: absolute;
                    bottom: 60px;
                    right: 60px;
                    width: 280px;
                    text-align: center;
                }
                .ttd-line {
                    border-bottom: 3px dashed #1E3A8A;
                    height: 45px;
                    margin-bottom: 12px;
                }
                .ttd-jabatan {
                    font-size: 15px;
                    font-weight: bold;
                    color: #2C3E50;
                    margin-bottom: 6px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .ttd-nama {
                    font-size: 17px;
                    font-weight: bold;
                    color: #1E3A8A;
                    margin-bottom: 4px;
                }
                .ttd-nip {
                    font-size: 13px;
                    color: #7F8C8D;
                    font-style: italic;
                }
                .tanggal {
                    position: absolute;
                    bottom: 25px;
                    left: 60px;
                    font-size: 16px;
                    color: #7F8C8D;
                    font-style: italic;
                }
                @media print {
                    body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                }
            </style>
        </head>
        <body>
            <div class="certificate">
                <div class="no-sertifikat">
                    No: ' . htmlspecialchars($data['nomor_sertifikat']) . '
                </div>
                
                <div class="header">
                    ' . $logo . htmlspecialchars($settings['namapanjang']) . '
                </div>
                
                <div class="title">SERTIFIKAT PENYELESAIAN</div>
                
                <div class="subtitle">Diberikan kepada</div>
                
                <div class="nama">' . htmlspecialchars($data['nama']) . '</div>
                
                <div class="detail">
                    Telah dengan baik dan penuh tanggung jawab melaksanakan<br>
                    <strong>Praktek Kerja Lapangan (PKL)</strong><br><br>
                    <strong>' . htmlspecialchars($data['instansi']) . '</strong><br>
                    ' . ($data['jurusan'] ? htmlspecialchars($data['jurusan']) : '') . '<br><br>
                    Periode: <strong>' . $tgl_mulai . ' s.d ' . $tgl_selesai . '</strong>
                </div>
                
                <div class="ttd-section">
                    <div class="ttd-line"></div>
                    <div class="ttd-jabatan">' . htmlspecialchars($settings['kepalajabatan']) . '</div>
                    <div class="ttd-nama">' . htmlspecialchars($settings['kepalanama']) . '</div>
                    ' . (!empty($settings['kepalanip']) ? '<div class="ttd-nip">NIP. ' . htmlspecialchars($settings['kepalanip']) . '</div>' : '') . '
                </div>
                
                <div class="tanggal">
                    ' . htmlspecialchars($settings['kotainstansi']) . ', ' . $tgl_terbit . '
                </div>
            </div>
        </body>
        </html>';
    }
    
    private function htmlToPdf($html, $data) {
        $options = new Options();
        $options->set('defaultFont', 'serif');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('dpi', 150);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $filename = "Sertifikat_" . preg_replace('/[^a-zA-Z0-9]/', '_', $data['nama']) . "_" . $data['nomor_sertifikat'] . ".pdf";
        $dompdf->stream($filename, ["Attachment" => false]);
    }
}

// MAIN EXECUTION
try {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Harap login terlebih dahulu.');
    }
    
    // DATABASE CONNECTION
    $pdo = null;
    if (function_exists('db')) {
        $pdo = db();
    } elseif (isset($GLOBALS['pdo'])) {
        $pdo = $GLOBALS['pdo'];
    } else {
        $pdo = new PDO("mysql:host=localhost;dbname=ptunwebsite;charset=utf8mb4", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }
    
    $generator = new CertificateGenerator($pdo);
    $peserta_id = (int)($_GET['id'] ?? 0);
    
    if ($_SESSION['role'] == 'peserta' && $_SESSION['user_id'] != $peserta_id) {
        throw new Exception('Akses ditolak.');
    }
    
    $generator->generate($peserta_id);
    
} catch (Exception $e) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error Sertifikat</title>
        <style>body{font-family:system-ui,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:linear-gradient(135deg,#667eea,#764ba2);}.error-box{background:rgba(255,255,255,0.95);padding:40px;border-radius:20px;box-shadow:0 20px 40px rgba(0,0,0,0.2);max-width:450px;text-align:center;}</style>
    </head>
    <body>
        <div class="error-box">
            <div style="font-size:60px;margin-bottom:20px;">⚠️</div>
            <h2><?=htmlspecialchars($e->getMessage())?></h2>
            <a href="javascript:history.back()" style="color:#667eea;text-decoration:none;font-weight:bold;">← Kembali</a>
        </div>
    </body>
    </html>
    <?php
}
?>
