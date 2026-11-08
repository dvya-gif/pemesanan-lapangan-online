<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Proses tandai semua sudah dibaca
if (isset($_POST['tandai_semua'])) {
    mysqli_query($conn, "UPDATE notifikasi SET is_dibaca = 1 WHERE id_pengguna = $user_id");
    header("Location: notifikasi.php");
    exit();
}

// Proses tandai dibaca satu per satu
if (isset($_GET['tandai']) && isset($_GET['id'])) {
    $id_notif = (int)$_GET['id'];
    mysqli_query($conn, "UPDATE notifikasi SET is_dibaca = 1 WHERE id_notifikasi = $id_notif AND id_pengguna = $user_id");
    header("Location: notifikasi.php");
    exit();
}

// Proses hapus notifikasi
if (isset($_GET['hapus']) && isset($_GET['id'])) {
    $id_notif = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM notifikasi WHERE id_notifikasi = $id_notif AND id_pengguna = $user_id");
    header("Location: notifikasi.php");
    exit();
}

// Ambil semua notifikasi user
$query = "SELECT * FROM notifikasi WHERE id_pengguna = $user_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
$notifikasi = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifikasi[] = $row;
}

// Hitung statistik
$total_semua = count($notifikasi);
$belum_dibaca = 0;
$sudah_dibaca = 0;

foreach ($notifikasi as $n) {
    if ($n['is_dibaca'] == 0) $belum_dibaca++;
    else $sudah_dibaca++;
}

