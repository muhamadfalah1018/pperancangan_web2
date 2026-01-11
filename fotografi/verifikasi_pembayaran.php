<?php
// verifikasi_pembayaran.php - FIXED: Status Badge Visibility
session_start();
include('includes/db_koneksi.php');

// Cek Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$message = '';
$error = '';

// --- PROSES VERIFIKASI ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $pembayaranId = $_POST['pembayaranId'];
    $orderId = $_POST['orderId'];
    $action = $_POST['action'];

    if ($action == 'terima') {
        // Cek Metode untuk menentukan Status Lunas / DP
        $cek = $koneksi->query("SELECT metode FROM pembayaran WHERE pembayaranId = '$pembayaranId'");
        $dataMetode = $cek->fetch_assoc();
        $metode = $dataMetode['metode'];

        // Deteksi string "DP" di metode pembayaran
        if (strpos($metode, 'DP') !== false) {
            $statusBaru = 'DP 50%'; 
        } else {
            $statusBaru = 'Lunas';
        }

        $koneksi->query("UPDATE pembayaran SET statusBayar = '$statusBaru' WHERE pembayaranId = '$pembayaranId'");
        $koneksi->query("UPDATE pemesanan SET statusPesanan = 'Terjadwal' WHERE orderId = '$orderId'");
        
        $message = "Pembayaran Berhasil Diterima. Status: <b>$statusBaru</b>.";

    } elseif ($action == 'tolak') {
        $koneksi->query("UPDATE pembayaran SET statusBayar = 'Ditolak' WHERE pembayaranId = '$pembayaranId'");
        $error = "Pembayaran ditolak.";
    }
}

// --- FILTER DATA ---
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';

// --- QUERY DATA ---
$query = "
    SELECT 
        pay.pembayaranId, pay.orderId, pay.tanggalBayar, pay.metode, pay.statusBayar, pay.buktiBayarLink, pay.catatan as catatan_bayar,
        ord.namaPelanggan, ord.tanggalPesan,
        pkt.namaPaket,
        COALESCE(ord.totalHarga, pkt.harga) as hargaFinal,
        u.email, 
        j.tanggal as tgl_foto, j.waktuMulai, j.waktuSelesai, j.lokasi, j.alamat as alamat_foto
    FROM pembayaran pay
    JOIN pemesanan ord ON pay.orderId = ord.orderId
    JOIN user u ON ord.userId = u.userId
    JOIN paketlayanan pkt ON ord.paketId = pkt.paketId
    LEFT JOIN jadwal j ON ord.orderId = j.orderId
    WHERE 1=1
";

if ($search) { $query .= " AND (ord.namaPelanggan LIKE '%$search%' OR pay.orderId LIKE '%$search%')"; }
if ($filter_status) { $query .= " AND pay.statusBayar = '$filter_status'"; }

$query .= " ORDER BY pay.tanggalBayar DESC";
$result = $koneksi->query($query);

