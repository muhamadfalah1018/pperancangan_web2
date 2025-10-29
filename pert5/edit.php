<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "input";

$con = new mysqli($host, $user, $password, $database);
if ($con->connect_error) {
    die("Koneksi gagal: " . $con->connect_error);
}

// Pastikan param id ada
if (!isset($_GET['id'])) {
    header("Location: tampil.php");
    exit;
}

$username_key = $_GET['id'];

// ambil data
$stmt = $con->prepare("SELECT username, email, password FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username_key);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $stmt->close();
    $con->close();
    echo "Data tidak ditemukan. <a href='tampil.php'>Kembali</a>";
    exit;
}
$data = $res->fetch_assoc();
$stmt->close();

// jika submit update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    $stmt = $con->prepare("UPDATE users SET email = ?, password = ? WHERE username = ?");
    $stmt->bind_param("sss", $email, $pass, $username_key);
    if ($stmt->execute()) {
        $stmt->close();
        $con->close();
        header("Location: tampil.php");
        exit;
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo "Gagal memperbarui data: " . htmlspecialchars($err) . " <a href='tampil.php'>Kembali</a>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data</title>
    <link rel="stylesheet" href="st.css">
</head>
<body class="body">
<div class="bungkus">
    <h2>Edit Data Pengguna</h2>
    <form method="post">
        <div class="kotak">
            <label>Username:</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($data['username']); ?>" readonly>
        </div>
        <div class="kotak">
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($data['email']); ?>" required>
        </div>
        <div class="kotak">
            <label>Password:</label>
            <input type="text" name="password" value="<?php echo htmlspecialchars($data['password']); ?>" required>
        </div>
        <div style="text-align:center;">
            <button class="button" type="submit" name="update">Update</button>
            <a class="button" href="tampil.php" style="text-decoration:none; padding:6px 10px; display:inline-block; margin-left:6px;">Batal</a>
        </div>
    </form>
</div>
</body>
</html>
<?php $con->close(); ?>
