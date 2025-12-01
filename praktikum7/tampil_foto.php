<?php
require_once 'koneksi.php';

$result = $koneksi->query("SELECT id, nama, foto, uploaded_at FROM namasiswa ORDER BY id DESC");
if (!$result) {
    die('Query gagal: ' . $koneksi->error);
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Halaman Tampil Foto</title>
</head>
<body>
  <h2>Daftar Foto</h2>
  <p><a href="input_foto.php">Tambah Data</a></p>
  <table border="1" cellpadding="6" cellspacing="0">
    <tr>
      <th>No</th>
      <th>Nama</th>
      <th>Foto</th>
      <th>Tanggal</th>
      <th>Aksi</th>
    </tr>
    <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?php echo $no++; ?></td>
      <td><?php echo htmlspecialchars($row['nama']); ?></td>
      <td style="text-align:center;">
        <?php if ($row['foto'] && file_exists(__DIR__ . '/gambar/' . $row['foto'])): ?>
          <img src="gambar/<?php echo rawurlencode($row['foto']); ?>" width="80" height="120" alt="foto">
        <?php else: ?>
          (tidak ada file)
        <?php endif; ?>
      </td>
      <td><?php echo htmlspecialchars($row['uploaded_at']); ?></td>
      <td>
        <a href="delete.php?del=<?php echo (int)$row['id']; ?>" onclick="return confirm('Yakin ingin menghapus?');">DELETE</a>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
<?php
$result->free();
$koneksi->close();
