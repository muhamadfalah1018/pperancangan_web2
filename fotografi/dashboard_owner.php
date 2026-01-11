<?php
// dashboard_owner.php - FIXED: Vertical Layout + Percentages
session_start();
include('includes/db_koneksi.php');

// 1. CEK AKSES OWNER
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Owner') {
    header("Location: index.php");
    exit();
}

$user_username = $_SESSION['username'];
$owner_name = $_SESSION['name'] ?? 'Owner';

// --- FILTER TANGGAL (Default: Bulan Ini) ---
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-t');

// --- 1. DATA PENDAPATAN (Line Chart) ---
$sql_income = "SELECT DATE(tanggalBayar) as tgl, SUM(jumlahBayar) as total 
               FROM pembayaran 
               WHERE tanggalBayar BETWEEN '$start_date' AND '$end_date' 
               AND statusBayar IN ('Lunas', 'DP 50%', 'DP 25%')
               GROUP BY tgl ORDER BY tgl ASC";
$res_income = $koneksi->query($sql_income);

$label_income = [];
$data_income = [];
$total_period_income = 0;

while($row = $res_income->fetch_assoc()) {
    $label_income[] = date('d M', strtotime($row['tgl']));
    $data_income[] = (int)$row['total'];
    $total_period_income += $row['total'];
}

// --- 2. DATA PAKET TERLARIS (Bar Chart) ---
$sql_paket = "SELECT pk.namaPaket, COUNT(p.orderId) as jumlah 
              FROM pemesanan p 
              JOIN paketlayanan pk ON p.paketId = pk.paketId 
              WHERE p.tanggalPesan BETWEEN '$start_date' AND '$end_date'
              GROUP BY pk.namaPaket ORDER BY jumlah DESC LIMIT 5";
$res_paket = $koneksi->query($sql_paket);

$label_paket = [];
$data_paket = [];

while($row = $res_paket->fetch_assoc()) {
    $label_paket[] = $row['namaPaket'];
    $data_paket[] = (int)$row['jumlah'];
}

// --- 3. DATA STATUS AKSES (Doughnut Chart) ---
$sql_akses = "SELECT statusAkses, COUNT(*) as jumlah FROM hasilfoto GROUP BY statusAkses";
$res_akses = $koneksi->query($sql_akses);

$label_akses = [];
$data_akses = [];

while($row = $res_akses->fetch_assoc()) {
    $status_label = $row['statusAkses'] ? $row['statusAkses'] : 'Aktif';
    $label_akses[] = $status_label;
    $data_akses[] = (int)$row['jumlah'];
}

// --- 4. DATA STATUS PEMBAYARAN (Pie Chart) ---
$sql_bayar = "SELECT statusBayar, COUNT(*) as jumlah FROM pembayaran 
              WHERE tanggalBayar BETWEEN '$start_date' AND '$end_date'
              GROUP BY statusBayar";
$res_bayar = $koneksi->query($sql_bayar);

$label_bayar = [];
$data_bayar = [];

while($row = $res_bayar->fetch_assoc()) {
    $label_bayar[] = $row['statusBayar'];
    $data_bayar[] = (int)$row['jumlah'];
}

