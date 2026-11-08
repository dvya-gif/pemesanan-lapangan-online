<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Ambil ID booking dari URL
$id_booking = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_booking == 0) {
    header("Location: riwayat_transaksi.php?msg=not_found");
    exit();
}

// Query ambil detail transaksi (DENGAN LOGIKA CASH)
$query = "SELECT 
            p.id_booking,
            p.status as pemesanan_status,
            p.tanggal_pemesanan as tgl_pemesanan,
            j.tanggal,
            j.jam_mulai,
            j.jam_selesai,
            l.id_lapangan,
            l.nama_lapangan,
            l.lokasi,
            l.harga_per_jam,
            py.id_pembayaran,
            CASE 
                WHEN py.metode_bayar IS NOT NULL THEN py.metode_bayar
                WHEN p.status IN ('selesai', 'dikonfirmasi') THEN 'CASH'
                ELSE NULL
            END as metode_bayar,
            CASE 
                WHEN py.status_pembayaran IS NOT NULL THEN py.status_pembayaran
                WHEN p.status IN ('selesai', 'dikonfirmasi') THEN 'lunas'
                WHEN p.status = 'dibatalkan' THEN 'ditolak'
                ELSE NULL
            END as status_pembayaran
          FROM pemesanan p 
          JOIN jadwal j ON p.id_jadwal = j.id_jadwal
          JOIN lapangan l ON j.id_lapangan = l.id_lapangan
          LEFT JOIN pembayaran py ON p.id_booking = py.id_booking
          WHERE p.id_booking = $id_booking AND p.id_pengguna = $user_id";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: riwayat_transaksi.php?msg=not_found");
    exit();
}

$transaksi = mysqli_fetch_assoc($result);

// Format data
$invoice_number = 'INV-' . str_pad($transaksi['id_booking'], 5, '0', STR_PAD_LEFT);
$tanggal_transaksi = date('d/m/Y H:i', strtotime($transaksi['tgl_pemesanan']));
$tanggal_main = date('d/m/Y', strtotime($transaksi['tanggal']));
$jam_main = substr($transaksi['jam_mulai'], 0, 5) . ' - ' . substr($transaksi['jam_selesai'], 0, 5) . ' WIB';
$harga = number_format($transaksi['harga_per_jam'], 0, ',', '.');

// Metode pembayaran display
$metode = isset($transaksi['metode_bayar']) ? strtoupper($transaksi['metode_bayar']) : '-';
if ($metode == 'CASH') {
    $metode_display = 'TUNAI';
} elseif ($metode == 'TRANSFER') {
    $metode_display = 'TRANSFER BANK';
} elseif ($metode == 'EWALLET') {
    $metode_display = 'E-WALLET';
} elseif ($metode == 'QRIS') {
    $metode_display = 'QRIS';
} else {
    $metode_display = '-';
}

// Status pembayaran
$status = isset($transaksi['status_pembayaran']) ? strtolower($transaksi['status_pembayaran']) : '';
if ($status == 'lunas') {
    $status_text = 'LUNAS';
    $status_class = 'success';
    $status_icon = 'fa-check-circle';
    $status_color = '#16a34a';
} elseif ($status == 'ditolak') {
    $status_text = 'DITOLAK';
    $status_class = 'danger';
    $status_icon = 'fa-times-circle';
    $status_color = '#dc2626';
} else {
    $status_text = 'PENDING';
    $status_class = 'warning';
    $status_icon = 'fa-clock';
    $status_color = '#d97706';
}

