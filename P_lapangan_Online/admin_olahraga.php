<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['user_name'];

// Tambah Olahraga
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah'])) {
    $nama_olahraga = mysqli_real_escape_string($conn, $_POST['nama_olahraga']);
    
    mysqli_query($conn, "INSERT INTO jenis_olahraga (nama_olahraga) VALUES ('$nama_olahraga')");
    header("Location: admin_olahraga.php");
    exit();
}

// Edit Olahraga
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $nama_olahraga = mysqli_real_escape_string($conn, $_POST['nama_olahraga']);
    
    mysqli_query($conn, "UPDATE jenis_olahraga SET nama_olahraga='$nama_olahraga' WHERE id_olahraga=$id");
    header("Location: admin_olahraga.php");
    exit();
}

// Hapus Olahraga
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Cek apakah ada lapangan yang menggunakan olahraga ini
    $cek = mysqli_query($conn, "SELECT COUNT(*) as total FROM lapangan WHERE id_olahraga=$id");
    $row = mysqli_fetch_assoc($cek);
    if ($row['total'] > 0) {
        $error = "Tidak bisa menghapus! Masih ada " . $row['total'] . " lapangan yang menggunakan olahraga ini.";
    } else {
        mysqli_query($conn, "DELETE FROM jenis_olahraga WHERE id_olahraga=$id");
        $success = "Olahraga berhasil dihapus.";
    }
    header("Location: admin_olahraga.php?error=" . urlencode($error) . "&success=" . urlencode($success));
    exit();
}

// Query dengan jumlah lapangan per olahraga
$olahraga = mysqli_query($conn, "SELECT 
                                    jo.id_olahraga, 
                                    jo.nama_olahraga,
                                    COUNT(l.id_lapangan) as jumlah_lapangan
                                FROM jenis_olahraga jo 
                                LEFT JOIN lapangan l ON jo.id_olahraga = l.id_olahraga 
                                GROUP BY jo.id_olahraga 
                                ORDER BY jo.id_olahraga ASC");

$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jenis Olahraga - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f9ff; }
        .sidebar {
            position: fixed; left: 0; top: 0; width: 260px; height: 100vh;
            background: linear-gradient(180deg, #1e40af, #2563eb);
            padding: 25px 0; overflow-y: auto;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
        .logo { text-align: center; margin-bottom: 35px; }
        .logo h1 { font-size: 24px; font-weight: 800; color: white; }
        .logo p { font-size: 11px; color: rgba(255,255,255,0.7); margin-top: 4px; }
        .nav-menu { list-style: none; }
        .nav-link {
            display: flex; align-items: center; padding: 12px 25px;
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
        .main-content { margin-left: 260px; padding: 25px 35px; }
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
        }
        .logout-btn:hover { background: #dc2626; transform: translateY(-2px); }
        .btn-primary {
            background: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            margin-bottom: 20px;
        }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); }
        .btn-edit {
            background: #f59e0b;
            color: white;
            padding: 5px 12px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }
        .btn-delete {
            background: #dc2626;
            color: white;
            padding: 5px 12px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
        }
        .table-container {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 700; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-blue { background: #dbeafe; color: #2563eb; }
        .badge-green { background: #dcfce7; color: #16a34a; }
        .alert-success { background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 12px; margin-bottom: 20px; }
        .alert-error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 20px; }
        .modal {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            z-index: 1000; align-items: center; justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-card {
            background: white; border-radius: 24px; max-width: 450px;
            width: 90%; overflow: hidden;
        }
        .modal-header {
            padding: 20px; background: linear-gradient(135deg, #1e40af, #2563eb);
            color: white; display: flex; justify-content: space-between;
        }
        .modal-header span { font-size: 28px; cursor: pointer; }
        .modal-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; }
        .form-group input {
            width: 100%; padding: 10px; border: 1.5px solid #e2e8f0;
            border-radius: 12px; font-family: 'Inter', sans-serif;
        }
        .btn-simpan {
            width: 100%; padding: 12px; background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white; border: none; border-radius: 40px; cursor: pointer;
            font-weight: 700; transition: 0.3s;
        }
        .btn-simpan:hover { transform: translateY(-2px); }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo p, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><h1>🏸 DBooking</h1><p>Admin Panel</p></div>
        <ul class="nav-menu">
            <li><a href="admin_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li><a href="admin_olahraga.php" class="nav-link active"><i class="fas fa-medal"></i> <span>Jenis Olahraga</span></a></li>
            <li><a href="admin_lapangan.php" class="nav-link"><i class="fas fa-futbol"></i> <span>Kelola Lapangan</span></a></li>
            <li><a href="admin_jadwal.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Jadwal Lapangan</span></a></li>
            <li><a href="admin_pemesanan.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Kelola Pemesanan</span></a></li>
            <li><a href="admin_verifikasi.php" class="nav-link"><i class="fas fa-credit-card"></i> <span>Verifikasi Pembayaran</span></a></li>
            <li><a href="admin_pengguna.php" class="nav-link"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
            <li><a href="admin_laporan.php" class="nav-link"><i class="fas fa-file-alt"></i> <span>Laporan</span></a></li>
            <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-medal"></i> Jenis Olahraga</h1>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if($success): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <button class="btn-primary" onclick="openTambahModal()"><i class="fas fa-plus"></i> Tambah Olahraga</button>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Olahraga</th>
                        <th>Jumlah Lapangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($olahraga) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($olahraga)): ?>
                        <tr>
                            <td><?php echo $row['id_olahraga']; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_olahraga']); ?></td>
                            <td>
                                <?php if($row['jumlah_lapangan'] > 0): ?>
                                    <span class="badge badge-green"><?php echo $row['jumlah_lapangan']; ?> Lapangan</span>
                                <?php else: ?>
                                    <span class="badge badge-blue">0 Lapangan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn-edit" onclick="openEditModal(<?php echo $row['id_olahraga']; ?>, '<?php echo addslashes($row['nama_olahraga']); ?>')">Edit</button>
                                <a href="?hapus=<?php echo $row['id_olahraga']; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus olahraga ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px;">
                                <i class="fas fa-info-circle" style="font-size: 40px; color: #94a3b8; margin-bottom: 10px; display: block;"></i>
                                <strong>Belum ada jenis olahraga</strong><br>
                                <small>Silakan tambah jenis olahraga terlebih dahulu.</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div id="tambahModal" class="modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Tambah Olahraga</h3>
                <span onclick="closeModal('tambahModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="tambah" value="1">
                    <div class="form-group">
                        <label>Nama Olahraga</label>
                        <input type="text" name="nama_olahraga" required placeholder="Contoh: Renang, Voli, Golf, Tenis Meja">
                    </div>
                    <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Olahraga</h3>
                <span onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="edit" value="1">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label>Nama Olahraga</label>
                        <input type="text" name="nama_olahraga" id="edit_nama" required>
                    </div>
                    <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Update</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openTambahModal() { 
            document.getElementById('tambahModal').classList.add('show'); 
        }
        function openEditModal(id, nama) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('editModal').classList.add('show');
        }
        function closeModal(id) { 
            document.getElementById(id).classList.remove('show'); 
        }
        window.onclick = function(e) { 
            if(e.target.classList.contains('modal')) e.target.classList.remove('show'); 
        }
    </script>
</body>
</html>