<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

function kirimEmail($jenis) {
    date_default_timezone_set("Asia/Jakarta");
    $waktu = date("Y-m-d H:i:s");

    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 2;

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'iniboot0@gmail.com';
        $mail->Password   = 'abcdabcdabcd'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('iniboot0@gmail.com', 'Sistem Login');
        $mail->addAddress('iniboot0@gmail.com');

        $mail->Subject = "ALERT {$waktu}_{$jenis} Autentikasi";
        $mail->Body    = "Login {$jenis} pada {$waktu}";

        $mail->send();
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    }
}
?>
