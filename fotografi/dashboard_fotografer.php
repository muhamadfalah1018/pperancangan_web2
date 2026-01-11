<?php
// dashboard_fotografer.php - ELEGANT UI + PHOTOGRAPHER BADGE
session_start();
include('includes/db_koneksi.php');

// Cek autentikasi dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Fotografer') {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$fotografer_name = $_SESSION['name'] ?? 'Fotografer';
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// --- QUERY DATA RINGKASAN KHUSUS FOTOGRAFER ---

// 1. Jadwal Hari Ini
$q_today = $koneksi->query("SELECT COUNT(*) as total FROM jadwal WHERE tanggal = '$today'");
$jadwal_hari_ini = $q_today->fetch_assoc()['total'] ?? 0;

// 2. Jadwal Besok
$q_tomorrow = $koneksi->query("SELECT COUNT(*) as total FROM jadwal WHERE tanggal = '$tomorrow'");
$jadwal_besok = $q_tomorrow->fetch_assoc()['total'] ?? 0;

// 3. Total Selesai
$q_selesai = $koneksi->query("SELECT COUNT(*) as total FROM pemesanan WHERE statusPesanan = 'Selesai'");
$total_selesai = $q_selesai->fetch_assoc()['total'] ?? 0;

// 4. Total Jadwal Aktif
$q_aktif = $koneksi->query("SELECT COUNT(*) as total FROM pemesanan WHERE statusPesanan = 'Terjadwal'");
$total_aktif = $q_aktif->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Fotografer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- STYLE GLOBAL (ABSTRACT ELEGANT) --- */
        :root {
            --primary: #6A5ACD;
            --primary-soft: rgba(106, 90, 205, 0.1);
            --white: #ffffff;
            --text-dark: #333;
            --text-gray: #666;
            --shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; padding: 0; 
            min-height: 100vh;
            color: var(--text-dark);
            overflow-x: hidden;
            
            /* BACKGROUND POLA GARIS */
            background-color: #f8f9fd;
            background-image: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 20px,
                rgba(106, 90, 205, 0.03) 20px,
                rgba(106, 90, 205, 0.03) 40px
            );
            position: relative;
        }

        /* Elemen Dekorasi Mengambang */
        body::before {
            content: ""; position: fixed; top: -100px; left: -50px; width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(106, 90, 205, 0.08) 0%, transparent 70%);
            border-radius: 50%; z-index: -1; animation: floatShape 15s infinite ease-in-out alternate;
        }
        body::after {
            content: ""; position: fixed; bottom: -80px; right: -50px; width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(0, 210, 255, 0.05) 0%, transparent 70%);
            border-radius: 50%; z-index: -1; animation: floatShape 20s infinite ease-in-out alternate-reverse;
        }
        @keyframes floatShape {
            0% { transform: translate(0, 0); }
            100% { transform: translate(20px, 30px); }
        }
        
        a { text-decoration: none; }

        /* --- NAVBAR --- */
        .top-nav { 
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            height: 80px; 
            display: flex; align-items: center; padding: 0 40px; 
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.5);
        }
        
        .logo-nav { display: flex; align-items: center; width: 250px; gap: 15px; }
        .logo-circle {
            width: 50px; height: 50px; border-radius: 50%; object-fit: cover;
            border: 2px solid var(--primary); box-shadow: 0 2px 10px rgba(106, 90, 205, 0.2);
        }
        .brand-text { font-weight: 700; font-size: 20px; color: var(--primary); letter-spacing: 1px; }

        .nav-links { flex-grow: 1; display: flex; gap: 10px; margin-left: 20px; }
        .nav-links a { 
            color: var(--text-gray); font-weight: 500; font-size: 14px; padding: 12px 18px; 
            border-radius: 12px; transition: all 0.3s ease; 
        }
        .nav-links a:hover, .nav-links .active-link { color: var(--primary); background-color: var(--primary-soft); font-weight: 600; }
        
        /* USER MENU DENGAN LABEL ROLE */
        .user-menu { margin-left: auto; position: relative; }
        .dropbtn { 
            background: #fff; border: 1px solid #f0f0f0; cursor: pointer; 
            display: flex; align-items: center; gap: 12px; 
            padding: 8px 20px 8px 8px; border-radius: 50px; transition: 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }
        .dropbtn:hover { background-color: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transform: translateY(-1px); }
        
        /* Layout User Info di Navbar */
        .user-info { text-align: left; line-height: 1.2; display: flex; flex-direction: column; }
        .user-name { font-weight: 600; font-size: 14px; color: var(--text-dark); }
        .user-role { font-size: 10px; color: var(--primary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: var(--primary-soft); padding: 2px 6px; border-radius: 4px; width: fit-content; margin-top: 2px; }

        .dropdown-content { display: none; position: absolute; right: 0; top: 130%; background-color: var(--white); min-width: 200px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; animation: slideUp 0.3s ease; border: 1px solid #f0f0f0; }
        .dropdown-content a { color: var(--text-dark); padding: 12px 20px; display: block; font-size: 14px; border-bottom: 1px solid #f9f9f9; }
        .dropdown-content a:hover { background-color: #f9f9ff; color: var(--primary); }
        .user-menu:hover .dropdown-content { display: block; }
        @keyframes slideUp { from {opacity:0; transform:translateY(10px);} to {opacity:1; transform:translateY(0);} }

        /* --- CONTENT --- */
        .content { max-width: 1200px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }

        /* HERO SECTION */
        .dashboard-hero { 
            background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%);
            color: white; padding: 40px; border-radius: 20px; margin-bottom: 40px; 
            box-shadow: 0 15px 30px rgba(106, 90, 205, 0.3); position: relative; overflow: hidden;
        }
        .dashboard-hero::before {
            content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        .hero-title { font-size: 28px; margin: 0 0 10px 0; font-weight: 700; }
        .hero-subtitle { font-size: 16px; opacity: 0.9; margin: 0; font-weight: 300; }

        h2.section-heading { 
            color: var(--text-dark); font-size: 20px; margin-bottom: 25px; 
            border-left: 5px solid var(--primary); padding-left: 15px; font-weight: 600;
        }

        /* --- KARTU STATISTIK --- */
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 50px; }
        
        .stat-card { 
            background-color: rgba(255, 255, 255, 0.9); padding: 25px; border-radius: 16px; 
            box-shadow: var(--shadow); transition: all 0.3s ease; position: relative; overflow: hidden;
            display: flex; flex-direction: column; justify-content: center; border: 1px solid rgba(255,255,255,0.8); backdrop-filter: blur(5px);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(106, 90, 205, 0.1); background-color: #fff; border-color: var(--primary-soft); }

        .sc-purple { border-left: 5px solid #6A5ACD; }
        .sc-orange { border-left: 5px solid #fd7e14; }
        .sc-green { border-left: 5px solid #28a745; }
        .sc-blue { border-left: 5px solid #007bff; }

        .stat-title { font-size: 14px; color: #888; font-weight: 600; text-transform: uppercase; margin-bottom: 10px; z-index: 2; }
        .stat-value { font-size: 36px; font-weight: 700; color: var(--text-dark); z-index: 2; line-height: 1; }
        
        .bg-icon { position: absolute; right: 15px; bottom: 15px; font-size: 80px; opacity: 0.05; color: #333; z-index: 1; transition: 0.3s; }
        .stat-card:hover .bg-icon { transform: scale(1.1); opacity: 0.1; }

        /* --- AKSI CEPAT --- */
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .action-card { 
            background: rgba(255, 255, 255, 0.95); padding: 30px; border-radius: 16px; 
            box-shadow: var(--shadow); display: flex; align-items: center; justify-content: space-between; 
            transition: 0.3s; border: 1px solid rgba(255,255,255,0.8);
        }
        .action-card:hover { transform: translateY(-3px); background: #fff; box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .ac-text h3 { margin: 0 0 5px 0; font-size: 18px; color: var(--text-dark); }
        .ac-text p { margin: 0; color: #777; font-size: 13px; }

        .btn-action { 
            background-color: transparent; color: var(--primary); border: 1px solid var(--primary); 
            padding: 10px 20px; border-radius: 50px; font-weight: 600; font-size: 13px; 
            transition: 0.3s; white-space: nowrap; cursor: pointer; text-decoration:none;
        }
        .btn-action:hover { background-color: var(--primary); color: white; box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3); }

    </style>
</head>
<body>
    
    <div class="top-nav">
        <div class="logo-nav">
            <img src="foto/logo.jpg" alt="Logo" class="logo-circle">
            <span class="brand-text">ENEMATIKA</span>
        </div>
        
        <div class="nav-links">
            <a href="dashboard_fotografer.php" class="active-link">Dashboard</a>
            <a href="jadwal_fotografer.php">Lihat Jadwal</a>
            <a href="upload_foto.php">Upload Foto</a>
            <a href="galeri_fotografer.php">Galeri</a>
        </div>
        
        <div class="user-menu">
            <button class="dropbtn">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_username); ?>&background=random" style="width:35px; height:35px; border-radius:50%;">
                
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user_username); ?></span>
                    <span class="user-role">FOTOGRAFER</span>
                </div>

                <i class="fas fa-chevron-down" style="font-size:10px; color:#888;"></i>
            </button>
            <div class="dropdown-content">
                <a href="#"><i class="fas fa-user"></i> Profil Saya</a>
                <a href="logout.php" style="color:red;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="content">
        
        <div class="dashboard-hero">
            <h1 class="hero-title">Halo, <?php echo htmlspecialchars($fotografer_name); ?>! 📸</h1>
            <p class="hero-subtitle">Siap untuk memotret hari ini? Berikut ringkasan tugas Anda.</p>
        </div>

        <h2 class="section-heading">Jadwal & Tugas</h2>
        
        <div class="card-grid">
             <div class="stat-card sc-purple">
                <div class="stat-title">Jadwal Hari Ini</div>
                <div class="stat-value"><?php echo $jadwal_hari_ini; ?></div>
                <i class="fas fa-calendar-day bg-icon" style="color: #6A5ACD;"></i>
            </div>
            
            <div class="stat-card sc-orange">
                <div class="stat-title">Jadwal Besok</div>
                <div class="stat-value"><?php echo $jadwal_besok; ?></div>
                <i class="fas fa-calendar-plus bg-icon" style="color: #fd7e14;"></i>
            </div>
            
            <div class="stat-card sc-green">
                <div class="stat-title">Total Jadwal Aktif</div>
                <div class="stat-value"><?php echo $total_aktif; ?></div>
                <i class="fas fa-clock bg-icon" style="color: #28a745;"></i>
            </div>
            
            <div class="stat-card sc-blue">
                <div class="stat-title">Tugas Selesai</div>
                <div class="stat-value"><?php echo $total_selesai; ?></div>
                <i class="fas fa-check-circle bg-icon" style="color: #007bff;"></i>
            </div>
        </div>
        
        <h2 class="section-heading">Aksi Cepat</h2>
        <div class="action-grid">
            <div class="action-card">
                <div class="ac-text">
                    <h3>Lihat Jadwal Lengkap</h3>
                    <p>Cek detail lokasi dan waktu pemotretan.</p>
                </div>
                <a href="jadwal.php" class="btn-action">Buka Jadwal &rarr;</a>
            </div>
            
            <div class="action-card">
                <div class="ac-text">
                    <h3>Upload Hasil Foto</h3>
                    <p>Kirim link Google Drive ke pelanggan.</p>
                </div>
                <a href="upload_foto.php" class="btn-action">Upload Sekarang &rarr;</a>
            </div>
        </div>

    </div>
</body>
</html>