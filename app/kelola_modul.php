<?php
session_start();

// Cek apakah user sudah login, jika tidak, arahkan kembali ke halaman login
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../public/login.php");
    exit;
}

// Cek peran user, hanya asisten yang boleh mengakses halaman ini
if($_SESSION["role"] !== "asisten"){
    header("location: dashboard_mahasiswa.php"); // Arahkan ke dashboard mahasiswa jika bukan asisten
    exit;
}

// Include file konfigurasi database
require_once '../includes/config.php';

$judul_modul = $deskripsi_modul = "";
$judul_modul_err = $deskripsi_modul_err = $file_materi_err = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list'; // Default action adalah 'list'
$id_edit = isset($_GET['id']) ? $_GET['id'] : null;
$mata_praktikum_id = isset($_GET['praktikum_id']) ? (int)$_GET['praktikum_id'] : null; // ID Mata Praktikum yang dipilih

// Redirect jika praktikum_id tidak valid saat ingin menambah/mengedit modul
if (($action == 'add' || $action == 'edit') && !$mata_praktikum_id) {
    header("location: kelola_mata_praktikum.php"); // Atau halaman daftar praktikum
    exit;
}

// Ambil nama mata praktikum untuk ditampilkan di header
$nama_mata_praktikum = "Pilih Mata Praktikum";
if ($mata_praktikum_id) {
    $sql_praktikum = "SELECT nama_praktikum FROM mata_praktikum WHERE id = ?";
    if ($stmt_praktikum = mysqli_prepare($link, $sql_praktikum)) {
        mysqli_stmt_bind_param($stmt_praktikum, "i", $param_praktikum_id);
        $param_praktikum_id = $mata_praktikum_id;
        if (mysqli_stmt_execute($stmt_praktikum)) {
            mysqli_stmt_bind_result($stmt_praktikum, $fetched_nama_praktikum);
            mysqli_stmt_fetch($stmt_praktikum);
            $nama_mata_praktikum = htmlspecialchars($fetched_nama_praktikum);
        }
        mysqli_stmt_close($stmt_praktikum);
    }
}


