<?php
session_start();

// Cek apakah user sudah login dan adalah mahasiswa
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "mahasiswa"){
    header("location: ../public/login.php");
    exit;
}

require_once '../includes/config.php';

$user_id = $_SESSION["id"];
$praktikum_diikuti = [];

// Ambil daftar mata praktikum yang diikuti oleh mahasiswa ini
$sql_select_praktikum = "
    SELECT
        mp.id,
        mp.nama_praktikum,
        mp.deskripsi,
        mp.kode_praktikum,
        pp.status_pendaftaran
    FROM
        pendaftaran_praktikum pp
    JOIN
        mata_praktikum mp ON pp.mata_praktikum_id = mp.id
    WHERE
        pp.user_id = ?
    ORDER BY
        mp.nama_praktikum ASC
";

if($stmt_select_praktikum = mysqli_prepare($link, $sql_select_praktikum)){
    mysqli_stmt_bind_param($stmt_select_praktikum, "i", $user_id);
    if(mysqli_stmt_execute($stmt_select_praktikum)){
        $result_praktikum = mysqli_stmt_get_result($stmt_select_praktikum);
        if(mysqli_num_rows($result_praktikum) > 0){
            while($row = mysqli_fetch_array($result_praktikum)){
                $praktikum_diikuti[] = $row;
            }
            mysqli_free_result($result_praktikum);
        }
    } else {
        echo "ERROR: Tidak dapat mengambil data praktikum. " . mysqli_error($link);
    }
    mysqli_stmt_close($stmt_select_praktikum);
} else {
    echo "ERROR: Terjadi kesalahan pada persiapan query. " . mysqli_error($link);
}

// Tutup koneksi
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Praktikum Saya - SIMPRAK Mahasiswa</title>
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
        <h2 class="text-3xl font-bold mb-6 text-center">Praktikum Saya</h2>

        <?php if(!empty($praktikum_diikuti)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($praktikum_diikuti as $praktikum): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?> (<?php echo htmlspecialchars($praktikum['kode_praktikum']); ?>)</h3>
                <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
                <p class="text-sm text-gray-600 mb-4">Status: <span class="font-bold <?php
                    if($praktikum['status_pendaftaran'] == 'approved') echo 'text-green-600';
                    elseif($praktikum['status_pendaftaran'] == 'pending') echo 'text-yellow-600';
                    else echo 'text-red-600';
                ?>"><?php echo htmlspecialchars(ucfirst($praktikum['status_pendaftaran'])); ?></span></p>
                <a href="detail_praktikum.php?praktikum_id=<?php echo $praktikum['id']; ?>" class="mt-4 inline-block bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">Lihat Detail & Tugas</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="text-center text-gray-600">Anda belum mendaftar di mata praktikum manapun. Kunjungi <a href="../public/katalog_praktikum.php" class="text-blue-500 hover:underline">Katalog Praktikum</a> untuk mendaftar.</p>
        <?php endif; ?>
    </div>
</body>
</html>