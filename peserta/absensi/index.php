<?php
require_once '../../config/database.php';
// session_start(); // DIHAPUS

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peserta') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$user = $_SESSION['user_data'];
$peserta_id = $user['id'];

// HANDLE CEK-IN
if(isset($_POST['checkin'])) {
    // Gunakan tanggal dari perangkat client (browser)
    $tanggal = $_POST['client_date'] ?? date('Y-m-d');
    // Validasi format tanggal
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $tanggal = date('Y-m-d');
    }
    $catatan = $_POST['catatan'] ?? '';
    
    $stmt = db()->prepare("SELECT id FROM absensi WHERE peserta_id=? AND tanggal=?");
    $stmt->execute([$peserta_id, $tanggal]);
    
    if($stmt->fetch()) {
        $error = "Anda sudah absen untuk tanggal tersebut!";
    } else {
        $stmt = db()->prepare("INSERT INTO absensi (peserta_id, tanggal, status, catatan, approved) VALUES (?, ?, 'hadir', ?, 0)");
        $stmt->execute([$peserta_id, $tanggal, $catatan]);
        header('Location: index.php?msg=success');
        exit;
    }
}

// GET DATA
$stmt = db()->prepare("SELECT * FROM absensi WHERE peserta_id=? ORDER BY tanggal DESC LIMIT 30");
$stmt->execute([$peserta_id]);
$riwayat = $stmt->fetchAll();

// Cek status hari ini akan ditentukan oleh JavaScript di client
$page_title = 'Absensi Harian';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">

    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-check-circle mr-2"></i>Absensi berhasil! Menunggu approval admin.
    </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8 shadow-sm">
        <i class="fas fa-exclamation-triangle mr-2"></i><?= $error ?>
    </div>
    <?php endif; ?>

    <div class="bg-gradient-to-br from-green-600 to-emerald-700 text-white rounded-3xl p-8 mb-8 shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10">
            <i class="fas fa-clock text-9xl"></i>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-3xl font-bold mb-1">Cek-In Kehadiran</h2>
                    <p class="text-green-100 text-lg" id="client-date-display">Memuat tanggal...</p>
                    <p class="text-green-200 text-sm mt-1" id="client-time-display"></p>
                </div>
            </div>
            
            <!-- Status absensi akan ditentukan oleh JavaScript -->
            <div id="already-checked-in" class="hidden bg-white/20 backdrop-blur-md rounded-2xl p-8 text-center border border-white/30">
                <div class="inline-flex bg-white text-green-600 rounded-full p-4 mb-4 shadow-lg">
                    <i class="fas fa-check text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold">Terima Kasih!</h3>
                <p class="text-green-50 mt-1">Anda sudah melakukan absensi hari ini.</p>
            </div>
            
            <form method="POST" id="checkin-form" class="space-y-4 bg-white/10 backdrop-blur-sm p-6 rounded-2xl border border-white/20">
                <!-- Hidden input untuk tanggal dari perangkat client -->
                <input type="hidden" name="client_date" id="client_date" value="">
                <div>
                    <label class="block text-sm font-bold mb-2 text-green-50">Catatan Harian (Opsional)</label>
                    <textarea name="catatan" rows="2" placeholder="Sedang mengerjakan apa hari ini..."
                              class="w-full px-4 py-3 border-0 rounded-xl text-gray-900 focus:ring-4 focus:ring-green-400 placeholder-gray-400 bg-white/90"></textarea>
                </div>
                <button type="submit" name="checkin" 
                        class="w-full bg-white text-green-700 py-4 rounded-xl font-bold text-lg hover:shadow-lg hover:bg-green-50 transition-all transform hover:-translate-y-1">
                    <i class="fas fa-fingerprint mr-2"></i>KLIK UNTUK ABSEN
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Riwayat 30 Hari Terakhir</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Tanggal</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Status</th>
                        <th class="text-left py-4 px-4 font-bold text-gray-700">Catatan</th>
                        <th class="text-center py-4 px-4 font-bold text-gray-700">Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($riwayat as $r): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4 font-semibold text-gray-900"><?= format_tanggal_id($r['tanggal']) ?></td>
                        <td class="py-4 px-4">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                <?= strtoupper($r['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-gray-600 italic"><?= htmlspecialchars($r['catatan'] ?? '-') ?></td>
                        <td class="py-4 px-4 text-center">
                            <?php if($r['approved']): ?>
                                <span class="text-green-600 font-bold text-sm"><i class="fas fa-check-circle"></i> Approved</span>
                            <?php else: ?>
                                <span class="text-orange-500 font-bold text-sm"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Data riwayat absensi dari server (untuk pengecekan di client-side)
const riwayatAbsensi = <?= json_encode(array_column($riwayat, 'tanggal')) ?>;

// Fungsi untuk format tanggal dalam bahasa Indonesia
function formatTanggalId(date) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('id-ID', options);
}

// Fungsi untuk format waktu
function formatWaktu(date) {
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// Fungsi untuk mendapatkan tanggal dalam format Y-m-d
function getDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Fungsi utama untuk update tampilan
function updateDisplay() {
    const now = new Date();
    const dateString = getDateString(now);
    
    // Update tampilan tanggal dan waktu
    document.getElementById('client-date-display').textContent = formatTanggalId(now);
    document.getElementById('client-time-display').textContent = 'Waktu perangkat: ' + formatWaktu(now);
    
    // Set hidden input dengan tanggal dari perangkat
    document.getElementById('client_date').value = dateString;
    
    // Cek apakah sudah absen hari ini (berdasarkan data riwayat)
    const alreadyCheckedIn = riwayatAbsensi.includes(dateString);
    
    if (alreadyCheckedIn) {
        document.getElementById('already-checked-in').classList.remove('hidden');
        document.getElementById('checkin-form').classList.add('hidden');
    } else {
        document.getElementById('already-checked-in').classList.add('hidden');
        document.getElementById('checkin-form').classList.remove('hidden');
    }
}

// Update tampilan saat halaman dimuat
updateDisplay();

// Update waktu setiap detik
setInterval(() => {
    const now = new Date();
    document.getElementById('client-time-display').textContent = 'Waktu perangkat: ' + formatWaktu(now);
    
    // Update tanggal jika berubah (misalnya melewati tengah malam)
    const currentDate = getDateString(now);
    if (document.getElementById('client_date').value !== currentDate) {
        updateDisplay();
    }
}, 1000);

<?php if(isset($_GET['msg'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: 'Absensi tercatat untuk ' + formatTanggalId(new Date()),
    timer: 2500,
    showConfirmButton: false
});
<?php endif; ?>
</script>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>