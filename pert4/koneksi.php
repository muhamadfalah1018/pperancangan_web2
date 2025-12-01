<?php
    $host="localhost";
    $user="root";
    $password="";
    $database="input";

    $con= new mysqli($host,$user,$password,$database);
 //koneksi
    if($con->connect_error){
        die("koneksi gagal : ". $con->connect_error);
    }
//ambil data dari form
    $username= $_POST['username'];
    $email= $_POST['email'];
//query untuk simpan data ke tabel
$sql="insert into users (username, email, password) values('$username','$email','$password')";
if ($con->query($sql)===true){
    echo"Data berhasil di simpan ! <br>";
    echo"<a href='form_user.html'>tambah user baru </a>";
    }
    else{
        echo"error:".sql."<br>".$con->error;
    }
    //menutup koneksi
    $con->close();
?>
