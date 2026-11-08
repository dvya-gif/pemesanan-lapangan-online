<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];
$user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengguna WHERE id_pengguna = $user_id"));

// Filter kategori
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Query ambil lapangan
$query = "SELECT l.*, jo.nama_olahraga 
          FROM lapangan l 
          JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga";

if ($kategori_filter && $kategori_filter != 'Semua') {
    $query .= " WHERE jo.nama_olahraga = '$kategori_filter'";
}

$lapangan = mysqli_query($conn, $query);
$kategori_list = mysqli_query($conn, "SELECT * FROM jenis_olahraga");

// Proses form submission via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    $id_lapangan = (int)$_POST['id_lapangan'];
    $nama_pemesan = mysqli_real_escape_string($conn, $_POST['nama_pemesan']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $tanggal_sewa = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam_mulai_sewa = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
    $metode_pembayaran = mysqli_real_escape_string($conn, $_POST['metode']);
    
    $harga_query = mysqli_query($conn, "SELECT harga_per_jam FROM lapangan WHERE id_lapangan = $id_lapangan");
    $harga_data = mysqli_fetch_assoc($harga_query);
    $total_harga = $harga_data['harga_per_jam'];
    $jam_selesai = date('H:i:s', strtotime($jam_mulai_sewa . ' +1 hours'));
    
    $query = "INSERT INTO pemesanan (id_pengguna, id_lapangan, tanggal_pesan, jam_mulai, jam_selesai, total_harga, status, created_at) 
              VALUES ('$user_id', '$id_lapangan', '$tanggal_sewa', '$jam_mulai_sewa', '$jam_selesai', '$total_harga', 'pending', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $id_pemesanan = mysqli_insert_id($conn);
        $query_bayar = "INSERT INTO pembayaran (id_booking, metode_bayar, status_pembayaran) 
                        VALUES ('$id_pemesanan', '$metode_pembayaran', 'pending')";
        
        if (mysqli_query($conn, $query_bayar)) {
            echo json_encode(['success' => true, 'message' => 'Pemesanan berhasil!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error pembayaran: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f9ff;
        }

        /* SIDEBAR - SAMA PERSIS DENGAN DASHBOARD */
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

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
        }

        .logo {
            padding: 0 25px;
            margin-bottom: 35px;
            text-align: center;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 800;
            color: white;
        }

        .logo p {
            font-size: 11px;
            color: rgba(255,255,255,0.7);
            margin-top: 4px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

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

        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .nav-link i {
            width: 22px;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 260px;
            padding: 25px 35px;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 15px 25px;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info span {
            color: #475569;
            font-weight: 500;
        }

        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 8px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* WELCOME SECTION */
        .welcome-section {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 24px;
            padding: 35px 40px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .welcome-section h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .welcome-section p {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.5;
            position: relative;
            z-index: 1;
        }

        /* FILTER SECTION */
        .filter-section {
            background: white;
            border-radius: 24px;
            padding: 20px 25px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .filter-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-title i {
            color: #2563eb;
        }

        .filter-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 22px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            color: #475569;
        }

        .filter-btn:hover {
            background: #e0e7ff;
            border-color: #2563eb;
            transform: translateY(-2px);
        }

        .filter-btn.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }

        /* CTA BANNER */
        .cta-banner {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .cta-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(37,99,235,0.2) 0%, transparent 70%);
            animation: rotate 15s linear infinite;
        }

        .cta-banner h3 {
            color: white;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .cta-banner p {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .cta-btn {
            background: white;
            color: #1e293b;
            padding: 10px 32px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            display: inline-block;
            transition: 0.3s;
            position: relative;
            z-index: 1;
        }

        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        /* LAPANGAN GRID */
        .lapangan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .lapangan-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
        }

        .lapangan-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 35px rgba(0,0,0,0.1);
            border-color: #2563eb;
        }

        .card-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .badge-kategori {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            color: white;
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            z-index: 2;
        }

        .card-body {
            padding: 20px;
        }

        .lapangan-name {
            font-size: 18px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .lokasi {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .fasilitas {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .fasilitas span {
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            color: #475569;
        }

        .harga {
            font-size: 24px;
            font-weight: 800;
            color: #2563eb;
            margin: 15px 0;
        }

        .harga small {
            font-size: 12px;
            font-weight: normal;
            color: #94a3b8;
        }

        .btn-sewa {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-sewa:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37,99,235,0.3);
        }

        .footer {
            text-align: center;
            padding: 30px;
            margin-top: 40px;
            color: #94a3b8;
            font-size: 12px;
            border-top: 1px solid #e2e8f0;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-card {
            max-width: 500px;
            width: 90%;
            background: white;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            animation: modalFadeIn 0.3s ease;
            overflow: hidden;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-header {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            padding: 20px 25px;
            text-align: center;
            position: relative;
        }
        .modal-header h2 { color: white; font-size: 20px; font-weight: 700; }
        .modal-header p { color: rgba(255,255,255,0.7); font-size: 12px; margin-top: 4px; }
        .close-modal {
            position: absolute; top: 15px; right: 20px; color: white;
            font-size: 24px; cursor: pointer; opacity: 0.7;
        }
        .close-modal:hover { opacity: 1; }
        .modal-body { padding: 25px; }
        .lapangan-info {
            background: #f8fafc; border-radius: 16px; padding: 15px;
            margin-bottom: 20px; display: flex; align-items: center; gap: 12px;
        }
        .lapangan-info .icon {
            width: 50px; height: 50px; background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 14px; display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: white;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 6px; }
        .form-group input, .form-group select {
            width: 100%; padding: 12px 14px; border: 1px solid #e2e8f0;
            border-radius: 12px; font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .form-row { display: flex; gap: 12px; }
        .payment-options { display: flex; gap: 8px; }
        .payment-option {
            flex: 1; padding: 10px 5px; background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 12px; text-align: center; cursor: pointer;
        }
        .payment-option.selected { border-color: #2563eb; background: #e0e7ff; }
        .payment-icon { font-size: 20px; }
        .payment-name { font-size: 10px; font-weight: 500; margin-top: 4px; }
        .action-buttons { display: flex; gap: 12px; margin-top: 20px; }
        .btn-back {
            flex: 1; padding: 12px; background: #f1f5f9; border: none; border-radius: 12px;
            font-weight: 600; cursor: pointer; text-align: center; color: #475569;
        }
        .btn-back:hover { background: #e2e8f0; }
        .btn-confirm {
            flex: 1; padding: 12px; background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer;
        }
        .btn-confirm:hover { transform: translateY(-2px); }
        .success-notif { background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 12px; margin-bottom: 15px; text-align: center; }
        .error-msg { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 15px; text-align: center; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo p, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
            .welcome-section h1 { font-size: 24px; }
            .filter-buttons { gap: 8px; }
            .filter-btn { padding: 6px 16px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR - SAMA PERSIS DENGAN DASHBOARD -->
    <div class="sidebar">
        <div class="logo">
            <h1>🏸 DBooking</h1>
            <p>Sport</p>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="cari_lapangan.php" class="nav-link active"><i class="fas fa-search"></i> <span>Cari Lapangan</span></a></li>
            <li class="nav-item"><a href="pemesanan_saya.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Pemesanan Saya</span></a></li>
            <li class="nav-item"><a href="riwayat_pemesanan.php" class="nav-link"><i class="fas fa-history"></i> <span>Riwayat Pemesanan</span></a></li>
            <li class="nav-item"><a href="pembayaran.php" class="nav-link"><i class="fas fa-credit-card"></i> <span>Pembayaran</span></a></li>
            <li class="nav-item"><a href="profil_saya.php" class="nav-link"><i class="fas fa-user"></i> <span>Profil</span></a></li>
            <li class="nav-item"><a href="bantuan.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Bantuan</span></a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Keluar</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <!-- HEADER -->
        <div class="header">
            <h1>Cari Lapangan</h1>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
            </div>
        </div>

        <!-- WELCOME SECTION -->
        <div class="welcome-section">
            <h1>🏀 Cari & Booking Lapangan Olahraga</h1>
            <p>Gunakan filter kategori cabang olahraga di bawah ini untuk melihat lapangan yang tersedia.</p>
        </div>

        <!-- FILTER SECTION -->
        <div class="filter-section">
            <div class="filter-title">
                <i class="fas fa-filter"></i> Filter Kategori Cabang Olahraga
            </div>
            <div class="filter-buttons" id="filterButtons">
                <button class="filter-btn <?php echo !$kategori_filter ? 'active' : ''; ?>" data-kategori="">Semua Cabang</button>
                <?php while($kat = mysqli_fetch_assoc($kategori_list)): ?>
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

        <!-- CTA BANNER -->
        <div class="cta-banner">
            <h3>Ayo Berolahraga! 🏃‍♂️</h3>
            <p>Booking lapangan favoritmu sekarang juga.</p>
            <a href="#" class="cta-btn" onclick="scrollToTop()">Cari Lapangan →</a>
        </div>

        <!-- LAPANGAN GRID -->
        <div class="lapangan-grid" id="lapanganGrid">
            <?php while($row = mysqli_fetch_assoc($lapangan)): 
                $gambar_url = '';
                if(!empty($row['gambar'])) {
                    $gambar_url = 'uploads/' . $row['gambar'];
                } else {
                    $olahraga = strtolower($row['nama_olahraga']);
                    if($olahraga == 'futsal') $gambar_url = 'uploads/futsal.jpg';
                    elseif($olahraga == 'basket') $gambar_url = 'uploads/basket.jpg';
                    elseif($olahraga == 'badminton') $gambar_url = 'uploads/badminton.jpg';
                    elseif($olahraga == 'tennis') $gambar_url = 'uploads/tennis.jpg';
                    else $gambar_url = '';
                }
                $file_exists = !empty($gambar_url) && file_exists($gambar_url);
            ?>
            <div class="lapangan-card" data-kategori="<?php echo $row['nama_olahraga']; ?>">
                <div class="card-image" style="<?php echo $file_exists ? "background-image: url('$gambar_url');" : "background: linear-gradient(135deg, #2563eb, #3b82f6);"; ?>">
                    <?php if(!$file_exists): ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                        <span style="font-size: 64px; color: white; opacity: 0.8;">
                            <?php 
                                if($row['nama_olahraga'] == 'Futsal') echo '⚽';
                                elseif($row['nama_olahraga'] == 'Basket') echo '🏀';
                                elseif($row['nama_olahraga'] == 'Badminton') echo '🏸';
                                elseif($row['nama_olahraga'] == 'Tennis') echo '🎾';
                                else echo '🏟️';
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="badge-kategori"><?php echo $row['nama_olahraga']; ?></div>
                </div>
                <div class="card-body">
                    <h3 class="lapangan-name"><?php echo $row['nama_lapangan']; ?></h3>
                    <div class="lokasi"><i class="fas fa-map-marker-alt"></i> <?php echo $row['lokasi']; ?></div>
                    <div class="fasilitas">
                        <span><i class="fas fa-car"></i> Parkir</span>
                        <span><i class="fas fa-couch"></i> Sintetis</span>
                        <span><i class="fas fa-utensils"></i> Kantin</span>
                        <span><i class="fas fa-lightbulb"></i> Lampu LED</span>
                    </div>
                    <div class="harga">
                        Rp <?php echo number_format($row['harga_per_jam'], 0, ',', '.'); ?> <small>/ jam</small>
                    </div>
                    <button class="btn-sewa" onclick="openModal(<?php echo $row['id_lapangan']; ?>, '<?php echo addslashes($row['nama_lapangan']); ?>', '<?php echo addslashes($row['lokasi']); ?>', <?php echo $row['harga_per_jam']; ?>)">
                        <i class="fas fa-calendar-check"></i> Sewa Sekarang
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="footer">
            <p>&copy; 2024 DBooking Sport - Sewa lapangan olahraga mudah dan cepat</p>
        </div>
    </div>

    <!-- MODAL POPUP -->
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
                        <div class="text"><h3 id="lapanganNama">-</h3><p id="lapanganLokasi">-</p></div>
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
                            <label>💳 Metode Pembayaran</label>
                            <div class="payment-options">
                                <div class="payment-option selected" onclick="selectPayment(this, 'cash')"><div class="payment-icon">💵</div><div class="payment-name">Cash</div></div>
                                <div class="payment-option" onclick="selectPayment(this, 'ewallet')"><div class="payment-icon">📱</div><div class="payment-name">E-Wallet</div></div>
                                <div class="payment-option" onclick="selectPayment(this, 'transfer')"><div class="payment-icon">🏦</div><div class="payment-name">Transfer</div></div>
                            </div>
                            <input type="hidden" name="metode" id="metode" value="cash">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>⏰ Pilih Jam Sewa (1 Jam Sesi)</label>
                        <select name="jam_mulai" id="jam_mulai" required>
                            <option value="">Pilih jam main</option>
                            <option value="08:00">08:00 - 09:00</option>
                            <option value="09:00">09:00 - 10:00</option>
                            <option value="10:00">10:00 - 11:00</option>
                            <option value="11:00">11:00 - 12:00</option>
                            <option value="12:00">12:00 - 13:00</option>
                            <option value="13:00">13:00 - 14:00</option>
                            <option value="14:00">14:00 - 15:00</option>
                            <option value="15:00">15:00 - 16:00</option>
                            <option value="16:00">16:00 - 17:00</option>
                            <option value="17:00">17:00 - 18:00</option>
                            <option value="18:00">18:00 - 19:00</option>
                            <option value="19:00">19:00 - 20:00</option>
                            <option value="20:00">20:00 - 21:00</option>
                            <option value="21:00">21:00 - 22:00</option>
                        </select>
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
        function scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); }

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

        function openModal(id, nama, lokasi, harga) {
            document.getElementById('id_lapangan').value = id;
            document.getElementById('lapanganNama').innerText = nama;
            document.getElementById('lapanganLokasi').innerText = lokasi;
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('tanggal').value = today;
            document.getElementById('tanggal').min = today;
            document.getElementById('modalMessage').innerHTML = '';
            document.getElementById('bookingModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('bookingModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function selectPayment(element, method) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('metode').value = method;
        }

        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            document.getElementById('modalMessage').innerHTML = '<div class="success-notif">⏳ Memproses...</div>';
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    document.getElementById('modalMessage').innerHTML = '<div class="success-notif">✅ ' + result.message + ' Mengalihkan...</div>';
                    setTimeout(() => { window.location.href = 'pemesanan_saya.php?success=1'; }, 1500);
                } else {
                    document.getElementById('modalMessage').innerHTML = '<div class="error-msg">❌ ' + result.message + '</div>';
                }
            } catch (error) {
                document.getElementById('modalMessage').innerHTML = '<div class="error-msg">❌ Terjadi kesalahan. Silakan coba lagi.</div>';
            }
        });

        window.onclick = function(event) {
            const modal = document.getElementById('bookingModal');
            if (event.target === modal) closeModal();
        }
    </script>
</body>
</html>