// Konversi ke JSON
$json_label_income = json_encode($label_income);
$json_data_income = json_encode($data_income);
$json_label_paket = json_encode($label_paket);
$json_data_paket = json_encode($data_paket);
$json_label_akses = json_encode($label_akses);
$json_data_akses = json_encode($data_akses);
$json_label_bayar = json_encode($label_bayar);
$json_data_bayar = json_encode($data_bayar);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Owner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- STYLE GLOBAL --- */
        :root { --primary: #6A5ACD; --text-dark: #333; --text-gray: #666; --white: #ffffff; }
        
        body { 
            font-family: 'Poppins', sans-serif; margin: 0; padding: 0; min-height: 100vh;
            color: var(--text-dark); overflow-x: hidden;
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
        .user-menu { margin-left: auto; font-weight: 600; color: #333; display: flex; align-items: center; gap: 10px; }
        .logout-btn { color: #dc3545; text-decoration: none; font-size: 14px; padding: 5px 10px; border: 1px solid #dc3545; border-radius: 20px; transition: 0.3s; }
        .logout-btn:hover { background: #dc3545; color: #fff; }

        /* CONTENT */
        .content { max-width: 900px; margin: 40px auto; padding: 0 20px; } /* Lebar diperkecil agar rapi */
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h2 { margin: 0; font-weight: 700; color: var(--text-dark); }
        .page-title p { margin: 5px 0 0; color: var(--text-gray); font-size: 14px; }

        /* FILTER BOX */
        .filter-box { background: #fff; padding: 15px 25px; border-radius: 15px; display: flex; gap: 15px; align-items: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 30px; flex-wrap: wrap; }
        .date-input { padding: 8px 15px; border: 1px solid #ddd; border-radius: 5px; font-family: 'Poppins'; }
        .btn-filter { background: var(--primary); color: #fff; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 5px 10px rgba(106, 90, 205, 0.3); }

        /* LAYOUT VERTIKAL (1 Kolom) */
        .dashboard-stack { 
            display: flex; 
            flex-direction: column; 
            gap: 30px; 
        }
        
        /* CHART CARD */
        .chart-card { 
            background: #fff; 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 5px 25px rgba(0,0,0,0.05); 
            border: 1px solid rgba(0,0,0,0.02);
            transition: 0.3s;
        }
        .chart-card:hover { transform: translateY(-3px); box-shadow: 0 10px 35px rgba(0,0,0,0.08); }
        
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f8f9fa; padding-bottom: 15px; }
        .chart-title { font-size: 18px; font-weight: 700; color: #333; display: flex; align-items: center; gap: 10px; }
        .chart-total { font-size: 16px; font-weight: 700; color: var(--primary); background: rgba(106,90,205,0.1); padding: 5px 15px; border-radius: 50px; }

        /* Canvas Wrapper untuk Kontrol Ukuran */
        .chart-container-large { position: relative; height: 350px; width: 100%; }
        .chart-container-small { position: relative; height: 300px; width: 100%; display: flex; justify-content: center; }
    </style>
</head>
<body>

    <div class="top-nav">
        <div class="logo-nav">
            <img src="foto/logo.jpg" alt="Logo" class="logo-circle">
            <span class="brand-text">ENEMATIKA</span>
        </div>
        <div class="nav-links">
            <a href="dashboard_owner.php" class="active-link">Dashboard Rincian</a>
            <a href="laporan_owner.php">Laporan</a>
        </div>
        <div class="user-menu">
            <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($owner_name); ?>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="content">
        <div class="page-header">
            <div class="page-title">
                <h2>Analisis Bisnis & Rincian</h2>
                <p>Pantau performa studio foto Anda secara visual.</p>
            </div>
        </div>

        <form method="GET" class="filter-box">
            <span style="font-weight:600; color:#555;">Periode:</span>
            <input type="date" name="start" value="<?php echo $start_date; ?>" class="date-input">
            <span>s/d</span>
            <input type="date" name="end" value="<?php echo $end_date; ?>" class="date-input">
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Terapkan</button>
        </form>

        <div class="dashboard-stack">
            
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-chart-line" style="color:#28a745;"></i> Tren Pendapatan Harian</div>
                    <div class="chart-total">Total: Rp <?php echo number_format($total_period_income, 0, ',', '.'); ?></div>
                </div>
                <div class="chart-container-large">
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-trophy" style="color:#ffc107;"></i> Paket Terlaris</div>
                </div>
                <div class="chart-container-large">
                    <canvas id="packageChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-unlock-alt" style="color:#17a2b8;"></i> Status Akses Foto (Persentase)</div>
                </div>
                <div class="chart-container-small">
                    <canvas id="accessChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-money-bill-wave" style="color:#6A5ACD;"></i> Komposisi Pembayaran (Persentase)</div>
                </div>
                <div class="chart-container-small">
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Registrasi Plugin Datalabels
        Chart.register(ChartDataLabels);

        // Data dari PHP
        const incomeLabels = <?php echo $json_label_income; ?>;
        const incomeData = <?php echo $json_data_income; ?>;
        const packageLabels = <?php echo $json_label_paket; ?>;
        const packageData = <?php echo $json_data_paket; ?>;
        const accessLabels = <?php echo $json_label_akses; ?>;
        const accessData = <?php echo $json_data_akses; ?>;
        const paymentLabels = <?php echo $json_label_bayar; ?>;
        const paymentData = <?php echo $json_data_bayar; ?>;

        // Opsi Umum untuk Datalabels (Persentase)
        const percentageOptions = {
            color: '#fff',
            font: { weight: 'bold', size: 12 },
            formatter: (value, ctx) => {
                let sum = 0;
                let dataArr = ctx.chart.data.datasets[0].data;
                dataArr.map(data => { sum += data; });
                let percentage = (value * 100 / sum).toFixed(1) + "%";
                return percentage; // Tampilkan hanya persentase
            }
        };

        // 1. INCOME CHART
        new Chart(document.getElementById('incomeChart'), {
            type: 'line',
            data: {
                labels: incomeLabels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: incomeData,
                    borderColor: '#6A5ACD',
                    backgroundColor: 'rgba(106, 90, 205, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: { display: false }, // Jangan tampilkan persentase di line chart
                    legend: { display: false }
                },
                scales: { y: { beginAtZero: true } }
            }
        });

        // 2. PACKAGE CHART (Horizontal Bar + Value Label)
        new Chart(document.getElementById('packageChart'), {
            type: 'bar',
            data: {
                labels: packageLabels,
                datasets: [{
                    label: 'Pesanan',
                    data: packageData,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderRadius: 5
                }]
            },
            options: {
                indexAxis: 'y', // Horizontal
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#333',
                        font: { weight: 'bold' },
                        formatter: (value) => value + ' Pesanan'
                    }
                }
            }
        });

        // 3. ACCESS CHART (Doughnut + Percentage)
        new Chart(document.getElementById('accessChart'), {
            type: 'doughnut',
            data: {
                labels: accessLabels,
                datasets: [{
                    data: accessData,
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#17a2b8'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: percentageOptions,
                    legend: { position: 'bottom' }
                }
            }
        });

        // 4. PAYMENT CHART (Pie + Percentage)
        new Chart(document.getElementById('paymentChart'), {
            type: 'pie',
            data: {
                labels: paymentLabels,
                datasets: [{
                    data: paymentData,
                    backgroundColor: ['#6A5ACD', '#ff6b6b', '#feca57', '#48dbfb'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: percentageOptions,
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>

</body>
</html>