<?php
// dashboard_admin.php - MODERN UI + BACKGROUND ANIMASI (BERGULIR) + LOGO BULAT
session_start();
include('includes/db_koneksi.php');

// Cek autentikasi dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$admin_name = $_SESSION['name'] ?? 'Admin';

// --- QUERY DATA RINGKASAN ---
$total_pesanan = $koneksi->query("SELECT COUNT(*) as total FROM pemesanan")->fetch_assoc()['total'] ?? 0;
$menunggu_verifikasi = $koneksi->query("SELECT COUNT(*) as total FROM pembayaran WHERE statusBayar = 'Menunggu Verifikasi'")->fetch_assoc()['total'] ?? 0;
$total_jadwal = $koneksi->query("SELECT COUNT(*) as total FROM pemesanan WHERE statusPesanan = 'Terjadwal'")->fetch_assoc()['total'] ?? 0;
$total_fotografer = $koneksi->query("SELECT COUNT(*) as total FROM user WHERE role = 'Fotografer'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Modern</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- 1. RESET & BASIC STYLE --- */
        :root {
            --primary: #6A5ACD;
            --primary-dark: #483D8B;
            --text-dark: #333;
            --text-gray: #666;
            --white: #ffffff;
            --shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; 
            padding: 0; 
            
            /* --- BACKGROUND BERGULIR (ANIMASI) --- */
            /* Menggunakan warna lembut: Putih kebiruan, Ungu pudar, dan Abu-abu muda */
            background: linear-gradient(-45deg, #e3eeff, #f3e7e9, #e8dbfc, #f5f7fa);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite; /* Durasi 15 detik, berulang selamanya */
            
            color: var(--text-dark); 
            min-height: 100vh; 
        }

        /* Keyframes untuk pergerakan background */
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        a { text-decoration: none; }

        /* --- 2. HEADER NAVIGASI (Sticky & Clean) --- */
        .top-nav { 
            background-color: rgba(255, 255, 255, 0.9); /* Sedikit transparan */
            backdrop-filter: blur(10px); /* Efek blur di belakang navbar */
            height: 80px; 
            display: flex; 
            align-items: center; 
            padding: 0 40px; 
            position: sticky; 
            top: 0; 
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.03);
        }
        
        .logo-nav { 
            display: flex; 
            align-items: center; 
            width: 250px; 
            gap: 15px;
        }
        
        .logo-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
            box-shadow: 0 2px 10px rgba(106, 90, 205, 0.2);
        }

        .brand-text {
            font-weight: 700;
            font-size: 20px;
            color: var(--primary);
            letter-spacing: 0.5px;
        }

        .nav-links { 
            flex-grow: 1; 
            display: flex; 
            gap: 10px; 
            margin-left: 20px;
        }
        
        .nav-links a { 
            color: var(--text-gray); 
            font-weight: 500; 
            font-size: 14px; 
            padding: 12px 18px; 
            border-radius: 12px; 
            transition: all 0.3s ease; 
        }
        
        .nav-links a:hover, .nav-links .active-link { 
            color: var(--primary); 
            background-color: rgba(106, 90, 205, 0.1); /* Ungu transparan */
            font-weight: 600; 
        }
        
        /* USER MENU */
        .user-menu { margin-left: auto; position: relative; }
        .dropbtn { 
            background: none; border: none; cursor: pointer; 
            display: flex; align-items: center; gap: 10px; 
            font-weight: 600; color: var(--text-dark); font-size: 14px;
            padding: 8px 15px; border-radius: 30px; transition: 0.3s;
        }
        .dropbtn:hover { background-color: rgba(0,0,0,0.05); }
        
        .dropdown-content { 
            display: none; position: absolute; right: 0; top: 120%; 
            background-color: var(--white); min-width: 200px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            border-radius: 10px; overflow: hidden; border: 1px solid #f0f0f0;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp { from {opacity:0; transform:translateY(10px);} to {opacity:1; transform:translateY(0);} }
        
        .dropdown-content a { 
            color: var(--text-dark); padding: 12px 20px; display: block; 
            font-size: 14px; border-bottom: 1px solid #f9f9f9; 
        }
        .dropdown-content a:hover { background-color: #f9f9ff; color: var(--primary); }
        .user-menu:hover .dropdown-content { display: block; }

        /* --- 3. KONTEN DASHBOARD --- */
        .content { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        /* HERO SECTION (Welcome Box) */
        .dashboard-hero { 
            background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%);
            color: white; 
            padding: 40px; 
            border-radius: 20px; 
            margin-bottom: 40px; 
            box-shadow: 0 10px 30px rgba(106, 90, 205, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        /* Hiasan background bulat samar */
        .dashboard-hero::before {
            content: ''; position: absolute; top: -50%; right: -10%; 
            width: 300px; height: 300px; background: rgba(255,255,255,0.1); 
            border-radius: 50%;
        }
        
        .hero-title { font-size: 28px; margin: 0 0 10px 0; font-weight: 700; }
        .hero-subtitle { font-size: 16px; opacity: 0.9; margin: 0; font-weight: 300; }

        /* SECTION TITLE */
        h2.section-heading { 
            color: var(--text-dark); 
            font-size: 20px; 
            margin-bottom: 25px; 
            border-left: 5px solid var(--primary); 
            padding-left: 15px;
            font-weight: 600;
        }

        /* --- 4. KARTU STATISTIK (MODERN CARD - GLASS EFFECT) --- */
        .card-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 25px; 
            margin-bottom: 50px; 
        }
        
        .stat-card { 
            background-color: rgba(255, 255, 255, 0.8); /* Agak transparan */
            padding: 25px; 
            border-radius: 16px; 
            box-shadow: var(--shadow); 
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border: 1px solid rgba(255,255,255,0.6);
            backdrop-filter: blur(5px);
        }
        
        .stat-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 30px rgba(0,0,0,0.1); 
            background-color: #fff;
        }

        /* Garis warna di kiri kartu */
        .sc-purple { border-left: 5px solid #6A5ACD; }
        .sc-yellow { border-left: 5px solid #ffc107; }
        .sc-green { border-left: 5px solid #28a745; }
        .sc-blue { border-left: 5px solid #007bff; }

        .stat-title { font-size: 14px; color: #888; font-weight: 600; text-transform: uppercase; margin-bottom: 10px; z-index: 2; }
        .stat-value { font-size: 36px; font-weight: 700; color: var(--text-dark); z-index: 2; line-height: 1; }
        
        /* Ikon Background Besar */
        .bg-icon {
            position: absolute;
            right: 15px;
            bottom: 15px;
            font-size: 80px;
            opacity: 0.08;
            color: #333;
            z-index: 1;
            transition: 0.3s;
        }
        .stat-card:hover .bg-icon { transform: scale(1.1); opacity: 0.15; }

        /* --- 5. AKSI CEPAT (Quick Actions) --- */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.85);
            padding: 30px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.6);
        }
        .action-card:hover { transform: translateY(-3px); background: #fff; }

        .ac-text h3 { margin: 0 0 5px 0; font-size: 18px; }
        .ac-text p { margin: 0; color: #777; font-size: 13px; }

        .btn-action {
            background-color: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 13px;
            transition: 0.3s;
            white-space: nowrap;
            cursor: pointer;
        }
        .btn-action:hover {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3);
        }

    </style>
</head>
<body>
    
    <div class="top-nav">
        <div class="logo-nav">
            <img src="foto/logo.jpg" alt="Logo" class="logo-circle">
            <span class="brand-text">ENEMATIKA</span>
        </div>
        
        <div class="nav-links">
            <a href="dashboard_admin.php" class="active-link">Dashboard</a>
            <a href="admin_paket.php">Paket</a>
            <a href="verifikasi_pembayaran.php">Pembayaran</a>
            <a href="verifikasi_pemesanan.php">Pemesanan</a>
            <a href="jadwal.php">Jadwal</a>
            <a href="kelola_galeri.php">galeri</a>
            <a href="laporan.php">Laporan</a>
        </div>
        
        <div class="user-menu">
            <button class="dropbtn">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_username); ?>&background=random" style="width:30px; height:30px; border-radius:50%;">
                <?php echo htmlspecialchars($user_username); ?> 
                <i class="fas fa-chevron-down" style="font-size:10px;"></i>
            </button>
            <div class="dropdown-content">
                <a href="#"><i class="fas fa-user"></i> Profil Saya</a>
                <a href="#"><i class="fas fa-cog"></i> Pengaturan</a>
                <a href="logout.php" style="color:red;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="content">
        
        <div class="dashboard-hero">
            <h1 class="hero-title">Halo, <?php echo htmlspecialchars($admin_name); ?>! 👋</h1>
            <p class="hero-subtitle">Berikut adalah ringkasan aktivitas studio foto Anda hari ini.</p>
        </div>

        <h2 class="section-heading">Ringkasan Kinerja</h2>
        
        <div class="card-grid">
             <div class="stat-card sc-purple">
                <div class="stat-title">Total Pesanan</div>
                <div class="stat-value"><?php echo $total_pesanan; ?></div>
                <i class="fas fa-shopping-bag bg-icon" style="color: #6A5ACD;"></i>
            </div>
            
            <div class="stat-card sc-yellow">
                <div class="stat-title">Verifikasi Bayar</div>
                <div class="stat-value"><?php echo $menunggu_verifikasi; ?></div>
                <i class="fas fa-wallet bg-icon" style="color: #ffc107;"></i>
            </div>
            
            <div class="stat-card sc-green">
                <div class="stat-title">Jadwal Aktif</div>
                <div class="stat-value"><?php echo $total_jadwal; ?></div>
                <i class="fas fa-calendar-check bg-icon" style="color: #28a745;"></i>
            </div>
            
            <div class="stat-card sc-blue">
                <div class="stat-title">Fotografer</div>
                <div class="stat-value"><?php echo $total_fotografer; ?></div>
                <i class="fas fa-camera bg-icon" style="color: #007bff;"></i>
            </div>
        </div>
        
        <h2 class="section-heading">Aksi Cepat</h2>
        <div class="action-grid">
            <div class="action-card">
                <div class="ac-text">
                    <h3>Kelola Paket Layanan</h3>
                    <p>Tambah, edit, atau update harga paket foto.</p>
                </div>
                <button onclick="window.location.href='admin_paket.php'" class="btn-action">Atur Paket &rarr;</button>
            </div>
            
            <div class="action-card">
                <div class="ac-text">
                    <h3>Laporan Keuangan</h3>
                    <p>Lihat pemasukan dan status transaksi.</p>
                </div>
                <button class="btn-action">Buka Laporan &rarr;</button>
            </div>
        </div>

    </div>
</body>
</html>