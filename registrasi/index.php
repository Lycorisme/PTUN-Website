<?php
require_once '../../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') exit;

// 1. HANDLE POST ACTIONS
if(isset($_POST['action_type'])) {
    $type = $_POST['action_type']; // single, bulk_selected, bulk_all
    $action = $_POST['action']; // approve, reject
    
    // Tentukan Status & Pesan
    if($action == 'approve') {
        $status_sql = "status='aktif'";
        $notif_msg = "Selamat! Akun Anda telah diaktifkan.";
        $msg_url = "approved";
    } else {
        // Untuk reject kita DELETE
        $status_sql = null; // Logic delete beda
        $msg_url = "rejected";
    }

    // Logic EXECUTION
    if($type == 'single') {
        $id = $_POST['id'];
        if($action == 'approve') {
            db()->prepare("UPDATE users SET status='aktif' WHERE id=?")->execute([$id]);
            db()->prepare("INSERT INTO notifications (to_user_id, title, pesan, tipe) VALUES (?, 'Registrasi Disetujui', ?, 'success')")->execute([$id, $notif_msg]);
        } else {
            db()->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        }
    } 
    elseif($type == 'bulk_selected') {
        $ids = explode(',', $_POST['ids']);
        if(!empty($ids)) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            if($action == 'approve') {
                db()->prepare("UPDATE users SET status='aktif' WHERE id IN ($placeholders)")->execute($ids);
                $notifStmt = db()->prepare("INSERT INTO notifications (to_user_id, title, pesan, tipe) VALUES (?, 'Registrasi Disetujui', ?, 'success')");
                foreach($ids as $uid) $notifStmt->execute([$uid, $notif_msg]);
            } else {
                db()->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute($ids);
            }
        }
    }
    elseif($type == 'bulk_all') {
        // Logic Acc All / Reject All (Global)
        if($action == 'approve') {
            // Ambil ID pending dulu untuk notif
            $pendings = db()->query("SELECT id FROM users WHERE role='peserta' AND status='pending'")->fetchAll(PDO::FETCH_COLUMN);
            if($pendings) {
                db()->query("UPDATE users SET status='aktif' WHERE role='peserta' AND status='pending'");
                $notifStmt = db()->prepare("INSERT INTO notifications (to_user_id, title, pesan, tipe) VALUES (?, 'Registrasi Disetujui', ?, 'success')");
                foreach($pendings as $uid) $notifStmt->execute([$uid, $notif_msg]);
            }
            $msg_url = "all_approved";
        } else {
            db()->query("DELETE FROM users WHERE role='peserta' AND status='pending'");
            $msg_url = "all_rejected";
        }
    }

    header("Location: index.php?msg=$msg_url");
    exit;
}

// 2. CONFIG PAGINATION & SEARCH
$limit = 9; // Grid 3x3 layout
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = "WHERE role='peserta' AND status='pending'";
$params = [];

if($search) {
    $where .= " AND (nama LIKE ? OR email LIKE ? OR instansi LIKE ?)";
    $params = array_fill(0, 3, "%$search%");
}

// Count Total
$stmt = db()->prepare("SELECT COUNT(*) as total FROM users $where");
$stmt->execute($params);
$total_pending = $stmt->fetch()['total'];
$total_pages = ceil($total_pending / $limit);

