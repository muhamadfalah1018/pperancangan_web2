<?php
// upload_foto.php - COMPLETE: Upload Logic + Detail Modal + Modern UI
session_start();
include('includes/db_koneksi.php');

// 1. CEK AKSES FOTOGRAFER
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Fotografer') {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$message = '';
$error = '';

// 2. PROSES SIMPAN / UPDATE LINK
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_link'])) {
    $orderId = $_POST['orderId'];
    $linkDrive = $_POST['linkDrive'];
    $tglUpload = date('Y-m-d H:i:s');

    // Cek apakah data sudah ada di tabel hasilfoto
    $cek = $koneksi->query("SELECT * FROM hasilfoto WHERE orderId = '$orderId'");
    
    if ($cek->num_rows > 0) {
        // UPDATE jika sudah ada
        $stmt = $koneksi->prepare("UPDATE hasilfoto SET linkDrive = ?, tanggalUpload = ? WHERE orderId = ?");
        $stmt->bind_param("sss", $linkDrive, $tglUpload, $orderId);
    } else {
        // INSERT jika belum ada
        $hasilId = 'RES' . date('dmy') . rand(100, 999);
        $stmt = $koneksi->prepare("INSERT INTO hasilfoto (hasilId, orderId, linkDrive, tanggalUpload, statusAkses) VALUES (?, ?, ?, ?, 'Aktif')");
        $stmt->bind_param("ssss", $hasilId, $orderId, $linkDrive, $tglUpload);
    }

    if ($stmt->execute()) {
        // Update status pesanan jadi 'Selesai' otomatis
        $koneksi->query("UPDATE pemesanan SET statusPesanan = 'Selesai' WHERE orderId = '$orderId'");
        $message = "Link Google Drive berhasil disimpan & Status pesanan diperbarui.";
    } else {
        $error = "Gagal menyimpan data: " . $koneksi->error;
    }
}

// 3. FILTER DATA
$search = $_GET['search'] ?? '';
$filter_tgl = $_GET['tgl'] ?? '';
$filter_status = $_GET['status'] ?? '';

// 4. QUERY UTAMA
// Mengambil data dari Pemesanan, Jadwal, Paket, dan HasilFoto
$query = "
    SELECT 
        p.orderId, p.namaPelanggan, p.catatan, 
        pk.namaPaket, pk.kategori,
        j.tanggal, j.waktuMulai, j.waktuSelesai, j.lokasi, j.alamat,
        h.linkDrive
    FROM pemesanan p
    JOIN jadwal j ON p.orderId = j.orderId
    JOIN paketlayanan pk ON p.paketId = pk.paketId
    LEFT JOIN hasilfoto h ON p.orderId = h.orderId
    WHERE p.statusPesanan IN ('Terjadwal', 'Selesai')
";

// Logic Filter
if ($search) {
    $query .= " AND (p.namaPelanggan LIKE '%$search%' OR p.orderId LIKE '%$search%')";
}
if ($filter_tgl) {
    $query .= " AND j.tanggal = '$filter_tgl'";
}
if ($filter_status == 'Sudah') {
    $query .= " AND h.linkDrive IS NOT NULL AND h.linkDrive != ''";
} elseif ($filter_status == 'Belum') {
    $query .= " AND (h.linkDrive IS NULL OR h.linkDrive = '')";
}

