<?php
session_start();
include 'koneksi.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = $_POST['password'];

    $query  = "SELECT * FROM pengguna WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) == 1){

        $user_data = mysqli_fetch_assoc($result);

        if($pass == $user_data['password']){

            $_SESSION['user_id']    = $user_data['id_pengguna'];
            $_SESSION['user_name']  = $user_data['nama_pengguna'];
            $_SESSION['user_email'] = $user_data['email'];
            $_SESSION['role']       = $user_data['role'];

            if($user_data['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();

        } else {
            $error = "Password salah!";
        }

    } else {
        $error = "Email tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, height=device-height">
    <title>Login - DBooking Sport</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: url('https://images.unsplash.com/photo-1529900748604-07564a03e7a6?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.8) 0%, rgba(59, 130, 246, 0.7) 50%, rgba(96, 165, 250, 0.6) 100%);
            z-index: 1;
            animation: gradientShift 10s ease infinite;
            background-size: 200% 200%;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .fullscreen-container {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .login-card {
            max-width: 1300px;
            width: 100%;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            display: flex;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            height: 85vh;
            max-height: 750px;
            min-height: 600px;
            animation: fadeInUp 0.6s ease;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.6);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .left-side {
            flex: 1.3;
            background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.98) 100%);
            padding: 40px 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        /* Animated floating sport icons background */
        .floating-sports {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 0;
        }

        .floating-sports span {
            position: absolute;
            font-size: 2rem;
            opacity: 0.08;
            animation: floatAround 20s linear infinite;
        }

        .floating-sports span:nth-child(1) { top: 10%; left: 5%; animation-duration: 18s; animation-delay: 0s; }
        .floating-sports span:nth-child(2) { top: 60%; left: 85%; animation-duration: 22s; animation-delay: 2s; }
        .floating-sports span:nth-child(3) { top: 70%; left: 15%; animation-duration: 25s; animation-delay: 5s; }
        .floating-sports span:nth-child(4) { top: 20%; left: 75%; animation-duration: 20s; animation-delay: 1s; }
        .floating-sports span:nth-child(5) { top: 85%; left: 45%; animation-duration: 28s; animation-delay: 3s; }
        .floating-sports span:nth-child(6) { top: 40%; left: 95%; animation-duration: 24s; animation-delay: 7s; }
        .floating-sports span:nth-child(7) { top: 15%; left: 50%; animation-duration: 30s; animation-delay: 4s; }
        .floating-sports span:nth-child(8) { top: 50%; left: 20%; animation-duration: 26s; animation-delay: 6s; }
        .floating-sports span:nth-child(9) { top: 30%; left: 110%; animation-duration: 19s; animation-delay: 8s; }
        .floating-sports span:nth-child(10) { top: 75%; left: 70%; animation-duration: 23s; animation-delay: 9s; }

        @keyframes floatAround {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(10deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        .logo-area {
            margin-bottom: 20px;
            animation: slideInLeft 0.6s ease;
            position: relative;
            z-index: 1;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
            transition: transform 0.3s ease;
        }

        .logo-icon:hover {
            transform: scale(1.05) rotate(5deg);
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.5);
            }
            50% {
                box-shadow: 0 0 0 12px rgba(37, 99, 235, 0);
            }
        }

        .logo-icon i {
            font-size: 24px;
            color: white;
            transition: transform 0.3s ease;
        }

        .logo-icon:hover i {
            transform: scale(1.1);
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 800;
        }

        .logo h1 .blue {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo h1 .dark {
            color: #0f172a;
        }

        .tagline {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(37, 99, 235, 0.08);
            padding: 8px 18px;
            border-radius: 50px;
            width: fit-content;
            transition: all 0.3s ease;
        }

        .tagline:hover {
            background: rgba(37, 99, 235, 0.15);
            transform: translateX(5px);
        }

        .hero-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        .main-title {
            font-size: 44px;
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
            margin-bottom: 20px;
        }

        .main-title span {
            background: linear-gradient(135deg, #2563eb, #3b82f6, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% auto;
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        .description {
            font-size: 15px;
            color: #475569;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        .description i {
            color: #3b82f6;
            margin-right: 8px;
        }

        .right-side {
            width: 420px;
            padding: 50px 45px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            animation: slideInRight 0.6s ease;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Decorative diagonal pattern on right side */
        .right-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #2563eb, #3b82f6, #60a5fa, #3b82f6, #2563eb);
            background-size: 200% 100%;
            animation: borderFlow 3s linear infinite;
        }

        @keyframes borderFlow {
            0% { background-position: 0% 0%; }
            100% { background-position: 200% 0%; }
        }

        /* Decorative dots pattern */
        .right-side::after {
            content: '';
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 100px;
            height: 100px;
            background-image: radial-gradient(#e2e8f0 2px, transparent 2px);
            background-size: 12px 12px;
            opacity: 0.5;
            pointer-events: none;
            border-radius: 20px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-icon {
            width: 75px;
            height: 75px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: float 3s ease-in-out infinite;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .login-icon:hover {
            transform: scale(1.1);
            border-radius: 35px;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .login-icon i {
            font-size: 34px;
            color: white;
        }

        .login-header h2 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #0f172a, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-header p {
            color: #64748b;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
            transition: color 0.3s ease;
        }

        .form-group:hover label {
            color: #2563eb;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i.input-icon {
            position: absolute;
            left: 16px;
            color: #94a3b8;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 48px 14px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 18px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #2563eb;
            background: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .input-wrapper input:focus + i.input-icon {
            color: #2563eb;
        }

        .toggle-pass {
            position: absolute;
            right: 16px;
            cursor: pointer;
            color: #94a3b8;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .toggle-pass:hover {
            color: #2563eb;
            transform: scale(1.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            border: none;
            border-radius: 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .register {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #475569;
            transition: all 0.3s ease;
        }

        .register a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
            display: inline-block;
        }

        .register a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            transition: width 0.3s ease;
        }

        .register a:hover::after {
            width: 100%;
        }

        .register a:hover {
            transform: translateX(3px);
        }

        .error-msg {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #dc2626;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .btn-login.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-login.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1100px) {
            .login-card {
                flex-direction: column;
                height: auto;
                max-height: 90vh;
                overflow-y: auto;
            }
            
            .right-side {
                width: 100%;
                padding: 35px;
            }
            
            .main-title {
                font-size: 36px;
            }
            
            .left-side {
                padding: 30px 35px;
            }
        }

        @media (max-width: 650px) {
            .left-side {
                padding: 25px 20px;
            }
            
            .right-side {
                padding: 30px 25px;
            }
            
            .main-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="fullscreen-container">
        <div class="login-card">
            <div class="left-side">
                <!-- Floating sport icons background -->
                <div class="floating-sports">
                    <span>⚽</span>
                    <span>🏀</span>
                    <span>🏸</span>
                    <span>🎾</span>
                    <span>🏐</span>
                    <span>⚾</span>
                    <span>🏓</span>
                    <span>🥊</span>
                    <span>🏊</span>
                    <span>🚴</span>
                </div>

                <div class="logo-area">
                    <div class="logo">
                        <div class="logo-icon">
                            <i class="fas fa-futbol"></i>
                        </div>
                        <h1><span class="blue">DBooking</span> <span class="dark">Sport</span></h1>
                    </div>
                    <div class="tagline">
                        <i class="fas fa-bolt"></i> Pesan Lapangan, Lebih Mudah, Lebih Cepat
                    </div>
                </div>

                <div class="hero-text">
                    <h2 class="main-title">
                        Booking Lapangan <br>
                        <span>Jadi Lebih Mudah!</span>
                    </h2>
                    <p class="description">
                        <i class="fas fa-check-circle"></i> Pesan lapangan olahraga favoritmu kapan saja<br>
                        dan di mana saja dengan mudah dan cepat.
                    </p>
                    <!-- FEATURE BADGES (24/7 Online, dll) TELAH DIHAPUS -->
                </div>
            </div>

            <div class="right-side">
                <div class="login-header">
                    <div class="login-icon">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <h2>Selamat Datang!</h2>
                    <p>Masuk ke akun DBooking Sport Anda</p>
                </div>

                <?php if($error): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <div class="form-group">
                        <label>Alamat Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" id="email" placeholder="contoh@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Kata Sandi</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="password" placeholder="Masukkan password Anda" required>
                            <i class="fas fa-eye toggle-pass" onclick="togglePassword()"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <i class="fas fa-arrow-right-to-bracket"></i> Masuk Sekarang
                    </button>
                </form>

                <div class="register">
                    <i class="fas fa-user-plus"></i> Belum punya akun? 
                    <a href="daftar.php">Daftar di sini <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-pass');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner"></i> Memproses...';
        });

        const inputs = document.querySelectorAll('.input-wrapper input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.01)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>