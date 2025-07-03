<?php
session_start();

// Cek apakah user sudah login dan adalah asisten
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "asisten"){
    header("location: ../public/login.php");
    exit;
}

require_once '../includes/config.php';

$username = $password = $confirm_password = $nama = $email = $role = "";
$username_err = $password_err = $confirm_password_err = $nama_err = $email_err = $role_err = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list'; // Default action adalah 'list'
$id_edit = isset($_GET['id']) ? (int)$_GET['id'] : null;

// --- PROSES TAMBAH/EDIT DATA ---
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validasi input form
    // Username
    if(empty(trim($_POST["username"]))){
        $username_err = "Mohon masukkan username.";
    } else {
        // Cek duplikasi username (untuk tambah dan edit)
        $sql_check = "SELECT id FROM users WHERE username = ?";
        if ($action == 'edit' && $id_edit) {
            $sql_check .= " AND id != ?";
        }

        if($stmt_check = mysqli_prepare($link, $sql_check)){
            if ($action == 'edit' && $id_edit) {
                mysqli_stmt_bind_param($stmt_check, "si", $param_username, $param_id);
                $param_id = $id_edit;
            } else {
                mysqli_stmt_bind_param($stmt_check, "s", $param_username);
            }
            $param_username = trim($_POST["username"]);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if(mysqli_stmt_num_rows($stmt_check) == 1){
                $username_err = "Username ini sudah terdaftar.";
            } else {
                $username = trim($_POST["username"]);
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    // Nama Lengkap
    if(empty(trim($_POST["nama"]))){
        $nama_err = "Mohon masukkan nama lengkap anda.";
    } else{
        $nama = trim($_POST["nama"]);
    }

    // Email
    if(empty(trim($_POST["email"]))){
        $email_err = "Mohon masukkan email anda.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Format email anda tidak valid.";
    } else {
        // Cek duplikasi email (untuk tambah dan edit)
        $sql_check = "SELECT id FROM users WHERE email = ?";
        if ($action == 'edit' && $id_edit) {
            $sql_check .= " AND id != ?";
        }
        if($stmt_check = mysqli_prepare($link, $sql_check)){
            if ($action == 'edit' && $id_edit) {
                mysqli_stmt_bind_param($stmt_check, "si", $param_email, $param_id_email);
                $param_id_email = $id_edit;
            } else {
                mysqli_stmt_bind_param($stmt_check, "s", $param_email);
            }
            $param_email = trim($_POST["email"]);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if(mysqli_stmt_num_rows($stmt_check) == 1){
                $email_err = "Email ini sudah terdaftar sebelumnya.";
            } else {
                $email = trim($_POST["email"]);
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    // Role
    if(empty(trim($_POST["role"]))){
        $role_err = "Mohon pilih peran (role).";
    } elseif(!in_array($_POST["role"], ['mahasiswa', 'asisten'])){
        $role_err = "Peran yang dipilih tidak valid.";
    } else {
        $role = trim($_POST["role"]);
    }

    // Password (hanya divalidasi jika menambah atau jika password diisi saat edit)
    if ($action == 'add' || (!empty(trim($_POST["password"])) && $action == 'edit')) {
        if(empty(trim($_POST["password"]))){
            $password_err = "Mohon masukkan password.";
        } elseif(strlen(trim($_POST["password"])) < 6){
            $password_err = "Password harus minimal 6 karakter.";
        } else{
            $password = trim($_POST["password"]);
        }

        if(empty(trim($_POST["confirm_password"]))){
            $confirm_password_err = "Mohon konfirmasi password.";
        } else{
            if(empty($password_err) && ($password != trim($_POST["confirm_password"]))){
                $confirm_password_err = "Password tidak cocok.";
            }
        }
    }


    // Jika tidak ada error input, lakukan INSERT/UPDATE
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($nama_err) && empty($email_err) && empty($role_err)){
        if($action == 'add'){
            $sql = "INSERT INTO users (username, password, role, nama, email) VALUES (?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sssss", $param_username, $param_password, $param_role, $param_nama, $param_email);
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $param_role = $role;
                $param_nama = $nama;
                $param_email = $email;
                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_akun.php");
                    exit();
                } else{
                    echo "Terjadi kesalahan saat menambahkan akun. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit' && $id_edit) {
            $sql = "UPDATE users SET username = ?, role = ?, nama = ?, email = ? WHERE id = ?";
            // Jika password diisi, tambahkan update password
            if(!empty($password)){
                $sql = "UPDATE users SET username = ?, password = ?, role = ?, nama = ?, email = ? WHERE id = ?";
            }

            if($stmt = mysqli_prepare($link, $sql)){
                if (!empty($password)) {
                    mysqli_stmt_bind_param($stmt, "sssssi", $param_username, $param_password, $param_role, $param_nama, $param_email, $param_id);
                    $param_password = password_hash($password, PASSWORD_DEFAULT);
                } else {
                    mysqli_stmt_bind_param($stmt, "ssssi", $param_username, $param_role, $param_nama, $param_email, $param_id);
                }
                $param_username = $username;
                $param_role = $role;
                $param_nama = $nama;
                $param_email = $email;
                $param_id = $id_edit;

                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_akun.php");
                    exit();
                } else{
                    echo "Terjadi kesalahan saat mengubah akun. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// --- PROSES AMBIL DATA AKUN UNTUK EDIT ---
if($action == 'edit' && $id_edit){
    $sql = "SELECT username, nama, email, role FROM users WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id_edit;
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $username, $nama, $email, $role);
                mysqli_stmt_fetch($stmt);
            } else {
                echo "Akun tidak ditemukan.";
                $action = 'list';
            }
        } else {
            echo "Ada yang salah. Mohon coba lagi nanti.";
        }
        mysqli_stmt_close($stmt);
    }
}

// --- PROSES HAPUS DATA ---
if($action == 'delete' && isset($_GET['id'])){
    $id_delete = (int)$_GET['id'];
    // Pastikan asisten tidak menghapus dirinya sendiri
    if ($id_delete == $_SESSION['id']) {
        $_SESSION['flash_message_akun'] = "Tidak bisa menghapus akun sendiri!";
        header("location: kelola_akun.php");
        exit;
    }

    $sql = "DELETE FROM users WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id_delete;
        if(mysqli_stmt_execute($stmt)){
            $_SESSION['flash_message_akun'] = "Akun berhasil dihapus!";
            header("location: kelola_akun.php");
            exit();
        } else{
            $_SESSION['flash_message_akun'] = "Terjadi kesalahan saat menghapus akun. Mohon coba lagi.";
            header("location: kelola_akun.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Ambil semua data akun untuk ditampilkan di tabel
$users_list = [];
$sql_select = "SELECT id, username, nama, email, role, created_at FROM users ORDER BY created_at DESC";
if($result = mysqli_query($link, $sql_select)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $users_list[] = $row;
        }
        mysqli_free_result($result);
    }
} else{
    echo "ERROR: Tidak dapat mengambil data akun. " . mysqli_error($link);
}

// Ambil pesan flash jika ada
$flash_message_akun = '';
if (isset($_SESSION['flash_message_akun'])) {
    $flash_message_akun = $_SESSION['flash_message_akun'];
    unset($_SESSION['flash_message_akun']); // Hapus pesan setelah ditampilkan
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Akun Pengguna - SIMPRAK Asisten</title>
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
        <h2 class="text-3xl font-bold mb-6">Kelola Akun Pengguna</h2>

        <a href="dashboard_asisten.php" class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mb-6">← Kembali ke Dashboard Asisten</a>

        <?php if (!empty($flash_message_akun)): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($flash_message_akun); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-lg shadow-md mb-8">
            <h3 class="text-2xl font-bold mb-4"><?php echo ($action == 'edit' ? 'Edit' : 'Tambah') . ' Akun Pengguna'; ?></h3>
            <form action="kelola_akun.php?action=<?php echo ($action == 'edit' && $id_edit ? 'edit&id=' . $id_edit : 'add'); ?>" method="post">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                    <input type="text" name="username" id="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($username_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $username_err; ?></span>
                </div>
                <div class="mb-4">
                    <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
                    <input type="text" name="nama" id="nama" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nama_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($nama); ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $nama_err; ?></span>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                    <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($email_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $email_err; ?></span>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password <?php echo ($action == 'edit' ? '(kosongkan jika tidak ingin mengubah)' : ''); ?>:</label>
                    <input type="password" name="password" id="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $password_err; ?></span>
                </div>
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($confirm_password_err)) ? 'border-red-500' : ''; ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $confirm_password_err; ?></span>
                </div>
                <div class="mb-6">
                    <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Peran (Role):</label>
                    <select name="role" id="role" class="block appearance-none w-full bg-white border border-gray-400 hover:border-gray-500 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($role_err)) ? 'border-red-500' : ''; ?>">
                        <option value="">Pilih Peran</option>
                        <option value="mahasiswa" <?php echo ($role == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                        <option value="asisten" <?php echo ($role == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                    </select>
                    <span class="text-red-500 text-xs italic"><?php echo $role_err; ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"><?php echo ($action == 'edit' ? 'Update' : 'Tambah'); ?></button>
                    <?php if($action == 'edit'): ?>
                        <a href="kelola_akun.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-md">
            <h3 class="text-2xl font-bold mb-4">Daftar Akun Pengguna</h3>
            <?php if(!empty($users_list)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">ID</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Username</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Nama</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Email</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Peran</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Terdaftar Sejak</th>
                            <th class="py-2 px-4 border-b text-left text-gray-600 font-bold uppercase text-sm">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users_list as $user): ?>
                        <tr>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['id']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['created_at']); ?></td>
                            <td class="py-2 px-4 border-b">
                                <a href="kelola_akun.php?action=edit&id=<?php echo $user['id']; ?>" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-3 rounded text-xs mr-2">Edit</a>
                                <?php if ($user['id'] != $_SESSION['id']): // Tidak bisa menghapus akun sendiri ?>
                                    <a href="kelola_akun.php?action=delete&id=<?php echo $user['id']; ?>" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-xs" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">Hapus</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="text-gray-600">Belum ada akun pengguna yang terdaftar.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>