<?php
session_start();

// Cek apakah user sudah login dan adalah mahasiswa
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "mahasiswa"){
    header("location: ../public/login.php");
    exit;
}

require_once '../includes/config.php';

$praktikum_id = isset($_GET['praktikum_id']) ? (int)$_GET['praktikum_id'] : 0;
$user_id = $_SESSION["id"];
$nama_praktikum = "Praktikum Tidak Ditemukan";
$modul_list = [];
$pendaftaran_id = null; // ID pendaftaran praktikum mahasiswa ini

if ($praktikum_id > 0) {
    // Ambil ID pendaftaran_praktikum untuk user ini dan praktikum ini
    $sql_get_pendaftaran_id = "SELECT id FROM pendaftaran_praktikum WHERE user_id = ? AND mata_praktikum_id = ?";
    if ($stmt_pendaftaran = mysqli_prepare($link, $sql_get_pendaftaran_id)) {
        mysqli_stmt_bind_param($stmt_pendaftaran, "ii", $user_id, $praktikum_id);
        mysqli_stmt_execute($stmt_pendaftaran);
        mysqli_stmt_store_result($stmt_pendaftaran);
        if (mysqli_stmt_num_rows($stmt_pendaftaran) == 1) {
            mysqli_stmt_bind_result($stmt_pendaftaran, $fetched_pendaftaran_id);
            mysqli_stmt_fetch($stmt_pendaftaran);
            $pendaftaran_id = $fetched_pendaftaran_id;
        }
        mysqli_stmt_close($stmt_pendaftaran);
    }

    if ($pendaftaran_id) {
        // Ambil detail mata praktikum
        $sql_praktikum = "SELECT nama_praktikum, deskripsi FROM mata_praktikum WHERE id = ?";
        if ($stmt_praktikum = mysqli_prepare($link, $sql_praktikum)) {
            mysqli_stmt_bind_param($stmt_praktikum, "i", $praktikum_id);
            if (mysqli_stmt_execute($stmt_praktikum)) {
                mysqli_stmt_bind_result($stmt_praktikum, $nama_praktikum, $deskripsi_praktikum);
                mysqli_stmt_fetch($stmt_praktikum);
            }
            mysqli_stmt_close($stmt_praktikum);
        }

        // Ambil daftar modul untuk praktikum ini beserta status laporan
        $sql_modul = "
            SELECT
                mp.id AS modul_id,
                mp.judul_modul,
                mp.deskripsi_modul,
                mp.nama_file_materi,
                lp.id AS laporan_id,
                lp.nama_file_laporan,
                lp.nilai,
                lp.feedback,
                lp.status AS laporan_status
            FROM
                modul_praktikum mp
            LEFT JOIN
                laporan_praktikum lp ON mp.id = lp.modul_id AND lp.pendaftaran_id = ?
            WHERE
                mp.mata_praktikum_id = ?
            ORDER BY
                mp.judul_modul ASC
        ";
        if($stmt_modul = mysqli_prepare($link, $sql_modul)){
            mysqli_stmt_bind_param($stmt_modul, "ii", $pendaftaran_id, $praktikum_id);
            if(mysqli_stmt_execute($stmt_modul)){
                $result_modul = mysqli_stmt_get_result($stmt_modul);
                if(mysqli_num_rows($result_modul) > 0){
                    while($row = mysqli_fetch_array($result_modul)){
                        $modul_list[] = $row;
                    }
                    mysqli_free_result($result_modul);
                }
            } else{
                echo "ERROR: Tidak dapat mengambil data modul. " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt_modul);
        }
    } else {
        // Jika ID pendaftaran tidak ditemukan, user belum terdaftar di praktikum ini
        header("location: praktikum_saya.php");
        exit;
    }
} else {
    // Jika praktikum_id tidak valid
    header("location: praktikum_saya.php");
    exit;
}


