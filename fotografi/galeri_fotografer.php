<?php
// galeri_fotografer.php - LOGIKA AKSES BERDASARKAN PEMBAYARAN
session_start();
include('includes/db_koneksi.php');

// 1. CEK AKSES
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Fotografer') {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];

// 2. FILTER DATA
$search = $_GET['search'] ?? '';
$filter_akses = $_GET['akses'] ?? '';

// 3. QUERY DATA LENGKAP
// Menggabungkan data Pemesanan, Pembayaran, dan Hasil Foto
$query = "
    SELECT 
        p.orderId, p.namaPelanggan, p.tanggalPesan,
        pk.namaPaket, pk.kategori,
        pay.statusBayar, pay.metode,
        h.linkDrive, h.tanggalUpload
    FROM pemesanan p
    JOIN paketlayanan pk ON p.paketId = pk.paketId
    LEFT JOIN pembayaran pay ON p.orderId = pay.orderId
    JOIN hasilfoto h ON p.orderId = h.orderId
    WHERE 1=1
";

if ($search) {
    $query .= " AND (p.namaPelanggan LIKE '%$search%' OR p.orderId LIKE '%$search%')";
}

$query .= " ORDER BY h.tanggalUpload DESC";
$result = $koneksi->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Galeri & Akses Foto</title>
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
            min-height: 100vh; color: var(--text-dark); overflow-x: hidden;
            background-color: #f4f6f9;
            background-image: linear-gradient(#eef2f3 1px, transparent 1px), linear-gradient(90deg, #eef2f3 1px, transparent 1px);
            background-size: 40px 40px; 
        }

        /* --- NAVBAR --- */
        .top-nav { 
            background-color: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            height: 80px; display: flex; align-items: center; padding: 0 40px; 
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.04); border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .logo-nav { display: flex; align-items: center; width: 250px; gap: 15px; }
        .logo-circle { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); box-shadow: 0 4px 10px rgba(106, 90, 205, 0.2); }
        .brand-text { font-weight: 700; font-size: 20px; color: var(--primary); letter-spacing: 0.5px; }
        .nav-links { flex-grow: 1; display: flex; gap: 10px; margin-left: 20px; }
        .nav-links a { color: var(--text-gray); font-weight: 500; font-size: 14px; padding: 12px 20px; border-radius: 30px; transition: all 0.3s ease; }
        .nav-links a:hover, .nav-links .active-link { color: var(--primary); background-color: var(--primary-soft); font-weight: 600; }
        
        .user-menu { margin-left: auto; position: relative; }
        .dropbtn { background: #fff; border: 1px solid #eee; cursor: pointer; display: flex; align-items: center; gap: 12px; padding: 6px 20px 6px 8px; border-radius: 50px; transition: 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
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
        .filter-bar { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; background: #fff; padding: 20px; border-radius: 20px; box-shadow: var(--shadow); border: 1px solid rgba(0,0,0,0.03); }
        .filter-input { padding: 12px 20px; border: 1px solid #eee; border-radius: 50px; font-size: 14px; outline: none; transition: 0.3s; background: #fcfcfc; flex: 1; }
        .filter-input:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px var(--primary-soft); }
        .btn-reset { background: #f0f0f0; border: none; color: var(--text-dark); padding: 12px 25px; border-radius: 50px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .btn-reset:hover { background: #e0e0e0; }

        /* TABLE */
        .table-card { background: #fff; border-radius: 20px; box-shadow: var(--shadow); overflow: hidden; border: 1px solid rgba(0,0,0,0.03); }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { background-color: #fafafa; color: var(--text-gray); font-weight: 700; padding: 25px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #eee; letter-spacing: 0.5px; }
        .modern-table td { padding: 25px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; font-size: 14px; color: var(--text-dark); }
        .modern-table tr:hover { background-color: #fcfcfc; }

        /* ACCESS BADGES (LOGIC VISUALIZATION) */
        .acc-full { background: #e0f2f1; color: #00695c; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid #80cbc4; display: inline-flex; align-items: center; gap: 5px; }
        .acc-view { background: #fff3e0; color: #ef6c00; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid #ffe0b2; display: inline-flex; align-items: center; gap: 5px; }
        .acc-lock { background: #ffebee; color: #c62828; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid #ef9a9a; display: inline-flex; align-items: center; gap: 5px; }

        /* BUTTONS */
        .btn-link { background: #fff; border: 1px solid var(--primary); color: var(--primary); padding: 8px 15px; border-radius: 50px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-link:hover { background: var(--primary); color: #fff; }
        
        .btn-disabled { background: #eee; border: 1px solid #ddd; color: #999; padding: 8px 15px; border-radius: 50px; font-size: 12px; font-weight: 600; cursor: not-allowed; text-decoration: none; display: inline-block; }

        .btn-detail { background: #f0f0f0; border: 1px solid #ccc; color: #555; width: 32px; height: 32px; border-radius: 50%; font-size: 14px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; margin-left: 5px; }
        .btn-detail:hover { background: #333; color: #fff; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background: #fff; margin: 10vh auto; padding: 40px; width: 500px; max-width: 90%; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); animation: slideUpModal 0.3s ease; border: 1px solid #eee; }
        @keyframes slideUpModal { from {transform:translateY(30px); opacity:0;} to {transform:translateY(0); opacity:1;} }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 25px; }
        .modal-header h3 { margin: 0; color: var(--primary); font-size: 20px; font-weight: 700; }
        .close-modal { font-size: 24px; color: #aaa; cursor: pointer; transition: 0.2s; }
        .close-modal:hover { color: #333; }

        .detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; border-bottom: 1px dashed #f5f5f5; padding-bottom: 8px; }
        .d-label { color: #888; font-weight: 500; width: 130px; } 
        .d-val { font-weight: 600; color: #333; flex: 1; text-align: right; }
        .d-highlight { color: var(--primary); }

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
            <a href="jadwal_fotografer.php">Lihat Jadwal</a>
            <a href="upload_foto.php">Upload Foto</a>
            <a href="galeri_fotografer.php" class="active-link">Galeri</a>
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
                <h2>Galeri & Hak Akses</h2>
                <p>Monitoring hak akses pelanggan terhadap file foto berdasarkan pembayaran.</p>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="filter-input" placeholder="Cari Pelanggan / ID Pesanan..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="akses" class="filter-input" onchange="this.form.submit()">
                <option value="">Semua Akses</option>
                <option value="Full" <?php echo ($filter_akses=='Full')?'selected':''; ?>>Full Access (Lunas)</option>
                <option value="Preview" <?php echo ($filter_akses=='Preview')?'selected':''; ?>>Preview Only (DP)</option>
                <option value="Locked" <?php echo ($filter_akses=='Locked')?'selected':''; ?>>Locked (Belum Bayar)</option>
            </select>
            <a href="galeri_fotografer.php" class="btn-reset"><i class="fas fa-sync-alt"></i> Reset</a>
        </form>

        <div class="table-card">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Status Bayar</th>
                        <th>Hak Akses</th>
                        <th style="text-align:center;">Link Drive</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            // --- LOGIKA UTAMA HAK AKSES ---
                            $stBayar = $row['statusBayar'];
                            $link = $row['linkDrive'];
                            
                            $aksesLevel = 'Locked';
                            $badgeHtml = '';
                            $linkHtml = '';

                            // 1. Cek Lunas
                            if ($stBayar == 'Lunas') {
                                $aksesLevel = 'Full';
                                $badgeHtml = '<span class="acc-full"><i class="fas fa-check-circle"></i> Full Access</span>';
                                // Link bisa di-klik untuk download/lihat
                                $linkHtml = "<a href='$link' target='_blank' class='btn-link'><i class='fas fa-external-link-alt'></i> Buka Link</a>";
                            
                            // 2. Cek DP / > 50%
                            } elseif (stripos($stBayar, 'DP') !== false || stripos($stBayar, '50%') !== false) {
                                $aksesLevel = 'Preview';
                                $badgeHtml = '<span class="acc-view"><i class="fas fa-eye"></i> Preview Only</span>';
                                // Link dikasih class disabled atau peringatan (Visualisasi saja untuk fotografer)
                                $linkHtml = "<span class='btn-disabled' title='Pelanggan hanya bisa melihat preview'><i class='fas fa-eye-slash'></i> Unduh Restricted</span>";
                            
                            // 3. Belum Bayar / Kurang
                            } else {
                                $aksesLevel = 'Locked';
                                $badgeHtml = '<span class="acc-lock"><i class="fas fa-lock"></i> Locked</span>';
                                $linkHtml = "<span class='btn-disabled'><i class='fas fa-lock'></i> Terkunci</span>";
                            }

                            // Filter tampilan berdasarkan dropdown (PHP filtering manual)
                            if ($filter_akses && $filter_akses != $aksesLevel) continue;
                        ?>
                            <tr>
                                <td><b><?php echo $row['orderId']; ?></b></td>
                                <td><?php echo htmlspecialchars($row['namaPelanggan']); ?><br><small><?php echo $row['namaPaket']; ?></small></td>
                                <td><?php echo $stBayar ?? 'Belum Ada'; ?></td>
                                <td><?php echo $badgeHtml; ?></td>
                                <td style="text-align:center;">
                                    <?php echo $linkHtml; ?>
                                </td>
                                <td>
                                    <button class="btn-detail" onclick='openModal(<?php echo json_encode($row); ?>, "<?php echo $aksesLevel; ?>")'><i class="fas fa-info-circle"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="empty-state">Belum ada data galeri yang diupload.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="detailModal" class="modal" onclick="if(event.target==this) closeModal()">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-images"></i> Detail Galeri</h3>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody">
                <div class="detail-row"><span class="d-label">ID Pesanan</span><span class="d-val d-highlight" id="m_id"></span></div>
                <div class="detail-row"><span class="d-label">Nama Pelanggan</span><span class="d-val" id="m_nama"></span></div>
                <div class="detail-row"><span class="d-label">Paket</span><span class="d-val" id="m_paket"></span></div>
                <div class="detail-row"><span class="d-label">Status Bayar</span><span class="d-val" id="m_bayar"></span></div>
                <hr style="border:0; border-top:1px dashed #ddd; margin:15px 0;">
                <div class="detail-row"><span class="d-label">Status Akses</span><span class="d-val" id="m_akses" style="font-weight:800;"></span></div>
                <div class="detail-row"><span class="d-label">Tanggal Upload</span><span class="d-val" id="m_tgl"></span></div>
                
                <div style="margin-top:20px; background:#f9f9f9; padding:15px; border-radius:10px; font-size:13px; color:#555;">
                    <i class="fas fa-info-circle"></i> <b>Keterangan Sistem:</b><br>
                    <ul style="padding-left:20px; margin:5px 0;">
                        <li><b>Lunas:</b> Pelanggan dapat melihat & mengunduh foto.</li>
                        <li><b>DP (>50%):</b> Pelanggan hanya bisa melihat (Preview), tombol unduh disembunyikan.</li>
                        <li><b>Belum Bayar:</b> Akses ke halaman galeri tertutup.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(data, aksesLevel) {
            document.getElementById('detailModal').style.display = 'block';
            document.getElementById('m_id').innerText = data.orderId;
            document.getElementById('m_nama').innerText = data.namaPelanggan;
            document.getElementById('m_paket').innerText = data.namaPaket;
            document.getElementById('m_bayar').innerText = data.statusBayar || 'Belum Bayar';
            document.getElementById('m_tgl').innerText = data.tanggalUpload;
            
            var aksesText = "";
            if(aksesLevel === 'Full') aksesText = "FULL ACCESS (Unduh & Lihat)";
            else if(aksesLevel === 'Preview') aksesText = "PREVIEW ONLY (Hanya Lihat)";
            else aksesText = "LOCKED (Terkunci)";
            
            document.getElementById('m_akses').innerText = aksesText;
            
            // Warnai status akses
            var el = document.getElementById('m_akses');
            if(aksesLevel === 'Full') el.style.color = '#00695c';
            else if(aksesLevel === 'Preview') el.style.color = '#ef6c00';
            else el.style.color = '#c62828';
        }
        function closeModal() { document.getElementById('detailModal').style.display = 'none'; }
    </script>

</body>
</html>