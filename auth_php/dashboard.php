<?php
session_start();

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}
?>

<h2>Dashboard</h2>

<?php
if($_SESSION['role'] == 'admin'){
    echo "Halo Admin!";
} else {
    echo "Halo User!";
}
?>
