#hitung soal ini <br>
// gaji = 175000 jumlah karyawan = 300 hari kerja = 30
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>quiz</title>
</head>
<body>
    <?php 
        $gaji = 175000 ;
        $j_kariawan = 300;
        $h_kerja = 30;
        $perkariawan = $gaji*$h_kerja;
        $semuakariawan = $gaji*$j_kariawan*$h_kerja;
        echo"total perkariawan = ".$perkariawan."<br>";
        echo"semua kariawan = ".$semuakariawan;
    ?>
    
</body>
</html>