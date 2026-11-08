<?php
include 'koneksi.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp    = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $alamat   = mysqli_real_escape_string($conn, $_POST['alamat']);
    $pass     = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_pass = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    if (empty($pass) || empty($confirm_pass)) {
        $error = "Password tidak boleh kosong!";
    } 
    elseif (strlen($pass) < 6) {
        $error = "Password minimal 6 karakter!";
    }
    elseif ($pass != $confirm_pass) {
        $error = "Password tidak sama!";
    } else {
        $cek = mysqli_query($conn, "SELECT * FROM pengguna WHERE email = '$email'");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            // LANGSUNG SIMPAN PASSWORD TANPA ENKRIPSI
            $query = "INSERT INTO pengguna (nama_pengguna, email, no_hp, alamat, password) 
                      VALUES ('$nama', '$email', '$no_hp', '$alamat', '$pass')";
            
            if (mysqli_query($conn, $query)) {
                $success = "Pendaftaran berhasil! Silakan <a href='login.php'>Login</a>";
            } else {
                $error = "Gagal: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Akun - DBooking Sport</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, height=device-height, initial-scale=1.0, user-scalable=no">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
        }

        .container {
            background: white;
            padding: 30px 35px;
            border-radius: 28px;
            width: 480px;
            max-width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transition: transform 0.2s ease;
            max-height: 95vh;
            overflow-y: auto;
        }

        .container::-webkit-scrollbar {
            width: 4px;
        }
        .container::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .container::-webkit-scrollbar-thumb {
            background: #2563eb;
            border-radius: 10px;
        }

        .container:hover {
            transform: translateY(-3px);
        }

        h2 {
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            color: #1e40af;
            margin-bottom: 8px;
        }

        p.subtitle {
            text-align: center;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
            font-weight: 400;
        }

        label {
            font-weight: 600;
            color: #1e293b;
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
        }

        input {
            width: 100%;
            padding: 10px 14px;
            margin: 6px 0 14px 0;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.2s ease;
            background-color: #ffffff;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            margin-top: 8px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .error {
            color: #dc2626;
            background: #fee2e2;
            padding: 10px 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 500;
            border-left: 4px solid #dc2626;
        }

        .success {
            color: #16a34a;
            background: #dcfce7;
            padding: 10px 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 500;
            border-left: 4px solid #16a34a;
        }

        .success a {
            color: #16a34a;
            font-weight: 700;
            text-decoration: underline;
        }

        .login-link {
            text-align: center;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
        }

        .login-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .brand-icon {
            text-align: center;
            font-size: 40px;
            margin-bottom: 8px;
        }

        @media (max-width: 500px) {
            .container {
                padding: 20px 25px;
                max-height: 98vh;
            }
            
            h2 {
                font-size: 24px;
            }
            
            .brand-icon {
                font-size: 32px;
            }
            
            input {
                padding: 8px 12px;
                margin: 4px 0 10px 0;
            }
            
            button {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="brand-icon">🏸</div>
        <h2>Daftar Akun Baru</h2>
        <p class="subtitle">Bergabunglah dengan DBooking Sport</p>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" required placeholder="Masukkan nama lengkap" value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
            
            <label>Email</label>
            <input type="email" name="email" required placeholder="contoh@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            
            <label>Nomor HP</label>
            <input type="text" name="no_hp" required placeholder="Masukkan nomor HP" value="<?php echo isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : ''; ?>">
            
            <label>Alamat</label>
            <input type="text" name="alamat" required placeholder="Masukkan alamat" value="<?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?>">
            
            <label>Password</label>
            <input type="password" name="password" required placeholder="Buat password (min. 6 karakter)">
            
            <label>Konfirmasi Password</label>
            <input type="password" name="confirm_password" required placeholder="Konfirmasi password">
            
            <button type="submit">Daftar Sekarang</button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
</body>
</html>