// --- PROSES UPLOAD LAPORAN ---
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_laporan'])){
    $modul_id_laporan = isset($_POST['modul_id']) ? (int)$_POST['modul_id'] : 0;
    $upload_error = "";
    $upload_success = "";

    if ($modul_id_laporan > 0 && $pendaftaran_id) {
        $target_dir = "../public/laporan_praktikum/";
        $uploadOk = 1;
        $file_name_for_db = null;
        $file_type_for_db = null;
        $file_size_for_db = null;

        if(isset($_FILES["file_laporan"]) && $_FILES["file_laporan"]["error"] == 0){
            $original_file_name = basename($_FILES["file_laporan"]["name"]);
            $file_extension = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
            $allowed_types = array("pdf", "doc", "docx");

            $file_name_for_db = uniqid('laporan_') . "." . $file_extension;
            $target_file = $target_dir . $file_name_for_db;

            if(!in_array($file_extension, $allowed_types)){
                $upload_error = "Maaf, hanya file PDF, DOC, dan DOCX yang diizinkan untuk laporan.";
                $uploadOk = 0;
            }

            if ($_FILES["file_laporan"]["size"] > 10 * 1024 * 1024) { // Max 10MB
                $upload_error = "Maaf, ukuran file laporan terlalu besar (maks 10MB).";
                $uploadOk = 0;
            }

            if($uploadOk == 1){
                if (move_uploaded_file($_FILES["file_laporan"]["tmp_name"], $target_file)) {
                    $file_type_for_db = $_FILES["file_laporan"]["type"];
                    $file_size_for_db = $_FILES["file_laporan"]["size"];

                    // Cek apakah laporan sudah pernah diunggah untuk modul ini (UPDATE atau INSERT)
                    $sql_check_laporan = "SELECT id, nama_file_laporan FROM laporan_praktikum WHERE pendaftaran_id = ? AND modul_id = ?";
                    if($stmt_check_laporan = mysqli_prepare($link, $sql_check_laporan)){
                        mysqli_stmt_bind_param($stmt_check_laporan, "ii", $pendaftaran_id, $modul_id_laporan);
                        mysqli_stmt_execute($stmt_check_laporan);
                        mysqli_stmt_store_result($stmt_check_laporan);

                        if(mysqli_stmt_num_rows($stmt_check_laporan) > 0){
                            // Laporan sudah ada, lakukan UPDATE
                            mysqli_stmt_bind_result($stmt_check_laporan, $laporan_id_old, $old_file_name);
                            mysqli_stmt_fetch($stmt_check_laporan);

                            // Hapus file lama jika ada
                            if($old_file_name && file_exists("../public/laporan_praktikum/" . $old_file_name)){
                                unlink("../public/laporan_praktikum/" . $old_file_name);
                            }

                            $sql_update_laporan = "UPDATE laporan_praktikum SET nama_file_laporan = ?, tipe_file_laporan = ?, ukuran_file_laporan = ?, tanggal_upload = CURRENT_TIMESTAMP, nilai = NULL, feedback = NULL, status = 'pending' WHERE id = ?";
                            if($stmt_update_laporan = mysqli_prepare($link, $sql_update_laporan)){
                                mysqli_stmt_bind_param($stmt_update_laporan, "ssii", $file_name_for_db, $file_type_for_db, $file_size_for_db, $laporan_id_old);
                                if(mysqli_stmt_execute($stmt_update_laporan)){
                                    $upload_success = "Laporan berhasil diperbarui.";
                                } else {
                                    $upload_error = "Error saat memperbarui laporan.";
                                }
                                mysqli_stmt_close($stmt_update_laporan);
                            }
                        } else {
                            // Laporan belum ada, lakukan INSERT
                            $sql_insert_laporan = "INSERT INTO laporan_praktikum (pendaftaran_id, modul_id, nama_file_laporan, tipe_file_laporan, ukuran_file_laporan) VALUES (?, ?, ?, ?, ?)";
                            if($stmt_insert_laporan = mysqli_prepare($link, $sql_insert_laporan)){
                                mysqli_stmt_bind_param($stmt_insert_laporan, "iissi", $pendaftaran_id, $modul_id_laporan, $file_name_for_db, $file_type_for_db, $file_size_for_db);
                                if(mysqli_stmt_execute($stmt_insert_laporan)){
                                    $upload_success = "Laporan berhasil diunggah.";
                                } else {
                                    $upload_error = "Error saat mengunggah laporan.";
                                }
                                mysqli_stmt_close($stmt_insert_laporan);
                            }
                        }
                        mysqli_stmt_close($stmt_check_laporan);
                    }
                } else {
                    $upload_error = "Maaf, ada error saat memindahkan file Anda.";
                }
            }
        } else {
            $upload_error = "Mohon pilih file laporan untuk diunggah.";
        }
    } else {
        $upload_error = "Modul tidak valid atau pendaftaran tidak ditemukan.";
    }

    // Simpan pesan flash dan redirect
    $_SESSION['flash_message_laporan'] = $upload_success;
    if (!empty($upload_error)) {
        $_SESSION['flash_message_laporan'] = $upload_error;
    }
    header("location: detail_praktikum.php?praktikum_id=" . $praktikum_id);
    exit;
}

