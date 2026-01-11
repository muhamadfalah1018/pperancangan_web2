<?php
// riwayat_pesanan.php - FIXED: Background Smooth (No Lines) + Logo
session_start();
include('includes/db_koneksi.php');
include('includes/auto_batal.php'); 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Pelanggan') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_username = $_SESSION['username'];
$user_name = $_SESSION['name'];
$message = '';
$error = '';

// --- LOGIKA BATAL PESANAN ---
if (isset($_POST['action']) && $_POST['action'] == 'batalkan' && isset($_POST['orderId'])) {
    $orderIdBatal = $_POST['orderId'];
    $cek = $koneksi->query("SELECT statusPesanan FROM pemesanan WHERE orderId = '$orderIdBatal' AND userId = '$user_id'");
    $dataCek = $cek->fetch_assoc();
    
    if ($dataCek && $dataCek['statusPesanan'] == 'Menunggu') {
        $koneksi->query("DELETE FROM jadwal WHERE orderId = '$orderIdBatal'");
        $stmt = $koneksi->prepare("UPDATE pemesanan SET statusPesanan = 'Dibatalkan' WHERE orderId = ?");
        $stmt->bind_param("s", $orderIdBatal);
        if ($stmt->execute()) {
            $message = "Pesanan berhasil dibatalkan.";
        } else {
            $error = "Gagal membatalkan pesanan.";
        }
    } else {
        $error = "Pesanan tidak dapat dibatalkan.";
    }
}

// --- AMBIL DATA ---
$orders = [
    'Menunggu' => [],
    'Terjadwal' => [],
    'Selesai' => [],
    'Dibatalkan' => []
];

$query = "
    SELECT 
        p.orderId, p.tanggalPesan, p.statusPesanan, p.namaPelanggan, p.catatan,
        pk.namaPaket, 
        COALESCE(p.totalHarga, pk.harga) as hargaFinal, 
        b.statusBayar, b.metode,
        j.tanggal as tgl_foto, j.waktuMulai, j.waktuSelesai, j.lokasi,
        h.linkDrive
    FROM pemesanan p
    JOIN paketlayanan pk ON p.paketId = pk.paketId
    LEFT JOIN pembayaran b ON p.orderId = b.orderId
    LEFT JOIN jadwal j ON p.orderId = j.orderId
    LEFT JOIN hasilfoto h ON p.orderId = h.orderId
    WHERE p.userId = ?
    ORDER BY p.tanggalPesan DESC
";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_semua_pesanan = 0;

while ($row = $result->fetch_assoc()) {
    $st = $row['statusPesanan'];
    $sb = $row['statusBayar'];

    // Filter: Hanya tampilkan jika sudah ada status bayar
    if ($st == 'Menunggu' && (empty($sb) || $sb == 'Belum Bayar')) {
        continue; 
    }

    if (isset($orders[$st])) {
        $orders[$st][] = $row;
        $total_semua_pesanan++;
    }
}

