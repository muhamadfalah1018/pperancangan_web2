<html>
<head>
    <title>Method POST proses</title>
</head>
<body>
    <?php
    if(isset($_POST["nama"])) {
        echo "Data nama yang diinputkan adalah : " . $_POST["nama"];
    } else {
        echo "Tidak ada data yang dikirim!";
    }
    ?>
</body>
</html>
