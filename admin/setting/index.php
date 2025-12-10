<?php
// =============================================
// ADMIN SETTINGS - FULL CONFIGURATION WITH SIGNATURE UPLOADS
// =============================================

require_once '../../config/database.php';

// Cek Login Admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/login/');
    exit;
}

// HANDLE UPDATE SETTINGS
if(isset($_POST['update_settings'])) {
    $group = $_POST['group'];
    
    // FUNCTION HELPER UPLOAD
    function handle_upload($input_name, $folder, $setting_key, $group) {
        if(isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == 0) {
            $target_dir = "../../uploads/$folder/";
            if(!is_dir($target_dir)) mkdir($target_dir, 0755, true);
            
            $file_ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
            $new_filename = $input_name . '_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            // Hapus file lama jika ada
            $old_file = get_setting($setting_key);
            if($old_file && file_exists("../.." . $old_file)) {
                @unlink("../.." . $old_file);
            }

            if(move_uploaded_file($_FILES[$input_name]['tmp_name'], $target_file)) {
                $stmt = db()->prepare("INSERT INTO settings (`key`, `value`, `group`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value`=?");
                $val = '/uploads/' . $folder . '/' . $new_filename;
                $stmt->execute([$setting_key, $val, $group, $val]);
            }
        }
    }

    // 1. Handle Logo Upload
    if(isset($_FILES['logo_url']) && $_FILES['logo_url']['error'] == 0) {
        $target_dir = "../../uploads/logos/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        $file_ext = pathinfo($_FILES['logo_url']['name'], PATHINFO_EXTENSION);
        $new_filename = 'logo_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        // Hapus logo lama jika ada
        $old_logo = get_setting('logo_url');
        if($old_logo && file_exists("../.." . $old_logo)) {
            @unlink("../.." . $old_logo);
        }

        if(move_uploaded_file($_FILES['logo_url']['tmp_name'], $target_file)) {
            $stmt = db()->prepare("INSERT INTO settings (`key`, `value`, `group`) VALUES ('logo_url', ?, 'institusi') ON DUPLICATE KEY UPDATE `value`=?");
            $val = '/uploads/logos/' . $new_filename;
            $stmt->execute([$val, $val]);
        }
    }
    
    // 2. Handle Favicon
    handle_upload('favicon', 'logos', 'favicon', $group);
    
    // 3. Handle Certificate Template (Deprecated but kept for compatibility)
    handle_upload('sertifikat_bg', 'sertifikat', 'sertifikat_bg', $group);

    // 4. Handle Tanda Tangan Uploads (NEW)
    handle_upload('ttd_img_kepala', 'sertifikat', 'ttd_img_kepala', $group);
    handle_upload('ttd_img_pembimbing', 'sertifikat', 'ttd_img_pembimbing', $group);
    
    // 5. Update Text Settings
    $allowed_keys = [
        'nama_website', 'nama_panjang', 'tagline', 'alamat_lengkap', 'no_telepon', 'email_kontak',
        'kota_instansi', 'kepala_nama', 'kepala_nip', 'kepala_jabatan', 'pembimbing_nama', 'pembimbing_nip',
        'copyright_text', 'social_facebook', 'social_instagram',
        'menu_beranda', 'menu_tentang', 'menu_layanan',
        'maintenance_mode', 'absensi_start_date', 'absensi_max_hari', 'aktivitas_max_perday',
        'sertifikat_min_hadir', 'sertifikat_min_score', 'sertifikat_bobot_hadir', 'sertifikat_bobot_laporan'
    ];

    $stmt = db()->prepare("INSERT INTO settings (`key`, `value`, `group`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value`=?");
    
    foreach($_POST as $key => $value) {
        if(in_array($key, $allowed_keys)) {
            $stmt->execute([$key, $value, $group, $value]);
        }
    }
    
    header('Location: index.php?msg=updated&tab=' . $group);
    exit;
}

 $active_tab = $_GET['tab'] ?? 'institusi';
 $page_title = 'Pengaturan Sistem';
