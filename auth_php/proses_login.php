<?php
session_start();

include 'koneksi.php';
include 'config_email.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$query = mysqli_query($koneksi,
    "SELECT * FROM user WHERE username='$username' AND password='$password'"
);

$data = mysqli_fetch_assoc($query);

if ($data) {
    $_SESSION['login'] = true;
    $_SESSION['role']  = $data['role'];

    // WAJIB kirim email
    kirimEmail("BERHASIL");

    echo "EMAIL AKAN DIKIRIM (LOGIN BERHASIL)";
    header("refresh:2;url=dashboard.php");

} else {
    // WAJIB kirim email
    kirimEmail("GAGAL");

    echo "EMAIL AKAN DIKIRIM (LOGIN GAGAL)";
    header("refresh:2;url=login.php");
}
?>
