<?php
session_start();
include 'koneksi.php';

// CEK ROLE - HARUS ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['user_name'];

// Statistik
$total_lapangan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM lapangan"))['total'];
$total_pemesanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan"))['total'];
$total_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pengguna WHERE role='user'"))['total'];

// Pembayaran menunggu verifikasi
$pending_verifikasi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pembayaran WHERE status_pembayaran='menunggu_verifikasi'"))['total'] ?? 0;

// Total pendapatan
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(l.harga_per_jam) as total 
    FROM pemesanan p 
    JOIN jadwal j ON p.id_jadwal = j.id_jadwal 
    JOIN lapangan l ON j.id_lapangan = l.id_lapangan 
    WHERE p.status='dikonfirmasi' OR p.status='selesai'"))['total'] ?? 0;

// Pemesanan terbaru - dengan harga
$recent_bookings = mysqli_query($conn, "SELECT 
    p.id_booking, 
    pg.nama_pengguna, 
    l.nama_lapangan, 
    j.tanggal, 
    j.jam_mulai, 
    j.jam_selesai,
    l.harga_per_jam,
    p.status 
    FROM pemesanan p 
    JOIN jadwal j ON p.id_jadwal = j.id_jadwal 
    JOIN lapangan l ON j.id_lapangan = l.id_lapangan 
    JOIN pengguna pg ON p.id_pengguna = pg.id_pengguna 
    ORDER BY p.tanggal_pemesanan DESC 
    LIMIT 10");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DBooking Sport</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .stat-number { font-size: 32px; font-weight: 800; color: #2563eb; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 5px; }
        
        .card {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .card-header {
            padding: 15px 20px;
            background: #fafcff;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;
            font-size: 16px;
        }
        .card-header i { color: #2563eb; margin-right: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 15px; background: #f8fafc; font-size: 12px; font-weight: 600; color: #475569; }
        td { padding: 12px 15px; font-size: 13px; color: #475569; border-bottom: 1px solid #f1f5f9; }
        .status-pending { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-dikonfirmasi { background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-selesai { background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-dibatalkan { background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .harga { font-weight: 700; color: #2563eb; }
        
        @media (max-width: 1000px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { 
            .sidebar { width: 70px; } 
            .sidebar .logo p, .sidebar .nav-link span { display: none; } 
            .main-content { margin-left: 70px; padding: 20px; } 
            table { font-size: 11px; }
            th, td { padding: 8px 10px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><h1>🏸 DBooking</h1><p>Admin Panel</p></div>
        <ul class="nav-menu">
            <li><a href="admin_dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li><a href="admin_olahraga.php" class="nav-link"><i class="fas fa-medal"></i> <span>Jenis Olahraga</span></a></li>
            <li><a href="admin_lapangan.php" class="nav-link"><i class="fas fa-futbol"></i> <span>Kelola Lapangan</span></a></li>
            <li><a href="admin_jadwal.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Jadwal Lapangan</span></a></li>
            <li><a href="admin_pemesanan.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>Kelola Pemesanan</span></a></li>
            <li><a href="admin_verifikasi.php" class="nav-link"><i class="fas fa-credit-card"></i> <span>Verifikasi Pembayaran</span></a></li>
            <li><a href="admin_pengguna.php" class="nav-link"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
            <li><a href="admin_laporan.php" class="nav-link"><i class="fas fa-file-alt"></i> <span>Laporan</span></a></li>
            <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> Dashboard Admin</h1>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_lapangan; ?></div>
                <div class="stat-label"><i class="fas fa-futbol"></i> Total Lapangan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_pemesanan; ?></div>
                <div class="stat-label"><i class="fas fa-calendar-check"></i> Total Pemesanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_user; ?></div>
                <div class="stat-label"><i class="fas fa-users"></i> Total User</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_verifikasi; ?></div>
                <div class="stat-label"><i class="fas fa-clock"></i> Menunggu Verifikasi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
                <div class="stat-label"><i class="fas fa-money-bill-wave"></i> Total Pendapatan</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header"><i class="fas fa-history"></i> Pemesanan Terbaru</div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pemesan</th>
                            <th>Lapangan</th>
                            <th>Tanggal</th>
                            <th>Jam Main</th>
                            <th>Durasi</th>
                            <th>Harga</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($recent_bookings) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($recent_bookings)): 
                                $start_time = strtotime($row['jam_mulai']);
                                $end_time = strtotime($row['jam_selesai']);
                                $duration = ($end_time - $start_time) / 3600;
                                $total_harga = $row['harga_per_jam'] * $duration;
                                
                                $status_class = '';
                                $status_text = '';
                                switch($row['status']) {
                                    case 'pending':
                                        $status_class = 'status-pending';
                                        $status_text = 'Pending';
                                        break;
                                    case 'menunggu_verifikasi':
                                        $status_class = 'status-pending';
                                        $status_text = 'Menunggu Verifikasi';
                                        break;
                                    case 'dikonfirmasi':
                                        $status_class = 'status-dikonfirmasi';
                                        $status_text = 'Dikonfirmasi';
                                        break;
                                    case 'selesai':
                                        $status_class = 'status-selesai';
                                        $status_text = 'Selesai';
                                        break;
                                    case 'dibatalkan':
                                        $status_class = 'status-dibatalkan';
                                        $status_text = 'Dibatalkan';
                                        break;
                                    default:
                                        $status_class = 'status-pending';
                                        $status_text = ucfirst($row['status']);
                                }
                            ?>
                            <tr>
                                <td>#<?php echo $row['id_booking']; ?></td>
                                <td><?php echo htmlspecialchars($row['nama_pengguna']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_lapangan']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                <td><?php echo substr($row['jam_mulai'],0,5); ?> - <?php echo substr($row['jam_selesai'],0,5); ?> WIB</td>
                                <td><?php echo $duration; ?> jam</td>
                                <td><span class="harga">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></span></td>
                                <td><span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:40px;">
                                    <i class="fas fa-inbox" style="font-size: 40px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                                    Belum ada pemesanan
                                 </td
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>