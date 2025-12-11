<?php
require_once '../../config/database.php';

// Protect admin page
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../index.php');
    exit;
}

// --- 1. HANDLE POST ACTIONS (SINGLE & BULK) ---
if(isset($_POST['action_type'])) {
    $type = $_POST['action_type'];
    $status = $_POST['status']; // 1 = Approve, 0 = Pending/Reset

    if($type == 'single') {
        $id = $_POST['id'];
        $stmt = db()->prepare("UPDATE absensi SET approved=?, approved_at=NOW() WHERE id=?");
        $stmt->execute([$status, $id]);
        $msg = $status ? 'approved' : 'reset';
    } 
    elseif($type == 'bulk') {
        $ids_string = $_POST['ids'];
        if(!empty($ids_string)) {
            $ids = explode(',', $ids_string);
            // Buat placeholder (?,?,?) sesuai jumlah ID
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "UPDATE absensi SET approved=?, approved_at=NOW() WHERE id IN ($placeholders)";
            $stmt = db()->prepare($sql);
            // Gabungkan status dan array IDs untuk eksekusi
            $params = array_merge([$status], $ids);
            $stmt->execute($params);
            $msg = 'bulk_approved';
        }
    }
    
    // Redirect untuk membersihkan POST
    header("Location: index.php?msg=$msg");
    exit;
}

// --- 2. SETUP PAGINATION & SEARCH ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build Query Logic
$where_query = "WHERE 1=1";
$params = [];

if($search) {
    $where_query .= " AND u.nama LIKE ?";
    $params[] = "%$search%";
}

// Hitung Total Data (untuk pagination)
$count_sql = "SELECT COUNT(*) as total FROM absensi a JOIN users u ON a.peserta_id=u.id $where_query";
$stmt_count = db()->prepare($count_sql);
$stmt_count->execute($params);
$total_results = $stmt_count->fetch()['total'];
$total_pages = ceil($total_results / $limit);

// Ambil Data Sebenarnya
$sql = "SELECT a.*, u.nama, u.instansi FROM absensi a 
        JOIN users u ON a.peserta_id=u.id 
        $where_query 
        ORDER BY a.tanggal DESC, a.created_at DESC 
        LIMIT $limit OFFSET $start"; // Menggunakan OFFSET agar aman dengan bind params

$stmt = db()->prepare($sql);
$stmt->execute($params);
$absensi_list = $stmt->fetchAll();

