<?php
// proses.php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$nama = trim($_POST['nama'] ?? '');

if ($nama === '') {
    exit('Nama masih kosong. <a href="input_foto.php">Kembali</a>');
}

// cek file ter-upload
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    exit('Terjadi kesalahan upload. Kode error: ' . ($_FILES['foto']['error'] ?? '0') . ' <a href="input_foto.php">Kembali</a>');
}

$file = $_FILES['foto'];
$tmpPath = $file['tmp_name'];
$originalName = $file['name'];
$fileSize = $file['size'];

// validasi ukuran (contoh 1MB)
$maxSize = 1_000_000; // bytes
if ($fileSize > $maxSize) {
    exit('Ukuran file terlalu besar (maks 1MB). <a href="input_foto.php">Kembali</a>');
}

// validasi ekstensi dan MIME
$allowedExtensions = ['jpg','jpeg','png','gif'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExtensions, true)) {
    exit('Tipe file tidak diperbolehkan. Hanya jpg, jpeg, png, gif. <a href="input_foto.php">Kembali</a>');
}

// cek MIME type dengan finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmpPath);
$allowedMime = [
    'image/jpeg' => ['jpg','jpeg'],
    'image/png'  => ['png'],
    'image/gif'  => ['gif']
];
if (!array_key_exists($mime, $allowedMime) || !in_array($ext, $allowedMime[$mime], true)) {
    exit('File bukan gambar yang valid. <a href="input_foto.php">Kembali</a>');
}

// buat nama file unik untuk mencegah konflik dan path traversal
$targetDir = __DIR__ . '/gambar';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}
$uniqueName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetPath = $targetDir . DIRECTORY_SEPARATOR . $uniqueName;

// pindahkan file
if (!move_uploaded_file($tmpPath, $targetPath)) {
    exit('Gagal menyimpan file di server. <a href="input_foto.php">Kembali</a>');
}

// simpan nama file ke database (gunakan prepared statement)
$sql = "INSERT INTO namasiswa (nama, foto) VALUES (?, ?)";
$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    // hapus file kalau DB gagal
    @unlink($targetPath);
    exit('Gagal menyiapkan query: ' . $koneksi->error);
}
$stmt->bind_param('ss', $nama, $uniqueName);
$ok = $stmt->execute();
if (!$ok) {
    @unlink($targetPath);
    exit('Gagal menyimpan di database: ' . $stmt->error);
}

$stmt->close();
$koneksi->close();

echo "Berhasil disimpan.<br>";
echo "Nama: " . htmlspecialchars($nama) . "<br>";
echo "<img src='gambar/" . rawurlencode($uniqueName) . "' height='200' alt='gambar'><br>";
echo "<a href='tampil_foto.php'>Lihat Semua</a>";
