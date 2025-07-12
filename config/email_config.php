<?php
// Konfigurasi Email
return [
    'smtp_host' => 'smtp.gmail.com', // Ganti sesuai dengan SMTP server Anda
    'smtp_port' => 587,
    'smtp_username' => 'arifianjuari@gmail.com', // Isi dengan email pengirim
    // Gunakan App Password dari Google Account -> Security -> App Passwords
    // JANGAN menggunakan password akun Gmail biasa
    'smtp_password' => 'gpwb pfsd mhxu dfja', // Isi dengan App Password dari Google
    'smtp_secure' => 'tls',
    'from_email' => 'arifianjuari@gmail.com', // Isi dengan email pengirim
    'from_name' => 'praktekobgin',
    'verification_subject' => 'Verifikasi Email - praktekobgin.com',
];
