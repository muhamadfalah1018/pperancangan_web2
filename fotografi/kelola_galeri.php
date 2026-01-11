<?php
// kelola_galeri.php - ADMIN THEME + GOOGLE DRIVE INTEGRATION READY
session_start();
include('includes/db_koneksi.php');

// 1. CEK AKSES ADMIN
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin'])) {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$message = '';
$api_error = '';

// --- 2. PERSIAPAN GOOGLE DRIVE API (SAFE MODE) ---
// Cek apakah library Google sudah terinstall via Composer
$google_api_ready = false;
if (file_exists('vendor/autoload.php') && file_exists('credentials.json')) {
    require_once 'vendor/autoload.php';
    $google_api_ready = true;
}

// Fungsi Helper Google Drive
function getDriveService() {
    $client = new Google\Client();
    $client->setAuthConfig('credentials.json');
    $client->addScope(Google\Service\Drive::DRIVE);
    return new Google\Service\Drive($client);
}

function getFileIdFromUrl($url) {
    preg_match('/[-\w]{25,}/', $url, $matches);
    return $matches[0] ?? null;
}

// --- 3. PROSES UPDATE AKSES ---
if (isset($_POST['update_akses'])) {
    $orderId = $_POST['orderId'];
    $statusAkses = $_POST['statusAkses'];
    
    // A. Update Database (Selalu dijalankan)
    $stmt = $koneksi->prepare("UPDATE hasilfoto SET statusAkses = ? WHERE orderId = ?");
    $stmt->bind_param("ss", $statusAkses, $orderId);
    
    if($stmt->execute()) { 
        $message = "Status akses di Database berhasil diperbarui!"; 
        
        // B. Update Google Drive (Hanya jika API siap)
        if ($google_api_ready) {
            // Ambil data link & email user
            $q_data = $koneksi->query("SELECT h.linkDrive, u.email FROM hasilfoto h JOIN pemesanan p ON h.orderId = p.orderId JOIN user u ON p.userId = u.userId WHERE h.orderId = '$orderId'");
            $data = $q_data->fetch_assoc();

            if ($data && !empty($data['linkDrive']) && !empty($data['email'])) {
                $fileId = getFileIdFromUrl($data['linkDrive']);
                $userEmail = $data['email'];

                if ($fileId) {
                    try {
                        $service = getDriveService();

                        // 1. Hapus izin lama (Reset)
                        try {
                            $permissions = $service->permissions->listPermissions($fileId, ['fields' => 'permissions(id, emailAddress)']);
                            foreach ($permissions->getPermissions() as $perm) {
                                if ($perm->emailAddress === $userEmail) {
                                    $service->permissions->delete($fileId, $perm->id);
                                }
                            }
                        } catch (Exception $e) {}

                        // 2. Beri izin baru sesuai status
                        if ($statusAkses != 'Nonaktif') {
                            $userPermission = new Google\Service\Drive\Permission([
                                'type' => 'user', 'role' => 'reader', 'emailAddress' => $userEmail
                            ]);
                            $service->permissions->create($fileId, $userPermission);

                            // Atur 'CopyRequiresWriterPermission' (Cegah Download)
                            $fileMetadata = new Google\Service\Drive\DriveFile();
                            $fileMetadata->setCopyRequiresWriterPermission($statusAkses == 'Terbatas');
                            $service->files->update($fileId, $fileMetadata);
                        }
                        $message .= " Dan sinkronisasi Google Drive Berhasil.";
                    } catch (Exception $e) {
                        $api_error = " (Google Drive Error: " . $e->getMessage() . ")";
                    }
                }
            }
        } else {
            // Pesan jika API belum disetup tapi database sukses update
            $api_error = " (Catatan: Sinkronisasi Google Drive dilewati karena library/kunci belum disetup).";
        }
    }
}

// --- 4. FILTER DATA ---
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';

// --- 5. QUERY DATA ---
$query = "
    SELECT 
        p.orderId, p.namaPelanggan, pk.namaPaket,
        pay.statusBayar, pay.metode,
        h.linkDrive, h.statusAkses, h.tanggalUpload
    FROM pemesanan p
    JOIN paketlayanan pk ON p.paketId = pk.paketId
    LEFT JOIN pembayaran pay ON p.orderId = pay.orderId
    JOIN hasilfoto h ON p.orderId = h.orderId
    WHERE 1=1
";

if ($search) { $query .= " AND (p.namaPelanggan LIKE '%$search%' OR p.orderId LIKE '%$search%')"; }
if ($filter_status == 'Lunas') { $query .= " AND pay.statusBayar = 'Lunas'"; }
elseif ($filter_status == 'DP') { $query .= " AND pay.statusBayar LIKE '%DP%'"; }

