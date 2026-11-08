<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id_pemesanan = isset($_GET['id']) ? $_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Ambil data pemesanan dengan JOIN ke lapangan
$query = "SELECT p.*, l.nama_lapangan, l.lokasi 
          FROM pemesanan p 
          JOIN lapangan l ON p.id_lapangan = l.id_lapangan 
          WHERE p.id_pemesanan = $id_pemesanan AND p.id_pengguna = $user_id";
$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    header("Location: pemesanan_saya.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pemesanan - SportBooking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .header {
            background: linear-gradient(135deg, #28a745, #20c997);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .header .icon {
            font-size: 64px;
            margin-bottom: 10px;
        }
        .header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .body {
            padding: 30px;
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 13px;
        }
        .info-value {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 14px;
        }
        .total {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            margin: 20px 0;
        }
        .total .label {
            font-size: 13px;
            opacity: 0.9;
        }
        .total .amount {
            font-size: 28px;
            font-weight: 800;
        }
        .payment-info {
            background: #e3f2fd;
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
        }
        .payment-info h4 {
            margin-bottom: 10px;
            color: #1565c0;
        }
        .payment-info p {
            font-size: 13px;
            color: #555;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .btn-primary {
            flex: 1;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 14px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            flex: 1;
            padding: 14px;
            background: #f0f2f5;
            color: #555;
            text-align: center;
            text-decoration: none;
            border-radius: 14px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            background: #fff3e0;
            color: #e65100;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="icon">✅</div>
            <h2>Pemesanan Berhasil!</h2>
            <p>Booking lapangan Anda telah kami terima</p>
        </div>
        
        <div class="body">
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Kode Booking</span>
                    <span class="info-value">#<?php echo str_pad($booking['id_pemesanan'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lapangan</span>
                    <span class="info-value"><?php echo $booking['nama_lapangan']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lokasi</span>
                    <span class="info-value"><?php echo $booking['lokasi']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal Main</span>
                    <span class="info-value"><?php echo date('d F Y', strtotime($booking['tanggal_pesan'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jam Main</span>
                    <span class="info-value"><?php echo substr($booking['jam_mulai'], 0, 5); ?> - <?php echo substr($booking['jam_selesai'], 0, 5); ?> WIB</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value"><span class="status-badge">Menunggu Pembayaran</span></span>
                </div>
            </div>
            
            <div class="total">
                <div class="label">Total Pembayaran</div>
                <div class="amount">Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></div>
            </div>
            
            <div class="payment-info">
                <h4>💳 Petunjuk Pembayaran</h4>
                <p>1. Transfer ke rekening berikut:</p>
                <p><strong>Bank BCA: 1234567890</strong><br>a.n SportBooking</p>
                <p><strong>Bank Mandiri: 9876543210</strong><br>a.n SportBooking</p>
                <p>2. Upload bukti transfer ke menu <strong>Pembayaran</strong></p>
                <p>3. Booking akan otomatis dikonfirmasi admin</p>
            </div>
            
            <div class="btn-group">
                <a href="pembayaran.php" class="btn-primary">💰 Bayar Sekarang</a>
                <a href="pemesanan_saya.php" class="btn-secondary">📋 Lihat Pesanan</a>
            </div>
        </div>
    </div>
</body>
</html>