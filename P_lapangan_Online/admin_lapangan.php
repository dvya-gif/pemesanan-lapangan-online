<?php
session_start();
include 'koneksi.php';

// CEK ROLE - HARUS ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['user_name'];

// Cek apakah ada jenis olahraga
$cek_olahraga = mysqli_query($conn, "SELECT COUNT(*) as total FROM jenis_olahraga");
$total_olahraga = mysqli_fetch_assoc($cek_olahraga)['total'];

// Tambah Lapangan (TANPA GAMBAR)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah'])) {
    $nama_lapangan = mysqli_real_escape_string($conn, $_POST['nama_lapangan']);
    $id_olahraga = (int)$_POST['id_olahraga'];
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $harga_per_jam = (int)$_POST['harga_per_jam'];
    
    $query = "INSERT INTO lapangan (nama_lapangan, id_olahraga, lokasi, harga_per_jam) 
              VALUES ('$nama_lapangan', $id_olahraga, '$lokasi', $harga_per_jam)";
    mysqli_query($conn, $query);
    header("Location: admin_lapangan.php");
    exit();
}
// Edit Lapangan (TANPA GAMBAR)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $nama_lapangan = mysqli_real_escape_string($conn, $_POST['nama_lapangan']);
    $id_olahraga = (int)$_POST['id_olahraga'];
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $harga_per_jam = (int)$_POST['harga_per_jam'];
    
    $query = "UPDATE lapangan SET nama_lapangan='$nama_lapangan', id_olahraga=$id_olahraga, 
              lokasi='$lokasi', harga_per_jam=$harga_per_jam WHERE id_lapangan=$id";
    mysqli_query($conn, $query);
    header("Location: admin_lapangan.php");
    exit();
}
// Hapus Lapangan
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM lapangan WHERE id_lapangan=$id");
    header("Location: admin_lapangan.php");
    exit();
}

