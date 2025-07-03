<?php
// Konfigurasi Database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Biasanya 'root' untuk XAMPP default
define('DB_PASSWORD', '');     // Kosongkan jika tidak ada password (default XAMPP)
define('DB_NAME', 'simbrak_db'); // Nama database yang sudah Anda buat

// Membuat koneksi ke database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Mengecek koneksi
if($link === false){
    die("ERROR: Tidak bisa terkoneksi ke database. " . mysqli_connect_error());
}
?>