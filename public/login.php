<?php
// Memulai session PHP
session_start();

// Cek jika user sudah login, redirect ke halaman dashboard yang sesuai
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if($_SESSION["role"] == "mahasiswa"){
        header("location: ../app/dashboard_mahasiswa.php"); // Nanti kita buat file ini
    } elseif($_SESSION["role"] == "asisten"){
        header("location: ../app/dashboard_asisten.php"); // Nanti kita buat file ini
    }
    exit;
}

// Include file konfigurasi database
require_once '../includes/config.php';

$username = $password = "";
$username_err = $password_err = $login_err = "";

// Memproses data ketika form disubmit
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validasi username
    if(empty(trim($_POST["username"]))){
        $username_err = "Mohon masukkan username.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Validasi password
    if(empty(trim($_POST["password"]))){
        $password_err = "Mohon masukkan password.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Cek kredensial
    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);

                // Cek jika username ada, kemudian verifikasi password
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password benar, mulai session
                            session_start();

                            // Simpan data ke session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;

                            // Redirect ke halaman dashboard yang sesuai
                            if($role == "mahasiswa"){
                                header("location: ../app/dashboard_mahasiswa.php");
                            } elseif($role == "asisten"){
                                header("location: ../app/dashboard_asisten.php");
                            }
                        } else{
                            // Password tidak valid
                            $login_err = "Username atau password salah.";
                        }
                    }
                } else{
                    // Username tidak ditemukan
                    $login_err = "Username atau password salah.";
                }
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
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
        <?php
        if(!empty($login_err)){
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . $login_err . '</div>';
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                <input type="text" name="username" id="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($username_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $username; ?>">
                <span class="text-red-500 text-xs italic"><?php echo $username_err; ?></span>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" name="password" id="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>">
                <span class="text-red-500 text-xs italic"><?php echo $password_err; ?></span>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Login</button>
            </div>
            <p class="text-center text-gray-600 text-sm mt-4">Belum punya akun? <a href="register.php" class="text-blue-500 hover:text-blue-800">Daftar di sini</a>.</p>
        </form>
    </div>
</body>
</html>