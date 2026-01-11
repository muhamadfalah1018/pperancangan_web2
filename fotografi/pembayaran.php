<?php
// pembayaran.php - ELEGANT UI + MATCHING USER MENU
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('includes/db_koneksi.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Pelanggan') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name']; // Ambil nama user untuk pesan sapaan
$user_username = $_SESSION['username']; // Added username variable for consistency
$orderId = $_GET['orderId'] ?? '';
$message = '';
$error = '';

// ==========================================
// KONDISI 1: BUKA DARI NAVBAR (LIST TAGIHAN)
// ==========================================
if (empty($orderId)) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Daftar Pembayaran</title>
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
            }

            body { 
                margin: 0; padding: 0; 
                font-family: 'Poppins', sans-serif;
                min-height: 100vh;
                color: var(--text-dark);
                overflow-x: hidden;
                
                /* BACKGROUND ABSTRAK */
                background-color: #f3f4f6;
                background-image: 
                    radial-gradient(circle at 10% 20%, rgba(106, 90, 205, 0.05) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(0, 210, 255, 0.05) 0%, transparent 40%);
                background-attachment: fixed;
            }

            /* --- NAVBAR --- */
            .top-nav { 
                background-color: rgba(255, 255, 255, 0.85); 
                backdrop-filter: blur(15px);
                box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
                height: 70px; 
                display: flex; align-items: center; padding: 0 30px; 
                position: sticky; top: 0; z-index: 1000; 
            }
            
            .logo-nav { display: flex; align-items: center; width: 250px; gap: 10px; }
            .logo-circle { 
                width: 40px; height: 40px; border-radius: 50%; object-fit: cover; 
                border: 2px solid var(--primary); box-shadow: 0 2px 5px rgba(106, 90, 205, 0.2);
            }
            .brand-text { font-weight: 700; font-size: 18px; color: var(--primary); letter-spacing: 1px; }

            .nav-links { flex-grow: 1; display: flex; gap: 5px; }
            .nav-links a { color: #555; font-weight: 500; text-decoration: none; font-size: 14px; padding: 10px 15px; border-radius: 6px; transition: all 0.3s ease; }
            .nav-links a:hover, .nav-links .active-link { color: var(--primary); background-color: var(--primary-soft); font-weight: 600; }
            
            /* USER MENU (MATCHING DASHBOARD) */
            .user-menu { margin-left: auto; position: relative; }
            .dropbtn { background: #fff; border: 1px solid #eee; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600; color: #333; font-size: 14px; padding: 8px 15px; border-radius: 30px; transition: 0.3s; }
            .dropbtn:hover { box-shadow: 0 3px 10px rgba(106,90,205,0.2); border-color: var(--primary); }
            .dropdown-content { display: none; position: absolute; right: 0; top: 120%; background-color: #fff; min-width: 180px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; border: 1px solid #eee; }
            .dropdown-content a { color: #333; padding: 12px 16px; display: block; font-size: 13px; text-decoration: none; border-bottom: 1px solid #f9f9f9; }
            .dropdown-content a:hover { background-color: #f9f9ff; color: var(--primary); }
            .user-menu:hover .dropdown-content { display: block; }

            /* --- CONTENT --- */
            .content { padding: 40px 20px; max-width: 800px; margin: 0 auto; position: relative; z-index: 1; }
            .page-title { text-align: center; margin-bottom: 40px; }
            .page-title h2 { margin: 0; color: var(--primary); font-size: 28px; }
            .page-title p { margin: 5px 0 0; color: var(--text-gray); }

            /* --- BILL CARD --- */
            .bill-card { 
                background: #fff; padding: 25px; border-radius: 12px; 
                box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; 
                border-left: 5px solid #dc3545; display: flex; justify-content: space-between; align-items: center; 
                transition: transform 0.3s;
            }
            .bill-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
            
            .bill-info h4 { margin: 0 0 5px 0; color: #333; font-size: 18px; }
            .bill-info p { margin: 0; color: #666; font-size: 14px; }
            
            .btn-pay-now { 
                background: linear-gradient(135deg, #28a745 0%, #218838 100%); 
                color: white; text-decoration: none; padding: 10px 25px; 
                border-radius: 50px; font-weight: bold; font-size: 14px; 
                box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3); transition: 0.3s; 
            }
            .btn-pay-now:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4); }
            
            /* --- EMPTY STATE (Jika Kosong) --- */
            .empty-state { 
                text-align: center; padding: 60px 20px; 
                background: rgba(255,255,255,0.8); border-radius: 20px; 
                border: 2px dashed #ddd; max-width: 600px; margin: 0 auto;
            }
            .empty-icon { font-size: 60px; color: #ccc; margin-bottom: 20px; }
            .empty-title { font-size: 22px; font-weight: bold; color: #444; margin-bottom: 10px; }
            .empty-desc { color: #666; margin-bottom: 30px; font-size: 15px; }
            .btn-action-big {
                background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%);
                color: white; padding: 12px 30px; text-decoration: none; 
                border-radius: 50px; font-weight: bold; font-size: 16px; 
                box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3); transition: 0.3s;
                display: inline-block;
            }
            .btn-action-big:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(106, 90, 205, 0.5); }

        </style>
    </head>
    <body>
        
        <div class="top-nav">
            <div class="logo-nav">
                <img src="foto/logo.jpg" alt="Logo" class="logo-circle">
                <span class="brand-text">ISTORE</span>
            </div>
            <div class="nav-links">
                <a href="dashboard_pelanggan.php">Beranda</a>
                <a href="pemesanan.php">Pemesanan</a>
                <a href="pembayaran.php" class="active-link">Pembayaran</a>
                <a href="riwayat_pesanan.php">Riwayat Pesanan</a>
            </div>
            <div class="user-menu">
                <button class="dropbtn">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_username); ?>&background=random" style="width:30px; height:30px; border-radius:50%;">
                    <?php echo htmlspecialchars($user_username); ?> 
                    <i class="fas fa-caret-down" style="font-size: 12px;"></i>
                </button>
                <div class="dropdown-content">
                    <a href="profil_pelanggan.php"><i class="fas fa-user"></i> Profil Saya</a>
                    <a href="profil_pelanggan.php"><i class="fas fa-cog"></i> Pengaturan</a>
                    <hr style="margin:0; border-top:1px solid #eee;">
                    <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="content">
            
            <?php
            $q = $koneksi->prepare("
                SELECT p.orderId, p.tanggalPesan, pk.namaPaket, COALESCE(p.totalHarga, pk.harga) as harga, b.statusBayar 
                FROM pemesanan p
                JOIN paketlayanan pk ON p.paketId = pk.paketId
                LEFT JOIN pembayaran b ON p.orderId = b.orderId
                WHERE p.userId = ? 
                AND p.statusPesanan != 'Dibatalkan' 
                AND (b.statusBayar IS NULL OR b.statusBayar = 'Ditolak' OR b.statusBayar LIKE 'DP%' OR b.statusBayar = 'Bayar di Tempat')
                ORDER BY p.tanggalPesan DESC
            ");
            $q->bind_param("s", $user_id);
            $q->execute();
            $res = $q->get_result();

            if ($res->num_rows > 0) {
                // JIKA ADA TAGIHAN
                ?>
                <div class="page-title">
                    <h2>Tagihan Pembayaran</h2>
                    <p>Silakan selesaikan pembayaran untuk pesanan Anda.</p>
                </div>
                <?php
                while ($row = $res->fetch_assoc()) {
                    $st = $row['statusBayar'] ?? 'Belum Dibayar';
                    $color = ($st == 'Ditolak') ? '#dc3545' : '#ffc107'; 
                    ?>
                    <div class="bill-card" style="border-left-color: <?php echo $color; ?>;">
                        <div class="bill-info">
                            <h4><?php echo htmlspecialchars($row['namaPaket']); ?> <small style="color:#999; font-size:12px;">(#<?php echo $row['orderId']; ?>)</small></h4>
                            <p>Total: <b style="color:var(--primary);">Rp <?php echo number_format($row['harga'],0,',','.'); ?></b> &bull; Status: <span style="color:<?php echo $color; ?>; font-weight:bold;"><?php echo $st; ?></span></p>
                        </div>
                        <a href="pembayaran.php?orderId=<?php echo $row['orderId']; ?>" class="btn-pay-now">Bayar Sekarang &rarr;</a>
                    </div>
                    <?php
                }
            } else {
                // JIKA KOSONG (EMPTY STATE)
                ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                    <div class="empty-title">Halo, <?php echo htmlspecialchars($user_name); ?>!</div>
                    <p class="empty-desc">
                        Saat ini belum ada tagihan pembayaran yang perlu diselesaikan.<br>
                        Ingin mengabadikan momen spesial Anda?
                    </p>
                    <a href="pemesanan.php" class="btn-action-big"><i class="fas fa-camera"></i> Buat Pesanan Baru</a>
                </div>
                <?php
            }
            ?>
        </div>
    </body>
    </html>
    <?php
    exit(); 
}

// ==========================================
// KONDISI 2: FORM PEMBAYARAN
// ==========================================

$stmt = $koneksi->prepare("
    SELECT p.totalHarga, pk.namaPaket, pk.harga 
    FROM pemesanan p 
    JOIN paketlayanan pk ON p.paketId = pk.paketId 
    WHERE p.orderId = ? AND p.userId = ?
");
$stmt->bind_param("ss", $orderId, $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$dataPesanan = $res->fetch_assoc();

if (!$dataPesanan) {
    echo "<script>alert('Pesanan tidak ditemukan!'); window.location='pembayaran.php';</script>";
    exit();
}

$totalTagihan = $dataPesanan['totalHarga'] ?: $dataPesanan['harga'];

// PROSES SUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_bayar'])) {
    
    $tipeBayar = $_POST['tipe_bayar']; // 'transfer' atau 'cod'
    $catatan = $_POST['catatan'];
    
    // --- SKENARIO 1: TRANSFER BANK ---
    if ($tipeBayar == 'transfer') {
        $bankTujuan = $_POST['bank_tujuan'];
        $skemaBayar = $_POST['skema_bayar']; 
        $persenDp = $_POST['persen_dp'] ?? 0; 

        // Hitung Nominal & String Metode
        if ($skemaBayar == 'dp') {
            if ($persenDp < 25 || $persenDp > 50) {
                $error = "Persentase DP harus antara 25% sampai 50%.";
            }
            $metodeSimpan = "Transfer $bankTujuan (DP $persenDp%)";
            $jumlahBayarReal = ($totalTagihan * $persenDp) / 100;
        } else {
            $metodeSimpan = "Transfer $bankTujuan (Lunas)";
            $jumlahBayarReal = $totalTagihan;
        }

        // Cek Upload Gambar
        if (empty($error)) {
            $target_dir = "uploads/bukti/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_name = time() . "_" . basename($_FILES["bukti_transfer"]["name"]);
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES["bukti_transfer"]["tmp_name"], $target_file)) {
                $statusAwal = 'Menunggu Verifikasi';
                $buktiDb = $target_file;
            } else {
                $error = "Gagal upload gambar. Pastikan file dipilih.";
            }
        }
    } 
    // --- SKENARIO 2: BAYAR DI TEMPAT (COD) ---
    else {
        $metodeSimpan = "Bayar di Tempat (COD)";
        $jumlahBayarReal = $totalTagihan; 
        $statusAwal = 'Bayar di Tempat'; 
        $buktiDb = NULL; 
    }

    // SIMPAN KE DATABASE
    if (empty($error)) {
        // Bersihkan data lama
        $koneksi->query("DELETE FROM pembayaran WHERE orderId='$orderId' AND (statusBayar='Ditolak' OR statusBayar IS NULL)");

        $payId = 'PAY' . date('dmy') . rand(100, 999);
        $tglBayar = date('Y-m-d H:i:s');

        $stmt_ins = $koneksi->prepare("INSERT INTO pembayaran (pembayaranId, orderId, tanggalBayar, jumlahBayar, metode, buktiBayarLink, statusBayar, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_ins->bind_param("ssssssss", $payId, $orderId, $tglBayar, $jumlahBayarReal, $metodeSimpan, $buktiDb, $statusAwal, $catatan);

        if ($stmt_ins->execute()) {
            echo "<script>alert('Konfirmasi pembayaran berhasil! Status: $statusAwal'); window.location.href='riwayat_pesanan.php';</script>";
            exit();
        } else {
            $error = "Gagal menyimpan ke database: " . $stmt_ins->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Pembayaran</title>
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
        }

        body { 
            margin: 0; padding: 0; 
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            color: var(--text-dark);
            overflow-x: hidden;
            
            /* BACKGROUND ABSTRAK */
            background-color: #f3f4f6;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(106, 90, 205, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(0, 210, 255, 0.05) 0%, transparent 40%);
            background-attachment: fixed;
        }

        /* --- NAVBAR --- */
        .top-nav { 
            background-color: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(15px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
            height: 70px; 
            display: flex; align-items: center; padding: 0 30px; 
            position: sticky; top: 0; z-index: 1000; 
        }
        
        .logo-nav { display: flex; align-items: center; width: 250px; gap: 10px; }
        .logo-circle { 
            width: 40px; height: 40px; border-radius: 50%; object-fit: cover; 
            border: 2px solid var(--primary); box-shadow: 0 2px 5px rgba(106, 90, 205, 0.2);
        }
        .brand-text { font-weight: 700; font-size: 18px; color: var(--primary); letter-spacing: 1px; }

        .nav-links { flex-grow: 1; display: flex; gap: 5px; }
        .nav-links a { color: #555; font-weight: 500; text-decoration: none; font-size: 14px; padding: 10px 15px; border-radius: 6px; transition: all 0.3s ease; }
        .nav-links a:hover, .nav-links .active-link { color: var(--primary); background-color: var(--primary-soft); font-weight: 600; }
        
        /* USER MENU (MATCHING DASHBOARD) */
        .user-menu { margin-left: auto; position: relative; }
        .dropbtn { background: #fff; border: 1px solid #eee; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600; color: #333; font-size: 14px; padding: 8px 15px; border-radius: 30px; transition: 0.3s; }
        .dropbtn:hover { box-shadow: 0 3px 10px rgba(106,90,205,0.2); border-color: var(--primary); }
        .dropdown-content { display: none; position: absolute; right: 0; top: 120%; background-color: #fff; min-width: 180px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; border: 1px solid #eee; }
        .dropdown-content a { color: #333; padding: 12px 16px; display: block; font-size: 13px; text-decoration: none; border-bottom: 1px solid #f9f9f9; }
        .dropdown-content a:hover { background-color: #f9f9ff; color: var(--primary); }
        .user-menu:hover .dropdown-content { display: block; }

        /* --- LAYOUT --- */
        .content { padding: 40px 20px; max-width: 1000px; margin: 0 auto; position: relative; z-index: 1; }
        .pay-container { display: flex; gap: 30px; flex-wrap: wrap; }
        .pay-left { flex: 1; min-width: 300px; }
        .pay-right { width: 350px; }

        .card { 
            background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); 
            padding: 25px; border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 20px; 
            border: 1px solid rgba(255,255,255,0.5);
        }
        .card h3 { margin-top: 0; color: var(--primary); border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; font-size: 18px; }
        
        .bill-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #555; }
        .total-row { display: flex; justify-content: space-between; margin-top: 15px; font-weight: bold; font-size: 18px; color: #333; padding-top: 15px; border-top: 2px dashed #eee; }

        label { display: block; margin-top: 15px; font-weight: 600; color: #444; font-size: 14px; }
        select, input[type="text"], input[type="file"], textarea, input[type="number"] { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-family: 'Poppins'; background: #fcfcfc; }
        select:focus, input:focus, textarea:focus { outline: none; border-color: var(--primary); background: #fff; }
        
        button { 
            background: linear-gradient(135deg, #28a745 0%, #218838 100%); 
            color: white; border: none; padding: 12px; width: 100%; border-radius: 50px; 
            font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 25px; 
            transition: 0.3s; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4); }

        .payment-type-group { display: flex; gap: 10px; margin-bottom: 20px; }
        .type-label { flex: 1; padding: 15px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; text-align: center; font-weight: bold; color: #666; transition: 0.2s; background: #fff; }
        .type-label:hover { border-color: var(--primary); background: var(--primary-soft); color: var(--primary); }
        input[type="radio"]:checked + .type-label { border-color: var(--primary); background: var(--primary-soft); color: var(--primary); }
        .hidden-radio { display: none; }

        .payment-options { display: flex; gap: 15px; margin-top: 5px; }
        .radio-label { display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal; background: #f9f9f9; padding: 10px; border-radius: 6px; border: 1px solid #ddd; flex: 1; font-size: 13px; }
        .radio-label:hover { background: #eef; border-color: var(--primary); }
        input[type="radio"] { accent-color: var(--primary); width: 16px; height: 16px; margin: 0; }

        /* Area Toggle */
        #areaTransfer, #areaCOD { display: none; animation: fadeIn 0.3s; }
        .dp-control { display: none; background: #fff8e1; padding: 15px; border-radius: 6px; border: 1px solid #ffe082; margin-top: 10px; }
        .calc-box { margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; text-align: right; border-radius: 6px; }
        
        .bank-info { background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 10px; border-left: 4px solid var(--primary); }
        .bank-info strong { display: block; color: #333; margin-bottom: 3px; }
        .bank-info span { color: #666; font-size: 13px; }
        
        @keyframes fadeIn { from { opacity:0; transform:translateY(-5px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>

<div class="top-nav">
    <div class="logo-nav">
        <img src="foto/logo.jpg" alt="Logo" class="logo-circle">
        <span class="brand-text">ISTORE</span>
    </div>
    <div class="nav-links">
        <a href="dashboard_pelanggan.php">Beranda</a>
        <a href="pemesanan.php">Pemesanan</a>
        <a href="pembayaran.php" class="active-link">Pembayaran</a>
        <a href="riwayat_pesanan.php">Riwayat Pesanan</a>
    </div>
    <div class="user-menu">
        <button class="dropbtn">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_username); ?>&background=random" style="width:30px; height:30px; border-radius:50%;">
            <?php echo htmlspecialchars($user_username); ?> 
            <i class="fas fa-caret-down" style="font-size: 12px;"></i>
        </button>
        <div class="dropdown-content">
            <a href="profil_pelanggan.php"><i class="fas fa-user"></i> Profil Saya</a>
            <a href="profil_pelanggan.php"><i class="fas fa-cog"></i> Pengaturan</a>
            <hr style="margin:0; border-top:1px solid #eee;">
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="content">
    <div class="pay-container">
        
        <div class="pay-left">
            <div class="card">
                <h3><i class="fas fa-wallet"></i> Konfirmasi Pembayaran</h3>
                
                <div style="background:#f0f4ff; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #dbeafe;">
                    <div class="bill-row"><span>ID Pesanan:</span> <b><?php echo $orderId; ?></b></div>
                    <div class="bill-row"><span>Paket Layanan:</span> <b><?php echo htmlspecialchars($dataPesanan['namaPaket']); ?></b></div>
                    <div class="total-row">
                        <span>Total Tagihan:</span>
                        <span style="color:var(--primary);">Rp <?php echo number_format($totalTagihan, 0, ',', '.'); ?></span>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <?php if ($error): ?> <div style="background:#f8d7da; color:#721c24; padding:12px; border-radius:6px; margin-bottom:15px; font-size:14px;"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div> <?php endif; ?>

                    <label style="margin-bottom:10px;">Metode Pembayaran:</label>
                    <div class="payment-type-group">
                        <label style="flex:1; margin:0;">
                            <input type="radio" name="tipe_bayar" value="transfer" class="hidden-radio" checked onchange="toggleTipe('transfer')">
                            <div class="type-label"><i class="fas fa-university"></i> Transfer Bank</div>
                        </label>
                        <label style="flex:1; margin:0;">
                            <input type="radio" name="tipe_bayar" value="cod" class="hidden-radio" onchange="toggleTipe('cod')">
                            <div class="type-label"><i class="fas fa-hand-holding-usd"></i> Bayar di Tempat</div>
                        </label>
                    </div>

                    <div id="areaTransfer" style="display:block;">
                        <label>Opsi Pembayaran:</label>
                        <div class="payment-options">
                            <label class="radio-label">
                                <input type="radio" name="skema_bayar" value="lunas" checked onchange="toggleDP(false)"> Bayar Lunas (100%)
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="skema_bayar" value="dp" onchange="toggleDP(true)"> Bayar DP (Cicil)
                            </label>
                        </div>

                        <div id="boxDP" class="dp-control">
                            <label style="margin-top:0;">Persentase DP (25% - 50%):</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="number" id="inputPersen" name="persen_dp" min="25" max="50" value="25" oninput="hitungBayar()" style="width:80px; text-align:center;">
                                <span style="font-weight:bold;">%</span>
                            </div>
                            <input type="range" id="rangePersen" min="25" max="50" value="25" style="width:100%; margin-top:10px; accent-color:var(--primary);" oninput="syncInput(this.value)">
                        </div>

                        <div class="calc-box">
                            <span style="font-size:13px; color:#666;">Nominal yang harus ditransfer:</span><br>
                            <span id="displayBayar" style="font-size:24px; font-weight:800; color:#28a745;">
                                Rp <?php echo number_format($totalTagihan, 0, ',', '.'); ?>
                            </span>
                        </div>

                        <label>Transfer ke Bank:</label>
                        <select name="bank_tujuan" id="inputBank">
                            <option value="BCA">BCA (Studio Foto)</option>
                            <option value="BRI">BRI (Studio Foto)</option>
                            <option value="DANA">DANA (E-Wallet)</option>
                        </select>

                        <label>Upload Bukti Transfer:</label>
                        <input type="file" name="bukti_transfer" id="inputFile" accept="image/*">
                        <small style="color:#888; font-size:12px;">*Format: JPG, PNG, JPEG. Max 2MB.</small>
                    </div>

                    <div id="areaCOD">
                        <div style="background:#fff3cd; color:#856404; padding:15px; border-radius:6px; border:1px solid #ffeeba; font-size:14px; line-height:1.5;">
                            <i class="fas fa-info-circle"></i> <b>Bayar di Tempat</b><br>
                            Silakan datang langsung ke studio kami untuk melakukan pembayaran tunai. Pesanan akan diproses setelah pembayaran diterima oleh admin di lokasi.
                        </div>
                    </div>

                    <label>Catatan Tambahan (Opsional):</label>
                    <textarea name="catatan" rows="2" placeholder="Contoh: Saya sudah transfer a.n Budi..."></textarea>

                    <button type="submit" name="submit_bayar">Kirim Konfirmasi <i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>

        <div class="pay-right">
            <div class="card">
                <h3><i class="fas fa-info-circle"></i> Rekening Tujuan</h3>
                <div class="bank-info">
                    <strong><i class="fas fa-money-check"></i> BCA</strong>
                    <span>123-456-7890 (Istore Studio)</span>
                </div>
                <div class="bank-info">
                    <strong><i class="fas fa-money-check"></i> BRI</strong>
                    <span>9876-01-0000 (Istore Studio)</span>
                </div>
                <div class="bank-info">
                    <strong><i class="fas fa-map-marker-alt"></i> Alamat Studio</strong>
                    <span>W4X2+GMF Balapulang Wetan, Kabupaten Tegal, Jawa Tengah.<br>(Buka: 09.00 - 21.00 WIB)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const totalTagihan = <?php echo $totalTagihan; ?>;

    function toggleTipe(tipe) {
        const trf = document.getElementById('areaTransfer');
        const cod = document.getElementById('areaCOD');
        const inpFile = document.getElementById('inputFile');
        const inpBank = document.getElementById('inputBank');
        
        if (tipe === 'transfer') {
            trf.style.display = 'block';
            cod.style.display = 'none';
            inpFile.setAttribute('required', 'required');
            inpBank.setAttribute('required', 'required');
        } else {
            trf.style.display = 'none';
            cod.style.display = 'block';
            inpFile.removeAttribute('required');
            inpBank.removeAttribute('required');
        }
    }

    function toggleDP(isDP) {
        const box = document.getElementById('boxDP');
        const inputPersen = document.getElementById('inputPersen');
        if (isDP) {
            box.style.display = 'block';
            inputPersen.setAttribute('required', 'required'); 
            hitungBayar();
        } else {
            box.style.display = 'none';
            inputPersen.removeAttribute('required'); 
            updateDisplay(totalTagihan);
        }
    }

    function syncInput(val) {
        document.getElementById('inputPersen').value = val;
        hitungBayar();
    }

    function hitungBayar() {
        let persen = parseInt(document.getElementById('inputPersen').value) || 25;
        if (persen < 25) persen = 25; if (persen > 50) persen = 50;
        document.getElementById('rangePersen').value = persen;
        const bayarSekarang = (totalTagihan * persen) / 100;
        updateDisplay(bayarSekarang);
    }

    function updateDisplay(amount) {
        document.getElementById('displayBayar').innerText = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
    }

    // Init
    toggleTipe('transfer');
</script>

</body>
</html>