<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($userEmail, $username, $verificationToken)
{
    $config = require __DIR__ . '/../config/email_config.php';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'];
        $mail->Port = $config['smtp_port'];

        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($userEmail, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $config['verification_subject'];

        $verificationLink = "https://www.praktekobgin.com/verify_email.php?token=" . $verificationToken;

        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Verifikasi Email Anda</h2>
                <p>Halo {$username},</p>
                <p>Terima kasih telah mendaftar di Sistem Antrian Pasien kami. Untuk menyelesaikan pendaftaran, silakan klik tombol di bawah ini untuk memverifikasi email Anda:</p>
                <p>
                    <a href='{$verificationLink}' 
                       style='background-color: #4CAF50; 
                              color: white; 
                              padding: 10px 20px; 
                              text-decoration: none; 
                              border-radius: 5px;
                              display: inline-block;'>
                        Verifikasi Email
                    </a>
                </p>
                <p>Atau salin dan tempel link berikut di browser Anda:</p>
                <p>{$verificationLink}</p>
                <p>Link ini akan kadaluarsa dalam 24 jam.</p>
                <p>Jika Anda tidak merasa mendaftar di sistem kami, Anda dapat mengabaikan email ini.</p>
            </body>
            </html>
        ";

        $mail->AltBody = "
            Halo {$username},
            
            Terima kasih telah mendaftar di Sistem Antrian Pasien kami. Untuk menyelesaikan pendaftaran, silakan kunjungi link berikut untuk memverifikasi email Anda:
            
            {$verificationLink}
            
            Link ini akan kadaluarsa dalam 24 jam.
            
            Jika Anda tidak merasa mendaftar di sistem kami, Anda dapat mengabaikan email ini.
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending verification email: " . $mail->ErrorInfo);
        return false;
    }
}
