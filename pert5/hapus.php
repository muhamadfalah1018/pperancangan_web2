<?php
if (!isset($_GET['id'])) {
    header("Location: tampil.php");
    exit;
}

$username = $_GET['id'];
$username_js = htmlspecialchars($username, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Hapus</title></head>
<body>
<script>
    if (confirm('Yakin ingin menghapus data user: <?php echo $username_js; ?> ?')) {
        window.location.href = 'hapus_aksi.php?id=<?php echo rawurlencode($username); ?>';
    } else {
        window.location.href = 'tampil.php';
    }
</script>
</body>
</html>
