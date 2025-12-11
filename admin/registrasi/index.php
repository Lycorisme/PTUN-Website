<?php
require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') exit;

// --- 1. HANDLE POST ACTIONS ---
if(isset($_POST['action_type'])) {
    $type = $_POST['action_type']; // single, bulk_all
    $action = $_POST['action']; // approve, reject
    
    $status_sql = "status='aktif'";
    $notif_msg = "Selamat! Akun Anda telah diaktifkan.";
    
    // SINGLE ACTION
    if($type == 'single') {
        $id = $_POST['id'];
        if($action == 'approve') {
            db()->prepare("UPDATE users SET status='aktif' WHERE id=?")->execute([$id]);
            db()->prepare("INSERT INTO notifications (to_user_id, title, pesan, tipe) VALUES (?, 'Registrasi Disetujui', ?, 'success')")->execute([$id, $notif_msg]);
            $msg_type = "approved";
        } else {
            db()->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            $msg_type = "rejected";
        }
    } 
    // BULK ALL (GLOBAL)
    elseif($type == 'bulk_all') {
        if($action == 'approve') {
            $pendings = db()->query("SELECT id FROM users WHERE role='peserta' AND status='pending'")->fetchAll(PDO::FETCH_COLUMN);
            if($pendings) {
                db()->query("UPDATE users SET status='aktif' WHERE role='peserta' AND status='pending'");
                $notifStmt = db()->prepare("INSERT INTO notifications (to_user_id, title, pesan, tipe) VALUES (?, 'Registrasi Disetujui', ?, 'success')");
                foreach($pendings as $uid) $notifStmt->execute([$uid, $notif_msg]);
            }
            $msg_type = "all_approved";
        } else {
            db()->query("DELETE FROM users WHERE role='peserta' AND status='pending'");
            $msg_type = "all_rejected";
        }
    }

    header("Location: index.php?msg=$msg_type");
    exit;
}

// --- 2. PAGINATION & SEARCH CONFIG ---
$limit = 9; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Query Builder
$where = "WHERE role='peserta' AND status='pending'";
$params = [];

if($search) {
    $where .= " AND (nama LIKE ? OR email LIKE ? OR instansi LIKE ?)";
    $params = array_fill(0, 3, "%$search%");
}

// Hitung Total Data
$stmt = db()->prepare("SELECT COUNT(*) as total FROM users $where");
$stmt->execute($params);
$total_data = $stmt->fetch()['total'];
$total_pages = ceil($total_data / $limit);

// Ambil Data
$sql = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT $limit OFFSET $start";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$pendaftar = $stmt->fetchAll();

