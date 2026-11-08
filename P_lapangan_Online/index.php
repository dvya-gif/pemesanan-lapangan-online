<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// SET ZONA WAKTU WIB
date_default_timezone_set('Asia/Jakarta');

// ========== UPDATE OTOMATIS STATUS JADI SELESAI JIKA WAKTU LEWAT ==========
mysqli_query($conn, "UPDATE pemesanan p
                     JOIN jadwal j ON p.id_jadwal = j.id_jadwal
                     SET p.status = 'selesai'
                     WHERE p.status = 'dikonfirmasi' 
                     AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW()");

// ========== PERBAIKI STATUS NULL MENJADI DIKONFIRMASI ==========
mysqli_query($conn, "UPDATE pemesanan SET status = 'dikonfirmasi' WHERE status IS NULL OR status = ''");

// ========== STATISTIK ==========

// Total semua pemesanan
$total_pemesanan = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE id_pengguna = $user_id");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_pemesanan = $row['total'];
}

// Menunggu pembayaran (pending)
$pemesanan_menunggu = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE id_pengguna = $user_id AND status = 'pending'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $pemesanan_menunggu = $row['total'];
}

// Akan Datang (dikonfirmasi DAN waktu main BELUM lewat)
$pemesanan_datang = 0;
$query_datang = "SELECT p.id_booking, j.tanggal, j.jam_selesai 
                 FROM pemesanan p 
                 JOIN jadwal j ON p.id_jadwal = j.id_jadwal
                 WHERE p.id_pengguna = $user_id 
                 AND p.status = 'dikonfirmasi'";
$result_datang = mysqli_query($conn, $query_datang);
while ($row = mysqli_fetch_assoc($result_datang)) {
    $waktu_selesai = strtotime($row['tanggal'] . ' ' . $row['jam_selesai']);
    if ($waktu_selesai >= time()) {
        $pemesanan_datang++;
    }
}

// Selesai (status = 'selesai' ATAU dikonfirmasi tapi waktu sudah lewat)
$pemesanan_selesai = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE id_pengguna = $user_id AND status = 'selesai'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $pemesanan_selesai = $row['total'];
}

// Dibatalkan
$pemesanan_dibatalkan = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE id_pengguna = $user_id AND status = 'dibatalkan'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $pemesanan_dibatalkan = $row['total'];
}

// Pemesanan terbaru (ambil 5 terbaru)
$recent_bookings = mysqli_query($conn, "SELECT 
    p.id_booking,
    p.status,
    p.tanggal_pemesanan,
    j.tanggal,
    j.jam_mulai,
    j.jam_selesai,
    l.nama_lapangan,
    l.harga_per_jam
FROM pemesanan p 
JOIN jadwal j ON p.id_jadwal = j.id_jadwal
JOIN lapangan l ON j.id_lapangan = l.id_lapangan
WHERE p.id_pengguna = $user_id 
ORDER BY p.tanggal_pemesanan DESC LIMIT 5");

// ========== GRAFIK 6 BULAN TERAKHIR ==========
$chart_data = [];
$bulan_labels = [];
$bulan_names = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

for ($i = 5; $i >= 0; $i--) {
    $bulan = date('n', strtotime("-$i month"));
    $tahun = date('Y', strtotime("-$i month"));
    $bulan_nama = $bulan_names[$bulan - 1];
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan p
        JOIN jadwal j ON p.id_jadwal = j.id_jadwal
        WHERE p.id_pengguna = $user_id 
        AND MONTH(j.tanggal) = '$bulan' 
        AND YEAR(j.tanggal) = '$tahun'");
    $count = 0;
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = $row['total'];
    }
    $chart_data[] = $count;
    $bulan_labels[] = $bulan_nama;
}

