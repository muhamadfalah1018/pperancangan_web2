<?php
// koneksi.php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';        // isi sesuai environment Anda
$DB_NAME = 'foto';

$koneksi = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($koneksi->connect_errno) {
    // jangan tampilkan password/detil sensitif di produksi
    die("Koneksi gagal: (" . $koneksi->connect_errno . ") " . $koneksi->connect_error);
}

// set charset
$koneksi->set_charset("utf8mb4");