require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">

    <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-sm flex items-center">
        <i class="fas fa-check-circle text-xl mr-3"></i>
        <span class="font-semibold">Pengaturan berhasil diperbarui!</span>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden mb-8">
        <div class="flex border-b border-gray-200 overflow-x-auto bg-gray-50">
            <button onclick="showTab('institusi')" id="tab-institusi" 
                    class="tab-btn flex-1 px-6 py-4 font-bold text-gray-700 hover:bg-white hover:text-blue-600 transition-all border-b-4 border-transparent <?= $active_tab=='institusi' ? '!border-blue-600 !text-blue-600 bg-white' : '' ?>">
                <i class="fas fa-university mr-2"></i>Institusi & Tampilan
            </button>
            <button onclick="showTab('sistem')" id="tab-sistem" 
                    class="tab-btn flex-1 px-6 py-4 font-bold text-gray-700 hover:bg-white hover:text-blue-600 transition-all border-b-4 border-transparent <?= $active_tab=='sistem' ? '!border-blue-600 !text-blue-600 bg-white' : '' ?>">
                <i class="fas fa-cogs mr-2"></i>Sistem & Penilaian
            </button>
            <button onclick="showTab('tampilan')" id="tab-tampilan" 
                    class="tab-btn flex-1 px-6 py-4 font-bold text-gray-700 hover:bg-white hover:text-blue-600 transition-all border-b-4 border-transparent <?= $active_tab=='tampilan' ? '!border-blue-600 !text-blue-600 bg-white' : '' ?>">
                <i class="fas fa-desktop mr-2"></i>Tampilan & Menu
            </button>
        </div>

        <div id="content-institusi" class="tab-content p-8 <?= $active_tab!='institusi' ? 'hidden' : '' ?>">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="group" value="institusi">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-5">
                        <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Identitas Instansi</h3>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Website (Singkat)</label>
                            <input type="text" name="nama_website" value="<?= get_setting('nama_website') ?>" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap Instansi</label>
                            <input type="text" name="nama_panjang" value="<?= get_setting('nama_panjang') ?>" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Tagline / Slogan</label>
                            <input type="text" name="tagline" value="<?= get_setting('tagline') ?>" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Kota Instansi (Untuk Surat)</label>
                            <input type="text" name="kota_instansi" value="<?= get_setting('kota_instansi', 'Banjarmasin') ?>" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 focus:border-blue-500 focus:outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Logo Instansi</label>
                            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-xl border border-gray-200">
                                <?php if(get_setting('logo_url')): ?>
                                    <img src="<?= BASE_URL . get_setting('logo_url') ?>" class="h-12 w-auto object-contain">
                                <?php endif; ?>
                                <input type="file" name="logo_url" accept="image/*" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Favicon (Ikon Tab Browser)</label>
                            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-xl border border-gray-200">
                                <?php if(get_setting('favicon')): ?>
                                    <img src="<?= BASE_URL . get_setting('favicon') ?>" class="h-8 w-8 object-contain">
                                <?php endif; ?>
                                <input type="file" name="favicon" accept="image/x-icon,image/png,image/jpeg" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Pejabat Penandatangan</h3>
                        
                        <div class="bg-blue-50 p-5 rounded-2xl border border-blue-100">
                            <label class="block text-sm font-bold text-blue-800 mb-3 uppercase tracking-wider">Kepala Instansi (TTD Sertifikat)</label>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs font-semibold text-gray-500">Nama Lengkap & Gelar</label>
                                    <input type="text" name="kepala_nama" value="<?= get_setting('kepala_nama') ?>" class="w-full border border-blue-200 rounded-lg px-3 py-2">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500">NIP (Opsional)</label>
                                        <input type="text" name="kepala_nip" value="<?= get_setting('kepala_nip') ?>" class="w-full border border-blue-200 rounded-lg px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500">Jabatan</label>
                                        <input type="text" name="kepala_jabatan" value="<?= get_setting('kepala_jabatan', 'Kepala Instansi') ?>" class="w-full border border-blue-200 rounded-lg px-3 py-2">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 block mb-1">Scan Tanda Tangan (Format PNG/Transparan)</label>
                                    <div class="flex items-center gap-3 bg-white p-2 rounded-lg border border-blue-200">
                                        <?php if(get_setting('ttd_img_kepala')): ?>
                                            <img src="<?= BASE_URL . get_setting('ttd_img_kepala') ?>" class="h-10 w-auto object-contain border rounded">
                                        <?php endif; ?>
                                        <input type="file" name="ttd_img_kepala" accept="image/png,image/jpeg" class="text-xs w-full">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 p-5 rounded-2xl border border-green-100">
                            <label class="block text-sm font-bold text-green-800 mb-3 uppercase tracking-wider">Pembimbing Lapangan (TTD Nilai)</label>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs font-semibold text-gray-500">Nama Lengkap & Gelar</label>
                                    <input type="text" name="pembimbing_nama" value="<?= get_setting('pembimbing_nama') ?>" class="w-full border border-green-200 rounded-lg px-3 py-2">
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-500">NIP (Opsional)</label>
                                    <input type="text" name="pembimbing_nip" value="<?= get_setting('pembimbing_nip') ?>" class="w-full border border-green-200 rounded-lg px-3 py-2">
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-gray-500 block mb-1">Scan Tanda Tangan (Format PNG/Transparan)</label>
                                    <div class="flex items-center gap-3 bg-white p-2 rounded-lg border border-green-200">
                                        <?php if(get_setting('ttd_img_pembimbing')): ?>
                                            <img src="<?= BASE_URL . get_setting('ttd_img_pembimbing') ?>" class="h-10 w-auto object-contain border rounded">
                                        <?php endif; ?>
                                        <input type="file" name="ttd_img_pembimbing" accept="image/png,image/jpeg" class="text-xs w-full">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <button type="submit" name="update_settings" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-save mr-2"></i> Simpan Perubahan Institusi
                    </button>
                </div>
            </form>
        </div>

        <div id="content-sistem" class="tab-content p-8 <?= $active_tab!='sistem' ? 'hidden' : '' ?>">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="group" value="sistem">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div class="bg-purple-50 p-5 rounded-2xl border border-purple-100">
                            <h3 class="font-bold text-purple-900 mb-4">Pengaturan Operasional</h3>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Maksimal Hari Kerja/Bulan</label>
                                <input type="number" name="absensi_max_hari" min="1" max="31" value="<?= get_setting('absensi_max_hari', 22) ?>" class="w-full border border-purple-200 rounded-lg px-3 py-2">
                                <p class="text-xs text-gray-500 mt-1">Digunakan untuk menghitung % kehadiran.</p>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Max Aktivitas Harian</label>
                                <input type="number" name="aktivitas_max_perday" min="1" max="20" value="<?= get_setting('aktivitas_max_perday', 5) ?>" class="w-full border border-purple-200 rounded-lg px-3 py-2">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Mode Maintenance</label>
                                <select name="maintenance_mode" class="w-full border border-purple-200 rounded-lg px-3 py-2 bg-white">
                                    <option value="0" <?= get_setting('maintenance_mode')=='0' ? 'selected' : '' ?>>Website Online (Normal)</option>
                                    <option value="1" <?= get_setting('maintenance_mode')=='1' ? 'selected' : '' ?>>Website Offline (Perbaikan)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-orange-50 p-5 rounded-2xl border border-orange-100">
                            <h3 class="font-bold text-orange-900 mb-4">Bobot Penilaian & Sertifikat</h3>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Min. Kehadiran (%)</label>
                                    <input type="number" name="sertifikat_min_hadir" value="<?= get_setting('sertifikat_min_hadir', 80) ?>" class="w-full border border-orange-200 rounded-lg px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Min. Nilai Akhir</label>
                                    <input type="number" name="sertifikat_min_score" value="<?= get_setting('sertifikat_min_score', 75) ?>" class="w-full border border-orange-200 rounded-lg px-3 py-2">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Bobot Kehadiran (%)</label>
                                    <input type="number" name="sertifikat_bobot_hadir" value="<?= get_setting('sertifikat_bobot_hadir', 60) ?>" class="w-full border border-orange-200 rounded-lg px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Bobot Laporan (%)</label>
                                    <input type="number" name="sertifikat_bobot_laporan" value="<?= get_setting('sertifikat_bobot_laporan', 40) ?>" class="w-full border border-orange-200 rounded-lg px-3 py-2">
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-white rounded-lg border border-orange-200 text-xs text-gray-600">
                                <p><strong>Info:</strong> Total bobot disarankan 100%. Rumus nilai akhir sertifikat menggunakan kalkulasi otomatis berdasarkan bobot ini jika admin tidak memasukkan nilai manual.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <button type="submit" name="update_settings" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-save mr-2"></i> Simpan Pengaturan Sistem
                    </button>
                </div>
            </form>
        </div>

        <div id="content-tampilan" class="tab-content p-8 <?= $active_tab!='tampilan' ? 'hidden' : '' ?>">
            <form method="POST">
                <input type="hidden" name="group" value="tampilan">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Informasi Footer</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Teks Copyright</label>
                                <input type="text" name="copyright_text" value="<?= get_setting('copyright_text') ?>" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Alamat Lengkap (Footer)</label>
                                <textarea name="alamat_lengkap" rows="3" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2"><?= get_setting('alamat_lengkap') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">No. Telepon / WA</label>
                                <input type="text" name="no_telepon" value="<?= get_setting('no_telepon') ?>" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Email Kontak</label>
                                <input type="email" name="email_kontak" value="<?= get_setting('email_kontak') ?>" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Media Sosial & Menu</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Facebook URL</label>
                                <input type="text" name="social_facebook" value="<?= get_setting('social_facebook') ?>" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Instagram URL</label>
                                <input type="text" name="social_instagram" value="<?= get_setting('social_instagram') ?>" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2">
                            </div>
                            <hr class="my-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Label Menu Beranda</label>
                                <input type="text" name="menu_beranda" value="<?= get_setting('menu_beranda', 'Beranda|/') ?>" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 font-mono text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <button type="submit" name="update_settings" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-save mr-2"></i> Simpan Tampilan
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    
    // Reset buttons
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('!border-blue-600', '!text-blue-600', 'bg-white');
    });
    
    // Show active
    document.getElementById('content-' + tabName).classList.remove('hidden');
    document.getElementById('tab-' + tabName).classList.add('!border-blue-600', '!text-blue-600', 'bg-white');
}
</script>

<?php require_once '../includes/sidebar.php'; ?>

</body>
</html>