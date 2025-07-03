<?php
session_start();
require_once '../includes/config.php';

// Ambil pesan flash jika ada
$flash_message = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); // Hapus pesan setelah ditampilkan
}

// Ambil semua data mata praktikum untuk ditampilkan
$mata_praktikum_list = [];
$sql_select = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
if($result = mysqli_query($link, $sql_select)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_array($result)){
            $mata_praktikum_list[] = $row;
        }
        mysqli_free_result($result);
    }
} else{
    echo "ERROR: Tidak dapat mengambil data mata praktikum. " . mysqli_error($link);
}

// Tutup koneksi
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Praktikum - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center">
        <h1 class="text-xl font-bold">SIMPRAK</h1>
        <div>
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <span class="mr-4">Halo, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
                <?php if($_SESSION["role"] == "mahasiswa"): ?>
                    <a href="../app/dashboard_mahasiswa.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">Dashboard Mahasiswa</a>
                <?php elseif($_SESSION["role"] == "asisten"): ?>
                    <a href="../app/dashboard_asisten.php" class="bg-blue-700 hover:bg-blue-900 text-white font-bold py-2 px-4 rounded mr-2">Dashboard Asisten</a>
                <?php endif; ?>
                <a href="logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Logout</a>
            <?php else: ?>
                <a href="login.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">Login</a>
                <a href="register.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <?php if (!empty($flash_message)): ?>
        <div class="container mx-auto mt-4 px-6">
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                <?php echo htmlspecialchars($flash_message); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="container mx-auto p-6">
        <h2 class="text-3xl font-bold mb-6 text-center">Katalog Mata Praktikum</h2>

        <?php if(!empty($mata_praktikum_list)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($mata_praktikum_list as $praktikum): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?> (<?php echo htmlspecialchars($praktikum['kode_praktikum']); ?>)</h3>
                <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] == "mahasiswa"): ?>
                    <form action="daftar_praktikum.php" method="post">
                        <input type="hidden" name="mata_praktikum_id" value="<?php echo $praktikum['id']; ?>">
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Daftar Praktikum</button>
                    </form>
                <?php elseif(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] == "asisten"): ?>
                    <p class="text-sm text-gray-500">Anda login sebagai Asisten.</p>
                <?php else: ?>
                    <p class="text-sm text-gray-500">Login sebagai Mahasiswa untuk mendaftar.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="text-center text-gray-600">Belum ada mata praktikum yang tersedia. Silakan hubungi admin.</p>
        <?php endif; ?>
    </div>
</body>
</html>