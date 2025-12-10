<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') {
    header('Location: ../../index.php');
    exit;
}

$user = $_SESSION['user_data'];
$peserta_id = $user['id'];

// HANDLE CEK-IN
if(isset($_POST['checkin'])) {
    $tanggal = date('Y-m-d');
    $catatan = $_POST['catatan'] ?? '';
    
    // Check if already checked in today
    $stmt = db()->prepare("SELECT id FROM absensi WHERE peserta_id=? AND tanggal=?");
    $stmt->execute([$peserta_id, $tanggal]);
    
    if($stmt->fetch()) {
        $error = "Anda sudah absen hari ini!";
    } else {
        $stmt = db()->prepare("INSERT INTO absensi (peserta_id, tanggal, status, catatan, approved) VALUES (?, ?, 'hadir', ?, 0)");
        $stmt->execute([$peserta_id, $tanggal, $catatan]);
        header('Location: index.php?msg=success');
        exit;
    }
}

// GET RIWAYAT ABSENSI
$stmt = db()->prepare("SELECT * FROM absensi WHERE peserta_id=? ORDER BY tanggal DESC LIMIT 30");
$stmt->execute([$peserta_id]);
$riwayat = $stmt->fetchAll();

// CHECK IF ALREADY CHECKED IN TODAY
$today = date('Y-m-d');
$stmt = db()->prepare("SELECT id FROM absensi WHERE peserta_id=? AND tanggal=?");
$stmt->execute([$peserta_id, $today]);
$already_checked_in = $stmt->fetch() ? true : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi - Peserta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

<nav class="bg-gradient-to-r from-green-600 to-emerald-700 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="../" class="text-white hover:text-green-100"><i class="fas fa-arrow-left text-xl"></i></a>
            <h1 class="text-2xl font-bold">Absensi Harian</h1>
        </div>
        <a href="../" class="bg-white/20 hover:bg-white/30 px-6 py-2 rounded-xl font-semibold">Dashboard</a>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-6 py-8">

    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
        <i class="fas fa-check-circle mr-2"></i>Absensi berhasil! Menunggu approval admin.
    </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8">
        <i class="fas fa-exclamation-triangle mr-2"></i><?= $error ?>
    </div>
    <?php endif; ?>

    <!-- CEK-IN CARD -->
    <div class="bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-3xl p-8 mb-8 shadow-xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold mb-2">Cek-In Hari Ini</h2>
                <p class="text-xl text-green-100"><?= format_tanggal_id($today) ?></p>
            </div>
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-check text-4xl"></i>
            </div>
        </div>
        
        <?php if($already_checked_in): ?>
            <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-6 text-center">
                <i class="fas fa-check-circle text-5xl mb-4"></i>
                <p class="text-2xl font-bold">Anda Sudah Absen Hari Ini!</p>
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-bold mb-2">Catatan (Opsional)</label>
                    <textarea name="catatan" rows="3" placeholder="Tambahkan catatan absensi..."
                              class="w-full px-4 py-3 border-0 rounded-xl text-gray-900 focus:ring-4 focus:ring-white/50"></textarea>
                </div>
                <button type="submit" name="checkin" 
                        class="w-full bg-white text-green-600 py-4 rounded-xl font-bold text-lg hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <i class="fas fa-check-circle mr-2"></i>CEK-IN SEKARANG
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- RIWAYAT ABSENSI -->
    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6">Riwayat Absensi (30 Hari Terakhir)</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 font-bold">Tanggal</th>
                        <th class="text-left py-4 px-4 font-bold">Status</th>
                        <th class="text-left py-4 px-4 font-bold">Catatan</th>
                        <th class="text-center py-4 px-4 font-bold">Approval</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($riwayat as $r): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-4 px-4 font-semibold"><?= format_tanggal_id($r['tanggal']) ?></td>
                        <td class="py-4 px-4">
                            <?php 
                            $badge = ['hadir'=>'bg-green-100 text-green-800', 'alfa'=>'bg-red-100 text-red-800', 'izin'=>'bg-yellow-100 text-yellow-800'];
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $badge[$r['status']] ?>">
                                <?= strtoupper($r['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($r['catatan'] ?? '-') ?></td>
                        <td class="py-4 px-4 text-center">
                            <?php if($r['approved']): ?>
                                <span class="text-green-600 font-bold"><i class="fas fa-check-circle"></i> Approved</span>
                            <?php else: ?>
                                <span class="text-orange-600 font-bold"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
<?php if(isset($_GET['msg'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Berhasil Cek-In!',
    text: 'Absensi Anda berhasil disimpan',
    confirmButtonColor: '#10b981'
});
<?php endif; ?>
</script>

</body>
</html>