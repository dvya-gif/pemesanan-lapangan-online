<?php
session_start();
include 'koneksi.php';

// CEK ROLE - HARUS ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
$admin_name = $_SESSION['user_name'];

// ========== AUTO UPDATE: Dikonfirmasi jadi Selesai jika jadwal sudah lewat ==========
$auto_update = "UPDATE pemesanan p 
                JOIN jadwal j ON p.id_jadwal = j.id_jadwal 
                SET p.status = 'selesai' 
                WHERE p.status = 'dikonfirmasi' 
                AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW()";
mysqli_query($conn, $auto_update);

// ========== AJAX HANDLER untuk mendapatkan jam tersedia ==========
if (isset($_GET['get_available_jams']) && isset($_GET['id_lapangan']) && isset($_GET['tanggal'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $id_lapangan = (int)$_GET['id_lapangan'];
    $tanggal = mysqli_real_escape_string($conn, $_GET['tanggal']);
    
    $query_jam = "SELECT jam_mulai, jam_selesai 
                  FROM jadwal 
                  WHERE id_lapangan = $id_lapangan 
                  AND tanggal = '$tanggal' 
                  AND status = 'tersedia'
                  ORDER BY jam_mulai";
    
    $result_jam = mysqli_query($conn, $query_jam);
    
    if (!$result_jam) {
        echo json_encode(['error' => mysqli_error($conn)]);
        exit();
    }
    
    $available = [];
    while ($row = mysqli_fetch_assoc($result_jam)) {
        $available[] = [
            'mulai' => substr($row['jam_mulai'], 0, 5),
            'selesai' => substr($row['jam_selesai'], 0, 5)
        ];
    }
    
    echo json_encode($available);
    exit();
}

// ========== TAMBAH PEMESANAN OLEH ADMIN ==========
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tambah_pemesanan') {
    $id_pengguna = (int)$_POST['id_pengguna'];
    $id_lapangan = (int)$_POST['id_lapangan'];
    $tanggal_sewa = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam_mulai_sewa = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $metode_bayar = mysqli_real_escape_string($conn, $_POST['metode']);
    
    $jam_mulai_formatted = $jam_mulai_sewa . ':00';
    
    $tanggal_sekarang = date('Y-m-d');
    if ($tanggal_sewa < $tanggal_sekarang) {
        $error_tambah = "Tanggal pemesanan tidak boleh kurang dari hari ini!";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            $query_cek = "SELECT j.id_jadwal, l.harga_per_jam, l.nama_lapangan
                          FROM jadwal j
                          JOIN lapangan l ON j.id_lapangan = l.id_lapangan
                          WHERE j.id_lapangan = $id_lapangan 
                          AND j.tanggal = '$tanggal_sewa' 
                          AND j.jam_mulai = '$jam_mulai_formatted'
                          AND j.status = 'tersedia'
                          FOR UPDATE";
            
            $result_cek = mysqli_query($conn, $query_cek);
            
            if (!$result_cek) {
                throw new Exception("Error query: " . mysqli_error($conn));
            }
            
            if (mysqli_num_rows($result_cek) == 0) {
                throw new Exception("Jadwal tidak tersedia! Pastikan jadwal sudah digenerate.");
            }
            
            $jadwal_data = mysqli_fetch_assoc($result_cek);
            $id_jadwal = $jadwal_data['id_jadwal'];
            
            // ========== PERBAIKAN: Tentukan status berdasarkan pilihan admin ==========
            if ($status == 'dikonfirmasi') {
                // Langsung dikonfirmasi (sudah bayar tunai)
                $status_pemesanan = 'dikonfirmasi';
                $status_pembayaran = 'lunas';
                $bukti_pembayaran = '';
            } else {
                // Menunggu bayar -> status 'menunggu_verifikasi' agar muncul di halaman verifikasi
                $status_pemesanan = 'menunggu_verifikasi';
                $status_pembayaran = 'pending';
                $bukti_pembayaran = '';
            }
            
            // Insert ke tabel pemesanan
            $query_insert = "INSERT INTO pemesanan (id_pengguna, id_jadwal, status, tanggal_pemesanan) 
                            VALUES ('$id_pengguna', '$id_jadwal', '$status_pemesanan', NOW())";
            
            if (!mysqli_query($conn, $query_insert)) {
                throw new Exception("Gagal menyimpan pemesanan: " . mysqli_error($conn));
            }
            
            $id_pemesanan = mysqli_insert_id($conn);
            
            // Insert ke tabel pembayaran
            $query_pembayaran = "INSERT INTO pembayaran (id_booking, metode_bayar, bukti_pembayaran, status_pembayaran) 
                                VALUES ('$id_pemesanan', '$metode_bayar', '$bukti_pembayaran', '$status_pembayaran')";
            
            if (!mysqli_query($conn, $query_pembayaran)) {
                throw new Exception("Gagal menyimpan pembayaran: " . mysqli_error($conn));
            }
            
            $query_update_jadwal = "UPDATE jadwal SET status = 'dipesan' WHERE id_jadwal = $id_jadwal";
            if (!mysqli_query($conn, $query_update_jadwal)) {
                throw new Exception("Gagal mengupdate status jadwal: " . mysqli_error($conn));
            }
            
            mysqli_commit($conn);
            
            $_SESSION['success_message'] = "✅ Pemesanan berhasil ditambahkan!";
            header("Location: admin_pemesanan.php?msg=add_success");
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_tambah = $e->getMessage();
        }
    }
}

