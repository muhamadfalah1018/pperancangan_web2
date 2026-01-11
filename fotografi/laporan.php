<?php
// laporan.php - FIXED: Hasil Foto Filter & Toggle Checkbox Link
session_start();
include('includes/db_koneksi.php');

// 1. CEK AKSES
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Owner'])) {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$user_role = $_SESSION['role'];
$user_fullname = $_SESSION['name'] ?? $user_username;

// 2. LOGIKA FILTER & NAVIGASI
$jenis  = $_GET['jenis'] ?? 'Pendapatan';
$satuan = $_GET['satuan'] ?? 'Harian';
$is_excel = (isset($_GET['export']) && $_GET['export'] == 'excel');

// LOGIKA CHECKBOX SERTAKAN LINK (Khusus Hasil Foto)
// Jika dicentang (ada di URL), nilai true. Jika tidak, false.
$with_link = (isset($_GET['with_link']) && $_GET['with_link'] == '1');

// Inisialisasi Default
$start_date = '';
$end_date   = '';
$label_periode = '';
$data_laporan = [];
$total_grand = 0;
$total_qty   = 0;

// SETTING PERIODE WAKTU
if ($satuan == 'Harian') {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date   = $_GET['end_date'] ?? date('Y-m-d');
    $label_periode = date('d/m/Y', strtotime($start_date)) . " - " . date('d/m/Y', strtotime($end_date));

} elseif ($satuan == 'Bulanan') {
    $bulan_input = $_GET['bulan'] ?? date('Y-m');
    $start_date  = $bulan_input . "-01";
    $end_date    = date('Y-m-t', strtotime($start_date));
    $label_periode = date('F Y', strtotime($start_date));

} elseif ($satuan == 'Tahunan') {
    $tahun_input = $_GET['tahun'] ?? date('Y');
    $start_date  = $tahun_input . "-01-01";
    $end_date    = $tahun_input . "-12-31";
    $label_periode = "Tahun " . $tahun_input;
}

// 3. LOGIKA QUERY PINTAR
if ($jenis == 'Pemesanan' || $jenis == 'Pendapatan') {
    $where_status = ($jenis == 'Pendapatan') ? "AND p.statusPesanan IN ('Terjadwal', 'Selesai')" : "";

    if ($satuan == 'Harian') {
        $query = "SELECT p.orderId, p.tanggalPesan, p.namaPelanggan, pk.namaPaket, p.statusPesanan, p.totalHarga 
                  FROM pemesanan p 
                  JOIN paketlayanan pk ON p.paketId = pk.paketId 
                  WHERE DATE(p.tanggalPesan) BETWEEN '$start_date' AND '$end_date' $where_status
                  ORDER BY p.tanggalPesan DESC";
    } elseif ($satuan == 'Bulanan') {
        $query = "SELECT DATE(p.tanggalPesan) as tgl, COUNT(*) as jumlah_transaksi, SUM(p.totalHarga) as total_nilai
                  FROM pemesanan p 
                  WHERE DATE(p.tanggalPesan) BETWEEN '$start_date' AND '$end_date' $where_status
                  GROUP BY tgl ORDER BY tgl ASC";
    } elseif ($satuan == 'Tahunan') {
        $query = "SELECT MONTH(p.tanggalPesan) as bln, COUNT(*) as jumlah_transaksi, SUM(p.totalHarga) as total_nilai
                  FROM pemesanan p 
                  WHERE DATE(p.tanggalPesan) BETWEEN '$start_date' AND '$end_date' $where_status
                  GROUP BY bln ORDER BY bln ASC";
    }

} elseif ($jenis == 'Pembayaran') {
    if ($satuan == 'Harian') {
        $query = "SELECT b.pembayaranId, b.orderId, b.tanggalBayar, b.jumlahBayar, b.metode, b.statusBayar 
                  FROM pembayaran b 
                  WHERE b.tanggalBayar BETWEEN '$start_date' AND '$end_date'
                  ORDER BY b.tanggalBayar DESC";
    } elseif ($satuan == 'Bulanan') {
        $query = "SELECT b.tanggalBayar as tgl, COUNT(*) as jumlah_transaksi, SUM(b.jumlahBayar) as total_nilai 
                  FROM pembayaran b 
                  WHERE b.tanggalBayar BETWEEN '$start_date' AND '$end_date'
                  GROUP BY tgl ORDER BY tgl ASC";
    } elseif ($satuan == 'Tahunan') {
        $query = "SELECT MONTH(b.tanggalBayar) as bln, COUNT(*) as jumlah_transaksi, SUM(b.jumlahBayar) as total_nilai 
                  FROM pembayaran b 
                  WHERE b.tanggalBayar BETWEEN '$start_date' AND '$end_date'
                  GROUP BY bln ORDER BY bln ASC";
    }

} elseif ($jenis == 'HasilFoto') {
    // REVISI: Menggunakan filter tanggal berdasarkan tanggalUpload atau tanggal jadwal
    // Disini menggunakan tanggalUpload di tabel hasilfoto agar akurat kapan foto selesai
    $query = "SELECT h.orderId, p.namaPelanggan, pk.namaPaket, j.tanggal as tanggalPotret, h.linkDrive, h.tanggalUpload 
              FROM hasilfoto h 
              JOIN pemesanan p ON h.orderId=p.orderId 
              JOIN paketlayanan pk ON p.paketId=pk.paketId 
              JOIN jadwal j ON h.orderId=j.orderId
              WHERE h.tanggalUpload BETWEEN '$start_date' AND '$end_date' 
              ORDER BY h.tanggalUpload DESC";

} elseif ($jenis == 'Paket') {
    $query = "SELECT * FROM paketlayanan ORDER BY namaPaket ASC";
    $label_periode = "Semua Paket Aktif";
}

