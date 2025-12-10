<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../index.php');
    exit;
}

// HANDLE APPROVE
if(isset($_POST['approve_absensi'])) {
    $id = $_POST['absensi_id'];
    $stmt = db()->prepare("UPDATE absensi SET approved=1, approved_at=NOW() WHERE id=?");
    $stmt->execute([$id]);
    header('Location: index.php?msg=approved');
    exit;
}

// GET ALL ABSENSI dengan JOIN peserta
$stmt = db()->query("SELECT a.*, u.nama, u.instansi FROM absensi a JOIN users u ON a.peserta_id=u.id ORDER BY a.tanggal DESC, a.created_at DESC LIMIT 50");
$absensi_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Absensi - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

<nav class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="../" class="text-white hover:text-blue-100"><i class="fas fa-arrow-left text-xl"></i></a>
            <h1 class="text-2xl font-bold">Kelola Absensi</h1>
        </div>
        <a href="../" class="bg-white/20 hover:bg-white/30 px-6 py-2 rounded-xl font-semibold transition-all">Dashboard</a>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
        <i class="fas fa-check-circle mr-2"></i>Absensi berhasil di-approve!
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6">Daftar Absensi Peserta</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 font-bold">Nama Peserta</th>
                        <th class="text-left py-4 px-4 font-bold">Instansi</th>
                        <th class="text-left py-4 px-4 font-bold">Tanggal</th>
                        <th class="text-left py-4 px-4 font-bold">Status</th>
                        <th class="text-left py-4 px-4 font-bold">Catatan</th>
                        <th class="text-center py-4 px-4 font-bold">Approval</th>
                        <th class="text-center py-4 px-4 font-bold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($absensi_list as $a): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-4 px-4 font-semibold"><?= htmlspecialchars($a['nama']) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($a['instansi']) ?></td>
                        <td class="py-4 px-4"><?= format_tanggal_id($a['tanggal']) ?></td>
                        <td class="py-4 px-4">
                            <?php 
                            $badge = ['hadir'=>'bg-green-100 text-green-800', 'alfa'=>'bg-red-100 text-red-800', 'izin'=>'bg-yellow-100 text-yellow-800'];
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $badge[$a['status']] ?>">
                                <?= strtoupper($a['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($a['catatan'] ?? '-') ?></td>
                        <td class="py-4 px-4 text-center">
                            <?php if($a['approved']): ?>
                                <span class="text-green-600 font-bold"><i class="fas fa-check-circle"></i> Approved</span>
                            <?php else: ?>
                                <span class="text-orange-600 font-bold"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <?php if(!$a['approved']): ?>
                            <button onclick="approveAbsensi(<?= $a['id'] ?>, '<?= htmlspecialchars($a['nama']) ?>')" 
                                    class="bg-green-500 text-white px-4 py-2 rounded-xl hover:bg-green-600">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
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
    <input type="hidden" name="absensi_id" id="approve_absensi_id">
    <input type="hidden" name="approve_absensi" value="1">
</form>

<script>
function approveAbsensi(id, nama) {
    Swal.fire({
        title: 'Approve Absensi?',
        text: `Approve absensi ${nama}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Approve!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('approve_absensi_id').value = id;
            document.getElementById('approveForm').submit();
        }
    });
}
</script>

</body>
</html>