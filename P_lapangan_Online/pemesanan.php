<?php
// File Kelola Pemesanan: pemesanan.php -> disesuaikan penuh dengan p_lapangan relational schema
require_once __DIR__ . '/header.php';

$action_msg = "";

// 1. PROSES BAYAR SEKARANG (DARI STATUS 'Belum Bayar' MENJADI 'Sudah Dibayar')
if (isset($_GET['bayar_id'])) {
    $bayar_id = intval($_GET['bayar_id']);
    
    // Perbarui status_pembayaran di table pembayaran
    $sql_update = "UPDATE pembayaran SET status_pembayaran = 'Sudah Dibayar' WHERE id_booking = $bayar_id";
    if (mysqli_query($conn, $sql_update)) {
        // Ambil info nama lapangan untuk alert konfirmasi sukses
        $query_info = mysqli_query($conn, "
            SELECT l.nama_lapangan, l.harga_per_jam, j.total_jam 
            FROM pemesanan b 
            JOIN jadwal j ON b.id_jadwal = j.id_jadwal 
            JOIN lapangan l ON j.id_lapangan = l.id_lapangan 
            WHERE b.id_booking = $bayar_id
        ");
        $info = mysqli_fetch_assoc($query_info);
        $nama_lapangan = $info['nama_lapangan'] ?? 'Lapangan';
        
        $action_msg = "<div class='alert alert-success' id='pay-alert-success'>Pembayaran lunas terkonfirmasi otomatis untuk <strong>" . htmlspecialchars($nama_lapangan) . "</strong>! Selamat bermain olahraga favorit Anda!</div>";
    }
}

// 2. PROSES PEMBATALAN PEMESANAN (CANCELLATION)
if (isset($_GET['batal_id'])) {
    $batal_id = intval($_GET['batal_id']);
    
    // Cari id_jadwal terlebih dahulu untuk diset kembali menjadi 'Tersedia'
    $query_batal_info = mysqli_query($conn, "
        SELECT b.id_jadwal, l.nama_lapangan 
        FROM pemesanan b
        JOIN jadwal j ON b.id_jadwal = j.id_jadwal
        JOIN lapangan l ON j.id_lapangan = l.id_lapangan
        WHERE b.id_booking = $batal_id
    ");
    $batal_info = mysqli_fetch_assoc($query_batal_info);
    
    if ($batal_info) {
        $id_jadwal = $batal_info['id_jadwal'];
        $nama_lapangan = $batal_info['nama_lapangan'];
        
        // Kembalikan status jadwal ke 'Tersedia'
        mysqli_query($conn, "UPDATE jadwal SET status = 'Tersedia' WHERE id_jadwal = $id_jadwal");
        
        // Hapus pemesanan (pembayaran otomatis terhapus berkat ON DELETE CASCADE)
        $sql_delete = "DELETE FROM pemesanan WHERE id_booking = $batal_id";
        if (mysqli_query($conn, $sql_delete)) {
            $action_msg = "<div class='alert alert-success' id='cancel-alert-success' style='background-color:#fee2e2; color:#b91c1c; border-left-color:#f87171;'>Reservasi sewa untuk <strong>" . htmlspecialchars($nama_lapangan) . "</strong> dibatalkan. Sesi jam & tanggal sewa dibebaskan kembali!</div>";
        }
    }
}

// Ambil list pemesanan aktif milik pengguna berdasarkan relasi tabel p_lapangan
$query_bookings = mysqli_query($conn, "
    SELECT 
        b.id_booking,
        l.nama_lapangan,
        l.lokasi,
        j.tanggal,
        j.jam_mulai,
        j.jam_selesai,
        j.total_jam,
        l.harga_per_jam,
        p.metode_bayar,
        p.status_pembayaran
    FROM pemesanan b
    JOIN jadwal j ON b.id_jadwal = j.id_jadwal
    JOIN lapangan l ON j.id_lapangan = l.id_lapangan
    LEFT JOIN pembayaran p ON b.id_booking = p.id_booking
    WHERE b.id_pengguna = $user_id
    ORDER BY j.tanggal ASC, j.jam_mulai ASC
");
?>

<!-- ALERT NOTIFIKASI -->
<?php if (!empty($action_msg)) echo $action_msg; ?>

<div style="text-align: left; margin-bottom: 2rem;">
  <p style="font-size: 0.85rem; color: var(--text-muted);">
    Di bawah ini adalah daftar jadwal sewa lapangan olahraga Anda yang terintegrasi langsung dengan database MySQL di XAMPP/phpMyAdmin lokal Anda.
  </p>
</div>

<!-- CONTAINER LAYOUT PEMESANAN DAN PANDUAN -->
<div class="grid grid-cols-3" style="align-items: flex-start; gap: 2rem;">
  
  <!-- Left Column: Card list pemesanan -->
  <div style="grid-column: span 2; display: flex; flex-direction: column; gap:1.25rem;">
    
    <h3 style="font-size: 1rem; font-weight:800; text-align: left; color:#374151; border-bottom: 2px solid var(--border-light); padding-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
      <i data-lucide="layers" style="width: 18px; height: 18px;"></i>
      Jadwal Reservasi Anda
    </h3>

    <?php if (mysqli_num_rows($query_bookings) == 0): ?>
      <div class="card" style="text-align: center; padding: 3.5rem;">
        <i data-lucide="frown" style="width: 48px; height: 48px; color: var(--text-lighter); margin-bottom: 0.75rem;"></i>
        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted);">Anda belum memiliki reservasi aktif.</h4>
        <p style="font-size: 0.75rem; color: var(--text-lighter); margin-top: 0.25rem;">Temukan slot waktu lapang di menu Cari Lapangan.</p>
        <div style="margin-top: 1.25rem;">
          <a href="cari.php" class="btn btn-primary btn-sm">Mulai Cari Lapangan</a>
        </div>
      </div>
    <?php else: ?>
      
      <?php while ($book = mysqli_fetch_assoc($query_bookings)): 
          $is_unpaid = ($book['status_pembayaran'] == 'Belum Bayar');
          $status_class = $is_unpaid ? 'status-pending' : 'status-active';
          $status_text = $is_unpaid ? 'Menunggu Pembayaran' : 'Disetujui / Lunas';
          
          // Mengalkulasi total_harga sewa
          $total_harga_sewa = $book['harga_per_jam'] * $book['total_jam'];
          
          // Visual Cover sesuai nama
          $dummy_image = "https://images.unsplash.com/photo-1518605333140-55279f518aa0?auto=format&fit=crop&q=80&w=200";
          if (stripos($book['nama_lapangan'], 'bulu tangkis') !== false || stripos($book['nama_lapangan'], 'badminton') !== false) {
              $dummy_image = "https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&q=80&w=200";
          } elseif (stripos($book['nama_lapangan'], 'tenis') !== false) {
              $dummy_image = "https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?auto=format&fit=crop&q=80&w=200";
          } elseif (stripos($book['nama_lapangan'], 'basket') !== false) {
              $dummy_image = "https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&q=80&w=200";
          }
      ?>
        <div class="card" id="booking-item-<?php echo $book['id_booking']; ?>" style="padding: 1.25rem; display: flex; justify-content: space-between; align-items: center; position: relative;">
          
          <div style="display: flex; gap: 1.25rem; align-items: center;">
            <img src="<?php echo $dummy_image; ?>" alt="Pic" style="width: 80px; height: 80px; border-radius: 12px; object-fit: cover;">
            <div style="text-align: left;">
              <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
              <h4 style="font-size: 0.95rem; font-weight: 800; margin-top: 0.35rem;"><?php echo htmlspecialchars($book['nama_lapangan']); ?></h4>
              
              <div style="display: flex; flex-direction: column; gap: 0.15rem; margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted);">
                <span style="display: flex; align-items: center; gap: 0.25rem;">
                  <i data-lucide="calendar-days" style="width: 12px; height: 12px;"></i>
                  <?php echo date('d M Y', strtotime($book['tanggal'])); ?> 
                </span>
                <span style="display: flex; align-items: center; gap: 0.25rem;">
                  <i data-lucide="clock-4" style="width: 12px; height: 12px;"></i>
                  Sesi Jam: <?php echo substr($book['jam_mulai'], 0, 5) . " - " . substr($book['jam_selesai'], 0, 5); ?> (<?php echo $book['total_jam']; ?> Jam)
                </span>
              </div>
            </div>
          </div>

          <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem;">
            <div>
              <span style="font-size: 0.65rem; color: var(--text-lighter); display: block;">Nilai Transaksi</span>
              <span style="font-size: 1.05rem; font-weight: 800; color: var(--accent-blue);"><?php echo rupiah($total_harga_sewa); ?></span>
            </div>

            <div style="display: flex; gap: 0.5rem;">
              <?php if ($is_unpaid): ?>
                <!-- Tombol Bayar Sekarang -->
                <a href="pemesanan.php?bayar_id=<?php echo $book['id_booking']; ?>" class="btn btn-emerald" style="padding: 0.5rem 0.85rem; font-size: 0.7rem; border-radius: 8px;">
                  <i data-lucide="credit-card" style="width: 14px; height: 14px;"></i>
                  Bayar Sekarang
                </a>
              <?php else: ?>
                <!-- Tombol Tiket Digital -->
                <button class="btn btn-outline" style="padding: 0.5rem 0.85rem; font-size: 0.7rem; border-radius: 8px;" onclick="bukaE_Tiket('<?php echo $book['id_booking']; ?>', '<?php echo htmlspecialchars($book['nama_lapangan']); ?>', '<?php echo date('d M Y', strtotime($book['tanggal'])); ?>', '<?php echo substr($book['jam_mulai'],0,5) . ' - ' . substr($book['jam_selesai'],0,5); ?>')">
                  <i data-lucide="ticket" style="width: 14px; height: 14px; color: var(--accent-blue);"></i>
                  Lihat E-Tiket
                </button>
              <?php endif; ?>

              <!-- Tombol Batalkan / Hapus Booking -->
              <a href="pemesanan.php?batal_id=<?php echo $book['id_booking']; ?>" onclick="return confirm('Apakah Anda yakin ingin mematalkan reservasi slot lapangan ini?')" class="btn btn-red" style="padding: 0.5rem; border-radius: 8px;" title="Batalkan Pemesanan">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
              </a>
            </div>
          </div>

        </div>
      <?php endwhile; ?>

    <?php endif; ?>

  </div>

  <!-- Right Column: Panduan & Info Pembayaran Instan Barcode -->
  <div style="display: flex; flex-direction: column; gap:1.25rem;">
    
    <div class="card" style="text-align: left;">
      <h3 style="font-size: 0.9rem; font-weight: 800; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; color: var(--accent-amber);">
        <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
        Kebijakan GOR
      </h3>
      <ul style="font-size: 0.75rem; color: var(--text-muted); padding-left: 1.2rem; display: flex; flex-direction: column; gap: 0.5rem;">
        <li>Slot pesanan terkoneksi langsung dengan database Anda.</li>
        <li>Bila memesan, jadwal lapangan akan dikunci ('Dipesan') agar tidak diduplikasi penyewa lain.</li>
        <li>Bila Anda memilih 'Batalkan', data pemesanan & pembayaran terhapus otomatis di sistem, serta mengembalikan status jadwal menjadi 'Tersedia'.</li>
      </ul>
    </div>

    <div class="card" style="text-align: center; border: 2px dashed #e2e8f0; background: #fafbfc;">
      <i data-lucide="qr-code" style="width: 36px; height: 36px; color: var(--text-muted); margin: 0 auto 0.5rem;"></i>
      <h4 style="font-size: 0.8rem; font-weight: 700; color: var(--text-main);">Metode Pembayaran QRIS</h4>
      <p style="font-size: 0.65rem; color: var(--text-lighter); margin-top: 0.25rem; line-height: 1.4;">
        Semua transaksi terintegrasi instan demi kepuasan bermain Anda di lapangan.
      </p>
    </div>

  </div>

</div>

<!-- MODAL TIKET DIGITAL (POP-UP) -->
<div id="tiket-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 500; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 380px; text-align: center; border-radius: 20px; border: 3px solid var(--accent-blue); padding: 2rem;">
    <h3 style="font-size: 1.2rem; color: var(--accent-blue); font-weight:800; margin-bottom: 1rem; border-bottom: 2px dashed #e5e7eb; padding-bottom: 0.75rem;">E-TIKET MASUK</h3>
    
    <div style="margin-bottom: 1rem; text-align: left; font-size: 0.8rem;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
        <span style="color:#9ca3af;">Kode Booking:</span>
        <strong style="color:var(--status-red-text);" id="tiket-id">#BOOKING-ID</strong>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
        <span style="color:#9ca3af;">Nama Pengguna:</span>
        <strong id="tiket-user"><?php echo htmlspecialchars($user_profile['nama_pengguna'] ?? 'Andi Pratama'); ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
        <span style="color:#9ca3af;">Tipe Lapangan:</span>
        <strong id="tiket-court">Lapangan Olahraga</strong>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
        <span style="color:#9ca3af;">Tanggal Main:</span>
        <strong id="tiket-date">Minggu, 24 Mei</strong>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
        <span style="color:#9ca3af;">Sesi Waktu:</span>
        <strong id="tiket-slot">18:00 - 19:30</strong>
      </div>
    </div>

    <!-- Simulasi barcode / qr -->
    <div style="background-color: #f3f4f6; padding: 0.85rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.25rem; align-items:center;">
       <i data-lucide="scan-barcode" style="width: 64px; height: 60px; color: #1e293b;"></i>
       <span style="font-size: 0.65rem; color: #6b7280; font-family: 'JetBrains Mono'; font-weight:700;">VALID_PASS_SBO_2026</span>
    </div>

    <button class="btn btn-outline" style="width:100%; font-size: 0.75rem;" onclick="tutupE_Tiket()">
      Tutup Tiket
    </button>
  </div>
</div>

<script>
  function bukaE_Tiket(id, court_name, date, slot) {
      document.getElementById('tiket-id').innerText = '#' + id;
      document.getElementById('tiket-court').innerText = court_name;
      document.getElementById('tiket-date').innerText = date;
      document.getElementById('tiket-slot').innerText = slot;
      
      document.getElementById('tiket-modal').style.display = 'flex';
  }

  function tutupE_Tiket() {
      document.getElementById('tiket-modal').style.display = 'none';
  }
</script>

<?php
require_once __DIR__ . '/footer.php';
?>