// Ambil pesan flash jika ada
$flash_message_laporan = '';
if (isset($_SESSION['flash_message_laporan'])) {
    $flash_message_laporan = $_SESSION['flash_message_laporan'];
    unset($_SESSION['flash_message_laporan']); // Hapus pesan setelah ditampilkan
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Praktikum - SIMPRAK Mahasiswa</title>
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
        <h2 class="text-3xl font-bold mb-6">Detail Praktikum: <?php echo htmlspecialchars($nama_praktikum); ?></h2>
        <p class="text-gray-700 mb-6"><?php echo htmlspecialchars($deskripsi_praktikum); ?></p>

        <a href="praktikum_saya.php" class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mb-6">← Kembali ke Praktikum Saya</a>

        <?php if (!empty($flash_message_laporan)): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($flash_message_laporan); ?>
            </div>
        <?php endif; ?>

        <h3 class="text-2xl font-bold mb-4">Daftar Modul & Tugas</h3>
        <?php if(!empty($modul_list)): ?>
            <div class="grid grid-cols-1 gap-6">
                <?php foreach($modul_list as $modul): ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h4 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($modul['judul_modul']); ?></h4>
                    <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($modul['deskripsi_modul']); ?></p>

                    <?php if(!empty($modul['nama_file_materi'])): ?>
                        <p class="mb-2">Materi Modul: <a href="../public/materi_modul/<?php echo htmlspecialchars($modul['nama_file_materi']); ?>" target="_blank" class="text-blue-500 hover:underline">Unduh Materi</a></p>
                    <?php else: ?>
                        <p class="mb-2 text-gray-500">Materi Modul: Belum tersedia.</p>
                    <?php endif; ?>

                    <div class="mt-4 border-t pt-4">
                        <h5 class="text-lg font-semibold mb-2">Laporan Anda:</h5>
                        <?php if(!empty($modul['laporan_id'])): ?>
                            <p class="mb-2">File Laporan: <a href="../public/laporan_praktikum/<?php echo htmlspecialchars($modul['nama_file_laporan']); ?>" target="_blank" class="text-blue-500 hover:underline">Unduh Laporan</a></p>
                            <p class="mb-2">Status Laporan: <span class="font-bold <?php
                                if($modul['laporan_status'] == 'dinilai') echo 'text-green-600';
                                else echo 'text-yellow-600';
                            ?>"><?php echo htmlspecialchars(ucfirst($modul['laporan_status'])); ?></span></p>
                            <?php if($modul['laporan_status'] == 'dinilai'): ?>
                                <p class="mb-2">Nilai: <span class="font-bold text-green-700 text-xl"><?php echo htmlspecialchars($modul['nilai']); ?></span></p>
                                <p class="mb-2">Feedback: <?php echo htmlspecialchars($modul['feedback']); ?></p>
                            <?php endif; ?>
                            <p class="text-sm text-gray-600 mb-2">Unggah ulang untuk memperbarui laporan.</p>
                        <?php else: ?>
                            <p class="mb-2 text-gray-500">Anda belum mengunggah laporan untuk modul ini.</p>
                        <?php endif; ?>

                        <form action="detail_praktikum.php?praktikum_id=<?php echo $praktikum_id; ?>" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="modul_id" value="<?php echo $modul['modul_id']; ?>">
                            <div class="mb-4">
                                <label for="file_laporan_<?php echo $modul['modul_id']; ?>" class="block text-gray-700 text-sm font-bold mb-2">Unggah Laporan (PDF/DOCX):</label>
                                <input type="file" name="file_laporan" id="file_laporan_<?php echo $modul['modul_id']; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <button type="submit" name="submit_laporan" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded">Unggah Laporan</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-600">Belum ada modul yang tersedia untuk praktikum ini.</p>
        <?php endif; ?>
    </div>
</body>
</html>