$lapangan = mysqli_query($conn, "SELECT l.*, jo.nama_olahraga FROM lapangan l JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga ORDER BY l.id_lapangan ASC");
$olahraga_list = mysqli_query($conn, "SELECT * FROM jenis_olahraga ORDER BY nama_olahraga");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapangan - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f9ff; }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #1e40af, #2563eb);
            padding: 25px 0;
            overflow-y: auto;
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
            transition: 0.3s;
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
            text-decoration: none;
            transition: 0.3s;
            margin-bottom: 20px;
        }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
        .btn-primary:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-edit {
            background: #f59e0b;
            color: white;
            padding: 6px 14px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
            text-decoration: none;
        }
        .btn-edit:hover { background: #d97706; transform: translateY(-2px); }
        .btn-delete {
            background: #dc2626;
            color: white;
            padding: 6px 14px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
            text-decoration: none;
        }
        .btn-delete:hover { background: #b91c1c; transform: translateY(-2px); }
        
        .table-container {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th { 
            text-align: left; 
            padding: 14px 15px; 
            background: #f8fafc; 
            font-size: 13px; 
            font-weight: 700; 
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
        }
        td { 
            padding: 12px 15px; 
            font-size: 13px; 
            color: #475569; 
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        tr:hover { background: #f8fafc; }
        .lapangan-name { font-weight: 700; color: #1e293b; }
        .price { font-weight: 700; color: #2563eb; }
        
        .alert-warning {
            background: #fef3c7;
            color: #d97706;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #d97706;
        }
        .alert-warning a {
            color: #d97706;
            font-weight: 600;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-card {
            background: white;
            border-radius: 24px;
            max-width: 500px;
            width: 90%;
            overflow: hidden;
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: white;
        }
        .modal-header h3 { font-size: 18px; font-weight: 700; color: white; }
        .modal-header span { font-size: 28px; cursor: pointer; color: white; opacity: 0.8; }
        .modal-header span:hover { opacity: 1; }
        .modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: #1e293b; }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 10px 14px; 
            border: 1.5px solid #e2e8f0; 
            border-radius: 12px; 
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .btn-simpan {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 700;
            transition: 0.3s;
            font-size: 14px;
        }
        .btn-simpan:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(34,197,94,0.3); }
        
        .link-olahraga {
            display: inline-block;
            margin-top: 5px;
            font-size: 11px;
        }
        .link-olahraga a {
            color: #2563eb;
            text-decoration: none;
        }
        .link-olahraga a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) { 
            .sidebar { width: 70px; } 
            .sidebar .logo p, .sidebar .nav-link span { display: none; } 
            .main-content { margin-left: 70px; padding: 20px; }
            .action-buttons { flex-direction: column; gap: 5px; }
            .btn-edit, .btn-delete { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><h1>🏸 DBooking</h1><p>Admin Panel</p></div>
        <ul class="nav-menu">
            <li><a href="admin_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li><a href="admin_olahraga.php" class="nav-link"><i class="fas fa-medal"></i> <span>Jenis Olahraga</span></a></li>
            <li><a href="admin_lapangan.php" class="nav-link active"><i class="fas fa-futbol"></i> <span>Kelola Lapangan</span></a></li>
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
            <h1><i class="fas fa-futbol"></i> Kelola Lapangan</h1>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if($total_olahraga == 0): ?>
            <div class="alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Belum ada Jenis Olahraga!</strong> Silakan 
                <a href="admin_olahraga.php">tambah jenis olahraga</a> terlebih dahulu sebelum menambah lapangan.
            </div>
        <?php endif; ?>

        <?php if($total_olahraga > 0): ?>
            <button class="btn-primary" onclick="openTambahModal()"><i class="fas fa-plus"></i> Tambah Lapangan</button>
        <?php else: ?>
            <button class="btn-primary" disabled><i class="fas fa-plus"></i> Tambah Lapangan (Buat Olahraga Dulu)</button>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Lapangan</th>
                        <th>Jenis Olahraga</th>
                        <th>Lokasi</th>
                        <th>Harga/Jam</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($lapangan) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($lapangan)): ?>
                        <tr>
                            <td><?php echo $row['id_lapangan']; ?></td>
                            <td class="lapangan-name"><?php echo htmlspecialchars($row['nama_lapangan']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_olahraga']); ?></td>
                            <td><?php echo htmlspecialchars($row['lokasi']); ?></td>
                            <td class="price">Rp <?php echo number_format($row['harga_per_jam'],0,',','.'); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-edit" onclick="openEditModal(<?php echo $row['id_lapangan']; ?>, '<?php echo addslashes($row['nama_lapangan']); ?>', <?php echo $row['id_olahraga']; ?>, '<?php echo addslashes($row['lokasi']); ?>', <?php echo $row['harga_per_jam']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?hapus=<?php echo $row['id_lapangan']; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus lapangan ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <i class="fas fa-futbol" style="font-size: 48px; color: #94a3b8; margin-bottom: 10px; display: block;"></i>
                                <strong>Belum ada lapangan</strong><br>
                                <?php if($total_olahraga > 0): ?>
                                    <small>Silakan tambah lapangan dengan tombol di atas.</small>
                                <?php else: ?>
                                    <small>Silakan <a href="admin_olahraga.php">tambah jenis olahraga</a> terlebih dahulu.</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah (TANPA UPLOAD GAMBAR) -->
    <div id="tambahModal" class="modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Tambah Lapangan</h3>
                <span onclick="closeModal('tambahModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="tambah" value="1">
                    <div class="form-group">
                        <label>Nama Lapangan</label>
                        <input type="text" name="nama_lapangan" required placeholder="Contoh: Futsal C">
                    </div>
                    <div class="form-group">
                        <label>Jenis Olahraga</label>
                        <select name="id_olahraga" required>
                            <option value="">-- Pilih Jenis Olahraga --</option>
                            <?php mysqli_data_seek($olahraga_list,0); while($o=mysqli_fetch_assoc($olahraga_list)): ?>
                            <option value="<?php echo $o['id_olahraga']; ?>"><?php echo htmlspecialchars($o['nama_olahraga']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="link-olahraga">
                            <i class="fas fa-plus-circle"></i> <a href="admin_olahraga.php">Tambah Jenis Olahraga Baru</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" required placeholder="Jl. ...">
                    </div>
                    <div class="form-group">
                        <label>Harga Per Jam</label>
                        <input type="number" name="harga_per_jam" required placeholder="50000">
                    </div>
                    <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit (TANPA UPLOAD GAMBAR) -->
    <div id="editModal" class="modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Lapangan</h3>
                <span onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="edit" value="1">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label>Nama Lapangan</label>
                        <input type="text" name="nama_lapangan" id="edit_nama" required>
                    </div>
                    <div class="form-group">
                        <label>Jenis Olahraga</label>
                        <select name="id_olahraga" id="edit_olahraga" required>
                            <option value="">-- Pilih Jenis Olahraga --</option>
                            <?php mysqli_data_seek($olahraga_list,0); while($o=mysqli_fetch_assoc($olahraga_list)): ?>
                            <option value="<?php echo $o['id_olahraga']; ?>"><?php echo htmlspecialchars($o['nama_olahraga']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="link-olahraga">
                            <i class="fas fa-plus-circle"></i> <a href="admin_olahraga.php">Tambah Jenis Olahraga Baru</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" id="edit_lokasi" required>
                    </div>
                    <div class="form-group">
                        <label>Harga Per Jam</label>
                        <input type="number" name="harga_per_jam" id="edit_harga" required>
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
        
        function openEditModal(id, nama, olahraga, lokasi, harga) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_olahraga').value = olahraga;
            document.getElementById('edit_lokasi').value = lokasi;
            document.getElementById('edit_harga').value = harga;
            
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