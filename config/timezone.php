<?php

/**
 * Konfigurasi Zona Waktu
 * 
 * File ini mengatur zona waktu default untuk seluruh aplikasi menjadi GMT+7 (WIB)
 * Impor file ini di awal setiap file PHP yang menggunakan fungsi waktu
 */

// Set zona waktu ke GMT+7 (WIB)
date_default_timezone_set('Asia/Jakarta');
