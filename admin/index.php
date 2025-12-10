<?php
// =============================================
// ADMIN DASHBOARD - PTUN WEBSITE
// C:\laragon\www\ptun-website\admin\index.php
// =============================================

require_once '../config/database.php';
session_start();

// Protect admin page
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$user = $_SESSION['user_data'];

// GET STATISTICS
$stmt = db()->query("SELECT COUNT(*) as total FROM users WHERE role='peserta'");
$total_peserta = $stmt->fetch()['total'];

$stmt = db()->query("SELECT COUNT(*) as total FROM absensi WHERE approved=0");
$pending_absensi = $stmt->fetch()['total'];

$stmt = db()->query("SELECT COUNT(*) as total FROM perkara WHERE approved=0");
$pending_perkara = $stmt->fetch()['total'];

$stmt = db()->query("SELECT COUNT(*) as total FROM laporan_harian WHERE approved=0");
$pending_laporan = $stmt->fetch()['total'];

// GET ALL PESERTA
$stmt = db()->query("SELECT id, nama, email, instansi, jurusan, status, created_at FROM users WHERE role='peserta' ORDER BY created_at DESC");
$peserta_list = $stmt->fetchAll();

// HANDLE DELETE
if(isset($_POST['delete_peserta'])) {
    $id = $_POST['peserta_id'];
    $stmt = db()->prepare("DELETE FROM users WHERE id=? AND role='peserta'");
    $stmt->execute([$id]);
    header('Location: index.php?msg=deleted');
    exit;
}

// HANDLE UPDATE STATUS
if(isset($_POST['update_status'])) {
    $id = $_POST['peserta_id'];
    $status = $_POST['status'];
    $stmt = db()->prepare("UPDATE users SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);
    header('Location: index.php?msg=updated');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= get_site_name() ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); }
    </style>
</head>
<body class="bg-gray-50">