// Get Data
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
            <h2 class="text-2xl font-bold text-gray-800">Registrasi Peserta</h2>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-gray-500 text-sm">Menunggu Persetujuan:</span>
                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs font-bold"><?= $total_pending ?></span>
            </div>
        </div>
        
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
            <form method="GET" class="relative w-full md:w-64">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari pendaftar..." 
                       class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:outline-none transition-all shadow-sm">
                <button type="submit" class="absolute left-3 top-3.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            
            <?php if($total_pending > 0): ?>
            <div class="flex gap-2">
                <button onclick="bulkActionSelected('approve')" class="bg-blue-600 text-white px-4 py-2 rounded-xl font-bold text-sm shadow hover:bg-blue-700 transition-all whitespace-nowrap">
                    <i class="fas fa-check-square mr-1"></i> Acc Selected
                </button>

                <button onclick="confirmGlobal('approve')" class="bg-green-600 text-white px-4 py-2 rounded-xl font-bold text-sm shadow hover:bg-green-700 transition-all whitespace-nowrap">
                    <i class="fas fa-check-double mr-1"></i> Acc All
                </button>
                <button onclick="confirmGlobal('reject')" class="bg-red-600 text-white px-4 py-2 rounded-xl font-bold text-sm shadow hover:bg-red-700 transition-all whitespace-nowrap">
                    <i class="fas fa-trash mr-1"></i> Reject All
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
                <?= $search ? 'Coba kata kunci lain.' : 'Semua pendaftar telah diproses.' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="cardContainer">
            <?php foreach($pendaftar as $p): ?>
            <div class="card-item bg-white rounded-2xl p-6 border border-gray-100 shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1 relative overflow-hidden group">
                <div class="absolute top-4 left-4 z-20">
                    <input type="checkbox" name="ids[]" value="<?= $p['id'] ?>" class="card-checkbox w-5 h-5 text-blue-600 border-2 border-gray-300 rounded-full focus:ring-blue-500 cursor-pointer transition-all checked:bg-blue-600 checked:border-transparent">
                </div>

                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-green-400 to-teal-500 opacity-10 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:scale-110"></div>

                <div class="flex items-center mb-5 relative z-10 pl-8"> <div class="w-14 h-14 bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl flex items-center justify-center mr-4 text-gray-500 shadow-inner">
                        <i class="fas fa-user text-2xl"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h3 class="font-bold text-lg text-gray-900 truncate"><?= htmlspecialchars($p['nama']) ?></h3>
                        <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($p['email']) ?></p>
                    </div>
                </div>
                
                <div class="space-y-3 mb-6 text-sm relative z-10">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-building mt-1 text-gray-400"></i>
                        <div>
                            <p class="text-xs text-gray-400 font-semibold uppercase">Instansi</p>
                            <p class="font-medium text-gray-700"><?= htmlspecialchars($p['instansi']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-clock mt-1 text-gray-400"></i>
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
                            class="flex-1 bg-red-50 text-red-600 border border-red-100 py-2.5 rounded-xl font-bold text-sm hover:bg-red-100 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i> Tolak
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="mt-8 flex justify-center">
            <div class="flex space-x-2 bg-white p-2 rounded-xl shadow-sm border border-gray-100">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&q=<?= $search ?>" 
                       class="w-10 h-10 flex items-center justify-center rounded-lg font-bold transition-all <?= $page == $i ? 'bg-blue-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-100' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<form id="actionForm" method="POST" class="hidden">
    <input type="hidden" name="action_type" id="input_type"> <input type="hidden" name="action" id="input_action"> <input type="hidden" name="id" id="input_id">
    <input type="hidden" name="ids" id="input_ids">
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// 1. Single Action
function processUser(action, id, name) {
    const isApprove = action === 'approve';
    Swal.fire({
        title: isApprove ? 'Terima Peserta?' : 'Tolak Peserta?',
        text: `Konfirmasi untuk: ${name}`,
        icon: isApprove ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
        confirmButtonText: isApprove ? 'Ya, Terima' : 'Ya, Hapus'
    }).then((result) => {
        if (result.isConfirmed) {
            submitForm('single', action, id, null);
        }
    });
}

// 2. Bulk Action by Checkbox (Selected)
function bulkActionSelected(action) {
    const selected = Array.from(document.querySelectorAll('.card-checkbox:checked')).map(cb => cb.value);
    
    if(selected.length === 0) {
        Swal.fire('Oops!', 'Pilih (centang) peserta pada card terlebih dahulu!', 'warning');
        return;
    }

    const actionText = action === 'approve' ? 'Terima' : 'Tolak';
    Swal.fire({
        title: `${actionText} ${selected.length} Peserta Terpilih?`,
        text: "Hanya data yang dicentang yang akan diproses.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        confirmButtonText: `Ya, ${actionText} Terpilih`
    }).then((result) => {
        if (result.isConfirmed) {
            submitForm('bulk_selected', action, null, selected.join(','));
        }
    });
}

// 3. Global Action (All Pending)
function confirmGlobal(action) {
    const isApprove = action === 'approve';
    const actionText = isApprove ? 'TERIMA SEMUA' : 'TOLAK SEMUA';
    
    Swal.fire({
        title: `Yakin ${actionText}?`,
        text: `Aksi ini akan memproses SEMUA <?= $total_pending ?> data pending (termasuk yang tidak tampil di halaman ini).`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: isApprove ? '#16a34a' : '#dc2626',
        confirmButtonText: `Ya, ${actionText}!`
    }).then((result) => {
        if (result.isConfirmed) {
            submitForm('bulk_all', action, null, null);
        }
    });
}

// Helper Submit
function submitForm(type, action, id, ids) {
    document.getElementById('input_type').value = type;
    document.getElementById('input_action').value = action;
    if(id) document.getElementById('input_id').value = id;
    if(ids) document.getElementById('input_ids').value = ids;
    document.getElementById('actionForm').submit();
}

// Notification Handling
<?php if(isset($_GET['msg'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Selesai',
        text: 'Aksi berhasil dilakukan',
        timer: 1500,
        showConfirmButton: false
    });
    window.history.replaceState(null, null, window.location.pathname);
<?php endif; ?>
</script>

<?php require_once '../includes/sidebar.php'; ?>