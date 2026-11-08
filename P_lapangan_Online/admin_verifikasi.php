<?php
session_start();
include 'koneksi.php';

// CEK ROLE - HARUS ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['user_name'];

// ========== PROSES VERIFIKASI PEMBAYARAN (TRANSFER/E-WALLET) ==========
if (isset($_GET['verifikasi']) && isset($_GET['status'])) {
    $id_pembayaran = (int)$_GET['verifikasi'];
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'menunggu';
    
    if ($status == 'lunas') {
        $query = "SELECT id_booking FROM pembayaran WHERE id_pembayaran = $id_pembayaran";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $payment = mysqli_fetch_assoc($result);
            $id_booking = $payment['id_booking'];
            
            mysqli_query($conn, "UPDATE pembayaran SET status_pembayaran = 'lunas' WHERE id_pembayaran = $id_pembayaran");
            mysqli_query($conn, "UPDATE pemesanan SET status = 'dikonfirmasi' WHERE id_booking = $id_booking");
            
            // Update jadwal jadi dipesan
            $query_jadwal = "SELECT id_jadwal FROM pemesanan WHERE id_booking = $id_booking";
            $result_jadwal = mysqli_query($conn, $query_jadwal);
            if ($result_jadwal && mysqli_num_rows($result_jadwal) > 0) {
                $jadwal_row = mysqli_fetch_assoc($result_jadwal);
                $id_jadwal = $jadwal_row['id_jadwal'];
                mysqli_query($conn, "UPDATE jadwal SET status = 'dipesan' WHERE id_jadwal = $id_jadwal");
            }
        }
        header("Location: admin_verifikasi.php?msg=verified&filter=" . $filter);
        exit();
    } 
    elseif ($status == 'ditolak') {
        $query = "SELECT id_booking FROM pembayaran WHERE id_pembayaran = $id_pembayaran";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $payment = mysqli_fetch_assoc($result);
            $id_booking = $payment['id_booking'];
            
            mysqli_query($conn, "UPDATE pembayaran SET status_pembayaran = 'ditolak' WHERE id_pembayaran = $id_pembayaran");
            mysqli_query($conn, "UPDATE pemesanan SET status = 'dibatalkan' WHERE id_booking = $id_booking");
            
            // Update jadwal kembali tersedia
            $query_jadwal = "SELECT id_jadwal FROM pemesanan WHERE id_booking = $id_booking";
            $result_jadwal = mysqli_query($conn, $query_jadwal);
            if ($result_jadwal && mysqli_num_rows($result_jadwal) > 0) {
                $jadwal_row = mysqli_fetch_assoc($result_jadwal);
                $id_jadwal = $jadwal_row['id_jadwal'];
                mysqli_query($conn, "UPDATE jadwal SET status = 'tersedia' WHERE id_jadwal = $id_jadwal");
            }
        }
        header("Location: admin_verifikasi.php?msg=rejected&filter=" . $filter);
        exit();
    }
}

