<?php
// File Cari Lapangan & Form Booking: cari.php -> disesuaikan penuh dengan p_lapangan relational schema
require_once __DIR__ . '/header.php';

// Menangkap filter kategori berdasarkan id_olahraga jika ada
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Proses simpan booking baru (INSERT) ke tabel pemesanan & pembayaran
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_pesan'])) {
    $id_jadwal = intval($_POST['id_jadwal']);
    $metode_bayar = mysqli_real_escape_string($conn, $_POST['metode_bayar']);
    
    // Validasi ketersediaan jadwal
    $query_jadwal_check = mysqli_query($conn, "
        SELECT j.*, l.nama_lapangan 
        FROM jadwal j 
        JOIN lapangan l ON j.id_lapangan = l.id_lapangan 
        WHERE j.id_jadwal = $id_jadwal AND j.status = 'Tersedia'
    ");
    $jadwal_info = mysqli_fetch_assoc($query_jadwal_check);
    
    if ($jadwal_info) {
        $nama_lapangan = $jadwal_info['nama_lapangan'];
        
        // 1. Simpan Pemesanan Baru (id_booking auto increment)
        $sql_insert_booking = "INSERT INTO pemesanan (id_pengguna, id_jadwal, tanggal_pemesanan) 
                               VALUES ($user_id, $id_jadwal, CURDATE())";
                               
        if (mysqli_query($conn, $sql_insert_booking)) {
            $inserted_booking_id = mysqli_insert_id($conn);
            
            // 2. Tandai Jadwal tersebut menjadi Terbooking ('Dipesan')
            mysqli_query($conn, "UPDATE jadwal SET status = 'Dipesan' WHERE id_jadwal = $id_jadwal");
            
            // 3. Tambahkan Transaksi di tabel pembayarana (Belum Bayar)
            $bukti_dummy = "bukti_" . $inserted_booking_id . ".jpg";
            $sql_insert_pembayaran = "INSERT INTO pembayaran (id_booking, metode_bayar, bukti_pembayaran, status_pembayaran) 
                                      VALUES ($inserted_booking_id, '$metode_bayar', '$bukti_dummy', 'Belum Bayar')";
            mysqli_query($conn, $sql_insert_pembayaran);
            
            $success_message = "Reservasi lapangan <strong>" . htmlspecialchars($nama_lapangan) . "</strong> sukses dibuat! Selesaikan tagihan pembayaran Anda di menu 'Pemesanan Saya'.";
        } else {
            $error_message = "Sistem gagal memesan sesi: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Maaf, sesi jadwal yang Anda pilih tidak aktif atau telah dipesan oleh pengguna lain.";
    }
}

// Mengambil Kategori Olahraga untuk filter tabs
$query_categories = mysqli_query($conn, "SELECT * FROM jenis_olahraga");

// Query list lapangan berdasarkan filter
if ($category_filter === 'all') {
    $lapangan_query = "
        SELECT l.*, o.nama_olahraga 
        FROM lapangan l 
        LEFT JOIN jenis_olahraga o ON l.id_olahraga = o.id_olahraga
    ";
} else {
    $category_id = intval($category_filter);
    $lapangan_query = "
        SELECT l.*, o.nama_olahraga 
        FROM lapangan l 
        LEFT JOIN jenis_olahraga o ON l.id_olahraga = o.id_olahraga 
        WHERE l.id_olahraga = $category_id
    ";
}
$list_lapangan = mysqli_query($conn, $lapangan_query);
?>

<!-- ALERT JIKA TRANSAKSI SUKSES / GAGAL -->
<?php if (!empty($success_message)): ?>
  <div class="alert alert-success" id="success-toast">
    <div style="display: flex; align-items: center; gap: 0.5rem;">
      <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
      <span><?php echo $success_message; ?></span>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
  <div class="alert alert-success" style="background-color: #fee2e2; color: #b91c1c; border-left-color: #f87171;" id="error-toast">
    <div style="display: flex; align-items: center; gap: 0.5rem;">
      <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
      <span><?php echo $error_message; ?></span>
    </div>
  </div>
<?php endif; ?>

<!-- 1. KATEGORI FILTER TABS -->
<div class="categories-tabs" id="tab-nav">
  <a href="cari.php?category=all" class="tab-pill <?php echo ($category_filter === 'all') ? 'active' : ''; ?>">⚽ Semua</a>
  <?php while($cat = mysqli_fetch_assoc($query_categories)): 
      $icon = "🎯";
      if (stripos($cat['nama_olahraga'], 'futsal') !== false) $icon = "🥅";
      elseif (stripos($cat['nama_olahraga'], 'badminton') !== false) $icon = "🏸";
      elseif (stripos($cat['nama_olahraga'], 'tenis') !== false) $icon = "🎾";
      elseif (stripos($cat['nama_olahraga'], 'basket') !== false) $icon = "🏀";
  ?>
    <a href="cari.php?category=<?php echo $cat['id_olahraga']; ?>" class="tab-pill <?php echo ($category_filter == $cat['id_olahraga']) ? 'active' : ''; ?>">
      <?php echo $icon . ' ' . htmlspecialchars($cat['nama_olahraga']); ?>
    </a>
  <?php endwhile; ?>
</div>

<!-- 2. GRID DAFTAR LAPANGAN -->
<div class="grid grid-cols-3" id="courts-grid">
  <?php
  if (mysqli_num_rows($list_lapangan) == 0) {
      echo '<div class="card" style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem;">
              <i data-lucide="info" style="width: 48px; height: 48px; color: var(--text-lighter); margin:0 auto 1rem;"></i>
              <h3>Belum ada lapangan yang tersedia untuk kategori olahraga pilihan ini.</h3>
            </div>';
  } else {
      while ($court = mysqli_fetch_assoc($list_lapangan)) {
          // Cari list jadwal sewa aktif yang tersedia (status = 'Tersedia')
          $c_id = $court['id_lapangan'];
          $query_schedules = mysqli_query($conn, "SELECT * FROM jadwal WHERE id_lapangan = $c_id AND status = 'Tersedia'");
          
          $schedules_list = [];
          while ($sc = mysqli_fetch_assoc($query_schedules)) {
              $schedules_list[] = $sc;
          }

          // Atur visual cover sesuai kategori olahraga
          $dummy_image = "https://images.unsplash.com/photo-1518605333140-55279f518aa0?auto=format&fit=crop&q=80&w=600";
          if (stripos($court['nama_olahraga'], 'badminton') !== false) {
              $dummy_image = "https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&q=80&w=600";
          } elseif (stripos($court['nama_olahraga'], 'tenis') !== false) {
              $dummy_image = "https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?auto=format&fit=crop&q=80&w=600";
          } elseif (stripos($court['nama_olahraga'], 'basket') !== false) {
              $dummy_image = "https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&q=80&w=600";
          }
          ?>
          <div class="court-card" id="court-<?php echo $court['id_lapangan']; ?>">
            <div class="court-image" style="background-image: url('<?php echo $dummy_image; ?>'); background-size: cover; background-position: center;">
              <span class="court-badge"><?php echo htmlspecialchars($court['nama_olahraga'] ?? 'Umum'); ?></span>
            </div>
            
            <div class="court-body" style="text-align: left;">
              <div class="court-rating">
                <i data-lucide="star" style="width: 14px; height: 14px; fill: var(--accent-amber);"></i>
                <span>4.8</span>
                <span style="color: var(--text-lighter); font-weight: normal;">(92 Ulasan)</span>
              </div>
              
              <h3 class="court-name"><?php echo htmlspecialchars($court['nama_lapangan']); ?></h3>
              
              <p class="court-location">
                <i data-lucide="map-pin" style="width: 14px; height: 14px;"></i>
                <?php echo htmlspecialchars($court['lokasi']); ?>
              </p>
              
              <!-- Fasilitas di-hardcode agar tampilan tetap bento box yang modern dan rapi -->
              <div class="court-facilities">
                <span class="facility-tag">Parkir Luas</span>
                <span class="facility-tag">Sintetis / Vinyl</span>
                <span class="facility-tag">Kantin</span>
                <span class="facility-tag">Lampu LED</span>
              </div>
              
              <div class="court-footer">
                <div class="court-price">
                  <span class="price-num"><?php echo rupiah($court['harga_per_jam']); ?></span>
                  <span class="price-unit">per jam (1 sesi)</span>
                </div>
                
                <?php if (count($schedules_list) > 0): ?>
                  <!-- Pilihan tombol booking memicu modal pembuka menu -->
                  <button class="btn btn-primary" onclick='bukaFormBooking(<?php echo json_encode($court); ?>, <?php echo json_encode($schedules_list); ?>)'>
                    Pesan Sesi (<?php echo count($schedules_list); ?> Slot)
                  </button>
                <?php else: ?>
                  <button class="btn btn-outline" style="cursor: not-allowed; color:#d1d5db;" disabled>
                    Penuh / Closed
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php
      }
  }
  ?>
</div>

<!-- 3. MODAL DETIL FORM BOOKING (MODAL OVERLAY POP-UP) -->
<div id="booking-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 500; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 480px; position: relative;">
    <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-light); padding-bottom: 0.75rem;">
      <h3 style="font-size: 1.1rem; font-weight: 800; text-align: left;" id="modal-court-title">Informasi Booking</h3>
      <button onclick="tutupFormBooking()" style="border: none; background: transparent; cursor: pointer; font-size: 1.25rem;">&times;</button>
    </div>

    <!-- POST form pemesanan -->
    <form action="cari.php" method="POST" style="text-align: left;">
      <input type="hidden" name="proses_pesan" value="1">

      <div class="form-group" style="margin-top: 1rem;">
        <label class="form-label">Pilih Jadwal Sesi yang Siaga</label>
        <select name="id_jadwal" id="form-id-jadwal" required class="form-control" onchange="hitungHargaSesi()">
          <!-- Options will be populated dynamically using Javascript -->
        </select>
        <p style="font-size:0.65rem; color:var(--text-muted); margin-top:0.25rem;">Menampilkan list jadwal status 'Tersedia' yang diambil otomatis dari tabel MySQL di xampp Anda.</p>
      </div>

      <div class="form-group">
        <label class="form-label">Pilihan Metode Pembayaran</label>
        <select name="metode_bayar" required class="form-control">
          <option value="E-Wallet">E-Wallet (GoPay, ShopeePay, Dana, OVO)</option>
          <option value="Transfer Bank">Transfer Virtual Account (Mandiri, BCA, BRI)</option>
          <option value="Cash">Cash (Bayar di Kasir GOR)</option>
        </select>
      </div>

      <div style="background-color: #f9fafb; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
        <span style="font-size: 0.75rem; color: var(--text-muted); display: block; font-weight: 700; text-transform: uppercase;">Apresiasi Pembayaran</span>
        <div style="font-size: 1.1rem; font-weight: 800; color: var(--accent-emerald);" id="modal-court-price">Rp 0</div>
        <p style="font-size: 0.65rem; color: var(--accent-emerald); font-weight: 500; margin-top: 0.15rem;">Bebas Biaya Platform & Pajak Administrator Rp 0 (Diskon SBO)</p>
      </div>

      <div style="display: flex; gap: 0.75rem;">
        <button type="button" class="btn btn-outline" style="flex: 1;" onclick="tutupFormBooking()">Batal</button>
        <button type="submit" class="btn btn-emerald" style="flex: 1;">Buat Booking &rarr;</button>
      </div>
    </form>
  </div>
