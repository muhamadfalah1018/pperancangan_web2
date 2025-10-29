<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "input";

$con = new mysqli($host, $user, $password, $database);
if ($con->connect_error) {
    die("Koneksi gagal: " . $con->connect_error);
}

if (!isset($_GET['id'])) {
    header("Location: tampil.php");
    exit;
}

$username = $_GET['id'];

$stmt = $con->prepare("DELETE FROM users WHERE username = ?");
$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    $stmt->close();
    $con->close();
    header("Location: tampil.php");
    exit;
} else {
    echo "Gagal menghapus data: " . htmlspecialchars($stmt->error);
    $stmt->close();
}

$con->close();
?>