// Filter berdasarkan status
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$notifikasi_filter = [];
foreach ($notifikasi as $n) {
    if ($filter == 'belum_dibaca' && $n['is_dibaca'] == 1) continue;
    if ($filter == 'sudah_dibaca' && $n['is_dibaca'] == 0) continue;
    $notifikasi_filter[] = $n;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - SportBooking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f0f2f5;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: white;
            border-right: 1px solid #e0e0e0;
            padding: 25px 0;
            z-index: 100;
            overflow-y: auto;
        }

        .logo {
            padding: 0 25px;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .logo p {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 2px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: #555;
            text-decoration: none;
            gap: 12px;
            font-size: 14px;
        }

        .nav-link:hover {
            background: #f5f5f5;
            color: #1a1a2e;
        }

        .nav-link.active {
            background: #f5f5f5;
            color: #1a1a2e;
            border-left: 3px solid #1a1a2e;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px 40px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a2e;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        /* STATS SECTION */
        .stats-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }

        .stats-grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            flex: 1;
            min-width: 100px;
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            display: block;
        }

        .stat-item:hover {
            background: #e8e8e8;
        }

        .stat-item.active {
            background: #1a1a2e;
            color: white;
        }

        .stat-item.active .stat-number,
        .stat-item.active .stat-label {
            color: white;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .stat-label {
            font-size: 13px;
            color: #888;
            margin-top: 5px;
        }

        /* CTA BUTTON */
        .cta-section {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }

        .cta-section h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .cta-section p {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 15px;
        }

        .cta-btn {
            background: white;
            color: #667eea;
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
            transition: 0.3s;
        }

        .cta-btn:hover {
            transform: translateY(-2px);
        }

        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-mark-all {
            background: #1a1a2e;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
        }

        .btn-mark-all:hover {
            background: #2d2d44;
        }

        /* NOTIFICATION LIST */
        .notif-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .notif-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
            transition: 0.3s;
            position: relative;
        }

        .notif-card.unread {
            border-left: 4px solid #667eea;
            background: #fafaff;
        }

        .notif-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .notif-content {
            padding: 18px 20px;
        }

        .notif-title {
            font-size: 15px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .notif-message {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .notif-time {
            font-size: 11px;
            color: #999;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .notif-actions {
            padding: 12px 20px;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 15px;
        }

        .btn-read {
            background: #28a745;
            color: white;
            padding: 6px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-read:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 6px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 20px;
            border: 1px solid #e0e0e0;
        }

        .footer {
            text-align: center;
            padding: 30px;
            margin-top: 40px;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #e0e0e0;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar .logo p,
            .sidebar .nav-link span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
                padding: 20px;
            }
            .stats-grid {
                flex-direction: column;
            }
            .notif-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h1>🏸 SportBooking</h1>
            <p>Pemesanan Lapangan Online</p>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="nav-link">📊 Dashboard</a></li>
            <li class="nav-item"><a href="cari_lapangan.php" class="nav-link">🔍 Cari Lapangan</a></li>
            <li class="nav-item"><a href="pemesanan_saya.php" class="nav-link">📅 Pemesanan Saya</a></li>
            <li class="nav-item"><a href="riwayat_pemesanan.php" class="nav-link">📜 Riwayat</a></li>
            <li class="nav-item"><a href="pembayaran.php" class="nav-link">💳 Pembayaran</a></li>
            <li class="nav-item"><a href="notifikasi.php" class="nav-link active">🔔 Notifikasi</a></li>
            <li class="nav-item"><a href="profil_saya.php" class="nav-link">👤 Profil</a></li>
            <li class="nav-item"><a href="bantuan.php" class="nav-link">❓ Bantuan</a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link">🚪 Keluar</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>🔔 Notifikasi</h1>
            <div class="user-info">
                <span>Halo, <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="logout-btn">Keluar</a>
            </div>
        </div>

        <!-- CTA SECTION -->
        <div class="cta-section">
            <h3>Ayo Berolahraga!</h3>
            <p>Booking lapangan favoritmu sekarang juga.</p>
            <a href="cari_lapangan.php" class="cta-btn">Cari Lapangan →</a>
        </div>

        <!-- STATS FILTER -->
        <div class="stats-section">
            <div class="stats-grid">
                <a href="?filter=semua" class="stat-item <?php echo $filter == 'semua' ? 'active' : ''; ?>">
                    <div class="stat-number"><?php echo $total_semua; ?></div>
                    <div class="stat-label">Semua (<?php echo $total_semua; ?>)</div>
                </a>
                <a href="?filter=belum_dibaca" class="stat-item <?php echo $filter == 'belum_dibaca' ? 'active' : ''; ?>">
                    <div class="stat-number"><?php echo $belum_dibaca; ?></div>
                    <div class="stat-label">Belum Dibaca (<?php echo $belum_dibaca; ?>)</div>
                </a>
                <a href="?filter=sudah_dibaca" class="stat-item <?php echo $filter == 'sudah_dibaca' ? 'active' : ''; ?>">
                    <div class="stat-number"><?php echo $sudah_dibaca; ?></div>
                    <div class="stat-label">Sudah Dibaca (<?php echo $sudah_dibaca; ?>)</div>
                </a>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="action-buttons">
            <form method="POST" style="margin: 0;">
                <button type="submit" name="tandai_semua" class="btn-mark-all">📌 Tandai Semua Sudah Dibaca</button>
            </form>
            <span class="notif-time">Baru saja</span>
        </div>

        <!-- NOTIFICATION LIST -->
        <?php if(count($notifikasi_filter) > 0): ?>
        <div class="notif-list">
            <?php foreach($notifikasi_filter as $notif): 
                $is_unread = ($notif['is_dibaca'] == 0);
                $time = strtotime($notif['created_at']);
                $now = time();
                $diff = $now - $time;
                
                if($diff < 60) {
                    $time_text = "Baru saja";
                } elseif($diff < 3600) {
                    $time_text = floor($diff/60) . " menit yang lalu";
                } elseif($diff < 86400) {
                    $time_text = floor($diff/3600) . " jam yang lalu";
                } else {
                    $time_text = floor($diff/86400) . " hari yang lalu";
                }
            ?>
            <div class="notif-card <?php echo $is_unread ? 'unread' : ''; ?>">
                <div class="notif-content">
                    <div class="notif-title">
                        <?php echo htmlspecialchars($notif['judul']); ?>
                        <?php if($is_unread): ?>
                        <span style="background: #667eea; color: white; font-size: 10px; padding: 2px 8px; border-radius: 10px; margin-left: 8px;">Baru</span>
                        <?php endif; ?>
                    </div>
                    <div class="notif-message">
                        <?php echo htmlspecialchars($notif['pesan']); ?>
                    </div>
                    <div class="notif-time">
                        🕐 <?php echo $time_text; ?>
                    </div>
                </div>
                <div class="notif-actions">
                    <?php if($is_unread): ?>
                    <a href="?tandai=1&id=<?php echo $notif['id_notifikasi']; ?>&filter=<?php echo $filter; ?>" class="btn-read">✓ Tandai Dibaca</a>
                    <?php endif; ?>
                    <a href="?hapus=1&id=<?php echo $notif['id_notifikasi']; ?>&filter=<?php echo $filter; ?>" class="btn-delete" onclick="return confirm('Hapus notifikasi ini?')">🗑️ Hapus</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <h3>📭 Tidak ada notifikasi</h3>
            <p>Belum ada notifikasi untuk ditampilkan.</p>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>&copy; 2024 SportBooking - Pemesanan Lapangan Online</p>
        </div>
    </div>
</body>
</html>