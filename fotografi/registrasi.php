<?php
// registrasi.php - Fixed Validation Logic & Modern UI
include('includes/db_koneksi.php');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'Pelanggan'; 

    // 1. Validasi Input Dasar
    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error = "Semua kolom wajib diisi.";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        
        // 2. CEK DUPLIKAT (USERNAME / EMAIL) SEBELUM INSERT
        // Ini lebih aman dan pasti daripada mengandalkan error code insert
        $stmt_cek = $koneksi->prepare("SELECT userId FROM user WHERE username = ? OR email = ?");
        $stmt_cek->bind_param("ss", $username, $email);
        $stmt_cek->execute();
        $stmt_cek->store_result();
        
        if ($stmt_cek->num_rows > 0) {
            $error = "Username atau Email sudah terdaftar. Silakan gunakan yang lain.";
            $stmt_cek->close();
        } else {
            $stmt_cek->close();

            // Jika aman, lanjutkan proses insert
            
            // Hash Password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate ID Unik (CUS + Tanggal + Urutan)
            $date_part = date('dmy');
            $role_code = 'CUS'; 

            $search_pattern = $role_code . $date_part . '%';
            $stmt_count = $koneksi->prepare("SELECT COUNT(*) as total FROM user WHERE userId LIKE ?");
            $stmt_count->bind_param("s", $search_pattern);
            $stmt_count->execute();
            $count = $stmt_count->get_result()->fetch_assoc()['total'];
            $stmt_count->close();

            $new_sequence = $count + 1;
            $sequence_part = str_pad($new_sequence, 3, '0', STR_PAD_LEFT);
            $userId = $role_code . $date_part . $sequence_part;

            // INSERT DATA
            $stmt_insert = $koneksi->prepare("INSERT INTO user (userId, username, name, email, passwordHash, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("ssssss", $userId, $username, $name, $email, $passwordHash, $role);
            
           if ($stmt_insert->execute()) {
    $success = "Registrasi berhasil! ID Anda: <b>" . $userId . "</b>.<br>Silakan Login.";
    // Reset form value agar tidak muncul lagi
    $name = $username = $email = ''; 
} else {
    $error = "Registrasi gagal: " . $stmt_insert->error;
}
$stmt_insert->close();

        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Studio Foto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- STYLE GLOBAL --- */
        :root {
            --primary: #6A5ACD;
            --white: #ffffff;
            --glass: rgba(255, 255, 255, 0.9);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            /* Aktifkan Scroll */
            overflow-x: hidden;
            overflow-y: auto;
            
            /* BACKGROUND IMAGE FIXED - HANYA FOTO */
            /* Pastikan path ini sesuai dengan lokasi gambar Anda */
            background: url('foto/back.webp') no-repeat center center fixed;
            background-size: cover;
        }

        /* --- WRAPPER UTAMA (Form Section) --- */
        .main-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            position: relative;
        }

        /* KARTU REGISTRASI */
        .register-card {
            background: var(--glass);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 480px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.4);
            animation: slideIn 0.6s ease-out;
            margin-bottom: 20px;
        }

        @keyframes slideIn {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* --- LOGO STYLE --- */
        .logo-container {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 3px solid var(--primary);
        }

        .logo-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            padding: 2px;
        }

        h2 { margin: 0 0 5px 0; color: #333; font-weight: 700; font-size: 24px; }
        p.subtitle { color: #666; font-size: 14px; margin-bottom: 25px; }

        /* INPUT FIELD */
        .form-group { margin-bottom: 15px; text-align: left; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; font-size: 14px; }
        .form-control {
            width: 100%; padding: 12px 15px 12px 45px;
            border: 2px solid #ddd; border-radius: 50px; font-size: 14px;
            box-sizing: border-box; transition: 0.3s;
            font-family: 'Poppins', sans-serif; background: rgba(255,255,255,0.8);
        }
        .form-control:focus { border-color: var(--primary); background: #fff; outline: none; box-shadow: 0 0 0 4px rgba(106, 90, 205, 0.15); }

        /* BUTTON */
        .btn-register {
            width: 100%; padding: 12px; border: none; border-radius: 50px;
            background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%);
            color: white; font-size: 16px; font-weight: 600; cursor: pointer;
            transition: 0.3s; box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3); margin-top: 10px;
        }
        .btn-register:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(106, 90, 205, 0.4); }

        .links { margin-top: 25px; font-size: 13px; color: #555; }
        .links a { color: var(--primary); text-decoration: none; font-weight: 700; }
        .links a:hover { text-decoration: underline; }

        .msg { padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 10px; }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #ffe6e6; color: #d63031; border-left: 4px solid #d63031; }

        /* SCROLL DOWN INDICATOR */
        .scroll-indicator {
            /* Mengubah warna ikon scroll agar terlihat di atas foto */
            color: var(--primary); 
            font-size: 12px; text-align: center; cursor: pointer;
            animation: bounce 2s infinite; margin-top: 20px;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(255,255,255,0.8);
        }
        .scroll-indicator i { display: block; font-size: 24px; margin-top: 5px; }
        @keyframes bounce { 0%, 20%, 50%, 80%, 100% {transform: translateY(0);} 40% {transform: translateY(-10px);} 60% {transform: translateY(-5px);} }

        /* --- CREDITS SECTION (BAGIAN BAWAH) --- */
        .credits-section {
            background: #ffffff;
            color: #333;
            padding: 60px 20px;
            text-align: center;
            border-top: 5px solid var(--primary);
        }
        .credits-content { max-width: 800px; margin: 0 auto; }
        .dev-profile { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); margin-bottom: 15px; }
        .dev-name { font-size: 24px; font-weight: 700; color: var(--primary); margin: 0; }
        .dev-role { font-size: 14px; color: #666; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
        .dev-desc { font-size: 15px; line-height: 1.6; color: #555; margin-bottom: 30px; }
        
        .social-icons a { 
            display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: 50%; background: #f0f0f0; 
            color: #555; text-decoration: none; margin: 0 5px; transition: 0.3s; font-size: 18px;
        }
        .social-icons a:hover { background: var(--primary); color: white; transform: translateY(-3px); }

    </style>
</head>
<body>

    <div class="main-wrapper">
        <div class="register-card">
            
            <div class="logo-container">
                <img src="foto/logo.jpg" alt="Logo Studio" class="logo-img">
            </div>

            <h2>Buat Akun Baru</h2>
            <p class="subtitle">Lengkapi data diri Anda untuk mendaftar</p>
            
            <?php if ($success): ?>
                <div class="msg success">
                    <i class="fas fa-check-circle"></i> <span><?php echo $success; ?></span>
                </div>
                <a href="index.php" class="btn-register" style="display:block; text-decoration:none; line-height:1.5;">Masuk ke Halaman Login</a>
            <?php else: ?>
                
                <?php if ($error): ?>
                    <div class="msg error">
                        <i class="fas fa-exclamation-circle"></i> <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="registrasi.php">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" class="form-control" placeholder="Nama Lengkap" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-id-card"></i>
                            <input type="text" name="username" class="form-control" placeholder="Username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-control" placeholder="Alamat Email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-key"></i>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi Password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-register">DAFTAR SEKARANG</button>
                </form>

                <div class="links">
                    Sudah punya akun? <a href="index.php">Login di sini</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="scroll-indicator" onclick="document.getElementById('credits').scrollIntoView({behavior: 'smooth'})">
            Lihat Pembuat
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>

    <div class="credits-section" id="credits">
        <div class="credits-content">
            <img src="foto/logo.jpg" alt="Developer" class="dev-profile"> 
            
            <h2 class="dev-name">ENEMATIKA DWICATUR</h2>
            <p class="dev-role">Full Stack Developer & Owner</p>
            
            <p class="dev-desc">
                Website Sistem Informasi Manajemen Studio Foto ini dirancang dan dikembangkan dengan penuh dedikasi. 
                Tujuannya adalah untuk mempermudah proses pemesanan, pengelolaan jadwal, dan administrasi studio foto 
                agar lebih efisien dan modern. Terima kasih telah menggunakan layanan kami.
            </p>

            <div class="social-icons">
                <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" title="GitHub"><i class="fab fa-github"></i></a>
                <a href="#" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                <a href="mailto:email@example.com" title="Email"><i class="fas fa-envelope"></i></a>
            </div>
            
            <br>
            <p style="font-size:12px; color:#999;">&copy; <?php echo date('Y'); ?> Developed by Enematika Dwicatur.</p>
        </div>
    </div>

</body>
</html>