// --- PROSES TAMBAH/EDIT DATA ---
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $mata_praktikum_id_post = isset($_POST['mata_praktikum_id']) ? (int)$_POST['mata_praktikum_id'] : $mata_praktikum_id;

    // Validasi Judul Modul
    if(empty(trim($_POST["judul_modul"]))){
        $judul_modul_err = "Mohon masukkan judul modul.";
    } else {
        $judul_modul = trim($_POST["judul_modul"]);
    }

    // Validasi Deskripsi Modul
    $deskripsi_modul = trim($_POST["deskripsi_modul"]); // Deskripsi bisa kosong

    // Handle File Upload
    $target_dir = "../public/materi_modul/";
    $uploadOk = 1;
    $file_name_for_db = null; // Nama file yang akan disimpan di database
    $file_type_for_db = null;
    $file_size_for_db = null;

    // Jika ada file yang diunggah
    if(isset($_FILES["file_materi"]) && $_FILES["file_materi"]["error"] == 0){
        $original_file_name = basename($_FILES["file_materi"]["name"]);
        $file_extension = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
        $allowed_types = array("pdf", "doc", "docx");

        // Buat nama file unik untuk menghindari duplikasi
        $file_name_for_db = uniqid() . "." . $file_extension;
        $target_file = $target_dir . $file_name_for_db;

        // Cek tipe file
        if(!in_array($file_extension, $allowed_types)){
            $file_materi_err = "Maaf, hanya file PDF, DOC, dan DOCX yang diizinkan.";
            $uploadOk = 0;
        }

        // Cek ukuran file (misal: maks 5MB)
        if ($_FILES["file_materi"]["size"] > 5 * 1024 * 1024) {
            $file_materi_err = "Maaf, ukuran file terlalu besar (maks 5MB).";
            $uploadOk = 0;
        }

        if($uploadOk == 0){
            // Jika ada error upload, set error message
        } else {
            if (move_uploaded_file($_FILES["file_materi"]["tmp_name"], $target_file)) {
                $file_type_for_db = $_FILES["file_materi"]["type"];
                $file_size_for_db = $_FILES["file_materi"]["size"];
            } else {
                $file_materi_err = "Maaf, ada error saat mengunggah file Anda.";
            }
        }
    } elseif ($action == 'edit' && $id_edit) {
        // Jika tidak ada file baru diunggah saat edit, gunakan file lama
        $sql_old_file = "SELECT nama_file_materi, tipe_file_materi, ukuran_file_materi FROM modul_praktikum WHERE id = ?";
        if($stmt_old_file = mysqli_prepare($link, $sql_old_file)){
            mysqli_stmt_bind_param($stmt_old_file, "i", $param_id_edit);
            $param_id_edit = $id_edit;
            if(mysqli_stmt_execute($stmt_old_file)){
                mysqli_stmt_bind_result($stmt_old_file, $file_name_for_db, $file_type_for_db, $file_size_for_db);
                mysqli_stmt_fetch($stmt_old_file);
            }
            mysqli_stmt_close($stmt_old_file);
        }
    }


    // Jika tidak ada error input dan upload
    if(empty($judul_modul_err) && empty($file_materi_err)){
        if($action == 'add'){
            $sql = "INSERT INTO modul_praktikum (mata_praktikum_id, judul_modul, deskripsi_modul, nama_file_materi, tipe_file_materi, ukuran_file_materi) VALUES (?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "issssi", $mata_praktikum_id_post, $param_judul, $param_deskripsi, $param_file_name, $param_file_type, $param_file_size);
                $param_judul = $judul_modul;
                $param_deskripsi = $deskripsi_modul;
                $param_file_name = $file_name_for_db;
                $param_file_type = $file_type_for_db;
                $param_file_size = $file_size_for_db;

                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_modul.php?praktikum_id=" . $mata_praktikum_id_post); // Redirect kembali ke daftar modul
                    exit();
                } else{
                    echo "Terjadi kesalahan saat menambahkan modul. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit' && $id_edit) {
            $sql = "UPDATE modul_praktikum SET judul_modul = ?, deskripsi_modul = ?, nama_file_materi = ?, tipe_file_materi = ?, ukuran_file_materi = ? WHERE id = ? AND mata_praktikum_id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sssisii", $param_judul, $param_deskripsi, $param_file_name, $param_file_type, $param_file_size, $param_id, $param_mata_praktikum_id);
                $param_judul = $judul_modul;
                $param_deskripsi = $deskripsi_modul;
                $param_file_name = $file_name_for_db;
                $param_file_type = $file_type_for_db;
                $param_file_size = $file_size_for_db;
                $param_id = $id_edit;
                $param_mata_praktikum_id = $mata_praktikum_id_post;

                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_modul.php?praktikum_id=" . $mata_praktikum_id_post); // Redirect kembali ke daftar modul
                    exit();
                } else{
                    echo "Terjadi kesalahan saat mengubah modul. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// --- PROSES AMBIL DATA MODUL UNTUK EDIT ---
if($action == 'edit' && $id_edit && $mata_praktikum_id){
    $sql = "SELECT judul_modul, deskripsi_modul, nama_file_materi FROM modul_praktikum WHERE id = ? AND mata_praktikum_id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $param_id, $param_praktikum_id);
        $param_id = $id_edit;
        $param_praktikum_id = $mata_praktikum_id;
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $judul_modul, $deskripsi_modul, $nama_file_materi_old);
                mysqli_stmt_fetch($stmt);
            } else {
                echo "Data modul tidak ditemukan.";
                $action = 'list';
            }
        } else {
            echo "Ada yang salah. Mohon coba lagi nanti.";
        }
        mysqli_stmt_close($stmt);
    }
}

