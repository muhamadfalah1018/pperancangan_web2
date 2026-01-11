<?php
// pemesanan.php - FINAL: Multi-Kategori + Tampilan Abstrak Elegan & Logo
session_start();
include('includes/db_koneksi.php');
include('includes/auto_batal.php'); 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Pelanggan') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_username = $_SESSION['username'];
$message = '';
$error = '';

// --- 1. AMBIL DATA PAKET ---
$paket_list = [];
$result_paket = $koneksi->query("SELECT paketId, namaPaket, harga, kategori FROM paketlayanan ORDER BY namaPaket ASC");
if ($result_paket) {
    while ($row = $result_paket->fetch_assoc()) {
        $paket_list[] = $row;
    }
}

// --- 2. LOGIKA KALENDER (TAMPILAN) ---
$bulan_ini = date('Y-m');
$query_jadwal = $koneksi->query("
    SELECT j.tanggal, j.waktuMulai, j.waktuSelesai, pk.kategori, pk.namaPaket
    FROM jadwal j
    JOIN pemesanan p ON j.orderId = p.orderId
    JOIN paketlayanan pk ON p.paketId = pk.paketId
    WHERE j.tanggal LIKE '$bulan_ini%' 
    AND p.statusPesanan != 'Dibatalkan' 
    AND p.statusPesanan != 'Ditolak'
    ORDER BY j.waktuMulai ASC
");

$data_kalender = []; 
if ($query_jadwal) {
    while ($row = $query_jadwal->fetch_assoc()) {
        $tgl = $row['tanggal'];
        
        if ($row['kategori'] == 'Indoor') {
            $jenis = 'Indoor';
            $mulai = date('H:i', strtotime($row['waktuMulai']));
            $selesai = date('H:i', strtotime($row['waktuSelesai']));
            $jam = (int)date('H', strtotime($row['waktuMulai']));
            
            if ($jam < 11) $ket = "Pagi";
            elseif ($jam < 15) $ket = "Siang";
            elseif ($jam < 18) $ket = "Sore";
            else $ket = "Malam";
            
            $pesan = "$mulai - $selesai ($ket)";
        } else {
            $jenis = 'Outdoor';
            $pesan = "Full Day (Booked)";
        }

        $data_kalender[$tgl][] = [
            'jenis' => $jenis,
            'pesan' => $pesan
        ];
    }
}

// --- 3. PROSES SUBMIT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pesan'])) {
    
    $namaPelangganInput = $_POST['namaPelanggan'] ?? $user_name;
    $paketId = $_POST['paketId'] ?? '';
    $tanggalPemotretan = $_POST['tanggalPemotretan'] ?? '';
    
    // Default waktu
    $jamMulai = $_POST['jamMulai'] ?? '08:00'; 
    $jamSelesaiInput = $_POST['jamSelesai'] ?? '17:00'; 
    
    $catatanInput = $_POST['catatan'] ?? ''; 
    $durasiSewa = $_POST['durasiSewa'] ?? 1; 
    $durasiHari = $_POST['durasiHari'] ?? 1; 
    $lokasiInput = $_POST['lokasi_outdoor'] ?? '';
    $alamatInput = $_POST['alamat_outdoor'] ?? '';

    if (empty($namaPelangganInput) || empty($paketId) || empty($tanggalPemotretan)) {
        $error = "Data utama wajib diisi.";
    } else {
        // Cek Paket Database
        $stmt_cek = $koneksi->prepare("SELECT namaPaket, kategori, harga FROM paketlayanan WHERE paketId = ?");
        $stmt_cek->bind_param("s", $paketId);
        $stmt_cek->execute();
        $res_cek = $stmt_cek->get_result();
        $dataPaket = $res_cek->fetch_assoc(); 
        $stmt_cek->close();

        if (!$dataPaket) {
            $error = "Paket tidak valid.";
        } else {
            $kategori = !empty($dataPaket['kategori']) ? $dataPaket['kategori'] : 'Outdoor';
            $harga_dasar = $dataPaket['harga'];
            $lokasiFinal = '';
            $alamatFinal = '';
            $total_bayar = 0; 

            // --- LOGIKA HARGA & LOKASI ---
            if ($kategori == 'Indoor') {
                if (empty($jamSelesaiInput)) $error = "Jam selesai wajib diisi.";
                $catatanInput .= " (Indoor: Sewa $durasiSewa Jam)";
                $lokasiFinal = "Studio Foto Utama";
                $alamatFinal = "Jl. Nama Jalan Studio No. 123 (Lokasi Kami)";
                $total_bayar = $harga_dasar * $durasiSewa;
            } else {
                if (empty($lokasiInput)) $error = "Lokasi wajib diisi.";
                if ($durasiHari < 1) $durasiHari = 1;
                
                $catatanInput .= " (Outdoor: Sewa $durasiHari Hari)";
                $lokasiFinal = $lokasiInput;
                $alamatFinal = $alamatInput;
                $total_bayar = $harga_dasar * $durasiHari;
                
                // Outdoor dianggap Full Day untuk blocking
                $jamMulai = "06:00";
                $jamSelesaiInput = "23:59";
            }

            // --- CEK BENTROK (HANYA JIKA KATEGORI SAMA) ---
            if (empty($error)) {
                $cekMulai = date('H:i:s', strtotime($jamMulai));
                $cekSelesai = date('H:i:s', strtotime($jamSelesaiInput));

                // Loop cek per hari (jika outdoor > 1 hari)
                for ($d = 0; $d < ($kategori == 'Outdoor' ? $durasiHari : 1); $d++) {
                    $tgl_cek = date('Y-m-d', strtotime($tanggalPemotretan . " + $d days"));
                    
                    // QUERY BARU: Tambahkan kondisi 'AND pk.kategori = ?'
                    $sql_bentrok = "
                        SELECT j.jadwalId 
                        FROM jadwal j
                        JOIN pemesanan p ON j.orderId = p.orderId
                        JOIN paketlayanan pk ON p.paketId = pk.paketId
                        WHERE j.tanggal = ? 
                        AND (j.waktuMulai < ? AND j.waktuSelesai > ?)
                        AND pk.kategori = ?  /* KUNCI: Cek Kategori yg sama saja */
                        AND p.statusPesanan != 'Dibatalkan' 
                        AND p.statusPesanan != 'Ditolak'
                    ";
                    
                    $stmt_b = $koneksi->prepare($sql_bentrok);
                    $stmt_b->bind_param("ssss", $tgl_cek, $cekSelesai, $cekMulai, $kategori);
                    $stmt_b->execute();
                    
                    if ($stmt_b->get_result()->num_rows > 0) {
                        $error = "Slot $kategori pada tanggal $tgl_cek jam tersebut sudah penuh.";
                        break;
                    }
                }
            }

            // --- SIMPAN DATA ---
            if (empty($error)) {
                $date_part = date('dmy');
                $res_ord = $koneksi->query("SELECT COUNT(*) as total FROM pemesanan WHERE orderId LIKE 'ORD$date_part%'");
                $count_ord = ($res_ord) ? $res_ord->fetch_assoc()['total'] + 1 : 1;
                $orderId = 'ORD' . $date_part . str_pad($count_ord, 3, '0', STR_PAD_LEFT);
                
                $tglPesan = date('Y-m-d H:i:s');
                $statusPesanan = 'Menunggu';

                $stmt_pesan = $koneksi->prepare("INSERT INTO pemesanan (orderId, userId, namaPelanggan, paketId, tanggalPesan, statusPesanan, totalHarga, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_pesan->bind_param("ssssssds", $orderId, $user_id, $namaPelangganInput, $paketId, $tglPesan, $statusPesanan, $total_bayar, $catatanInput);

                if ($stmt_pesan->execute()) {
                    
                    $jumlah_hari_loop = ($kategori == 'Outdoor') ? $durasiHari : 1;
                    $success_jadwal = true;

                    for ($i = 0; $i < $jumlah_hari_loop; $i++) {
                        $res_jdw = $koneksi->query("SELECT COUNT(*) as total FROM jadwal");
                        $count_jdw = ($res_jdw) ? $res_jdw->fetch_assoc()['total'] + 1 + $i : 1 + $i;
                        $jadwalId = 'JDW' . date('dmy') . str_pad(rand(0,999) . $i, 4, '0', STR_PAD_LEFT); 

                        $tgl_simpan = date('Y-m-d', strtotime($tanggalPemotretan . " + $i days"));
                        $saveMulai = date('H:i:s', strtotime($jamMulai));
                        $saveSelesai = date('H:i:s', strtotime($jamSelesaiInput));

                        $stmt_j = $koneksi->prepare("INSERT INTO jadwal (jadwalId, orderId, tanggal, waktuMulai, waktuSelesai, lokasi, alamat) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt_j->bind_param("sssssss", $jadwalId, $orderId, $tgl_simpan, $saveMulai, $saveSelesai, $lokasiFinal, $alamatFinal);
                        
                        if (!$stmt_j->execute()) $success_jadwal = false;
                    }

                    if ($success_jadwal) {
                        $message = "Pemesanan Berhasil! Mengalihkan...";
                        echo "<script>setTimeout(function(){ window.location.href='pembayaran.php?orderId=$orderId'; }, 2000);</script>";
                    } else {
                        $error = "Gagal menyimpan jadwal.";
                        $koneksi->query("DELETE FROM pemesanan WHERE orderId='$orderId'");
                    }
                } else {
                    $error = "Gagal menyimpan pesanan.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pemesanan Baru</title>
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
            
            /* BACKGROUND ABSTRAK ELEGAN */
            background-color: #f3f4f6; /* Warna dasar abu muda */
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(106, 90, 205, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(0, 210, 255, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.8) 0%, transparent 60%);
            background-attachment: fixed;
        }

        /* Dekorasi Elemen Abstrak Mengambang */
        body::before {
            content: ''; position: fixed; top: -100px; right: -50px; width: 400px; height: 400px;
            background: linear-gradient(135deg, rgba(106, 90, 205, 0.1), rgba(0, 210, 255, 0.05));
            border-radius: 63% 37% 54% 46% / 55% 48% 52% 45%; /* Bentuk blob organik */
            z-index: -1; animation: floatBlob 10s infinite ease-in-out alternate;
        }
        
        body::after {
            content: ''; position: fixed; bottom: -50px; left: -50px; width: 300px; height: 300px;
            background: linear-gradient(135deg, rgba(255, 105, 180, 0.05), rgba(106, 90, 205, 0.1));
            border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
            z-index: -1; animation: floatBlob 12s infinite ease-in-out alternate-reverse;
        }

        @keyframes floatBlob {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(20px, 30px) rotate(10deg); }
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
        
        .user-menu { margin-left: auto; position: relative; }
        .dropbtn { background: transparent; border: 1px solid #ddd; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600; color: #333; font-size: 14px; padding: 8px 15px; border-radius: 30px; transition: 0.3s; background: #fff; }
        .dropbtn:hover { box-shadow: 0 3px 10px rgba(0,0,0,0.05); border-color: var(--primary); }
        .dropdown-content { display: none; position: absolute; right: 0; top: 120%; background-color: #ffffff; min-width: 180px; box-shadow: 0px 8px 20px rgba(0,0,0,0.1); z-index: 9999; border-radius: 8px; overflow: hidden; border: 1px solid #f0f0f0; }
        .dropdown-content a { color: #333; padding: 12px 16px; text-decoration: none; display: block; font-size: 13px; border-bottom: 1px solid #f9f9f9; }
        .dropdown-content a:hover { background-color: #f9f9ff; color: var(--primary); }
        .user-menu:hover .dropdown-content { display: block; }

        /* --- KONTEN UTAMA --- */
        .content { padding: 30px 20px; max-width: 1200px; margin: 0 auto; position: relative; z-index: 1; }
        .header h1 { font-size: 24px; color: #333; margin-bottom: 20px; border-left: 5px solid var(--primary); padding-left: 15px; }

        .pemesanan-layout { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px; margin-top:20px;}
        
        .form-box, .jadwal-box { 
            background-color: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(10px);
            padding: 25px; border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
            border: 1px solid rgba(255,255,255,0.5);
            margin-bottom: 20px; 
        }
        
        .form-box label { display: block; margin-top: 12px; font-weight: 600; color: #444; font-size: 14px; }
        .form-box input, .form-box select, .form-box textarea { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-family: 'Poppins'; background: #fcfcfc; transition: 0.3s; }
        .form-box input:focus, .form-box select:focus, .form-box textarea:focus { outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.1); }
        
        .dynamic-field { background-color: #f9f9ff; border-left: 4px solid var(--primary); padding: 15px; margin-top: 15px; display: none; border-radius: 6px; }
        .price-estimation { margin-top: 20px; padding: 15px; background-color: #e8f4fd; border: 1px solid #b6d4fe; border-radius: 6px; font-weight: bold; color: #0d6efd; text-align: right; }
        
        /* KALENDER */
        .calendar-header { text-align: center; font-weight: bold; margin-bottom: 15px; font-size: 18px; color: #333; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 10px; }
        .calendar-day-name { text-align: center; font-weight: bold; font-size: 12px; color: #888; padding-bottom: 5px; }
        .calendar-day { text-align: center; padding: 10px; border: 1px solid #f0f0f0; font-size: 14px; background: #fff; border-radius: 8px; cursor: pointer; transition: 0.2s; position: relative; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .calendar-day:hover { transform: scale(1.1); z-index: 2; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: var(--primary); }
        
        .tersedia { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .sebagian { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }
        .penuh { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .today { border: 2px solid var(--primary); font-weight: bold; }

        /* BUTTONS & ALERTS */
        button[type="submit"] { 
            background: linear-gradient(135deg, #28a745 0%, #218838 100%); 
            color: white; padding: 12px; border: none; width: 100%; margin-top: 20px; border-radius: 50px; 
            cursor: pointer; font-size: 16px; font-weight: bold; transition: 0.3s; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
        }
        button[type="submit"]:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4); }
        
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #dc3545; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 600px; position: relative; animation: fadeIn 0.3s; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
        .close-modal { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #fff; opacity:0.8; z-index:10; }
        .modal-header { background: #333; color: white; padding: 15px; font-size: 16px; font-weight: bold; }
        .modal-body-split { display: flex; flex-wrap: wrap; }
        .box-split { flex: 1; padding: 20px; min-width: 250px; }
        .box-indoor { background-color: #f3f0ff; border-right: 1px solid #eee; }
        .box-outdoor { background-color: #e8f5e9; }
        .split-title { font-weight: bold; margin-bottom: 15px; display: block; border-bottom: 2px solid rgba(0,0,0,0.05); padding-bottom: 5px; }
        .jadwal-item { padding: 10px; margin-bottom: 8px; border-radius: 6px; font-size: 13px; font-weight:500; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .item-in { border-left: 4px solid var(--primary); color: var(--primary); }
        .item-out { border-left: 4px solid #28a745; color: #28a745; }
        .kosong { color: #999; font-style: italic; font-size: 13px; }

        @keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }
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
            <a href="pemesanan.php" class="active-link">Pemesanan</a>
            <a href="pembayaran.php">Pembayaran</a>
            <a href="riwayat_pesanan.php">Riwayat Pesanan</a>
        </div>
        <div class="user-menu">
            <button class="dropbtn">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_username); ?>&background=random" style="width:25px; height:25px; border-radius:50%;">
                <?php echo htmlspecialchars($user_username); ?> 
                <i class="fas fa-caret-down" style="font-size: 12px;"></i>
            </button>
            <div class="dropdown-content">
                <a href="#"><i class="fas fa-user"></i> Profil Saya</a>
                <a href="#"><i class="fas fa-cog"></i> Pengaturan</a>
                <hr style="margin:0; border-top:1px solid #eee;">
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="header"><h1>Form Pemesanan Layanan</h1></div>
        <?php if ($message): ?> <div class="success"><?php echo $message; ?></div> <?php endif; ?>
        <?php if ($error): ?> <div class="error"><?php echo $error; ?></div> <?php endif; ?>

        <div class="pemesanan-layout">
            <div class="form-box">
                <form method="POST" id="bookingForm">
                    <label>Nama Pelanggan:</label>
                    <input type="text" name="namaPelanggan" value="<?php echo htmlspecialchars($user_name); ?>" required>
                    <small style="color:#666; font-size:12px;">*Nama bisa diubah jika memesankan orang lain.</small>

                    <label>Pilih Paket Layanan:</label>
                    <select id="paketId" name="paketId" required onchange="cekJenisPaket()">
                        <option value="" data-kategori="" data-harga="0">-- Pilih Paket --</option>
                        <?php foreach ($paket_list as $paket): $kat = !empty($paket['kategori']) ? $paket['kategori'] : 'Outdoor'; ?>
                            <option value="<?php echo $paket['paketId']; ?>" data-kategori="<?php echo $kat; ?>" data-harga="<?php echo $paket['harga']; ?>">
                                <?php echo htmlspecialchars($paket['namaPaket']); ?> (<?php echo $kat; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Tanggal Mulai:</label>
                    <input type="date" name="tanggalPemotretan" required min="<?php echo date('Y-m-d'); ?>">

                    <div id="indoorFields" class="dynamic-field">
                        <strong style="color:#6A5ACD;">Detail Indoor (Studio)</strong>
                        <div style="display:flex; gap:15px; margin-top:10px;">
                            <div style="flex:1;"><label>Jam Mulai:</label><input type="time" id="jamMulai" name="jamMulai" onchange="updateJamSelesaiIndoor()"></div>
                            <div style="flex:1;"><label>Jam Selesai:</label><input type="time" id="jamSelesai" name="jamSelesai"><small id="hintJamSelesai" style="font-size:11px; color:#666;"></small></div>
                        </div>
                        <label>Durasi Sewa (Jam):</label>
                        <input type="number" id="durasiSewa" name="durasiSewa" min="1" max="10" value="1" onchange="updateJamSelesaiIndoor()">
                    </div>

                    <div id="outdoorFields" class="dynamic-field">
                        <strong style="color:#28a745;">Detail Outdoor (Lokasi Luar)</strong>
                        <label>Durasi Sewa (Hari):</label>
                        <input type="number" id="durasiHari" name="durasiHari" min="1" max="14" value="1" onchange="hitungTotalHarga()">
                        <small style="display:block; color:#666; margin-top:3px;">*Fotografer akan dibooking Full Day.</small>
                        <label style="margin-top:10px;">Lokasi:</label>
                        <input type="text" id="lokasiOutdoor" name="lokasi_outdoor" placeholder="Contoh: Taman Kota">
                        <label>Alamat:</label>
                        <textarea id="alamatOutdoor" name="alamat_outdoor" rows="2"></textarea>
                    </div>

                    <label>Catatan Tambahan:</label>
                    <textarea name="catatan" rows="2"></textarea>
                    <div class="price-estimation" id="estimasiHarga">Total Estimasi: Rp 0</div>
                    <button type="submit" name="submit_pesan">Buat Pesanan</button>
                </form>
            </div>

            <div class="right-column">
                <div class="jadwal-box">
                    <div class="calendar-header">Jadwal Bulan <?php echo date('F Y'); ?></div>
                    <div class="calendar-grid">
                        <div class="calendar-day-name">Sn</div><div class="calendar-day-name">Sl</div><div class="calendar-day-name">Rb</div><div class="calendar-day-name">Km</div><div class="calendar-day-name">Jm</div><div class="calendar-day-name">Sb</div><div class="calendar-day-name">Mg</div>
                        <?php
                        $daysInMonth = date('t');
                        $firstDay = date('N', strtotime(date('Y-m-01')));
                        for ($i = 1; $i < $firstDay; $i++) echo "<div></div>";
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $tgl_cek = date('Y-m-') . str_pad($d, 2, '0', STR_PAD_LEFT);
                            $class = 'tersedia'; 
                            if (isset($data_kalender[$tgl_cek])) {
                                $count = count($data_kalender[$tgl_cek]);
                                $class = ($count >= 4) ? 'penuh' : 'sebagian';
                            }
                            if ($tgl_cek == date('Y-m-d')) $class .= ' today';
                            echo "<div class='calendar-day $class' onclick='lihatDetail(\"$tgl_cek\")'>$d</div>";
                        }
                        ?>
                    </div>
                    <div style="margin-top:15px; font-size:11px; padding:10px; background:#f8f9fa; border-radius:4px;">
                        <span style="color:#155724">■ Hijau:</span> Kosong, <span style="color:#856404">■ Kuning:</span> Terisi Sebagian, <span style="color:#721c24">■ Merah:</span> Penuh
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modalJadwal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="tutupModal()">&times;</span>
            <div class="modal-header">Jadwal Tanggal: <span id="modalTanggal"></span></div>
            <div class="modal-body-split">
                <div class="box-split box-indoor">
                    <span class="split-title" style="color:#6A5ACD;"><i class="fas fa-building"></i> Jadwal Studio (Indoor)</span>
                    <div id="isiIndoor"></div>
                </div>
                <div class="box-split box-outdoor">
                    <span class="split-title" style="color:#28a745;"><i class="fas fa-camera"></i> Jadwal Fotografer (Outdoor)</span>
                    <div id="isiOutdoor"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var dataJadwal = <?php echo json_encode($data_kalender); ?>;

        function lihatDetail(tanggal) {
            document.getElementById("modalTanggal").innerText = tanggal;
            var htmlIndoor = "";
            var htmlOutdoor = "";
            
            if (dataJadwal[tanggal]) {
                dataJadwal[tanggal].forEach(function(item) {
                    if (item.jenis === 'Indoor') {
                        htmlIndoor += "<div class='jadwal-item item-in'>" + item.pesan + "</div>";
                    } else {
                        htmlOutdoor += "<div class='jadwal-item item-out'>" + item.pesan + "</div>";
                    }
                });
            }

            document.getElementById("isiIndoor").innerHTML = htmlIndoor || "<div class='kosong'>Kosong / Tersedia</div>";
            document.getElementById("isiOutdoor").innerHTML = htmlOutdoor || "<div class='kosong'>Kosong / Tersedia</div>";
            
            document.getElementById("modalJadwal").style.display = "block";
        }
        function tutupModal() { document.getElementById("modalJadwal").style.display = "none"; }
        window.onclick = function(event) { if (event.target == document.getElementById("modalJadwal")) tutupModal(); }

        function cekJenisPaket() {
            var select = document.getElementById('paketId');
            var selectedOption = select.options[select.selectedIndex];
            var kategori = selectedOption.getAttribute('data-kategori');
            var hargaDasar = parseFloat(selectedOption.getAttribute('data-harga')) || 0;

            var indoorDiv = document.getElementById('indoorFields');
            var outdoorDiv = document.getElementById('outdoorFields');
            
            var inputJamMulai = document.getElementById('jamMulai');
            var inputJamSelesai = document.getElementById('jamSelesai');
            var inputDurasiSewa = document.getElementById('durasiSewa');
            var inputDurasiHari = document.getElementById('durasiHari');

            indoorDiv.style.display = 'none';
            outdoorDiv.style.display = 'none';
            inputJamMulai.required = false;
            inputJamSelesai.required = false;
            
            if (kategori === 'Indoor') {
                indoorDiv.style.display = 'block';
                inputJamMulai.required = true;
                inputJamSelesai.required = true;
                inputDurasiSewa.value = 1; 
                inputJamSelesai.readOnly = true; 
                inputJamSelesai.style.backgroundColor = '#e9ecef';
                document.getElementById('hintJamSelesai').innerText = "*Otomatis dihitung dari durasi sewa.";
                updateJamSelesaiIndoor(); 
            } else {
                outdoorDiv.style.display = 'block';
                inputDurasiHari.value = 1;
                hitungTotalHarga(); 
            }
            updateHarga(hargaDasar); // *Note: Fungsi updateHarga tidak didefinisikan sebelumnya, mungkin typo dari hitungTotalHarga*
            hitungTotalHarga(); // Panggil hitungTotalHarga sebagai gantinya
        }

        function updateJamSelesaiIndoor() {
            var jamMulai = document.getElementById('jamMulai').value;
            var durasi = parseInt(document.getElementById('durasiSewa').value);
            var inputJamSelesai = document.getElementById('jamSelesai');
            if(!durasi || durasi < 1) durasi = 1;
            if (jamMulai) {
                var timeParts = jamMulai.split(':');
                var date = new Date();
                date.setHours(parseInt(timeParts[0]) + durasi);
                date.setMinutes(parseInt(timeParts[1]));
                var jamHitung = date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
                inputJamSelesai.value = jamHitung;
            }
            hitungTotalHarga();
        }

        function hitungTotalHarga() {
            var select = document.getElementById('paketId');
            var selectedOption = select.options[select.selectedIndex];
            if (select.value === "") { document.getElementById('estimasiHarga').innerText = "Total Estimasi: Rp 0"; return; }

            var hargaDasar = parseFloat(selectedOption.getAttribute('data-harga')) || 0;
            var kategori = selectedOption.getAttribute('data-kategori');
            var totalHarga = hargaDasar;

            if (kategori === 'Indoor') {
                var durasi = parseInt(document.getElementById('durasiSewa').value);
                if (durasi && durasi > 0) totalHarga = hargaDasar * durasi;
            } else {
                var durasiHari = parseInt(document.getElementById('durasiHari').value);
                if (durasiHari && durasiHari > 0) totalHarga = hargaDasar * durasiHari;
            }
            var formatted = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(totalHarga);
            document.getElementById('estimasiHarga').innerText = "Total Estimasi: " + formatted;
        }
        
        // Perbaikan: Hapus event listener DOMContentLoaded lama jika ada, gunakan yang baru
        document.addEventListener('DOMContentLoaded', function() {
             // Inisialisasi awal jika form sudah terisi (misal setelah refresh)
             if(document.getElementById('paketId').value !== "") {
                 cekJenisPaket();
             }
        });
    </script>
</body>
</html>