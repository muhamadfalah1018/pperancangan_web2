<?php
// reset_password.php
include('includes/db_koneksi.php');

$token = $_GET['token'] ?? null;
$error = '';
$message = '';
$show_form = false;

if ($token) {
    // 1. Cek token di DB dan pastikan belum kedaluwarsa
    $current_time = date("Y-m-d H:i:s");
    $stmt = $koneksi->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > ?");
    $stmt->bind_param("ss", $token, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $email = $data['email'];
        $show_form = true; // Tampilkan form reset
    } else {
        $error = "Token reset tidak valid atau sudah kedaluwarsa.";
    }
}

// 2. Proses update password jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        // Ambil email lagi dari token (untuk keamanan)
        $stmt_check = $koneksi->prepare("SELECT email FROM password_resets WHERE token = ?");
        $stmt_check->bind_param("s", $token);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        
        if ($res_check->num_rows === 1) {
            $email_data = $res_check->fetch_assoc();
            $target_email = $email_data['email'];
            
            // Hash password baru
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password di tabel user
            $stmt_update = $koneksi->prepare("UPDATE user SET passwordHash = ? WHERE email = ?");
            $stmt_update->bind_param("ss", $passwordHash, $target_email);
            $stmt_update->execute();
            
            // Hapus token reset dari DB agar tidak bisa digunakan lagi
            $koneksi->query("DELETE FROM password_resets WHERE token = '$token'");
            
            $message = "Password Anda berhasil direset. Silakan login dengan password baru Anda.";
            $show_form = false;
        } else {
            $error = "Terjadi kesalahan saat verifikasi token.";
        }
    }
}

// Jika tidak ada token
if (!$token && !isset($_POST['password'])) {
    $error = "Akses tidak sah. Silakan gunakan tautan dari email Anda.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Reset Password</h2>
        
        <?php if ($message): ?>
            <p class="success"><?php echo $message; ?></p>
            <a href="index.php">Kembali ke Login</a>
        <?php elseif ($error): ?>
            <p class="error"><?php echo $error; ?></p>
            <?php if (!$show_form): ?>
                 <a href="lupa_password.php">Minta tautan reset baru</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($show_form && !$message): ?>
            <p>Masukkan password baru Anda.</p>
            <form method="POST" action="reset_password.php?token=<?php echo $token; ?>">
                <input type="password" name="password" placeholder="Password Baru" required>
                <input type="password" name="confirm_password" placeholder="Konfirmasi Password Baru" required>
                <button type="submit">Ubah Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>