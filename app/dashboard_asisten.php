<?php
session_start();

// Cek apakah user sudah login, jika tidak, arahkan kembali ke halaman login
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../public/login.php");
    exit;
}

// Cek peran user, jika bukan asisten, arahkan ke dashboard yang sesuai atau logout
if($_SESSION["role"] !== "asisten"){
    if($_SESSION["role"] == "mahasiswa"){
        header("location: dashboard_mahasiswa.php"); // Arahkan ke dashboard mahasiswa jika mahasiswa
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
    <title>Dashboard Asisten - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-800 p-4 text-white flex justify-between items-center">
        <h1 class="text-xl font-bold">SIMPRAK (Asisten)</h1>
        <div>
            <span class="mr-4">Selamat datang, <?php echo htmlspecialchars($_SESSION["username"]); ?> (Asisten)!</span>
            <a href="../public/logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <h2 class="text-3xl font-bold mb-6">Dashboard Asisten</h2>
        <p class="text-gray-700">Ini adalah halaman utama untuk asisten. Di sini Anda akan dapat mengelola mata praktikum, modul, dan laporan mahasiswa.</p>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Kelola Mata Praktikum</h3>
                <p class="text-gray-600">Tambahkan, ubah, atau hapus mata praktikum.</p>
                <a href="kelola_mata_praktikum.php" class="mt-4 inline-block bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">Kelola</a>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Kelola Modul</h3>
                <p class="text-gray-600">Tambahkan dan kelola modul untuk mata praktikum.</p>
                <a href="kelola_mata_praktikum.php" class="mt-4 inline-block bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded">Kelola</a>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Lihat Laporan Masuk</h3>
                <p class="text-gray-600">Periksa dan nilai laporan yang dikumpulkan mahasiswa.</p>
                <a href="laporan_masuk.php" class="mt-4 inline-block bg-purple-500 hover:bg-purple-600 text-white py-2 px-4 rounded">Lihat Laporan</a>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Kelola Akun Pengguna</h3>
                <p class="text-gray-600">Tambahkan, ubah, atau hapus akun pengguna.</p>
                <a href="kelola_akun.php" class="mt-4 inline-block bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded">Kelola Akun</a>
            </div>
        </div>
    </div>
</body>
</html>