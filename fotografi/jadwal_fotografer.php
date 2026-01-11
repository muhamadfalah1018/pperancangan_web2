<?php
// jadwal_fotografer.php - FIXED: Button Available for ALL Dates
session_start();
include('includes/db_koneksi.php');

// CEK AKSES FOTOGRAFER
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Fotografer') {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$today = date('Y-m-d');

// Inisialisasi Variabel Pesan
$message = '';
$error = '';

// --- PROSES TANDAI SELESAI ---
if (isset($_POST['action']) && $_POST['action'] == 'selesai') {
    $orderIdSelesai = $_POST['orderId'];
    // Update status pesanan menjadi Selesai
    $stmt = $koneksi->prepare("UPDATE pemesanan SET statusPesanan = 'Selesai' WHERE orderId = ?");
    $stmt->bind_param("s", $orderIdSelesai);
    
    if ($stmt->execute()) {
        $message = "Status berhasil diperbarui! Tugas telah selesai.";
    } else {
        $error = "Gagal mengupdate status: " . $koneksi->error;
    }
}

// --- FILTER DATA ---
$search = $_GET['search'] ?? '';
$filter_tgl = $_GET['tgl'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Query Data
$query = "
    SELECT 
        j.*, 
        p.namaPelanggan, p.statusPesanan, p.catatan,
        pk.namaPaket, pk.kategori
    FROM jadwal j
    JOIN pemesanan p ON j.orderId = p.orderId
    JOIN paketlayanan pk ON p.paketId = pk.paketId
    WHERE 1=1
";

// Filter Logic
if ($search) {
    $query .= " AND (p.namaPelanggan LIKE '%$search%' OR j.orderId LIKE '%$search%' OR j.lokasi LIKE '%$search%')";
}
if ($filter_tgl) {
    $query .= " AND j.tanggal = '$filter_tgl'";
}
if ($filter_status) {
    $query .= " AND p.statusPesanan = '$filter_status'";
} else {
    // Default: Tampilkan Terjadwal & Selesai (Semua tanggal masuk)
    $query .= " AND p.statusPesanan IN ('Terjadwal', 'Selesai')";
}

// Urutkan: Tanggal (Ascending) agar jadwal terdekat/hari ini ada di atas
$query .= " ORDER BY j.tanggal ASC, j.waktuMulai ASC";
$result = $koneksi->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Pemotretan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- STYLE GLOBAL (MODERN GEOMETRIC) --- */
        :root {
            --primary: #6A5ACD;
            --primary-soft: rgba(106, 90, 205, 0.1);
            --white: #ffffff;
            --text-dark: #333;
            --text-gray: #666;
            --shadow: 0 8px 25px rgba(0,0,0,0.05);
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; padding: 0; 
            min-height: 100vh;
            color: var(--text-dark);
            overflow-x: hidden;
            background-color: #f4f6f9;
            background-image: linear-gradient(#eef2f3 1px, transparent 1px), linear-gradient(90deg, #eef2f3 1px, transparent 1px);
            background-size: 40px 40px; 
        }

        /* Hiasan Latar Belakang */
        body::before {
            content: ''; position: fixed; top: -150px; right: -50px; width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(106, 90, 205, 0.08) 0%, transparent 70%);
            border-radius: 50%; z-index: -1; animation: pulseBlob 8s infinite alternate;
        }
        @keyframes pulseBlob {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.1); opacity: 1; }
        }
        
        a { text-decoration: none; }

        /* --- NAVBAR --- */
        .top-nav { 
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            height: 80px; 
            display: flex; align-items: center; padding: 0 40px; 
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.04);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .logo-nav { display: flex; align-items: center; width: 250px; gap: 15px; }
        .logo-circle {
            width: 50px; height: 50px; border-radius: 50%; object-fit: cover;
            border: 2px solid var(--primary); 
            box-shadow: 0 4px 10px rgba(106, 90, 205, 0.2);
        }
        .brand-text { font-weight: 700; font-size: 20px; color: var(--primary); letter-spacing: 0.5px; }

        .nav-links { flex-grow: 1; display: flex; gap: 10px; margin-left: 20px; }
        .nav-links a { 
            color: var(--text-gray); font-weight: 500; font-size: 14px; padding: 12px 20px; 
            border-radius: 30px; transition: all 0.3s ease; 
        }
        .nav-links a:hover, .nav-links .active-link { color: var(--primary); background-color: var(--primary-soft); font-weight: 600; }
        
        .user-menu { margin-left: auto; position: relative; }
        .dropbtn { 
            background: #fff; border: 1px solid #eee; cursor: pointer; 
            display: flex; align-items: center; gap: 12px; 
            padding: 6px 20px 6px 8px; border-radius: 50px; transition: 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .dropbtn:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.08); transform: translateY(-1px); }
        
        .user-info { text-align: left; line-height: 1.2; display: flex; flex-direction: column; }
        .user-name { font-weight: 600; font-size: 14px; color: var(--text-dark); }
        .user-role { font-size: 10px; color: var(--primary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: var(--primary-soft); padding: 2px 8px; border-radius: 4px; width: fit-content; margin-top: 2px; }

        .dropdown-content { display: none; position: absolute; right: 0; top: 130%; background-color: var(--white); min-width: 200px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 12px; overflow: hidden; animation: slideUp 0.3s ease; border: 1px solid #f0f0f0; }
        .dropdown-content a { color: var(--text-dark); padding: 12px 20px; display: block; font-size: 14px; border-bottom: 1px solid #f9f9f9; }
        .dropdown-content a:hover { background-color: #f9f9ff; color: var(--primary); }
        .user-menu:hover .dropdown-content { display: block; }
        @keyframes slideUp { from {opacity:0; transform:translateY(10px);} to {opacity:1; transform:translateY(0);} }

        /* --- CONTENT --- */
        .content { max-width: 1200px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h2 { margin: 0; font-weight: 700; color: var(--text-dark); border-left: 6px solid var(--primary); padding-left: 15px; font-size: 24px; }
        .page-title p { margin: 5px 0 0 20px; color: var(--text-gray); font-size: 14px; }

        /* FILTER BAR */
        .filter-bar { 
            display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; 
            background: #fff; padding: 20px; border-radius: 20px; 
            box-shadow: var(--shadow); border: 1px solid rgba(0,0,0,0.03); 
        }
        .filter-input { 
            padding: 12px 20px; border: 1px solid #eee; border-radius: 50px; 
            font-size: 14px; outline: none; transition: 0.3s; background: #fcfcfc; flex: 1; 
        }
        .filter-input:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px var(--primary-soft); }
        
        .btn-reset { 
            background: #f0f0f0; border: none; color: var(--text-dark); padding: 12px 25px; 
            border-radius: 50px; font-size: 14px; font-weight: 600; cursor: pointer; 
            transition: 0.3s; display: flex; align-items: center; gap: 8px; 
        }
        .btn-reset:hover { background: #e0e0e0; }

        /* TABEL MODERN */
        .table-card { 
            background: #fff; border-radius: 20px; 
            box-shadow: var(--shadow); overflow: hidden; 
            border: 1px solid rgba(0,0,0,0.03); 
        }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { 
            background-color: #fafafa; color: var(--text-gray); font-weight: 700; 
            padding: 25px; text-align: left; font-size: 13px; 
            text-transform: uppercase; border-bottom: 2px solid #eee; letter-spacing: 0.5px;
        }
        .modern-table td { 
            padding: 25px; border-bottom: 1px solid #f9f9f9; 
            vertical-align: middle; font-size: 14px; color: var(--text-dark); 
        }
        .modern-table tr:hover { background-color: #fcfcfc; }

        /* OVERDUE ALERT ROW */
        /* Baris merah muda jika lewat tanggal DAN belum selesai */
        .row-overdue { background-color: #fff5f5 !important; border-left: 4px solid #dc3545; }
        .overdue-text { color: #dc3545; font-weight: 700; font-size: 11px; display: block; margin-top: 5px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* BADGES */
        .badge { padding: 8px 15px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .st-terjadwal { background: #e0f7fa; color: #0097a7; }
        .st-selesai { background: #e0f2f1; color: #00695c; }
        
        .loc-indoor { color: #6A5ACD; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .loc-outdoor { color: #28a745; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }

        /* BUTTONS */
        .btn-detail { 
            background: #fff; border: 2px solid var(--primary-soft); color: var(--primary); 
            padding: 10px 20px; border-radius: 50px; font-size: 12px; font-weight: 700; 
            cursor: pointer; transition: 0.3s; 
        }
        .btn-detail:hover { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 5px 15px rgba(106, 90, 205, 0.2); }

        .btn-done {
            background: #28a745; color: #fff; border: none; padding: 10px 20px; border-radius: 50px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-left: 5px; display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-done:hover { background: #218838; box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3); }

        /* ALERT & MODAL */
        .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #28a745; font-size: 14px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #dc3545; font-size: 14px; }

        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background: #fff; margin: 10vh auto; padding: 40px; width: 500px; max-width: 90%; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); animation: slideUpModal 0.3s ease; }
        @keyframes slideUpModal { from {transform:translateY(30px); opacity:0;} to {transform:translateY(0); opacity:1;} }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 25px; }
        .modal-header h3 { margin: 0; color: var(--primary); font-size: 20px; font-weight: 700; }
        .close-modal { font-size: 24px; color: #aaa; cursor: pointer; transition: 0.2s; }
        .close-modal:hover { color: #333; }

        .detail-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px; border-bottom: 1px dashed #f5f5f5; padding-bottom: 10px; }
        .d-label { color: #888; font-weight: 500; width: 130px; } 
        .d-val { font-weight: 600; color: #333; flex: 1; text-align: right; }
        .d-val.note { font-weight: normal; color: #555; text-align: left; background: #f9f9f9; padding: 15px; border-radius: 12px; margin-top: 10px; width: 100%; }

        .btn-close { width: 100%; background: #f0f0f0; color: #333; border: none; padding: 15px; border-radius: 12px; font-weight: 700; cursor: pointer; margin-top: 20px; transition: 0.2s; font-size: 14px; }
        .btn-close:hover { background: #e0e0e0; }

        .empty-state { text-align: center; padding: 60px; color: #999; font-style: italic; }
    </style>
</head>
<body>

    <div class="top-nav">
        <div class="logo-nav">
            <img src="foto/logo.jpg" alt="Logo" class="logo-circle">
            <span class="brand-text">ENEMATIKA</span>
        </div>
        
        <div class="nav-links">
            <a href="dashboard_fotografer.php">Dashboard</a>
            <a href="jadwal_fotografer.php" class="active-link">Lihat Jadwal</a>
            <a href="upload_foto.php">Upload Foto</a>
            <a href="#">Galeri</a>
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
        <div class="page-header">
            <div class="page-title">
                <h2>Jadwal Pemotretan</h2>
                <p>Daftar semua tugas pemotretan (Aktif, Hari Ini, dan Mendatang).</p>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="filter-input" placeholder="Cari Nama Pelanggan / ID..." value="<?php echo htmlspecialchars($search); ?>">
            <input type="date" name="tgl" class="filter-input" value="<?php echo $filter_tgl; ?>">
            <select name="status" class="filter-input" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="Terjadwal" <?php echo ($filter_status=='Terjadwal')?'selected':''; ?>>Terjadwal</option>
                <option value="Selesai" <?php echo ($filter_status=='Selesai')?'selected':''; ?>>Selesai</option>
            </select>
            <a href="jadwal_fotografer.php" class="btn-reset"><i class="fas fa-sync-alt"></i> Reset Filter</a>
        </form>

        <?php if ($message): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-card">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Tanggal & Waktu</th>
                        <th>Pelanggan</th>
                        <th>Paket</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            // LOGIKA WARNA: Merah muda jika tanggal lewat dan belum selesai
                            $isOverdue = ($row['tanggal'] < $today && $row['statusPesanan'] != 'Selesai');
                            $rowClass = $isOverdue ? 'row-overdue' : '';
                        ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td>
                                    <div style="font-weight:700; color:var(--text-dark); margin-bottom:4px;"><?php echo date('d M Y', strtotime($row['tanggal'])); ?></div>
                                    <div style="font-size:12px; color:var(--primary); font-weight:600;"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($row['waktuMulai'])); ?> WIB</div>
                                    <?php if ($isOverdue): ?>
                                        <span class="overdue-text"><i class="fas fa-exclamation-circle"></i> TERLEWAT!</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <b style="font-size:15px;"><?php echo htmlspecialchars($row['namaPelanggan']); ?></b>
                                    <div style="font-size:12px; color:#888; margin-top:2px;">ID: <?php echo $row['orderId']; ?></div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['namaPaket']); ?>
                                </td>
                                <td>
                                    <?php if($row['kategori'] == 'Indoor'): ?>
                                        <span class="loc-indoor"><i class="fas fa-building"></i> Studio</span>
                                    <?php else: ?>
                                        <span class="loc-outdoor"><i class="fas fa-tree"></i> <?php echo htmlspecialchars($row['lokasi']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $st = $row['statusPesanan'];
                                        $badgeClass = ($st == 'Selesai') ? 'st-selesai' : 'st-terjadwal';
                                        echo "<span class='badge $badgeClass'>$st</span>";
                                    ?>
                                </td>
                                <td style="text-align:center; min-width:180px;">
                                    <button class="btn-detail" onclick='openModal(<?php echo json_encode($row); ?>)'>Detail</button>
                                    
                                    <?php if($row['statusPesanan'] == 'Terjadwal'): ?>
                                        <form method="POST" onsubmit="return confirm('Konfirmasi: Apakah pemotretan ini sudah selesai dilaksanakan?');" style="display:inline;">
                                            <input type="hidden" name="orderId" value="<?php echo $row['orderId']; ?>">
                                            <input type="hidden" name="action" value="selesai">
                                            <button type="submit" class="btn-done"><i class="fas fa-check"></i> Selesai</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="empty-state">Belum ada jadwal pemotretan saat ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="detailModal" class="modal" onclick="if(event.target==this) closeModal()">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Detail Tugas</h3>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody">
                <div class="detail-row"><span class="d-label">ID Pesanan</span><span class="d-val" id="d_id" style="color:var(--primary);"></span></div>
                <div class="detail-row"><span class="d-label">Nama Klien</span><span class="d-val" id="d_nama"></span></div>
                <div class="detail-row"><span class="d-label">Paket Foto</span><span class="d-val" id="d_paket"></span></div>
                <div class="detail-row"><span class="d-label">Tanggal</span><span class="d-val" id="d_tgl"></span></div>
                <div class="detail-row"><span class="d-label">Waktu</span><span class="d-val" id="d_waktu"></span></div>
                <div class="detail-row"><span class="d-label">Lokasi</span><span class="d-val" id="d_lokasi"></span></div>
                <div class="detail-row"><span class="d-label">Alamat Lengkap</span><span class="d-val" id="d_alamat"></span></div>
                
                <div style="margin-top:20px;">
                    <span class="d-label" style="display:block; margin-bottom:5px;">Catatan Tambahan:</span>
                    <div class="d-val note" id="d_catatan"></div>
                </div>
            </div>
            <button class="btn-close" onclick="closeModal()">Tutup</button>
        </div>
    </div>

    <script>
        function openModal(data) {
            document.getElementById('detailModal').style.display = 'block';
            document.getElementById('d_id').innerText = data.orderId;
            document.getElementById('d_nama').innerText = data.namaPelanggan;
            document.getElementById('d_paket').innerText = data.namaPaket;
            document.getElementById('d_tgl').innerText = data.tanggal;
            document.getElementById('d_waktu').innerText = data.waktuMulai + ' - ' + data.waktuSelesai + ' WIB';
            document.getElementById('d_lokasi').innerText = data.lokasi || 'Studio';
            document.getElementById('d_alamat').innerText = data.alamat || 'Studio Pusat';
            document.getElementById('d_catatan').innerText = data.catatan || 'Tidak ada catatan khusus.';
        }
        function closeModal() { document.getElementById('detailModal').style.display = 'none'; }
    </script>

</body>
</html>