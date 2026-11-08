<?php
session_start();
include 'koneksi.php';

// CEK ROLE - HARUS ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
// Verifikasi pembayaran
if (isset($_GET['verifikasi']) && isset($_GET['id'])) {
    $id_pembayaran = (int)$_GET['verifikasi'];
    mysqli_query($conn, "UPDATE pembayaran SET status_pembayaran = 'lunas' WHERE id_pembayaran = $id_pembayaran");
    header("Location: admin_pembayaran.php?msg=verified");
    exit();
}

// Tolak pembayaran
if (isset($_GET['tolak']) && isset($_GET['id'])) {
    $id_pembayaran = (int)$_GET['tolak'];
    mysqli_query($conn, "UPDATE pembayaran SET status_pembayaran = 'ditolak' WHERE id_pembayaran = $id_pembayaran");
    header("Location: admin_pembayaran.php?msg=rejected");
    exit();
}

// Ambil data pembayaran yang menunggu verifikasi
$query = "SELECT py.*, p.id_pengguna, pg.nama_pengguna, l.nama_lapangan, j.tanggal, j.jam_mulai
          FROM pembayaran py
          JOIN pemesanan p ON py.id_booking = p.id_booking
          JOIN jadwal j ON p.id_jadwal = j.id_jadwal
          JOIN lapangan l ON j.id_lapangan = l.id_lapangan
          JOIN pengguna pg ON p.id_pengguna = pg.id_pengguna
          WHERE py.status_pembayaran = 'menunggu_verifikasi'
          ORDER BY py.tanggal_upload DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f0f9ff; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #1e40af, #2563eb); color: white; padding: 20px 25px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8fafc; }
        td { padding: 15px; border-bottom: 1px solid #e2e8f0; }
        .btn-verif { background: #22c55e; color: white; padding: 6px 15px; border-radius: 20px; text-decoration: none; font-size: 12px; }
        .btn-tolak { background: #ef4444; color: white; padding: 6px 15px; border-radius: 20px; text-decoration: none; font-size: 12px; }
        .btn-verif:hover, .btn-tolak:hover { opacity: 0.8; }
        .bukti-img { max-width: 80px; border-radius: 8px; cursor: pointer; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal img { max-width: 90%; max-height: 90%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h2><i class="fas fa-credit-card"></i> Verifikasi Pembayaran</h2>
                <p>Daftar pembayaran yang menunggu verifikasi</p>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Pemesan</th><th>Lapangan</th><th>Tanggal/Jam</th><th>Metode</th><th>Bukti</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>#<?php echo $row['id_pembayaran']; ?></td>
                            <td><?php echo $row['nama_pengguna']; ?></td>
                            <td><?php echo $row['nama_lapangan']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?> <?php echo substr($row['jam_mulai'],0,5); ?> WIB</td>
                            <td><?php echo strtoupper($row['metode_bayar']); ?></td>
                            <td>
                                <?php if($row['bukti_pembayaran']): ?>
                                    <img src="uploads/bukti_pembayaran/<?php echo $row['bukti_pembayaran']; ?>" class="bukti-img" onclick="showImage(this.src)">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                             </td>
                            <td>
                                <a href="?verifikasi=<?php echo $row['id_pembayaran']; ?>" class="btn-verif" onclick="return confirm('Verifikasi pembayaran ini?')"><i class="fas fa-check"></i> Verifikasi</a>
                                <a href="?tolak=<?php echo $row['id_pembayaran']; ?>" class="btn-tolak" onclick="return confirm('Tolak pembayaran ini?')"><i class="fas fa-times"></i> Tolak</a>
                             </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if(mysqli_num_rows($result) == 0): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px;">Tidak ada pembayaran yang menunggu verifikasi</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="imageModal" class="modal" onclick="closeModal()">
        <img id="modalImage" src="">
    </div>
    
    <script>
        function showImage(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
    </script>
</body>
</html>