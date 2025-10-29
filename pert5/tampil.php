<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'input';

$con = new mysqli($host, $user, $password, $database);
if ($con->connect_error) {
    die("Koneksi gagal: " . $con->connect_error);
}

$sql = "SELECT * FROM users ORDER BY username";
$result = $con->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Pengguna</title>
    <link rel="stylesheet" href="st.css">
    <style>
        table { width:90%; border-collapse:collapse; margin:24px auto; background:#fff; }
        th, td { border:1px solid #ccc; padding:8px 10px; text-align:left; }
        th { background:rgb(45, 87, 73); }
        tr:nth-child(even){ background:#f7f7f7; }
        .btn { padding:6px 8px; color:#fff; text-decoration:none; border-radius:4px; margin-right:6px; display:inline-block; }
        .edit { background:rgb(142, 221, 111); }
        .hapus{ background:rgb(161, 89, 89); }
        .tambah{ display:block; width:190px; margin:18px auto; text-align:center; background:#28a745; color:#fff; padding:10px 0; border-radius:8px; text-decoration:none;}
    </style>
</head>
<body class="body">
    <h2 style="text-align:center; margin-top:18px; ">DAFTAR DATA PENGGUNA</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr><th>Username</th><th>Email</th><th>Password</th><th>Aksi</th></tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['password']); ?></td>
                <td>
                    <a class="btn edit"  href="edit.php?id=<?php echo rawurlencode($row['username']); ?>">Edit</a>
                    <a class="btn hapus" href="hapus.php?id=<?php echo rawurlencode($row['username']); ?>">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="text-align:center;">Tidak ada data.</p>
    <?php endif; ?>

    <a class="tambah" href="form_user.html">+ Tambah Data Baru</a>
</body>
</html>

<?php
if ($result) $result->free();
$con->close();
?>
