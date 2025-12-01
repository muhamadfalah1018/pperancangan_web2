<?php
$koneksi = new mysqli("localhost", "root", "", "coba");

if ($koneksi->connect_errno) {
    die("Gagal koneksi: " . $koneksi->connect_error);
}
?>