<!-- NAVBAR -->
<nav class="gradient-bg text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-shield text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold">Admin SIM PTUN</h1>
                <p class="text-sm text-blue-100"><?= $user['nama'] ?></p>
            </div>
        </div>
        <a href="../index.php" class="bg-white/20 hover:bg-white/30 px-6 py-3 rounded-xl font-semibold transition-all">
            <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="max-w-7xl mx-auto px-6 py-8">
    
    <!-- SUCCESS MESSAGE -->
    <?php if(isset($_GET['msg'])): ?>
    <div id="successMsg" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 flex items-center">
        <i class="fas fa-check-circle text-2xl mr-4"></i>
        <span class="font-semibold">
            <?= $_GET['msg'] == 'deleted' ? 'Peserta berhasil dihapus!' : 'Data berhasil diupdate!' ?>
        </span>
    </div>
    <?php endif; ?>

    <!-- STATISTICS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Card 1 -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-3xl p-6 shadow-xl hover:shadow-2xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <span class="text-4xl font-bold"><?= $total_peserta ?></span>
            </div>
            <h3 class="text-xl font-semibold">Total Peserta</h3>
            <p class="text-blue-100 text-sm">Peserta magang aktif</p>
        </div>

        <!-- Card 2 -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-3xl p-6 shadow-xl hover:shadow-2xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-calendar-check text-2xl"></i>
                </div>
                <span class="text-4xl font-bold"><?= $pending_absensi ?></span>
            </div>
            <h3 class="text-xl font-semibold">Absensi Pending</h3>
            <p class="text-orange-100 text-sm">Menunggu approval</p>
        </div>

        <!-- Card 3 -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-3xl p-6 shadow-xl hover:shadow-2xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-file-alt text-2xl"></i>
                </div>
                <span class="text-4xl font-bold"><?= $pending_perkara ?></span>
            </div>
            <h3 class="text-xl font-semibold">Perkara Pending</h3>
            <p class="text-purple-100 text-sm">Menunggu verifikasi</p>
        </div>

        <!-- Card 4 -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-3xl p-6 shadow-xl hover:shadow-2xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-2xl"></i>
                </div>
                <span class="text-4xl font-bold"><?= $pending_laporan ?></span>
            </div>
            <h3 class="text-xl font-semibold">Laporan Pending</h3>
            <p class="text-emerald-100 text-sm">Butuh review admin</p>
        </div>
    </div>

    <!-- QUICK MENU -->
    <div class="bg-white rounded-3xl shadow-xl p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Quick Menu</h2>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <a href="setting/" class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl hover:shadow-lg transition-all text-center">
                <i class="fas fa-cog text-3xl text-blue-600 mb-2"></i>
                <p class="text-sm font-semibold text-gray-800">Settings</p>
            </a>
            <a href="registrasi/" class="p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-2xl hover:shadow-lg transition-all text-center">
                <i class="fas fa-user-plus text-3xl text-green-600 mb-2"></i>
                <p class="text-sm font-semibold text-gray-800">Registrasi</p>
            </a>
            <a href="absensi/" class="p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl hover:shadow-lg transition-all text-center">
                <i class="fas fa-calendar-day text-3xl text-orange-600 mb-2"></i>
                <p class="text-sm font-semibold text-gray-800">Absensi</p>
            </a>
            <a href="perkara/" class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl hover:shadow-lg transition-all text-center">
                <i class="fas fa-briefcase text-3xl text-purple-600 mb-2"></i>
                <p class="text-sm font-semibold text-gray-800">Perkara</p>
            </a>
            <a href="laporan/" class="p-4 bg-gradient-to-br from-pink-50 to-pink-100 rounded-2xl hover:shadow-lg transition-all text-center">
                <i class="fas fa-chart-bar text-3xl text-pink-600 mb-2"></i>
                <p class="text-sm font-semibold text-gray-800">Laporan</p>
            </a>
            <a href="sertifikat/" class="p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-2xl hover:shadow-lg transition-all text-center">
                <i class="fas fa-certificate text-3xl text-yellow-600 mb-2"></i>
                <p class="text-sm font-semibold text-gray-800">Sertifikat</p>
            </a>
        </div>
    </div>

    <!-- TABLE PESERTA -->
    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Daftar Peserta Magang</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Nama</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Email</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Instansi</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Jurusan</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Status</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($peserta_list as $p): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-all">
                        <td class="py-4 px-4 font-semibold text-gray-900"><?= htmlspecialchars($p['nama']) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($p['email']) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($p['instansi']) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($p['jurusan']) ?></td>
                        <td class="py-4 px-4">
                            <?php 
                            $badge = [
                                'aktif' => 'bg-green-100 text-green-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'selesai' => 'bg-blue-100 text-blue-800'
                            ];
                            ?>
                            <span class="px-4 py-2 rounded-full text-sm font-semibold <?= $badge[$p['status']] ?>">
                                <?= strtoupper($p['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <button onclick="editPeserta(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama']) ?>', '<?= $p['status'] ?>')" 
                                    class="bg-blue-500 text-white px-4 py-2 rounded-xl hover:bg-blue-600 transition-all mr-2">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama']) ?>')" 
                                    class="bg-red-500 text-white px-4 py-2 rounded-xl hover:bg-red-600 transition-all">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL EDIT PESERTA -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8">
        <h3 class="text-2xl font-bold text-gray-900 mb-6">Edit Status Peserta</h3>
        <form method="POST">
            <input type="hidden" name="peserta_id" id="edit_peserta_id">
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Peserta</label>
                <input type="text" id="edit_nama" readonly class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl bg-gray-50">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Status</label>
                <select name="status" id="edit_status" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500">
                    <option value="pending">Pending</option>
                    <option value="aktif">Aktif</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
            <div class="flex space-x-4">
                <button type="submit" name="update_status" class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3 rounded-xl font-bold hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-300 transition-all">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- FORM DELETE HIDDEN -->
<form id="deleteForm" method="POST" class="hidden">
    <input type="hidden" name="peserta_id" id="delete_peserta_id">
    <input type="hidden" name="delete_peserta" value="1">
</form>

<script>
// Auto hide success message
setTimeout(() => {
    const msg = document.getElementById('successMsg');
    if(msg) msg.style.display = 'none';
}, 3000);

function editPeserta(id, nama, status) {
    document.getElementById('edit_peserta_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_status').value = status;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Peserta?',
        text: `Yakin ingin menghapus "${nama}"? Data tidak dapat dikembalikan!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete_peserta_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}

// Close modal on outside click
document.getElementById('editModal').addEventListener('click', function(e) {
    if(e.target === this) closeModal();
});
</script>

</body>
</html>