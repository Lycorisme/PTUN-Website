<?php
require_once '../../config/database.php';
// session_start(); // Session sudah distart otomatis di config/database.php

// Protect admin page
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

// Set Page Title & Header
$page_title = 'Kelola Absensi';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-check-circle mr-2"></i>Absensi berhasil di-approve!
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Daftar Absensi Peserta</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Nama Peserta</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Instansi</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Tanggal</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Status</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Catatan</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Approval</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($absensi_list as $a): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4 font-semibold text-gray-900"><?= htmlspecialchars($a['nama']) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($a['instansi']) ?></td>
                        <td class="py-4 px-4 text-gray-700"><?= format_tanggal_id($a['tanggal']) ?></td>
                        <td class="py-4 px-4">
                            <?php 
                            $badge = ['hadir'=>'bg-green-100 text-green-800', 'alfa'=>'bg-red-100 text-red-800', 'izin'=>'bg-yellow-100 text-yellow-800'];
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $badge[$a['status']] ?>">
                                <?= strtoupper($a['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-gray-600 italic"><?= htmlspecialchars($a['catatan'] ?? '-') ?></td>
                        <td class="py-4 px-4 text-center">
                            <?php if($a['approved']): ?>
                                <span class="text-green-600 font-bold flex justify-center items-center gap-1">
                                    <i class="fas fa-check-circle"></i> Approved
                                </span>
                            <?php else: ?>
                                <span class="text-orange-600 font-bold flex justify-center items-center gap-1">
                                    <i class="fas fa-clock"></i> Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <?php if(!$a['approved']): ?>
                            <button onclick="approveAbsensi(<?= $a['id'] ?>, '<?= htmlspecialchars($a['nama']) ?>')" 
                                    class="bg-green-500 text-white px-4 py-2 rounded-xl hover:bg-green-600 transition-all shadow-md hover:shadow-lg">
                                <i class="fas fa-check mr-1"></i> Approve
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

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>