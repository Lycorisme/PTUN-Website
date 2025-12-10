<?php
require_once '../../config/database.php';
$user = protect_page('peserta');
if($_POST) {
    db()->prepare("UPDATE users SET nama=?, bio=?, instansi=? WHERE id=?")
        ->execute([$_POST['nama'], $_POST['bio'], $_POST['instansi'], $user['id']]);
    header("Location: index.php?msg=saved");
}
?>
<!DOCTYPE html>
<html><head><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50">
<div class="flex min-h-screen"><?php include '../includes/sidebar.php'; ?><div class="flex-1 p-8">
    <h1 class="text-2xl font-bold mb-6">Edit Biodata</h1>
    <form method="POST" class="bg-white p-6 rounded shadow max-w-lg">
        <label class="block mb-2">Nama Lengkap</label>
        <input type="text" name="nama" value="<?= $user['nama'] ?>" class="border p-2 rounded w-full mb-4">
        <label class="block mb-2">Instansi / Sekolah</label>
        <input type="text" name="instansi" value="<?= $user['instansi'] ?>" class="border p-2 rounded w-full mb-4">
        <label class="block mb-2">Bio / Keterangan</label>
        <textarea name="bio" class="border p-2 rounded w-full mb-4"><?= $user['bio'] ?></textarea>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
    </form>
</div></div></body></html>