<?php
session_start();
include 'koneksi.php';

// CEK ROLE - HARUS ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
$admin_name = $_SESSION['user_name'];

// Filter tanggal
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

// FILTER STATUS: 'semua' atau 'lunas_berhasil'
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'semua';

// Bangun query berdasarkan filter
if ($filter_status == 'lunas_berhasil') {
    // Hanya transaksi LUNAS dan SELESAI
    $query = "SELECT 
        p.id_booking, 
        pg.nama_pengguna, 
        l.nama_lapangan, 
        jo.nama_olahraga,
        j.tanggal, 
        j.jam_mulai, 
        j.jam_selesai, 
        p.status, 
        l.harga_per_jam, 
        py.metode_bayar, 
        py.status_pembayaran,
        py.id_pembayaran
        FROM pemesanan p 
        JOIN jadwal j ON p.id_jadwal = j.id_jadwal 
        JOIN lapangan l ON j.id_lapangan = l.id_lapangan 
        JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga
        JOIN pengguna pg ON p.id_pengguna = pg.id_pengguna 
        LEFT JOIN pembayaran py ON p.id_booking = py.id_booking 
        WHERE j.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
        AND p.status = 'selesai'
        AND py.status_pembayaran = 'lunas'
        ORDER BY j.tanggal DESC";
} else {
    // SEMUA transaksi 
    $query = "SELECT 
        p.id_booking, 
        pg.nama_pengguna, 
        l.nama_lapangan, 
        jo.nama_olahraga,
        j.tanggal, 
        j.jam_mulai, 
        j.jam_selesai, 
        p.status, 
        l.harga_per_jam, 
        py.metode_bayar, 
        py.status_pembayaran,
        py.id_pembayaran
        FROM pemesanan p 
        JOIN jadwal j ON p.id_jadwal = j.id_jadwal 
        JOIN lapangan l ON j.id_lapangan = l.id_lapangan 
        JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga
        JOIN pengguna pg ON p.id_pengguna = pg.id_pengguna 
        LEFT JOIN pembayaran py ON p.id_booking = py.id_booking 
        WHERE j.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
        ORDER BY j.tanggal DESC";
}

$laporan = mysqli_query($conn, $query);

