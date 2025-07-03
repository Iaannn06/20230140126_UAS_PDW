<?php
session_start();

// Cek apakah user sudah login dan adalah mahasiswa
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "mahasiswa"){
    header("location: login.php"); // Arahkan ke login jika tidak valid
    exit;
}

require_once '../includes/config.php';

// Proses pendaftaran ketika form dikirim
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $user_id = $_SESSION["id"];
    $mata_praktikum_id = isset($_POST["mata_praktikum_id"]) ? (int)$_POST["mata_praktikum_id"] : 0;

    if($user_id > 0 && $mata_praktikum_id > 0){
        // Cek apakah mahasiswa sudah terdaftar di praktikum ini
        $sql_check = "SELECT id FROM pendaftaran_praktikum WHERE user_id = ? AND mata_praktikum_id = ?";
        if($stmt_check = mysqli_prepare($link, $sql_check)){
            mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $mata_praktikum_id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);

            if(mysqli_stmt_num_rows($stmt_check) > 0){
                // Sudah terdaftar
                $_SESSION['flash_message'] = "Anda sudah terdaftar di praktikum ini!";
            } else {
                // Belum terdaftar, lakukan INSERT
                $sql_insert = "INSERT INTO pendaftaran_praktikum (user_id, mata_praktikum_id) VALUES (?, ?)";
                if($stmt_insert = mysqli_prepare($link, $sql_insert)){
                    mysqli_stmt_bind_param($stmt_insert, "ii", $user_id, $mata_praktikum_id);
                    if(mysqli_stmt_execute($stmt_insert)){
                        $_SESSION['flash_message'] = "Berhasil mendaftar praktikum!";
                    } else{
                        $_SESSION['flash_message'] = "Error: Tidak dapat mendaftar. " . mysqli_error($link);
                    }
                    mysqli_stmt_close($stmt_insert);
                }
            }
            mysqli_stmt_close($stmt_check);
        }
    } else {
        $_SESSION['flash_message'] = "Praktikum tidak valid.";
    }
    mysqli_close($link);
    header("location: katalog_praktikum.php"); // Kembali ke katalog setelah proses
    exit;
} else {
    // Jika diakses langsung tanpa POST, redirect ke katalog
    header("location: katalog_praktikum.php");
    exit;
}
?>

