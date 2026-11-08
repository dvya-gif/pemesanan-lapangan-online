<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengguna WHERE id_pengguna = $user_id"));

$id_lapangan = isset($_GET['id']) ? $_GET['id'] : 1;
$tanggal_pilih = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

$query_lapangan = "SELECT l.*, jo.nama_olahraga 
    FROM lapangan l 
    LEFT JOIN jenis_olahraga jo ON l.id_olahraga = jo.id_olahraga 
    WHERE l.id_lapangan = $id_lapangan";
$result = mysqli_query($conn, $query_lapangan);
$lapangan = mysqli_fetch_assoc($result);

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_pemesan = $_POST['nama_pemesan'];
    $no_hp = $_POST['no_hp'];
    $tanggal_sewa = $_POST['tanggal'];
    $jam_mulai_sewa = $_POST['jam_mulai'];
    $metode_pembayaran = $_POST['metode'];
    $total_harga = $lapangan['harga_per_jam'];
    
    $query = "INSERT INTO pemesanan (id_pengguna, id_lapangan, tanggal_pesan, jam_mulai, jam_selesai, total_harga, status) 
              VALUES ('$user_id', '$id_lapangan', '$tanggal_sewa', '$jam_mulai_sewa', DATE_ADD('$jam_mulai_sewa', INTERVAL 1 HOUR), '$total_harga', 'pending')";
    
    if (mysqli_query($conn, $query)) {
        $id_pemesanan = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO pembayaran (id_booking, metode_bayar, status_pembayaran) 
                            VALUES ('$id_pemesanan', '$metode_pembayaran', 'pending')");
        header("Location: pemesanan_saya.php?success=1");
        exit();
    } else {
        $error = "Gagal memesan: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Reservasi - SportBooking</title>
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
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .form-container {
            max-width: 550px;
            width: 100%;
            background: white;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .form-header {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            padding: 28px;
            text-align: center;
        }

        .form-header h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .form-header p {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
        }

        .form-body {
            padding: 28px;
        }

        /* Lapangan Info Card */
        .lapangan-card {
            background: linear-gradient(135deg, #f8f9fa, #f0f2f5);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .lapangan-card h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .lapangan-alamat {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }

        /* Section */
        .form-section {
            margin-bottom: 25px;
        }

        .form-section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Form Row */
        .form-row {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .form-label {
            width: 140px;
            font-size: 14px;
            font-weight: 500;
            color: #555;
            padding-top: 10px;
        }

        .form-field {
            flex: 1;
        }

        .form-field input, 
        .form-field select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            background: #fafafa;
            transition: 0.3s;
        }

        .form-field input:focus,
        .form-field select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }

        .form-field .value-text {
            padding: 10px 0;
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        /* Payment Options */
        .payment-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .payment-option {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .payment-option:hover {
            border-color: #667eea;
        }

        .payment-option.selected {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .payment-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .payment-name {
            font-size: 12px;
            font-weight: 600;
        }

        /* Jam Grid */
        .jam-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .jam-item {
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            font-size: 13px;
            background: #fafafa;
            transition: all 0.2s;
        }

        .jam-item:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .jam-item.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        /* Error Message */
        .error-msg {
            background: #fee;
            color: #c62828;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }

        /* Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn-back {
            flex: 1;
            padding: 14px;
            background: #f0f2f5;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            color: #555;
            font-size: 14px;
        }

        .btn-back:hover {
            background: #e5e7eb;
        }

        .btn-submit {
            flex: 1;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: transform 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 550px) {
            .form-row {
                flex-direction: column;
            }
            .form-label {
                width: 100%;
                margin-bottom: 5px;
            }
            .jam-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>📋 Formulir Reservasi</h1>
            <p>Lengkap data untuk sewa lapangan</p>
        </div>

        <div class="form-body">
            <?php if($error): ?>
            <div class="error-msg">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Lapangan Info -->
                <div class="lapangan-card">
                    <h2><?php echo $lapangan['nama_lapangan']; ?></h2>
                    <div class="lapangan-alamat"><?php echo $lapangan['lokasi']; ?></div>
                </div>

                <!-- Info Pemesan -->
                <div class="form-section">
                    <div class="form-section-title">Info Pemesan</div>
                    
                    <div class="form-row">
                        <div class="form-label">Nama Pemesan</div>
                        <div class="form-field">
                            <input type="text" name="nama_pemesan" value="<?php echo htmlspecialchars($user_data['nama_pengguna']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Nomor Handphone</div>
                        <div class="form-field">
                            <input type="tel" name="no_hp" value="<?php echo htmlspecialchars($user_data['no_hp']); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Tanggal & Jam Sewa -->
                <div class="form-section">
                    <div class="form-section-title">Tanggal & Jam Sewa</div>
                    
                    <div class="form-row">
                        <div class="form-label">Pilih Tanggal</div>
                        <div class="form-field">
                            <input type="date" name="tanggal" id="tanggal" value="<?php echo $tanggal_pilih; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Metode Pembayaran</div>
                        <div class="form-field">
                            <div class="payment-options">
                                <div class="payment-option selected" onclick="selectPayment(this, 'gopay')">
                                    <div class="payment-icon">💚</div>
                                    <div class="payment-name">GoPay</div>
                                </div>
                                <div class="payment-option" onclick="selectPayment(this, 'transfer')">
                                    <div class="payment-icon">🏦</div>
                                    <div class="payment-name">Transfer</div>
                                </div>
                                <div class="payment-option" onclick="selectPayment(this, 'qris')">
                                    <div class="payment-icon">📱</div>
                                    <div class="payment-name">QRIS</div>
                                </div>
                            </div>
                            <input type="hidden" name="metode" id="metode" value="gopay">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Pilih Jam Sewa (1 Jam Sesi)</div>
                        <div class="form-field">
                            <select name="jam_mulai" id="jam_mulai" required style="width: 100%;">
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
                    </div>
                </div>

                <!-- Buttons -->
                <div class="action-buttons">
                    <a href="cari_lapangan.php" class="btn-back">← Kembali</a>
                    <button type="submit" class="btn-submit">Konfirmasi Pemesanan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('tanggal').min = today;

        function selectPayment(element, method) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('metode').value = method;
        }
    </script>
</body>
</html>