// Status pemesanan
$status_pemesanan = $transaksi['pemesanan_status'];
if ($status_pemesanan == 'pending') {
    $status_pemesanan_text = 'Menunggu Pembayaran';
    $status_pemesanan_class = 'warning';
} elseif ($status_pemesanan == 'menunggu_verifikasi') {
    $status_pemesanan_text = 'Menunggu Verifikasi';
    $status_pemesanan_class = 'warning';
} elseif ($status_pemesanan == 'dikonfirmasi') {
    $status_pemesanan_text = 'Dikonfirmasi';
    $status_pemesanan_class = 'success';
} elseif ($status_pemesanan == 'selesai') {
    $status_pemesanan_text = 'Selesai';
    $status_pemesanan_class = 'info';
} elseif ($status_pemesanan == 'dibatalkan') {
    $status_pemesanan_text = 'Dibatalkan';
    $status_pemesanan_class = 'danger';
} else {
    $status_pemesanan_text = ucfirst($status_pemesanan);
    $status_pemesanan_class = 'secondary';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi - DBooking Sport</title>
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

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: #2563eb;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .btn-back:hover {
            background: #f1f5f9;
            transform: translateX(-3px);
        }

        .detail-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .invoice-card {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 25px;
            animation: fadeInUp 0.4s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .invoice-header {
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .invoice-header h2 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 5px;
        }
        .invoice-header p {
            font-size: 13px;
            opacity: 0.9;
        }
        .invoice-header .status-badge {
            display: inline-block;
            margin-top: 15px;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge.success {
            background: #16a34a;
            color: white;
        }
        .status-badge.warning {
            background: #d97706;
            color: white;
        }
        .status-badge.danger {
            background: #dc2626;
            color: white;
        }
        .status-badge.info {
            background: #0ea5e9;
            color: white;
        }
        .status-badge.secondary {
            background: #64748b;
            color: white;
        }

        .invoice-body {
            padding: 30px;
        }

        .info-section {
            margin-bottom: 30px;
        }
        .info-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-label {
            font-size: 13px;
            color: #64748b;
        }
        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }

        .divider {
            height: 1px;
            background: #e2e8f0;
            margin: 25px 0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            background: #f8fafc;
            border-radius: 12px;
            margin-top: 15px;
        }
        .total-label {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }
        .total-value {
            font-size: 24px;
            font-weight: 800;
            color: #2563eb;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        .btn-primary {
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }
        .btn-outline {
            padding: 12px 24px;
            background: white;
            color: #2563eb;
            border: 1.5px solid #2563eb;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-outline:hover {
            background: #eff6ff;
            transform: translateY(-2px);
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
            .info-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
            .action-buttons a, .action-buttons button { text-align: center; justify-content: center; }
        }

        @media print {
            .sidebar, .header, .action-buttons, .footer, .btn-back {
                display: none;
            }
            .main-content {
                margin: 0;
                padding: 0;
            }
            .invoice-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            body {
                background: white;
            }
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
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
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
            <h1><i class="fas fa-file-invoice" style="margin-right: 10px; color:#2563eb;"></i> Detail Transaksi</h1>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
            </div>
        </div>

        <div class="detail-container">
            <a href="riwayat_transaksi.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
            </a>

            <div class="invoice-card">
                <div class="invoice-header">
                    <h2>INVOICE</h2>
                    <p><?php echo $invoice_number; ?></p>
                    <div class="status-badge <?php echo $status_class; ?>">
                        <i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                    </div>
                </div>
                <div class="invoice-body">
                    <!-- Informasi Transaksi -->
                    <div class="info-section">
                        <div class="info-title">
                            <i class="fas fa-receipt"></i> Informasi Transaksi
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">ID Transaksi</span>
                                <span class="info-value"><?php echo $invoice_number; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">ID Booking</span>
                                <span class="info-value">booking-<?php echo $transaksi['id_booking']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tanggal Transaksi</span>
                                <span class="info-value"><?php echo $tanggal_transaksi; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Metode Pembayaran</span>
                                <span class="info-value"><?php echo $metode_display; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status Pembayaran</span>
                                <span class="info-value" style="color: <?php echo $status_color; ?>;"><?php echo $status_text; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Lapangan & Jadwal -->
                    <div class="info-section">
                        <div class="info-title">
                            <i class="fas fa-map-marker-alt"></i> Informasi Lapangan
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Nama Lapangan</span>
                                <span class="info-value">🏸 <?php echo htmlspecialchars($transaksi['nama_lapangan']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Lokasi</span>
                                <span class="info-value"><?php echo htmlspecialchars($transaksi['lokasi']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tanggal Main</span>
                                <span class="info-value"><?php echo $tanggal_main; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Jam Main</span>
                                <span class="info-value"><?php echo $jam_main; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status Pemesanan</span>
                                <span class="info-value">
                                    <span class="status-badge <?php echo $status_pemesanan_class; ?>" style="font-size: 10px; padding: 2px 10px;">
                                        <?php echo $status_pemesanan_text; ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Harga -->
                    <div class="info-section">
                        <div class="info-title">
                            <i class="fas fa-calculator"></i> Detail Harga
                        </div>
                        <div class="info-item">
                            <span class="info-label">Harga per Jam</span>
                            <span class="info-value">Rp <?php echo $harga; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Durasi</span>
                            <span class="info-value">1 Jam</span>
                        </div>
                        <div class="divider"></div>
                        <div class="total-row">
                            <span class="total-label">TOTAL PEMBAYARAN</span>
                            <span class="total-value">Rp <?php echo $harga; ?></span>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="action-buttons">
                        <?php if($status == 'pending'): ?>
                            <a href="bayar.php?id=<?php echo $transaksi['id_booking']; ?>" class="btn-primary">
                                <i class="fas fa-credit-card"></i> Selesaikan Pembayaran
                            </a>
                            <button class="btn-outline" onclick="window.print()">
                                <i class="fas fa-print"></i> Cetak Invoice
                            </button>
                        <?php elseif($status == 'lunas'): ?>
                            <button class="btn-primary" onclick="openTicket(<?php echo $transaksi['id_booking']; ?>, '<?php echo addslashes($transaksi['nama_lapangan']); ?>', '<?php echo $tanggal_main; ?>', '<?php echo substr($transaksi['jam_mulai'], 0, 5); ?>', <?php echo $transaksi['harga_per_jam']; ?>)">
                                <i class="fas fa-ticket-alt"></i> Buka E-Tiket
                            </button>
                            <button class="btn-outline" onclick="window.print()">
                                <i class="fas fa-print"></i> Cetak Invoice
                            </button>
                        <?php else: ?>
                            <a href="cari_lapangan.php" class="btn-primary">
                                <i class="fas fa-plus"></i> Booking Lagi
                            </a>
                            <button class="btn-outline" onclick="window.print()">
                                <i class="fas fa-print"></i> Cetak Invoice
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2026 DBooking Sport - Pemesanan Lapangan Online</p>
        </div>
    </div>

    <script>
        function openTicket(id, nama, tanggal, jam, harga) {
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
                        .info-row { display: flex; justify-content: space-between; padding: 10px 0; }
                        .info-label { color: #64748b; font-size: 12px; font-weight: 500; }
                        .info-value { font-weight: 700; color: #1e293b; font-size: 14px; }
                        .footer { padding: 25px; text-align: center; font-size: 11px; color: #94a3b8; }
                        .button-group { padding: 20px 25px 25px 25px; display: flex; gap: 12px; }
                        .btn-print { flex: 1; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 40px; cursor: pointer; font-size: 14px; font-weight: 600; transition: 0.3s; }
                        .btn-print:hover { background: #1d4ed8; transform: translateY(-2px); }
                        .btn-back { flex: 1; padding: 12px; background: #f1f5f9; color: #475569; border: none; border-radius: 40px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; text-align: center; display: inline-block; transition: 0.3s; }
                        .btn-back:hover { background: #e2e8f0; transform: translateY(-2px); }
                        @media print {
                            .button-group { display: none; }
                            body { background: white; padding: 0; }
                        }
                    </style>
                </head>
                <body>
                    <div class="ticket">
                        <div class="header">
                            <h2>🏸 DBooking Sport</h2>
                            <p>E-TICKET</p>
                        </div>
                        <div class="qr">🎟️</div>
                        <div class="info">
                            <div class="info-row"><span class="info-label">ID Booking</span><span class="info-value">#${id}</span></div>
                            <div class="info-row"><span class="info-label">Lapangan</span><span class="info-value">${nama}</span></div>
                            <div class="info-row"><span class="info-label">Tanggal</span><span class="info-value">${tanggal}</span></div>
                            <div class="info-row"><span class="info-label">Jam</span><span class="info-value">${jam} WIB</span></div>
                            <div class="info-row"><span class="info-label">Total Bayar</span><span class="info-value">Rp ${harga.toLocaleString('id-ID')}</span></div>
                        </div>
                        <div class="footer">
                            <p>Tunjukkan tiket ini saat masuk venue</p>
                            <p>Terima kasih telah menggunakan DBooking Sport!</p>
                        </div>
                        <div class="button-group">
                            <button class="btn-print" onclick="window.print()">🖨️ Cetak Tiket</button>
                            <button class="btn-back" onclick="window.close()">← Kembali</button>
                        </div>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>