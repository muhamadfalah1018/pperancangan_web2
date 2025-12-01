<?php
session_start();

// cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head><title>Home</title></head>
<body>

<h2>SELAMAT DATANG!</h2>

<?php
echo "Halo, <b>" . $_SESSION['username'] . "</b><br>";
echo "Gender: " . $_SESSION['gender'] . "<br>";
?>

<br>
<a href="logout.php">Logout</a>

</body>
</html>