// ========== UPDATE STATUS PEMESANAN ==========
if (isset($_GET['update_status']) && isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    
    if ($status == 'dikonfirmasi') {
        mysqli_query($conn, "UPDATE pembayaran SET status_pembayaran='lunas' WHERE id_booking=$id");
    }
    
    if ($status == 'dibatalkan') {
        $query_jadwal = "SELECT id_jadwal FROM pemesanan WHERE id_booking = $id";
        $result_jadwal = mysqli_query($conn, $query_jadwal);
        if ($row_jadwal = mysqli_fetch_assoc($result_jadwal)) {
            mysqli_query($conn, "UPDATE jadwal SET status = 'tersedia' WHERE id_jadwal = " . $row_jadwal['id_jadwal']);
        }
        mysqli_query($conn, "UPDATE pembayaran SET status_pembayaran='dibatalkan' WHERE id_booking=$id");
    }
    
    if ($status == 'selesai') {
        $query_jadwal = "SELECT id_jadwal FROM pemesanan WHERE id_booking = $id";
        $result_jadwal = mysqli_query($conn, $query_jadwal);
        if ($row_jadwal = mysqli_fetch_assoc($result_jadwal)) {
            mysqli_query($conn, "UPDATE jadwal SET status = 'selesai' WHERE id_jadwal = " . $row_jadwal['id_jadwal']);
        }
    }
    
    mysqli_query($conn, "UPDATE pemesanan SET status='$status' WHERE id_booking=$id");
    header("Location: admin_pemesanan.php?msg=status_updated");
    exit();
}

// ========== HAPUS PEMESANAN ==========
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    
    $query_jadwal = "SELECT id_jadwal FROM pemesanan WHERE id_booking = $id";
    $result_jadwal = mysqli_query($conn, $query_jadwal);
    if ($row_jadwal = mysqli_fetch_assoc($result_jadwal)) {
        mysqli_query($conn, "UPDATE jadwal SET status = 'tersedia' WHERE id_jadwal = " . $row_jadwal['id_jadwal']);
    }
    
    mysqli_query($conn, "DELETE FROM pembayaran WHERE id_booking=$id");
    mysqli_query($conn, "DELETE FROM pemesanan WHERE id_booking=$id");
    
    header("Location: admin_pemesanan.php?msg=deleted");
    exit();
}

// ========== QUERY DATA PEMESANAN ==========
$query = "SELECT 
    p.id_booking, 
    pg.nama_pengguna, 
    pg.email, 
    l.nama_lapangan, 
    jo.nama_olahraga,
    j.tanggal, 
    j.jam_mulai, 
    j.jam_selesai,
    l.harga_per_jam,
    p.status, 
    p.tanggal_pemesanan 
    FROM pemesanan p 
    JOIN jadwal j ON p.id_jadwal = j.id_jadwal 
    JOIN lapangan l ON j.id_lapangan = l.id_lapangan 
    JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga
    JOIN pengguna pg ON p.id_pengguna = pg.id_pengguna 
    ORDER BY p.tanggal_pemesanan DESC";