function formatRupiah($angka) { return "Rp " . number_format($angka, 0, ',', '.'); }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pesanan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- STYLE GLOBAL (ABSTRACT SMOOTH - NO LINES) --- */
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
            
            /* BACKGROUND SMOOTH (GRADASI HALUS) */
            background-color: #f8f9fd;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,0) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,0) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,0) 0, transparent 50%);
            /* Tambahan hiasan lembut */
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            position: relative;
        }

        /* Hiasan Bulat (Blob) Halus - Tidak mengganggu navbar */
        body::before {
            content: ''; position: fixed; top: -100px; left: -50px; width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(106, 90, 205, 0.05) 0%, transparent 70%);
            border-radius: 50%; z-index: -1; animation: float 10s infinite ease-in-out;
        }
        body::after {
            content: ''; position: fixed; bottom: -100px; right: -50px; width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(0, 210, 255, 0.05) 0%, transparent 70%);
            border-radius: 50%; z-index: -1; animation: float 12s infinite ease-in-out reverse;
        }
        @keyframes float {
            0% { transform: translateY(0); }
            50% { transform: translateY(20px); }
            100% { transform: translateY(0); }
        }

        /* --- NAVBAR --- */
        .top-nav { 
            background-color: rgba(255, 255, 255, 0.95); /* Lebih solid agar tidak tembus pandang berlebihan */
            backdrop-filter: blur(10px);
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
        
        .user-menu { margin-left: auto; font-weight: 600; font-size: 14px; color: #555; }

        /* --- TABS --- */
        .tab-container { display: flex; justify-content: center; margin-bottom: 30px; border-bottom: 2px solid rgba(0,0,0,0.05); flex-wrap: wrap; }
        .tab-button { background: none; border: none; padding: 15px 30px; font-size: 15px; cursor: pointer; color: #888; font-weight: 600; position: relative; transition: 0.3s; font-family: 'Poppins'; }
        .tab-button:hover { color: var(--primary); background: rgba(106, 90, 205, 0.05); border-radius: 8px 8px 0 0; }
        .tab-button.active { color: var(--primary); border-bottom: 3px solid var(--primary); margin-bottom: -2px; }

        /* --- CONTENT --- */
        .content { padding: 40px 20px; max-width: 1000px; margin: 0 auto; position: relative; z-index: 1; }
        
        /* Kartu Pesanan */
        .order-card { 
            background-color: #ffffff; border: 1px solid #f0f0f0; border-radius: 16px; 
            padding: 25px; margin-bottom: 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.03); 
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; 
            transition: transform 0.3s, box-shadow 0.3s; 
        }
        .order-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(106, 90, 205, 0.1); border-color: rgba(106, 90, 205, 0.2); }
        
        .card-info { flex: 1; min-width: 300px; padding-right: 20px; }
        .info-title { font-weight: 700; font-size: 18px; margin-bottom: 12px; color: #333; display: flex; align-items: center; gap: 10px; }
        .info-row { font-size: 14px; color: #666; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .info-row i { color: #aaa; width: 20px; text-align: center; }
        
        /* Badge Status */
        .badge { padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 600; display: inline-block; min-width: 100px; text-align: center; }
        .bg-danger { background: #fff5f5; color: #c62828; border: 1px solid #ffcdd2; } 
        .bg-ok { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }     
        .bg-info { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }    
        .bg-dp { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; }

        /* Buttons */
        .btn-detail { background: #fff; border: 1px solid var(--primary); color: var(--primary); padding: 8px 20px; border-radius: 50px; font-weight: bold; cursor: pointer; transition: 0.3s; text-decoration: none; }
        .btn-detail:hover { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(106, 90, 205, 0.3); }
        
        /* --- EMPTY STATE --- */
        .empty-hero {
            text-align: center; background: #fff; padding: 60px 40px; border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.05); margin-top: 30px; border: 1px solid #eee;
        }
        .empty-hero i { font-size: 60px; color: #ccc; margin-bottom: 20px; }
        .empty-hero h2 { color: #333; margin-bottom: 10px; font-size: 24px; }
        .empty-hero p { color: #666; margin-bottom: 30px; line-height: 1.6; }
        .btn-big-order {
            background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%);
            color: white; padding: 15px 40px; border-radius: 50px; text-decoration: none;
            font-weight: bold; font-size: 16px; box-shadow: 0 10px 25px rgba(106, 90, 205, 0.3);
            transition: 0.3s; display: inline-block;
        }
        .btn-big-order:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(106, 90, 205, 0.5); }

        /* --- MODAL --- */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background-color: #fff; color: #333; margin: 8% auto; padding: 30px; border-radius: 20px; width: 90%; max-width: 550px; font-family: 'Poppins'; box-shadow: 0 25px 60px rgba(0,0,0,0.2); position: relative; animation: zoomIn 0.3s; border: 1px solid #eee; }
        .modal-header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; font-size: 20px; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        .detail-row { display: flex; margin-bottom: 12px; font-size: 14px; align-items: flex-start; }
        .d-label { width: 140px; color: #888; font-weight: 500; }
        .d-val { flex: 1; color: #333; font-weight: 600; }
        .close { position: absolute; top: 20px; right: 25px; font-size: 24px; color: #aaa; cursor: pointer; transition: 0.2s; }
        .close:hover { color: #333; }
        @keyframes zoomIn { from {transform:scale(0.95); opacity:0;} to {transform:scale(1); opacity:1;} }
        
        .tab-content { display: none; }

        /* USER DROPDOWN */
        .dropbtn { background: transparent; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600; color: #555; font-size: 14px; padding: 8px 15px; border-radius: 30px; transition: 0.3s; }
        .dropbtn:hover { background-color: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .dropdown-content { display: none; position: absolute; right: 0; top: 120%; background-color: #ffffff; min-width: 180px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; border: 1px solid #f0f0f0; }
        .dropdown-content a { color: #333; padding: 12px 16px; text-decoration: none; display: block; font-size: 13px; border-bottom: 1px solid #f9f9f9; }
        .dropdown-content a:hover { background-color: #f9f9ff; color: var(--primary); }
        .user-menu:hover .dropdown-content { display: block; }
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
            <a href="pembayaran.php">Pembayaran</a>
            <a href="riwayat_pesanan.php" class="active-link">Riwayat Pesanan</a>
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
        
        <?php if ($total_semua_pesanan == 0): ?>
            <div class="empty-hero">
                <i class="fas fa-camera-retro"></i>
                <h2>Halo, <?php echo htmlspecialchars($user_name); ?>!</h2>
                <p>Sepertinya Anda belum pernah melakukan pemesanan di studio kami.<br>
                Yuk, abadikan momen spesialmu sekarang juga dengan paket menarik dari Enematika Studio!</p>
                <a href="pemesanan.php" class="btn-big-order"><i class="fas fa-plus-circle"></i> Pesan Sekarang</a>
            </div>
        <?php else: ?>
            
            <h2 style="text-align: center; margin-bottom: 10px; color:#333; font-weight:700;">Riwayat Pesanan</h2>
            <p style="text-align:center; color:#666; font-size:14px; margin-bottom:40px;">Pantau status pesanan Anda di sini.</p>
            
            <?php if ($message): ?><p style="text-align:center; color:green; background:#d4edda; padding:10px; border-radius:5px;"><?php echo $message; ?></p><?php endif; ?>
            <?php if ($error): ?><p style="text-align:center; color:red; background:#f8d7da; padding:10px; border-radius:5px;"><?php echo $error; ?></p><?php endif; ?>

            <div class="tab-container">
                <button class="tab-button active" onclick="openTab('Menunggu')">Menunggu Konfirmasi</button>
                <button class="tab-button" onclick="openTab('Terjadwal')">Terjadwal</button>
                <button class="tab-button" onclick="openTab('Selesai')">Selesai</button>
                <button class="tab-button" onclick="openTab('Dibatalkan')">Dibatalkan</button>
            </div>

            <?php 
            $statuses = ['Menunggu', 'Terjadwal', 'Selesai', 'Dibatalkan'];
            foreach ($statuses as $statusKey): 
                $dataOrders = $orders[$statusKey];
            ?>
            <div id="<?php echo $statusKey; ?>" class="tab-content" style="display: <?php echo ($statusKey == 'Menunggu') ? 'block' : 'none'; ?>;">
                
                <?php if (empty($dataOrders)): ?>
                    <div style="text-align: center; padding: 60px; color: #999; background:rgba(255,255,255,0.6); border-radius:12px; border:2px dashed #ddd;">
                        <i class="fas fa-box-open" style="font-size: 40px; margin-bottom: 15px; opacity:0.5;"></i><br>
                        Tidak ada riwayat di kategori ini.<br>
                    </div>
                <?php else: ?>
                    <?php foreach ($dataOrders as $d): 
                        $stBayarDB = !empty($d['statusBayar']) ? $d['statusBayar'] : 'Belum Dibayar';
                        
                        $classBadge = 'bg-info'; 
                        if ($stBayarDB == 'Lunas') { $classBadge = 'bg-ok'; } 
                        elseif ($stBayarDB == 'Menunggu Verifikasi') { $classBadge = 'bg-info'; } 
                        elseif (stripos($stBayarDB, 'DP') !== false) { $classBadge = 'bg-dp'; } 
                        elseif ($stBayarDB == 'Ditolak') { $classBadge = 'bg-danger'; }
                    ?>
                    
                    <div class="order-card">
                        <div class="card-info">
                            <div class="info-title">
                                <i class="fas fa-camera" style="color:var(--primary);"></i> <?php echo htmlspecialchars($d['namaPaket']); ?>
                            </div>
                            <div class="info-row"><i class="fas fa-receipt"></i> ID: <b><?php echo $d['orderId']; ?></b></div>
                            <div class="info-row"><i class="fas fa-calendar-alt"></i> Tgl Pesan: <?php echo date('d M Y', strtotime($d['tanggalPesan'])); ?></div>
                            <div class="info-row"><i class="fas fa-money-bill-wave"></i> Total: <b style="color:var(--primary);"><?php echo formatRupiah($d['hargaFinal']); ?></b></div>
                            <div class="info-row"><i class="fas fa-info-circle"></i> Status Bayar: <span class="badge <?php echo $classBadge; ?>"><?php echo $stBayarDB; ?></span></div>
                        </div>
                        
                        <div class="card-actions">
                            <button class="btn-detail" onclick='openModal(<?php echo json_encode($d); ?>)'>Lihat Detail</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div class="modal-header"><i class="fas fa-file-invoice"></i> Detail Pesanan</div>
            <div id="modalBody"></div>
            <div style="margin-top:20px; text-align:right; border-top:1px solid #eee; padding-top:15px;">
                <button onclick="closeModal()" style="background:#eee; color:#333; border:none; padding:10px 25px; cursor:pointer; border-radius:50px; font-weight:bold;">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function openTab(statusName) {
            var i;
            var x = document.getElementsByClassName("tab-content");
            var tabs = document.getElementsByClassName("tab-button");
            for (i = 0; i < x.length; i++) { x[i].style.display = "none"; }
            for (i = 0; i < tabs.length; i++) { tabs[i].classList.remove("active"); }
            document.getElementById(statusName).style.display = "block";
            
            var buttons = document.getElementsByTagName("button");
            for (i = 0; i < buttons.length; i++) {
                if(buttons[i].textContent == statusName) buttons[i].classList.add("active");
            }
        }

        var modal = document.getElementById("detailModal");

        function openModal(data) {
            var jadwal = data.tgl_foto ? (data.tgl_foto + " (" + data.waktuMulai + " WIB)") : "Belum ditentukan";
            var lokasi = data.lokasi ? data.lokasi : "-";
            var harga = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(data.hargaFinal);
            var statusBayar = data.statusBayar ? data.statusBayar : 'Belum Dibayar';

            var html = `
                <div class="detail-row"><span class="d-label">ID Pesanan</span> <span class="d-val blue">#${data.orderId}</span></div>
                <div class="detail-row"><span class="d-label">Nama Paket</span> <span class="d-val">${data.namaPaket}</span></div>
                <div class="detail-row"><span class="d-label">Total Harga</span> <span class="d-val">${harga}</span></div>
                <div class="detail-row"><span class="d-label">Tanggal Pesan</span> <span class="d-val">${data.tanggalPesan}</span></div>
                <hr style="border-color:#eee; margin:15px 0;">
                <div class="detail-row"><span class="d-label">Status Pesanan</span> <span class="d-val" style="color:var(--primary);">${data.statusPesanan}</span></div>
                <div class="detail-row"><span class="d-label">Pembayaran</span> <span class="d-val">${statusBayar}</span></div>
                <div class="detail-row"><span class="d-label">Metode</span> <span class="d-val">${data.metode || '-'}</span></div>
                <div class="detail-row"><span class="d-label">Jadwal Foto</span> <span class="d-val">${jadwal}</span></div>
                <div class="detail-row"><span class="d-label">Lokasi</span> <span class="d-val">${lokasi}</span></div>
                <div class="detail-row"><span class="d-label">Catatan</span> <span class="d-val" style="font-weight:normal; color:#666;">${data.catatan || '-'}</span></div>
            `;
            
            if(data.linkDrive) {
                html += `<hr style="border-color:#eee; margin:15px 0;"><div class="detail-row"><span class="d-label">Hasil Foto</span> <a href="${data.linkDrive}" target="_blank" style="color:#007bff; text-decoration:underline; font-weight:bold;">[ Download Foto ]</a></div>`;
            }

            document.getElementById("modalBody").innerHTML = html;
            modal.style.display = "block";
        }

        function closeModal() { modal.style.display = "none"; }
        window.onclick = function(event) { if (event.target == modal) closeModal(); }
    </script>
</body>
</html>