$query .= " ORDER BY h.tanggalUpload DESC";
$result = $koneksi->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Galeri - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- STYLE ADMIN MODERN (Sama dengan Dashboard Admin) --- */
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

        /* KONTEN UTAMA */
        .content { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-header h2 { margin: 0 0 5px 0; font-weight: 700; color: var(--text-dark); }
        
        /* FILTER BAR */
        .filter-bar { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-input { padding: 10px 15px; border: 1px solid #ddd; border-radius: 50px; font-size: 14px; outline: none; transition: 0.3s; background: rgba(255,255,255,0.8); }
        .filter-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.1); background: #fff; }
        .btn-reset { background: #fff; border: 1px solid #ddd; color: var(--text-dark); padding: 10px 20px; border-radius: 50px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s; }
        .btn-reset:hover { background: #f0f0f0; }

        /* TABEL MODERN */
        .table-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid rgba(255,255,255,0.5); }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { background-color: rgba(106, 90, 205, 0.05); color: var(--primary); font-weight: 700; padding: 20px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #eee; }
        .modern-table td { padding: 20px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; font-size: 14px; }
        .modern-table tr:hover { background-color: #fff; }

        /* BADGES */
        .badge { padding: 6px 12px; border-radius: 30px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .st-lunas { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .st-dp { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }
        .st-belum { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .acs-full { color: #28a745; font-weight: bold; display: flex; align-items: center; gap: 5px; }
        .acs-limit { color: #ffc107; font-weight: bold; display: flex; align-items: center; gap: 5px; }
        .acs-lock { color: #dc3545; font-weight: bold; display: flex; align-items: center; gap: 5px; }

        /* TOMBOL AKSI */
        .btn-manage { border: none; padding: 8px 15px; border-radius: 50px; cursor: pointer; font-size: 12px; color: #fff; background: #6A5ACD; box-shadow: 0 4px 10px rgba(106, 90, 205, 0.3); transition: 0.2s; display:inline-flex; align-items:center; gap:5px; font-weight:600; }
        .btn-manage:hover { transform: translateY(-2px); opacity: 0.9; }

        /* LINK BOX */
        .link-display { background: #fff; border: 1px solid #ddd; padding: 5px 10px; border-radius: 5px; font-size: 12px; color: #666; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }
        .link-display:hover { border-color: var(--primary); color: var(--primary); }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background: #fff; margin: 5vh auto; padding: 30px; width: 500px; max-width: 90%; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); animation: slideUpModal 0.3s ease; }
        @keyframes slideUpModal { from {transform:translateY(50px); opacity:0;} to {transform:translateY(0); opacity:1;} }
        
        .modal-header h3 { margin: 0 0 20px 0; color: var(--primary); font-size: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; display:flex; justify-content:space-between; align-items:center; }
        .close-modal { font-size: 24px; color: #999; cursor: pointer; }
        .info-panel { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid var(--primary); }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; }
        .i-label { color: #888; } .i-val { font-weight: 600; color: #333; }
        .btn-save { width: 100%; padding: 12px; background: var(--primary); color: #fff; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 14px; margin-top: 10px; }
        .btn-save:hover { background: #5a4db8; box-shadow: 0 5px 15px rgba(106, 90, 205, 0.2); }
        
        .alert-box { background:#d4edda; color:#155724; padding:15px; border-radius:10px; margin-bottom:20px; box-shadow:0 4px 6px rgba(0,0,0,0.05); }
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
            <a href="verifikasi_pembayaran.php">Pembayaran</a>
            <a href="verifikasi_pemesanan.php">Pemesanan</a>
            <a href="jadwal.php">Jadwal</a>
            <a href="kelola_galeri.php" class="active-link">Galeri</a>
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
            <h2>Kelola Akses Galeri</h2>
        </div>

        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="filter-input" placeholder="ID / Nama Pelanggan..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status" class="filter-input" onchange="this.form.submit()">
                <option value="">-- Semua Status Bayar --</option>
                <option value="Lunas" <?php if($filter_status=='Lunas') echo 'selected'; ?>>Lunas</option>
                <option value="DP" <?php if($filter_status=='DP') echo 'selected'; ?>>DP / Belum Lunas</option>
            </select>
            <a href="kelola_galeri.php" class="btn-reset">Reset Filter</a>
        </form>

        <?php if ($message): ?>
            <div class="alert-box">
                <i class="fas fa-check-circle"></i> <?php echo $message . $api_error; ?>
            </div>
        <?php endif; ?>

        <?php if (!$google_api_ready): ?>
            <div style="background:#fff3cd; color:#856404; padding:10px; border-radius:10px; margin-bottom:20px; font-size:13px; border:1px solid #ffeeba;">
                <i class="fas fa-exclamation-triangle"></i> <b>Mode Offline:</b> Library Google Drive belum terdeteksi. Sistem hanya akan mengupdate status di database saja.
            </div>
        <?php endif; ?>

        <div class="table-card">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Status Bayar</th>
                        <th>Status Akses</th>
                        <th>Link Drive</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $stBayar = $row['statusBayar'];
                            $stAkses = $row['statusAkses'] ?? 'Aktif';
                            
                            $badgeBayar = 'st-belum';
                            if ($stBayar == 'Lunas') $badgeBayar = 'st-lunas';
                            elseif (stripos($stBayar, 'DP') !== false) $badgeBayar = 'st-dp';

                            $visualAkses = '';
                            if ($stAkses == 'Nonaktif') {
                                $visualAkses = '<span class="acs-lock"><i class="fas fa-lock"></i> Terkunci (Manual)</span>';
                            } else {
                                if ($stBayar == 'Lunas') {
                                    $visualAkses = '<span class="acs-full"><i class="fas fa-check-circle"></i> Full Access</span>';
                                } elseif (stripos($stBayar, 'DP') !== false) {
                                    $visualAkses = '<span class="acs-limit"><i class="fas fa-eye"></i> Preview Only</span>';
                                } else {
                                    $visualAkses = '<span class="acs-lock"><i class="fas fa-lock"></i> Terkunci (Otomatis)</span>';
                                }
                            }
                        ?>
                            <tr>
                                <td><b><?php echo $row['orderId']; ?></b></td>
                                <td><?php echo htmlspecialchars($row['namaPelanggan']); ?><br><small style="color:#888;"><?php echo $row['namaPaket']; ?></small></td>
                                <td><span class="badge <?php echo $badgeBayar; ?>"><?php echo $stBayar ?? 'Belum'; ?></span></td>
                                <td><?php echo $visualAkses; ?></td>
                                <td>
                                    <?php if(!empty($row['linkDrive'])): ?>
                                        <a href="<?php echo $row['linkDrive']; ?>" target="_blank" class="link-display" title="<?php echo $row['linkDrive']; ?>">
                                            <i class="fab fa-google-drive"></i> <?php echo substr($row['linkDrive'], 0, 25) . '...'; ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#ccc;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-manage" onclick='openModal(<?php echo json_encode($row); ?>)'>
                                        <i class="fas fa-cog"></i> Kelola
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#999;">Belum ada data galeri.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="aksesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-shield-alt"></i> Kontrol Akses</h3>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            
            <div class="info-panel">
                <div class="info-row"><span class="i-label">ID Pesanan</span><span class="i-val" id="m_id"></span></div>
                <div class="info-row"><span class="i-label">Pelanggan</span><span class="i-val" id="m_nama"></span></div>
                <div class="info-row"><span class="i-label">Status Bayar</span><span class="i-val" id="m_bayar"></span></div>
            </div>

            <form method="POST">
                <input type="hidden" name="orderId" id="in_orderId">
                
                <div class="form-group">
                    <label>Override Status Akses</label>
                    <select name="statusAkses" id="in_status" class="filter-input" style="width:100%;">
                        <option value="Aktif">Aktif (Ikuti Aturan Sistem)</option>
                        <option value="Terbatas">Terbatas (Paksa Preview Only)</option>
                        <option value="Nonaktif">Nonaktif (Kunci Total)</option>
                    </select>
                </div>

                <div style="font-size:12px; color:#666; margin-bottom:15px; line-height:1.5;">
                    <i class="fas fa-info-circle"></i> <b>Catatan:</b><br>
                    Secara default, sistem otomatis memberikan akses unduh jika status bayar <b>Lunas</b>. Gunakan menu ini jika Anda ingin memblokir akses pelanggan tertentu secara manual.
                </div>

                <button type="submit" name="update_akses" class="btn-save">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(data) {
            document.getElementById('m_id').innerText = data.orderId;
            document.getElementById('m_nama').innerText = data.namaPelanggan;
            document.getElementById('m_bayar').innerText = data.statusBayar || 'Belum Bayar';
            
            document.getElementById('in_orderId').value = data.orderId;
            document.getElementById('in_status').value = data.statusAkses || 'Aktif';
            
            document.getElementById('aksesModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('aksesModal').style.display = 'none';
        }

        window.onclick = function(e) {
            if(e.target == document.getElementById('aksesModal')) closeModal();
        }
    </script>

</body>
</html>