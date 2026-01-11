<?php
// admin_paket.php - MODERN UI + ANIMATED BG + LOGO BULAT
session_start();
include('includes/db_koneksi.php');

// CEK AKSES ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$message = '';
$error = '';

// --- LOGIKA HAPUS PAKET ---
if (isset($_GET['hapus'])) {
    $idHapus = $_GET['hapus'];
    $cek = $koneksi->query("SELECT * FROM pemesanan WHERE paketId = '$idHapus'");
    if ($cek->num_rows > 0) {
        $error = "Gagal: Paket ini sedang digunakan dalam riwayat pemesanan pelanggan.";
    } else {
        $stmt = $koneksi->prepare("DELETE FROM paketlayanan WHERE paketId = ?");
        $stmt->bind_param("s", $idHapus);
        if ($stmt->execute()) {
            $message = "Paket berhasil dihapus.";
        } else {
            $error = "Gagal menghapus paket.";
        }
    }
}

// --- AMBIL DATA PAKET ---
$paket_list = [];
$result = $koneksi->query("SELECT * FROM paketlayanan ORDER BY paketId ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $paket_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Paket - Admin Modern</title>
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
            /* BACKGROUND ANIMASI BERGULIR */
            background: linear-gradient(-45deg, #e3eeff, #f3e7e9, #e8dbfc, #f5f7fa);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: var(--text-dark); 
            min-height: 100vh;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        a { text-decoration: none; }

        /* --- 2. HEADER NAVIGASI --- */
        .top-nav { 
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            height: 80px; 
            display: flex; 
            align-items: center; 
            padding: 0 40px; 
            position: sticky; 
            top: 0; 
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.03);
        }
        
        .logo-nav { display: flex; align-items: center; width: 250px; gap: 15px; }
        .logo-circle {
            width: 50px; height: 50px; border-radius: 50%; object-fit: cover;
            border: 2px solid var(--primary); box-shadow: 0 2px 10px rgba(106, 90, 205, 0.2);
        }
        .brand-text { font-weight: 700; font-size: 20px; color: var(--primary); }

        .nav-links { flex-grow: 1; display: flex; gap: 10px; margin-left: 20px; }
        .nav-links a { 
            color: var(--text-gray); font-weight: 500; font-size: 14px; padding: 12px 18px; 
            border-radius: 12px; transition: all 0.3s ease; 
        }
        .nav-links a:hover, .nav-links .active-link { color: var(--primary); background-color: rgba(106, 90, 205, 0.1); font-weight: 600; }
        
        .user-menu { margin-left: auto; position: relative; }
        .dropbtn { background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: 600; color: var(--text-dark); font-size: 14px; padding: 8px 15px; border-radius: 30px; transition: 0.3s; }
        .dropbtn:hover { background-color: rgba(0,0,0,0.05); }
        .dropdown-content { display: none; position: absolute; right: 0; top: 120%; background-color: var(--white); min-width: 200px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; animation: slideUp 0.3s ease; }
        .dropdown-content a { color: var(--text-dark); padding: 12px 20px; display: block; font-size: 14px; border-bottom: 1px solid #f9f9f9; }
        .dropdown-content a:hover { background-color: #f9f9ff; color: var(--primary); }
        .user-menu:hover .dropdown-content { display: block; }
        @keyframes slideUp { from {opacity:0; transform:translateY(10px);} to {opacity:1; transform:translateY(0);} }

        /* --- 3. KONTEN HALAMAN --- */
        .content { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        /* Header Halaman & Tombol Add */
        .page-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;
        }
        .page-title h2 { margin: 0; color: var(--text-dark); font-size: 24px; font-weight: 700; }
        .page-title p { margin: 5px 0 0; color: var(--text-gray); font-size: 14px; }

        .btn-add-new {
            background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%);
            color: white; padding: 12px 25px; border-radius: 50px;
            font-weight: 600; font-size: 14px; box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3);
            transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-add-new:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(106, 90, 205, 0.4); }

        /* Alerts */
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* --- 4. TABEL MODERN --- */
        .table-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden; /* Agar sudut tumpul tabel tetap terlihat */
            border: 1px solid rgba(255,255,255,0.5);
        }

        .table-responsive { overflow-x: auto; }

        .modern-table { width: 100%; border-collapse: collapse; }
        
        .modern-table th {
            background-color: rgba(106, 90, 205, 0.05); /* Ungu sangat muda */
            color: var(--primary);
            font-weight: 700;
            padding: 20px;
            text-align: left;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #eee;
        }

        .modern-table td {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            font-size: 14px;
            color: var(--text-dark);
        }

        .modern-table tr:hover { background-color: #fff; }
        .modern-table tr:last-child td { border-bottom: none; }

        /* ID Style */
        .id-badge { font-family: monospace; background: #eee; padding: 4px 8px; border-radius: 4px; font-size: 12px; color: #555; font-weight: bold; }

        /* Badges Kategori */
        .badge { padding: 6px 12px; border-radius: 30px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-outdoor { background-color: #fff8e1; color: #ffa000; border: 1px solid #ffe082; }
        .badge-indoor { background-color: #e0f7fa; color: #0097a7; border: 1px solid #80deea; }

        /* Harga */
        .price-tag { font-weight: 700; color: var(--primary); font-size: 15px; }

        /* Tombol Aksi Kecil */
        .action-buttons { display: flex; gap: 8px; }
        .btn-icon {
            width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            border: none; cursor: pointer; transition: 0.2s; font-size: 14px;
        }
        .btn-edit { background: #fff3cd; color: #856404; }
        .btn-edit:hover { background: #ffeeba; transform: scale(1.1); }
        
        .btn-delete { background: #f8d7da; color: #721c24; }
        .btn-delete:hover { background: #f5c6cb; transform: scale(1.1); }

    </style>
</head>
<body>
    
    <div class="top-nav">
        <div class="logo-nav">
            <img src="foto/logo.jpg" alt="Logo" class="logo-circle">
            <span class="brand-text">ENEMATIKA</span>
        </div>
        
        <div class="nav-links">
            <a href="dashboard_admin.php">Dashboard</a>
            <a href="admin_paket.php" class="active-link">Paket</a>
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
        
        <div class="page-header">
            <div class="page-title">
                <h2>Kelola Paket Layanan</h2>
                <p>Tambah, ubah, atau hapus paket foto yang tersedia.</p>
            </div>
            <a href="admin_paket_form.php" class="btn-add-new">
                <i class="fas fa-plus"></i> Tambah Paket
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID Paket</th>
                            <th>Nama Paket</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Deskripsi Singkat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paket_list)): ?>
                            <tr><td colspan="6" style="text-align:center; padding: 40px; color: #999;">Belum ada data paket. Silakan tambah baru.</td></tr>
                        <?php else: ?>
                            <?php foreach ($paket_list as $row): ?>
                            <tr>
                                <td><span class="id-badge"><?php echo $row['paketId']; ?></span></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['namaPaket']); ?></td>
                                <td>
                                    <?php 
                                    $kat = $row['kategori'];
                                    if (empty($kat)) $kat = 'Outdoor'; 
                                    $badgeClass = ($kat == 'Indoor') ? 'badge-indoor' : 'badge-outdoor';
                                    echo "<span class='badge $badgeClass'>$kat</span>";
                                    ?>
                                </td>
                                <td><span class="price-tag">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></span></td>
                                <td style="color: #666; font-size: 13px;"><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 60)) . '...'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="admin_paket_form.php?edit=<?php echo $row['paketId']; ?>" class="btn-icon btn-edit" title="Edit">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <a href="admin_paket.php?hapus=<?php echo $row['paketId']; ?>" class="btn-icon btn-delete" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus paket ini?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>