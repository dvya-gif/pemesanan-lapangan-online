<?php
session_start();
include 'koneksi.php';

// SET ZONA WAKTU KE WIB
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// CEK ROLE - ADMIN TIDAK BISA AKSES HALAMAN PEMESANAN USER
if ($_SESSION['role'] == 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// ========== PROSES PEMBATALAN ==========
if (isset($_GET['batalkan']) && isset($_GET['id'])) {
    $id_booking = (int)$_GET['id'];
    
    $cek = mysqli_query($conn, "SELECT * FROM pemesanan WHERE id_booking = $id_booking AND id_pengguna = $user_id AND status = 'pending'");
    
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "UPDATE pemesanan SET status = 'dibatalkan' WHERE id_booking = $id_booking");
        
        $cek_tabel_pembayaran = mysqli_query($conn, "SHOW TABLES LIKE 'pembayaran'");
        if (mysqli_num_rows($cek_tabel_pembayaran) > 0) {
            mysqli_query($conn, "UPDATE pembayaran SET status_pembayaran = 'dibatalkan' WHERE id_booking = $id_booking");
        }
        
        $query_jadwal = "UPDATE jadwal j 
                         JOIN pemesanan p ON j.id_jadwal = p.id_jadwal 
                         SET j.status = 'tersedia'
                         WHERE p.id_booking = $id_booking";
        mysqli_query($conn, $query_jadwal);
        
        header("Location: pemesanan_saya.php?msg=batal_sukses");
        exit();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'batal_sukses') {
    $success_message = "Pemesanan berhasil dibatalkan.";
}

// ========== AUTO UPDATE STATUS MENJADI SELESAI ==========
$query_auto_update = "UPDATE pemesanan p
                      JOIN jadwal j ON p.id_jadwal = j.id_jadwal
                      SET p.status = 'selesai'
                      WHERE p.status = 'dikonfirmasi' 
                      AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW()";
mysqli_query($conn, $query_auto_update);

// ========== AMBIL DATA PEMESANAN ==========
$query = "SELECT 
            p.id_booking,
            p.status as status_pemesanan,
            p.tanggal_pemesanan,
            l.id_lapangan,
            l.nama_lapangan,
            l.lokasi,
            l.harga_per_jam,
            j.id_jadwal,
            j.tanggal,
            j.jam_mulai,
            j.jam_selesai,
            j.status as status_jadwal,
            pym.id_pembayaran,
            COALESCE(pym.metode_bayar, '') as metode_bayar,
            COALESCE(pym.status_pembayaran, '') as status_pembayaran
          FROM pemesanan p 
          JOIN jadwal j ON p.id_jadwal = j.id_jadwal
          JOIN lapangan l ON j.id_lapangan = l.id_lapangan
          LEFT JOIN pembayaran pym ON p.id_booking = pym.id_booking
          WHERE p.id_pengguna = $user_id 
          ORDER BY p.tanggal_pemesanan DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

$pemesanan = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pemesanan[] = $row;
}

// Hitung statistik
$total_pemesanan = count($pemesanan);
$menunggu = 0;
$akan_datang = 0;
$selesai = 0;
$dibatalkan = 0;
$menunggu_verifikasi = 0;

$waktu_sekarang = time();

