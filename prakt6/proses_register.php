<?php
require "koneksi.php";

// ambil input
$username = $_POST['username'];
$password = $_POST['password'];
$gender   = $_POST['gender'];

// cek apakah username sudah ada
$cek = $koneksi->query("SELECT * FROM login WHERE username='$username'");
if ($cek->num_rows > 0) {
    die("Username sudah digunakan. <a href='register.php'>Coba lagi</a>");
}

// hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// simpan
$sql = $koneksi->prepare("INSERT INTO login(username, password, gender) VALUES (?, ?, ?)");
$sql->bind_param("sss", $username, $hash, $gender);

if ($sql->execute()) {
    echo "User berhasil terdaftar<br>";
    echo "<a href='login.php'>Klik untuk login</a>";
} else {
    echo "Gagal mendaftar.";
}
?>
