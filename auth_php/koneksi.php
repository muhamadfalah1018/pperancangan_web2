<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "praktikum_auth";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if(!$koneksi){
    echo "Koneksi gagal";
}
?>
