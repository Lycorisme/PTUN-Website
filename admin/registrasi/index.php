<?php
require_once '../../config/database.php';
// session_start(); // Session sudah distart otomatis

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') exit;

if(isset($_POST['approve'])) {
    $stmt = db()->prepare("UPDATE users SET status='aktif' WHERE id=?");
    $stmt->execute([$_POST['peserta_id']]);
    
    // Send notification
    $stmt = db()->prepare("INSERT INTO notifications (to_user_id, title, pesan, tipe) VALUES (?, 'Registrasi Disetujui', 'Selamat! Akun Anda telah diaktifkan oleh admin', 'success')");
    $stmt->execute([$_POST['peserta_id']]);
    
    header('Location: index.php?msg=approved');
    exit;
}

if(isset($_POST['reject'])) {
    $stmt = db()->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$_POST['peserta_id']]);
    header('Location: index.php?msg=rejected');
    exit;
}

$stmt = db()->query("SELECT * FROM users WHERE role='peserta' AND status='pending' ORDER BY created_at DESC");
$pendaftar = $stmt->fetchAll();

// Set Page Title & Header
$page_title = 'Kelola Registrasi';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-<?= $_GET['msg']=='approved' ? 'green' : 'red' ?>-100 border-l-4 border-<?= $_GET['msg']=='approved' ? 'green' : 'red' ?>-500 text-<?= $_GET['msg']=='approved' ? 'green' : 'red' ?>-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-check-circle mr-2"></i><?= $_GET['msg']=='approved' ? 'Pendaftar berhasil di-approve!' : 'Pendaftar berhasil ditolak!' ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Pendaftar Baru</h2>
            <span class="bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm font-bold">
                Total: <?= count($pendaftar) ?>
            </span>
        </div>
        
        <?php if(count($pendaftar) == 0): ?>
        <div class="text-center py-12 border-2 border-dashed border-gray-200 rounded-2xl">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-500">Tidak ada pendaftar baru saat ini.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($pendaftar as $p): ?>
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-6 border border-gray-200 hover:shadow-lg transition-all transform hover:-translate-y-1">
                <div class="flex items-center mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mr-4 shadow-md">
                        <i class="fas fa-user text-2xl text-white"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h3 class="font-bold text-lg text-gray-900 truncate"><?= htmlspecialchars($p['nama']) ?></h3>
                        <p class="text-sm text-gray-600 truncate"><?= htmlspecialchars($p['email']) ?></p>
                    </div>
                </div>
                
                <div class="space-y-2 mb-6 text-sm bg-white p-4 rounded-xl shadow-sm">
                    <p class="flex justify-between"><span class="font-semibold text-gray-600">Instansi:</span> <span class="text-gray-900"><?= htmlspecialchars($p['instansi']) ?></span></p>
                    <p class="flex justify-between"><span class="font-semibold text-gray-600">Jurusan:</span> <span class="text-gray-900"><?= htmlspecialchars($p['jurusan']) ?></span></p>
                    <p class="flex justify-between"><span class="font-semibold text-gray-600">Tanggal:</span> <span class="text-gray-900"><?= format_tanggal_id($p['created_at']) ?></span></p>
                </div>
                
                <div class="flex space-x-2">
                    <form method="POST" class="flex-1">
                        <input type="hidden" name="peserta_id" value="<?= $p['id'] ?>">
                        <button type="submit" name="approve" class="w-full bg-green-500 text-white py-3 rounded-xl font-bold hover:bg-green-600 transition-all shadow-sm">
                            <i class="fas fa-check mr-1"></i> Approve
                        </button>
                    </form>
                    <form method="POST" class="flex-1" onsubmit="return confirm('Yakin tolak pendaftar ini? Data akan dihapus permanen.')">
                        <input type="hidden" name="peserta_id" value="<?= $p['id'] ?>">
                        <button type="submit" name="reject" class="w-full bg-red-500 text-white py-3 rounded-xl font-bold hover:bg-red-600 transition-all shadow-sm">
                            <i class="fas fa-times mr-1"></i> Tolak
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>