function formatRupiah($angka) { return "Rp " . number_format($angka, 0, ',', '.'); }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Pembayaran</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- STYLE GLOBAL --- */
        :root { --primary: #6A5ACD; --text-dark: #333; --text-gray: #666; --white: #ffffff; }
        
        body { 
            font-family: 'Poppins', sans-serif; margin: 0; padding: 0; color: var(--text-dark); min-height: 100vh;
            background: linear-gradient(-45deg, #e3eeff, #f3e7e9, #e8dbfc, #f5f7fa); 
            background-size: 400% 400%; animation: gradientBG 15s ease infinite; 
        }
        @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        
        /* NAVBAR */
        .top-nav { background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); height: 80px; display: flex; align-items: center; padding: 0 40px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 15px rgba(0,0,0,0.03); }
        .logo-nav { display: flex; align-items: center; width: 250px; gap: 15px; }
        .logo-circle { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .brand-text { font-weight: 700; font-size: 20px; color: var(--primary); }
        
        .nav-links { flex-grow: 1; display: flex; gap: 10px; margin-left: 20px; }
        .nav-links a { color: var(--text-gray); font-weight: 500; font-size: 14px; padding: 12px 18px; border-radius: 12px; transition: all 0.3s ease; text-decoration: none; }
        .nav-links a:hover, .nav-links .active-link { color: var(--primary); background-color: rgba(106, 90, 205, 0.1); font-weight: 600; }
        
        .user-menu { margin-left: auto; position: relative; }
        .dropbtn { background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: 600; color: var(--text-dark); font-size: 14px; padding: 8px 15px; border-radius: 30px; transition: 0.3s; }
        .dropbtn:hover { background-color: rgba(0,0,0,0.05); }
        
        .dropdown-content { display: none; position: absolute; right: 0; top: 120%; background-color: var(--white); min-width: 200px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
        .dropdown-content a { color: var(--text-dark); padding: 12px 20px; display: block; font-size: 14px; border-bottom: 1px solid #f9f9f9; text-decoration: none; }
        .dropdown-content a:hover { background-color: #f9f9ff; color: var(--primary); }
        .user-menu:hover .dropdown-content { display: block; }

        /* KONTEN */
        .content { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-header h2 { margin: 0 0 5px 0; font-weight: 700; color: var(--text-dark); }
        
        /* FILTER */
        .filter-bar { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-input { padding: 10px 15px; border: 1px solid #ddd; border-radius: 50px; font-size: 14px; outline: none; transition: 0.3s; background: rgba(255,255,255,0.8); }
        .filter-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.1); background: #fff; }
        .btn-reset { background: #fff; border: 1px solid #ddd; color: var(--text-dark); padding: 10px 20px; border-radius: 50px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s; }
        .btn-reset:hover { background: #f0f0f0; }

        /* TABEL */
        .table-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid rgba(255,255,255,0.5); }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { background-color: rgba(106, 90, 205, 0.05); color: var(--primary); font-weight: 700; padding: 20px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #eee; }
        .modern-table td { padding: 20px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; font-size: 14px; }
        .modern-table tr:hover { background-color: #fff; }

        /* BADGES STATUS - FIXED */
        .badge { 
            padding: 8px 15px; border-radius: 30px; font-size: 11px; font-weight: 800; text-transform: uppercase; 
            display: inline-block; min-width: 120px; text-align: center; white-space: nowrap;
        }
        
        /* Warna Badge yang Kontras */
        .st-wait { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }  /* Kuning Gelap */
        .st-lunas { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; } /* Hijau Gelap */
        .st-dp { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }    /* Biru Gelap */
        .st-tolak { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }  /* Merah Gelap */
        .st-bayarditempat { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; } /* Abu-abu */

        /* BUTTONS */
        .btn-act { border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; margin-right: 5px; color: #fff; transition: 0.2s; display:inline-flex; align-items:center; gap:5px; }
        .btn-detail { background: #6A5ACD; box-shadow: 0 4px 10px rgba(106, 90, 205, 0.3); }
        .btn-accept { background: #28a745; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3); }
        .btn-reject { background: #dc3545; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); }
        .btn-act:hover { transform: translateY(-2px); opacity: 0.9; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background: #fff; margin: 5vh auto; padding: 30px; width: 600px; max-width: 90%; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); animation: slideUpModal 0.3s ease; max-height: 90vh; overflow-y: auto; }
        @keyframes slideUpModal { from {transform:translateY(50px); opacity:0;} to {transform:translateY(0); opacity:1;} }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .modal-header h3 { margin: 0; color: var(--primary); font-size: 20px; }
        .close-modal { font-size: 24px; color: #999; cursor: pointer; transition: 0.2s; }
        .close-modal:hover { color: #333; }

        .detail-group { margin-bottom: 15px; }
        .d-label { font-size: 12px; color: #888; text-transform: uppercase; font-weight: 600; margin-bottom: 3px; }
        .d-val { font-size: 15px; color: #333; font-weight: 500; }
        .d-highlight { color: var(--primary); font-weight: 700; font-size: 16px; }
        
        .img-preview { width: 100%; max-height: 300px; object-fit: contain; border-radius: 10px; border: 1px solid #eee; margin-top: 10px; background: #fafafa; }
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
            <a href="admin_paket.php">Paket</a>
            <a href="verifikasi_pembayaran.php" class="active-link">Pembayaran</a>
            <a href="verifikasi_pemesanan.php">Pemesanan</a>
            <a href="jadwal.php">Jadwal</a>
            <a href="kelola_galeri.php">galeri</a>
            <a href="laporan.php">Laporan</a>
        </div>
        <div class="user-menu">
            <button class="dropbtn">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_username); ?>&background=random" style="width:30px; height:30px; border-radius:50%;">
                <?php echo htmlspecialchars($user_username); ?> <i class="fas fa-chevron-down" style="font-size:10px;"></i>
            </button>
            <div class="dropdown-content">
                <a href="#"><i class="fas fa-user"></i> Profil Saya</a>
                <a href="logout.php" style="color:red;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="page-header">
            <h2>Verifikasi Pembayaran</h2>
        </div>

        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="filter-input" placeholder="Cari ID / Nama..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status" class="filter-input" onchange="this.form.submit()">
                <option value="">-- Semua Status --</option>
                <option value="Menunggu Verifikasi" <?php if($filter_status=='Menunggu Verifikasi') echo 'selected'; ?>>Menunggu Verifikasi</option>
                <option value="Lunas" <?php if($filter_status=='Lunas') echo 'selected'; ?>>Lunas</option>
                <option value="DP 50%" <?php if($filter_status=='DP 50%') echo 'selected'; ?>>DP 50%</option>
                <option value="Ditolak" <?php if($filter_status=='Ditolak') echo 'selected'; ?>>Ditolak</option>
            </select>
            <a href="verifikasi_pembayaran.php" class="btn-reset">Reset Filter</a>
        </form>

        <?php if ($message): ?> <div style="background:#d4edda; color:#155724; padding:15px; border-radius:10px; margin-bottom:20px;"><?php echo $message; ?></div> <?php endif; ?>
        <?php if ($error): ?> <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:10px; margin-bottom:20px;"><?php echo $error; ?></div> <?php endif; ?>

        <div class="table-card">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID & Nama</th>
                        <th>Paket</th>
                        <th>Tagihan</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $st = !empty($row['statusBayar']) ? $row['statusBayar'] : 'Belum Ada Status';
                            
                            // Logika Warna Badge yang Lebih Kuat
                            $badgeClass = 'st-wait'; 
                            if ($st == 'Lunas') $badgeClass = 'st-lunas';
                            elseif (stripos($st, 'DP') !== false) $badgeClass = 'st-dp';
                            elseif ($st == 'Ditolak') $badgeClass = 'st-tolak';
                            elseif ($st == 'Bayar di Tempat') $badgeClass = 'st-bayarditempat';

                            $row['hargaFormatted'] = formatRupiah($row['hargaFinal']);
                            $jsonData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr>
                                <td><b><?php echo $row['orderId']; ?></b><br><span style="font-size:12px; color:#888;"><?php echo htmlspecialchars($row['namaPelanggan']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['namaPaket']); ?></td>
                                <td style="color:#6A5ACD; font-weight:600;"><?php echo formatRupiah($row['hargaFinal']); ?></td>
                                <td><?php echo $row['metode']; ?></td>
                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $st; ?></span></td>
                                <td>
                                    <button class="btn-act btn-detail" onclick='openModal(<?php echo $jsonData; ?>)'><i class="fas fa-eye"></i></button>
                                    <?php if ($st == 'Menunggu Verifikasi'): ?>
                                        <form method="POST" onsubmit="return confirm('Validasi pembayaran ini?');" style="display:inline;">
                                            <input type="hidden" name="pembayaranId" value="<?php echo $row['pembayaranId']; ?>">
                                            <input type="hidden" name="orderId" value="<?php echo $row['orderId']; ?>">
                                            <button type="submit" name="action" value="terima" class="btn-act btn-accept"><i class="fas fa-check"></i></button>
                                            <button type="submit" name="action" value="tolak" class="btn-act btn-reject"><i class="fas fa-times"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#999;">Belum ada data pembayaran.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Rincian Pembayaran</h3>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="detail-group"><div class="d-label">Nama Pelanggan</div><div class="d-val" id="m_nama"></div></div>
                    <div class="detail-group"><div class="d-label">ID Pesanan</div><div class="d-val" id="m_orderId"></div></div>
                    <div class="detail-group"><div class="d-label">Paket Layanan</div><div class="d-val" id="m_paket"></div></div>
                    <div class="detail-group"><div class="d-label">Total Tagihan</div><div class="d-val d-highlight" id="m_harga"></div></div>
                </div>
                <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
                <div class="detail-group"><div class="d-label">Jadwal Foto</div><div class="d-val" id="m_jadwal"></div></div>
                <div class="detail-group"><div class="d-label">Metode Bayar</div><div class="d-val" id="m_metode"></div></div>
                <div class="detail-group"><div class="d-label">Catatan User</div><div class="d-val" id="m_catatan"></div></div>
                
                <div style="margin-top:20px;">
                    <div class="d-label">Bukti Transfer</div>
                    <img id="m_bukti" src="" class="img-preview" alt="Bukti">
                    <p id="m_no_bukti" style="color:red; display:none; font-size:13px; margin-top:5px;">Tidak ada bukti diupload.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(data) {
            document.getElementById('m_nama').innerText = data.namaPelanggan;
            document.getElementById('m_orderId').innerText = data.orderId;
            document.getElementById('m_paket').innerText = data.namaPaket;
            document.getElementById('m_harga').innerText = data.hargaFormatted;
            
            var jadwalInfo = (data.tgl_foto || 'Belum dijadwalkan');
            if(data.waktuMulai) jadwalInfo += ' (' + data.waktuMulai + ' - ' + data.waktuSelesai + ')';
            document.getElementById('m_jadwal').innerText = jadwalInfo;

            document.getElementById('m_metode').innerText = data.metode + ' (' + data.tanggalBayar + ')';
            document.getElementById('m_catatan').innerText = data.catatan_bayar || '-';

            var img = document.getElementById('m_bukti');
            var txt = document.getElementById('m_no_bukti');
            if (data.buktiBayarLink) { img.src = data.buktiBayarLink; img.style.display = 'block'; txt.style.display = 'none'; } 
            else { img.style.display = 'none'; txt.style.display = 'block'; }

            document.getElementById('detailModal').style.display = 'block';
        }
        function closeModal() { document.getElementById('detailModal').style.display = 'none'; }
        window.onclick = function(e) { if(e.target == document.getElementById('detailModal')) closeModal(); }
    </script>
</body>
</html>