// ========== PROSES KONFIRMASI CASH ==========
if (isset($_GET['konfirmasi_cash']) && isset($_GET['id'])) {
    $id_booking = (int)$_GET['id'];
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'menunggu';
    
    // Update status pemesanan
    mysqli_query($conn, "UPDATE pemesanan SET status = 'dikonfirmasi' WHERE id_booking = $id_booking");
    
    // Update/Insert ke tabel pembayaran
    $check_payment = mysqli_query($conn, "SELECT id_pembayaran FROM pembayaran WHERE id_booking = $id_booking");
    if (mysqli_num_rows($check_payment) == 0) {
        mysqli_query($conn, "INSERT INTO pembayaran (id_booking, metode_bayar, bukti_pembayaran, status_pembayaran) 
                            VALUES ($id_booking, 'cash', '', 'lunas')");
    } else {
        mysqli_query($conn, "UPDATE pembayaran SET status_pembayaran = 'lunas', metode_bayar = 'cash' WHERE id_booking = $id_booking");
    }
    
    // Update jadwal jadi dipesan
    $query_jadwal = "SELECT id_jadwal FROM pemesanan WHERE id_booking = $id_booking";
    $result_jadwal = mysqli_query($conn, $query_jadwal);
    if ($result_jadwal && mysqli_num_rows($result_jadwal) > 0) {
        $jadwal_row = mysqli_fetch_assoc($result_jadwal);
        $id_jadwal = $jadwal_row['id_jadwal'];
        mysqli_query($conn, "UPDATE jadwal SET status = 'dipesan' WHERE id_jadwal = $id_jadwal");
    }
    
    header("Location: admin_verifikasi.php?msg=cash_confirmed&filter=" . $filter);
    exit();
}

// ========== PROSES BATAL KONFIRMASI (TOLAK CASH) ==========
if (isset($_GET['batal_cash']) && isset($_GET['id'])) {
    $id_booking = (int)$_GET['id'];
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'menunggu';
    
    // Update status pemesanan
    mysqli_query($conn, "UPDATE pemesanan SET status = 'dibatalkan' WHERE id_booking = $id_booking");
    
    // Update status pembayaran
    mysqli_query($conn, "UPDATE pembayaran SET status_pembayaran = 'ditolak' WHERE id_booking = $id_booking");
    
    // Update jadwal kembali tersedia
    $query_jadwal = "SELECT id_jadwal FROM pemesanan WHERE id_booking = $id_booking";
    $result_jadwal = mysqli_query($conn, $query_jadwal);
    if ($result_jadwal && mysqli_num_rows($result_jadwal) > 0) {
        $jadwal_row = mysqli_fetch_assoc($result_jadwal);
        $id_jadwal = $jadwal_row['id_jadwal'];
        mysqli_query($conn, "UPDATE jadwal SET status = 'tersedia' WHERE id_jadwal = $id_jadwal");
    }
    
    header("Location: admin_verifikasi.php?msg=cash_cancelled&filter=" . $filter);
    exit();
}

// Filter status
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'menunggu';

// ========== QUERY UNTUK MENAMPILKAN DATA ==========
// PERBAIKAN: Query yang lebih sederhana dan jelas
if ($filter == 'menunggu') {
    // Menampilkan yang statusnya 'menunggu_verifikasi' (dari admin atau dari user upload)
    $query = "SELECT 
                p.id_booking,
                p.status as pemesanan_status,
                pg.nama_pengguna,
                pg.email,
                l.nama_lapangan,
                jo.nama_olahraga,
                l.harga_per_jam,
                j.tanggal,
                j.jam_mulai,
                j.jam_selesai,
                pym.id_pembayaran,
                COALESCE(pym.metode_bayar, 'transfer') as metode_bayar,
                pym.bukti_pembayaran,
                COALESCE(pym.status_pembayaran, 'menunggu_verifikasi') as status_pembayaran
              FROM pemesanan p 
              JOIN pengguna pg ON p.id_pengguna = pg.id_pengguna
              JOIN jadwal j ON p.id_jadwal = j.id_jadwal
              JOIN lapangan l ON j.id_lapangan = l.id_lapangan
              JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga
              LEFT JOIN pembayaran pym ON p.id_booking = pym.id_booking
              WHERE p.status = 'menunggu_verifikasi'
              ORDER BY p.tanggal_pemesanan DESC";
} elseif ($filter == 'sudah') {
    // Menampilkan yang sudah dikonfirmasi/selesai
    $query = "SELECT 
                p.id_booking,
                p.status as pemesanan_status,
                pg.nama_pengguna,
                pg.email,
                l.nama_lapangan,
                jo.nama_olahraga,
                l.harga_per_jam,
                j.tanggal,
                j.jam_mulai,
                j.jam_selesai,
                pym.id_pembayaran,
                COALESCE(pym.metode_bayar, 'cash') as metode_bayar,
                pym.bukti_pembayaran,
                COALESCE(pym.status_pembayaran, 'lunas') as status_pembayaran
              FROM pemesanan p 
              JOIN pengguna pg ON p.id_pengguna = pg.id_pengguna
              JOIN jadwal j ON p.id_jadwal = j.id_jadwal
              JOIN lapangan l ON j.id_lapangan = l.id_lapangan
              JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga
              LEFT JOIN pembayaran pym ON p.id_booking = pym.id_booking
              WHERE p.status IN ('dikonfirmasi', 'selesai')
              ORDER BY p.tanggal_pemesanan DESC";
} else {
    // Semua data
    $query = "SELECT 
                p.id_booking,
                p.status as pemesanan_status,
                pg.nama_pengguna,
                pg.email,
                l.nama_lapangan,
                jo.nama_olahraga,
                l.harga_per_jam,
                j.tanggal,
                j.jam_mulai,
                j.jam_selesai,
                pym.id_pembayaran,
                COALESCE(pym.metode_bayar, 'transfer') as metode_bayar,
                pym.bukti_pembayaran,
                COALESCE(pym.status_pembayaran, 
                    CASE WHEN p.status = 'dikonfirmasi' THEN 'lunas' 
                         WHEN p.status = 'dibatalkan' THEN 'ditolak'
                         WHEN p.status = 'selesai' THEN 'lunas'
                         ELSE 'menunggu_verifikasi' END) as status_pembayaran
              FROM pemesanan p 
              JOIN pengguna pg ON p.id_pengguna = pg.id_pengguna
              JOIN jadwal j ON p.id_jadwal = j.id_jadwal
              JOIN lapangan l ON j.id_lapangan = l.id_lapangan
              JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga
              LEFT JOIN pembayaran pym ON p.id_booking = pym.id_booking
              ORDER BY p.tanggal_pemesanan DESC";
}

$result = mysqli_query($conn, $query);

// Hitung jumlah menunggu (berdasarkan status pemesanan)
$query_menunggu = "SELECT COUNT(*) as total FROM pemesanan WHERE status = 'menunggu_verifikasi'";
$total_menunggu_result = mysqli_query($conn, $query_menunggu);
$total_menunggu = $total_menunggu_result ? mysqli_fetch_assoc($total_menunggu_result)['total'] : 0;
$total_data = $result ? mysqli_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - Admin</title>
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
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
        }
        
        .filter-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 8px 24px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            color: #64748b;
            text-decoration: none;
            display: inline-block;
        }
        .filter-btn:hover { background: #f1f5f9; border-color: #2563eb; }
        .filter-btn.active { background: #2563eb; color: white; border-color: #2563eb; }
        
        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }
        .stats-number {
            font-size: 32px;
            font-weight: 800;
            color: #f59e0b;
        }
        .stats-label {
            font-size: 14px;
            color: #64748b;
        }
        
        .table-container {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        th { text-align: left; padding: 12px 15px; background: #f8fafc; font-size: 13px; font-weight: 600; color: #1e293b; }
        td { padding: 12px 15px; font-size: 13px; color: #475569; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        
        .badge-warning {
            background: #fef3c7;
            color: #d97706;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success {
            background: #dcfce7;
            color: #16a34a;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-danger {
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-cash {
            background: #e0e7ff;
            color: #2563eb;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .btn-success {
            background: #22c55e;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-success:hover { background: #16a34a; }
        
        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-danger:hover { background: #dc2626; }
        
        .btn-view {
            background: #3b82f6;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-view:hover { background: #2563eb; }
        
        .btn-cash {
            background: #f59e0b;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-cash:hover { background: #d97706; }
        
        .btn-warning-cancel {
            background: #6c757d;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-warning-cancel:hover { background: #5a6268; }
        
        .action-cell { white-space: nowrap; display: flex; gap: 5px; flex-wrap: wrap; }
        .empty-state { text-align: center; padding: 60px; color: #94a3b8; }
        
        .id-pembayaran {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            font-weight: 600;
            color: #1e40af;
            background: #eff6ff;
            padding: 4px 8px;
            border-radius: 8px;
            display: inline-block;
        }
        
        .modal-bukti {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-bukti.show {
            display: flex;
        }
        .modal-bukti-content {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            overflow: hidden;
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-bukti-header {
            background: linear-gradient(135deg, #1e40af, #2563eb);
            padding: 15px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-bukti-header h3 { font-size: 18px; font-weight: 600; }
        .modal-bukti-header .close { font-size: 28px; cursor: pointer; opacity: 0.8; }
        .modal-bukti-header .close:hover { opacity: 1; }
        .modal-bukti-body { padding: 25px; text-align: center; }
        .modal-bukti-body img { max-width: 100%; border-radius: 12px; }
        .modal-bukti-body p { margin-top: 15px; color: #64748b; font-size: 12px; }
        .modal-bukti-footer { padding: 15px 20px; background: #f8fafc; display: flex; gap: 12px; justify-content: flex-end; }
        .btn-close-modal { padding: 8px 20px; background: #e2e8f0; border: none; border-radius: 10px; cursor: pointer; }
        
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
            <li><a href="admin_olahraga.php" class="nav-link"><i class="fas fa-medal"></i> <span>Jenis Olahraga</span></a></li>
            <li><a href="admin_lapangan.php" class="nav-link"><i class="fas fa-futbol"></i> <span>Kelola Lapangan</span></a></li>
            <li><a href="admin_jadwal.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Jadwal Lapangan</span></a></li>
            <li><a href="admin_pemesanan.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Kelola Pemesanan</span></a></li>
            <li><a href="admin_verifikasi.php" class="nav-link active"><i class="fas fa-credit-card"></i> <span>Verifikasi Pembayaran</span></a></li>
            <li><a href="admin_pengguna.php" class="nav-link"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
            <li><a href="admin_laporan.php" class="nav-link"><i class="fas fa-file-alt"></i> <span>Laporan</span></a></li>
            <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-credit-card"></i> Verifikasi Pembayaran & Konfirmasi Cash</h1>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'verified'): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> Pembayaran berhasil diverifikasi!</div>
            <?php elseif($_GET['msg'] == 'rejected'): ?>
            <div class="alert-danger"><i class="fas fa-times-circle"></i> Pembayaran ditolak!</div>
            <?php elseif($_GET['msg'] == 'cash_confirmed'): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> Pemesanan CASH berhasil dikonfirmasi!</div>
            <?php elseif($_GET['msg'] == 'cash_cancelled'): ?>
            <div class="alert-danger"><i class="fas fa-times-circle"></i> Pemesanan CASH dibatalkan!</div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="filter-buttons">
            <a href="?filter=menunggu" class="filter-btn <?php echo $filter == 'menunggu' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Menunggu Verifikasi 
                <?php if($total_menunggu > 0): ?>
                    <span style="background:#ef4444; color:white; border-radius:20px; padding:2px 8px; margin-left:5px;"><?php echo $total_menunggu; ?></span>
                <?php endif; ?>
            </a>
            <a href="?filter=sudah" class="filter-btn <?php echo $filter == 'sudah' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Sudah Dikonfirmasi
            </a>
            <a href="?filter=semua" class="filter-btn <?php echo $filter == 'semua' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> Semua
            </a>
        </div>
        
        <div class="stats-card">
            <div class="stats-number"><?php echo $total_menunggu; ?></div>
            <div class="stats-label"><i class="fas fa-clock"></i> Menunggu Verifikasi</div>
        </div>
        
        <div class="table-container">
            <table class="verifikasi-table">
                <thead>
                    <tr>
                        <th>ID Booking</th>
                        <th>ID Pembayaran</th>
                        <th>User</th>
                        <th>Lapangan</th>
                        <th>Jenis</th>
                        <th>Tanggal/Jam</th>
                        <th>Total</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($total_data > 0 && $result): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): 
                            $total_harga = 'Rp ' . number_format($row['harga_per_jam'], 0, ',', '.');
                            $tanggal_jadwal = date('d/m/Y', strtotime($row['tanggal']));
                            $jam_mulai = substr($row['jam_mulai'], 0, 5);
                            $jam_selesai = substr($row['jam_selesai'], 0, 5);
                            $metode = strtoupper($row['metode_bayar']);
                            $pemesanan_status = $row['pemesanan_status'];
                            $id_pembayaran = isset($row['id_pembayaran']) ? $row['id_pembayaran'] : '-';
                            
                            // Tentukan status badge
                            if ($pemesanan_status == 'menunggu_verifikasi') {
                                $status_badge = '<span class="badge-warning"><i class="fas fa-hourglass-half"></i> Menunggu Verifikasi</span>';
                                $show_actions = true;
                            } elseif ($pemesanan_status == 'dikonfirmasi') {
                                $status_badge = '<span class="badge-success"><i class="fas fa-check-circle"></i> Dikonfirmasi</span>';
                                $show_actions = false;
                            } elseif ($pemesanan_status == 'selesai') {
                                $status_badge = '<span class="badge-success"><i class="fas fa-check-double"></i> Selesai</span>';
                                $show_actions = false;
                            } elseif ($pemesanan_status == 'dibatalkan') {
                                $status_badge = '<span class="badge-danger"><i class="fas fa-times-circle"></i> Dibatalkan</span>';
                                $show_actions = false;
                            } else {
                                $status_badge = '<span class="badge-warning"><i class="fas fa-hourglass-half"></i> Menunggu Konfirmasi</span>';
                                $show_actions = true;
                            }
                            
                            // Cek apakah metode cash
                            $is_cash_method = ($metode == 'CASH');
                            
                            // Path bukti pembayaran
                            $bukti_path = 'uploads/bukti_pembayaran/' . $row['bukti_pembayaran'];
                            $has_bukti = !empty($row['bukti_pembayaran']) && file_exists($bukti_path);
                        ?>
                        <tr>
                            <td><strong>#<?php echo $row['id_booking']; ?></strong></td>
                            <td>
                                <?php if($id_pembayaran != '-'): ?>
                                    <span class="id-pembayaran">
                                        <i class="fas fa-hashtag"></i> <?php echo $id_pembayaran; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="id-pembayaran" style="background:#f1f5f9; color:#94a3b8;">
                                        <i class="fas fa-minus"></i> Belum ada
                                    </span>
                                <?php endif; ?>
                              </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['nama_pengguna']); ?><br>
                                <small><?php echo htmlspecialchars($row['email']); ?></small>
                              </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['nama_lapangan']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_olahraga']); ?></td>
                            <td><?php echo $tanggal_jadwal; ?> <?php echo $jam_mulai; ?> - <?php echo $jam_selesai; ?> WIB</div>
                            <td><?php echo $total_harga; ?></td>
                            <td>
                                <?php if($is_cash_method): ?>
                                    <span class="badge-cash"><i class="fas fa-money-bill-wave"></i> CASH</span>
                                <?php else: ?>
                                    <span class="badge-cash"><i class="fas fa-credit-card"></i> <?php echo $metode; ?></span>
                                <?php endif; ?>
                              </div>
                            <td><?php echo $status_badge; ?></td>
                            <td>
                                <?php if(!$is_cash_method && $has_bukti): ?>
                                    <button class="btn-view" onclick="showBuktiModal('<?php echo $bukti_path; ?>', '<?php echo $row['id_booking']; ?>', '<?php echo $id_pembayaran; ?>')">
                                        <i class="fas fa-image"></i> Lihat Bukti
                                    </button>
                                <?php elseif(!$is_cash_method && !$has_bukti && $pemesanan_status == 'menunggu_verifikasi'): ?>
                                    <span style="color:#94a3b8;">Menunggu upload</span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">-</span>
                                <?php endif; ?>
                              </div>
                            <td>
                                <?php if($show_actions && $pemesanan_status == 'menunggu_verifikasi'): ?>
                                    <?php if($is_cash_method): ?>
                                        <div class="action-cell">
                                            <a href="?konfirmasi_cash=1&id=<?php echo $row['id_booking']; ?>&filter=<?php echo $filter; ?>" class="btn-cash" onclick="return confirm('Konfirmasi pemesanan CASH ini? Pastikan customer sudah membayar di tempat.')">
                                                <i class="fas fa-check"></i> Konfirmasi
                                            </a>
                                            <a href="?batal_cash=1&id=<?php echo $row['id_booking']; ?>&filter=<?php echo $filter; ?>" class="btn-warning-cancel" onclick="return confirm('Batalkan pemesanan CASH ini?')">
                                                <i class="fas fa-times"></i> Batalkan
                                            </a>
                                        </div>
                                    <?php elseif(isset($row['id_pembayaran']) && $row['id_pembayaran']): ?>
                                        <div class="action-cell">
                                            <?php if($has_bukti): ?>
                                            <a href="?verifikasi=<?php echo $row['id_pembayaran']; ?>&status=lunas&filter=<?php echo $filter; ?>" class="btn-success" onclick="return confirm('Verifikasi pembayaran ini?')">
                                                <i class="fas fa-check"></i> Verifikasi
                                            </a>
                                            <a href="?verifikasi=<?php echo $row['id_pembayaran']; ?>&status=ditolak&filter=<?php echo $filter; ?>" class="btn-danger" onclick="return confirm('Tolak pembayaran ini?')">
                                                <i class="fas fa-times"></i> Tolak
                                            </a>
                                            <?php else: ?>
                                            <span style="color:#94a3b8;">Belum upload bukti</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">Menunggu pembayaran</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">-</span>
                                <?php endif; ?>
                              </div>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="empty-state">
                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                                <strong>Tidak ada data</strong><br>
                                <small>
                                    <?php 
                                    if ($filter == 'menunggu') echo 'Tidak ada pemesanan yang menunggu verifikasi.';
                                    elseif ($filter == 'sudah') echo 'Belum ada pemesanan yang sudah dikonfirmasi.';
                                    else echo 'Belum ada data pemesanan.';
                                    ?>
                                </small>
                            </div>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="buktiModal" class="modal-bukti">
        <div class="modal-bukti-content">
            <div class="modal-bukti-header">
                <h3><i class="fas fa-receipt"></i> Bukti Pembayaran</h3>
                <span class="close" onclick="closeBuktiModal()">&times;</span>
            </div>
            <div class="modal-bukti-body">
                <img id="buktiImage" src="" alt="Bukti Pembayaran">
                <p id="buktiInfo">Pastikan bukti pembayaran valid sebelum memverifikasi.</p>
            </div>
            <div class="modal-bukti-footer">
                <button class="btn-close-modal" onclick="closeBuktiModal()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function showBuktiModal(imageUrl, idBooking, idPembayaran) {
            const modal = document.getElementById('buktiModal');
            const img = document.getElementById('buktiImage');
            const info = document.getElementById('buktiInfo');
            
            img.src = imageUrl;
            info.innerHTML = `Booking #${idBooking} | ID Pembayaran: ${idPembayaran}<br>Pastikan data sesuai sebelum diverifikasi.`;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeBuktiModal() {
            const modal = document.getElementById('buktiModal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('buktiModal');
            if (event.target === modal) {
                closeBuktiModal();
            }
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeBuktiModal();
            }
        });
    </script>
</body>
</html>