<?php
// dashboard_pelanggan.php - LIGHTNING THEME + FOOTER INFO
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('includes/db_koneksi.php');
include('includes/auto_batal.php');

// Cek autentikasi
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Pelanggan') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_username = $_SESSION['username'];
$tahun_ini = date('Y');

// --- DATA RINGKASAN ---
$q1 = $koneksi->query("SELECT COUNT(*) as total FROM pemesanan WHERE userId = '$user_id' AND YEAR(tanggalPesan) = '$tahun_ini'");
$total_pesanan = $q1->fetch_assoc()['total'];

$q2 = $koneksi->query("SELECT COUNT(*) as total FROM pemesanan p LEFT JOIN pembayaran b ON p.orderId = b.orderId WHERE p.userId = '$user_id' AND p.statusPesanan = 'Menunggu' AND (b.statusBayar IS NULL OR b.statusBayar = 'Ditolak')");
$belum_bayar = $q2->fetch_assoc()['total'];

$q3 = $koneksi->query("SELECT COUNT(*) as total FROM pemesanan p JOIN pembayaran b ON p.orderId = b.orderId WHERE p.userId = '$user_id' AND p.statusPesanan = 'Menunggu' AND b.statusBayar = 'Menunggu Verifikasi'");
$sedang_verif = $q3->fetch_assoc()['total'];

$q4 = $koneksi->query("SELECT COUNT(*) as total FROM pemesanan WHERE userId = '$user_id' AND statusPesanan = 'Terjadwal'");
$terjadwal = $q4->fetch_assoc()['total'];

