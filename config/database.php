<?php
// =============================================
// CONFIG/DATABASE.PHP - PTUN WEBSITE
// PDO Connection + Helper Functions
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
    $stmt = db()->prepare("SELECT `value` FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['value'] : $default;
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
        if($menu_str) {
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
    session_start();
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

// GET ABSENSI STATISTICS (Peserta)
function get_absensi_stats($peserta_id, $bulan = null, $tahun = null) {
    $where = "peserta_id = ?";
    $params = [$peserta_id];
    
    if($bulan && $tahun) {
        $where .= " AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
        $params[] = $bulan;
        $params[] = $tahun;
    }
    
    $stmt = db()->prepare("SELECT status, COUNT(*) as total FROM absensi WHERE $where GROUP BY status");
    $stmt->execute($params);
    
    $stats = ['hadir' => 0, 'alfa' => 0, 'izin' => 0];
    while($row = $stmt->fetch()) {
        $stats[$row['status']] = $row['total'];
    }
    return $stats;
}

// NOTIFICATION COUNT
function get_notification_count($user_id) {
    $stmt = db()->prepare("SELECT COUNT(*) as unread FROM notifications WHERE to_user_id = ? AND dibaca = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['unread'];
}

// FORMAT TANGGAL INDONESIA
function format_tanggal_id($tanggal) {
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $date = new DateTime($tanggal);
    return $date->format('d') . ' ' . $bulan[$date->format('n')] . ' ' . $date->format('Y');
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
        if($stmt->fetch()['count'] == 0) {
            echo "<div class='alert alert-info'>Settings table kosong. Data default dimuat...</div>";
            // Insert default settings jika kosong (tidak duplicate dengan SQL dump)
        }
    } catch(Exception $e) {
        // Settings table akan diisi dari SQL dump
    }
}

// INIT
session_start();
ensure_settings_table();
?>
