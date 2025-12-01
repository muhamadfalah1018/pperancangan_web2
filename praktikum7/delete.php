<?php
require_once 'koneksi.php';

if (!isset($_GET['del'])) {
    exit('ID tidak ditemukan. <a href="tampil_foto.php">Kembali</a>');
}

$id = (int) $_GET['del'];
if ($id <= 0) {
    exit('ID tidak valid. <a href="tampil_foto.php">Kembali</a>');
}

// ambil nama file dulu
$sql = "SELECT foto FROM namasiswa WHERE id = ?";
$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    exit('Gagal menyiapkan query: ' . $koneksi->error);
}
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($fotoName);
if (!$stmt->fetch()) {
    $stmt->close();
    exit('Data tidak ditemukan. <a href="tampil_foto.php">Kembali</a>');
}
$stmt->close();

// hapus file fisik
$filePath = __DIR__ . '/gambar/' . $fotoName;
if (is_file($filePath)) {
    @unlink($filePath);
}

// hapus record DB
$stmt2 = $koneksi->prepare("DELETE FROM namasiswa WHERE id = ?");
if (!$stmt2) {
    exit('Gagal menyiapkan query delete: ' . $koneksi->error);
}
$stmt2->bind_param('i', $id);
$ok = $stmt2->execute();
$stmt2->close();
$koneksi->close();

if ($ok) {
    echo "Gambar berhasil dihapus. <a href='tampil_foto.php'>Kembali</a>";
} else {
    echo "Gagal menghapus dari database. <a href='tampil_foto.php'>Kembali</a>";
}
