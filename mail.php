<?php
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 0;                      
    $mail->isSMTP();
    $mail->Host = 'smtp.zoho.eu';
    $mail->SMTPAuth = true;
    $mail->Username = 'mail@mcyigit.net';
    $mail->Password = 'izJqMjp5B2R5';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('mail@mcyigit.net', 'Gonderen Ismi');
    $mail->addAddress('cahityigit0068@gmail.com', 'Alici Ismi');

    $mail->isHTML(true);                                  
    $mail->Subject = 'PHP ile Zoho Mail Testi';
    $mail->Body = 'Merhaba, bu e-posta <b>PHP</b> kullanılarak gönderildi.';
    $mail->AltBody = 'Merhaba, bu e-posta PHP kullanılarak gönderildi. (HTML desteklemeyenler için)';

    $mail->send();
    echo 'Mesaj başarıyla gönderildi!';
} catch (Exception $e) {
    echo "Mesaj gönderilemedi. Hata: {$mail->ErrorInfo}";
}
?>