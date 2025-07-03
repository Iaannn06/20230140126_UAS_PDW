<?php
// Include file konfigurasi database
require_once '../includes/config.php';

$username = $password = $confirm_password = $nama = $email = $role = "";
$username_err = $password_err = $confirm_password_err = $nama_err = $email_err = $role_err = "";

// Memproses data ketika form disubmit
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validasi username
    if(empty(trim($_POST["username"]))){
        $username_err = "Mohon masukkan username.";
    } else {
        // Cek apakah username sudah ada di database
        $sql = "SELECT id FROM users WHERE username = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "Username ini sudah terdaftar.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Ada yang salah. Mohon coba lagi nanti.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validasi nama
    if(empty(trim($_POST["nama"]))){
        $nama_err = "Mohon masukkan nama lengkap.";
    } else{
        $nama = trim($_POST["nama"]);
    }

    // Validasi email
    if(empty(trim($_POST["email"]))){
        $email_err = "Mohon masukkan email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Format email tidak valid.";
    } else {
        // Cek apakah email sudah ada di database
        $sql = "SELECT id FROM users WHERE email = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "Email ini sudah terdaftar.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Ada yang salah. Mohon coba lagi nanti.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validasi password
    if(empty(trim($_POST["password"]))){
        $password_err = "Mohon masukkan password.";
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password harus minimal 6 karakter.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validasi konfirmasi password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Mohon konfirmasi password.";
    } else{
        if(empty($password_err) && ($password != trim($_POST["confirm_password"]))){
            $confirm_password_err = "Password tidak cocok.";
        }
    }

    // Validasi role
    if(empty(trim($_POST["role"]))){
        $role_err = "Mohon pilih peran (role).";
    } elseif(!in_array($_POST["role"], ['mahasiswa', 'asisten'])){
        $role_err = "Peran yang dipilih tidak valid.";
    } else {
        $role = trim($_POST["role"]);
    }

    // Cek input errors sebelum menyimpan ke database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($nama_err) && empty($email_err) && empty($role_err)){

        // Siapkan statement INSERT
        $sql = "INSERT INTO users (username, password, role, nama, email) VALUES (?, ?, ?, ?, ?)";

        if($stmt = mysqli_prepare($link, $sql)){
            // Bind parameter ke statement
            mysqli_stmt_bind_param($stmt, "sssss", $param_username, $param_password, $param_role, $param_nama, $param_email);

            // Set parameter
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Mengenkripsi password
            $param_role = $role;
            $param_nama = $nama;
            $param_email = $email;

            // Eksekusi statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect ke halaman login setelah registrasi berhasil
                header("location: login.php"); // Nanti kita akan buat login.php
            } else{
                echo "Ada yang salah. Mohon coba lagi nanti.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Tutup koneksi
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Registrasi Akun</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                <input type="text" name="username" id="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($username_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $username; ?>">
                <span class="text-red-500 text-xs italic"><?php echo $username_err; ?></span>
            </div>
            <div class="mb-4">
                <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
                <input type="text" name="nama" id="nama" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nama_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $nama; ?>">
                <span class="text-red-500 text-xs italic"><?php echo $nama_err; ?></span>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($email_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $email; ?>">
                <span class="text-red-500 text-xs italic"><?php echo $email_err; ?></span>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" name="password" id="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $password; ?>">
                <span class="text-red-500 text-xs italic"><?php echo $password_err; ?></span>
            </div>
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($confirm_password_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $confirm_password; ?>">
                <span class="text-red-500 text-xs italic"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="mb-6">
                <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Daftar sebagai:</label>
                <select name="role" id="role" class="block appearance-none w-full bg-white border border-gray-400 hover:border-gray-500 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($role_err)) ? 'border-red-500' : ''; ?>">
                    <option value="">Pilih Peran</option>
                    <option value="mahasiswa" <?php echo ($role == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="asisten" <?php echo ($role == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                </select>
                <span class="text-red-500 text-xs italic"><?php echo $role_err; ?></span>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Daftar</button>
            </div>
            <p class="text-center text-gray-600 text-sm mt-4">Sudah punya akun? <a href="login.php" class="text-blue-500 hover:text-blue-800">Login di sini</a>.</p>
        </form>
    </div>
</body>
</html>