<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Ambil pemesanan terbaru user
$query = "SELECT p.*, l.nama_lapangan, l.lokasi 
          FROM pemesanan p 
          LEFT JOIN lapangan l ON p.id_lapangan = l.id_lapangan
          WHERE p.id_pengguna = $user_id 
          ORDER BY p.created_at DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    echo "<h2>Belum ada pemesanan</h2>";
    echo "<a href='cari_lapangan.php'>Booking lapangan sekarang</a>";
    exit();
}

// Proses konfirmasi
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_pemesanan = $booking['id_booking'];
    $metode = $_POST['metode'];
    
    $update = mysqli_query($conn, "UPDATE pemesanan SET status = 'dikonfirmasi' WHERE id_booking = $id_pemesanan");
    
    if ($update) {
        // Simpan ke pembayaran
        mysqli_query($conn, "INSERT INTO pembayaran (id_pemesanan, id_pengguna, jumlah, metode, status) 
                            VALUES ('$id_pemesanan', '$user_id', '{$booking['total_harga']}', '$metode', 'lunas')");
        
        $message = '<div style="background:#e8f5e9; color:#2e7d32; padding:12px; border-radius:10px; margin-bottom:20px;">✅ Pembayaran berhasil! Booking Anda telah dikonfirmasi.</div>';
    } else {
        $message = '<div style="background:#fee; color:#c62828; padding:12px; border-radius:10px; margin-bottom:20px;">❌ Gagal, silakan coba lagi.</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Konfirmasi Pemesanan - SportBooking</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .card {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            padding: 25px;
            text-align: center;
            color: white;
        }
        .header h1 { font-size: 22px; margin-bottom: 5px; }
        .header p { font-size: 13px; opacity: 0.8; }
        .body { padding: 25px; }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label { color: #888; font-size: 13px; }
        .info-value { font-weight: 600; color: #333; }
        .total { font-size: 22px; color: #28a745; font-weight: bold; }
        .payment-options {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .payment-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            background: white;
        }
        .payment-btn.selected {
            border-color: #28a745;
            background: #e8f5e9;
        }
        .instructions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
            font-size: 13px;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .btn-back {
            flex: 1;
            padding: 12px;
            background: #f0f2f5;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            color: #555;
            font-weight: 600;
        }
        .btn-confirm {
            flex: 1;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .status-pending {
            background: #fff3e0;
            color: #e65100;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>💳 Konfirmasi Pembayaran</h1>
            <p>Silakan selesaikan pembayaran Anda</p>
        </div>
        <div class="body">
            <?php echo $message; ?>
            
            <div class="info-row">
                <span class="info-label">ID Booking</span>
                <span class="info-value">#<?php echo $booking['id_booking']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Lapangan</span>
                <span class="info-value"><?php echo $booking['nama_lapangan'] ?? 'Lapangan'; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal Main</span>
                <span class="info-value"><?php echo date('d/m/Y', strtotime($booking['tanggal_pesan'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Jam Main</span>
                <span class="info-value"><?php echo substr($booking['jam_mulai'], 0, 5); ?> WIB</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="info-value"><span class="status-pending">Menunggu Pembayaran</span></span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Bayar</span>
                <span class="info-value total">Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></span>
            </div>

            <?php if($booking['status'] == 'pending'): ?>
            <form method="POST">
                <div style="font-weight:600; margin:15px 0 10px;">Pilih Metode:</div>
                <div class="payment-options">
                    <div class="payment-btn selected" onclick="selectPayment(this, 'gopay')">💚 GoPay</div>
                    <div class="payment-btn" onclick="selectPayment(this, 'transfer')">🏦 Transfer</div>
                    <div class="payment-btn" onclick="selectPayment(this, 'qris')">📱 QRIS</div>
                </div>
                <input type="hidden" name="metode" id="metode" value="gopay">

                <div class="instructions" id="instructions">
                    <strong>📋 Petunjuk:</strong><br>
                    1. Buka GoPay Anda<br>
                    2. Scan QRIS atau transfer ke:<br>
                    <strong>BCA: 1234567890 a.n SportBooking</strong><br>
                    3. Masukkan nominal Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?><br>
                    4. Klik tombol konfirmasi di bawah
                </div>

                <div class="btn-group">
                    <a href="cari_lapangan.php" class="btn-back">← Kembali</a>
                    <button type="submit" class="btn-confirm">✅ Saya Sudah Bayar</button>
                </div>
            </form>
            <?php else: ?>
            <div class="instructions" style="background:#e8f5e9;">
                ✅ Pemesanan sudah dikonfirmasi! Terima kasih.
            </div>
            <div class="btn-group">
                <a href="pemesanan_saya.php" class="btn-back">Lihat Pemesanan</a>
                <a href="cari_lapangan.php" class="btn-confirm" style="text-decoration:none; text-align:center;">Booking Lagi</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function selectPayment(el, method) {
            document.querySelectorAll('.payment-btn').forEach(btn => btn.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('metode').value = method;
            
            let instruksi = '';
            if(method === 'gopay') {
                instruksi = '<strong>📋 Petunjuk:</strong><br>1. Buka GoPay Anda<br>2. Scan QRIS atau transfer ke:<br><strong>BCA: 1234567890 a.n SportBooking</strong><br>3. Masukkan nominal Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?><br>4. Klik tombol konfirmasi di bawah';
            } else if(method === 'transfer') {
                instruksi = '<strong>📋 Petunjuk Transfer Bank:</strong><br>1. Buka mobile banking Anda<br>2. Transfer ke rekening:<br><strong>BCA: 1234567890 a.n SportBooking</strong><br>3. Masukkan nominal Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?><br>4. Klik tombol konfirmasi setelah transfer';
            } else {
                instruksi = '<strong>📋 Petunjuk QRIS:</strong><br>1. Buka aplikasi pembayaran QRIS Anda<br>2. Scan QRIS yang tersedia di lokasi<br>3. Konfirmasi pembayaran<br>4. Klik tombol konfirmasi di bawah';
            }
            document.getElementById('instructions').innerHTML = instruksi;
        }
    </script>
</body>
</html>