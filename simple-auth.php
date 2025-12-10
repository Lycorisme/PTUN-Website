<?php
// =============================================
// SIMPLE-AUTH.PHP - MINIMAL AUTH 1 FILE
// UNTUK PKL BTIKP - NO COMPLICATION
// =============================================

session_start();

function login_simple($email, $pass) {
    require_once 'config/database.php';
    $stmt = db()->prepare("SELECT * FROM users WHERE email=? AND password=? AND status='aktif'");
    $stmt->execute([$email, $pass]);
    $user = $stmt->fetch();
    if($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_data'] = $user;
        return $user['role'];
    }
    return false;
}

function cek_login() {
    if(!isset($_SESSION['user_id'])) return false;
    try {
        require_once 'config/database.php';
        $stmt = db()->prepare("SELECT role,nama FROM users WHERE id=?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if($user) {
            $_SESSION['user_data'] = $user;
            return $user['role'];
        }
    } catch(Exception $e) {}
    return false;
}

function protect_page() {
    if(!cek_login()) {
        header('Location: login/');
        exit;
    }
}

function is_admin() {
    return cek_login() == 'admin';
}

function is_peserta() {
    return cek_login() == 'peserta';
}

function get_user_name() {
    return $_SESSION['user_data']['nama'] ?? 'User';
}

function logout_simple() {
    session_destroy();
    header('Location: index.php');
    exit;
}

// SETTINGS SIMPLE
function site_name() {
    try {
        require_once 'config/database.php';
        $stmt = db()->prepare("SELECT value FROM settings WHERE `key`='nama_website' LIMIT 1");
        $stmt->execute();
        return $stmt->fetch()['value'] ?? 'PTUN Banjarmasin';
    } catch(Exception $e) {
        return 'PTUN Banjarmasin';
    }
}
?>
