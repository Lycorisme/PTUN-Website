<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') exit;

if(isset($_POST['approve'])) {
    $stmt = db()->prepare("UPDATE perkara SET approved=1, approved_at=NOW() WHERE id=?");
    $stmt->execute([$_POST['perkara_id']]);
    header('Location: index.php?msg=approved');
    exit;
}

$stmt = db()->query("SELECT p.*, u.nama, u.instansi FROM perkara p JOIN users u ON p.peserta_id=u.id ORDER BY p.tanggal DESC LIMIT 50");
$perkara_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Perkara - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
<nav class="bg-gradient-to-r from-purple-600 to-purple-700 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="../" class="text-white"><i class="fas fa-arrow-left text-xl"></i></a>
            <h1 class="text-2xl font-bold">Kelola Perkara</h1>
        </div>
        <a href="../" class="bg-white/20 px-6 py-2 rounded-xl">Dashboard</a>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
        <i class="fas fa-check-circle mr-2"></i>Perkara berhasil di-approve!
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6">Daftar Perkara Peserta</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 font-bold">Nama</th>
                        <th class="text-left py-4 px-4 font-bold">Tanggal</th>
                        <th class="text-left py-4 px-4 font-bold">Deskripsi</th>
                        <th class="text-left py-4 px-4 font-bold">Jam</th>
                        <th class="text-left py-4 px-4 font-bold">Bukti</th>
                        <th class="text-center py-4 px-4 font-bold">Status</th>
                        <th class="text-center py-4 px-4 font-bold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($perkara_list as $p): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-4 px-4 font-semibold"><?= htmlspecialchars($p['nama']) ?></td>
                        <td class="py-4 px-4"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars(substr($p['deskripsi'], 0, 50)) ?>...</td>
                        <td class="py-4 px-4"><?= $p['jam_mulai'] ?> - <?= $p['jam_selesai'] ?></td>
                        <td class="py-4 px-4">
                            <?php if($p['bukti_file']): ?>
                                <a href="../../<?= $p['bukti_file'] ?>" target="_blank" class="text-blue-600"><i class="fas fa-file"></i> Lihat</a>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <?php if($p['approved']): ?>
                                <span class="text-green-600 font-bold"><i class="fas fa-check-circle"></i></span>
                            <?php else: ?>
                                <span class="text-orange-600 font-bold"><i class="fas fa-clock"></i></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <?php if(!$p['approved']): ?>
                            <button onclick="approvePerkara(<?= $p['id'] ?>)" class="bg-green-500 text-white px-4 py-2 rounded-xl hover:bg-green-600">
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
    <input type="hidden" name="perkara_id" id="approve_id">
    <input type="hidden" name="approve" value="1">
</form>

<script>
function approvePerkara(id) {
    Swal.fire({
        title: 'Approve Perkara?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        confirmButtonText: 'Ya, Approve!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('approve_id').value = id;
            document.getElementById('approveForm').submit();
        }
    });
}
</script>
</body>
</html>