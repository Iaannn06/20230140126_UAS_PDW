<?php
session_start();

// Cek apakah user sudah login dan adalah asisten
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "asisten"){
    header("location: ../public/login.php");
    exit;
}

require_once '../includes/config.php';

$laporan_list = [];
$filter_modul_id = isset($_GET['filter_modul']) ? (int)$_GET['filter_modul'] : 0;
$filter_user_id = isset($_GET['filter_mahasiswa']) ? (int)$_GET['filter_mahasiswa'] : 0;
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

$where_clauses = [];
$param_types = "";
$param_values = [];

if ($filter_modul_id > 0) {
    $where_clauses[] = "lp.modul_id = ?";
    $param_types .= "i";
    $param_values[] = $filter_modul_id;
}
if ($filter_user_id > 0) {
    $where_clauses[] = "pp.user_id = ?";
    $param_types .= "i";
    $param_values[] = $filter_user_id;
}
if (!empty($filter_status) && in_array($filter_status, ['pending', 'dinilai'])) {
    $where_clauses[] = "lp.status = ?";
    $param_types .= "s";
    $param_values[] = $filter_status;
}

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
";

if (!empty($where_clauses)) {
    $sql_select_laporan .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_select_laporan .= " ORDER BY lp.tanggal_upload DESC";

if ($stmt_select_laporan = mysqli_prepare($link, $sql_select_laporan)) {
    if (!empty($param_values)) {
        mysqli_stmt_bind_param($stmt_select_laporan, $param_types, ...$param_values);
    }
    if (mysqli_stmt_execute($stmt_select_laporan)) {
        $result_laporan = mysqli_stmt_get_result($stmt_select_laporan);
        if (mysqli_num_rows($result_laporan) > 0) {
            while ($row = mysqli_fetch_array($result_laporan, MYSQLI_ASSOC)) {
                $laporan_list[] = $row;
            }
        }
        mysqli_free_result($result_laporan);
    } else {
        echo "ERROR: Tidak dapat mengambil data laporan. " . mysqli_error($link);
    }
    mysqli_stmt_close($stmt_select_laporan);
}

// Ambil daftar modul untuk filter
$modul_filter_list = [];
$sql_modul_filter = "SELECT id, judul_modul FROM modul_praktikum ORDER BY judul_modul ASC";
if ($result_modul_filter = mysqli_query($link, $sql_modul_filter)) {
    while ($row = mysqli_fetch_assoc($result_modul_filter)) {
        $modul_filter_list[] = $row;
    }
    mysqli_free_result($result_modul_filter);
}

// Ambil daftar mahasiswa untuk filter
$mahasiswa_filter_list = [];
$sql_mahasiswa_filter = "SELECT id, nama, username FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC";
if ($result_mahasiswa_filter = mysqli_query($link, $sql_mahasiswa_filter)) {
    while ($row = mysqli_fetch_assoc($result_mahasiswa_filter)) {
        $mahasiswa_filter_list[] = $row;
    }
    mysqli_free_result($result_mahasiswa_filter);
}

// Ambil pesan flash jika ada
$flash_message_laporan_masuk = '';
if (isset($_SESSION['flash_message_laporan_masuk'])) {
    $flash_message_laporan_masuk = $_SESSION['flash_message_laporan_masuk'];
    unset($_SESSION['flash_message_laporan_masuk']); // Hapus pesan setelah ditampilkan
}


mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Masuk - SIMPRAK Asisten</title>
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
        <h2 class="text-3xl font-bold mb-6">Laporan Masuk</h2>

        <a href="dashboard_asisten.php" class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mb-6">← Kembali ke Dashboard Asisten</a>

        <?php if (!empty($flash_message_laporan_masuk)): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($flash_message_laporan_masuk); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h3 class="text-xl font-bold mb-4">Filter Laporan</h3>
            <form action="laporan_masuk.php" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="filter_modul" class="block text-gray-700 text-sm font-bold mb-2">Modul:</label>
                    <select name="filter_modul" id="filter_modul" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="0">Semua Modul</option>
                        <?php foreach($modul_filter_list as $modul_item): ?>
                            <option value="<?php echo $modul_item['id']; ?>" <?php echo ($filter_modul_id == $modul_item['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($modul_item['judul_modul']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_mahasiswa" class="block text-gray-700 text-sm font-bold mb-2">Mahasiswa:</label>
                    <select name="filter_mahasiswa" id="filter_mahasiswa" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="0">Semua Mahasiswa</option>
                        <?php foreach($mahasiswa_filter_list as $user_item): ?>
                            <option value="<?php echo $user_item['id']; ?>" <?php echo ($filter_user_id == $user_item['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user_item['nama'] . " (" . $user_item['username'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_status" class="block text-gray-700 text-sm font-bold mb-2">Status:</label>
                    <select name="filter_status" id="filter_status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="dinilai" <?php echo ($filter_status == 'dinilai') ? 'selected' : ''; ?>>Dinilai</option>
                    </select>
                </div>
                <div class="md:col-span-3 flex justify-end">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Terapkan Filter</button>
                    <a href="laporan_masuk.php" class="ml-2 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Reset Filter</a>
                </div>
            </form>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-md">
            <h3 class="text-2xl font-bold mb-4">Daftar Laporan</h3>
            <?php if(!empty($laporan_list)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">ID Laporan</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Mahasiswa</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Praktikum</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Modul</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Tanggal Upload</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Status</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Nilai</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($laporan_list as $laporan): ?>
                        <tr>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($laporan['laporan_id']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($laporan['nama_mahasiswa']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($laporan['nama_praktikum']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($laporan['judul_modul']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($laporan['tanggal_upload']); ?></td>
                            <td class="py-2 px-4 border-b">
                                <span class="font-bold <?php
                                    if($laporan['status'] == 'dinilai') echo 'text-green-600';
                                    else echo 'text-yellow-600';
                                ?>"><?php echo htmlspecialchars(ucfirst($laporan['status'])); ?></span>
                            </td>
                            <td class="py-2 px-4 border-b">
                                <?php echo $laporan['nilai'] !== null ? htmlspecialchars($laporan['nilai']) : '-'; ?>
                            </td>
                            <td class="py-2 px-4 border-b">
                                <a href="../public/laporan_praktikum/<?php echo htmlspecialchars($laporan['nama_file_laporan']); ?>" target="_blank" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-xs mr-2">Unduh</a>
                                <a href="beri_nilai_laporan.php?id=<?php echo $laporan['laporan_id']; ?>" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-xs">Beri Nilai</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="text-gray-600">Belum ada laporan yang masuk.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>