// --- DATA REFERENSI ---
$referensi_list = [
    [
        "img" => "foto/ss.jpg",
        "title" => "Studio Creative",
        "desc" => "Eksplorasi kreativitas tanpa batas di studio kami. Sesi ini sangat cocok untuk foto grup teman, prewedding indoor konsep unik, atau foto artistik dengan pengaturan lighting yang dramatis."
    ],
    [
        "img" => "foto/makanan.jpeg",
        "title" => "Food Photography",
        "desc" => "Buat pelanggan lapar hanya dengan melihat foto! Layanan ini mencakup *food styling* sederhana untuk membuat makanan terlihat menggugah selera. Cocok untuk buku menu restoran, banner promosi kuliner, atau konten Instagram bisnis makanan Anda."
    ],
    [
        "img" => "foto/produk.webp",
        "title" => "Creative Product Shot",
        "desc" => "Sesi foto produk dengan konsep kreatif menggunakan properti pendukung dan latar belakang artistik. Tujuannya adalah membangun *brand image* yang kuat dan unik, berbeda dari foto katalog biasa yang hanya berlatar putih polos."
    ],
    [
        "img" => "foto/potret.png",
        "title" => "Portrait Session",
        "desc" => "Sesi foto potret personal untuk kebutuhan profesional, personal branding, atau sekadar koleksi pribadi di media sosial. Fotografer kami akan membantu mengarahkan gaya (posing) agar karakter terbaik Anda terpancar kuat dalam setiap jepretan."
    ],
    [
        "img" => "foto/wedding.jpg",
        "title" => "Wedding Photography",
        "desc" => "Abadikan momen sakral pernikahan Anda dengan sentuhan artistik. Paket ini mencakup sesi foto akad, resepsi, hingga candid moments yang penuh emosi. Kami menggunakan pencahayaan natural dan teknik komposisi modern untuk menghasilkan foto yang timeless dan bercerita."
    ],
    [
        "img" => "foto/wisuda.jpg",
        "title" => "Wisuda & Graduation",
        "desc" => "Rayakan pencapaian akademis Anda bersama keluarga dan sahabat tercinta. Paket wisuda kami dirancang khusus untuk menangkap kebahagiaan dan kebanggaan di hari kelulusan dengan pilihan latar belakang studio yang elegan atau sesi outdoor di area kampus."
    ],
    [
        "img" => "foto/keluarga.jpg",
        "title" => "Foto Keluarga",
        "desc" => "Momen kebersamaan keluarga sangat berharga dan tak terulang. Kami menyediakan sesi foto keluarga yang hangat, ceria, dan santai. Cocok untuk keluarga besar maupun kecil, dengan berbagai properti pendukung yang membuat suasana lebih hidup."
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pelanggan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- 1. GLOBAL STYLE (LIGHTNING THEME) --- */
        :root {
            --primary: #6A5ACD; /* Electric Purple */
            --cyan: #00d2ff;    /* Electric Blue */
            --text-dark: #333;
            --text-gray: #666;
            --bg-light: #f4f7fc;
        }

        body { 
            margin: 0; padding: 0; 
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            color: var(--text-dark);
            overflow-x: hidden;
            background-color: var(--bg-light);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* Background Elements */
        body::before {
            content: ''; position: fixed; top: -50px; left: -20px; width: 350px; height: 100vh;
            background: linear-gradient(135deg, rgba(106, 90, 205, 0.25), rgba(0, 210, 255, 0.25));
            clip-path: polygon(55% 0, 10% 40%, 50% 40%, 20% 100%, 80% 55%, 45% 55%, 75% 0);
            z-index: -1; animation: floatBolt 6s ease-in-out infinite alternate;
            filter: drop-shadow(0 0 15px rgba(106, 90, 205, 0.3));
        }
        body::after {
            content: ''; position: fixed; bottom: 0; right: -50px; width: 300px; height: 80vh;
            background: linear-gradient(135deg, rgba(0, 210, 255, 0.2), rgba(106, 90, 205, 0.2));
            clip-path: polygon(60% 0, 20% 45%, 55% 45%, 30% 100%, 90% 50%, 50% 50%);
            z-index: -1; animation: floatBolt 8s ease-in-out infinite alternate-reverse;
            filter: drop-shadow(0 0 15px rgba(0, 210, 255, 0.3));
        }
        @keyframes floatBolt {
            0% { transform: translateY(0) scale(1); opacity: 0.8; }
            100% { transform: translateY(-20px) scale(1.05); opacity: 1; }
        }
        .bg-flash {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.8), transparent 70%);
            z-index: -2; opacity: 0; animation: flash 10s infinite; pointer-events: none;
        }
        @keyframes flash {
            0%, 95%, 100% { opacity: 0; }
            96%, 98% { opacity: 0.1; }
        }

        /* --- 2. HEADER NAVIGASI --- */
        .top-nav { 
            background-color: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 15px rgba(0,0,0,0.05); 
            height: 70px; display: flex; align-items: center; padding: 0 30px; 
            position: sticky; top: 0; z-index: 1000; 
        }
        .logo-nav { display: flex; align-items: center; width: 250px; gap: 10px; }
        .logo-circle { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .brand-text { font-weight: 700; font-size: 18px; color: var(--primary); letter-spacing: 1px; }
        .nav-links { flex-grow: 1; display: flex; gap: 5px; }
        .nav-links a { color: #666; font-weight: 500; text-decoration: none; font-size: 14px; padding: 10px 15px; border-radius: 6px; transition: all 0.3s ease; }
        .nav-links a:hover, .nav-links .active-link { color: var(--primary); background-color: rgba(106, 90, 205, 0.1); font-weight: 600; }
        .user-menu { margin-left: auto; position: relative; }
        .dropbtn { background: #fff; border: 1px solid #eee; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600; color: #333; font-size: 14px; padding: 8px 15px; border-radius: 30px; transition: 0.3s; }
        .dropbtn:hover { box-shadow: 0 3px 10px rgba(106,90,205,0.2); border-color: var(--primary); }
        .dropdown-content { display: none; position: absolute; right: 0; top: 120%; background-color: #fff; min-width: 180px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; border: 1px solid #eee; }
        .dropdown-content a { color: #333; padding: 12px 16px; display: block; font-size: 13px; text-decoration: none; border-bottom: 1px solid #f9f9f9; }
        .dropdown-content a:hover { background-color: #f9f9ff; color: var(--primary); }
        .user-menu:hover .dropdown-content { display: block; }

        /* --- 3. KONTEN DASHBOARD --- */
        .content { padding: 30px 20px; max-width: 1200px; margin: 0 auto; flex: 1; width: 100%; box-sizing: border-box; }

        .main-header { 
            background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%); 
            color: white; padding: 40px; border-radius: 20px; margin-bottom: 40px; 
            box-shadow: 0 15px 30px rgba(106, 90, 205, 0.3); 
            position: relative; overflow: hidden;
        }
        .main-header h2 { margin: 0 0 5px 0; font-size: 28px; position: relative; z-index: 2; }
        .main-header p { margin: 0; font-size: 16px; opacity: 0.9; position: relative; z-index: 2; }
        .main-header::before {
            content: ''; position: absolute; top: -20px; right: 20px; width: 100px; height: 200px;
            background: rgba(255,255,255,0.1); clip-path: polygon(55% 0, 10% 40%, 50% 40%, 20% 100%, 80% 55%, 45% 55%, 75% 0);
            transform: rotate(15deg);
        }

        /* CARD GRID */
        h3.section-title { 
            color: #444; margin-bottom: 20px; font-size: 20px; 
            border-left: 5px solid var(--primary); padding-left: 15px;
        }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 50px; }
        .card { 
            background: #ffffff; padding: 25px; border-radius: 16px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; 
            border: 1px solid #f0f0f0; transition: all 0.3s ease; position: relative;
        }
        .card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(106, 90, 205, 0.2); border-color: rgba(106, 90, 205, 0.3); }
        .card h3 { font-size: 13px; color: #888; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 1px; }
        .card p { font-size: 3em; margin: 0; font-weight: 700; color: #333; line-height: 1; }
        .icon-card { font-size: 40px; margin-bottom: 15px; opacity: 0.8; }
        
        .c-blue .icon-card { color: #007bff; } .c-blue:hover { box-shadow: 0 15px 30px rgba(0, 123, 255, 0.2); }
        .c-red .icon-card { color: #dc3545; } .c-red:hover { box-shadow: 0 15px 30px rgba(220, 53, 69, 0.2); }
        .c-orange .icon-card { color: #fd7e14; } .c-orange:hover { box-shadow: 0 15px 30px rgba(253, 126, 20, 0.2); }
        .c-green .icon-card { color: #28a745; } .c-green:hover { box-shadow: 0 15px 30px rgba(40, 167, 69, 0.2); }

        /* --- 4. GALLERY SLIDER (Thumbnail) --- */
        .gallery-container {
            margin-top: 40px; overflow-x: auto; white-space: nowrap; padding-bottom: 20px;
            -webkit-overflow-scrolling: touch; scrollbar-width: thin; 
        }
        .gallery-container::-webkit-scrollbar { height: 8px; }
        .gallery-container::-webkit-scrollbar-track { background: #eee; border-radius: 4px; }
        .gallery-container::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 4px; }

        .gallery-item {
            display: inline-block; width: 280px; height: 180px; margin-right: 20px; border-radius: 15px;
            overflow: hidden; position: relative; box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: 0.3s; background: #fff; border: 4px solid #fff; cursor: pointer;
        }
        .gallery-item:hover { transform: scale(1.05); z-index: 10; box-shadow: 0 15px 30px rgba(106, 90, 205, 0.3); border-color: var(--primary); }
        
        .gallery-img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-caption {
            position: absolute; bottom: 0; left: 0; right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white; padding: 15px; font-size: 14px; font-weight: 600;
        }

        .btn-action-big {
            background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%);
            color: white; padding: 15px 40px; text-decoration: none; 
            border-radius: 50px; font-weight: bold; font-size: 16px; 
            box-shadow: 0 10px 25px rgba(106, 90, 205, 0.4); 
            transition: 0.3s; display: inline-flex; align-items: center; gap: 10px;
        }
        .btn-action-big:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(106, 90, 205, 0.6); }

        /* --- 5. MODAL LIGHTBOX STYLE --- */
        .modal {
            display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.9); backdrop-filter: blur(5px);
            justify-content: center; align-items: center; flex-direction: column;
        }
        
        .modal-content-wrapper {
            position: relative; max-width: 900px; width: 90%; 
            display: flex; flex-direction: column; align-items: center;
        }

        .modal-img {
            max-width: 100%; max-height: 60vh; border-radius: 10px; box-shadow: 0 0 50px rgba(106, 90, 205, 0.5);
            object-fit: contain; animation: zoomIn 0.3s ease;
        }
        @keyframes zoomIn { from {transform: scale(0.8); opacity: 0;} to {transform: scale(1); opacity: 1;} }

        .modal-text {
            background: white; padding: 20px; border-radius: 10px; margin-top: 20px; width: 100%;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3); text-align: left; animation: slideUp 0.4s ease;
        }
        @keyframes slideUp { from {transform: translateY(20px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }

        .modal-text h3 { margin: 0 0 10px 0; color: var(--primary); font-size: 20px; }
        .modal-text p { margin: 0; font-size: 14px; line-height: 1.6; color: #444; }

        .close-modal {
            position: absolute; top: -40px; right: 0; color: #fff; font-size: 35px; font-weight: bold; cursor: pointer; transition: 0.3s;
        }
        .close-modal:hover { color: var(--cyan); transform: scale(1.1); }

        /* Navigation Buttons */
        .prev, .next {
            cursor: pointer; position: absolute; top: 40%; width: auto; padding: 16px;
            margin-top: -50px; color: white; font-weight: bold; font-size: 24px;
            transition: 0.6s ease; border-radius: 0 3px 3px 0; user-select: none;
            background-color: rgba(0,0,0,0.3);
        }
        .next { right: -60px; border-radius: 3px 0 0 3px; }
        .prev { left: -60px; border-radius: 0 3px 3px 0; }
        .prev:hover, .next:hover { background-color: var(--primary); }

        /* --- 6. FOOTER SECTION --- */
        .site-footer {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            padding: 50px 20px 20px;
            margin-top: 60px;
            border-top: 4px solid var(--primary);
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }
        .footer-col h4 {
            color: var(--cyan);
            font-size: 18px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer-col p {
            font-size: 14px;
            line-height: 1.6;
            color: #b0b0b0;
        }
        .footer-socials {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .social-btn {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            color: #fff; text-decoration: none;
            transition: 0.3s;
        }
        .social-btn:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }
        .footer-bottom {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 13px;
            color: #888;
        }

        @media (max-width: 768px) {
            .prev, .next { position: absolute; top: unset; bottom: -60px; background: rgba(255,255,255,0.1); }
            .prev { left: 0; } .next { right: 0; }
            .modal-img { max-height: 50vh; }
        }

    </style>
</head>
<body>
    
    <div class="bg-flash"></div>

    <div class="top-nav">
        <div class="logo-nav">
            <img src="foto/logo.jpg" alt="Logo" class="logo-circle">
            <span class="brand-text">ISTORE</span>
        </div>
        
        <div class="nav-links">
            <a href="dashboard_pelanggan.php" class="active-link">Beranda</a>
            <a href="pemesanan.php">Pemesanan</a>
            <a href="pembayaran.php">Pembayaran</a>
            <a href="riwayat_pesanan.php">Riwayat</a>
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
        
        <div class="main-header">
            <h2>Halo, <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
            <p>Selamat datang kembali di ISTORE Studio. Berikut ringkasan aktivitas foto Anda.</p>
        </div>

        <h3 class="section-title">Status Pesanan (<?php echo $tahun_ini; ?>)</h3>
        <div class="card-grid">
            <div class="card c-blue">
                <div class="icon-card"><i class="fas fa-folder-open"></i></div>
                <h3>Total Riwayat</h3>
                <p><?php echo $total_pesanan; ?></p>
            </div>
            
            <div class="card c-red">
                <div class="icon-card"><i class="fas fa-exclamation-circle"></i></div>
                <h3>Belum Dibayar</h3>
                <p><?php echo $belum_bayar; ?></p>
                <?php if($belum_bayar > 0): ?>
                    <a href="pembayaran.php" style="display:block; margin-top:10px; font-size:12px; color:#dc3545; font-weight:bold; text-decoration:none;">Bayar Sekarang &rarr;</a>
                <?php endif; ?>
            </div>

            <div class="card c-orange">
                <div class="icon-card"><i class="fas fa-clock"></i></div>
                <h3>Sedang Diverifikasi</h3>
                <p><?php echo $sedang_verif; ?></p>
            </div>
            
            <div class="card c-green">
                <div class="icon-card"><i class="fas fa-check-circle"></i></div>
                <h3>Jadwal Aktif</h3>
                <p><?php echo $terjadwal; ?></p>
            </div>
        </div>

        <div style="text-align:center; margin-bottom: 50px;">
            <a href="pemesanan.php" class="btn-action-big">
                <i class="fas fa-plus-circle"></i> Buat Pesanan Baru
            </a>
        </div>

        <h3 class="section-title">Referensi Gaya Foto</h3>
        <p style="color:#666; font-size:14px; margin-bottom:15px; font-style:italic;">Klik foto untuk melihat detail dan geser untuk melihat lainnya.</p>
        
        <div class="gallery-container">
            <?php foreach($referensi_list as $index => $ref): ?>
                <div class="gallery-item" onclick="openModal(<?php echo $index; ?>)">
                    <img src="<?php echo $ref['img']; ?>" class="gallery-img" alt="<?php echo $ref['title']; ?>">
                    <div class="gallery-caption"><?php echo $ref['title']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
    </div>

    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-col">
                <h4>Tentang ISTORE</h4>
                <p>Studio fotografi profesional yang mengutamakan kualitas dan kepuasan pelanggan. Kami hadir untuk mengabadikan setiap momen berharga Anda menjadi kenangan abadi.</p>
            </div>
            <div class="footer-col">
                <h4>Hubungi Kami</h4>
                <p><i class="fas fa-map-marker-alt"></i> W4X2+GMF Balapulang Wetan, Kabupaten Tegal, Jawa Tengah </p>
                <p><i class="fas fa-envelope"></i> Istore@gmail.com</p>
                <p><i class="fas fa-phone"></i> +62 812-3456-7890</p>
            </div>
            <div class="footer-col">
                <h4>Ikuti Kami</h4>
                <div class="footer-socials">
                    <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-tiktok"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> ISTORE Studio. Created with <i class="fas fa-heart" style="color:var(--primary)"></i> by <b>Enematika Dwicatur</b>.
        </div>
    </footer>

    <div id="galleryModal" class="modal">
        <div class="modal-content-wrapper">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            
            <img id="modalImg" class="modal-img" src="" alt="Referensi">
            
            <a class="prev" onclick="changeSlide(-1)">&#10094;</a>
            <a class="next" onclick="changeSlide(1)">&#10095;</a>

            <div class="modal-text">
                <h3 id="modalTitle"></h3>
                <p id="modalDesc"></p>
            </div>
        </div>
    </div>

    <script>
        // Data dari PHP ke JS
        const galleryData = <?php echo json_encode($referensi_list); ?>;
        let currentIndex = 0;

        function openModal(index) {
            currentIndex = index;
            updateModalContent();
            document.getElementById('galleryModal').style.display = "flex";
        }

        function closeModal() {
            document.getElementById('galleryModal').style.display = "none";
        }

        function changeSlide(n) {
            currentIndex += n;
            // Loop jika index lewat batas
            if (currentIndex >= galleryData.length) { currentIndex = 0; }
            if (currentIndex < 0) { currentIndex = galleryData.length - 1; }
            updateModalContent();
        }

        function updateModalContent() {
            const item = galleryData[currentIndex];
            document.getElementById('modalImg').src = item.img;
            document.getElementById('modalTitle').innerText = item.title;
            document.getElementById('modalDesc').innerText = item.desc;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('galleryModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

</body>
</html>