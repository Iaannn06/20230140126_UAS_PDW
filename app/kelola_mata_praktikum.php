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

$nama_praktikum = $deskripsi = $kode_praktikum = "";
$nama_praktikum_err = $deskripsi_err = $kode_praktikum_err = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list'; // Default action adalah 'list'
$id_edit = isset($_GET['id']) ? $_GET['id'] : null;

// --- PROSES TAMBAH/EDIT DATA ---
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validasi input form
    // Nama Praktikum
    if(empty(trim($_POST["nama_praktikum"]))){
        $nama_praktikum_err = "Mohon masukkan nama praktikum.";
    } else {
        // Cek duplikasi nama_praktikum (untuk tambah dan edit)
        $sql_check = "SELECT id FROM mata_praktikum WHERE nama_praktikum = ?";
        if ($action == 'edit' && $id_edit) {
            $sql_check .= " AND id != ?";
        }

        if($stmt_check = mysqli_prepare($link, $sql_check)){
            if ($action == 'edit' && $id_edit) {
                mysqli_stmt_bind_param($stmt_check, "si", $param_nama_praktikum, $param_id);
                $param_id = $id_edit;
            } else {
                mysqli_stmt_bind_param($stmt_check, "s", $param_nama_praktikum);
            }
            $param_nama_praktikum = trim($_POST["nama_praktikum"]);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if(mysqli_stmt_num_rows($stmt_check) == 1){
                $nama_praktikum_err = "Nama praktikum ini sudah ada.";
            } else {
                $nama_praktikum = trim($_POST["nama_praktikum"]);
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    // Deskripsi
    $deskripsi = trim($_POST["deskripsi"]); // Deskripsi bisa kosong

    // Kode Praktikum
    if(empty(trim($_POST["kode_praktikum"]))){
        $kode_praktikum_err = "Mohon masukkan kode praktikum.";
    } else {
        // Cek duplikasi kode_praktikum (untuk tambah dan edit)
        $sql_check = "SELECT id FROM mata_praktikum WHERE kode_praktikum = ?";
        if ($action == 'edit' && $id_edit) {
            $sql_check .= " AND id != ?";
        }
        if($stmt_check = mysqli_prepare($link, $sql_check)){
            if ($action == 'edit' && $id_edit) {
                mysqli_stmt_bind_param($stmt_check, "si", $param_kode_praktikum, $param_id_kode);
                $param_id_kode = $id_edit;
            } else {
                mysqli_stmt_bind_param($stmt_check, "s", $param_kode_praktikum);
            }
            $param_kode_praktikum = trim($_POST["kode_praktikum"]);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if(mysqli_stmt_num_rows($stmt_check) == 1){
                $kode_praktikum_err = "Kode praktikum ini sudah ada.";
            } else {
                $kode_praktikum = trim($_POST["kode_praktikum"]);
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    // Jika tidak ada error input, lakukan INSERT/UPDATE
    if(empty($nama_praktikum_err) && empty($kode_praktikum_err)){
        if($action == 'add'){
            $sql = "INSERT INTO mata_praktikum (nama_praktikum, deskripsi, kode_praktikum) VALUES (?, ?, ?)";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sss", $param_nama, $param_deskripsi, $param_kode);
                $param_nama = $nama_praktikum;
                $param_deskripsi = $deskripsi;
                $param_kode = $kode_praktikum;
                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_mata_praktikum.php"); // Redirect kembali ke daftar setelah tambah
                    exit();
                } else{
                    echo "Terjadi kesalahan saat menambahkan data. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit' && $id_edit) {
            $sql = "UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ?, kode_praktikum = ? WHERE id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sssi", $param_nama, $param_deskripsi, $param_kode, $param_id);
                $param_nama = $nama_praktikum;
                $param_deskripsi = $deskripsi;
                $param_kode = $kode_praktikum;
                $param_id = $id_edit;
                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_mata_praktikum.php"); // Redirect kembali ke daftar setelah edit
                    exit();
                } else{
                    echo "Terjadi kesalahan saat mengubah data. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// --- PROSES AMBIL DATA UNTUK EDIT ---
if($action == 'edit' && $id_edit){
    $sql = "SELECT nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id_edit;
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $nama_praktikum, $deskripsi, $kode_praktikum);
                mysqli_stmt_fetch($stmt);
            } else {
                echo "Data praktikum tidak ditemukan.";
                $action = 'list'; // Kembali ke mode list jika data tidak ditemukan
            }
        } else {
            echo "Ada yang salah. Mohon coba lagi nanti.";
        }
        mysqli_stmt_close($stmt);
    }
}

// --- PROSES HAPUS DATA ---
if($action == 'delete' && isset($_GET['id'])){
    $id_delete = $_GET['id'];
    $sql = "DELETE FROM mata_praktikum WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id_delete;
        if(mysqli_stmt_execute($stmt)){
            header("location: kelola_mata_praktikum.php"); // Redirect kembali ke daftar setelah hapus
            exit();
        } else{
            echo "Terjadi kesalahan saat menghapus data. Mohon coba lagi.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Ambil semua data mata praktikum untuk ditampilkan di tabel
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
    <title>Kelola Mata Praktikum - SIMPRAK Asisten</title>
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
        <h2 class="text-3xl font-bold mb-6">Kelola Mata Praktikum</h2>

        <div class="bg-white p-8 rounded-lg shadow-md mb-8">
            <h3 class="text-2xl font-bold mb-4"><?php echo ($action == 'edit' ? 'Edit' : 'Tambah') . ' Mata Praktikum'; ?></h3>
            <form action="kelola_mata_praktikum.php?action=<?php echo ($action == 'edit' && $id_edit ? 'edit&id=' . $id_edit : 'add'); ?>" method="post">
                <div class="mb-4">
                    <label for="nama_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Nama Praktikum:</label>
                    <input type="text" name="nama_praktikum" id="nama_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nama_praktikum_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($nama_praktikum); ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $nama_praktikum_err; ?></span>
                </div>
                <div class="mb-4">
                    <label for="kode_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Kode Praktikum:</label>
                    <input type="text" name="kode_praktikum" id="kode_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($kode_praktikum_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($kode_praktikum); ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $kode_praktikum_err; ?></span>
                </div>
                <div class="mb-6">
                    <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
                    <textarea name="deskripsi" id="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($deskripsi); ?></textarea>
                    <span class="text-red-500 text-xs italic"><?php echo $deskripsi_err; ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"><?php echo ($action == 'edit' ? 'Update' : 'Tambah'); ?></button>
                    <?php if($action == 'edit'): ?>
                        <a href="kelola_mata_praktikum.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-md">
            <h3 class="text-2xl font-bold mb-4">Daftar Mata Praktikum</h3>
            <?php if(!empty($mata_praktikum_list)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">ID</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Nama Praktikum</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Kode</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Deskripsi</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mata_praktikum_list as $praktikum): ?>
                        <tr>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($praktikum['id']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></td>
                            <td class="py-2 px-4 border-b">
                                <a href="kelola_modul.php?praktikum_id=<?php echo $praktikum['id']; ?>" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-1 px-3 rounded text-xs mr-2">Kelola Modul</a>
                                <a href="kelola_mata_praktikum.php?action=edit&id=<?php echo $praktikum['id']; ?>" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-3 rounded text-xs mr-2">Edit</a>
                                <a href="kelola_mata_praktikum.php?action=delete&id=<?php echo $praktikum['id']; ?>" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-xs" onclick="return confirm('Apakah Anda yakin ingin menghapus praktikum ini?');">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="text-gray-600">Belum ada mata praktikum yang ditambahkan.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>