if (isset($query)) {
    $result = $koneksi->query($query);
    while ($row = $result->fetch_assoc()) {
        $data_laporan[] = $row;
        if (isset($row['totalHarga'])) $total_grand += $row['totalHarga'];
        if (isset($row['jumlahBayar'])) $total_grand += $row['jumlahBayar'];
        if (isset($row['total_nilai'])) $total_grand += $row['total_nilai'];
        if (isset($row['jumlah_transaksi'])) $total_qty += $row['jumlah_transaksi'];
    }
}

function bulanIndo($angkaBulan) {
    $bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    return $bulan[$angkaBulan] ?? $angkaBulan;
}

// --- LOGIKA DOWNLOAD EXCEL ---
if ($is_excel) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_{$jenis}_{$satuan}_" . date('Ymd') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "<style>
        table { width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; }
        th, td { border: 1px solid black; padding: 8px; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan - <?php echo $jenis; ?></title>
    
    <?php if (!$is_excel): ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- TEMA AWAL (LIGHT PURPLE GRADIENT) --- */
        :root {
            --primary: #6A5ACD;
            --text-dark: #333;
            --text-gray: #666;
            --white: #ffffff;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; padding: 0; 
            background: linear-gradient(-45deg, #e3eeff, #f3e7e9, #e8dbfc, #f5f7fa);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: var(--text-dark); min-height: 100vh; 
        }
        @keyframes gradientBG { 0% {background-position:0% 50%} 50% {background-position:100% 50%} 100% {background-position:0% 50%} }

        /* NAVBAR */
        .top-nav { 
            background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); 
            height: 70px; padding: 0 40px; display: flex; align-items: center; 
            position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 15px rgba(0,0,0,0.05); 
        }
        .logo-nav { display: flex; align-items: center; gap: 10px; width: 250px; font-weight: 700; color: var(--primary); font-size: 18px; }
        .logo-circle { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover; }
        .nav-links a { text-decoration: none; color: #555; font-weight: 500; font-size: 14px; margin-right: 15px; padding: 8px 12px; transition: 0.3s; border-radius: 6px; }
        .nav-links a:hover, .nav-links .active-link { color: var(--primary); background: #f0f0ff; font-weight: 600; }
        .user-menu { margin-left: auto; font-weight: 600; font-size: 14px; color: #333; }

        /* TAB NAVIGASI */
        .report-nav { max-width: 1100px; margin: 30px auto 20px auto; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .tab-btn { 
            background: #fff; color: #666; padding: 10px 20px; border-radius: 50px; text-decoration: none; 
            font-weight: 600; font-size: 13px; transition: 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex; align-items: center; gap: 8px; border: 1px solid #eee;
        }
        .tab-btn:hover { background: #f9f9f9; color: var(--primary); transform: translateY(-2px); }
        .tab-btn.active { 
            background: linear-gradient(135deg, #6A5ACD 0%, #9370DB 100%); 
            color: white; border-color: transparent; box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3); 
        }

        /* FILTER AREA */
        .filter-container { 
            max-width: 1100px; margin: 0 auto 30px auto; background: rgba(255,255,255,0.9); 
            padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; border: 1px solid #fff;
        }
        .filter-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-control { 
            padding: 8px 12px; background: #fff; border: 1px solid #ccc; 
            border-radius: 6px; color: #333; font-family: 'Poppins'; 
        }
        .form-control:focus { outline: none; border-color: var(--primary); }
        
        .btn-show { background: var(--primary); color: #fff; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; box-shadow: 0 3px 10px rgba(106, 90, 205, 0.2); transition: 0.3s; }
        .btn-show:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(106, 90, 205, 0.3); }
        
        .btn-print { background: #333; color: #fff; text-decoration: none; padding: 8px 20px; border-radius: 50px; font-size: 13px; font-weight: bold; display: flex; align-items: center; gap: 5px; transition: 0.3s; }
        .btn-print:hover { background: #000; transform: translateY(-2px); }
        
        .btn-excel { background: #28a745; color: #fff; text-decoration: none; padding: 8px 20px; border-radius: 50px; font-size: 13px; font-weight: bold; display: flex; align-items: center; gap: 5px; transition: 0.3s; }
        .btn-excel:hover { background: #218838; transform: translateY(-2px); }

        .checkbox-wrapper { display: flex; align-items: center; gap: 5px; color: #555; font-size: 13px; cursor: pointer; background: #fff; padding: 5px 10px; border-radius: 5px; border: 1px solid #ddd; }
        
        .d-none { display: none; } .d-flex { display: flex; align-items: center; gap: 10px; }

        /* PRINT VIEW (KERTAS PUTIH) */
        .paper-view { background: white; width: 210mm; min-height: 297mm; margin: 0 auto 50px auto; padding: 15mm 20mm; box-shadow: 0 5px 30px rgba(0,0,0,0.1); color: #000; font-family: 'Times New Roman', Times, serif; box-sizing: border-box; }
        .report-header { text-align: center; border-bottom: 2px dashed #000; border-top: 2px dashed #000; padding: 15px 0; margin-bottom: 25px; display: flex; justify-content: center; align-items: center; gap: 20px; }
        .header-logo img { width: 70px; height: 70px; border: 1px solid #000; border-radius: 50%; padding: 2px; }
        .header-text h1 { margin: 0; font-size: 18px; text-transform: uppercase; font-weight: bold; }
        .header-text p { margin: 2px 0; font-size: 12px; }
        .report-title { text-align: center; margin-bottom: 30px; }
        .report-title h2 { margin: 0; font-size: 16px; text-decoration: underline; text-transform: uppercase; }
        .report-title p { margin: 5px 0; font-size: 12px; font-style: italic; }
        .report-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 20px; }
        .report-table th, .report-table td { border: 1px solid #000; padding: 8px; color: #000; }
        .report-table th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
        .text-center { text-align: center; } .text-right { text-align: right; }
        .link-text { color: blue; text-decoration: underline; word-break: break-all; font-size: 11px; }
        .report-footer { margin-top: 50px; font-size: 12px; page-break-inside: avoid; }

        @media print { 
            body { background: white; margin: 0; } 
            .top-nav, .report-nav, .filter-container { display: none !important; } 
            .paper-view { box-shadow: none; margin: 0; width: 100%; padding: 0; border: none; } 
            @page { size: A4; margin: 20mm; } 
        }
    </style>
    <?php endif; ?>
</head>
<body>

    <?php if (!$is_excel): ?>
    <div class="top-nav">
        <div class="logo-nav"><img src="foto/logo.jpg" alt="Logo" class="logo-circle"><span class="brand-text">STUDIO FOTO</span></div>
        <div class="nav-links">
            <a href="<?php echo ($_SESSION['role'] == 'Admin') ? 'dashboard_admin.php' : 'dashboard_owner.php'; ?>">Dashboard</a>
             <a href="admin_paket.php">paket</a>
            <a href="verifikasi_pemesanan.php">Pemesanan</a>
            <a href="verifikasi_pembayaran.php">pembayaran</a>
            <a href="jadwal.php">Jadwal</a>
            <a href="kelola_galeri.php">galeri</a>
            <a href="laporan.php" class="active-link">Laporan</a>
        </div>
        <div class="user-menu"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_fullname); ?></div>
    </div>

    <div class="report-nav">
        <a href="?jenis=Pendapatan&satuan=<?php echo $satuan; ?>" class="tab-btn <?php echo ($jenis == 'Pendapatan') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Pendapatan</a>
        <a href="?jenis=Pemesanan&satuan=<?php echo $satuan; ?>" class="tab-btn <?php echo ($jenis == 'Pemesanan') ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Pemesanan</a>
        <a href="?jenis=Pembayaran&satuan=<?php echo $satuan; ?>" class="tab-btn <?php echo ($jenis == 'Pembayaran') ? 'active' : ''; ?>"><i class="fas fa-money-bill-wave"></i> Pembayaran</a>
        <a href="?jenis=HasilFoto&satuan=Harian" class="tab-btn <?php echo ($jenis == 'HasilFoto') ? 'active' : ''; ?>"><i class="fas fa-camera"></i> Hasil Foto</a>
        <a href="?jenis=Paket" class="tab-btn <?php echo ($jenis == 'Paket') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Paket</a>
    </div>

    <div class="filter-container">
        <form method="GET" class="filter-form">
            <input type="hidden" name="jenis" value="<?php echo $jenis; ?>">
            
            <?php 
            // PERBAIKAN: Filter Mode (Harian/Bulanan/Tahunan) Tampil juga untuk HasilFoto (kecuali Paket)
            if ($jenis != 'Paket'): 
            ?>
                <label style="color:#666; font-size:12px;">Mode:</label>
                <select name="satuan" class="form-control" id="selectSatuan" onchange="toggleFilter()">
                    <option value="Harian" <?php echo ($satuan == 'Harian') ? 'selected' : ''; ?>>Rincian Harian</option>
                    <option value="Bulanan" <?php echo ($satuan == 'Bulanan') ? 'selected' : ''; ?>>Rekap Bulanan</option>
                    <option value="Tahunan" <?php echo ($satuan == 'Tahunan') ? 'selected' : ''; ?>>Rekap Tahunan</option>
                </select>

                <div id="filterHarian" class="<?php echo ($satuan == 'Harian') ? 'd-flex' : 'd-none'; ?>">
                    <input type="date" name="start_date" class="form-control" value="<?php echo $_GET['start_date'] ?? date('Y-m-01'); ?>">
                    <span style="color:#555;">-</span>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $_GET['end_date'] ?? date('Y-m-d'); ?>">
                </div>

                <div id="filterBulanan" class="<?php echo ($satuan == 'Bulanan') ? 'd-flex' : 'd-none'; ?>">
                    <input type="month" name="bulan" class="form-control" value="<?php echo $_GET['bulan'] ?? date('Y-m'); ?>">
                </div>

                <div id="filterTahunan" class="<?php echo ($satuan == 'Tahunan') ? 'd-flex' : 'd-none'; ?>">
                    <select name="tahun" class="form-control">
                        <?php 
                        $curYear = date('Y');
                        for($i = $curYear; $i >= $curYear - 5; $i--) {
                            $sel = (isset($_GET['tahun']) && $_GET['tahun'] == $i) ? 'selected' : '';
                            echo "<option value='$i' $sel>$i</option>";
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if ($jenis == 'HasilFoto'): ?>
                <label class="checkbox-wrapper">
                    <input type="checkbox" name="with_link" value="1" <?php echo $with_link ? 'checked' : ''; ?>> Sertakan Link
                </label>
            <?php endif; ?>

            <button type="submit" class="btn-show">Tampilkan</button>
        </form>
        
        <?php if(!empty($data_laporan)): ?>
            <div style="display:flex; gap:5px;">
                <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Cetak PDF</button>
                <?php 
                    $params = $_GET;
                    $params['export'] = 'excel';
                    $excel_link = '?' . http_build_query($params);
                ?>
                <a href="<?php echo $excel_link; ?>" target="_blank" class="btn-excel"><i class="fas fa-file-excel"></i> Excel</a>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="paper-view">
        <?php if(!$is_excel): ?>
        <div class="report-header">
            <div class="header-logo"><img src="foto/logo.jpg" alt="LOGO"></div>
            <div class="header-text">
                <h1>STUDIO FOTO ISTORE</h1>
                <p>Jl. Fotografi No. 123, Juwana, Jawa Tengah</p>
                <p>Telepon: 0812-3456-7890 | IG: @namastudio</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="report-title">
            <h2>LAPORAN <?php echo strtoupper(str_replace('HasilFoto', 'Hasil Pemotretan', $jenis)); ?></h2>
            <p><?php echo ($jenis != 'Paket') ? "Periode: $label_periode" : "Daftar Paket Aktif per " . date('d F Y'); ?></p>
            <?php if($satuan != 'Harian' && $jenis != 'Paket' && $jenis != 'HasilFoto'): ?>
                <p style="font-weight:bold; font-size:12px;">(REKAPITULASI <?php echo strtoupper($satuan); ?>)</p>
            <?php endif; ?>
        </div>

        <?php if (empty($data_laporan)): ?>
            <div style="text-align:center; padding:50px; border:1px solid #000; font-size:14px;">TIDAK ADA DATA</div>
        <?php else: ?>
            <table class="report-table" border="<?php echo $is_excel ? '1' : '0'; ?>">
                
                <?php if (($satuan == 'Bulanan' || $satuan == 'Tahunan') && ($jenis != 'Paket' && $jenis != 'HasilFoto')): ?>
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th><?php echo ($satuan == 'Bulanan') ? 'Tanggal' : 'Bulan'; ?></th>
                            <th class="text-right">Jumlah Transaksi</th>
                            <th class="text-right">Total Nilai (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach($data_laporan as $r): 
                            $label = ($satuan == 'Bulanan') ? date('d F Y', strtotime($r['tgl'])) : bulanIndo($r['bln']);
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td><?php echo $label; ?></td>
                            <td class="text-right"><?php echo $r['jumlah_transaksi']; ?> Order</td>
                            <td class="text-right"><?php echo $is_excel ? $r['total_nilai'] : 'Rp '.number_format($r['total_nilai'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background:#eee; font-weight:bold;">
                            <td colspan="2" class="text-right">TOTAL KESELURUHAN</td>
                            <td class="text-right"><?php echo $total_qty; ?> Order</td>
                            <td class="text-right"><?php echo $is_excel ? $total_grand : 'Rp '.number_format($total_grand, 0, ',', '.'); ?></td>
                        </tr>
                    </tbody>

                <?php else: ?>
                    
                    <?php if ($jenis == 'Pendapatan' || $jenis == 'Pemesanan'): ?>
                        <thead><tr><th>No</th><th>ID Order</th><th>Tgl Pesan</th><th>Nama</th><th>Paket</th><th>Status</th><?php if($jenis=='Pendapatan') echo '<th>Pendapatan</th>'; ?></tr></thead>
                        <tbody><?php $no=1; foreach($data_laporan as $r): ?><tr><td class="text-center"><?php echo $no++; ?></td><td><?php echo $r['orderId']; ?></td><td class="text-center"><?php echo date('d/m/Y', strtotime($r['tanggalPesan'])); ?></td><td><?php echo htmlspecialchars($r['namaPelanggan']); ?></td><td><?php echo htmlspecialchars($r['namaPaket']); ?></td><td class="text-center"><?php echo $r['statusPesanan']; ?></td><?php if($jenis=='Pendapatan') echo '<td class="text-right">'. ($is_excel ? $r['totalHarga'] : 'Rp '.number_format($r['totalHarga'],0,',','.')) .'</td>'; ?></tr><?php endforeach; ?>
                        <?php if($jenis=='Pendapatan'): ?><tr style="background:#eee; font-weight:bold;"><td colspan="6" class="text-right">TOTAL PENDAPATAN</td><td class="text-right"><?php echo $is_excel ? $total_grand : 'Rp '.number_format($total_grand,0,',','.'); ?></td></tr><?php endif; ?></tbody>

                    <?php elseif ($jenis == 'Pembayaran'): ?>
                        <thead><tr><th>No</th><th>ID Bayar</th><th>ID Order</th><th>Tgl Bayar</th><th>Metode</th><th>Jumlah</th><th>Status</th></tr></thead>
                        <tbody><?php $no=1; foreach($data_laporan as $r): ?><tr><td class="text-center"><?php echo $no++; ?></td><td><?php echo $r['pembayaranId']; ?></td><td><?php echo $r['orderId']; ?></td><td class="text-center"><?php echo date('d/m/Y', strtotime($r['tanggalBayar'])); ?></td><td><?php echo $r['metode']; ?></td><td class="text-right"><?php echo $is_excel ? $r['jumlahBayar'] : 'Rp '.number_format($r['jumlahBayar'],0,',','.'); ?></td><td class="text-center"><?php echo $r['statusBayar']; ?></td></tr><?php endforeach; ?></tbody>

                    <?php elseif ($jenis == 'HasilFoto'): ?>
                        <thead>
                            <tr>
                                <th>No</th><th>ID Pesanan</th><th>Nama</th><th>Paket</th><th>Tgl Potret</th>
                                <?php if($with_link) echo '<th>Link Drive</th>'; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach($data_laporan as $r): ?>
                            <tr>
                                <td class="text-center"><?php echo $no++; ?></td>
                                <td><?php echo $r['orderId']; ?></td>
                                <td><?php echo $r['namaPelanggan']; ?></td>
                                <td><?php echo $r['namaPaket']; ?></td>
                                <td class="text-center"><?php echo date('d/m/Y', strtotime($r['tanggalPotret'])); ?></td>
                                <?php if($with_link): ?>
                                    <td>
                                        <?php 
                                            if ($is_excel) {
                                                echo $r['linkDrive']; // Mode Excel: Teks
                                            } else {
                                                echo "<a href='".$r['linkDrive']."' target='_blank' style='color:blue; text-decoration:underline;'>Buka Link</a>"; // Mode Web: Link
                                            }
                                        ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>

                    <?php elseif ($jenis == 'Paket'): ?>
                        <thead><tr><th>No</th><th>Nama Paket</th><th>Kategori</th><th>Harga</th><th>Deskripsi</th></tr></thead>
                        <tbody><?php $no=1; foreach($data_laporan as $r): ?><tr><td class="text-center"><?php echo $no++; ?></td><td><?php echo $r['namaPaket']; ?></td><td class="text-center"><?php echo $r['kategori']; ?></td><td class="text-right"><?php echo $is_excel ? $r['harga'] : 'Rp '.number_format($r['harga'],0,',','.'); ?></td><td><?php echo $r['deskripsi']; ?></td></tr><?php endforeach; ?></tbody>
                    
                    <?php endif; ?>

                <?php endif; ?>
            </table>
        <?php endif; ?>

        <?php if(!$is_excel): ?>
        <div class="report-footer">
            <div class="footer-row">Dicetak oleh : <?php echo htmlspecialchars($user_fullname); ?></div>
            <div class="footer-row">Tanggal : <?php echo date('d F Y, H:i'); ?> WIB</div>
        </div>
        <?php endif; ?>
    </div>

    <?php if(!$is_excel): ?>
    <script>
        function toggleFilter() {
            var s = document.getElementById('selectSatuan').value;
            document.getElementById('filterHarian').className  = (s === 'Harian') ? 'd-flex' : 'd-none';
            document.getElementById('filterBulanan').className = (s === 'Bulanan') ? 'd-flex' : 'd-none';
            document.getElementById('filterTahunan').className = (s === 'Tahunan') ? 'd-flex' : 'd-none';
        }
    </script>
    <?php endif; ?>
</body>
</html>