<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengguna WHERE id_pengguna = $user_id"));

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    
    $update = mysqli_query($conn, "UPDATE pengguna SET nama_pengguna='$nama', no_hp='$no_hp', alamat='$alamat' WHERE id_pengguna=$user_id");
    
    if ($update) {
        $_SESSION['user_name'] = $nama;
        $success = "Profil berhasil diperbarui!";
        $user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengguna WHERE id_pengguna = $user_id"));
    } else {
        $error = "Gagal memperbarui profil!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - DBooking Sport</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f9ff;
        }

        /* SIDEBAR - BIRU GRADIENT */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #1e40af 0%, #2563eb 100%);
            padding: 25px 0;
            z-index: 100;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }

        .logo {
            padding: 0 25px;
            margin-bottom: 35px;
            text-align: center;
        }
        .logo h1 { font-size: 24px; font-weight: 800; color: white; }
        .logo p { font-size: 11px; color: rgba(255,255,255,0.7); margin-top: 4px; }

        .nav-menu { list-style: none; }
        .nav-item { margin-bottom: 5px; }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            gap: 12px;
            font-size: 14px;
            transition: all 0.3s;
            border-radius: 12px;
            margin: 0 10px;
        }
        .nav-link:hover { background: rgba(255,255,255,0.15); color: white; transform: translateX(5px); }
        .nav-link.active { background: rgba(255,255,255,0.2); color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .nav-link i { width: 22px; }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 260px;
            padding: 25px 35px;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 15px 25px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            position: relative;
        }
        .header h1:before {
            content: '';
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background: #2563eb;
            border-radius: 4px;
        }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-info span { color: #475569; font-weight: 500; }
        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 8px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
        }
        .logout-btn:hover { background: #dc2626; transform: translateY(-2px); }

        /* PROFILE SECTION */
        .profile-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .profile-sidebar {
            flex: 1;
            min-width: 280px;
        }

        .profile-card {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.3s;
        }
        .profile-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        }
        .profile-avatar {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            padding: 30px;
            color: white;
            text-align: center;
        }
        .profile-avatar i {
            font-size: 80px;
        }
        .profile-avatar h2 {
            font-size: 20px;
            margin-top: 15px;
            font-weight: 700;
        }
        .profile-avatar p {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }
        .profile-info {
            padding: 25px;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }
        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        /* Style khusus untuk ID Pengguna */
        .info-row.user-id-row {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 12px;
            padding: 12px 15px;
            margin-top: 10px;
            border-bottom: none;
        }
        .user-id-value {
            font-family: monospace;
            font-size: 14px;
            font-weight: 700;
            color: #1e40af;
            letter-spacing: 0.5px;
        }
        .badge-id {
            background: #2563eb;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
        }
        .tips-card {
            background: #eff6ff;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #dbeafe;
        }
        .tips-card h4 {
            font-size: 14px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tips-card p {
            font-size: 12px;
            color: #475569;
            line-height: 1.5;
        }

        .form-container {
            flex: 2;
            min-width: 350px;
        }

        .form-card {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.3s;
        }
        .form-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        }
        .form-header {
            padding: 20px 25px;
            background: #fafcff;
            border-bottom: 2px solid #f1f5f9;
        }
        .form-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-header p {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        .form-body {
            padding: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        .form-group label i {
            margin-right: 8px;
            color: #2563eb;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: white;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .form-group input:disabled {
            background: #f8fafc;
            color: #94a3b8;
            cursor: not-allowed;
        }
        .form-group .user-id-display {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1e40af;
            font-weight: 600;
            font-family: monospace;
        }
        .form-group small {
            display: block;
            margin-top: 6px;
            font-size: 11px;
            color: #94a3b8;
        }
        .btn-save {
            width: 100%;
            padding: 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
        }
        .btn-save:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            border-left: 4px solid #16a34a;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .footer {
            text-align: center;
            padding: 30px;
            margin-top: 40px;
            color: #94a3b8;
            font-size: 12px;
            border-top: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo p, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
            .profile-container { flex-direction: column; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <h1>🏸 DBooking</h1>
            <p>Sport</p>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="cari_lapangan.php" class="nav-link"><i class="fas fa-search"></i> <span>Cari Lapangan</span></a></li>
            <li class="nav-item"><a href="pemesanan_saya.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Pemesanan Saya</span></a></li>
            <li class="nav-item"><a href="riwayat_transaksi.php" class="nav-link"><i class="fas fa-history"></i> <span>Riwayat Transaksi</span></a></li>
            <li class="nav-item"><a href="profil_saya.php" class="nav-link active"><i class="fas fa-user"></i> <span>Profil</span></a></li>
            <li class="nav-item"><a href="bantuan.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Bantuan</span></a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Keluar</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-user-circle" style="margin-right: 10px; color:#2563eb;"></i> Profil Saya</h1>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
            </div>
        </div>

        <!-- ALERT MESSAGES -->
        <?php if($success): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <div class="profile-container">
            <!-- LEFT COLUMN - PROFILE INFO -->
            <div class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                        <h2><?php echo htmlspecialchars($user_data['nama_pengguna']); ?></h2>
                        <p>Member</p>
                    </div>
                    <div class="profile-info">
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-id-card"></i> ID Pengguna</span>
                            <span class="info-value user-id-value">
                                <i class="fas fa-hashtag"></i> <?php echo $user_data['id_pengguna']; ?>
                                <span class="badge-id">Primary</span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-phone"></i> No. HP</span>
                            <span class="info-value"><?php echo $user_data['no_hp'] ? htmlspecialchars($user_data['no_hp']) : '-'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-map-marker-alt"></i> Alamat</span>
                            <span class="info-value"><?php echo $user_data['alamat'] ? htmlspecialchars($user_data['alamat']) : '-'; ?></span>
                        </div>
                        <!-- Baris tambahan untuk menampilkan ID Pengguna dengan style khusus -->
                        <div class="info-row user-id-row">
                            <span class="info-label"><i class="fas fa-qrcode"></i> Kode Unik</span>
                            <span class="info-value">
                                <code style="background: #e0e7ff; padding: 4px 8px; border-radius: 8px;">DB-<?php echo str_pad($user_data['id_pengguna'], 6, '0', STR_PAD_LEFT); ?></code>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="tips-card">
                    <h4><i class="fas fa-lightbulb"></i> Tips Keamanan</h4>
                    <p>🔒 Jaga kerahasiaan akun Anda.<br>
                    🔒 Jangan berikan password kepada siapapun.<br>
                    🔒 Gunakan password yang kuat dan unik.<br>
                    🔒 Segera laporkan jika ada aktivitas mencurigakan.</p>
                    <p style="margin-top: 10px; font-size: 11px; color: #1e40af;">
                        <i class="fas fa-shield-alt"></i> ID Pengguna: <?php echo $user_data['id_pengguna']; ?>
                    </p>
                </div>
            </div>

            <!-- RIGHT COLUMN - EDIT FORM -->
            <div class="form-container">
                <div class="form-card">
                    <div class="form-header">
                        <h3><i class="fas fa-edit" style="color:#2563eb;"></i> Edit Profil</h3>
                        <p>Perbarui informasi pribadi Anda di sini</p>
                    </div>
                    <div class="form-body">
                        <form method="POST" id="profileForm">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Nama Lengkap</label>
                                <input type="text" name="nama" value="<?php echo htmlspecialchars($user_data['nama_pengguna']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-id-card"></i> ID Pengguna</label>
                                <input type="text" class="user-id-display" value="<?php echo $user_data['id_pengguna']; ?> (DB-<?php echo str_pad($user_data['id_pengguna'], 6, '0', STR_PAD_LEFT); ?>)" disabled>
                                <small><i class="fas fa-info-circle"></i> ID Pengguna bersifat unik dan tidak dapat diubah</small>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                                <small><i class="fas fa-info-circle"></i> Email tidak dapat diubah</small>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Nomor HP</label>
                                <input type="text" name="no_hp" value="<?php echo htmlspecialchars($user_data['no_hp']); ?>" placeholder="Masukkan nomor HP" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                                <input type="text" name="alamat" value="<?php echo htmlspecialchars($user_data['alamat']); ?>" placeholder="Masukkan alamat lengkap" required>
                            </div>
                            <button type="submit" class="btn-save" id="saveBtn">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2026 DBooking Sport - Pemesanan Lapangan Online | ID Anda: <?php echo $user_data['id_pengguna']; ?></p>
        </div>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div id="toastNotif" style="position: fixed; bottom: 30px; right: 30px; background: #1e293b; color: white; padding: 12px 24px; border-radius: 40px; font-size: 13px; font-weight: 500; box-shadow: 0 8px 20px rgba(0,0,0,0.15); transform: translateX(400px); transition: transform 0.3s ease; z-index: 1000;"></div>

    <script>
        // Toast notification
        let toastTimeout = null;
        function showToast(message, isSuccess = true) {
            const toast = document.getElementById('toastNotif');
            toast.textContent = message;
            toast.style.background = isSuccess ? '#16a34a' : '#dc2626';
            toast.style.transform = 'translateX(0)';
            
            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.style.transform = 'translateX(400px)';
            }, 2500);
        }

        // Form submit dengan efek loading
        const form = document.getElementById('profileForm');
        const saveBtn = document.getElementById('saveBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Menyimpan...';
                saveBtn.disabled = true;
                
                setTimeout(() => {
                    // Submit akan dilanjutkan
                }, 100);
            });
        }

        // Validasi input realtime (hanya angka untuk No HP)
        const noHpInput = document.querySelector('input[name="no_hp"]');
        if (noHpInput) {
            noHpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }

        // Tampilkan toast jika ada alert
        <?php if($success): ?>
        showToast('✅ <?php echo $success; ?>', true);
        <?php endif; ?>
        
        <?php if($error): ?>
        showToast('❌ <?php echo $error; ?>', false);
        <?php endif; ?>
    </script>
</body>
</html>