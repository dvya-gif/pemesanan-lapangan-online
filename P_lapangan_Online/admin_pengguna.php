<?php
session_start();
include 'koneksi.php';

// CEK ROLE - HARUS ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
$admin_name = $_SESSION['user_name'];

// PROSES TAMBAH PENGGUNA
if (isset($_POST['tambah_pengguna'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Cek email sudah terdaftar atau belum
    $cek_email = mysqli_query($conn, "SELECT email FROM pengguna WHERE email = '$email'");
    if (mysqli_num_rows($cek_email) > 0) {
        $error = "Email sudah terdaftar!";
    } else {
        $query = "INSERT INTO pengguna (nama_pengguna, email, password, no_hp, alamat, role) 
                  VALUES ('$nama', '$email', '$password', '$no_hp', '$alamat', '$role')";
        if (mysqli_query($conn, $query)) {
            $success = "Pengguna berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan pengguna: " . mysqli_error($conn);
        }
    }
}

// PROSES EDIT PENGGUNA
if (isset($_POST['edit_pengguna'])) {
    $id = (int)$_POST['id_pengguna'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Cek apakah user yang diedit adalah admin yang sedang login
    if ($id == $_SESSION['user_id'] && $role != 'admin') {
        $error = "Anda tidak dapat mengubah role sendiri!";
    } else {
        // Query update tanpa password jika password kosong
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $query = "UPDATE pengguna SET 
                      nama_pengguna = '$nama',
                      email = '$email',
                      password = '$password',
                      no_hp = '$no_hp',
                      alamat = '$alamat',
                      role = '$role'
                      WHERE id_pengguna = $id";
        } else {
            $query = "UPDATE pengguna SET 
                      nama_pengguna = '$nama',
                      email = '$email',
                      no_hp = '$no_hp',
                      alamat = '$alamat',
                      role = '$role'
                      WHERE id_pengguna = $id";
        }
        
        if (mysqli_query($conn, $query)) {
            $success = "Pengguna berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate pengguna: " . mysqli_error($conn);
        }
    }
}

// Hapus user (hanya user biasa, bukan admin)
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Cek dulu apakah user yang akan dihapus bukan admin
    $cek = mysqli_query($conn, "SELECT role FROM pengguna WHERE id_pengguna = $id");
    $user = mysqli_fetch_assoc($cek);
    if ($user && $user['role'] != 'admin') {
        mysqli_query($conn, "DELETE FROM pengguna WHERE id_pengguna = $id");
        $success = "Pengguna berhasil dihapus!";
    }
    header("Location: admin_pengguna.php");
    exit();
}

// Query ambil data pengguna
$pengguna = mysqli_query($conn, "SELECT id_pengguna, nama_pengguna, email, no_hp, alamat, role FROM pengguna ORDER BY id_pengguna DESC");

// Hitung total user
$total_user = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengguna WHERE role = 'user'");
$total_user_count = mysqli_fetch_assoc($total_user);

$total_admin = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengguna WHERE role = 'admin'");
$total_admin_count = mysqli_fetch_assoc($total_admin);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f0f9ff; 
            overflow-y: auto;
            height: auto;
            min-height: 100vh;
        }
        
        html {
            overflow-y: auto;
            height: auto;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #1e40af, #2563eb);
            padding: 25px 0;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
        .logo { text-align: center; margin-bottom: 35px; }
        .logo h1 { font-size: 24px; font-weight: 800; color: white; }
        .logo p { font-size: 11px; color: rgba(255,255,255,0.7); margin-top: 4px; }
        .nav-menu { list-style: none; }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            gap: 12px;
            font-size: 14px;
            transition: 0.3s;
            border-radius: 12px;
            margin: 0 10px;
        }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.2); color: white; transform: translateX(5px); }
        .nav-link i { width: 22px; }
        
        .main-content { 
            margin-left: 260px; 
            padding: 25px 35px; 
            min-height: 100vh;
            overflow-y: visible;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 15px 25px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }
        .header h1 { font-size: 24px; font-weight: 700; color: #1e293b; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 8px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            transition: 0.3s;
        }
        .logout-btn:hover { background: #dc2626; transform: translateY(-2px); }
        
        /* Stats Cards */
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .stats-card {
            background: white;
            padding: 20px 25px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            flex: 1;
            min-width: 150px;
            transition: 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        .stats-number {
            font-size: 32px;
            font-weight: 800;
            color: #2563eb;
        }
        .stats-label {
            font-size: 13px;
            color: #64748b;
            margin-top: 5px;
        }
        .stats-icon {
            float: right;
            font-size: 32px;
            color: #cbd5e1;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
            padding: 6px 14px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
        }
        .btn-warning:hover { background: #d97706; transform: translateY(-1px); }
        
        .btn-danger { 
            background: #dc2626; 
            color: white; 
            padding: 6px 14px; 
            border: none; 
            border-radius: 20px; 
            cursor: pointer; 
            font-size: 12px; 
            text-decoration: none; 
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
        }
        .btn-danger:hover { background: #b91c1c; transform: translateY(-1px); }
        
        /* Role Badges - Lebih rapi di satu kolom */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .role-admin {
            background: #2563eb;
            color: white;
        }
        .role-user {
            background: #10b981;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
            overflow-y: auto;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border-radius: 20px;
            animation: slideDown 0.3s;
            position: relative;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .modal-header h2 {
            font-size: 20px;
            color: #1e293b;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #94a3b8;
            transition: 0.3s;
        }
        
        .close:hover {
            color: #ef4444;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .table-container {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
            overflow-y: visible;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            min-width: 700px;
        }
        th { 
            text-align: left; 
            padding: 14px 16px; 
            background: #f8fafc; 
            font-size: 13px; 
            font-weight: 700; 
            color: #1e293b; 
            border-bottom: 2px solid #e2e8f0;
        }
        td { 
            padding: 14px 16px; 
            font-size: 13px; 
            color: #475569; 
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        tr:hover {
            background: #f8fafc;
        }
        
        .id-badge {
            background: #e2e8f0;
            color: #1e293b;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .alamat-cell {
            max-width: 200px;
            white-space: normal;
            word-wrap: break-word;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 16px;
        }
        
        .btn-tambah {
            float: right;
            margin-bottom: 20px;
        }
        
        /* Toolbar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            flex: 1;
            max-width: 350px;
        }
        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }
        .search-box button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 12px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) { 
            .sidebar { 
                width: 70px; 
                overflow-x: hidden;
            } 
            .sidebar .logo p, .sidebar .nav-link span { display: none; } 
            .main-content { 
                margin-left: 70px; 
                padding: 20px; 
            } 
            .alamat-cell { 
                max-width: 120px; 
            }
            th, td { 
                padding: 10px 12px; 
                font-size: 12px; 
            }
            .action-buttons { 
                flex-direction: column; 
                gap: 5px;
            }
            .btn-tambah { 
                float: none; 
                margin-bottom: 20px; 
                width: 100%; 
                justify-content: center; 
            }
            .toolbar {
                flex-direction: column;
            }
            .search-box {
                max-width: 100%;
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 15px;
            }
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            .user-info {
                width: 100%;
                justify-content: center;
            }
            .stats-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><h1>🏸 DBooking</h1><p>Admin Panel</p></div>
        <ul class="nav-menu">
            <li><a href="admin_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li><a href="admin_olahraga.php" class="nav-link"><i class="fas fa-medal"></i> <span>Jenis Olahraga</span></a></li>
            <li><a href="admin_lapangan.php" class="nav-link"><i class="fas fa-futbol"></i> <span>Kelola Lapangan</span></a></li>
            <li><a href="admin_jadwal.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Jadwal Lapangan</span></a></li>
            <li><a href="admin_pemesanan.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>Kelola Pemesanan</span></a></li>
            <li><a href="admin_verifikasi.php" class="nav-link"><i class="fas fa-credit-card"></i> <span>Verifikasi Pembayaran</span></a></li>
            <li><a href="admin_pengguna.php" class="nav-link active"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
            <li><a href="admin_laporan.php" class="nav-link"><i class="fas fa-file-alt"></i> <span>Laporan</span></a></li>
            <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-users"></i> Kelola Pengguna</h1>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <!-- Notifikasi -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistik -->
        <div class="stats-container">
            <div class="stats-card">
                <i class="fas fa-users stats-icon"></i>
                <div class="stats-number"><?php echo $total_user_count['total']; ?></div>
                <div class="stats-label"><i class="fas fa-user"></i> Total User</div>
            </div>
            <div class="stats-card">
                <i class="fas fa-user-shield stats-icon"></i>
                <div class="stats-number"><?php echo $total_admin_count['total']; ?></div>
                <div class="stats-label"><i class="fas fa-crown"></i> Total Admin</div>
            </div>
            <div class="stats-card">
                <i class="fas fa-users stats-icon"></i>
                <div class="stats-number"><?php echo $total_user_count['total'] + $total_admin_count['total']; ?></div>
                <div class="stats-label"><i class="fas fa-database"></i> Total Pengguna</div>
            </div>
        </div>
        
        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Cari nama, email, atau no HP..." onkeyup="searchTable()">
                <button onclick="searchTable()"><i class="fas fa-search"></i></button>
            </div>
            <button class="btn-primary btn-tambah" onclick="openTambahModal()">
                <i class="fas fa-plus"></i> Tambah Pengguna Baru
            </button>
        </div>
        
        <div class="table-container">
            <table id="userTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>No HP</th>
                        <th>Alamat</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pengguna && mysqli_num_rows($pengguna) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($pengguna)): ?>
                        <tr class="user-row">
                            <td><span class="id-badge">#<?php echo $row['id_pengguna']; ?></span></td>
                            <td class="nama-cell"><?php echo htmlspecialchars($row['nama_pengguna']); ?></td>
                            <td class="email-cell"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="hp-cell"><?php echo htmlspecialchars($row['no_hp'] ?: '-'); ?></td>
                            <td class="alamat-cell"><?php echo htmlspecialchars($row['alamat'] ?: '-'); ?></td>
                            <td class="role-cell">
                                <?php if($row['role'] == 'admin'): ?>
                                    <span class="role-badge role-admin">
                                        <i class="fas fa-crown"></i> Admin
                                    </span>
                                <?php else: ?>
                                    <span class="role-badge role-user">
                                        <i class="fas fa-user"></i> User
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="openEditModal(<?php echo $row['id_pengguna']; ?>)" class="btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <?php if($row['role'] != 'admin'): ?>
                                        <a href="?hapus=<?php echo $row['id_pengguna']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus user <?php echo htmlspecialchars($row['nama_pengguna']); ?>?')">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px; display:inline-flex; align-items:center; gap:4px;">
                                            <i class="fas fa-lock"></i> Tidak bisa dihapus
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <p>Belum ada data pengguna</p>
                                <small style="color: #94a3b8;">Pengguna akan muncul setelah registrasi</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- MODAL TAMBAH PENGGUNA -->
    <div id="tambahModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Tambah Pengguna Baru</h2>
                <span class="close" onclick="closeTambahModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Lengkap <span style="color:red;">*</span></label>
                    <input type="text" name="nama" required placeholder="Masukkan nama lengkap">
                </div>
                <div class="form-group">
                    <label>Email <span style="color:red;">*</span></label>
                    <input type="email" name="email" required placeholder="Masukkan email">
                </div>
                <div class="form-group">
                    <label>Password <span style="color:red;">*</span></label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter">
                </div>
                <div class="form-group">
                    <label>No Handphone</label>
                    <input type="text" name="no_hp" placeholder="Contoh: 08123456789">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" rows="3" placeholder="Masukkan alamat lengkap"></textarea>
                </div>
                <div class="form-group">
                    <label>Role <span style="color:red;">*</span></label>
                    <select name="role" required>
                        <option value="user">👤 User (Biasa)</option>
                        <option value="admin">👑 Admin</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="tambah_pengguna" class="btn-primary" style="flex:1; justify-content: center;">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <button type="button" onclick="closeTambahModal()" class="btn-danger" style="flex:1; justify-content: center;">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL EDIT PENGGUNA -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Edit Pengguna</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="id_pengguna" id="edit_id">
                <div class="form-group">
                    <label>Nama Lengkap <span style="color:red;">*</span></label>
                    <input type="text" name="nama" id="edit_nama" required>
                </div>
                <div class="form-group">
                    <label>Email <span style="color:red;">*</span></label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>Password (Kosongkan jika tidak ingin mengubah)</label>
                    <input type="password" name="password" placeholder="Minimal 6 karakter">
                </div>
                <div class="form-group">
                    <label>No Handphone</label>
                    <input type="text" name="no_hp" id="edit_no_hp">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" id="edit_alamat" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Role <span style="color:red;">*</span></label>
                    <select name="role" id="edit_role" required>
                        <option value="user">👤 User (Biasa)</option>
                        <option value="admin">👑 Admin</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit_pengguna" class="btn-primary" style="flex:1; justify-content: center;">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn-danger" style="flex:1; justify-content: center;">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Data pengguna untuk keperluan edit
        const usersData = <?php 
            $data = [];
            mysqli_data_seek($pengguna, 0);
            while($row = mysqli_fetch_assoc($pengguna)) {
                $data[$row['id_pengguna']] = $row;
            }
            echo json_encode($data);
        ?>;
        
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('userTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const namaCell = row.querySelector('.nama-cell');
                const emailCell = row.querySelector('.email-cell');
                const hpCell = row.querySelector('.hp-cell');
                
                if (namaCell || emailCell || hpCell) {
                    const nama = namaCell ? namaCell.textContent.toLowerCase() : '';
                    const email = emailCell ? emailCell.textContent.toLowerCase() : '';
                    const hp = hpCell ? hpCell.textContent.toLowerCase() : '';
                    
                    if (nama.includes(filter) || email.includes(filter) || hp.includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }
        }
        
        function openTambahModal() {
            document.getElementById('tambahModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeTambahModal() {
            document.getElementById('tambahModal').style.display = 'none';
            document.body.style.overflow = '';
        }
        
        function openEditModal(id) {
            const user = usersData[id];
            if (user) {
                document.getElementById('edit_id').value = user.id_pengguna;
                document.getElementById('edit_nama').value = user.nama_pengguna;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_no_hp').value = user.no_hp || '';
                document.getElementById('edit_alamat').value = user.alamat || '';
                document.getElementById('edit_role').value = user.role;
                document.getElementById('editModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('editForm').reset();
            document.body.style.overflow = '';
        }
        
        // Tutup modal jika klik di luar modal
        window.onclick = function(event) {
            const tambahModal = document.getElementById('tambahModal');
            const editModal = document.getElementById('editModal');
            if (event.target == tambahModal) {
                closeTambahModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }
        
        // Hapus alert setelah beberapa detik
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 3000);
        
        // Enter key untuk search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTable();
            }
        });
    </script>
</body>
</html>