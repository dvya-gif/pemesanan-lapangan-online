<?php
session_start();
include 'koneksi.php';

// CEK ROLE - HARUS ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// ========== AUTO UPDATE STATUS JADWAL YANG SUDAH LEWAT ==========
// Update jadwal yang tanggalnya sudah lewat
// - Jika status 'dipesan' → menjadi 'selesai'
// - Jika status 'tersedia' → menjadi 'kadaluarsa'
$query_update = "UPDATE jadwal 
    SET status = CASE 
        WHEN status = 'dipesan' THEN 'selesai'
        WHEN status = 'tersedia' THEN 'kadaluarsa'
        ELSE status
    END
    WHERE tanggal < CURDATE() 
    OR (tanggal = CURDATE() AND jam_selesai < CURTIME())";
mysqli_query($conn, $query_update);

$admin_name = $_SESSION['user_name'];
$message = '';
$error = '';

// ========== GENERATE JADWAL ==========
if (isset($_POST['generate'])) {
    $tanggal_mulai = mysqli_real_escape_string($conn, $_POST['tanggal_mulai']);
    $tanggal_akhir = mysqli_real_escape_string($conn, $_POST['tanggal_akhir']);
    $id_lapangan = (int)$_POST['id_lapangan'];
    
    // Jam operasional (08:00 - 22:00, per 1 jam)
    $jam_operasional = [
        '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00',
        '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00',
        '18:00:00', '19:00:00', '20:00:00', '21:00:00', '22:00:00'
    ];
    
    // Query lapangan
    $lapangan_query = ($id_lapangan > 0) ? "WHERE id_lapangan = $id_lapangan" : "";
    $lapangan_list_gen = mysqli_query($conn, "SELECT id_lapangan FROM lapangan $lapangan_query");
    
    if (mysqli_num_rows($lapangan_list_gen) == 0) {
        $error = "Tidak ada lapangan yang dipilih!";
    } else {
        $start = new DateTime($tanggal_mulai);
        $end = new DateTime($tanggal_akhir);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($start, $interval, $end);
        
        $total_insert = 0;
        $total_skip = 0;
        $total_exist = 0;
        
        foreach ($date_range as $date) {
            $tanggal = $date->format('Y-m-d');
            
            // Skip tanggal yang sudah lewat
            if ($tanggal < date('Y-m-d')) {
                $total_skip++;
                continue;
            }
            
            mysqli_data_seek($lapangan_list_gen, 0);
            while ($lapangan = mysqli_fetch_assoc($lapangan_list_gen)) {
                $id_lap = $lapangan['id_lapangan'];
                
                foreach ($jam_operasional as $jam_mulai) {
                    $jam_selesai = date('H:i:s', strtotime($jam_mulai . ' +1 hour'));
                    
                    // Cek apakah sudah ada
                    $cek = mysqli_query($conn, "SELECT id_jadwal FROM jadwal 
                        WHERE id_lapangan = $id_lap AND tanggal = '$tanggal' AND jam_mulai = '$jam_mulai'");
                    
                    if (mysqli_num_rows($cek) == 0) {
                        mysqli_query($conn, "INSERT INTO jadwal (id_lapangan, tanggal, jam_mulai, jam_selesai, status) 
                            VALUES ($id_lap, '$tanggal', '$jam_mulai', '$jam_selesai', 'tersedia')");
                        $total_insert++;
                    } else {
                        $total_exist++;
                    }
                }
            }
        }
        
        $message = "✅ Berhasil generate $total_insert jadwal baru";
        if ($total_exist > 0) $message .= " ($total_exist sudah ada)";
        if ($total_skip > 0) $message .= " (Skip $total_skip tanggal yang sudah lewat)";
    }
}

// Filter tanggal dan lapangan
$tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$lapangan_filter = isset($_GET['lapangan']) ? (int)$_GET['lapangan'] : 0;
$status_filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Ambil semua lapangan untuk filter
$lapangan_list = mysqli_query($conn, "SELECT l.*, jo.nama_olahraga 
    FROM lapangan l 
    JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga 
    ORDER BY l.nama_lapangan ASC");

// Query ambil jadwal
$query_jadwal = "SELECT j.*, l.nama_lapangan, l.harga_per_jam, jo.nama_olahraga, l.id_lapangan as lapangan_id
    FROM jadwal j 
    JOIN lapangan l ON j.id_lapangan = l.id_lapangan 
    JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga 
    WHERE j.tanggal = '$tanggal_filter'";

if ($lapangan_filter > 0) {
    $query_jadwal .= " AND j.id_lapangan = $lapangan_filter";
}

if ($status_filter && $status_filter != '') {
    $query_jadwal .= " AND j.status = '$status_filter'";
}

$query_jadwal .= " ORDER BY l.nama_lapangan, j.jam_mulai";

$jadwal = mysqli_query($conn, $query_jadwal);

// Statistik (hanya 4 status)
$total_tersedia = 0;
$total_dipesan = 0;
$total_selesai = 0;
$total_kadaluarsa = 0;

$stat_query = "SELECT status FROM jadwal WHERE tanggal = '$tanggal_filter'";
if ($lapangan_filter > 0) {
    $stat_query .= " AND id_lapangan = $lapangan_filter";
}
$stat_result = mysqli_query($conn, $stat_query);
while($row = mysqli_fetch_assoc($stat_result)) {
    switch($row['status']) {
        case 'tersedia': $total_tersedia++; break;
        case 'dipesan': $total_dipesan++; break;
        case 'selesai': $total_selesai++; break;
        case 'kadaluarsa': $total_kadaluarsa++; break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Lapangan - Admin</title>
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
        
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #16a34a;
        }
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-number { font-size: 28px; font-weight: 800; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 5px; }
        .stat-tersedia .stat-number { color: #22c55e; }
        .stat-dipesan .stat-number { color: #f59e0b; }
        .stat-selesai .stat-number { color: #16a34a; }
        .stat-kadaluarsa .stat-number { color: #94a3b8; }
        
        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
            border: 1px solid #e2e8f0;
        }
        .filter-box input, .filter-box select {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
        }
        .btn-primary {
            background: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); }
        .btn-success {
            background: #22c55e;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-success:hover { background: #16a34a; transform: translateY(-2px); }
        
        .table-container {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: auto;
        }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th { text-align: left; padding: 12px 15px; background: #f8fafc; font-size: 13px; font-weight: 600; color: #1e293b; }
        td { padding: 12px 15px; font-size: 13px; color: #475569; border-bottom: 1px solid #e2e8f0; }
        
        .id-badge {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .id-jadwal {
            background: #e0e7ff;
            color: #1e40af;
        }
        .id-lapangan {
            background: #dcfce7;
            color: #16a34a;
        }
        .tanggal-badge {
            background: #fef3c7;
            color: #d97706;
            font-family: monospace;
            font-size: 11px;
        }
        
        .status-tersedia { background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; display: inline-block; font-size: 11px; font-weight: 600; }
        .status-dipesan { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; display: inline-block; font-size: 11px; font-weight: 600; }
        .status-selesai { background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; display: inline-block; font-size: 11px; font-weight: 600; }
        .status-kadaluarsa { background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 20px; display: inline-block; font-size: 11px; font-weight: 600; text-decoration: line-through; }
        
        .olahraga-badge {
            background: #e0e7ff;
            color: #2563eb;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
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
        }
        .modal-header {
            padding: 20px;
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: white;
            display: flex;
            justify-content: space-between;
        }
        .modal-header span { font-size: 28px; cursor: pointer; opacity: 0.8; }
        .modal-header span:hover { opacity: 1; }
        .modal-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; color: #1e293b; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px; border: 1.5px solid #e2e8f0;
            border-radius: 12px; font-family: 'Inter', sans-serif;
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
        }
        .btn-simpan:hover { transform: translateY(-2px); }
        
        @media (max-width: 768px) { 
            .sidebar { width: 70px; } 
            .sidebar .logo p, .sidebar .nav-link span { display: none; } 
            .main-content { margin-left: 70px; padding: 20px; } 
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
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
            <li><a href="admin_jadwal.php" class="nav-link active"><i class="fas fa-calendar-alt"></i> <span>Jadwal Lapangan</span></a></li>
            <li><a href="admin_pemesanan.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>Kelola Pemesanan</span></a></li>
            <li><a href="admin_verifikasi.php" class="nav-link"><i class="fas fa-credit-card"></i> <span>Verifikasi Pembayaran</span></a></li>
            <li><a href="admin_pengguna.php" class="nav-link"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
            <li><a href="admin_laporan.php" class="nav-link"><i class="fas fa-file-alt"></i> <span>Laporan</span></a></li>
            <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> Jadwal Lapangan</h1>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <?php if($message): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Tombol Generate -->
        <div style="margin-bottom: 20px;">
            <button class="btn-success" onclick="openModal('generateModal')">
                <i class="fas fa-calendar-plus"></i> Generate Jadwal
            </button>
        </div>
        
        <!-- Statistik (4 kolom) -->
        <div class="stats-grid">
            <div class="stat-card stat-tersedia" onclick="window.location.href='?tanggal=<?php echo $tanggal_filter; ?>&lapangan=<?php echo $lapangan_filter; ?>&filter=tersedia'">
                <div class="stat-number"><?php echo $total_tersedia; ?></div>
                <div class="stat-label"><i class="fas fa-check-circle"></i> Tersedia</div>
            </div>
            <div class="stat-card stat-dipesan" onclick="window.location.href='?tanggal=<?php echo $tanggal_filter; ?>&lapangan=<?php echo $lapangan_filter; ?>&filter=dipesan'">
                <div class="stat-number"><?php echo $total_dipesan; ?></div>
                <div class="stat-label"><i class="fas fa-clock"></i> Dipesan</div>
            </div>
            <div class="stat-card stat-selesai" onclick="window.location.href='?tanggal=<?php echo $tanggal_filter; ?>&lapangan=<?php echo $lapangan_filter; ?>&filter=selesai'">
                <div class="stat-number"><?php echo $total_selesai; ?></div>
                <div class="stat-label"><i class="fas fa-check-double"></i> Selesai</div>
            </div>
            <div class="stat-card stat-kadaluarsa" onclick="window.location.href='?tanggal=<?php echo $tanggal_filter; ?>&lapangan=<?php echo $lapangan_filter; ?>&filter=kadaluarsa'">
                <div class="stat-number"><?php echo $total_kadaluarsa; ?></div>
                <div class="stat-label"><i class="fas fa-calendar-times"></i> Kadaluarsa</div>
            </div>
        </div>
        
        <!-- Filter -->
        <form method="GET" class="filter-box">
            <div>
                <label>📅 Pilih Tanggal</label><br>
                <input type="date" name="tanggal" value="<?php echo $tanggal_filter; ?>">
            </div>
            <div>
                <label>🏟️ Pilih Lapangan</label><br>
                <select name="lapangan">
                    <option value="0">-- Semua Lapangan --</option>
                    <?php mysqli_data_seek($lapangan_list, 0); while($l = mysqli_fetch_assoc($lapangan_list)): ?>
                    <option value="<?php echo $l['id_lapangan']; ?>" <?php echo $lapangan_filter == $l['id_lapangan'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($l['nama_lapangan']); ?> (<?php echo $l['nama_olahraga']; ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
            <div>
                <button type="button" class="btn-primary" onclick="window.location.href='admin_jadwal.php?tanggal=<?php echo date('Y-m-d'); ?>'" style="background:#64748b;">
                    <i class="fas fa-calendar-day"></i> Hari Ini
                </button>
            </div>
            <?php if($status_filter): ?>
            <div>
                <a href="admin_jadwal.php?tanggal=<?php echo $tanggal_filter; ?>&lapangan=<?php echo $lapangan_filter; ?>" class="btn-primary" style="background:#ef4444; text-decoration:none;">
                    <i class="fas fa-times"></i> Hapus Filter
                </a>
            </div>
            <?php endif; ?>
        </form>
        
        <!-- Tabel Jadwal -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID Jadwal</th>
                        <th>ID Lapangan</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Lapangan</th>
                        <th>Jenis Olahraga</th>
                        <th>Harga/Jam</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($jadwal) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($jadwal)): 
                            $status_class = '';
                            $status_text = '';
                            switch($row['status']) {
                                case 'tersedia':
                                    $status_class = 'status-tersedia';
                                    $status_text = 'Tersedia';
                                    break;
                                case 'dipesan':
                                    $status_class = 'status-dipesan';
                                    $status_text = 'Dipesan';
                                    break;
                                case 'selesai':
                                    $status_class = 'status-selesai';
                                    $status_text = 'Selesai';
                                    break;
                                case 'kadaluarsa':
                                    $status_class = 'status-kadaluarsa';
                                    $status_text = 'Kadaluarsa';
                                    break;
                                default:
                                    $status_class = 'status-tersedia';
                                    $status_text = 'Tersedia';
                            }
                        ?>
                        <tr>
                            <td><span class="id-badge id-jadwal"><i class="fas fa-hashtag"></i> <?php echo $row['id_jadwal']; ?></span></td>
                            <td><span class="id-badge id-lapangan"><i class="fas fa-hashtag"></i> <?php echo $row['lapangan_id']; ?></span></td>
                            <td><span class="id-badge tanggal-badge"><i class="fas fa-calendar-day"></i> <?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></span></td>
                            <td><strong><?php echo substr($row['jam_mulai'],0,5); ?> - <?php echo substr($row['jam_selesai'],0,5); ?></strong> WIB</td>
                            <td><strong><?php echo htmlspecialchars($row['nama_lapangan']); ?></strong></td>
                            <td><span class="olahraga-badge"><?php echo htmlspecialchars($row['nama_olahraga']); ?></span></td>
                            <td>Rp <?php echo number_format($row['harga_per_jam'],0,',','.'); ?></td>
                            <td><span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <i class="fas fa-calendar-times" style="font-size: 48px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                                Tidak ada jadwal untuk tanggal <?php echo date('d/m/Y', strtotime($tanggal_filter)); ?>
                                <?php if($lapangan_filter > 0): ?>
                                    <br><small>dengan lapangan yang dipilih</small>
                                <?php endif; ?>
                                <br><br>
                                <button class="btn-success" onclick="openModal('generateModal')" style="padding: 8px 20px;">
                                    <i class="fas fa-calendar-plus"></i> Generate Jadwal Sekarang
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 16px; font-size: 12px; color: #64748b;">
            <i class="fas fa-info-circle"></i> 
            <strong>Keterangan Status:</strong>
            <span style="background:#dcfce7; color:#16a34a; padding:2px 8px; border-radius:12px;">Tersedia</span> - Bisa dipesan
            <span style="background:#fef3c7; color:#d97706; padding:2px 8px; border-radius:12px;">Dipesan</span> - Sudah ada yang booking
            <span style="background:#dcfce7; color:#16a34a; padding:2px 8px; border-radius:12px;">Selesai</span> - Waktu main sudah lewat (ada booking)
            <span style="background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:12px; text-decoration:line-through;">Kadaluarsa</span> - Waktu main sudah lewat (tanpa booking)
        </div>
    </div>
    
    <!-- Modal Generate Jadwal -->
    <div id="generateModal" class="modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-plus"></i> Generate Jadwal</h3>
                <span onclick="closeModal('generateModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="generate" value="1">
                    <div class="form-group">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Pilih Lapangan</label>
                        <select name="id_lapangan">
                            <option value="0">-- Semua Lapangan --</option>
                            <?php 
                            $all_lapangan = mysqli_query($conn, "SELECT * FROM lapangan ORDER BY nama_lapangan");
                            while($l = mysqli_fetch_assoc($all_lapangan)): ?>
                            <option value="<?php echo $l['id_lapangan']; ?>"><?php echo htmlspecialchars($l['nama_lapangan']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <small style="color: #64748b;">
                            <i class="fas fa-info-circle"></i> 
                            Jam operasional: 08:00 - 22:00 (per 1 jam)
                        </small>
                    </div>
                    <button type="submit" class="btn-simpan"><i class="fas fa-play"></i> Generate Sekarang</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }
        
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>