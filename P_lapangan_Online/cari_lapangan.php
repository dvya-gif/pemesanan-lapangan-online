<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] == 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];
$user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengguna WHERE id_pengguna = $user_id"));

// Filter kategori
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Query ambil lapangan
$query = "SELECT l.*, jo.nama_olahraga, jo.id_olahraga 
          FROM lapangan l 
          JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga";

if ($kategori_filter && $kategori_filter != 'Semua') {
    $query .= " WHERE jo.nama_olahraga = '$kategori_filter'";
}

$lapangan = mysqli_query($conn, $query);
$kategori_list = mysqli_query($conn, "SELECT * FROM jenis_olahraga");

// ========== PROSES BOOKING ==========
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $id_lapangan = (int)$_POST['id_lapangan'];
    $tanggal_sewa = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam_mulai_sewa = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
    $metode_pembayaran = mysqli_real_escape_string($conn, $_POST['metode']);
    
    $jam_mulai_formatted = $jam_mulai_sewa . ':00';
    $jam_mulai_int = (int)str_replace(':', '', $jam_mulai_formatted);
    
    $tanggal_sekarang = date('Y-m-d');
    if ($tanggal_sewa < $tanggal_sekarang) {
        echo json_encode(['success' => false, 'message' => 'Tanggal pemesanan tidak boleh kurang dari hari ini!']);
        exit();
    }
    
    if ($tanggal_sewa == $tanggal_sekarang) {
        $jam_sekarang = date('H:i:s');
        $jam_sekarang_int = (int)str_replace(':', '', $jam_sekarang);
        
        if ($jam_mulai_int < $jam_sekarang_int) {
            echo json_encode(['success' => false, 'message' => 'Tidak bisa memesan jam yang sudah lewat!']);
            exit();
        }
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        $query_cek = "SELECT j.id_jadwal, l.harga_per_jam 
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
            throw new Exception("Jadwal tidak tersedia atau sudah dipesan!");
        }
        
        $jadwal_data = mysqli_fetch_assoc($result_cek);
        $id_jadwal = $jadwal_data['id_jadwal'];
        
        if ($metode_pembayaran == 'cash') {
            $status_pemesanan = 'menunggu_verifikasi';
        } else {
            $status_pemesanan = 'pending';
        }
        
        $query_insert = "INSERT INTO pemesanan (id_pengguna, id_jadwal, status, tanggal_pemesanan) 
                        VALUES ('$user_id', '$id_jadwal', '$status_pemesanan', NOW())";
        
        if (!mysqli_query($conn, $query_insert)) {
            throw new Exception("Gagal menyimpan pemesanan: " . mysqli_error($conn));
        }
        
        $id_pemesanan = mysqli_insert_id($conn);
        
        $query_update_jadwal = "UPDATE jadwal 
                               SET status = 'dipesan'
                               WHERE id_jadwal = $id_jadwal";
        
        if (!mysqli_query($conn, $query_update_jadwal)) {
            throw new Exception("Gagal mengupdate status jadwal: " . mysqli_error($conn));
        }
        
        $_SESSION['metode_pembayaran'] = $metode_pembayaran;
        $_SESSION['id_booking_baru'] = $id_pemesanan;
        
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true, 
            'message' => '✅ Pemesanan berhasil! Mengarahkan ke halaman pembayaran...',
            'id_booking' => $id_pemesanan,
            'redirect' => 'bayar.php?id=' . $id_pemesanan
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== AJAX UNTUK MENDAPATKAN JAM TERSEDIA ==========
if (isset($_GET['get_booked_jams']) && isset($_GET['id_lapangan']) && isset($_GET['tanggal'])) {
    $id_lapangan = (int)$_GET['id_lapangan'];
    $tanggal = mysqli_real_escape_string($conn, $_GET['tanggal']);
    
    $query_jam = "SELECT jam_mulai, jam_selesai 
                  FROM jadwal 
                  WHERE id_lapangan = $id_lapangan 
                  AND tanggal = '$tanggal' 
                  AND status = 'tersedia'
                  ORDER BY jam_mulai";
    
    $result_jam = mysqli_query($conn, $query_jam);
    $available = [];
    
    while ($row = mysqli_fetch_assoc($result_jam)) {
        $available[] = [
            'mulai' => substr($row['jam_mulai'], 0, 5),
            'selesai' => substr($row['jam_selesai'], 0, 5)
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($available);
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Lapangan - DBooking Sport</title>
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;
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
        .welcome-banner {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 24px; padding: 30px; margin-bottom: 25px; color: white;
            position: relative; overflow: hidden;
            cursor: pointer;
        }
        .welcome-banner::before {
            content: ''; position: absolute; top: -50%; right: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .welcome-banner h2 { font-size: 24px; font-weight: 800; margin-bottom: 8px; position: relative; z-index: 1; }
        .welcome-banner p { opacity: 0.9; font-size: 14px; position: relative; z-index: 1; }
        .filter-section {
            background: white; border-radius: 24px; padding: 20px 25px;
            margin-bottom: 30px; border: 1px solid #e2e8f0;
        }
        .filter-title { font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .filter-title i { color: #2563eb; }
        .filter-buttons { display: flex; gap: 12px; flex-wrap: wrap; }
        .filter-btn {
            padding: 8px 22px; background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 40px; cursor: pointer; font-size: 13px; font-weight: 500;
            transition: all 0.3s; color: #475569;
        }
        .filter-btn:hover { background: #e0e7ff; border-color: #2563eb; transform: translateY(-2px); }
        .filter-btn.active { background: #2563eb; color: white; border-color: #2563eb; box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
        .lapangan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .lapangan-card {
            background: white; border: 1px solid #e2e8f0; border-radius: 24px;
            overflow: hidden; transition: all 0.3s; cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .lapangan-card:hover { transform: translateY(-8px); box-shadow: 0 20px 35px rgba(0,0,0,0.1); border-color: #2563eb; }
        
        .card-header {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            padding: 20px 20px;
            position: relative;
            min-height: 70px;
        }
        .badge-kategori {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            color: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        /* ID Badge Styles */
        .id-badge {
            position: absolute;
            bottom: 10px;
            left: 15px;
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(4px);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            display: flex;
            gap: 12px;
        }
        .id-badge span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .id-badge i {
            font-size: 10px;
        }
        .card-body { padding: 20px; }
        .lapangan-name { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 8px; }
        .lokasi { color: #64748b; font-size: 13px; margin-bottom: 15px; display: flex; align-items: center; gap: 6px; }
        .info-ids {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            font-size: 11px;
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 12px;
        }
        .info-ids .id-item {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
        .info-ids .id-lapangan {
            color: #2563eb;
        }
        .info-ids .id-olahraga {
            color: #7c3aed;
        }
        .harga { font-size: 24px; font-weight: 800; color: #2563eb; margin: 15px 0; }
        .harga small { font-size: 12px; font-weight: normal; color: #94a3b8; }
        .btn-sewa {
            width: 100%; padding: 12px; background: #2563eb; color: white;
            border: none; border-radius: 14px; cursor: pointer; font-weight: 700;
            font-size: 14px; transition: 0.3s; display: flex; align-items: center;
            justify-content: center; gap: 8px;
        }
        .btn-sewa:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(37,99,235,0.3); }
        .cta-section {
            background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 24px;
            padding: 30px; text-align: center; margin-bottom: 25px; position: relative; overflow: hidden;
        }
        .cta-section::before {
            content: ''; position: absolute; top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(37,99,235,0.2) 0%, transparent 70%);
            animation: rotate 15s linear infinite;
        }
        .cta-section h3 { color: white; font-size: 20px; font-weight: 800; margin-bottom: 10px; position: relative; z-index: 1; }
        .cta-section p { color: rgba(255,255,255,0.7); font-size: 13px; margin-bottom: 20px; position: relative; z-index: 1; }
        .cta-btn {
            background: white; color: #1e293b; padding: 12px 30px;
            border-radius: 40px; text-decoration: none; font-weight: 700;
            font-size: 14px; display: inline-block; transition: 0.3s; position: relative; z-index: 1;
        }
        .cta-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-card { max-width: 550px; width: 90%; background: white; border-radius: 32px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4); animation: modalFadeIn 0.3s ease; overflow: hidden; }
        @keyframes modalFadeIn { from { opacity: 0; transform: scale(0.95) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-header { background: linear-gradient(135deg, #2563eb, #3b82f6); padding: 20px 25px; text-align: center; position: relative; }
        .modal-header h2 { color: white; font-size: 20px; font-weight: 700; }
        .modal-header p { color: rgba(255,255,255,0.7); font-size: 12px; margin-top: 4px; }
        .close-modal { position: absolute; top: 15px; right: 20px; color: white; font-size: 24px; cursor: pointer; opacity: 0.7; }
        .close-modal:hover { opacity: 1; }
        .modal-body { padding: 25px; max-height: 80vh; overflow-y: auto; }
        .lapangan-info { background: #f8fafc; border-radius: 16px; padding: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .lapangan-info .icon { width: 50px; height: 50px; background: linear-gradient(135deg, #2563eb, #3b82f6); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .lapangan-info .text h3 { font-size: 16px; font-weight: 700; color: #1e293b; }
        .lapangan-info .text .id-info { font-size: 11px; color: #64748b; margin-top: 4px; font-family: 'Courier New', monospace; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 6px; }
        .form-group input, .form-group select { width: 100%; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-row { display: flex; gap: 12px; }
        .form-row .form-group { flex: 1; }
        .payment-options { display: flex; gap: 8px; }
        .payment-option { flex: 1; padding: 10px 5px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .payment-option.selected { border-color: #2563eb; background: #e0e7ff; }
        .payment-icon { font-size: 20px; }
        .payment-name { font-size: 10px; font-weight: 500; margin-top: 4px; }
        .action-buttons { display: flex; gap: 12px; margin-top: 20px; }
        .btn-back { flex: 1; padding: 12px; background: #f1f5f9; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; text-align: center; color: #475569; }
        .btn-back:hover { background: #e2e8f0; }
        .btn-confirm { flex: 1; padding: 12px; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
        .btn-confirm:hover { transform: translateY(-2px); }
        .success-notif { background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 12px; margin-bottom: 15px; text-align: center; }
        .error-notif { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 15px; text-align: center; }
        .jam-info { font-size: 11px; margin-top: 6px; display: flex; gap: 8px; flex-wrap: wrap; }
        .jam-tersedia { color: #16a34a; }
        .jam-terbooking { color: #dc2626; }
        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0; margin-top: 20px; }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo p, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
            .form-row { flex-direction: column; }
            .info-ids { flex-direction: column; gap: 5px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><h1>🏸 DBooking</h1><p>Sport</p></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="cari_lapangan.php" class="nav-link active"><i class="fas fa-search"></i> <span>Cari Lapangan</span></a></li>
            <li class="nav-item"><a href="pemesanan_saya.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Pemesanan Saya</span></a></li>
            <li class="nav-item"><a href="riwayat_transaksi.php" class="nav-link"><i class="fas fa-history"></i> <span>Riwayat Transaksi</span></a></li>
            <li class="nav-item"><a href="profil_saya.php" class="nav-link"><i class="fas fa-user"></i> <span>Profil</span></a></li>
            <li class="nav-item"><a href="bantuan.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Bantuan</span></a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Keluar</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Cari Lapangan</h1>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
            </div>
        </div>

        <div class="welcome-banner" onclick="window.location.href='cari_lapangan.php'">
            <h2>Ayo Berolahraga! 🏃‍♂️</h2>
            <p>Cari dan booking lapangan favoritmu sekarang juga.</p>
        </div>

        <div class="filter-section">
            <div class="filter-title"><i class="fas fa-filter"></i> Filter Kategori Cabang Olahraga</div>
            <div class="filter-buttons" id="filterButtons">
                <button class="filter-btn <?php echo !$kategori_filter ? 'active' : ''; ?>" data-kategori="">Semua Cabang</button>
                <?php 
                mysqli_data_seek($kategori_list, 0);
                while($kat = mysqli_fetch_assoc($kategori_list)): ?>
                <button class="filter-btn <?php echo $kategori_filter == $kat['nama_olahraga'] ? 'active' : ''; ?>" data-kategori="<?php echo $kat['nama_olahraga']; ?>">
                    <i class="fas <?php 
                        if($kat['nama_olahraga'] == 'Futsal') echo 'fa-futbol';
                        elseif($kat['nama_olahraga'] == 'Basket') echo 'fa-basketball';
                        elseif($kat['nama_olahraga'] == 'Badminton') echo 'fa-table-tennis';
                        else echo 'fa-tennis-ball';
                    ?>"></i> <?php echo $kat['nama_olahraga']; ?>
                </button>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="lapangan-grid" id="lapanganGrid">
            <?php 
            if(mysqli_num_rows($lapangan) > 0):
                while($row = mysqli_fetch_assoc($lapangan)): 
                    $bg_color = 'linear-gradient(135deg, #2563eb, #3b82f6)';
            ?>
            <div class="lapangan-card" data-kategori="<?php echo $row['nama_olahraga']; ?>">
                <div class="card-header" style="background: <?php echo $bg_color; ?>">
                    <div class="badge-kategori"><?php echo $row['nama_olahraga']; ?></div>
                    <div class="id-badge">
                        <span><i class="fas fa-hashtag"></i> Lapangan: #<?php echo $row['id_lapangan']; ?></span>
                        <span><i class="fas fa-medal"></i> Olahraga: #<?php echo $row['id_olahraga']; ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <h3 class="lapangan-name">🏸 <?php echo htmlspecialchars($row['nama_lapangan']); ?></h3>
                    <div class="info-ids">
                        <span class="id-item id-lapangan"><i class="fas fa-hashtag"></i> ID Lapangan: <?php echo $row['id_lapangan']; ?></span>
                        <span class="id-item id-olahraga"><i class="fas fa-medal"></i> ID Olahraga: <?php echo $row['id_olahraga']; ?></span>
                    </div>
                    <div class="lokasi"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['lokasi']); ?></div>
                    <div class="harga">Rp <?php echo number_format($row['harga_per_jam'], 0, ',', '.'); ?> <small>/ jam</small></div>
                    <button class="btn-sewa" onclick="openModal(<?php echo $row['id_lapangan']; ?>, '<?php echo addslashes($row['nama_lapangan']); ?>', '<?php echo addslashes($row['lokasi']); ?>', <?php echo $row['harga_per_jam']; ?>, <?php echo $row['id_olahraga']; ?>)">
                        <i class="fas fa-calendar-check"></i> Sewa Sekarang
                    </button>
                </div>
            </div>
            <?php 
                endwhile;
            else: ?>
                <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 24px; grid-column: 1/-1;">
                    <i class="fas fa-futbol" style="font-size: 48px; color: #94a3b8; margin-bottom: 16px; display: block;"></i>
                    <h3 style="color: #1e293b;">Belum ada lapangan</h3>
                    <p style="color: #64748b;">Silakan cek kembali nanti.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="cta-section">
            <h3>🏸 Pesan Lapangan Baru</h3>
            <p>Booking lapangan favoritmu sekarang juga dan rasakan pengalaman bermain yang menyenangkan!</p>
            <a href="cari_lapangan.php" class="cta-btn">Cari Lapangan →</a>
        </div>

        <div class="footer">
            <p>&copy; 2026 DBooking Sport - Sewa lapangan olahraga mudah dan cepat</p>
        </div>
    </div>

    <div id="bookingModal" class="modal">
        <div class="modal-card">
            <div class="modal-header">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <h2>📋 Formulir Reservasi</h2>
                <p>Lengkap data untuk sewa lapangan</p>
            </div>
            <div class="modal-body">
                <div id="modalMessage"></div>
                <form id="bookingForm">
                    <input type="hidden" name="id_lapangan" id="id_lapangan">
                    <input type="hidden" name="ajax" value="1">
                    
                    <div class="lapangan-info" id="lapanganInfo">
                        <div class="icon">🏟️</div>
                        <div class="text">
                            <h3 id="lapanganNama">-</h3>
                            <div class="id-info" id="lapanganIdInfo">ID Lapangan: -</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>👤 Nama Pemesan</label>
                        <input type="text" name="nama_pemesan" id="nama_pemesan" value="<?php echo htmlspecialchars($user_data['nama_pengguna']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>📱 Nomor Handphone</label>
                        <input type="tel" name="no_hp" id="no_hp" value="<?php echo htmlspecialchars($user_data['no_hp']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>📅 Pilih Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" required>
                        </div>
                        <div class="form-group">
                            <label>💰 Metode Pembayaran</label>
                            <div class="payment-options">
                                <div class="payment-option" onclick="selectPayment(this, 'cash')"><div class="payment-icon">💵</div><div class="payment-name">Cash</div></div>
                                <div class="payment-option" onclick="selectPayment(this, 'ewallet')"><div class="payment-icon">📱</div><div class="payment-name">E-Wallet</div></div>
                                <div class="payment-option selected" onclick="selectPayment(this, 'transfer')"><div class="payment-icon">🏦</div><div class="payment-name">Transfer</div></div>
                            </div>
                            <input type="hidden" name="metode" id="metode" value="transfer">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>⏰ Pilih Jam Sewa (1 Jam Sesi)</label>
                        <select name="jam_mulai" id="jam_mulai" required>
                            <option value="">-- Pilih tanggal terlebih dahulu --</option>
                        </select>
                        <div class="jam-info" id="jamInfo"></div>
                    </div>

                    <div class="action-buttons">
                        <button type="button" class="btn-back" onclick="closeModal()">← Kembali</button>
                        <button type="submit" class="btn-confirm">Konfirmasi Pemesanan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentLapanganId = null;
        let currentHarga = 0;
        let currentOlahragaId = null;
        
        const filterBtns = document.querySelectorAll('.filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const kategori = this.getAttribute('data-kategori');
                const url = new URL(window.location.href);
                if (kategori) url.searchParams.set('kategori', kategori);
                else url.searchParams.delete('kategori');
                window.location.href = url;
            });
        });

        function openModal(id, nama, lokasi, harga, idOlahraga) {
            currentLapanganId = id;
            currentHarga = harga;
            currentOlahragaId = idOlahraga;
            document.getElementById('id_lapangan').value = id;
            document.getElementById('lapanganNama').innerHTML = `${nama} <span style="font-size:11px; color:#64748b;">(${lokasi})</span>`;
            document.getElementById('lapanganIdInfo').innerHTML = `ID Lapangan: ${id} | ID Olahraga: ${idOlahraga || '-'}`;
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('tanggal').value = today;
            document.getElementById('tanggal').min = today;
            document.getElementById('modalMessage').innerHTML = '';
            document.getElementById('jam_mulai').innerHTML = '<option value="">-- Pilih tanggal terlebih dahulu --</option>';
            document.getElementById('jam_mulai').disabled = true;
            document.getElementById('jamInfo').innerHTML = '';
            document.getElementById('bookingModal').classList.add('show');
            document.body.style.overflow = 'hidden';
            
            loadAvailableJams(today, id);
        }

        function closeModal() {
            document.getElementById('bookingModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            currentLapanganId = null;
        }

        function selectPayment(element, method) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('metode').value = method;
        }

        async function loadAvailableJams(tanggal, idLapangan) {
            const jamSelect = document.getElementById('jam_mulai');
            const jamInfo = document.getElementById('jamInfo');
            
            jamSelect.innerHTML = '<option value="">Memuat jadwal...</option>';
            jamSelect.disabled = true;
            
            try {
                const response = await fetch(`cari_lapangan.php?get_booked_jams=1&id_lapangan=${idLapangan}&tanggal=${tanggal}`);
                const availableJams = await response.json();
                
                const allJams = [
                    '08:00', '09:00', '10:00', '11:00', '12:00',
                    '13:00', '14:00', '15:00', '16:00', '17:00',
                    '18:00', '19:00', '20:00', '21:00', '22:00'
                ];
                
                const availableSet = new Set();
                availableJams.forEach(jam => {
                    availableSet.add(jam.mulai);
                });
                
                const sekarang = new Date();
                const tanggalHariIni = sekarang.toISOString().split('T')[0];
                const jamSekarang = sekarang.getHours();
                const menitSekarang = sekarang.getMinutes();
                
                let options = '<option value="">-- Pilih jam --</option>';
                let tersedia = 0;
                let terbooking = 0;
                let expired = 0;
                
                allJams.forEach(jam => {
                    const jamMulai = parseInt(jam.split(':')[0]);
                    const jamSelesai = formatJamSelesai(jam);
                    
                    let isExpired = false;
                    if (tanggal === tanggalHariIni) {
                        if (jamMulai < jamSekarang) {
                            isExpired = true;
                        } else if (jamMulai === jamSekarang && menitSekarang > 0) {
                            isExpired = true;
                        }
                    }
                    
                    if (availableSet.has(jam) && !isExpired) {
                        options += `<option value="${jam}">✅ ${jam} - ${jamSelesai} (Tersedia)</option>`;
                        tersedia++;
                    } else if (!availableSet.has(jam) && !isExpired) {
                        options += `<option value="${jam}" disabled style="color:#dc2626; background:#fee2e2;">⛔ ${jam} - ${jamSelesai} (Sudah Dibooking)</option>`;
                        terbooking++;
                    } else if (isExpired) {
                        options += `<option value="${jam}" disabled style="color:#94a3b8; background:#f1f5f9;">⏰ ${jam} - ${jamSelesai} (Jam sudah lewat)</option>`;
                        expired++;
                    } else {
                        options += `<option value="${jam}" disabled style="color:#dc2626; background:#fee2e2;">⛔ ${jam} - ${jamSelesai} (Sudah Dibooking)</option>`;
                        terbooking++;
                    }
                });
                
                jamSelect.innerHTML = options;
                jamSelect.disabled = false;
                
                jamInfo.innerHTML = `
                    <span class="jam-tersedia"><i class="fas fa-check-circle"></i> ${tersedia} jam tersedia</span>
                    <span class="jam-terbooking"><i class="fas fa-times-circle"></i> ${terbooking} jam sudah dibooking</span>
                    ${expired > 0 ? `<span style="color:#94a3b8;"><i class="fas fa-clock"></i> ${expired} jam sudah lewat</span>` : ''}
                `;
            } catch (error) {
                console.error('Error loading jams:', error);
                jamSelect.innerHTML = '<option value="">Gagal memuat jadwal</option>';
                jamSelect.disabled = true;
                jamInfo.innerHTML = '<span class="jam-terbooking">Gagal memuat data jadwal. Pastikan jadwal sudah digenerate.</span>';
            }
        }

        function formatJamSelesai(jamMulai) {
            const [jam] = jamMulai.split(':');
            let jamSelesai = parseInt(jam) + 1;
            return `${jamSelesai.toString().padStart(2, '0')}:00`;
        }

        document.getElementById('tanggal').addEventListener('change', function() {
            const tanggal = this.value;
            if (tanggal && currentLapanganId) {
                loadAvailableJams(tanggal, currentLapanganId);
            }
        });

        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const tanggal = document.getElementById('tanggal').value;
            const jamMulai = document.getElementById('jam_mulai').value;
            const metode = document.getElementById('metode').value;
            const idLapangan = document.getElementById('id_lapangan').value;
            const namaPemesan = document.getElementById('nama_pemesan').value;
            const noHp = document.getElementById('no_hp').value;
            
            if (!tanggal) {
                document.getElementById('modalMessage').innerHTML = '<div class="error-notif">⚠️ Silakan pilih tanggal terlebih dahulu!</div>';
                return;
            }
            if (!jamMulai) {
                document.getElementById('modalMessage').innerHTML = '<div class="error-notif">⚠️ Silakan pilih jam sewa!</div>';
                return;
            }
            if (!metode) {
                document.getElementById('modalMessage').innerHTML = '<div class="error-notif">⚠️ Silakan pilih metode pembayaran!</div>';
                return;
            }
            
            const selectedOption = document.querySelector('#jam_mulai option:checked');
            if (selectedOption && selectedOption.disabled) {
                document.getElementById('modalMessage').innerHTML = '<div class="error-notif">⏰ Maaf, jam yang Anda pilih sudah dibooking atau sudah lewat! Silakan pilih jam lain.</div>';
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('id_lapangan', idLapangan);
            formData.append('nama_pemesan', namaPemesan);
            formData.append('no_hp', noHp);
            formData.append('tanggal', tanggal);
            formData.append('jam_mulai', jamMulai);
            formData.append('metode', metode);
            
            document.getElementById('modalMessage').innerHTML = '<div class="success-notif">⏳ Memproses pemesanan...</div>';
            
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('modalMessage').innerHTML = '<div class="success-notif">✅ ' + result.message + '</div>';
                    setTimeout(() => { 
                        window.location.href = result.redirect || 'pemesanan_saya.php';
                    }, 1500);
                } else {
                    document.getElementById('modalMessage').innerHTML = '<div class="error-notif">❌ ' + result.message + '</div>';
                    if (tanggal && currentLapanganId) {
                        loadAvailableJams(tanggal, currentLapanganId);
                    }
                }
            } catch (error) {
                console.error('Fetch error:', error);
                document.getElementById('modalMessage').innerHTML = '<div class="error-notif">❌ Terjadi kesalahan: ' + error.message + '</div>';
            }
        });

        window.onclick = function(event) {
            const modal = document.getElementById('bookingModal');
            if (event.target === modal) closeModal();
        }
    </script>
</body>
</html>