$page_title = 'Kelola Registrasi';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">Registrasi Peserta</h2>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-gray-500 text-sm">Menunggu Persetujuan:</span>
                <span class="bg-blue-100 text-blue-800 px-3 py-0.5 rounded-full text-xs font-bold"><?= $total_data ?></span>
            </div>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <form method="GET" class="relative w-full md:w-64">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari pendaftar..." 
                       class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:outline-none transition-all shadow-sm">
                <button type="submit" class="absolute left-3 top-3.5 text-gray-400 hover:text-green-600">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <?php if($total_data > 0 && empty($search)): ?>
            <div class="flex gap-2">
                <button onclick="confirmGlobal('approve')" class="bg-green-600 text-white px-4 py-2 rounded-xl font-bold text-sm shadow hover:bg-green-700 transition-all flex items-center whitespace-nowrap">
                    <i class="fas fa-check-double mr-2"></i> Acc Semua
                </button>
                <button onclick="confirmGlobal('reject')" class="bg-red-600 text-white px-4 py-2 rounded-xl font-bold text-sm shadow hover:bg-red-700 transition-all flex items-center whitespace-nowrap">
                    <i class="fas fa-trash mr-2"></i> Tolak Semua
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if(empty($pendaftar)): ?>
        <div class="text-center py-16 bg-white rounded-3xl border-2 border-dashed border-gray-200">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-check text-3xl text-gray-300"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-600">
                <?= $search ? 'Pencarian tidak ditemukan' : 'Tidak Ada Pendaftar Baru' ?>
            </h3>
            <p class="text-gray-400 text-sm">
                <?= $search ? "Tidak ada hasil untuk '$search'" : 'Semua pendaftar telah diproses.' ?>
            </p>
            <?php if($search): ?>
                <a href="index.php" class="mt-4 inline-block text-green-600 hover:underline font-semibold">Reset Pencarian</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($pendaftar as $p): ?>
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1 relative overflow-hidden group">
                
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-green-400 to-teal-500 opacity-10 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:scale-110"></div>

                <div class="flex items-center mb-5 relative z-10">
                    <div class="w-14 h-14 bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl flex items-center justify-center mr-4 text-gray-500 shadow-inner">
                        <i class="fas fa-user text-2xl"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h3 class="font-bold text-lg text-gray-900 truncate"><?= htmlspecialchars($p['nama']) ?></h3>
                        <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($p['email']) ?></p>
                    </div>
                </div>
                
                <div class="space-y-3 mb-6 text-sm relative z-10">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-building mt-1 text-gray-400 w-4"></i>
                        <div>
                            <p class="text-xs text-gray-400 font-semibold uppercase">Instansi</p>
                            <p class="font-medium text-gray-700"><?= htmlspecialchars($p['instansi']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-graduation-cap mt-1 text-gray-400 w-4"></i>
                        <div>
                            <p class="text-xs text-gray-400 font-semibold uppercase">Jurusan</p>
                            <p class="font-medium text-gray-700"><?= htmlspecialchars($p['jurusan']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-clock mt-1 text-gray-400 w-4"></i>
                        <div>
                            <p class="text-xs text-gray-400 font-semibold uppercase">Mendaftar</p>
                            <p class="font-medium text-gray-700"><?= date('d M Y, H:i', strtotime($p['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3 relative z-10">
                    <button onclick="processUser('approve', <?= $p['id'] ?>, '<?= htmlspecialchars($p['nama']) ?>')" 
                            class="flex-1 bg-green-500 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-green-600 transition-all shadow-md flex items-center justify-center gap-2">
                        <i class="fas fa-check"></i> Terima
                    </button>
                    <button onclick="processUser('reject', <?= $p['id'] ?>, '<?= htmlspecialchars($p['nama']) ?>')" 
                            class="flex-1 bg-white text-red-600 border border-red-200 py-2.5 rounded-xl font-bold text-sm hover:bg-red-50 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i> Tolak
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="mt-8 flex justify-center">
            <nav class="flex space-x-2 bg-white p-2 rounded-xl shadow-sm border border-gray-100">
                <?php if($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&q=<?= $search ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-bold"><</a>
                <?php endif; ?>
                
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&q=<?= $search ?>" 
                       class="w-10 h-10 flex items-center justify-center rounded-lg font-bold transition-all <?= $page == $i ? 'bg-green-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-100' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?>&q=<?= $search ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-bold">></a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<form id="actionForm" method="POST" class="hidden">
    <input type="hidden" name="action_type" id="input_type">
    <input type="hidden" name="action" id="input_action">
    <input type="hidden" name="id" id="input_id">
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Single Action
function processUser(action, id, name) {
    const isApprove = action === 'approve';
    Swal.fire({
        title: isApprove ? 'Terima Peserta?' : 'Tolak Peserta?',
        text: isApprove ? `Aktifkan akun untuk ${name}?` : `Hapus pendaftaran ${name}?`,
        icon: isApprove ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
        confirmButtonText: isApprove ? 'Ya, Terima' : 'Ya, Hapus'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('input_type').value = 'single';
            document.getElementById('input_action').value = action;
            document.getElementById('input_id').value = id;
            document.getElementById('actionForm').submit();
        }
    });
}

// Global Action
function confirmGlobal(action) {
    const isApprove = action === 'approve';
    const actionText = isApprove ? 'TERIMA SEMUA' : 'TOLAK SEMUA';
    
    Swal.fire({
        title: `Yakin ${actionText}?`,
        text: `Aksi ini akan memproses SEMUA data pending.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: isApprove ? '#16a34a' : '#dc2626',
        confirmButtonText: `Ya, ${actionText}!`
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('input_type').value = 'bulk_all';
            document.getElementById('input_action').value = action;
            document.getElementById('actionForm').submit();
        }
    });
}

// Notifications
<?php if(isset($_GET['msg'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Data telah diperbarui',
        timer: 1500,
        showConfirmButton: false
    });
    window.history.replaceState(null, null, window.location.pathname);
<?php endif; ?>
</script>

<?php require_once '../includes/sidebar.php'; ?>