// --- PROSES HAPUS DATA ---
if($action == 'delete' && isset($_GET['id']) && isset($_GET['praktikum_id'])){
    $id_delete = $_GET['id'];
    $mata_praktikum_id_delete = $_GET['praktikum_id'];

    // Hapus file fisik sebelum menghapus entri database
    $sql_get_file = "SELECT nama_file_materi FROM modul_praktikum WHERE id = ?";
    if($stmt_get_file = mysqli_prepare($link, $sql_get_file)){
        mysqli_stmt_bind_param($stmt_get_file, "i", $param_id_delete);
        $param_id_delete = $id_delete;
        if(mysqli_stmt_execute($stmt_get_file)){
            mysqli_stmt_bind_result($stmt_get_file, $file_to_delete);
            mysqli_stmt_fetch($stmt_get_file);
            if($file_to_delete && file_exists("../public/materi_modul/" . $file_to_delete)){
                unlink("../public/materi_modul/" . $file_to_delete);
            }
        }
        mysqli_stmt_close($stmt_get_file);
    }

    $sql = "DELETE FROM modul_praktikum WHERE id = ? AND mata_praktikum_id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $param_id, $param_praktikum_id);
        $param_id = $id_delete;
        $param_praktikum_id = $mata_praktikum_id_delete;
        if(mysqli_stmt_execute($stmt)){
            header("location: kelola_modul.php?praktikum_id=" . $mata_praktikum_id_delete); // Redirect kembali ke daftar modul
            exit();
        } else{
            echo "Terjadi kesalahan saat menghapus modul. Mohon coba lagi.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Ambil semua data modul untuk mata praktikum yang dipilih
$modul_list = [];
if ($mata_praktikum_id) {
    $sql_select_modul = "SELECT id, judul_modul, deskripsi_modul, nama_file_materi FROM modul_praktikum WHERE mata_praktikum_id = ? ORDER BY judul_modul ASC";
    if($stmt_select_modul = mysqli_prepare($link, $sql_select_modul)){
        mysqli_stmt_bind_param($stmt_select_modul, "i", $param_praktikum_id);
        $param_praktikum_id = $mata_praktikum_id;
        if(mysqli_stmt_execute($stmt_select_modul)){
            $result_modul = mysqli_stmt_get_result($stmt_select_modul);
            if(mysqli_num_rows($result_modul) > 0){
                while($row = mysqli_fetch_array($result_modul)){
                    $modul_list[] = $row;
                }
                mysqli_free_result($result_modul);
            }
        } else{
            echo "ERROR: Tidak dapat mengambil data modul. " . mysqli_error($link);
        }
        mysqli_stmt_close($stmt_select_modul);
    }
}

// Tutup koneksi
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Modul - SIMPRAK Asisten</title>
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
        <h2 class="text-3xl font-bold mb-6">Kelola Modul untuk Praktikum: <?php echo $nama_mata_praktikum; ?></h2>
        <a href="kelola_mata_praktikum.php" class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mb-6">← Kembali ke Kelola Praktikum</a>

        <div class="bg-white p-8 rounded-lg shadow-md mb-8">
            <h3 class="text-2xl font-bold mb-4"><?php echo ($action == 'edit' ? 'Edit' : 'Tambah') . ' Modul'; ?></h3>
            <form action="kelola_modul.php?action=<?php echo ($action == 'edit' && $id_edit ? 'edit&id=' . $id_edit : 'add'); ?>&praktikum_id=<?php echo $mata_praktikum_id; ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="mata_praktikum_id" value="<?php echo $mata_praktikum_id; ?>">
                <div class="mb-4">
                    <label for="judul_modul" class="block text-gray-700 text-sm font-bold mb-2">Judul Modul/Pertemuan:</label>
                    <input type="text" name="judul_modul" id="judul_modul" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($judul_modul_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($judul_modul); ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $judul_modul_err; ?></span>
                </div>
                <div class="mb-4">
                    <label for="deskripsi_modul" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Modul:</label>
                    <textarea name="deskripsi_modul" id="deskripsi_modul" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($deskripsi_modul); ?></textarea>
                    <span class="text-red-500 text-xs italic"><?php echo $deskripsi_modul_err; ?></span>
                </div>
                <div class="mb-6">
                    <label for="file_materi" class="block text-gray-700 text-sm font-bold mb-2">File Materi (PDF/DOCX):</label>
                    <input type="file" name="file_materi" id="file_materi" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($file_materi_err)) ? 'border-red-500' : ''; ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $file_materi_err; ?></span>
                    <?php if($action == 'edit' && !empty($nama_file_materi_old)): ?>
                        <p class="text-sm text-gray-600 mt-2">File materi saat ini: <a href="../public/materi_modul/<?php echo htmlspecialchars($nama_file_materi_old); ?>" target="_blank" class="text-blue-500 hover:underline">Lihat File</a> (Kosongkan untuk tidak mengubah)</p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"><?php echo ($action == 'edit' ? 'Update' : 'Tambah'); ?></button>
                    <?php if($action == 'edit'): ?>
                        <a href="kelola_modul.php?praktikum_id=<?php echo $mata_praktikum_id; ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-md">
            <h3 class="text-2xl font-bold mb-4">Daftar Modul</h3>
            <?php if(!empty($modul_list)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">ID</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Judul Modul</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Deskripsi</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">File Materi</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($modul_list as $modul): ?>
                        <tr>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($modul['id']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($modul['judul_modul']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($modul['deskripsi_modul']); ?></td>
                            <td class="py-2 px-4 border-b">
                                <?php if(!empty($modul['nama_file_materi'])): ?>
                                    <a href="../public/materi_modul/<?php echo htmlspecialchars($modul['nama_file_materi']); ?>" target="_blank" class="text-blue-500 hover:underline">Unduh File</a>
                                <?php else: ?>
                                    Tidak ada file
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border-b">
                                <a href="kelola_modul.php?action=edit&id=<?php echo $modul['id']; ?>&praktikum_id=<?php echo $mata_praktikum_id; ?>" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-3 rounded text-xs mr-2">Edit</a>
                                <a href="kelola_modul.php?action=delete&id=<?php echo $modul['id']; ?>&praktikum_id=<?php echo $mata_praktikum_id; ?>" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-xs" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini?');">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="text-gray-600">Belum ada modul yang ditambahkan untuk praktikum ini.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>