<?php
// index.php - LOGIN PAGE MODERN (FIXED: Column passwordHash)
session_start();
include('includes/db_koneksi.php');

// 1. CEK SESSION
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role == 'Admin') header("Location: dashboard_admin.php");
    elseif ($role == 'Pelanggan') header("Location: dashboard_pelanggan.php");
    elseif ($role == 'Owner') header("Location: dashboard_owner.php");
    elseif ($role == 'Fotografer') header("Location: dashboard_fotografer.php");
    exit();
}

$error = '';

// 2. PROSES LOGIN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Silakan isi username dan password.";
    } else {
        $stmt = $koneksi->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // PERBAIKAN DI SINI: Menggunakan $row['passwordHash'] sesuai database Anda
            if (password_verify($password, $row['passwordHash'])) { 
                
                $_SESSION['user_id'] = $row['userId'];
                $_SESSION['username'] = $row['username'];
                // Sesuaikan nama kolom nama lengkap jika ada, kalau tidak pakai username
                $_SESSION['name'] = $row['name'] ?? $row['username']; 
                $_SESSION['role'] = $row['role'];

                if ($row['role'] == 'Admin') header("Location: dashboard_admin.php");
                elseif ($row['role'] == 'Pelanggan') header("Location: dashboard_pelanggan.php");
                elseif ($row['role'] == 'Owner') header("Location: dashboard_owner.php");
                elseif ($row['role'] == 'Fotografer') header("Location: dashboard_fotografer.php");
                else $error = "Role akun tidak valid.";
                exit();
            } else {
                $error = "Password salah.";
            }
        } else {
            $error = "Username tidak ditemukan.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Studio Foto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            overflow-x: hidden; 
            overflow-y: auto; 
            
            /* BACKGROUND IMAGE FIXED */
            background: url('foto/back.webp') no-repeat center center fixed;
            background-size: cover;
        }

        /* --- BAGIAN 1: FORM LOGIN (Layar Penuh) --- */
        .main-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            position: relative;
        }

        /* KARTU LOGIN */
        .login-card {
            background: var(--glass);
            backdrop-filter: blur(15px);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.4);
            animation: slideIn 0.6s ease-out;
            margin-bottom: 20px;
        }

        @keyframes slideIn {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* LOGO */
        .logo-container {
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 3px solid var(--primary);
        }

        .logo-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; padding: 2px; }

        h2 { margin: 0; color: #333; font-weight: 700; font-size: 24px; }
        p.subtitle { color: #666; font-size: 14px; margin-top: 5px; margin-bottom: 30px; }

        /* FORM INPUT */
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { font-size: 13px; font-weight: 600; color: #444; margin-bottom: 8px; display: block; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; font-size: 14px; }
        .form-control {
            width: 100%; padding: 12px 15px 12px 45px;
            border: 2px solid #ddd; border-radius: 50px; font-size: 14px;
            box-sizing: border-box; font-family: 'Poppins', sans-serif; transition: 0.3s;
            background: rgba(255,255,255,0.8);
        }
        .form-control:focus { border-color: var(--primary); background: #fff; outline: none; box-shadow: 0 0 0 4px rgba(106, 90, 205, 0.15); }

        .btn-login {
            width: 100%; padding: 12px; border: none; border-radius: 50px;
            background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%);
            color: white; font-size: 16px; font-weight: 600; cursor: pointer;
            transition: 0.3s; box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3); margin-top: 10px;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(106, 90, 205, 0.4); }

        .alert-error { background: #ffe6e6; color: #d63031; padding: 12px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; border-left: 4px solid #d63031; text-align: left; display: flex; align-items: center; gap: 10px; }
        
        .footer-link { margin-top: 25px; font-size: 13px; color: #555; }
        .footer-link a { color: var(--primary); text-decoration: none; font-weight: 700; }
        .footer-link a:hover { text-decoration: underline; }

        /* SCROLL DOWN INDICATOR */
        .scroll-indicator {
            color: var(--primary);
            font-size: 12px; text-align: center; cursor: pointer;
            animation: bounce 2s infinite; margin-top: 20px;
            font-weight: 700; text-shadow: 0 1px 2px rgba(255,255,255,0.8);
        }
        .scroll-indicator i { display: block; font-size: 24px; margin-top: 5px; }
        @keyframes bounce { 0%, 20%, 50%, 80%, 100% {transform: translateY(0);} 40% {transform: translateY(-10px);} 60% {transform: translateY(-5px);} }

        /* --- BAGIAN 2: INFO / CREDITS (Scroll ke bawah) --- */
        .credits-section {
            background: #ffffff;
            color: #333;
            padding: 60px 20px;
            text-align: center;
            border-top: 5px solid var(--primary);
        }
        .credits-content { max-width: 800px; margin: 0 auto; }
        
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-bottom: 40px; text-align: left; }
        .info-item h3 { color: var(--primary); margin-bottom: 10px; font-size: 18px; }
        .info-item p { font-size: 14px; color: #666; line-height: 1.6; }

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
        <div class="login-card">
            <div class="logo-container">
                <img src="foto/logo.jpg" alt="Logo Studio" class="logo-img">
            </div>
            
            <h2>Selamat Datang di Istore</h2>
            <p class="subtitle">Silakan login ke akun Anda</p>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">MASUK SEKARANG <i class="fas fa-arrow-right" style="margin-left:5px; font-size:12px;"></i></button>
            </form>

            <div class="footer-link">
                Belum punya akun?  <a href="registrasi.php">Daftar di sini</a>
            </div>
        </div>

        <div class="scroll-indicator" onclick="document.getElementById('info').scrollIntoView({behavior: 'smooth'})">
            Info Studio & Pembuat
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>

    <div class="credits-section" id="info">
        <div class="credits-content">
            <h2 style="color:var(--primary); margin-bottom:10px;">Studio Foto Profesional</h2>
            <h1 style="color:var(--primary); margin-bottom:10px;">Istore</h1>
            <p style="color:#666; margin-bottom:40px;">Mengabadikan momen terbaik dalam hidup Anda dengan kualitas premium.</p>

            <div class="info-grid">
                <div class="info-item">
                    <h3><i class="fas fa-clock"></i> Jam Operasional</h3>
                    <p>Senin - Jumat: 09:00 - 20:00<br>Sabtu - Minggu: 08:00 - 22:00</p>
                </div>
                <div class="info-item">
                    <h3><i class="fas fa-map-marker-alt"></i> Lokasi Studio</h3>
                    <p>W4X2+GMF Balapulang Wetan, Kabupaten Tegal, Jawa Tengah.<br>(Dekat di Hati)</p>
                </div>
                <div class="info-item">
                    <h3><i class="fas fa-phone"></i> Kontak Kami</h3>
                    <p>WhatsApp: 0812-3456-7890<br>Email: info@studiofoto.com</p>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid #eee; margin:30px 0;">

            <div style="margin-bottom:20px;">
                <img src="foto/logo.jpg" alt="Dev" style="width:60px; height:60px; border-radius:50%; border:3px solid var(--primary); margin-bottom:10px;">
                <h3 style="margin:0; font-size:18px;">ENEMATIKA DWICATUR</h3>
                <p style="margin:0; font-size:13px; color:#888;">Full Stack Developer</p>
            </div>

            <div class="social-icons">
                <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" title="GitHub"><i class="fab fa-github"></i></a>
                <a href="#" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
            </div>
            
            <br>
            <p style="font-size:12px; color:#999;">&copy; <?php echo date('Y'); ?> Developed by Enematika.</p>
        </div>
    </div>

</body>
</html>