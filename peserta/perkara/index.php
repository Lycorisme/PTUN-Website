<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') exit;

$user = $_SESSION['user_data'];
$peserta_id = $user['id'];

if(isset($_POST['submit_perkara'])) {
    $tanggal = $_POST['tanggal'];
    $deskripsi = $_POST['deskripsi'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $bukti_file = null;
    
    if(isset($_FILES['bukti']) && $_FILES['bukti']['error'] == 0) {
        $target_dir = "../../uploads/perkara/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $file_ext = pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION);
        $filename = 'perkara_' . $peserta_id . '_' . time() . '.' . $file_ext;
        if(move_uploaded_file($_FILES['bukti']['tmp_name'], $target_dir . $filename)) {
            $bukti_file = 'uploads/perkara/' . $filename;
        }
    }
    
    $stmt = db()->prepare("INSERT INTO perkara (peserta_id, tanggal, deskripsi, bukti_file, jam_mulai, jam_selesai) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$peserta_id, $tanggal, $deskripsi, $bukti_file, $jam_mulai, $jam_selesai]);
    header('Location: index.php?msg=success');
    exit;
}

$stmt = db()->prepare("SELECT * FROM perkara WHERE peserta_id=? ORDER BY tanggal DESC LIMIT 30");
$stmt->execute([$peserta_id]);
$riwayat = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perkara - Peserta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
<nav class="bg-gradient-to-r from-purple-600 to-purple-700 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="../" class="text-white"><i class="fas fa-arrow-left text-xl"></i></a>
            <h1 class="text-2xl font-bold">Input Perkara</h1>
        </div>
        <a href="../" class="bg-white/20 px-6 py-2 rounded-xl">Dashboard</a>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
        <i class="fas fa-check-circle mr-2"></i>Perkara berhasil disubmit!
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8 mb-8">
        <h2 class="text-2xl font-bold mb-6">Form Input Perkara Harian</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-2">Tanggal</label>
                    <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500">
                </div>
                <div>
                    <label class="block font-bold mb-2">Upload Bukti</label>
                    <input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-2">Jam Mulai</label>
                    <input type="time" name="jam_mulai" required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500">
                </div>
                <div>
                    <label class="block font-bold mb-2">Jam Selesai</label>
                    <input type="time" name="jam_selesai" required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500">
                </div>
            </div>
            <div>
                <label class="block font-bold mb-2">Deskripsi Perkara</label>
                <textarea name="deskripsi" required rows="5" placeholder="Jelaskan perkara yang dikerjakan..."
                          class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500"></textarea>
            </div>
            <button type="submit" name="submit_perkara"
                    class="w-full bg-gradient-to-r from-purple-500 to-purple-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg">
                <i class="fas fa-paper-plane mr-2"></i>Submit Perkara
            </button>
        </form>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6">Riwayat Perkara</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2">
                        <th class="text-left py-4 px-4 font-bold">Tanggal</th>
                        <th class="text-left py-4 px-4 font-bold">Deskripsi</th>
                        <th class="text-left py-4 px-4 font-bold">Jam</th>
                        <th class="text-center py-4 px-4 font-bold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($riwayat as $r): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-4 px-4"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                        <td class="py-4 px-4"><?= htmlspecialchars(substr($r['deskripsi'], 0, 50)) ?>...</td>
                        <td class="py-4 px-4"><?= $r['jam_mulai'] ?> - <?= $r['jam_selesai'] ?></td>
                        <td class="py-4 px-4 text-center">
                            <?php if($r['approved']): ?>
                                <span class="text-green-600 font-bold"><i class="fas fa-check-circle"></i></span>
                            <?php else: ?>
                                <span class="text-orange-600 font-bold"><i class="fas fa-clock"></i></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>