<?php
// File Header Bersama: header.php
require_once __DIR__ . '/config.php';

// Mendapatkan nama file saat ini untuk deteksi menu aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Hitung jumlah tagihan belum dibayar secara live dari tabel pembayaran
$query_unpaid = mysqli_query($conn, "SELECT COUNT(*) as unpaid FROM pembayaran WHERE status_pembayaran = 'Belum Bayar'");
$unpaid_count = mysqli_fetch_assoc($query_unpaid)['unpaid'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SportBooking - Sistem Penyewaan Lapangan Olahraga</title>
  <link rel="stylesheet" href="style.css">
  <!-- Kita gunakan Lucide Icons dari CDN untuk menu navigasi yang cantik -->
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body id="sportbooking-php-body">

  <!-- 1. SIDEBAR NAVIGATION -->
  <aside class="sidebar" id="app-sidebar">
    <a href="index.php" class="sidebar-brand">
      <div class="brand-icon">S</div>
      <span class="brand-name">SportBooking</span>
    </a>

    <ul class="sidebar-menu">
      <li class="sidebar-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
        <a href="index.php">
          <i data-lucide="layout-dashboard"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li class="sidebar-item <?php echo ($current_page == 'cari.php') ? 'active' : ''; ?>">
        <a href="cari.php">
          <i data-lucide="search"></i>
          <span>Cari Lapangan</span>
        </a>
      </li>
      <li class="sidebar-item <?php echo ($current_page == 'pemesanan.php') ? 'active' : ''; ?>">
        <a href="pemesanan.php">
          <i data-lucide="calendar"></i>
          <span>Pemesanan Saya</span>
          <!-- Tampilkan badge billing jika ada tagihan Belum Bayar -->
          <?php if ($unpaid_count > 0): ?>
              <span class="notif-badge" style="background-color: var(--accent-amber);"><?php echo $unpaid_count; ?></span>
          <?php endif; ?>
        </a>
      </li>
    </ul>

    <!-- PROFIL PENGGUNA DI SUDUT BAWAH SIDEBAR -->
    <div class="sidebar-profile">
      <!-- Inisial avatar anggun -->
      <div style="width: 42px; height: 42px; border-radius: 50%; background-color: var(--accent-blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem;">
        <?php echo strtoupper(substr($user_profile['nama_pengguna'] ?? 'A', 0, 1)); ?>
      </div>
      <div class="profile-info">
        <span class="profile-name"><?php echo htmlspecialchars($user_profile['nama_pengguna'] ?? 'Andi Pratama'); ?></span>
        <span class="profile-role">Pengguna (Active)</span>
      </div>
    </div>
  </aside>

  <!-- 2. AREA UTAMA (VIEWPORT) -->
  <div class="viewport">
    
    <!-- TOP NAVBAR -->
    <header class="navbar">
      <div class="navbar-title">
        <?php
        switch ($current_page) {
            case 'index.php': echo 'Dashboard Utama'; break;
            case 'cari.php': echo 'Cari & Sewa Lapangan'; break;
            case 'pemesanan.php': echo 'Daftar Reservasi Anda'; break;
            default: echo 'SportBooking';
        }
        ?>
      </div>

      <div class="navbar-actions">
        <!-- Tagihan Alert info -->
        <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem;">
          <i data-lucide="wallet" style="width: 18px; height: 18px; color: var(--accent-blue);"></i>
          <span>Tagihan Tertunda</span>
          <?php if ($unpaid_count > 0): ?>
            <span class="notif-badge" style="background-color: var(--accent-amber);"><?php echo $unpaid_count; ?></span>
          <?php else: ?>
            <span class="notif-badge" style="background-color: var(--accent-emerald);">0</span>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <!-- CONTENT PORT -->
    <main class="main-container">
