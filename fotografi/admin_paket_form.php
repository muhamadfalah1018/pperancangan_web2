<?php
// admin_paket_form.php - MODERN UI + ANIMATED BG + LOGO BULAT
session_start();
include('includes/db_koneksi.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$error = '';
$paketId = '';
$namaPaket = '';
$kategori = 'Outdoor'; // Default
$harga = '';
$deskripsi = '';
$is_edit = false;

// --- MODE EDIT: AMBIL DATA ---
if (isset($_GET['edit'])) {
    $is_edit = true;
    $paketId = $_GET['edit'];
    $stmt = $koneksi->prepare("SELECT * FROM paketlayanan WHERE paketId = ?");
    $stmt->bind_param("s", $paketId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $namaPaket = $row['namaPaket'];
        $kategori = $row['kategori'];
        $harga = $row['harga'];
        $deskripsi = $row['deskripsi'];
    } else {
        header("Location: admin_paket.php");
        exit();
    }
}

// --- PROSES SIMPAN DATA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $namaPaket = $_POST['namaPaket'];
    $kategori = $_POST['kategori'];
    $harga = $_POST['harga'];
    $deskripsi = $_POST['deskripsi'];

    if ($is_edit) {
        // UPDATE
        $stmt = $koneksi->prepare("UPDATE paketlayanan SET namaPaket=?, kategori=?, harga=?, deskripsi=? WHERE paketId=?");
        $stmt->bind_param("ssdss", $namaPaket, $kategori, $harga, $deskripsi, $paketId);
        if ($stmt->execute()) {
            header("Location: admin_paket.php");
            exit();
        } else {
            $error = "Gagal update: " . $koneksi->error;
        }
    } else {
        // INSERT - Generate ID Otomatis
        $q = $koneksi->query("SELECT MAX(paketId) as maxId FROM paketlayanan");
        $data = $q->fetch_assoc();
        $kode = $data['maxId']; 
        $urutan = (int) substr($kode, 3, 3);
        $urutan++;
        $paketId = "PKT" . sprintf("%03s", $urutan);

        $stmt = $koneksi->prepare("INSERT INTO paketlayanan (paketId, namaPaket, kategori, harga, deskripsi) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $paketId, $namaPaket, $kategori, $harga, $deskripsi);
        
        if ($stmt->execute()) {
            header("Location: admin_paket.php");
            exit();
        } else {
            $error = "Gagal simpan: " . $koneksi->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_edit ? 'Edit' : 'Tambah'; ?> Paket - Admin</title>
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
            /* BACKGROUND ANIMASI */
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

        /* --- 3. KONTEN FORMULIR --- */
        .content { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: flex; justify-content: center; }
        
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 700px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.5);
        }

        .form-header {
            text-align: center; margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0; padding-bottom: 20px;
        }
        .form-header h2 { margin: 0; color: var(--text-dark); font-weight: 700; font-size: 24px; }
        .form-header p { color: var(--text-gray); font-size: 14px; margin-top: 5px; }

        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-dark); font-size: 14px; }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
            box-sizing: border-box;
            background-color: #fcfcfc;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            background-color: #fff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(106, 90, 205, 0.1);
        }

        /* Styling Readonly Input */
        .form-control[disabled] {
            background-color: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
            border-color: #ddd;
        }

        textarea.form-control { resize: vertical; min-height: 100px; }

        /* Tombol Aksi */
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        
        .btn-submit {
            flex: 2;
            background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(106, 90, 205, 0.4); }

        .btn-cancel {
            flex: 1;
            background: #fff;
            color: var(--text-gray);
            border: 2px solid #eee;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-cancel:hover { background: #f9f9f9; border-color: #ddd; color: var(--text-dark); }

        .alert-error {
            background: #ffe6e6; color: #d63031; padding: 15px;
            border-radius: 10px; margin-bottom: 20px; font-size: 14px;
            border-left: 5px solid #d63031; display: flex; align-items: center; gap: 10px;
        }

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
            <a href="#">Foto</a>
            <a href="#">Laporan</a>
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
        <div class="form-card">
            <div class="form-header">
                <h2><?php echo $is_edit ? 'Edit Paket Layanan' : 'Tambah Paket Baru'; ?></h2>
                <p>Silakan isi informasi paket dengan lengkap dan jelas.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                
                <?php if ($is_edit): ?>
                    <div class="form-group">
                        <label>ID Paket</label>
                        <input type="text" class="form-control" value="<?php echo $paketId; ?>" disabled>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Nama Paket <span style="color:red">*</span></label>
                    <input type="text" name="namaPaket" class="form-control" value="<?php echo htmlspecialchars($namaPaket); ?>" required placeholder="Contoh: Paket Wedding Exclusive">
                </div>

                <div class="form-group">
                    <label>Kategori Layanan <span style="color:red">*</span></label>
                    <select name="kategori" class="form-control" required>
                        <option value="Outdoor" <?php echo ($kategori == 'Outdoor') ? 'selected' : ''; ?>>Outdoor (Lokasi Luar)</option>
                        <option value="Indoor" <?php echo ($kategori == 'Indoor') ? 'selected' : ''; ?>>Indoor (Studio)</option>
                    </select>
                    <small style="color:var(--text-gray); margin-top:5px; display:block; font-size:12px;">*Kategori Indoor akan memunculkan opsi "Durasi Sewa" di formulir pelanggan.</small>
                </div>

                <div class="form-group">
                    <label>Harga (Rp) <span style="color:red">*</span></label>
                    <input type="number" name="harga" class="form-control" value="<?php echo $harga; ?>" required placeholder="Tanpa titik/koma, misal: 1500000">
                </div>

                <div class="form-group">
                    <label>Deskripsi Paket <span style="color:red">*</span></label>
                    <textarea name="deskripsi" class="form-control" required placeholder="Jelaskan detail apa saja yang didapat pelanggan (Jumlah foto, durasi, cetak, dll)..."><?php echo htmlspecialchars($deskripsi); ?></textarea>
                </div>

                <div class="btn-group">
                    <a href="admin_paket.php" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>