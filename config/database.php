<?php
// =============================================
// CONFIG/DATABASE.PHP - PTUN WEBSITE
// PDO Connection + Helper Functions
// FIXED: No double session_start()
// =============================================

class Database {
    private $host = 'localhost';
    private $db_name = 'ptun_website';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// =============================================
// HELPER FUNCTIONS - GLOBAL UTAMA
// =============================================

function db() {
    $database = new Database();
    return $database->getConnection();
}

// SETTINGS DINAMIS (15+ Parameter)
function get_setting($key, $default = '') {
    try {
        $stmt = db()->prepare("SELECT `value` FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : $default;
    } catch(Exception $e) {
        return $default;
    }
}

// GET SITE INFO (Untuk header/footer)
function get_site_name() {
    return get_setting('nama_website', 'PTUN Banjarmasin');
}

function get_site_tagline() {
    return get_setting('tagline', 'Keadilan Administrasi Negara');
}

function get_logo_url() {
    return get_setting('logo_url', '/assets/logo-default.png');
}

function get_menu_items() {
    $menus = [];
    $menu_keys = ['menu_beranda', 'menu_tentang', 'menu_layanan', 'menu_kontak'];
    
    foreach($menu_keys as $key) {
        $menu_str = get_setting($key, '');
        if($menu_str && strpos($menu_str, '|') !== false) {
            list($title, $url) = explode('|', $menu_str, 2);
            $menus[] = ['title' => trim($title), 'url' => trim($url)];
        }
    }
    return $menus;
}

// USER SESSION CHECK
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    if(!is_logged_in()) return false;
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function protect_page($role = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if(!is_logged_in()) {
        header('Location: ../login/');
        exit;
    }
    
    $user = current_user();
    if($role && $user['role'] != $role) {
        header('Location: ../login/?error=unauthorized');
        exit;
    }
    
    $_SESSION['user_data'] = $user;
    return $user;
}

// =============================================
// ABSENSI DINAMIS FUNCTIONS
// =============================================

// Get max hari kerja dari settings
function absensi_max_hari() {
    return (int)get_setting('absensi_max_hari', 22);
}

// Get absensi statistics untuk peserta
function get_absensi_stats($peserta_id) {
    $stmt = db()->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status='hadir' AND approved=1 THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status='alfa' THEN 1 ELSE 0 END) as alfa,
            SUM(CASE WHEN status='izin' THEN 1 ELSE 0 END) as izin
        FROM absensi 
        WHERE peserta_id = ?
    ");
    $stmt->execute([$peserta_id]);
    $result = $stmt->fetch();
    
    return [
        'total' => $result['total'] ?? 0,
        'hadir' => $result['hadir'] ?? 0,
        'alfa' => $result['alfa'] ?? 0,
        'izin' => $result['izin'] ?? 0
    ];
}

// Alias untuk backward compatibility
function absensi_stats($peserta_id) {
    return get_absensi_stats($peserta_id);
}

// Calculate percentage kehadiran
function absensi_percentage($peserta_id) {
    $stats = get_absensi_stats($peserta_id);
    $max_hari = absensi_max_hari();
    
    if($max_hari == 0) return 0;
    
    $persen = ($stats['hadir'] / $max_hari) * 100;
    return round($persen, 1);
}

// =============================================
// SERTIFIKAT DINAMIS FUNCTIONS
// =============================================

// Get min hadir % untuk sertifikat
function sertifikat_min_hadir() {
    return (int)get_setting('sertifikat_min_hadir', 80);
}

// Get min score untuk sertifikat
function sertifikat_min_score() {
    return (int)get_setting('sertifikat_min_score', 75);
}

// Get bobot kehadiran
function sertifikat_bobot_hadir() {
    return (int)get_setting('sertifikat_bobot_hadir', 60);
}

// Get bobot laporan
function sertifikat_bobot_laporan() {
    return (int)get_setting('sertifikat_bobot_laporan', 40);
}

// Calculate sertifikat score
function calculate_sertifikat_score($peserta_id) {
    // Hitung persentase kehadiran
    $stats = get_absensi_stats($peserta_id);
    $max_hadir = absensi_max_hari();
    $persen_hadir = $max_hadir > 0 ? ($stats['hadir'] / $max_hadir) * 100 : 0;
    
    // Hitung rata-rata penilaian laporan
    $stmt = db()->prepare("
        SELECT AVG(lp.penilaian) as avg_penilaian 
        FROM laporan_harian lh
        LEFT JOIN laporan_penilaian lp ON lh.id = lp.laporan_id
        WHERE lh.peserta_id = ? AND lh.approved = 1
    ");
    $stmt->execute([$peserta_id]);
    $result = $stmt->fetch();
    $avg_laporan = $result['avg_penilaian'] ?? 0;
    
    // Hitung score final
    $bobot_hadir = sertifikat_bobot_hadir();
    $bobot_laporan = sertifikat_bobot_laporan();
    
    $score_hadir = ($persen_hadir * $bobot_hadir) / 100;
    $score_laporan = ($avg_laporan * $bobot_laporan) / 10; // Asumsi penilaian max 10
    
    return round($score_hadir + $score_laporan, 1);
}

// Check apakah peserta bisa generate sertifikat
function can_generate_sertifikat($peserta_id) {
    $score = calculate_sertifikat_score($peserta_id);
    $min_score = sertifikat_min_score();
    $min_hadir = sertifikat_min_hadir();
    
    $persen_hadir = absensi_percentage($peserta_id);
    
    return ($score >= $min_score && $persen_hadir >= $min_hadir);
}

// NOTIFICATION COUNT
function get_notification_count($user_id) {
    try {
        $stmt = db()->prepare("SELECT COUNT(*) as unread FROM notifications WHERE to_user_id = ? AND dibaca = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetch()['unread'] ?? 0;
    } catch(Exception $e) {
        return 0;
    }
}

// FORMAT TANGGAL INDONESIA
function format_tanggal_id($tanggal) {
    if(!$tanggal || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') return '-';
    
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    try {
        $date = new DateTime($tanggal);
        return $date->format('d') . ' ' . $bulan[$date->format('n')] . ' ' . $date->format('Y');
    } catch(Exception $e) {
        return $tanggal;
    }
}

// DEBUG HELPER
function dd($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    die();
}

// =============================================
// AUTO CREATE SETTINGS TABLE (First Run)
// =============================================
function ensure_settings_table() {
    try {
        $stmt = db()->query("SELECT COUNT(*) as count FROM settings");
    } catch(Exception $e) {
        // Settings table akan diisi dari SQL dump
    }
}

// INIT - Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ensure_settings_table();
?>