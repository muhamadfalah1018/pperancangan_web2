<?php
// lupa_password.php
include('includes/db_koneksi.php');
include('includes/mailer_config.php'); // Include fungsi email

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $koneksi->real_escape_string($_POST['email']);
    
    // 1. Cek apakah email terdaftar
    $stmt = $koneksi->prepare("SELECT userId FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // 2. Buat Token Unik
        $token = bin2hex(random_bytes(50));
        $expires_at = date("Y-m-d H:i:s", time() + 3600); // Token berlaku 1 jam

        // 3. Simpan Token ke DB
        // Hapus token lama untuk email ini (opsional tapi baik)
        $koneksi->query("DELETE FROM password_resets WHERE email = '$email'"); 

        $stmt_insert = $koneksi->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("sss", $email, $token, $expires_at);
        $stmt_insert->execute();

        // 4. Kirim Email Reset Link
        $reset_link = "http://localhost/fotografi/reset_password.php?token=" . $token; // GANTI URL INI
        
        $subject = "Permintaan Reset Password Akun Studio Foto";
        $body = "
            <h2>Reset Password Anda</h2>
            <p>Anda telah meminta reset password untuk akun Anda di sistem pemesanan Studio Foto.</p>
            <p>Silakan klik tautan di bawah ini untuk mengatur ulang password Anda. Tautan ini akan kedaluwarsa dalam 1 jam.</p>
            <p><a href='$reset_link' style='background-color: #6A5ACD; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
            <p>Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.</p>
            <p>Terima kasih.</p>
        ";
        
        if (sendEmail($email, $subject, $body)) {
            $message = "Tautan reset password telah dikirimkan ke email Anda. Silakan cek kotak masuk Anda.";
        } else {
            $error = "Gagal mengirim email reset password. Silakan coba lagi.";
        }

    } else {
        $error = "Email tidak terdaftar dalam sistem kami.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lupa Password</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Lupa Password</h2>
        <p>Masukkan email Anda untuk menerima tautan reset.</p>
        
        <?php if ($message): ?>
            <p class="success"><?php echo $message; ?></p>
            <a href="index.php">Kembali ke Login</a>
        <?php elseif ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if (!$message): ?>
        <form method="POST" action="lupa_password.php">
            <input type="email" name="email" placeholder="Email Anda" required>
            <button type="submit">Kirim Tautan Reset</button>
        </form>
        <a href="index.php">Kembali ke Login</a>
        <?php endif; ?>
    </div>
</body>
</html>