<?php
// jadwal.php - MODERN UI + ANIMATED BG + LOGO BULAT + FITUR RESET
session_start();
include('includes/db_koneksi.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$admin_id = $_SESSION['user_id'];
$message = '';

// --- 1. LOGIKA UTAMA (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // TAMBAH MANUAL
    if (isset($_POST['tambah_manual'])) {
        $namaPelanggan = $_POST['new_nama'];
        $paketId = $_POST['new_paketId'];
        $tgl = $_POST['new_tanggal'];
        $wkt = $_POST['new_waktu'];
        $lok = $_POST['new_lokasi'];
        $alm = $_POST['new_alamat'];
        $catatan = $_POST['new_catatan'];

        $orderId = 'ORD' . date('dmyHis'); 

        $stmt1 = $koneksi->prepare("INSERT INTO pemesanan (orderId, userId, namaPelanggan, paketId, tanggalPesan, statusPesanan, catatan) VALUES (?, ?, ?, ?, NOW(), 'Terjadwal', ?)");
        $stmt1->bind_param("sssss", $orderId, $admin_id, $namaPelanggan, $paketId, $catatan);

        $stmt2 = $koneksi->prepare("INSERT INTO jadwal (orderId, tanggal, waktuMulai, lokasi, alamat) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("sssss", $orderId, $tgl, $wkt, $lok, $alm);

        if ($stmt1->execute() && $stmt2->execute()) {
            $message = "Jadwal Manual Berhasil Ditambahkan.";
        } else {
            $message = "Gagal: " . $koneksi->error;
        }
    }

    // AKSI CEPAT (UPDATE STATUS & DELETE)
    if (isset($_POST['action'])) {
        $orderId = $_POST['orderId'];
        $act = $_POST['action'];

        if ($act == 'setuju') {
            $koneksi->query("UPDATE pemesanan SET statusPesanan = 'Terjadwal' WHERE orderId = '$orderId'");
            $message = "Jadwal Disetujui.";
        } elseif ($act == 'tolak') {
            $koneksi->query("UPDATE pemesanan SET statusPesanan = 'Ditolak' WHERE orderId = '$orderId'");
            $message = "Jadwal Ditolak.";
        } elseif ($act == 'reset') { 
            $koneksi->query("UPDATE pemesanan SET statusPesanan = 'Menunggu' WHERE orderId = '$orderId'");
            $message = "Status dikembalikan ke Menunggu. Silakan pilih ulang.";
        } elseif ($act == 'selesai') {
            $koneksi->query("UPDATE pemesanan SET statusPesanan = 'Selesai' WHERE orderId = '$orderId'");
            $message = "Pesanan Selesai.";
        } elseif ($act == 'hapus') {
            $koneksi->query("DELETE FROM jadwal WHERE orderId = '$orderId'");
            $koneksi->query("UPDATE pemesanan SET statusPesanan = 'Menunggu' WHERE orderId = '$orderId'"); 
            $message = "Jadwal dihapus dari daftar.";
        }
    }
    
    // EDIT JADWAL
    if (isset($_POST['edit_jadwal'])) {
        $oid = $_POST['edit_orderId'];
        $tgl = $_POST['edit_tanggal'];
        $wkt = $_POST['edit_waktu'];
        $lok = $_POST['edit_lokasi'];
        $alm = $_POST['edit_alamat'];
        
        $stmt = $koneksi->prepare("UPDATE jadwal SET tanggal=?, waktuMulai=?, lokasi=?, alamat=? WHERE orderId=?");
        $stmt->bind_param("sssss", $tgl, $wkt, $lok, $alm, $oid);
        $stmt->execute();
        $message = "Jadwal berhasil diupdate.";
    }
}

// --- 2. DATA PENDUKUNG ---
$list_paket = [];
$q_paket = $koneksi->query("SELECT paketId, namaPaket FROM paketlayanan ORDER BY namaPaket ASC");
while($p = $q_paket->fetch_assoc()) { $list_paket[] = $p; }

// --- 3. FILTER ---
$search = $_GET['search'] ?? '';
$filter_tgl = $_GET['tgl'] ?? '';
$filter_status = $_GET['status'] ?? '';

$query = "
    SELECT 
        j.*, 
        p.namaPelanggan, p.statusPesanan, p.catatan,
        pk.namaPaket
    FROM jadwal j
    JOIN pemesanan p ON j.orderId = p.orderId
    JOIN paketlayanan pk ON p.paketId = pk.paketId
    WHERE 1=1
";

if ($search) $query .= " AND (p.namaPelanggan LIKE '%$search%' OR j.orderId LIKE '%$search%')";
if ($filter_tgl) $query .= " AND j.tanggal = '$filter_tgl'";
if ($filter_status) $query .= " AND p.statusPesanan = '$filter_status'";

$query .= " ORDER BY j.tanggal ASC";
$result = $koneksi->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Pemotretan - Admin Modern</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- STYLE GLOBAL & ANIMASI --- */
        :root { --primary: #6A5ACD; --text-dark: #333; --text-gray: #666; --white: #ffffff; }
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; color: var(--text-dark); min-height: 100vh;
            background: linear-gradient(-45deg, #e3eeff, #f3e7e9, #e8dbfc, #f5f7fa); background-size: 400% 400%; animation: gradientBG 15s ease infinite; }
        @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        
        /* HEADER NAVIGASI */
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h2 { margin: 0; font-weight: 700; color: var(--text-dark); }
        
        /* FILTER BAR MODERN */
        .filter-bar { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-input { padding: 10px 15px; border: 1px solid #ddd; border-radius: 50px; font-size: 14px; outline: none; transition: 0.3s; background: rgba(255,255,255,0.8); }
        .filter-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.1); background: #fff; }
        .btn-reset { background: #fff; border: 1px solid #ddd; color: var(--text-dark); padding: 10px 20px; border-radius: 50px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s; }
        .btn-add { background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%); color: #fff; padding: 12px 25px; border-radius: 50px; font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3); transition: 0.3s; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(106, 90, 205, 0.4); }

        /* TABEL MODERN */
        .table-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid rgba(255,255,255,0.5); }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { background-color: rgba(106, 90, 205, 0.05); color: var(--primary); font-weight: 700; padding: 20px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #eee; }
        .modern-table td { padding: 20px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; font-size: 14px; }
        .modern-table tr:hover { background-color: #fff; cursor: pointer; }

        /* BADGES */
        .badge { padding: 6px 12px; border-radius: 30px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .st-menunggu { background: #fff8e1; color: #ffa000; border: 1px solid #ffe082; }
        .st-terjadwal { background: #e0f7fa; color: #0097a7; border: 1px solid #80deea; }
        .st-selesai { background: #e0f2f1; color: #00695c; border: 1px solid #80cbc4; }
        .st-tolak { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

        /* TOMBOL AKSI KECIL */
        .btn-circle { width: 32px; height: 32px; border-radius: 50%; border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; margin-right: 5px; font-size: 13px; transition: 0.2s; color: #fff; }
        .btn-check { background: #28a745; box-shadow: 0 2px 5px rgba(40,167,69,0.3); }
        .btn-cross { background: #dc3545; box-shadow: 0 2px 5px rgba(220,53,69,0.3); }
        .btn-refresh { background: #007bff; box-shadow: 0 2px 5px rgba(0,123,255,0.3); }
        .btn-refresh:hover { transform: rotate(180deg); }

        /* MODAL POPUP MODERN */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background: #fff; margin: 5vh auto; padding: 30px; width: 500px; max-width: 90%; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); animation: slideUpModal 0.3s ease; max-height: 90vh; overflow-y: auto; }
        @keyframes slideUpModal { from {transform:translateY(50px); opacity:0;} to {transform:translateY(0); opacity:1;} }
        
        .modal-header h3 { margin: 0 0 20px 0; color: var(--primary); font-size: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 5px; text-transform: uppercase; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        .form-input:focus { border-color: var(--primary); outline: none; }
        
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; border-bottom: 1px dashed #eee; padding-bottom: 5px; }
        .d-label { color: #888; } .d-val { font-weight: 600; color: #333; }
        
        .actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-modal { flex: 1; padding: 10px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn-cancel { background: #f0f0f0; color: #333; } 
        .btn-save { background: var(--primary); color: #fff; }
        .btn-del { background: #dc3545; color: #fff; }
        
        .hidden-form { display: none; }
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
            <a href="verifikasi_pemesanan.php">Pesanan</a>
            <a href="jadwal.php" class="active-link">Jadwal</a>
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
            <div class="page-title">
                <h2>Jadwal Pemotretan</h2>
            </div>
            <a href="#" onclick="showAddModal()" class="btn-add"><i class="fas fa-plus"></i> Tambah Jadwal</a>
        </div>

        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="filter-input" placeholder="Cari Nama / ID..." value="<?php echo htmlspecialchars($search); ?>">
            <input type="date" name="tgl" class="filter-input" value="<?php echo $filter_tgl; ?>">
            <select name="status" class="filter-input" onchange="this.form.submit()">
                <option value="">Status</option>
                <option value="Menunggu">Menunggu</option>
                <option value="Terjadwal">Terjadwal</option>
                <option value="Selesai">Selesai</option>
            </select>
            <a href="jadwal.php" class="btn-reset">Reset</a>
        </form>

        <?php if($message): ?><div style="background:#d4edda; color:#155724; padding:15px; border-radius:10px; margin-bottom:20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div><?php endif; ?>

        <div class="table-card">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID & Nama</th>
                        <th>Paket</th>
                        <th>Tanggal & Waktu</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr onclick='openDetail(<?php echo json_encode($row); ?>)'>
                                <td><b><?php echo $row['orderId']; ?></b><br><span style="font-size:12px; color:#888;"><?php echo htmlspecialchars($row['namaPelanggan']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['namaPaket']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?><br><span style="font-size:12px; color:#6A5ACD; font-weight:600;"><?php echo date('H:i', strtotime($row['waktuMulai'])); ?> WIB</span></td>
                                <td><?php echo htmlspecialchars($row['lokasi'] ?? '-'); ?></td>
                                <td>
                                    <?php 
                                        $st = $row['statusPesanan'];
                                        $badgeClass = 'st-wait';
                                        if ($st == 'Terjadwal') $badgeClass = 'st-terjadwal';
                                        if ($st == 'Selesai') $badgeClass = 'st-selesai';
                                        if ($st == 'Dibatalkan') $badgeClass = 'st-tolak';
                                        echo "<span class='badge $badgeClass'>$st</span>";
                                    ?>
                                </td>
                                <td onclick="event.stopPropagation();">
                                    <?php if ($st == 'Menunggu'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="orderId" value="<?php echo $row['orderId']; ?>">
                                            <button type="submit" name="action" value="setuju" class="btn-circle btn-check" title="Setujui"><i class="fas fa-check"></i></button>
                                            <button type="submit" name="action" value="tolak" class="btn-circle btn-cross" title="Tolak"><i class="fas fa-times"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <div style="display:flex; align-items:center;">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="orderId" value="<?php echo $row['orderId']; ?>">
                                                <button type="submit" name="action" value="reset" class="btn-circle btn-refresh" title="Reset / Pilih Ulang" onclick="return confirm('Kembalikan status ke Menunggu?');"><i class="fas fa-sync-alt"></i></button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#999;">Tidak ada jadwal.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addModal" class="modal" onclick="if(event.target==this) closeAddModal()">
        <div class="modal-content">
            <div class="modal-header"><h3>Tambah Jadwal Manual</h3></div>
            <form method="POST">
                <div class="form-group"><label>Nama Pelanggan</label><input type="text" name="new_nama" class="form-input" required></div>
                <div class="form-group"><label>Paket</label><select name="new_paketId" class="form-input" required><?php foreach($list_paket as $lp): ?><option value="<?php echo $lp['paketId']; ?>"><?php echo $lp['namaPaket']; ?></option><?php endforeach; ?></select></div>
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;"><label>Tanggal</label><input type="date" name="new_tanggal" class="form-input" required></div>
                    <div class="form-group" style="flex:1;"><label>Waktu</label><input type="time" name="new_waktu" class="form-input" required></div>
                </div>
                <div class="form-group"><label>Lokasi</label><input type="text" name="new_lokasi" class="form-input"></div>
                <div class="form-group"><label>Alamat</label><input type="text" name="new_alamat" class="form-input"></div>
                <div class="form-group"><label>Catatan</label><input type="text" name="new_catatan" class="form-input"></div>
                <div class="actions">
                    <button type="button" onclick="closeAddModal()" class="btn-modal btn-cancel">Batal</button>
                    <button type="submit" name="tambah_manual" class="btn-modal btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="detailModal" class="modal" onclick="if(event.target==this) closeDetail()">
        <div class="modal-content">
            <div class="modal-header"><h3>Detail Jadwal</h3></div>
            <div id="viewMode">
                <div class="detail-row"><span class="d-label">ID Pesanan</span><span class="d-val" id="d_id"></span></div>
                <div class="detail-row"><span class="d-label">Nama</span><span class="d-val" id="d_nama"></span></div>
                <div class="detail-row"><span class="d-label">Paket</span><span class="d-val" id="d_paket"></span></div>
                <div class="detail-row"><span class="d-label">Tanggal</span><span class="d-val" id="d_tgl"></span></div>
                <div class="detail-row"><span class="d-label">Waktu</span><span class="d-val" id="d_waktu"></span></div>
                <div class="detail-row"><span class="d-label">Lokasi</span><span class="d-val" id="d_lokasi"></span></div>
                <div class="detail-row"><span class="d-label">Alamat</span><span class="d-val" id="d_alamat"></span></div>
                <div class="detail-row"><span class="d-label">Catatan</span><span class="d-val" id="d_catatan"></span></div>
                
                <div class="actions">
                    <button class="btn-modal btn-cancel" onclick="showEditForm()">Edit</button>
                    <form method="POST" style="flex:1;" onsubmit="return confirm('Selesaikan?');"><input type="hidden" name="orderId" id="form_finish_id"><button type="submit" name="action" value="selesai" class="btn-modal btn-save">Selesai</button></form>
                    <form method="POST" style="flex:1;" onsubmit="return confirm('Hapus?');"><input type="hidden" name="orderId" id="form_del_id"><button type="submit" name="action" value="hapus" class="btn-modal btn-del">Hapus</button></form>
                </div>
            </div>

            <div id="editMode" class="hidden-form">
                <form method="POST">
                    <input type="hidden" name="edit_orderId" id="e_id">
                    <div class="form-group"><label>Tanggal</label><input type="date" name="edit_tanggal" id="e_tgl" class="form-input" required></div>
                    <div class="form-group"><label>Waktu</label><input type="time" name="edit_waktu" id="e_waktu" class="form-input" required></div>
                    <div class="form-group"><label>Lokasi</label><input type="text" name="edit_lokasi" id="e_lokasi" class="form-input"></div>
                    <div class="form-group"><label>Alamat</label><input type="text" name="edit_alamat" id="e_alamat" class="form-input"></div>
                    <div class="actions">
                        <button type="button" onclick="hideEditForm()" class="btn-modal btn-cancel">Batal</button>
                        <button type="submit" name="edit_jadwal" class="btn-modal btn-save">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddModal() { document.getElementById('addModal').style.display = 'block'; }
        function closeAddModal() { document.getElementById('addModal').style.display = 'none'; }
        function openDetail(data) {
            document.getElementById('detailModal').style.display = 'block';
            document.getElementById('viewMode').style.display = 'block';
            document.getElementById('editMode').style.display = 'none';
            document.getElementById('d_id').innerText = data.orderId; document.getElementById('d_nama').innerText = data.namaPelanggan;
            document.getElementById('d_paket').innerText = data.namaPaket; document.getElementById('d_tgl').innerText = data.tanggal;
            document.getElementById('d_waktu').innerText = data.waktuMulai; document.getElementById('d_lokasi').innerText = data.lokasi || '-';
            document.getElementById('d_alamat').innerText = data.alamat || '-'; document.getElementById('d_catatan').innerText = data.catatan || '-';
            document.getElementById('form_del_id').value = data.orderId; document.getElementById('form_finish_id').value = data.orderId;
            document.getElementById('e_id').value = data.orderId; document.getElementById('e_tgl').value = data.tanggal;
            document.getElementById('e_waktu').value = data.waktuMulai; document.getElementById('e_lokasi').value = data.lokasi;
            document.getElementById('e_alamat').value = data.alamat;
        }
        function closeDetail() { document.getElementById('detailModal').style.display = 'none'; }
        function showEditForm() { document.getElementById('viewMode').style.display = 'none'; document.getElementById('editMode').style.display = 'block'; }
        function hideEditForm() { document.getElementById('viewMode').style.display = 'block'; document.getElementById('editMode').style.display = 'none'; }
    </script>
</body>
</html>