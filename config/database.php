<?php
// =============================================
// CONFIG/DATABASE.PHP - PTUN WEBSITE
// =============================================

// 1. DEFINISI BASE URL (Ganti sesuai nama folder project Anda)
// Jika di Laragon folder project Anda 'ptun-website', maka:
define('BASE_URL', 'http://localhost/ptun-website'); 

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

// SETTINGS DINAMIS
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

// GET SITE INFO
function get_site_name() {
    return get_setting('nama_website', 'PTUN Banjarmasin');
}

function get_site_tagline() {
    return get_setting('tagline', 'Keadilan Administrasi Negara');
}

function get_logo_url() {
    return get_setting('logo_url', '/assets/logo-default.png');
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
        header('Location: ' . BASE_URL . '/login/');
        exit;
    }
    
    $user = current_user();
    if($role && $user['role'] != $role) {
        header('Location: ' . BASE_URL . '/login/?error=unauthorized');
        exit;
    }
    
    $_SESSION['user_data'] = $user;
    return $user;
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

// =============================================
// ABSENSI & SERTIFIKAT HELPER (Tetap Dipertahankan)
// =============================================
function absensi_max_hari() { return (int)get_setting('absensi_max_hari', 22); }

function get_absensi_stats($peserta_id) {
    $stmt = db()->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='hadir' AND approved=1 THEN 1 ELSE 0 END) as hadir, SUM(CASE WHEN status='alfa' THEN 1 ELSE 0 END) as alfa, SUM(CASE WHEN status='izin' THEN 1 ELSE 0 END) as izin FROM absensi WHERE peserta_id = ?");
    $stmt->execute([$peserta_id]);
    $result = $stmt->fetch();
    return ['total' => $result['total']??0, 'hadir' => $result['hadir']??0, 'alfa' => $result['alfa']??0, 'izin' => $result['izin']??0];
}

function absensi_percentage($peserta_id) {
    $stats = get_absensi_stats($peserta_id);
    $max_hari = absensi_max_hari();
    return ($max_hari == 0) ? 0 : round(($stats['hadir'] / $max_hari) * 100, 1);
}

// =============================================
// INIT SESSION
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>