$page_title = 'Kelola Absensi';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Verifikasi Absensi</h2>
            <p class="text-gray-500 text-sm">Total Data: <?= $total_results ?></p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <form method="GET" class="relative w-full sm:w-64">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama peserta..." 
                       class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-all shadow-sm">
                <button type="submit" class="absolute left-3 top-3.5 text-gray-400 hover:text-blue-500">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <button onclick="bulkApprove()" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-md hover:shadow-lg transition-all flex items-center justify-center text-sm whitespace-nowrap">
                <i class="fas fa-check-double mr-2"></i> Approve Terpilih
            </button>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 relative min-h-[400px]">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 tracking-wider">
                        <th class="p-4 w-10 text-center">
                            <input type="checkbox" id="checkAll" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
                        </th>
                        <th class="p-4 font-bold">Peserta</th>
                        <th class="p-4 font-bold">Tanggal</th>
                        <th class="p-4 font-bold text-center">Status</th>
                        <th class="p-4 font-bold">Catatan</th>
                        <th class="p-4 font-bold text-center">Verifikasi</th>
                        <th class="p-4 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if(count($absensi_list) > 0): ?>
                        <?php foreach($absensi_list as $a): ?>
                        <tr class="hover:bg-blue-50/30 transition-colors duration-200">
                            <td class="p-4 text-center">
                                <?php if(!$a['approved']): ?>
                                    <input type="checkbox" name="ids[]" value="<?= $a['id'] ?>" class="row-checkbox w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
                                <?php else: ?>
                                    <i class="fas fa-check text-gray-300 text-xs"></i>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-gray-800"><?= htmlspecialchars($a['nama']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($a['instansi']) ?></p>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2 text-gray-700">
                                    <i class="fas fa-calendar-alt text-blue-400"></i>
                                    <?= date('d M Y', strtotime($a['tanggal'])) ?>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <?php 
                                $badge = [
                                    'hadir' => 'bg-green-100 text-green-700', 
                                    'alfa' => 'bg-red-100 text-red-700', 
                                    'izin' => 'bg-yellow-100 text-yellow-700'
                                ]; 
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $badge[$a['status']] ?? 'bg-gray-100' ?>">
                                    <?= $a['status'] ?>
                                </span>
                            </td>
                            <td class="p-4 text-sm text-gray-600 italic max-w-xs truncate">
                                <?= htmlspecialchars($a['catatan'] ?? '-') ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if($a['approved']): ?>
                                    <span class="inline-flex items-center gap-1 text-green-600 font-bold text-sm bg-green-50 px-3 py-1 rounded-lg">
                                        <i class="fas fa-check-circle"></i> Approved
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-orange-500 font-bold text-sm bg-orange-50 px-3 py-1 rounded-lg animate-pulse">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if(!$a['approved']): ?>
                                    <button onclick="singleAction(<?= $a['id'] ?>, 1, '<?= htmlspecialchars($a['nama']) ?>')" 
                                            class="bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition-all shadow-md tooltip" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php else: ?>
                                    <button onclick="singleAction(<?= $a['id'] ?>, 0, '<?= htmlspecialchars($a['nama']) ?>')" 
                                            class="bg-gray-200 text-gray-500 p-2 rounded-lg hover:bg-gray-300 transition-all shadow-sm tooltip" title="Reset">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-12 text-center">
                                <div class="flex flex-col items-center justify-center opacity-70">
                                    <div class="bg-gray-100 p-4 rounded-full mb-3">
                                        <i class="fas fa-wind text-4xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-600">Ups, data menghilang?</h3>
                                    <p class="text-gray-400 text-sm max-w-xs">
                                        <?= $search ? 'Tidak ada peserta dengan nama "<b>'.htmlspecialchars($search).'</b>". Coba kata kunci lain.' : 'Belum ada data absensi yang masuk.' ?>
                                    </p>
                                    <?php if($search): ?>
                                        <a href="index.php" class="mt-4 text-blue-600 hover:underline font-medium text-sm">Kembali ke Semua Data</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-between items-center">
            <span class="text-sm text-gray-500">Halaman <?= $page ?> dari <?= $total_pages ?></span>
            <div class="flex space-x-1">
                <?php if($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&q=<?= $search ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 transition-all">Prev</a>
                <?php endif; ?>
                
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&q=<?= $search ?>" class="px-3 py-1 rounded-md border transition-all <?= $page == $i ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-100' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?>&q=<?= $search ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 transition-all">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<form id="actionForm" method="POST" class="hidden">
    <input type="hidden" name="action_type" id="action_type">
    <input type="hidden" name="id" id="action_id">
    <input type="hidden" name="ids" id="action_ids">
    <input type="hidden" name="status" id="action_status">
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Logic Check All
document.getElementById('checkAll').addEventListener('change', function() {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
});

// Logic Single Action
function singleAction(id, status, nama) {
    Swal.fire({
        title: status ? 'Approve Absensi?' : 'Reset Status?',
        text: `Peserta: ${nama}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Ya, Lanjutkan'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('action_type').value = 'single';
            document.getElementById('action_id').value = id;
            document.getElementById('action_status').value = status;
            document.getElementById('actionForm').submit();
        }
    });
}

// Logic Bulk Approve
function bulkApprove() {
    // Ambil semua value dari checkbox yang dicentang
    const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    
    // Validasi jika kosong
    if(selected.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Eits, Tunggu Dulu!',
            text: 'Kamu belum memilih data apapun. Centang dulu checkbox di sebelah kiri nama peserta.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Konfirmasi SweetAlert
    Swal.fire({
        title: `Approve ${selected.length} Data Terpilih?`,
        text: "Semua data yang kamu centang akan langsung disetujui.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Approve Semua!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('action_type').value = 'bulk';
            document.getElementById('action_ids').value = selected.join(',');
            document.getElementById('action_status').value = 1;
            document.getElementById('actionForm').submit();
        }
    });
}

// Success Notification
<?php if(isset($_GET['msg'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Data telah diperbarui.',
        timer: 1500,
        showConfirmButton: false
    });
    // Bersihkan URL
    window.history.replaceState(null, null, window.location.pathname);
<?php endif; ?>
</script>

<?php require_once '../includes/sidebar.php'; ?>