$pemesanan = mysqli_query($conn, $query);

// ========== QUERY DROPDOWN CUSTOMER ==========
$query_pengguna = "SELECT id_pengguna, nama_pengguna, email, no_hp FROM pengguna ORDER BY nama_pengguna";
$pengguna_list = mysqli_query($conn, $query_pengguna);

// ========== QUERY DROPDOWN LAPANGAN ==========
$query_lapangan = "SELECT l.id_lapangan, l.nama_lapangan, l.lokasi, l.harga_per_jam, jo.nama_olahraga 
                   FROM lapangan l 
                   JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga 
                   ORDER BY jo.nama_olahraga, l.nama_lapangan";
$lapangan_list = mysqli_query($conn, $query_lapangan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemesanan - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f9ff; }
        
        /* SIDEBAR STYLES */
        .sidebar {
            position: fixed; left: 0; top: 0; width: 260px; height: 100vh;
            background: linear-gradient(180deg, #1e40af, #2563eb);
            padding: 25px 0; overflow-y: auto; z-index: 100;
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
            color: rgba(255,255,255,0.8); text-decoration: none; gap: 12px;
            font-size: 14px; transition: 0.3s; border-radius: 12px; margin: 0 10px;
        }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.2); color: white; transform: translateX(5px); }
        .nav-link i { width: 22px; }
        
        /* MAIN CONTENT */
        .main-content { margin-left: 260px; padding: 25px 35px; }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; background: white; padding: 15px 25px;
            border-radius: 20px; border: 1px solid #e2e8f0; flex-wrap: wrap; gap: 15px;
        }
        .header h1 { font-size: 24px; font-weight: 700; color: #1e293b; }
        .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .logout-btn {
            background: #ef4444; color: white; padding: 8px 18px;
            border-radius: 12px; text-decoration: none; font-size: 13px;
            transition: 0.3s;
        }
        .logout-btn:hover { background: #dc2626; transform: translateY(-2px); }
        
        /* BUTTONS */
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white; padding: 10px 20px; border: none;
            border-radius: 12px; font-size: 14px; font-weight: 600;
            cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: 0.3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(37,99,235,0.3); }
        
        /* ALERTS */
        .alert {
            padding: 12px 20px; border-radius: 12px; margin-bottom: 20px;
        }
        .alert-success { background: #dcfce7; color: #16a34a; border-left: 4px solid #16a34a; }
        .alert-error { background: #fee2e2; color: #dc2626; border-left: 4px solid #dc2626; }
        .alert-warning { background: #fef3c7; color: #d97706; border-left: 4px solid #d97706; }
        
        /* TABLE */
        .table-container {
            background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th { text-align: left; padding: 12px 15px; background: #f8fafc; font-size: 13px; font-weight: 600; color: #1e293b; }
        td { padding: 12px 15px; font-size: 13px; color: #475569; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        
        /* STATUS BADGES */
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px;
            border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .status-pending { background: #dbeafe; color: #2563eb; }
        .status-menunggu_verifikasi { background: #fef3c7; color: #d97706; }
        .status-dikonfirmasi { background: #dcfce7; color: #16a34a; }
        .status-selesai { background: #d1fae5; color: #065f46; }
        .status-dibatalkan { background: #fee2e2; color: #dc2626; }
        
        /* ACTION BUTTONS */
        .action-cell { white-space: nowrap; min-width: 220px; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .btn-status, .btn-verify, .btn-warning, .btn-danger {
            padding: 5px 10px; border: none; border-radius: 15px;
            font-size: 11px; font-weight: 500; text-decoration: none;
            display: inline-flex; align-items: center; gap: 4px; transition: 0.2s;
        }
        .btn-status { background: #22c55e; color: white; }
        .btn-status:hover { background: #16a34a; }
        .btn-verify { background: #8b5cf6; color: white; }
        .btn-verify:hover { background: #7c3aed; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        
        .harga { font-weight: 700; color: #2563eb; }
        .olahraga-badge {
            display: inline-block; padding: 2px 8px; border-radius: 20px;
            font-size: 11px; font-weight: 600; background: #e0e7ff; color: #2563eb;
        }
        
        /* SEARCH & FILTER */
        .search-filters {
            margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;
        }
        .search-filters input {
            flex: 1; padding: 10px 15px; border: 1px solid #e2e8f0;
            border-radius: 12px; font-family: 'Inter', sans-serif;
        }
        .search-filters select {
            padding: 10px 15px; border: 1px solid #e2e8f0;
            border-radius: 12px; background: white; min-width: 150px;
        }
        
        /* MODAL STYLES */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content {
            background: white; margin: auto; width: 90%; max-width: 650px;
            border-radius: 24px; box-shadow: 0 20px 35px rgba(0,0,0,0.2);
            animation: slideDown 0.3s ease; max-height: 90vh; overflow-y: auto;
        }
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            padding: 20px 25px; background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white; border-radius: 24px 24px 0 0;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0;
        }
        .modal-header h3 { font-size: 20px; font-weight: 600; }
        .close-modal { color: white; font-size: 28px; cursor: pointer; }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b; font-size: 13px; }
        .form-group label i { margin-right: 6px; color: #2563eb; }
        .form-group select, .form-group input {
            width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0;
            border-radius: 12px; font-family: 'Inter', sans-serif; font-size: 14px;
        }
        .form-group select:focus, .form-group input:focus {
            outline: none; border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        
        /* PAYMENT & STATUS OPTIONS */
        .payment-options, .status-options {
            display: flex; gap: 10px; margin-top: 5px;
        }
        .payment-option, .status-option {
            flex: 1; padding: 10px; background: #f8fafc;
            border: 1px solid #e2e8f0; border-radius: 12px;
            text-align: center; cursor: pointer; transition: 0.2s;
        }
        .payment-option.selected, .status-option.selected {
            border-color: #2563eb; background: #e0e7ff;
        }
        .payment-icon, .status-icon { font-size: 20px; margin-bottom: 5px; }
        .payment-name, .status-name { font-size: 11px; font-weight: 500; }
        
        .info-box {
            background: #f0f9ff; border-radius: 12px; padding: 12px 15px;
            margin: 15px 0; border-left: 4px solid #2563eb;
        }
        .info-box p { margin: 5px 0; font-size: 13px; }
        
        .jam-info { font-size: 11px; margin-top: 8px; display: flex; gap: 15px; flex-wrap: wrap; }
        .jam-tersedia { color: #16a34a; }
        .jam-terbooking { color: #dc2626; }
        .jam-none { color: #d97706; }
        
        .modal-footer {
            padding: 20px 25px; border-top: 1px solid #e2e8f0;
            display: flex; justify-content: flex-end; gap: 12px;
        }
        .btn-secondary {
            background: #e2e8f0; color: #1e293b; padding: 10px 20px;
            border: none; border-radius: 12px; cursor: pointer; font-weight: 500;
        }
        .btn-save {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white; padding: 10px 20px; border: none;
            border-radius: 12px; cursor: pointer; font-weight: 600;
        }
        
        small { font-size: 11px; color: #64748b; display: block; margin-top: 5px; }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo p, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
            .form-row { flex-direction: column; gap: 0; }
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
            <li><a href="admin_pemesanan.php" class="nav-link active"><i class="fas fa-calendar-check"></i> <span>Kelola Pemesanan</span></a></li>
            <li><a href="admin_verifikasi.php" class="nav-link"><i class="fas fa-credit-card"></i> <span>Verifikasi Pembayaran</span></a></li>
            <li><a href="admin_pengguna.php" class="nav-link"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
            <li><a href="admin_laporan.php" class="nav-link"><i class="fas fa-file-alt"></i> <span>Laporan</span></a></li>
            <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-calendar-check"></i> Kelola Pemesanan</h1>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
                <button onclick="openTambahModal()" class="btn-primary"><i class="fas fa-plus-circle"></i> Tambah Pemesanan</button>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'status_updated'): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> ✅ Status pemesanan berhasil diperbarui!</div>
            <?php elseif($_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success"><i class="fas fa-trash-alt"></i> 🗑️ Pemesanan berhasil dihapus!</div>
            <?php elseif($_GET['msg'] == 'add_success'): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> ✅ Pemesanan baru berhasil ditambahkan!</div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if(isset($error_tambah)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> ⚠️ <?php echo $error_tambah; ?></div>
        <?php endif; ?>
        
        <div class="search-filters">
            <input type="text" id="searchInput" placeholder="🔍 Cari pemesan, lapangan, atau email..." onkeyup="filterTable()">
            <select id="statusFilter" onchange="filterTable()">
                <option value="">Semua Status</option>
                <option value="pending">Menunggu Bayar</option>
                <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                <option value="dikonfirmasi">Dikonfirmasi</option>
                <option value="selesai">Selesai</option>
                <option value="dibatalkan">Dibatalkan</option>
            </select>
        </div>
        
        <div class="table-container">
            <table id="pemesananTable">
                <thead>
                    <tr>
                        <th>ID</th><th>Pemesan</th><th>Email</th><th>Lapangan</th>
                        <th>Jenis</th><th>Tanggal Main</th><th>Jam</th>
                        <th>Durasi</th><th>Harga</th><th>Status</th>
                        <th>Tgl Pesan</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($pemesanan && mysqli_num_rows($pemesanan) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($pemesanan)): 
                            $start_time = strtotime($row['jam_mulai']);
                            $end_time = strtotime($row['jam_selesai']);
                            $duration = ($end_time - $start_time) / 3600;
                            $total_harga = $row['harga_per_jam'] * $duration;
                            
                            $status_text = ''; $status_icon = '';
                            switch($row['status']) {
                                case 'pending': $status_text = 'Menunggu Bayar'; $status_icon = 'fa-clock'; break;
                                case 'menunggu_verifikasi': $status_text = 'Menunggu Verifikasi'; $status_icon = 'fa-hourglass-half'; break;
                                case 'dikonfirmasi': $status_text = 'Dikonfirmasi'; $status_icon = 'fa-check-circle'; break;
                                case 'selesai': $status_text = 'Selesai'; $status_icon = 'fa-flag-checkered'; break;
                                case 'dibatalkan': $status_text = 'Dibatalkan'; $status_icon = 'fa-times-circle'; break;
                                default: $status_text = ucfirst($row['status']); $status_icon = 'fa-info-circle';
                            }
                        ?>
                        <tr class="booking-row" data-status="<?php echo $row['status']; ?>">
                            <td>#<?php echo $row['id_booking']; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_pengguna']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_lapangan']); ?></td>
                            <td><span class="olahraga-badge"><?php echo htmlspecialchars($row['nama_olahraga']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo substr($row['jam_mulai'],0,5); ?> - <?php echo substr($row['jam_selesai'],0,5); ?></td>
                            <td><?php echo $duration; ?> jam</td>
                            <td class="harga">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></td>
                            <td><span class="status-badge status-<?php echo $row['status']; ?>"><i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_pemesanan'])); ?></td>
                            <td class="action-cell">
                                <div class="action-buttons">
                                    <?php if($row['status'] == 'menunggu_verifikasi'): ?>
                                        <a href="?update_status=1&id=<?php echo $row['id_booking']; ?>&status=dikonfirmasi" class="btn-verify" onclick="return confirm('Verifikasi pembayaran ini?')"><i class="fas fa-check-double"></i> Verif</a>
                                    <?php endif; ?>
                                    <?php if($row['status'] != 'dikonfirmasi' && $row['status'] != 'selesai' && $row['status'] != 'dibatalkan' && $row['status'] != 'menunggu_verifikasi'): ?>
                                        <a href="?update_status=1&id=<?php echo $row['id_booking']; ?>&status=dikonfirmasi" class="btn-status" onclick="return confirm('Konfirmasi pemesanan ini?')"><i class="fas fa-check"></i> Konfirm</a>
                                    <?php endif; ?>
                                    <?php if($row['status'] != 'selesai' && $row['status'] != 'dibatalkan'): ?>
                                        <a href="?update_status=1&id=<?php echo $row['id_booking']; ?>&status=selesai" class="btn-status" onclick="return confirm('Tandai selesai?')"><i class="fas fa-clock"></i> Selesai</a>
                                    <?php endif; ?>
                                    <?php if($row['status'] != 'dibatalkan' && $row['status'] != 'selesai'): ?>
                                        <a href="?update_status=1&id=<?php echo $row['id_booking']; ?>&status=dibatalkan" class="btn-warning" onclick="return confirm('Batalkan pemesanan ini?')"><i class="fas fa-times"></i> Batal</a>
                                    <?php endif; ?>
                                    <a href="?hapus=<?php echo $row['id_booking']; ?>" class="btn-danger" onclick="return confirm('Yakin hapus pemesanan ini?')"><i class="fas fa-trash"></i> Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="12" style="text-align:center; padding:40px;"><i class="fas fa-inbox" style="font-size:40px; color:#cbd5e1;"></i><br>Belum ada pemesanan</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH PEMESANAN -->
    <div id="tambahModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Tambah Pemesanan Baru</h3>
                <span class="close-modal" onclick="closeTambahModal()">&times;</span>
            </div>
            <form method="POST" action="" id="formTambahPemesanan">
                <input type="hidden" name="action" value="tambah_pemesanan">
                <div class="modal-body">
                    <div id="modalMessage"></div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Pilih Customer</label>
                        <select name="id_pengguna" id="id_pengguna" required>
                            <option value="">-- Pilih Customer --</option>
                            <?php 
                            mysqli_data_seek($pengguna_list, 0);
                            while($user = mysqli_fetch_assoc($pengguna_list)): ?>
                                <option value="<?php echo $user['id_pengguna']; ?>">
                                    <?php echo htmlspecialchars($user['nama_pengguna']); ?> - <?php echo htmlspecialchars($user['email']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-futbol"></i> Pilih Lapangan</label>
                        <select name="id_lapangan" id="id_lapangan" required onchange="loadHargaDanJadwal()">
                            <option value="">-- Pilih Lapangan --</option>
                            <?php 
                            mysqli_data_seek($lapangan_list, 0);
                            while($lapangan = mysqli_fetch_assoc($lapangan_list)): ?>
                                <option value="<?php echo $lapangan['id_lapangan']; ?>" 
                                        data-harga="<?php echo $lapangan['harga_per_jam']; ?>"
                                        data-nama="<?php echo htmlspecialchars($lapangan['nama_lapangan']); ?>"
                                        data-olahraga="<?php echo $lapangan['nama_olahraga']; ?>">
                                    🏸 <?php echo htmlspecialchars($lapangan['nama_lapangan']); ?> 
                                    (<?php echo $lapangan['nama_olahraga']; ?>) - 
                                    Rp <?php echo number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?>/jam
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Tanggal Sewa</label>
                            <input type="date" name="tanggal" id="tanggal" required min="<?php echo date('Y-m-d'); ?>" onchange="loadJadwal()">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Jam Sewa (1 Jam)</label>
                            <select name="jam_mulai" id="jam_mulai" required>
                                <option value="">-- Pilih lapangan & tanggal --</option>
                            </select>
                            <div class="jam-info" id="jamInfo"></div>
                        </div>
                    </div>
                    
                    <div id="infoBox" class="info-box" style="display: none;">
                        <p><strong><i class="fas fa-info-circle"></i> Detail:</strong></p>
                        <p>🏟️ Lapangan: <span id="infoLapangan">-</span> | ⚽ <span id="infoOlahraga">-</span></p>
                        <p>💰 Harga: Rp <span id="infoHarga">0</span> / jam</p>
                        <p>💵 Total: <strong>Rp <span id="infoTotal">0</span></strong></p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Metode Pembayaran</label>
                            <div class="payment-options">
                                <div class="payment-option" onclick="selectPayment(this, 'cash')"><div class="payment-icon">💵</div><div class="payment-name">Cash</div></div>
                                <div class="payment-option" onclick="selectPayment(this, 'ewallet')"><div class="payment-icon">📱</div><div class="payment-name">E-Wallet</div></div>
                                <div class="payment-option selected" onclick="selectPayment(this, 'transfer')"><div class="payment-icon">🏦</div><div class="payment-name">Transfer</div></div>
                            </div>
                            <input type="hidden" name="metode" id="metode" value="transfer">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Status Pemesanan</label>
                            <div class="status-options">
                                <div class="status-option" onclick="selectStatus(this, 'pending')">
                                    <div class="status-icon">⏳</div>
                                    <div class="status-name">Menunggu Bayar</div>
                                </div>
                                <div class="status-option selected" onclick="selectStatus(this, 'dikonfirmasi')">
                                    <div class="status-icon">✅</div>
                                    <div class="status-name">Langsung Dikonfirmasi</div>
                                </div>
                            </div>
                            <input type="hidden" name="status" id="status" value="dikonfirmasi">
                            <small>
                                <i class="fas fa-info-circle"></i> 
                                <strong>Menunggu Bayar</strong>: Pemesanan akan muncul di halaman Verifikasi Pembayaran untuk dikonfirmasi admin.<br>
                                <strong>Langsung Dikonfirmasi</strong>: Pemesanan langsung selesai tanpa perlu verifikasi (untuk pembayaran tunai).
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeTambahModal()">Batal</button>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Pemesanan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentHarga = 0;
        let currentNamaLapangan = '';
        let currentOlahraga = '';
        let currentIdLapangan = '';
        
        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            document.querySelectorAll('.booking-row').forEach(row => {
                const match = row.textContent.toLowerCase().includes(search);
                const matchStatus = status === '' || row.dataset.status === status;
                row.style.display = (match && matchStatus) ? '' : 'none';
            });
        }
        
        function openTambahModal() {
            document.getElementById('tambahModal').classList.add('show');
            document.getElementById('formTambahPemesanan').reset();
            document.getElementById('infoBox').style.display = 'none';
            document.getElementById('jam_mulai').innerHTML = '<option value="">-- Pilih lapangan & tanggal --</option>';
            document.getElementById('modalMessage').innerHTML = '';
            document.getElementById('jamInfo').innerHTML = '';
            // Reset ke default
            document.getElementById('status').value = 'dikonfirmasi';
            document.querySelectorAll('.status-option').forEach(opt => opt.classList.remove('selected'));
            document.querySelector('.status-option:last-child').classList.add('selected');
            document.getElementById('metode').value = 'transfer';
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            document.querySelector('.payment-option:last-child').classList.add('selected');
        }
        
        function closeTambahModal() {
            document.getElementById('tambahModal').classList.remove('show');
        }
        
        function selectPayment(el, method) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('metode').value = method;
        }
        
        function selectStatus(el, status) {
            document.querySelectorAll('.status-option').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('status').value = status;
        }
        
        function loadHargaDanJadwal() {
            const select = document.getElementById('id_lapangan');
            const opt = select.options[select.selectedIndex];
            if (select.value && opt) {
                currentHarga = parseInt(opt.dataset.harga) || 0;
                currentNamaLapangan = opt.dataset.nama || '';
                currentOlahraga = opt.dataset.olahraga || '';
                currentIdLapangan = select.value;
                
                document.getElementById('infoHarga').innerText = formatRupiah(currentHarga);
                document.getElementById('infoTotal').innerText = formatRupiah(currentHarga);
                document.getElementById('infoLapangan').innerText = currentNamaLapangan;
                document.getElementById('infoOlahraga').innerText = currentOlahraga;
                document.getElementById('infoBox').style.display = 'block';
                
                document.getElementById('jam_mulai').innerHTML = '<option value="">-- Pilih tanggal --</option>';
                document.getElementById('jam_mulai').disabled = true;
                document.getElementById('jamInfo').innerHTML = '';
                
                const tanggal = document.getElementById('tanggal').value;
                if (tanggal) loadJadwal();
            } else {
                document.getElementById('infoBox').style.display = 'none';
            }
        }
        
        async function loadJadwal() {
            const tanggal = document.getElementById('tanggal').value;
            const idLapangan = document.getElementById('id_lapangan').value;
            
            if (!tanggal || !idLapangan) {
                document.getElementById('jam_mulai').innerHTML = '<option value="">-- Pilih lapangan & tanggal --</option>';
                return;
            }
            
            const jamSelect = document.getElementById('jam_mulai');
            const jamInfo = document.getElementById('jamInfo');
            
            jamSelect.innerHTML = '<option value="">Memuat jadwal...</option>';
            jamSelect.disabled = true;
            
            try {
                const response = await fetch(`admin_pemesanan.php?get_available_jams=1&id_lapangan=${idLapangan}&tanggal=${tanggal}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const availableJams = await response.json();
                
                if (availableJams.error) {
                    throw new Error(availableJams.error);
                }
                
                const allJams = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00'];
                const availableSet = new Set(availableJams.map(j => j.mulai));
                
                const sekarang = new Date();
                const tanggalHariIni = sekarang.toISOString().split('T')[0];
                const jamSekarang = sekarang.getHours();
                
                let options = '<option value="">-- Pilih jam --</option>';
                let tersedia = 0, terbooking = 0, expired = 0;
                
                if (availableJams.length === 0) {
                    jamSelect.innerHTML = '<option value="">-- Belum ada jadwal untuk tanggal ini --</option>';
                    jamInfo.innerHTML = '<span class="jam-none">⚠️ Belum ada jadwal. Silakan generate jadwal terlebih dahulu di menu Jadwal Lapangan.</span>';
                    jamSelect.disabled = true;
                } else {
                    for (const jam of allJams) {
                        const jamMulai = parseInt(jam.split(':')[0]);
                        const jamSelesai = jamMulai + 1;
                        let isExpired = (tanggal === tanggalHariIni && jamMulai <= jamSekarang);
                        
                        if (availableSet.has(jam) && !isExpired) {
                            options += `<option value="${jam}">✅ ${jam} - ${jamSelesai}:00 (Tersedia)</option>`;
                            tersedia++;
                        } else if (availableSet.has(jam) && isExpired) {
                            options += `<option value="${jam}" disabled style="color:#94a3b8;">⏰ ${jam} - ${jamSelesai}:00 (Jam sudah lewat)</option>`;
                            expired++;
                        } else if (!availableSet.has(jam) && !isExpired) {
                            options += `<option value="${jam}" disabled style="color:#dc2626;">⛔ ${jam} - ${jamSelesai}:00 (Sudah Dibooking)</option>`;
                            terbooking++;
                        } else {
                            options += `<option value="${jam}" disabled style="color:#94a3b8;">⏰ ${jam} - ${jamSelesai}:00 (Jam sudah lewat)</option>`;
                            expired++;
                        }
                    }
                    
                    jamSelect.innerHTML = options;
                    jamSelect.disabled = false;
                    
                    let infoHtml = `<span class="jam-tersedia">✅ ${tersedia} jam tersedia</span>`;
                    if (terbooking > 0) infoHtml += `<span class="jam-terbooking">⛔ ${terbooking} jam dibooking</span>`;
                    if (expired > 0) infoHtml += `<span style="color:#94a3b8;">⏰ ${expired} jam lewat</span>`;
                    jamInfo.innerHTML = infoHtml;
                }
            } catch (error) {
                console.error('Error:', error);
                jamSelect.innerHTML = '<option value="">Gagal memuat jadwal</option>';
                jamInfo.innerHTML = '<span class="jam-terbooking">Error: ' + error.message + '</span>';
                jamSelect.disabled = true;
            }
        }
        
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }
        
        // Event Listeners
        document.getElementById('tanggal').addEventListener('change', loadJadwal);
        document.getElementById('jam_mulai').addEventListener('change', function() {
            if (this.value && currentHarga > 0) {
                document.getElementById('infoTotal').innerText = formatRupiah(currentHarga);
            }
        });
        
        // Validasi submit
        document.getElementById('formTambahPemesanan').addEventListener('submit', function(e) {
            const jamSelect = document.getElementById('jam_mulai');
            const opt = jamSelect.options[jamSelect.selectedIndex];
            if (!jamSelect.value || (opt && opt.disabled)) {
                e.preventDefault();
                document.getElementById('modalMessage').innerHTML = '<div class="alert alert-error">⚠️ Silakan pilih jam yang tersedia!</div>';
            }
        });
        
        // Tutup modal jika klik di luar
        window.onclick = function(event) {
            const modal = document.getElementById('tambahModal');
            if (event.target === modal) closeTambahModal();
        }
    </script>
</body>
</html>