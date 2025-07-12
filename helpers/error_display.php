<?php
// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fungsi untuk menangani error
function displayErrorHandler($errno, $errstr, $errfile, $errline)
{
    // Ubah lokasi file log ke direktori tmp yang biasanya dapat diakses
    $log_dir = sys_get_temp_dir();
    $error_message = date('Y-m-d H:i:s') . " Error [$errno]: $errstr in $errfile on line $errline\n";

    // Coba tulis ke log file, jika gagal, abaikan saja (gunakan @ untuk menekan error)
    @error_log($error_message, 3, $log_dir . "/antrian_pasien_error.log");

    if (!(error_reporting() & $errno)) {
        return false;
    }

    switch ($errno) {
        case E_USER_ERROR:
            echo "<b>FATAL ERROR</b><br />\n";
            echo "Error type: [$errno] $errstr<br />\n";
            echo "Fatal error on line $errline in file $errfile<br />\n";
            exit(1);
            break;

        case E_USER_WARNING:
            echo "<b>WARNING</b><br />\n";
            echo "Warning type: [$errno] $errstr<br />\n";
            break;

        case E_USER_NOTICE:
            echo "<b>NOTICE</b><br />\n";
            echo "Notice type: [$errno] $errstr<br />\n";
            break;

        default:
            echo "<b>Unknown error type</b><br />\n";
            echo "Error type: [$errno] $errstr<br />\n";
            break;
    }

    return true;
}

// Set custom error handler
set_error_handler("displayErrorHandler");
