<?php
session_start();

// Cek apakah user sudah login, jika tidak, arahkan kembali ke halaman login
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../public/login.php");
    exit;
}

// Cek peran user, jika bukan mahasiswa, arahkan ke dashboard yang sesuai atau logout
if($_SESSION["role"] !== "mahasiswa"){
    if($_SESSION["role"] == "asisten"){
        header("location: dashboard_asisten.php"); // Arahkan ke dashboard asisten jika asisten
    } else {
        // Jika peran tidak dikenali, atau tidak cocok, bisa logout
        header("location: ../public/logout.php"); // Nanti kita buat logout.php
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center">
        <h1 class="text-xl font-bold">SIMPRAK</h1>
        <div>
            <span class="mr-4">Selamat datang, <?php echo htmlspecialchars($_SESSION["username"]); ?> (Mahasiswa)!</span>
            <a href="../public/logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <h2 class="text-3xl font-bold mb-6">Dashboard Mahasiswa</h2>
        <p class="text-gray-700">Ini adalah halaman utama untuk mahasiswa. Di sini Anda akan dapat melihat mata praktikum yang diikuti, mengunduh materi, mengumpulkan laporan, dan melihat nilai.</p>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Mata Praktikum Saya</h3>
                <p class="text-gray-600">Lihat daftar praktikum yang Anda ikuti.</p>
                <a href="praktikum_saya.php" class="mt-4 inline-block bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">Lihat Praktikum</a>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Mencari Mata Praktikum</h3>
                <p class="text-gray-600">Cari dan daftar mata praktikum baru.</p>
                <a href="../public/katalog_praktikum.php" class="mt-4 inline-block bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded">Cari Praktikum</a>
            </div>
        </div>
    </div>
</body>
</html>