$max_chart = max($chart_data) ?: 1;
$total_booking_6bulan = array_sum($chart_data);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DBooking Sport</title>
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

        .main-content {
            margin-left: 260px;
            padding: 25px 35px;
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

        .welcome-banner {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 25px;
            color: white;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .welcome-banner:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(37,99,235,0.3);
        }
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .welcome-banner h2 { font-size: 24px; font-weight: 800; margin-bottom: 8px; position: relative; z-index: 1; }
        .welcome-banner p { opacity: 0.9; font-size: 14px; position: relative; z-index: 1; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 22px 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #60a5fa);
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
            border-color: #2563eb;
        }
        .stat-card .number { 
            font-size: 32px; 
            font-weight: 800; 
            color: #2563eb; 
            line-height: 1.2;
            margin-bottom: 8px;
        }
        .stat-card .label { 
            font-size: 13px; 
            color: #64748b; 
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .stat-card .label i {
            font-size: 12px;
            color: #94a3b8;
        }

        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        .card {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.3s;
        }
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafcff;
        }
        .card-header h3 { 
            font-size: 16px; 
            font-weight: 700; 
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-header a { 
            color: #2563eb; 
            text-decoration: none; 
            font-size: 12px; 
            font-weight: 600; 
            transition: 0.3s;
            padding: 5px 12px;
            border-radius: 20px;
            background: #eff6ff;
        }
        .card-header a:hover { 
            background: #dbeafe;
        }

        .chart-container { 
            padding: 20px 20px 24px; 
        }
        .chart-stats { 
            text-align: center; 
            margin-bottom: 16px;
            background: #f8fafc;
            padding: 12px;
            border-radius: 16px;
        }
        .chart-stats .max-booking { 
            font-size: 28px; 
            font-weight: 800; 
            color: #2563eb;
            display: block;
        }
        .chart-bars {
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
            gap: 8px;
            margin-top: 20px;
        }
        .bar-item { 
            text-align: center; 
            flex: 1;
            cursor: pointer; 
            transition: 0.2s; 
        }
        .bar-item:hover { 
            transform: translateY(-5px); 
        }
        .bar {
            background: linear-gradient(180deg, #2563eb, #3b82f6);
            width: 100%;
            max-width: 45px;
            margin: 0 auto;
            border-radius: 10px 10px 5px 5px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .bar-item:hover .bar {
            background: linear-gradient(180deg, #1d4ed8, #2563eb);
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }
        .bar-value { 
            font-size: 13px; 
            font-weight: 700; 
            margin-bottom: 6px; 
            color: #1e293b; 
        }
        .bar-label { 
            font-size: 11px; 
            color: #64748b; 
            margin-top: 10px;
            font-weight: 500;
        }

        .booking-list { 
            padding: 0 20px 12px; 
        }
        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
            cursor: pointer;
        }
        .booking-item:hover {
            background: #f8fafc;
            padding-left: 12px;
            padding-right: 12px;
            border-radius: 12px;
        }
        .booking-item:last-child { border-bottom: none; }
        .booking-info h4 { 
            font-size: 15px; 
            font-weight: 700; 
            margin-bottom: 5px; 
            color: #1e293b; 
        }
        .booking-info p { 
            font-size: 11px; 
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .booking-price { 
            font-weight: 800; 
            color: #2563eb;
            font-size: 14px;
            text-align: right;
        }
        .booking-status { 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 10px; 
            font-weight: 700;
            display: inline-block;
            margin-top: 5px;
            text-align: center;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-akan-datang { background: #dbeafe; color: #2563eb; }
        .status-selesai { background: #dcfce7; color: #16a34a; }
        .status-dibatalkan { background: #fee2e2; color: #dc2626; }

        .cta-section {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-radius: 24px;
            padding: 28px 35px;
            text-align: center;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .cta-section:hover { transform: translateY(-3px); }
        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(37,99,235,0.2) 0%, transparent 70%);
            animation: rotate 15s linear infinite;
        }
        .cta-section h3 { 
            color: white; 
            font-size: 22px; 
            font-weight: 800; 
            margin-bottom: 10px; 
            position: relative; 
            z-index: 1; 
        }
        .cta-section p { 
            color: rgba(255,255,255,0.8); 
            font-size: 14px; 
            margin-bottom: 20px; 
            position: relative; 
            z-index: 1; 
        }
        .cta-btn {
            background: white;
            color: #1e293b;
            padding: 12px 32px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            position: relative;
            z-index: 1;
        }
        .cta-btn:hover { 
            transform: translateY(-2px) scale(1.02); 
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            background: #f8fafc;
            gap: 12px;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 12px;
            border-top: 1px solid #e2e8f0;
            margin-top: 10px;
        }

        .toast-notif {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1e293b;
            color: white;
            padding: 12px 24px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 500;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        .toast-notif.show {
            transform: translateX(0);
        }

        @media (max-width: 992px) {
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo p, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .two-columns { grid-template-columns: 1fr; }
            .chart-bars { gap: 4px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h1>🏸 DBooking</h1>
            <p>Sport</p>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="nav-link active"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="cari_lapangan.php" class="nav-link"><i class="fas fa-search"></i> <span>Cari Lapangan</span></a></li>
            <li class="nav-item"><a href="pemesanan_saya.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Pemesanan Saya</span></a></li>
            <li class="nav-item"><a href="riwayat_transaksi.php" class="nav-link"><i class="fas fa-history"></i> <span>Riwayat Transaksi</span></a></li>
            <li class="nav-item"><a href="profil_saya.php" class="nav-link"><i class="fas fa-user"></i> <span>Profil</span></a></li>
            <li class="nav-item"><a href="bantuan.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Bantuan</span></a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Keluar</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-chalkboard-user" style="margin-right: 10px; color:#2563eb;"></i> Dashboard</h1>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
            </div>
        </div>

        <div class="welcome-banner" onclick="window.location.href='cari_lapangan.php'">
            <h2>Ayo Berolahraga! 🏃‍♂️⚽</h2>
            <p>Booking lapangan favoritmu sekarang juga dan rasakan keseruannya!</p>
        </div>

        <!-- STATISTIK 5 KARTU (DENGAN LABEL YANG JELAS) -->
        <div class="stats-grid">
            <div class="stat-card" onclick="showToast('Total pemesanan: <?php echo $total_pemesanan; ?>')">
                <div class="number"><?php echo $total_pemesanan; ?></div>
                <div class="label"><i class="fas fa-calendar-check"></i> Total Pemesanan</div>
            </div>
            <div class="stat-card" onclick="showToast('Menunggu pembayaran: <?php echo $pemesanan_menunggu; ?>')">
                <div class="number"><?php echo $pemesanan_menunggu; ?></div>
                <div class="label"><i class="fas fa-clock"></i> Menunggu Bayar</div>
            </div>
            <div class="stat-card" onclick="showToast('Akan datang: <?php echo $pemesanan_datang; ?>')">
                <div class="number"><?php echo $pemesanan_datang; ?></div>
                <div class="label"><i class="fas fa-calendar-week"></i> Akan Datang</div>
            </div>
            <div class="stat-card" onclick="showToast('Selesai: <?php echo $pemesanan_selesai; ?>')">
                <div class="number"><?php echo $pemesanan_selesai; ?></div>
                <div class="label"><i class="fas fa-check-circle"></i> Selesai</div>
            </div>
            <div class="stat-card" onclick="showToast('Dibatalkan: <?php echo $pemesanan_dibatalkan; ?>')">
                <div class="number"><?php echo $pemesanan_dibatalkan; ?></div>
                <div class="label"><i class="fas fa-times-circle"></i> Dibatalkan</div>
            </div>
        </div>

        <div class="two-columns">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line" style="color:#2563eb;"></i> Grafik Pemesanan</h3>
                    <a href="riwayat_transaksi.php">Lihat semua →</a>
                </div>
                <div class="chart-container">
                    <div class="chart-stats">
                        <span class="max-booking"><?php echo $total_booking_6bulan; ?> Booking</span>
                        <p style="font-size: 12px; color: #64748b; margin-top: 5px;">6 Bulan Terakhir</p>
                    </div>
                    <div class="chart-bars">
                        <?php for($i = 0; $i < count($chart_data); $i++): 
                            $height = ($chart_data[$i] / $max_chart) * 120;
                        ?>
                        <div class="bar-item" onclick="showToast('<?php echo $bulan_labels[$i]; ?>: <?php echo $chart_data[$i]; ?> pemesanan')">
                            <div class="bar-value"><?php echo $chart_data[$i]; ?></div>
                            <div class="bar" style="height: <?php echo max($height, 8); ?>px"></div>
                            <div class="bar-label"><?php echo $bulan_labels[$i]; ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list-ul" style="color:#2563eb;"></i> Pemesanan Terbaru</h3>
                    <a href="pemesanan_saya.php">Lihat semua →</a>
                </div>
                <div class="booking-list">
                    <?php if(mysqli_num_rows($recent_bookings) > 0): ?>
                        <?php while($booking = mysqli_fetch_assoc($recent_bookings)): 
                            $waktu_main_selesai = strtotime($booking['tanggal'] . ' ' . $booking['jam_selesai']);
                            $is_masih_berlaku = ($waktu_main_selesai >= time());
                            $tanggal = date('d/m/Y', strtotime($booking['tanggal']));
                            $jam = substr($booking['jam_mulai'], 0, 5);
                            
                            // LOGIKA STATUS YANG JELAS
                            if ($booking['status'] == 'pending') {
                                $status_text = 'Menunggu Bayar';
                                $status_class = 'status-pending';
                            } elseif ($booking['status'] == 'dibatalkan') {
                                $status_text = 'Dibatalkan';
                                $status_class = 'status-dibatalkan';
                            } elseif ($booking['status'] == 'dikonfirmasi' && $is_masih_berlaku) {
                                $status_text = 'Akan Datang';
                                $status_class = 'status-akan-datang';
                            } elseif ($booking['status'] == 'selesai' || ($booking['status'] == 'dikonfirmasi' && !$is_masih_berlaku)) {
                                $status_text = 'Selesai';
                                $status_class = 'status-selesai';
                            } else {
                                $status_text = 'Selesai';
                                $status_class = 'status-selesai';
                            }
                        ?>
                        <div class="booking-item" onclick="window.location.href='pemesanan_saya.php'">
                            <div class="booking-info">
                                <h4><?php echo htmlspecialchars($booking['nama_lapangan']); ?></h4>
                                <p><i class="far fa-calendar-alt"></i> <?php echo $tanggal; ?> | <i class="far fa-clock"></i> <?php echo $jam; ?> WIB</p>
                            </div>
                            <div>
                                <div class="booking-price">Rp <?php echo number_format($booking['harga_per_jam'], 0, ',', '.'); ?></div>
                                <div class="booking-status <?php echo $status_class; ?>"><?php echo $status_text; ?></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="padding: 40px 20px; text-align: center; color: #94a3b8;">
                            <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
                            Belum ada pemesanan
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="cta-section">
            <h3>🏸 Pesan Lapangan Baru</h3>
            <p>Booking lapangan favoritmu sekarang juga dan rasakan pengalaman bermain yang menyenangkan!</p>
            <a href="cari_lapangan.php" class="cta-btn"><i class="fas fa-search"></i> Cari Lapangan →</a>
        </div>

        <div class="footer">
            <p>&copy; 2026 DBooking Sport - Solusi Pemesanan Lapangan Online Terpercaya</p>
        </div>
    </div>

    <div id="toastNotif" class="toast-notif"></div>

    <script>
        let toastTimeout = null;
        function showToast(message) {
            const toast = document.getElementById('toastNotif');
            if (!toast) return;
            toast.textContent = message;
            toast.classList.add('show');
            
            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 2000);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const bars = document.querySelectorAll('.bar');
            bars.forEach(bar => {
                bar.addEventListener('mouseenter', function() {
                    this.style.opacity = '0.85';
                });
                bar.addEventListener('mouseleave', function() {
                    this.style.opacity = '1';
                });
            });
        });

        const ctaBtn = document.querySelector('.cta-btn');
        if (ctaBtn) {
            ctaBtn.addEventListener('click', (e) => {
                showToast('🔄 Mengarahkan ke halaman cari lapangan...');
            });
        }

        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                if (!link.classList.contains('active')) {
                    showToast('📂 Membuka halaman ' + (link.querySelector('span')?.innerText || '...'));
                }
            });
        });

        const welcomeBanner = document.querySelector('.welcome-banner');
        if (welcomeBanner) {
            welcomeBanner.addEventListener('click', () => {
                showToast('🏟️ Ayo cari lapangan sekarang!');
            });
        }
    </script>
</body>
</html>