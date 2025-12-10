<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') exit;

$jenis = $_GET['jenis'] ?? 'harian';

if(isset($_POST['approve'])) {
    $table = 'laporan_' . $_POST['jenis'];
    $stmt = db()->prepare("UPDATE $table SET approved=1, approved_at=NOW() WHERE id=?");
    $stmt->execute([$_POST['laporan_id']]);
    header('Location: index.php?jenis=' . $_POST['jenis'] . '&msg=approved');
    exit;
}

$tables = ['harian', 'mingguan', 'bulanan', 'ringkasan'];
$laporan_data = [];

foreach($tables as $t) {
    $stmt = db()->query("SELECT l.*, u.nama FROM laporan_$t l JOIN users u ON l.peserta_id=u.id ORDER BY l.id DESC LIMIT 20");
    $laporan_data[$t] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Laporan - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
<nav class="bg-gradient-to-r from-pink-600 to-pink-700 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="../" class="text-white"><i class="fas fa-arrow-left text-xl"></i></a>
            <h1 class="text-2xl font-bold">Kelola Laporan</h1>
        </div>
        <a href="../" class="bg-white/20 px-6 py-2 rounded-xl">Dashboard</a>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
        <i class="fas fa-check-circle mr-2"></i>Laporan berhasil di-approve!
    </div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden mb-8">
        <div class="flex border-b overflow-x-auto">
            <?php foreach($tables as $t): ?>
            <a href="?jenis=<?= $t ?>" class="px-6 py-4 font-bold <?= $jenis==$t ? 'border-b-4 border-pink-600 text-pink-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                <?= ucfirst($t) ?>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="p-8">
            <h2 class="text-2xl font-bold mb-6">Laporan <?= ucfirst($jenis) ?></h2>
            <table class="w-full">
                <thead>
                    <tr class="border-b-2">
                        <th class="text-left py-4 px-4 font-bold">Nama Peserta</th>
                        <th class="text-left py-4 px-4 font-bold">Periode</th>
                        <th class="text-left py-4 px-4 font-bold">Isi Laporan</th>
                        <th class="text-center py-4 px-4 font-bold">Status</th>
                        <th class="text-center py-4 px-4 font-bold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($laporan_data[$jenis] as $l): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-4 px-4 font-semibold"><?= htmlspecialchars($l['nama']) ?></td>
                        <td class="py-4 px-4">
                            <?php if($jenis=='harian'): ?>
                                <?= date('d/m/Y', strtotime($l['tanggal'])) ?>
                            <?php elseif($jenis=='mingguan'): ?>
                                Minggu ke-<?= $l['minggu_ke'] ?>, <?= $l['bulan'] ?>/<?= $l['tahun'] ?>
                            <?php elseif($jenis=='bulanan'): ?>
                                <?= $l['bulan'] ?>/<?= $l['tahun'] ?>
                            <?php else: ?>
                                <?= date('d/m/Y', strtotime($l['periode_start'])) ?> - <?= date('d/m/Y', strtotime($l['periode_end'])) ?>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4"><?= htmlspecialchars(substr($l['isi_laporan'] ?? $l['ringkasan'], 0, 50)) ?>...</td>
                        <td class="py-4 px-4 text-center">
                            <?php if($l['approved']): ?>
                                <span class="text-green-600 font-bold"><i class="fas fa-check-circle"></i></span>
                            <?php else: ?>
                                <span class="text-orange-600 font-bold"><i class="fas fa-clock"></i></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <?php if(!$l['approved']): ?>
                            <button onclick="approveLaporan(<?= $l['id'] ?>, '<?= $jenis ?>')" class="bg-green-500 text-white px-4 py-2 rounded-xl hover:bg-green-600">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="approveForm" method="POST" class="hidden">
    <input type="hidden" name="laporan_id" id="approve_id">
    <input type="hidden" name="jenis" id="approve_jenis">
    <input type="hidden" name="approve" value="1">
</form>

<script>
function approveLaporan(id, jenis) {
    Swal.fire({
        title: 'Approve Laporan?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        confirmButtonText: 'Ya, Approve!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('approve_id').value = id;
            document.getElementById('approve_jenis').value = jenis;
            document.getElementById('approveForm').submit();
        }
    });
}
</script>
</body>
</html>