$query .= " ORDER BY j.tanggal DESC";
$result = $koneksi->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload Hasil Foto</title>
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
        .logo-circle { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); box-shadow: 0 4px 10px rgba(106, 90, 205, 0.2); }
        .brand-text { font-weight: 700; font-size: 20px; color: var(--primary); letter-spacing: 0.5px; }

        .nav-links { flex-grow: 1; display: flex; gap: 10px; margin-left: 20px; }
        .nav-links a { color: var(--text-gray); font-weight: 500; font-size: 14px; padding: 12px 20px; border-radius: 30px; transition: all 0.3s ease; }
        .nav-links a:hover, .nav-links .active-link { color: var(--primary); background-color: var(--primary-soft); font-weight: 600; }
        
        /* USER MENU */
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

        /* TABEL MODERN */
        .table-card { background: #fff; border-radius: 20px; box-shadow: var(--shadow); overflow: hidden; border: 1px solid rgba(0,0,0,0.03); }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { background-color: #fafafa; color: var(--text-gray); font-weight: 700; padding: 25px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #eee; letter-spacing: 0.5px; }
        .modern-table td { padding: 25px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; font-size: 14px; color: var(--text-dark); }
        .modern-table tr:hover { background-color: #fcfcfc; }

        /* BADGES & BUTTONS */
        .badge { padding: 8px 15px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .st-uploaded { background: #e0f2f1; color: #00695c; border: 1px solid #b2dfdb; }
        .st-pending { background: #fff3e0; color: #ef6c00; border: 1px solid #ffe0b2; }

        /* Button Group Wrapper */
        .btn-group { display: flex; gap: 5px; align-items: center; }

        /* Button Upload/Edit */
        .btn-upload { background: linear-gradient(135deg, #6A5ACD 0%, #836FFF 100%); color: #fff; padding: 8px 15px; border-radius: 50px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; border: none; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
        .btn-upload.edit { background: #ffc107; color: #333; background-image: none; }
        .btn-upload:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3); }
        
        /* Button View Link */
        .btn-view { background: #fff; border: 2px solid var(--primary-soft); color: var(--primary); padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-view:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* Button Detail (The "i" Icon) */
        .btn-detail { background: #eef2f3; border: 1px solid #ddd; color: #555; width: 32px; height: 32px; border-radius: 50%; font-size: 14px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
        .btn-detail:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background: #fff; margin: 10vh auto; padding: 40px; width: 500px; max-width: 90%; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); animation: slideUpModal 0.3s ease; border: 1px solid #eee; }
        @keyframes slideUpModal { from {transform:translateY(30px); opacity:0;} to {transform:translateY(0); opacity:1;} }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 25px; }
        .modal-header h3 { margin: 0; color: var(--primary); font-size: 20px; font-weight: 700; }
        .close-modal { font-size: 24px; color: #aaa; cursor: pointer; transition: 0.2s; }
        .close-modal:hover { color: #333; }

        .detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; border-bottom: 1px dashed #f5f5f5; padding-bottom: 8px; }
        .d-label { color: #888; font-weight: 500; width: 120px; } 
        .d-val { font-weight: 600; color: #333; flex: 1; text-align: right; }
        .d-val.note { font-weight: normal; color: #555; text-align: left; background: #f9f9f9; padding: 10px; border-radius: 8px; margin-top: 5px; width: 100%; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 8px; }
        .form-input { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 10px; font-size: 14px; box-sizing: border-box; font-family: 'Poppins', sans-serif; transition: 0.3s; }
        .form-input:focus { border-color: var(--primary); outline: none; background: #fcfcfc; box-shadow: 0 0 0 3px var(--primary-soft); }
        .form-input[readonly] { background: #f9f9f9; color: #888; border-color: #eee; }

        .btn-submit { width: 100%; background: var(--primary); color: #fff; border: none; padding: 15px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit:hover { background: #5a4db8; box-shadow: 0 5px 15px rgba(106, 90, 205, 0.2); }

        .btn-close { width: 100%; background: #f0f0f0; color: #333; border: none; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; margin-top: 15px; transition: 0.2s; }
        .btn-close:hover { background: #e0e0e0; }

        .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #28a745; font-size: 14px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #dc3545; font-size: 14px; }
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
            <a href="upload_foto.php" class="active-link">Upload Foto</a>
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
        <div class="page-header">
            <div class="page-title">
                <h2>Upload Hasil Foto</h2>
                <p>Kirimkan link Google Drive kepada pelanggan setelah pemotretan selesai.</p>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="filter-input" placeholder="Cari Nama / ID Pesanan..." value="<?php echo htmlspecialchars($search); ?>">
            <input type="date" name="tgl" class="filter-input" value="<?php echo $filter_tgl; ?>">
            <select name="status" class="filter-input" onchange="this.form.submit()">
                <option value="">Status Upload</option>
                <option value="Sudah" <?php echo ($filter_status=='Sudah')?'selected':''; ?>>Sudah Upload</option>
                <option value="Belum" <?php echo ($filter_status=='Belum')?'selected':''; ?>>Belum Upload</option>
            </select>
            <a href="upload_foto.php" class="btn-reset"><i class="fas fa-sync-alt"></i> Reset</a>
        </form>

        <?php if ($message): ?> <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div> <?php endif; ?>
        <?php if ($error): ?> <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div> <?php endif; ?>

        <div class="table-card">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Nama Pemesan</th>
                        <th>Paket</th>
                        <th>Tanggal Potret</th>
                        <th>Status Upload</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><b><?php echo $row['orderId']; ?></b></td>
                                <td><?php echo htmlspecialchars($row['namaPelanggan']); ?></td>
                                <td><?php echo htmlspecialchars($row['namaPaket']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                <td>
                                    <?php if (!empty($row['linkDrive'])): ?>
                                        <span class="badge st-uploaded"><i class="fas fa-check"></i> Sudah Upload</span>
                                    <?php else: ?>
                                        <span class="badge st-pending"><i class="fas fa-clock"></i> Belum Upload</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <?php if (!empty($row['linkDrive'])): ?>
                                            <a href="<?php echo $row['linkDrive']; ?>" target="_blank" class="btn-view" title="Buka Link"><i class="fas fa-external-link-alt"></i></a>
                                            <button class="btn-upload edit" onclick='openUploadModal(<?php echo json_encode($row); ?>)' title="Edit Link"><i class="fas fa-pencil-alt"></i></button>
                                        <?php else: ?>
                                            <button class="btn-upload" onclick='openUploadModal(<?php echo json_encode($row); ?>)'><i class="fas fa-cloud-upload-alt"></i> Upload</button>
                                        <?php endif; ?>
                                        
                                        <button class="btn-detail" onclick='openDetailModal(<?php echo json_encode($row); ?>)' title="Lihat Detail Pesanan">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="empty-state">Tidak ada data pesanan yang perlu diupload.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="uploadModal" class="modal" onclick="if(event.target==this) closeModal('uploadModal')">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fab fa-google-drive" style="color:var(--primary);"></i> Form Upload Foto</h3>
                <span class="close-modal" onclick="closeModal('uploadModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>ID Pesanan</label>
                    <input type="text" name="orderId" id="up_orderId" class="form-input" readonly>
                </div>
                <div class="form-group">
                    <label>Nama Pemesan</label>
                    <input type="text" id="up_nama" class="form-input" readonly>
                </div>
                <div style="background:#fff3cd; padding:15px; border-radius:10px; margin-bottom:20px; font-size:13px; color:#856404; border:1px solid #ffeeba;">
                    <i class="fas fa-info-circle"></i> Pastikan link Google Drive sudah diatur <b>"Anyone with the link"</b> agar pelanggan bisa mengakses.
                </div>
                <div class="form-group">
                    <label>Link Google Drive <span style="color:red">*</span></label>
                    <input type="url" name="linkDrive" id="up_link" class="form-input" placeholder="https://drive.google.com/..." required>
                </div>
                <button type="submit" name="submit_link" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan & Upload
                </button>
            </form>
        </div>
    </div>

    <div id="detailModal" class="modal" onclick="if(event.target==this) closeModal('detailModal')">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle" style="color:var(--primary);"></i> Detail Pesanan</h3>
                <span class="close-modal" onclick="closeModal('detailModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="detail-row"><span class="d-label">ID Pesanan</span><span class="d-val" id="d_id" style="color:var(--primary);"></span></div>
                <div class="detail-row"><span class="d-label">Nama Klien</span><span class="d-val" id="d_nama"></span></div>
                <div class="detail-row"><span class="d-label">Paket</span><span class="d-val" id="d_paket"></span></div>
                <div class="detail-row"><span class="d-label">Kategori</span><span class="d-val" id="d_kategori"></span></div>
                <div class="detail-row"><span class="d-label">Tanggal</span><span class="d-val" id="d_tgl"></span></div>
                <div class="detail-row"><span class="d-label">Waktu</span><span class="d-val" id="d_waktu"></span></div>
                <div class="detail-row"><span class="d-label">Lokasi</span><span class="d-val" id="d_lokasi"></span></div>
                <div class="detail-row"><span class="d-label">Alamat</span><span class="d-val" id="d_alamat"></span></div>
                <div style="margin-top:20px;">
                    <span class="d-label" style="display:block; margin-bottom:5px;">Catatan Pelanggan:</span>
                    <div class="d-val note" id="d_catatan"></div>
                </div>
            </div>
            <button class="btn-close" onclick="closeModal('detailModal')">Tutup</button>
        </div>
    </div>

    <script>
        // Modal Upload
        function openUploadModal(data) {
            document.getElementById('uploadModal').style.display = 'block';
            document.getElementById('up_orderId').value = data.orderId;
            document.getElementById('up_nama').value = data.namaPelanggan;
            document.getElementById('up_link').value = data.linkDrive || '';
        }

        // Modal Detail (Read-Only)
        function openDetailModal(data) {
            document.getElementById('detailModal').style.display = 'block';
            document.getElementById('d_id').innerText = data.orderId;
            document.getElementById('d_nama').innerText = data.namaPelanggan;
            document.getElementById('d_paket').innerText = data.namaPaket;
            document.getElementById('d_kategori').innerText = data.kategori || '-';
            document.getElementById('d_tgl').innerText = data.tanggal;
            document.getElementById('d_waktu').innerText = data.waktuMulai + ' - ' + data.waktuSelesai;
            document.getElementById('d_lokasi').innerText = data.lokasi || '-';
            document.getElementById('d_alamat').innerText = data.alamat || '-';
            document.getElementById('d_catatan').innerText = data.catatan || 'Tidak ada catatan.';
        }

        // Close Logic
        function closeModal(modalId) { 
            document.getElementById(modalId).style.display = 'none'; 
        }
    </script>

</body>
</html>