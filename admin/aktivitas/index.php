<?php
require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../index.php');
    exit;
}

// --- 1. HANDLE POST ACTIONS ---
if(isset($_POST['action_type'])) {
    $type = $_POST['action_type'];
    $status = $_POST['status']; 

    if($type == 'single') {
        $id = $_POST['id'];
        $stmt = db()->prepare("UPDATE aktivitas SET approved=?, approved_at=NOW() WHERE id=?");
        $stmt->execute([$status, $id]);
        $msg = $status ? 'approved' : 'reset';
    } 
    elseif($type == 'bulk') {
        $ids_string = $_POST['ids'];
        if(!empty($ids_string)) {
            $ids = explode(',', $ids_string);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "UPDATE aktivitas SET approved=?, approved_at=NOW() WHERE id IN ($placeholders)";
            $stmt = db()->prepare($sql);
            $params = array_merge([$status], $ids);
            $stmt->execute($params);
            $msg = 'bulk_approved';
        }
    }
    header("Location: index.php?msg=$msg");
    exit;
}

// --- 2. PAGINATION & SEARCH ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build Query
$where_query = "WHERE 1=1";
$params = [];

if($search) {
    $where_query .= " AND (u.nama LIKE ? OR ak.deskripsi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count Total
$count_sql = "SELECT COUNT(*) as total FROM aktivitas ak JOIN users u ON ak.peserta_id=u.id $where_query";
$stmt_count = db()->prepare($count_sql);
$stmt_count->execute($params);
$total_results = $stmt_count->fetch()['total'];
$total_pages = ceil($total_results / $limit);

// Get Data
$sql = "SELECT ak.*, u.nama, u.instansi FROM aktivitas ak 
        JOIN users u ON ak.peserta_id=u.id 
        $where_query 
        ORDER BY ak.tanggal DESC, ak.jam_mulai DESC 
        LIMIT $limit OFFSET $start";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$aktivitas_list = $stmt->fetchAll();

$page_title = 'Verifikasi Jurnal';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Verifikasi Jurnal Kegiatan</h2>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <form method="GET" class="relative w-full sm:w-64">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama/kegiatan..." 
                       class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:outline-none transition-all shadow-sm">
                <button type="submit" class="absolute left-3 top-3.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <button onclick="bulkApprove()" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-md hover:shadow-lg transition-all flex items-center justify-center text-sm whitespace-nowrap">
                <i class="fas fa-check-double mr-2"></i> Approve Terpilih
            </button>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 min-h-[400px]">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 tracking-wider">
                        <th class="p-4 w-10 text-center">
                            <input type="checkbox" id="checkAll" class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500 cursor-pointer">
                        </th>
                        <th class="p-4 font-bold">Peserta</th>
                        <th class="p-4 font-bold">Waktu</th>
                        <th class="p-4 font-bold w-1/3">Deskripsi</th>
                        <th class="p-4 font-bold text-center">Bukti</th>
                        <th class="p-4 font-bold text-center">Status</th>
                        <th class="p-4 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if(count($aktivitas_list) > 0): ?>
                        <?php foreach($aktivitas_list as $a): ?>
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="p-4 text-center">
                                <?php if(!$a['approved']): ?>
                                    <input type="checkbox" name="ids[]" value="<?= $a['id'] ?>" class="row-checkbox w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500 cursor-pointer">
                                <?php else: ?>
                                    <i class="fas fa-check text-gray-300"></i>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-gray-800"><?= htmlspecialchars($a['nama']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($a['instansi']) ?></p>
                            </td>
                            <td class="p-4 whitespace-nowrap text-sm text-gray-600">
                                <div class="font-bold text-gray-700"><?= date('d M Y', strtotime($a['tanggal'])) ?></div>
                                <div class="text-xs"><?= $a['jam_mulai'] ?> - <?= $a['jam_selesai'] ?></div>
                            </td>
                            <td class="p-4 text-sm text-gray-600">
                                <?= nl2br(htmlspecialchars(substr($a['deskripsi'], 0, 100))) ?>
                                <?= strlen($a['deskripsi']) > 100 ? '...' : '' ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if($a['bukti_file']): ?>
                                    <a href="../../<?= $a['bukti_file'] ?>" target="_blank" class="text-purple-600 hover:text-purple-800 text-sm font-semibold underline">
                                        Lihat
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs italic">Tidak ada</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if($a['approved']): ?>
                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">Approved</span>
                                <?php else: ?>
                                    <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-bold">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if(!$a['approved']): ?>
                                    <button onclick="singleAction(<?= $a['id'] ?>, 1, '<?= htmlspecialchars($a['nama']) ?>')" class="bg-purple-600 text-white p-2 rounded-lg hover:bg-purple-700 shadow-md">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php else: ?>
                                    <button onclick="singleAction(<?= $a['id'] ?>, 0, '<?= htmlspecialchars($a['nama']) ?>')" class="bg-gray-200 text-gray-500 p-2 rounded-lg hover:bg-gray-300">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-16 text-center">
                                <div class="flex flex-col items-center justify-center opacity-70">
                                    <i class="fas fa-box-open text-6xl text-gray-200 mb-4"></i>
                                    <h3 class="text-xl font-bold text-gray-600">Zona Kosong!</h3>
                                    <p class="text-gray-400">
                                        <?= $search ? 'Kata kunci "<b>'.$search.'</b>" tidak membuahkan hasil.' : 'Belum ada peserta yang setor jurnal.' ?>
                                    </p>
                                    <?php if($search): ?>
                                        <a href="index.php" class="mt-4 text-purple-600 hover:underline">Reset</a>
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
            <span class="text-sm text-gray-500">Hal <?= $page ?> dari <?= $total_pages ?></span>
            <div class="flex space-x-1">
                <?php if($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&q=<?= $search ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-100">Prev</a>
                <?php endif; ?>
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&q=<?= $search ?>" class="px-3 py-1 rounded-md border <?= $page == $i ? 'bg-purple-600 text-white border-purple-600' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-100' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?>&q=<?= $search ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-100">Next</a>
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
document.getElementById('checkAll').addEventListener('change', function() {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
});

function singleAction(id, status, nama) {
    Swal.fire({
        title: status ? 'Approve?' : 'Reset?',
        text: `Jurnal milik: ${nama}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#9333ea',
        confirmButtonText: 'Ya!'
    }).then((res) => {
        if(res.isConfirmed) {
            document.getElementById('action_type').value = 'single';
            document.getElementById('action_id').value = id;
            document.getElementById('action_status').value = status;
            document.getElementById('actionForm').submit();
        }
    });
}

function bulkApprove() {
    const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    if(selected.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Ups!', 
            text: 'Pilih minimal satu jurnal untuk di-approve.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    Swal.fire({
        title: 'Approve Jurnal Terpilih?',
        text: 'Yakin mau approve semua yang dicentang?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#9333ea',
        confirmButtonText: 'Gas, Approve!'
    }).then((res) => {
        if(res.isConfirmed) {
            document.getElementById('action_type').value = 'bulk';
            document.getElementById('action_ids').value = selected.join(',');
            document.getElementById('action_status').value = 1;
            document.getElementById('actionForm').submit();
        }
    });
}

<?php if(isset($_GET['msg'])): ?>
    Swal.fire({ icon: 'success', title: 'Sukses', timer: 1500, showConfirmButton: false });
    window.history.replaceState(null, null, window.location.pathname);
<?php endif; ?>
</script>

<?php require_once '../includes/sidebar.php'; ?>