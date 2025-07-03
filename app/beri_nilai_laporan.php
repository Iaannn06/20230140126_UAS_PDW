<?php
session_start();

// Cek apakah user sudah login dan adalah asisten
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "asisten"){
    header("location: ../public/login.php");
    exit;
}

require_once '../includes/config.php';

$laporan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$laporan_data = null;
$nilai = $feedback = "";
$nilai_err = "";
$flash_message = "";

if ($laporan_id > 0) {
    // Ambil data laporan
    $sql_select_laporan = "
        SELECT
            lp.id AS laporan_id,
            u.nama AS nama_mahasiswa,
            mpk.nama_praktikum,
            md.judul_modul,
            lp.nama_file_laporan,
            lp.tanggal_upload,
            lp.nilai,
            lp.feedback,
            lp.status
        FROM
            laporan_praktikum lp
        JOIN
            pendaftaran_praktikum pp ON lp.pendaftaran_id = pp.id
        JOIN
            users u ON pp.user_id = u.id
        JOIN
            modul_praktikum md ON lp.modul_id = md.id
        JOIN
            mata_praktikum mpk ON md.mata_praktikum_id = mpk.id
        WHERE
            lp.id = ?
    ";
    if($stmt_select_laporan = mysqli_prepare($link, $sql_select_laporan)){
        mysqli_stmt_bind_param($stmt_select_laporan, "i", $laporan_id);
        if(mysqli_stmt_execute($stmt_select_laporan)){
            $result_laporan = mysqli_stmt_get_result($stmt_select_laporan);
            $laporan_data = mysqli_fetch_assoc($result_laporan);
            if ($laporan_data) {
                $nilai = $laporan_data['nilai'];
                $feedback = $laporan_data['feedback'];
            }
            mysqli_free_result($result_laporan);
        } else {
            $flash_message = "ERROR: Tidak dapat mengambil detail laporan.";
        }
        mysqli_stmt_close($stmt_select_laporan);
    }
}

// Proses submit nilai
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_nilai'])){
    $nilai = trim($_POST["nilai"]);
    $feedback = trim($_POST["feedback"]);

    // Validasi nilai
    if(empty($nilai)){
        $nilai_err = "Mohon masukkan nilai.";
    } elseif (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        $nilai_err = "Nilai harus angka antara 0-100.";
    }

    if (empty($nilai_err) && $laporan_id > 0) {
        $sql_update_nilai = "UPDATE laporan_praktikum SET nilai = ?, feedback = ?, status = 'dinilai' WHERE id = ?";
        if($stmt_update_nilai = mysqli_prepare($link, $sql_update_nilai)){
            mysqli_stmt_bind_param($stmt_update_nilai, "isi", $param_nilai, $param_feedback, $param_laporan_id);
            $param_nilai = $nilai;
            $param_feedback = $feedback;
            $param_laporan_id = $laporan_id;

            if(mysqli_stmt_execute($stmt_update_nilai)){
                $_SESSION['flash_message_laporan_masuk'] = "Nilai dan feedback berhasil disimpan!";
                header("location: laporan_masuk.php");
                exit;
            } else {
                $flash_message = "Error saat menyimpan nilai: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt_update_nilai);
        }
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Nilai Laporan - SIMPRAK Asisten</title>
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
        <h2 class="text-3xl font-bold mb-6">Beri Nilai Laporan</h2>

        <?php if (!empty($flash_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($flash_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($laporan_data): ?>
        <div class="bg-white p-8 rounded-lg shadow-md mb-8">
            <p class="mb-2"><strong>Mahasiswa:</strong> <?php echo htmlspecialchars($laporan_data['nama_mahasiswa']); ?></p>
            <p class="mb-2"><strong>Praktikum:</strong> <?php echo htmlspecialchars($laporan_data['nama_praktikum']); ?></p>
            <p class="mb-2"><strong>Modul:</strong> <?php echo htmlspecialchars($laporan_data['judul_modul']); ?></p>
            <p class="mb-4"><strong>File Laporan:</strong> <a href="../public/laporan_praktikum/<?php echo htmlspecialchars($laporan_data['nama_file_laporan']); ?>" target="_blank" class="text-blue-500 hover:underline">Unduh Laporan</a></p>

            <form action="beri_nilai_laporan.php?id=<?php echo $laporan_id; ?>" method="post">
                <div class="mb-4">
                    <label for="nilai" class="block text-gray-700 text-sm font-bold mb-2">Nilai (0-100):</label>
                    <input type="number" name="nilai" id="nilai" min="0" max="100" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nilai_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($nilai); ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $nilai_err; ?></span>
                </div>
                <div class="mb-6">
                    <label for="feedback" class="block text-gray-700 text-sm font-bold mb-2">Feedback:</label>
                    <textarea name="feedback" id="feedback" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($feedback); ?></textarea>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" name="submit_nilai" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Simpan Nilai</button>
                    <a href="laporan_masuk.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Batal</a>
                </div>
            </form>
        </div>
        <?php else: ?>
            <p class="text-center text-gray-600">Laporan tidak ditemukan atau tidak valid.</p>
            <a href="laporan_masuk.php" class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mt-4">← Kembali ke Laporan Masuk</a>
        <?php endif; ?>
    </div>
</body>
</html>