foreach ($pemesanan as $p) {
    $waktu_main_selesai = strtotime($p['tanggal'] . ' ' . $p['jam_selesai']);
    $is_masih_berlaku = ($waktu_main_selesai > $waktu_sekarang);
    
    if ($p['status_pemesanan'] == 'pending') {
        $menunggu++;
    } elseif ($p['status_pemesanan'] == 'menunggu_verifikasi') {
        $menunggu_verifikasi++;
    } elseif ($p['status_pemesanan'] == 'dibatalkan') {
        $dibatalkan++;
    } elseif ($p['status_pemesanan'] == 'dikonfirmasi' && $is_masih_berlaku) {
        $akan_datang++;
    } elseif ($p['status_pemesanan'] == 'selesai' || ($p['status_pemesanan'] == 'dikonfirmasi' && !$is_masih_berlaku)) {
        $selesai++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Saya - DBooking Sport</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f9ff; }
        .sidebar {
            position: fixed; left: 0; top: 0; width: 260px; height: 100vh;
            background: linear-gradient(180deg, #1e40af 0%, #2563eb 100%);
            padding: 25px 0; z-index: 100; overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
        .logo { padding: 0 25px; margin-bottom: 35px; text-align: center; }
        .logo h1 { font-size: 24px; font-weight: 800; color: white; }
        .logo p { font-size: 11px; color: rgba(255,255,255,0.7); margin-top: 4px; }
        .nav-menu { list-style: none; }
        .nav-item { margin-bottom: 5px; }
        .nav-link {
            display: flex; align-items: center; padding: 12px 25px;
            color: rgba(255,255,255,0.8); text-decoration: none; gap: 12px;
            font-size: 14px; transition: all 0.3s; border-radius: 12px; margin: 0 10px;
        }
        .nav-link:hover { background: rgba(255,255,255,0.15); color: white; transform: translateX(5px); }
        .nav-link.active { background: rgba(255,255,255,0.2); color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .nav-link i { width: 22px; }
        .main-content { margin-left: 260px; padding: 25px 35px; }
        .header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;
            background: white; padding: 15px 25px; border-radius: 20px;
            border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .header h1 { font-size: 24px; font-weight: 700; color: #1e293b; position: relative; }
        .header h1:before {
            content: ''; position: absolute; left: -15px; top: 50%;
            transform: translateY(-50%); width: 4px; height: 24px;
            background: #2563eb; border-radius: 4px;
        }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-info span { color: #475569; font-weight: 500; }
        .logout-btn {
            background: #ef4444; color: white; padding: 8px 18px;
            border-radius: 12px; text-decoration: none; font-size: 13px;
            transition: all 0.3s;
        }
        .logout-btn:hover { background: #dc2626; transform: translateY(-2px); }
        .alert-success {
            background: #d4edda; color: #155724; padding: 12px 20px;
            border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #28a745;
            font-size: 14px; animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .stats-section {
            background: white; border-radius: 20px; padding: 25px;
            margin-bottom: 30px; border: 1px solid #e2e8f0; transition: all 0.3s;
        }
        .stats-section:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .stats-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .stats-grid { display: flex; gap: 25px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-item {
            flex: 1; min-width: 100px; text-align: center; padding: 15px;
            background: #f8fafc; border-radius: 16px; transition: all 0.3s; cursor: pointer;
        }
        .stat-item:hover { background: #eef2ff; transform: translateY(-2px); }
        .stat-number { font-size: 36px; font-weight: 800; color: #2563eb; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 5px; font-weight: 500; }
        .stats-note {
            font-size: 12px; color: #94a3b8; padding-top: 15px;
            border-top: 1px solid #e2e8f0; margin-top: 10px;
        }
        .booking-card {
            background: white; border-radius: 24px; border: 1px solid #e2e8f0;
            overflow: hidden; margin-bottom: 20px; transition: all 0.3s;
            animation: fadeInUp 0.4s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .booking-card:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
        .card-header {
            padding: 16px 25px; background: #fafcff; border-bottom: 2px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
        }
        .booking-ref { 
            font-size: 12px; 
            color: #64748b; 
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .id-booking {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: #1e40af;
            background: #dbeafe;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            font-size: 12px;
        }
        .id-pembayaran {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #059669;
            background: #d1fae5;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            font-size: 12px;
        }
        .id-pembayaran-null {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #94a3b8;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            font-size: 12px;
        }
        .status-badge { padding: 5px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-menunggu-verifikasi { background: #fef3c7; color: #d97706; }
        .status-dikonfirmasi { background: #dbeafe; color: #2563eb; }
        .status-selesai { background: #dcfce7; color: #16a34a; }
        .status-dibatalkan { background: #fee2e2; color: #dc2626; }
        .card-body { padding: 25px; }
        .lapangan-name { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 8px; }
        .lapangan-lokasi { font-size: 12px; color: #64748b; margin-bottom: 15px; display: flex; align-items: center; gap: 5px; }
        .booking-details { display: flex; gap: 25px; margin-bottom: 20px; flex-wrap: wrap; }
        .detail-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569; background: #f8fafc; padding: 6px 12px; border-radius: 30px; }
        .tanggal-pesan {
            background: #e0e7ff;
            color: #1e40af;
        }
        .message-box { background: #eff6ff; border-radius: 16px; padding: 15px; margin: 20px 0; font-size: 13px; color: #1e40af; border-left: 3px solid #2563eb; }
        .price { font-size: 24px; font-weight: 800; color: #2563eb; margin-top: 10px; }
        .card-footer { padding: 15px 25px; background: #fafcff; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-primary {
            padding: 10px 24px; background: #2563eb; color: white; border: none;
            border-radius: 40px; cursor: pointer; font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px; font-size: 13px; transition: all 0.3s;
        }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
        .btn-outline {
            padding: 10px 24px; background: white; color: #2563eb; border: 1.5px solid #2563eb;
            border-radius: 40px; cursor: pointer; font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px; font-size: 13px; transition: all 0.3s;
        }
        .btn-outline:hover { background: #eff6ff; transform: translateY(-2px); }
        .btn-danger {
            padding: 10px 24px; background: #dc2626; color: white; border: none;
            border-radius: 40px; cursor: pointer; font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px; font-size: 13px; transition: all 0.3s;
        }
        .btn-danger:hover { background: #b91c1c; transform: translateY(-2px); }
        .empty-state { text-align: center; padding: 60px; background: white; border-radius: 24px; border: 1px solid #e2e8f0; }
        .empty-state h3 { font-size: 20px; color: #1e293b; margin-bottom: 10px; }
        .empty-state p { color: #64748b; margin-bottom: 20px; }
        .footer { text-align: center; padding: 30px; margin-top: 40px; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0; }
        .filter-buttons { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-btn {
            padding: 8px 20px; background: white; border: 1px solid #e2e8f0;
            border-radius: 40px; cursor: pointer; font-size: 13px; font-weight: 500;
            transition: all 0.3s; color: #64748b;
        }
        .filter-btn:hover { background: #f1f5f9; }
        .filter-btn.active { background: #2563eb; color: white; border-color: #2563eb; }
        .search-box { margin-bottom: 25px; position: relative; }
        .search-box input {
            width: 100%; padding: 12px 20px; padding-left: 45px;
            border: 1px solid #e2e8f0; border-radius: 50px; font-size: 14px; transition: all 0.3s;
        }
        .search-box input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .search-box i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo p, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
            .stats-grid { flex-direction: column; gap: 10px; }
            .booking-details { gap: 10px; }
            .card-footer { flex-direction: column; }
            .btn-primary, .btn-outline, .btn-danger { justify-content: center; }
            .booking-ref { flex-direction: column; align-items: flex-start; gap: 8px; }
        }
        @media print {
            .sidebar, .header, .stats-section, .card-footer, .footer, .filter-buttons, .search-box { display: none; }
            .main-content { margin: 0; padding: 0; }
            .booking-card { page-break-inside: avoid; break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><h1>🏸 DBooking</h1><p>Sport</p></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="cari_lapangan.php" class="nav-link"><i class="fas fa-search"></i> <span>Cari Lapangan</span></a></li>
            <li class="nav-item"><a href="pemesanan_saya.php" class="nav-link active"><i class="fas fa-calendar-alt"></i> <span>Pemesanan Saya</span></a></li>
            <li class="nav-item"><a href="riwayat_transaksi.php" class="nav-link"><i class="fas fa-history"></i> <span>Riwayat Transaksi</span></a></li>
            <li class="nav-item"><a href="profil_saya.php" class="nav-link"><i class="fas fa-user"></i> <span>Profil</span></a></li>
            <li class="nav-item"><a href="bantuan.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Bantuan</span></a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Keluar</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-calendar-alt" style="margin-right: 10px; color:#2563eb;"></i> Pemesanan Saya</h1>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
            </div>
        </div>

        <?php if(isset($success_message)): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="stats-section">
            <div class="stats-title"><i class="fas fa-chart-pie" style="color:#2563eb;"></i> Ringkasan Pemesanan</div>
            <div class="stats-grid">
                <div class="stat-item" onclick="filterBookings('all')"><div class="stat-number"><?php echo $total_pemesanan; ?></div><div class="stat-label"><i class="fas fa-calendar-check"></i> Total Pemesanan</div></div>
                <div class="stat-item" onclick="filterBookings('pending')"><div class="stat-number"><?php echo $menunggu; ?></div><div class="stat-label"><i class="fas fa-clock"></i> Menunggu Bayar</div></div>
                <div class="stat-item" onclick="filterBookings('menunggu_verifikasi')"><div class="stat-number"><?php echo $menunggu_verifikasi; ?></div><div class="stat-label"><i class="fas fa-hourglass-half"></i> Verifikasi</div></div>
                <div class="stat-item" onclick="filterBookings('akan_datang')"><div class="stat-number"><?php echo $akan_datang; ?></div><div class="stat-label"><i class="fas fa-calendar-week"></i> Akan Datang</div></div>
                <div class="stat-item" onclick="filterBookings('selesai')"><div class="stat-number"><?php echo $selesai; ?></div><div class="stat-label"><i class="fas fa-check-circle"></i> Selesai</div></div>
                <div class="stat-item" onclick="filterBookings('dibatalkan')"><div class="stat-number"><?php echo $dibatalkan; ?></div><div class="stat-label"><i class="fas fa-times-circle"></i> Dibatalkan</div></div>
            </div>
            <div class="stats-note"><i class="fas fa-info-circle"></i> Pemesanan dengan status "Akan Datang" adalah jadwal yang sudah dikonfirmasi dan waktu main masih tersedia.</div>
        </div>
        
        <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="Cari berdasarkan nama lapangan, ID booking, atau ID pembayaran..."></div>
        
        <div class="filter-buttons">
            <button class="filter-btn active" onclick="filterBookings('all')">Semua</button>
            <button class="filter-btn" onclick="filterBookings('pending')">Menunggu Bayar</button>
            <button class="filter-btn" onclick="filterBookings('menunggu_verifikasi')">Verifikasi</button>
            <button class="filter-btn" onclick="filterBookings('dikonfirmasi')">Akan Datang</button>
            <button class="filter-btn" onclick="filterBookings('selesai')">Selesai</button>
            <button class="filter-btn" onclick="filterBookings('dibatalkan')">Dibatalkan</button>
        </div>

        <div id="bookingsContainer">
        <?php if(count($pemesanan) > 0): ?>
            <?php foreach($pemesanan as $index => $booking): 
                $tanggal = date('d/m/Y', strtotime($booking['tanggal']));
                $tanggal_pemesanan = !empty($booking['tanggal_pemesanan']) ? date('d/m/Y H:i', strtotime($booking['tanggal_pemesanan'])) : date('d/m/Y H:i', strtotime($booking['tanggal_pemesanan']));
                $jam_mulai = substr($booking['jam_mulai'], 0, 5);
                $jam_selesai = substr($booking['jam_selesai'], 0, 5);
                
                $waktu_main_selesai = strtotime($booking['tanggal'] . ' ' . $booking['jam_selesai']);
                $waktu_sekarang = time();
                $is_expired = ($waktu_main_selesai <= $waktu_sekarang);
                
                $metode_bayar = isset($booking['metode_bayar']) ? $booking['metode_bayar'] : '';
                $status_pembayaran = isset($booking['status_pembayaran']) ? $booking['status_pembayaran'] : '';
                $id_pembayaran = isset($booking['id_pembayaran']) ? $booking['id_pembayaran'] : null;
                
                $metode_display = '';
                if (!empty($metode_bayar)) {
                    switch($metode_bayar) {
                        case 'bank_transfer': case 'transfer': $metode_display = 'Transfer Bank'; break;
                        case 'qris': case 'QRIS': $metode_display = 'QRIS'; break;
                        case 'cash': $metode_display = 'Tunai (Bayar di Tempat)'; break;
                        case 'e_wallet': case 'ewallet': $metode_display = 'E-Wallet'; break;
                        default: $metode_display = ucfirst(str_replace('_', ' ', $metode_bayar));
                    }
                }
                
                if($booking['status_pemesanan'] == 'pending') {
                    $status_text = 'Menunggu Pembayaran';
                    $status_class = 'status-pending';
                    $status_filter = 'pending';
                    $message = 'Segera selesaikan pembayaran untuk mengkonfirmasi pemesanan Anda.';
                    $icon = 'fa-clock';
                } elseif($booking['status_pemesanan'] == 'menunggu_verifikasi') {
                    $status_text = 'Menunggu Verifikasi';
                    $status_class = 'status-menunggu-verifikasi';
                    $status_filter = 'menunggu_verifikasi';
                    $message = 'Pembayaran Anda sedang diverifikasi oleh admin. Status akan berubah setelah diverifikasi.';
                    $icon = 'fa-hourglass-half';
                } elseif($booking['status_pemesanan'] == 'dibatalkan') {
                    $status_text = 'Dibatalkan';
                    $status_class = 'status-dibatalkan';
                    $status_filter = 'dibatalkan';
                    $message = 'Pemesanan ini telah dibatalkan.';
                    $icon = 'fa-times-circle';
                } elseif($booking['status_pemesanan'] == 'dikonfirmasi') {
                    if($is_expired) {
                        $status_text = 'Selesai';
                        $status_class = 'status-selesai';
                        $status_filter = 'selesai';
                        $message = 'Waktu main telah lewat. Terima kasih telah menggunakan layanan kami.';
                        $icon = 'fa-check-circle';
                    } else {
                        $status_text = 'Akan Datang';
                        $status_class = 'status-dikonfirmasi';
                        $status_filter = 'dikonfirmasi';
                        $message = 'Pemesanan Anda telah dikonfirmasi. Tunjukkan E-Tiket saat datang.';
                        $icon = 'fa-calendar-check';
                    }
                } else {
                    if($is_expired) {
                        $status_text = 'Selesai';
                        $status_class = 'status-selesai';
                        $status_filter = 'selesai';
                        $message = 'Terima kasih telah menggunakan layanan kami.';
                        $icon = 'fa-check-circle';
                    } else {
                        $status_text = 'Akan Datang';
                        $status_class = 'status-dikonfirmasi';
                        $status_filter = 'dikonfirmasi';
                        $message = 'Pemesanan Anda telah dikonfirmasi. Tunjukkan E-Tiket saat datang.';
                        $icon = 'fa-calendar-check';
                    }
                }
            ?>
            <div class="booking-card" data-status="<?php echo $status_filter; ?>" data-search="<?php echo strtolower($booking['nama_lapangan'] . ' ' . $booking['id_booking'] . ' ' . ($id_pembayaran ?: '')); ?>" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                <div class="card-header">
                    <div class="booking-ref">
                        <span class="id-booking"><i class="fas fa-ticket-alt"></i> Booking #<?php echo $booking['id_booking']; ?></span>
                        <?php if($id_pembayaran): ?>
                            <span class="id-pembayaran"><i class="fas fa-credit-card"></i> Pembayaran #<?php echo $id_pembayaran; ?></span>
                        <?php else: ?>
                            <span class="id-pembayaran-null"><i class="fas fa-minus-circle"></i> Belum Ada Pembayaran</span>
                        <?php endif; ?>
                    </div>
                    <span class="status-badge <?php echo $status_class; ?>"><i class="fas <?php echo $icon; ?>"></i> <?php echo $status_text; ?></span>
                </div>
                <div class="card-body">
                    <h3 class="lapangan-name">🏸 <?php echo htmlspecialchars($booking['nama_lapangan']); ?></h3>
                    <?php if($booking['lokasi']): ?>
                    <div class="lapangan-lokasi"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['lokasi']); ?></div>
                    <?php endif; ?>
                    <div class="booking-details">
                        <div class="detail-item"><i class="fas fa-calendar-day"></i> <?php echo $tanggal; ?></div>
                        <div class="detail-item"><i class="fas fa-clock"></i> <?php echo $jam_mulai; ?> - <?php echo $jam_selesai; ?> WIB</div>
                        <div class="detail-item tanggal-pesan"><i class="fas fa-calendar-plus"></i> Dipesan: <?php echo $tanggal_pemesanan; ?> WIB</div>
                        <?php if(!empty($metode_display)): ?>
                        <div class="detail-item"><i class="fas fa-wallet"></i> <?php echo htmlspecialchars($metode_display); ?></div>
                        <?php endif; ?>
                        <?php if(!empty($status_pembayaran) && $status_pembayaran == 'lunas'): ?>
                        <div class="detail-item" style="background: #dcfce7; color: #16a34a;"><i class="fas fa-check-circle"></i> Lunas</div>
                        <?php elseif(!empty($status_pembayaran) && $status_pembayaran == 'pending'): ?>
                        <div class="detail-item" style="background: #fef3c7; color: #d97706;"><i class="fas fa-clock"></i> Pembayaran Diproses</div>
                        <?php endif; ?>
                    </div>
                    <div class="message-box"><i class="fas fa-info-circle"></i> <?php echo $message; ?></div>
                    <div class="price">Rp <?php echo number_format($booking['harga_per_jam'], 0, ',', '.'); ?></div>
                </div>
                <div class="card-footer">
                    <?php if($booking['status_pemesanan'] == 'pending'): ?>
                        <a href="bayar.php?id=<?php echo $booking['id_booking']; ?>" class="btn-primary"><i class="fas fa-credit-card"></i> Bayar Sekarang</a>
                        <a href="?batalkan=1&id=<?php echo $booking['id_booking']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin membatalkan pemesanan ini?')"><i class="fas fa-times"></i> Batalkan</a>
                        <a href="cari_lapangan.php" class="btn-outline"><i class="fas fa-search"></i> Cari Lagi</a>
                    <?php elseif($booking['status_pemesanan'] == 'menunggu_verifikasi'): ?>
                        <span class="btn-primary" style="background: #94a3b8; cursor: not-allowed;"><i class="fas fa-hourglass-half"></i> Sedang Diverifikasi</span>
                        <a href="cari_lapangan.php" class="btn-outline"><i class="fas fa-search"></i> Cari Lagi</a>
                    <?php elseif($booking['status_pemesanan'] == 'dibatalkan'): ?>
                        <a href="cari_lapangan.php" class="btn-primary"><i class="fas fa-plus"></i> Booking Lagi</a>
                    <?php elseif($booking['status_pemesanan'] == 'dikonfirmasi' && !$is_expired): ?>
                        <button class="btn-primary" onclick="openTicket(<?php echo $booking['id_booking']; ?>, '<?php echo addslashes($booking['nama_lapangan']); ?>', '<?php echo $tanggal; ?>', '<?php echo $jam_mulai; ?>', <?php echo $booking['harga_per_jam']; ?>, '<?php echo $id_pembayaran ?: '-'; ?>')"><i class="fas fa-ticket-alt"></i> Buka Tiket</button>
                        <a href="cari_lapangan.php" class="btn-outline"><i class="fas fa-plus"></i> Booking Lagi</a>
                    <?php else: ?>
                        <a href="cari_lapangan.php" class="btn-primary"><i class="fas fa-plus"></i> Booking Lagi</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox" style="font-size: 64px; color: #cbd5e1; margin-bottom: 20px; display: block;"></i>
                <h3>Belum Ada Pemesanan</h3>
                <p>Yuk booking lapangan favoritmu sekarang!</p>
                <a href="cari_lapangan.php" class="btn-primary" style="display: inline-flex; margin-top: 15px;"><i class="fas fa-search"></i> Cari Lapangan →</a>
            </div>
        <?php endif; ?>
        </div>

        <div class="footer"><p>&copy; 2026 DBooking Sport - Pemesanan Lapangan Online</p></div>
    </div>

    <script>
        function filterBookings(status) {
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            if (event && event.target) {
                let clickedButton = event.target;
                if (clickedButton.tagName !== 'BUTTON') clickedButton = clickedButton.closest('.filter-btn');
                if (clickedButton) clickedButton.classList.add('active');
            }
            const cards = document.querySelectorAll('.booking-card');
            cards.forEach(card => {
                if (status === 'all') card.style.display = 'block';
                else card.style.display = card.dataset.status === status ? 'block' : 'none';
            });
        }
        
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                document.querySelectorAll('.booking-card').forEach(card => {
                    card.style.display = card.dataset.search.includes(searchTerm) ? 'block' : 'none';
                });
            });
        }
        
        function openTicket(id, nama, tanggal, jam, harga, idPembayaran) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>E-Ticket DBooking Sport</title>
                    <meta charset="UTF-8">
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { font-family: 'Inter', Arial, sans-serif; background: linear-gradient(135deg, #f0f9ff, #e0f2fe); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
                        .ticket { max-width: 420px; margin: 0 auto; background: white; border-radius: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); overflow: hidden; }
                        .header { background: linear-gradient(135deg, #1e40af, #2563eb); color: white; padding: 30px; text-align: center; }
                        .header h2 { font-size: 26px; margin-bottom: 5px; font-weight: 800; }
                        .header p { font-size: 12px; opacity: 0.9; }
                        .qr { font-size: 80px; text-align: center; padding: 35px; background: white; }
                        .info { padding: 25px; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
                        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
                        .info-row:last-child { border-bottom: none; }
                        .info-label { color: #64748b; font-size: 12px; font-weight: 500; }
                        .info-value { font-weight: 700; color: #1e293b; font-size: 14px; }
                        .id-box { background: #f8fafc; border-radius: 16px; padding: 12px; margin-top: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
                        .id-item { font-family: 'Courier New', monospace; font-size: 11px; background: white; padding: 6px 12px; border-radius: 20px; border: 1px solid #e2e8f0; }
                        .footer { padding: 25px; text-align: center; font-size: 11px; color: #94a3b8; }
                        .button-group { padding: 20px 25px 25px 25px; display: flex; gap: 12px; }
                        .btn-print { flex: 1; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 40px; cursor: pointer; font-size: 14px; font-weight: 600; transition: 0.3s; }
                        .btn-print:hover { background: #1d4ed8; transform: translateY(-2px); }
                        .btn-back { flex: 1; padding: 12px; background: #f1f5f9; color: #475569; border: none; border-radius: 40px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; text-align: center; display: inline-block; transition: 0.3s; }
                        .btn-back:hover { background: #e2e8f0; transform: translateY(-2px); }
                        @media print { .button-group { display: none; } body { background: white; padding: 0; } .id-box { background: #f8fafc; } }
                    </style>
                </head>
                <body>
                    <div class="ticket">
                        <div class="header"><h2>🏸 DBooking Sport</h2><p>E-TICKET</p></div>
                        <div class="qr">🎟️</div>
                        <div class="info">
                            <div class="info-row"><span class="info-label">ID Booking</span><span class="info-value">#${id}</span></div>
                            ${idPembayaran !== '-' ? `<div class="info-row"><span class="info-label">ID Pembayaran</span><span class="info-value">#${idPembayaran}</span></div>` : ''}
                            <div class="info-row"><span class="info-label">Lapangan</span><span class="info-value">${nama}</span></div>
                            <div class="info-row"><span class="info-label">Tanggal</span><span class="info-value">${tanggal}</span></div>
                            <div class="info-row"><span class="info-label">Jam</span><span class="info-value">${jam} WIB</span></div>
                            <div class="info-row"><span class="info-label">Total Bayar</span><span class="info-value">Rp ${harga.toLocaleString('id-ID')}</span></div>
                            <div class="id-box">
                                <span class="id-item"><i class="fas fa-ticket-alt"></i> Booking #${id}</span>
                                ${idPembayaran !== '-' ? `<span class="id-item"><i class="fas fa-credit-card"></i> Pembayaran #${idPembayaran}</span>` : '<span class="id-item"><i class="fas fa-clock"></i> Belum Dibayar</span>'}
                            </div>
                        </div>
                        <div class="footer"><p>Tunjukkan tiket ini saat masuk venue</p><p>Terima kasih telah menggunakan DBooking Sport!</p></div>
                        <div class="button-group"><button class="btn-print" onclick="window.print()">🖨️ Cetak Tiket</button><button class="btn-back" onclick="window.close()">← Kembali</button></div>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>