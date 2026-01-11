<?php
// includes/db_koneksi.php

$host = 'localhost';
$username = 'root'; // Ganti dengan username DB Anda
$password = '';     // Ganti dengan password DB Anda
$database = 'studiofotodb'; // Nama database Anda

// Membuat koneksi
$koneksi = new mysqli($host, $username, $password, $database);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
// Tidak perlu echo "Koneksi berhasil!"; di sini
?>