</div>

<!-- SCRIPT DI ATAS LAYAR UNTUK INTERAKSI FORM POP-UP MODAL -->
<script>
  let currentHargaPerJam = 0;

  function bukaFormBooking(courtObj, schedulesList) {
      document.getElementById('modal-court-title').innerText = "Konfirmasi Booking: " + courtObj.nama_lapangan;
      currentHargaPerJam = parseInt(courtObj.harga_per_jam);
      
      // Populate select dropdown dengan jadwal dari tabel MySQL
      const selectJadwal = document.getElementById('form-id-jadwal');
      selectJadwal.innerHTML = '';
      
      schedulesList.forEach(sc => {
          const opt = document.createElement('option');
          opt.value = sc.id_jadwal;
          // Menyalin properti jam dan durasi
          opt.setAttribute('data-total-jam', sc.total_jam);
          
          // Mengubah format tanggal untuk kenyamanan dibaca
          const dateObj = new Date(sc.tanggal);
          const optText = `${dateObj.toLocaleDateString('id-ID', {day: 'numeric', month: 'short'})} | ${sc.jam_mulai.substring(0,5)} - ${sc.jam_selesai.substring(0,5)} (${sc.total_jam} Jam)`;
          
          opt.innerText = optText;
          selectJadwal.appendChild(opt);
      });
      
      // Hitung harga sesi default
      hitungHargaSesi();
      
      const modal = document.getElementById('booking-modal');
      modal.style.display = 'flex';
  }

  function hitungHargaSesi() {
      const selectJadwal = document.getElementById('form-id-jadwal');
      if (selectJadwal.selectedIndex === -1) return;
      
      const selectedOpt = selectJadwal.options[selectJadwal.selectedIndex];
      const totalJam = parseInt(selectedOpt.getAttribute('data-total-jam') || 1);
      
      const grandTotalPrice = currentHargaPerJam * totalJam;
      
      // Format rupiah
      document.getElementById('modal-court-price').innerText = "Rp " + grandTotalPrice.toLocaleString('id-ID');
  }

  function tutupFormBooking() {
      document.getElementById('booking-modal').style.display = 'none';
  }
</script>

<?php
require_once __DIR__ . '/footer.php';
?>
