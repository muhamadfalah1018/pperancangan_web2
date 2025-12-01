<?php
include("conn.php");

// Hitung jumlah data
$query = mysqli_query($conn, "SELECT COUNT(userid) FROM user");
$row = mysqli_fetch_row($query);
$rows = $row[0];

// jumlah data per halaman
$page_rows = 10;

// hitung total halaman
$last = ceil($rows / $page_rows);
if ($last < 1) $last = 1;

// halaman aktif
$pagenum = isset($_GET['pn']) ? (int)$_GET['pn'] : 1;

// amanin jika user input angka aneh
if ($pagenum < 1) $pagenum = 1;
else if ($pagenum > $last) $pagenum = $last;

// LIMIT MySQL
$limit = "LIMIT " . (($pagenum - 1) * $page_rows) . "," . $page_rows;

// Query data
$nquery = mysqli_query($conn, "SELECT * FROM user $limit");

// --------- Pagination Controls ----------
$paginationCtrls = "";

if ($last != 1) {
    // Tombol previous
    if ($pagenum > 1) {
        $previous = $pagenum - 1;
        $paginationCtrls .= "<a href='?pn=$previous' class='btn btn-default'>Previous</a> &nbsp; &nbsp;";
    }

    // Link nomor halaman sebelum current
    for ($i = $pagenum - 4; $i < $pagenum; $i++) {
        if ($i > 0) {
            $paginationCtrls .= "<a href='?pn=$i' class='btn btn-default'>$i</a> &nbsp;";
        }
    }

    // Current page
    $paginationCtrls .= "<span class='btn btn-primary'>$pagenum</span> &nbsp;";

    // Link nomor halaman setelah current
    for ($i = $pagenum + 1; $i <= $last; $i++) {
        $paginationCtrls .= "<a href='?pn=$i' class='btn btn-default'>$i</a> &nbsp;";
        if ($i >= $pagenum + 4) break;
    }

    // Tombol next
    if ($pagenum != $last) {
        $next = $pagenum + 1;
        $paginationCtrls .= " &nbsp; &nbsp;<a href='?pn=$next' class='btn btn-default'>Next</a>";
    }
}
