<?php
session_start();
include 'koneksi.php';

// CEK ROLE - HARUS ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM admin WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id_admin'];
            $_SESSION['admin_name'] = $admin['nama_lengkap'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - DBooking Sport</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #1e40af, #2563eb); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card { max-width: 420px; width: 100%; background: white; border-radius: 32px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; }
        .login-header { background: linear-gradient(135deg, #1e40af, #2563eb); padding: 30px; text-align: center; color: white; }
        .login-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 5px; }
        .login-header p { font-size: 13px; opacity: 0.9; }
        .login-body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 14px; font-size: 14px; transition: 0.3s; }
        .form-group input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-login { width: 100%; padding: 14px; background: #2563eb; color: white; border: none; border-radius: 40px; font-weight: 700; font-size: 14px; cursor: pointer; transition: 0.3s; }
        .btn-login:hover { background: #1d4ed8; transform: translateY(-2px); }
        .alert-error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 14px; margin-bottom: 20px; font-size: 13px; text-align: center; border-left: 4px solid #dc2626; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #64748b; text-decoration: none; font-size: 13px; }
        .back-link:hover { color: #2563eb; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1>🏸 Admin Panel</h1>
            <p>DBooking Sport Management System</p>
        </div>
        <div class="login-body">
            <?php if($error): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" placeholder="Masukkan username" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
            <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Halaman User</a>
        </div>
    </div>
</body>
</html>