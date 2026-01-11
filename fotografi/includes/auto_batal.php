<?php
// includes/auto_batal.php - FIX: Jangan hapus yang sudah bayar!

if (isset($koneksi)) {
    
    $batas_waktu = date('Y-m-d H:i:s', strtotime('-1 hour'));

    // Query Cerdas: 
    // Hanya pilih pesanan yang 'Menunggu' DAN (Belum ada data pembayaran ATAU Pembayarannya Ditolak)
    // Jika status bayar = 'Menunggu Verifikasi' atau 'Lunas', JANGAN dibatalkan.
    $query_expired = "
        SELECT p.orderId 
        FROM pemesanan p
        LEFT JOIN pembayaran b ON p.orderId = b.orderId
        WHERE p.statusPesanan = 'Menunggu' 
        AND p.tanggalPesan < '$batas_waktu'
        AND (b.pembayaranId IS NULL OR b.statusBayar = 'Ditolak')
    ";

    $result = $koneksi->query($query_expired);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $expiredId = $row['orderId'];

            // Hapus Jadwal
            $koneksi->query("DELETE FROM jadwal WHERE orderId = '$expiredId'");

            // Set Status Batal
            $koneksi->query("UPDATE pemesanan SET statusPesanan = 'Dibatalkan' WHERE orderId = '$expiredId'");
        }
    }
}
?>