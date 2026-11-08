<?php
session_start();
include 'koneksi.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] == 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// ========== AUTO UPDATE STATUS JADWAL YANG SUDAH LEWAT ==========
$auto_update_pemesanan = "UPDATE pemesanan p 
                          JOIN jadwal j ON p.id_jadwal = j.id_jadwal 
                          SET p.status = 'selesai' 
                          WHERE (p.status = 'menunggu_verifikasi' OR p.status = 'dikonfirmasi')
                          AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW()";
mysqli_query($conn, $auto_update_pemesanan);

$auto_update_pembayaran = "UPDATE pembayaran py 
                           JOIN pemesanan p ON py.id_booking = p.id_booking
                           JOIN jadwal j ON p.id_jadwal = j.id_jadwal
                           SET py.status_pembayaran = 'lunas' 
                           WHERE py.status_pembayaran != 'lunas'
                           AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW()";
mysqli_query($conn, $auto_update_pembayaran);

// ========== QUERY DENGAN LOGIKA UNTUK CASH ==========
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
          WHERE p.id_pengguna = $user_id 
          ORDER BY j.tanggal DESC, j.jam_mulai DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

$transactions = [];
$total_lunas = 0;
$total_nominal_lunas = 0;
$total_menunggu = 0;
$total_nominal_menunggu = 0;
$total_dibatalkan = 0;
$total_semua = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $waktu_selesai = strtotime($row['tanggal'] . ' ' . $row['jam_selesai']);
    $waktu_sekarang = time();
    $is_expired = ($waktu_selesai <= $waktu_sekarang);
    
    if ($is_expired && ($row['pemesanan_status'] == 'menunggu_verifikasi' || $row['pemesanan_status'] == 'dikonfirmasi')) {
        $row['pemesanan_status'] = 'selesai';
        if (is_null($row['metode_bayar'])) {
            $row['metode_bayar'] = 'CASH';
            $row['status_pembayaran'] = 'lunas';
        }
    }
    
    $transactions[] = $row;
    $total_semua++;
    
    $status_bayar = isset($row['status_pembayaran']) ? strtolower($row['status_pembayaran']) : '';
    
    if ($status_bayar == 'lunas' || $row['pemesanan_status'] == 'selesai') {
        $total_lunas++;
        $total_nominal_lunas += $row['harga_per_jam'];
    } 
    elseif ($row['pemesanan_status'] == 'dibatalkan') {
        $total_dibatalkan++;
    }
    else {
        $total_menunggu++;
        $total_nominal_menunggu += $row['harga_per_jam'];
    }
}

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'success') {
        $message = '<div class="alert-success"><i class="fas fa-check-circle"></i> Pembayaran berhasil dikonfirmasi!</div>';
    } elseif ($_GET['msg'] == 'not_found') {
        $message = '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> Transaksi tidak ditemukan.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - DBooking Sport</title>
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
            border: 1px solid #e2e8f0;
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
        .alert-success { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #28a745; font-size: 14px; animation: slideDown 0.3s ease-out; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #dc2626; font-size: 14px; animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 20px; padding: 25px; border: 1px solid #e2e8f0; transition: all 0.3s; cursor: pointer; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, #2563eb, #60a5fa); transform: scaleX(0); transition: transform 0.3s; }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .stat-card.lunas { border-left: 4px solid #16a34a; }
        .stat-card.menunggu { border-left: 4px solid #f59e0b; }
        .stat-card.total { border-left: 4px solid #3b82f6; }
        .stat-title { font-size: 13px; font-weight: 500; color: #64748b; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .stat-amount { font-size: 28px; font-weight: 800; color: #1e293b; margin-bottom: 5px; }
        .stat-sub { font-size: 11px; color: #94a3b8; }
        .stat-card.lunas .stat-amount { color: #16a34a; }
        .stat-card.menunggu .stat-amount { color: #f59e0b; }
        .stat-card.total .stat-amount { color: #3b82f6; }
        .table-container { background: white; border-radius: 24px; border: 1px solid #e2e8f0; overflow-x: auto; transition: all 0.3s; }
        .table-container:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
        .table-header { padding: 18px 25px; background: #fafcff; border-bottom: 2px solid #f1f5f9; }
        .table-header h3 { font-size: 16px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        .table-header p { font-size: 12px; color: #64748b; margin-top: 5px; }
        .transaction-table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        .transaction-table th { text-align: left; padding: 15px 20px; background: #f8fafc; font-size: 12px; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0; }
        .transaction-table td { padding: 15px 20px; font-size: 13px; color: #475569; border-bottom: 1px solid #f1f5f9; }
        .transaction-table tr:last-child td { border-bottom: none; }
        .transaction-table tr:hover td { background: #f8fafc; }
        
        /* ID Pembayaran Badge */
        .id-pembayaran-badge {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            background: #d1fae5;
            color: #059669;
        }
        .id-pembayaran-null {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #94a3b8;
        }
        
        .status-lunas { display: inline-block; padding: 4px 12px; background: #dcfce7; color: #16a34a; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .status-pending { display: inline-block; padding: 4px 12px; background: #fef3c7; color: #d97706; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .status-selesai { display: inline-block; padding: 4px 12px; background: #d1fae5; color: #065f46; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .status-dibatalkan { display: inline-block; padding: 4px 12px; background: #fee2e2; color: #dc2626; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .method-cash { display: inline-block; padding: 4px 12px; background: #dcfce7; color: #065f46; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .method-transfer { display: inline-block; padding: 4px 12px; background: #dbeafe; color: #1e40af; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .method-ewallet { display: inline-block; padding: 4px 12px; background: #fef3c7; color: #d97706; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .method-qris { display: inline-block; padding: 4px 12px; background: #e0e7ff; color: #4338ca; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .method-other { display: inline-block; padding: 4px 12px; background: #f1f5f9; color: #64748b; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .empty-state { text-align: center; padding: 60px; background: white; border-radius: 24px; border: 1px solid #e2e8f0; }
        .empty-state h3 { font-size: 20px; color: #1e293b; margin-bottom: 10px; }
        .empty-state p { color: #64748b; margin-bottom: 20px; }
        .btn-primary { padding: 10px 24px; background: #2563eb; color: white; border: none; border-radius: 40px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; transition: all 0.3s; }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
        .footer { text-align: center; padding: 30px; margin-top: 40px; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0; }
        .search-box { margin-bottom: 20px; position: relative; }
        .search-box input { width: 100%; padding: 12px 20px; padding-left: 45px; border: 1px solid #e2e8f0; border-radius: 50px; font-size: 14px; transition: all 0.3s; }
        .search-box input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .search-box i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .logo p, .sidebar .nav-link span { display: none; } .main-content { margin-left: 70px; padding: 20px; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><h1>🏸 DBooking</h1><p>Sport</p></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="cari_lapangan.php" class="nav-link"><i class="fas fa-search"></i> <span>Cari Lapangan</span></a></li>
            <li class="nav-item"><a href="pemesanan_saya.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Pemesanan Saya</span></a></li>
            <li class="nav-item"><a href="riwayat_transaksi.php" class="nav-link active"><i class="fas fa-history"></i> <span>Riwayat Transaksi</span></a></li>
            <li class="nav-item"><a href="profil_saya.php" class="nav-link"><i class="fas fa-user"></i> <span>Profil</span></a></li>
            <li class="nav-item"><a href="bantuan.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Bantuan</span></a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Keluar</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-history" style="margin-right: 10px; color:#2563eb;"></i> Riwayat Transaksi</h1>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cari berdasarkan ID booking, ID pembayaran, atau nama lapangan...">
        </div>

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-title"><i class="fas fa-chart-line"></i> TOTAL RIWAYAT</div>
                <div class="stat-amount"><?php echo $total_semua; ?></div>
                <div class="stat-sub">Semua transaksi</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list-ul"></i> Riwayat Pemesanan & Transaksi</h3>
                <p>Daftar semua pemesanan dan status pembayaran Anda</p>
            </div>
            
            <?php if(count($transactions) > 0): ?>
            <table class="transaction-table" id="transactionTable">
                <thead>
                    <tr>
                        <th>ID Booking</th>
                        <th>ID Pembayaran</th>
                        <th>Lapangan</th>
                        <th>Tanggal & Jam</th>
                        <th>Metode</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transactions as $item): 
                        $tanggal_jadwal = date('d/m/Y', strtotime($item['tanggal']));
                        $jam_mulai = substr($item['jam_mulai'], 0, 5);
                        $jam_selesai = substr($item['jam_selesai'], 0, 5);
                        $tanggal_jam = $tanggal_jadwal . ' ' . $jam_mulai . ' - ' . $jam_selesai . ' WIB';
                        
                        $metode = isset($item['metode_bayar']) ? strtoupper($item['metode_bayar']) : '-';
                        $id_pembayaran = isset($item['id_pembayaran']) ? $item['id_pembayaran'] : null;
                        
                        // Tampilan metode dengan badge
                        if ($metode == 'CASH') {
                            $metode_badge = '<span class="method-cash"><i class="fas fa-money-bill-wave"></i> TUNAI</span>';
                        } elseif ($metode == 'TRANSFER') {
                            $metode_badge = '<span class="method-transfer"><i class="fas fa-university"></i> TRANSFER</span>';
                        } elseif ($metode == 'EWALLET') {
                            $metode_badge = '<span class="method-ewallet"><i class="fas fa-mobile-alt"></i> E-WALLET</span>';
                        } elseif ($metode == 'QRIS') {
                            $metode_badge = '<span class="method-qris"><i class="fas fa-qrcode"></i> QRIS</span>';
                        } else {
                            $metode_badge = '<span class="method-other">-</span>';
                        }
                        
                        // Tampilan ID Pembayaran
                        if ($id_pembayaran) {
                            $id_pembayaran_display = '<span class="id-pembayaran-badge"><i class="fas fa-credit-card"></i> #' . $id_pembayaran . '</span>';
                        } else {
                            $id_pembayaran_display = '<span class="id-pembayaran-null"><i class="fas fa-minus-circle"></i> -</span>';
                        }
                        
                        $nominal = number_format($item['harga_per_jam'], 0, ',', '.');
                        $status_bayar = isset($item['status_pembayaran']) ? strtolower($item['status_pembayaran']) : '';
                        
                        $waktu_selesai = strtotime($item['tanggal'] . ' ' . $item['jam_selesai']);
                        $waktu_sekarang = time();
                        $is_expired = ($waktu_selesai <= $waktu_sekarang);
                        
                        if ($status_bayar == 'lunas' || $item['pemesanan_status'] == 'selesai') {
                            $status_class = 'status-lunas';
                            $status_text = 'LUNAS';
                        } elseif ($item['pemesanan_status'] == 'dibatalkan') {
                            $status_class = 'status-dibatalkan';
                            $status_text = 'DIBATALKAN';
                        } elseif ($is_expired && ($item['pemesanan_status'] == 'menunggu_verifikasi' || $item['pemesanan_status'] == 'dikonfirmasi')) {
                            $status_class = 'status-selesai';
                            $status_text = 'SELESAI';
                        } else {
                            $status_class = 'status-pending';
                            $status_text = 'MENUNGGU';
                        }
                        
                        $search_data = strtolower($item['id_booking'] . ' ' . ($id_pembayaran ?: '') . ' ' . $item['nama_lapangan']);
                    ?>
                    <tr data-search="<?php echo $search_data; ?>">
                        <td><strong>#<?php echo $item['id_booking']; ?></strong></td>
                        <td><?php echo $id_pembayaran_display; ?></td>
                        <td><?php echo htmlspecialchars($item['nama_lapangan']); ?></td>
                        <td><?php echo $tanggal_jam; ?></td>
                        <td><?php echo $metode_badge; ?></td>
                        <td>Rp <?php echo $nominal; ?></td>
                        <td><span class="<?php echo $status_class; ?>"><i class="fas <?php echo $status_text == 'LUNAS' || $status_text == 'SELESAI' ? 'fa-check-circle' : ($status_text == 'DIBATALKAN' ? 'fa-times-circle' : 'fa-clock'); ?>"></i> <?php echo $status_text; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox" style="font-size: 64px; color: #cbd5e1; margin-bottom: 20px; display: block;"></i>
                <h3>Belum Ada Pemesanan</h3>
                <p>Anda belum memiliki riwayat pemesanan.</p>
                <a href="cari_lapangan.php" class="btn-primary"><i class="fas fa-search"></i> Booking Sekarang →</a>
            </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>&copy; 2026 DBooking Sport - Pemesanan Lapangan Online</p>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#transactionTable tbody tr');
                rows.forEach(row => {
                    const searchData = row.getAttribute('data-search');
                    row.style.display = searchData.includes(searchTerm) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>