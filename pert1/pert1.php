<?php
// LATIHAN & TUGAS
echo'<h2> Latihan praktikum </h2>';
echo '';
echo 'Nama  : Muhamad Falahadin <br>';
echo 'Kelas : TI3B <br>';
echo 'NIM   : 2402043 ';

// 1. Pemakaian Variabel
echo "<h3>1. Pemakaian Variabel</h3>";
$nama = "Falah";
$umur = 18;
echo "Nama saya adalah $nama <br>";
echo "Umur saya $umur tahun <br><br>";

// 2. Operator Penugasan
echo "<h3>2. Operator Penugasan</h3>";
$a = 3;
$b = 7;
$a += 5; // sama dengan $a = $a + 5
$b = ($c = 11) + 3;
echo "Nilai variabel a adalah = $a <br>";
echo "Nilai variabel b adalah = $b <br>";
echo "Nilai variabel c adalah = $c <br><br>";

// 3. Operasi Aritmatika
echo "<h3>3. Operasi Aritmatika</h3>";
$a = 10; $b = 3;
echo "\$a + \$b = " . ($a + $b) . "<br>";
echo "\$a - \$b = " . ($a - $b) . "<br>";
echo "\$a * \$b = " . ($a * $b) . "<br>";
echo "\$a / \$b = " . ($a / $b) . "<br>";
echo "\$a % \$b = " . ($a % $b) . "<br><br>";

// 4. Operator Logika
echo "<h3>4. Operator Logika</h3>";
$b = 4!=4;
$c = 3+7 == 10;
$a = ($b and $c); echo "\$a=$a <br>";
$a = ($b or $c);  echo "\$a=$a <br>";
$a = ($b xor $c); echo "\$a=$a <br>";
$a = (!$b or $c); echo "\$a=$a <br>";
$a = $b && $c;    echo "\$a=$a <br>";
$a = $b || $c;    echo "\$a=$a <br><br>";

// 5. Operator Pembandingan
echo "<h3>5. Operator Pembandingan</h3>";
$x = 4;
echo "\$x == 4 → " . ($x == 4) . "<br>";
echo "\$x === '4' → " . ($x === "4") . "<br>";
echo "\$x != 4 → " . ($x != 4) . "<br>";
echo "\$x !== '4' → " . ($x !== "4") . "<br>";
echo "\$x < 5 → " . ($x < 5) . "<br>";
echo "\$x > 5 → " . ($x > 5) . "<br>";
echo "\$x <= 4 → " . ($x <= 4) . "<br>";
echo "\$x >= 5 → " . ($x >= 5) . "<br><br>";

// 6. Struktur Kontrol : If
echo "<h3>6. If</h3>";
$a = 5; $b = 7;
if ($a < $b) {
    echo "\$a lebih kecil daripada \$b<br><br>";
}

// 7. If – Else
echo "<h3>7. If – Else</h3>";
$a = 5; $b = 3;
if ($a < $b) {
    echo "\$a lebih kecil daripada \$b";
} else {
    echo "\$a lebih besar daripada \$b";
}
echo "<br><br>";

// 8. If – Else versi lain
echo "<h3>8. If – Else versi lain</h3>";
$a = 5; $b = 7;
if ($a == $b):
    echo "\$a sama dengan \$b";
elseif ($a > $b):
    echo "\$a lebih besar daripada \$b";
else:
    echo "\$a lebih kecil daripada \$b";
endif;
echo "<br><br>";

// 9. Switch
echo "<h3>9. Switch</h3>";
$a = 5;
switch ($a) {
    case 0: echo "\$a sama dengan 0"; break;
    case 1: echo "\$a sama dengan 1"; break;
    case 2: echo "\$a sama dengan 2"; break;
    default: echo "\$a tidak sama dengan 0, 1, atau 2";
}
echo "<br><br>";

// 10. While
echo "<h3>10. While</h3>";
$i = 1;
while ($i <= 10) {
    echo "$i ";
    $i++;
}
echo "<br><br>";

// 11. Do...While
echo "<h3>11. Do...While</h3>";
$i = 2;
do {
    echo "\$i = $i <br>";
    $i++;
} while ($i < 5);
echo "<br>";

// 12. For
echo "<h3>12. For</h3>";
for ($i = 1; $i <= 10; $i++) {
    echo "$i ";
}
echo "<br><br>";

// 13. Break
echo "<h3>13. Break</h3>";
for ($i = 1; $i <= 10; $i++) {
    if ($i == 6) break;
    echo "\$i = $i <br>";
}
echo "<br>";

// 14. Continue
echo "<h3>14. Continue</h3>";
for ($i = 1; $i <= 10; $i++) {
    if ($i % 2 == 0) continue;
    echo "\$i = $i <br>";
}
echo "<br>";

// TUGAS

echo "<h2>Tugas</h2>";

// a. 4 6 9 13 18 ? ?
echo "<h3>Tugas a</h3>";
$a = 4;
echo "$a ";
for ($i=2; $i<=6; $i++) {
    $a += $i;
    echo "$a ";
}
echo "<br><br>";

// b. 2 2 3 3 4 ? ?
echo "<h3>Tugas b</h3>";
$angka = 2; $count = 0;
for ($i=1; $i<=7; $i++) {
    echo "$angka ";
    $count++;
    if ($count == 2) {
        $angka++;
        $count = 0;
    }
}
echo "<br><br>";

// c. 1 9 2 10 3 ? ?
echo "<h3>Tugas c</h3>";
for ($i=1; $i<=4; $i++) {
    echo $i . " ";
    echo ($i+8) . " ";
}
echo "<br><br>";

?>