// Hitung total berdasarkan data yang tampil
$total_pendapatan = 0;
$total_transaksi = 0;
$data_rows = [];
while ($row = mysqli_fetch_assoc($laporan)) {
    $total_transaksi++;
    // Hanya hitung pendapatan jika status pembayaran LUNAS
    if (isset($row['status_pembayaran']) && $row['status_pembayaran'] == 'lunas') {
        $total_pendapatan += $row['harga_per_jam'];
    }
    $data_rows[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin</title>
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
        }
        .logout-btn:hover { background: #dc2626; transform: translateY(-2px); }
        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
            border: 1px solid #e2e8f0;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        .filter-group input, .filter-group select {
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
        }
        .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
            height: 100%;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            cursor: pointer;
        }
        .btn-primary {
            background: #2563eb;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 500;
        }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-print {
            background: #22c55e;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 500;
        }
        .btn-print:hover { background: #16a34a; }
        .info-banner {
            background: #dbeafe;
            border-left: 5px solid #2563eb;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            color: #1e40af;
        }
        .table-container {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: auto;
        }
        table { width: 100%; border-collapse: collapse; min-width: 1100px; }
        th { text-align: left; padding: 12px 15px; background: #f8fafc; font-size: 13px; font-weight: 600; color: #1e293b; }
        td { padding: 12px 15px; font-size: 13px; color: #475569; border-bottom: 1px solid #e2e8f0; }
        .olahraga-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #e0e7ff;
            color: #2563eb;
        }
        .id-pembayaran-badge {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            background: #d1fae5;
            color: #059669;
        }
        .id-pembayaran-null {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #94a3b8;
        }
        .status-selesai { color: #16a34a; font-weight: 600; }
        .status-dibatalkan { color: #dc2626; font-weight: 600; }
        .status-pending { color: #eab308; font-weight: 600; }
        .bayar-lunas { color: #16a34a; font-weight: 600; }
        .bayar-ditolak { color: #dc2626; font-weight: 600; }
        .bayar-menunggu { color: #eab308; font-weight: 600; }
        .total {
            background: white;
            padding: 15px 20px;
            border-radius: 20px;
            margin-top: 20px;
            font-size: 16px;
            font-weight: 700;
            border: 1px solid #e2e8f0;
        }
        @media print {
            .sidebar, .filter-box, .header .user-info, .logout-btn, .btn-primary, .btn-print, .info-banner {
                display: none !important;
            }
            .main-content {
                margin: 0;
                padding: 10px;
            }
            .table-container {
                border: 1px solid #ccc;
            }
            th, td {
                border: 1px solid #ddd !important;
            }
        }
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
            <li><a href="admin_verifikasi.php" class="nav-link"><i class="fas fa-credit-card"></i> <span>Verifikasi Pembayaran</span></a></li>
            <li><a href="admin_pengguna.php" class="nav-link"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
            <li><a href="admin_laporan.php" class="nav-link active"><i class="fas fa-file-alt"></i> <span>Laporan</span></a></li>
            <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Laporan Pemesanan</h1>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <form method="GET" class="filter-box">
            <div class="filter-group">
                <label>📅 Tanggal Awal</label>
                <input type="date" name="tanggal_awal" value="<?php echo $tanggal_awal; ?>">
            </div>
            <div class="filter-group">
                <label>📅 Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>">
            </div>
            <div class="filter-group">
                <label>🔍 Filter Status Transaksi</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="filter_status" value="semua" <?php echo ($filter_status == 'semua') ? 'checked' : ''; ?>>
                        <span>📋 Semua Transaksi</span>
                    </label>
                    <label>
                        <input type="radio" name="filter_status" value="lunas_berhasil" <?php echo ($filter_status == 'lunas_berhasil') ? 'checked' : ''; ?>>
                        <span>✅ Lunas & Berhasil (Selesai)</span>
                    </label>
                </div>
            </div>
            <div>
                <button type="submit" class="btn-primary"><i class="fas fa-filter"></i> Tampilkan</button>
            </div>
            <div>
                <button type="button" class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
            </div>
        </form>
        
        <?php if ($filter_status == 'lunas_berhasil'): ?>
        <div class="info-banner">
            <i class="fas fa-check-circle"></i> <strong>Filter Aktif:</strong> Hanya menampilkan pemesanan dengan status <strong>LUNAS</strong> (pembayaran) dan <strong>SELESAI</strong> (berhasil).
        </div>
        <?php else: ?>
        <div class="info-banner">
            <i class="fas fa-database"></i> <strong>Filter Aktif:</strong> Menampilkan <strong>SEMUA TRANSAKSI</strong> (termasuk pending, dibatalkan, ditolak).
        </div>
        <?php endif; ?>
        
        <div class="table-container">
            <table id="laporanTable">
                <thead>
                    <tr>
                        <th>ID Booking</th>
                        <th>ID Pembayaran</th>
                        <th>Pemesan</th>
                        <th>Lapangan</th>
                        <th>Jenis Olahraga</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Status</th>
                        <th>Metode</th>
                        <th>Status Bayar</th>
                        <th>Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data_rows) == 0): ?>
                    <tr>
                        <td colspan="11" style="text-align:center; padding:40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                            Tidak ada data pada periode ini.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($data_rows as $row): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id_booking']; ?></strong></td>
                            <td>
                                <?php if(isset($row['id_pembayaran']) && $row['id_pembayaran']): ?>
                                    <span class="id-pembayaran-badge"><i class="fas fa-credit-card"></i> #<?php echo $row['id_pembayaran']; ?></span>
                                <?php else: ?>
                                    <span class="id-pembayaran-null"><i class="fas fa-minus-circle"></i> -</span>
                                <?php endif; ?>
                             </td>
                            <td><?php echo htmlspecialchars($row['nama_pengguna']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_lapangan']); ?></td>
                            <td><span class="olahraga-badge"><?php echo htmlspecialchars($row['nama_olahraga']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo substr($row['jam_mulai'],0,5); ?> - <?php echo substr($row['jam_selesai'],0,5); ?> WIB</td>
                            <td class="<?php 
                                $status = strtolower($row['status']);
                                if($status == 'selesai') echo 'status-selesai';
                                elseif($status == 'dibatalkan') echo 'status-dibatalkan';
                                elseif($status == 'pending') echo 'status-pending';
                            ?>"><?php echo ucfirst($row['status']); ?></td>
                            <td><?php echo strtoupper($row['metode_bayar'] ?? '-'); ?></td>
                            <td class="<?php 
                                $bayar = strtolower($row['status_pembayaran'] ?? '');
                                if($bayar == 'lunas') echo 'bayar-lunas';
                                elseif($bayar == 'ditolak') echo 'bayar-ditolak';
                                elseif($bayar == 'menunggu_verifikasi') echo 'bayar-menunggu';
                                elseif($bayar == 'dibatalkan') echo 'bayar-ditolak';
                            ?>"><?php 
                                $status_bayar = $row['status_pembayaran'] ?? '-';
                                if($status_bayar == 'menunggu_verifikasi') echo 'Menunggu Verifikasi';
                                else echo ucfirst($status_bayar);
                            ?></td>
                            <td>Rp <?php echo number_format($row['harga_per_jam'],0,',','.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="total">
            <strong>📊 Ringkasan:</strong><br>
            Total Transaksi: <?php echo $total_transaksi; ?> pemesanan<br>
            Total Pendapatan (Lunas): Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
        </div>
    </div>
</body>
</html>