<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Upload Gambar</title>
</head>
<body>
  <h2>Upload Gambar</h2>
  <form method="post" action="proses.php" enctype="multipart/form-data">
    <label>Nama:
      <input type="text" name="nama" id="nama" placeholder="Masukan nama" required>
    </label>
    <br><br>
    <label>Pilih Foto:
      <input type="file" name="foto" id="foto" accept=".jpg,.jpeg,.png,.gif" required>
    </label>
    <br><br>
    <button type="submit" name="kirim">Simpan</button>
  </form>

  <p><a href="tampil_foto.php">Lihat Foto yang sudah diunggah</a></p>
</body>
</html>
