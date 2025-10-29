<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "input";

$con = new mysqli($host, $user, $password, $database);
if ($con->connect_error) {
    die("Koneksi gagal: " . $con->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = trim($_POST['password'] ?? '');

    if ($username === '' || $email === '') {
        echo "<script>alert('Username dan email wajib diisi'); window.history.back();</script>";
        exit;
    }

    // Cek duplicate email
    $stmt = $con->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        echo "<script>alert('Email sudah terdaftar. Gunakan email lain.'); window.location.href='form_user.html';</script>";
        exit;
    }
    $stmt->close();

    // Insert data (prepared)
    $stmt = $con->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $pass);
    if ($stmt->execute()) {
        $stmt->close();
        $con->close();
        header("Location: tampil.php");
        exit;
    } else {
        echo "Error: " . htmlspecialchars($stmt->error);
        $stmt->close();
    }
}

$con->close();
?>
