<?php
require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') exit;

// HANDLE APPROVE
if(isset($_POST['approve'])) {
    $stmt = db()->prepare("UPDATE aktivitas SET approved=1, approved_at=NOW() WHERE id=?");
    $stmt->execute([$_POST['aktivitas_id']]);
    header('Location: index.php?msg=approved');
    exit;
}

// GET DATA (Table: aktivitas)
$stmt = db()->query("SELECT p.*, u.nama, u.instansi FROM aktivitas p JOIN users u ON p.peserta_id=u.id ORDER BY p.tanggal DESC LIMIT 50");
$aktivitas_list = $stmt->fetchAll();

$page_title = 'Kelola Aktivitas';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-check-circle mr-2"></i>Aktivitas berhasil di-approve!
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Daftar Aktivitas Peserta</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Nama</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Tanggal</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Deskripsi</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Jam</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Bukti</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Status</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($aktivitas_list as $p): ?>
                    <tr class="border-b hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4 font-semibold text-gray-900"><?= htmlspecialchars($p['nama']) ?></td>
                        <td class="py-4 px-4 text-gray-700"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars(substr($p['deskripsi'], 0, 50)) ?>...</td>
                        <td class="py-4 px-4 text-gray-700"><?= $p['jam_mulai'] ?> - <?= $p['jam_selesai'] ?></td>
                        <td class="py-4 px-4">
                            <?php if($p['bukti_file']): ?>
                                <a href="<?= BASE_URL . '/' . $p['bukti_file'] ?>" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors">
                                    <i class="fas fa-file"></i> Lihat
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <?php if($p['approved']): ?>
                                <span class="text-green-600 font-bold flex justify-center items-center gap-1">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            <?php else: ?>
                                <span class="text-orange-600 font-bold flex justify-center items-center gap-1">
                                    <i class="fas fa-clock"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <?php if(!$p['approved']): ?>
                            <button onclick="approveAktivitas(<?= $p['id'] ?>)" 
                                    class="bg-green-500 text-white px-4 py-2 rounded-xl hover:bg-green-600 transition-all shadow-md hover:shadow-lg">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <?php else: ?>
                                <span class="text-gray-400 font-semibold text-sm">Disetujui</span>
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
    <input type="hidden" name="aktivitas_id" id="approve_id">
    <input type="hidden" name="approve" value="1">
</form>

<script>
function approveAktivitas(id) {
    Swal.fire({
        title: 'Approve Aktivitas?',
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

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>