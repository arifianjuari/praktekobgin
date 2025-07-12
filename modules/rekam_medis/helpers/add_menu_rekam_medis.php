<?php
require_once 'config/database.php';

try {
    // Buat tabel menu jika belum ada
    $sql = "CREATE TABLE IF NOT EXISTS user_menu (
        id INT PRIMARY KEY AUTO_INCREMENT,
        menu VARCHAR(50) NOT NULL,
        icon VARCHAR(50) NOT NULL,
        sequence INT NOT NULL DEFAULT 0
    )";
    $conn->exec($sql);
    echo "Tabel user_menu berhasil dibuat<br>";

    // Buat tabel submenu jika belum ada
    $sql = "CREATE TABLE IF NOT EXISTS user_sub_menu (
        id INT PRIMARY KEY AUTO_INCREMENT,
        menu_id INT NOT NULL,
        title VARCHAR(50) NOT NULL,
        url VARCHAR(100) NOT NULL,
        icon VARCHAR(50) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sequence INT NOT NULL DEFAULT 0,
        FOREIGN KEY (menu_id) REFERENCES user_menu(id)
    )";
    $conn->exec($sql);
    echo "Tabel user_sub_menu berhasil dibuat<br>";

    // Insert menu Pelayanan jika belum ada
    $sql = "INSERT IGNORE INTO user_menu (menu, icon, sequence) 
            VALUES ('Pelayanan', 'fas fa-hospital', 2)";
    $conn->exec($sql);
    echo "Menu Pelayanan berhasil ditambahkan<br>";

    // Insert submenu Rekam Medis
    $sql = "INSERT INTO user_sub_menu (menu_id, title, url, icon, sequence) 
            SELECT id, 'Rekam Medis', 'index.php?module=rekam_medis', 'fas fa-notes-medical', 3
            FROM user_menu 
            WHERE menu = 'Pelayanan'";
    $conn->exec($sql);
    echo "Submenu Rekam Medis berhasil ditambahkan<br>";

    echo "Proses selesai!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
