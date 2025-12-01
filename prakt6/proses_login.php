<?php
session_start();
require "koneksi.php";

$username = $_POST['username'];
$password = $_POST['password'];

// cek user
$sql = $koneksi->prepare("SELECT * FROM login WHERE username=?");
$sql->bind_param("s", $username);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows === 0) {
    die("Akun tidak ditemukan. <a href='login.php'>Coba lagi</a>");
}

$user = $result->fetch_assoc();

// verifikasi password
if (!password_verify($password, $user['password'])) {
    die("Password salah. <a href='login.php'>Coba lagi</a>");
}

// simpan ke session
$_SESSION['username'] = $user['username'];
$_SESSION['gender']   = $user['gender'];

header("Location: home.php");
exit;
