<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id_booking = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Ambil metode dari session (dikirim dari halaman reservasi)
$metode_terpilih = isset($_SESSION['metode_pembayaran']) ? $_SESSION['metode_pembayaran'] : (isset($_GET['metode']) ? $_GET['metode'] : 'transfer');

if ($id_booking == 0) {
    header("Location: pemesanan_saya.php");
    exit();
}

// Ambil data pemesanan
$query = "SELECT p.id_booking, p.status, l.nama_lapangan, l.harga_per_jam, j.tanggal, j.jam_mulai, j.jam_selesai
          FROM pemesanan p
          JOIN jadwal j ON p.id_jadwal = j.id_jadwal
          JOIN lapangan l ON j.id_lapangan = l.id_lapangan
          WHERE p.id_booking = $id_booking AND p.id_pengguna = $user_id";

$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    header("Location: pemesanan_saya.php");
    exit();
}

// Cek pembayaran yang sudah ada
$check_payment = "SELECT id_pembayaran, status_pembayaran, bukti_pembayaran FROM pembayaran WHERE id_booking = $id_booking";
$payment_result = mysqli_query($conn, $check_payment);
$existing_payment = mysqli_fetch_assoc($payment_result);

if ($existing_payment && ($existing_payment['status_pembayaran'] == 'lunas' || $existing_payment['status_pembayaran'] == 'menunggu_verifikasi')) {
    header("Location: pemesanan_saya.php?msg=already_paid");
    exit();
}

// Hapus data pembayaran dengan status 'pending' atau 'ditolak'
if ($existing_payment && ($existing_payment['status_pembayaran'] == 'pending' || $existing_payment['status_pembayaran'] == 'ditolak')) {
    mysqli_query($conn, "DELETE FROM pembayaran WHERE id_booking = $id_booking");
    $existing_payment = null;
}

// Proses pembayaran - UNTUK TRANSFER & E-WALLET SAJA
$error = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && $metode_terpilih != 'cash') {
    $metode_bayar = $metode_terpilih;
    $bukti_file = '';
    
    // Untuk transfer dan ewallet: wajib upload bukti
    if ($metode_bayar == 'transfer' || $metode_bayar == 'ewallet') {
        if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $filename = $_FILES['bukti_bayar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'bukti_' . $id_booking . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/bukti_pembayaran/' . $new_filename;
                
                if (!is_dir('uploads/bukti_pembayaran')) {
                    mkdir('uploads/bukti_pembayaran', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['bukti_bayar']['tmp_name'], $upload_path)) {
                    $bukti_file = $new_filename;
                } else {
                    $error = "Gagal mengupload file bukti pembayaran.";
                }
            } else {
                $error = "Format file tidak didukung. Gunakan JPG, PNG, atau PDF.";
            }
        } else {
            $error = "Silakan upload bukti pembayaran.";
        }
    }
    
    if (empty($error)) {
        mysqli_begin_transaction($conn);
        
        try {
            // Update status pemesanan menjadi menunggu_verifikasi
            mysqli_query($conn, "UPDATE pemesanan SET status = 'menunggu_verifikasi' WHERE id_booking = $id_booking");
            
            if ($existing_payment) {
                mysqli_query($conn, "DELETE FROM pembayaran WHERE id_booking = $id_booking");
            }
            
            // Insert pembayaran (TANPA tanggal_upload)
            $query_payment = "INSERT INTO pembayaran (id_booking, metode_bayar, bukti_pembayaran, status_pembayaran) 
                              VALUES ('$id_booking', '$metode_bayar', '$bukti_file', 'menunggu_verifikasi')";
            mysqli_query($conn, $query_payment);
            
            unset($_SESSION['metode_pembayaran']);
            
            mysqli_commit($conn);
            
            $success_message = "Bukti pembayaran berhasil diupload. Admin akan memverifikasi dalam waktu dekat.";
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

$metode_display = [
    'cash' => 'Cash (Bayar di Tempat)',
    'transfer' => 'Transfer Bank',
    'ewallet' => 'E-Wallet (DANA/OVO/GoPay)'
];
$metode_icon = [
    'cash' => 'fa-money-bill-wave',
    'transfer' => 'fa-university',
    'ewallet' => 'fa-mobile-alt'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, height=device-height, user-scalable=no">
    <title>Konfirmasi Pembayaran - DBooking Sport</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            height: 100%;
            overflow: hidden;
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%); 
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { 
            max-width: 550px; 
            width: 100%;
            margin: 0 auto; 
        }
        
        .card { 
            background: white; 
            border-radius: 28px; 
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-height: 95vh;
        }
        
        .card::-webkit-scrollbar { width: 4px; }
        .card::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 10px; }
        .card::-webkit-scrollbar-thumb { background: #2563eb; border-radius: 10px; }
        
        .header { 
            background: linear-gradient(135deg, #1e40af, #2563eb); 
            color: white; 
            padding: 20px 25px; 
            text-align: center; 
        }
        .header h1 { font-size: 22px; font-weight: 800; }
        .header p { font-size: 13px; opacity: 0.9; margin-top: 4px; }
        
        .body { padding: 20px 25px; }
        
        .info { 
            background: #f8fafc; 
            border-radius: 16px; 
            padding: 15px; 
            margin-bottom: 20px; 
        }
        .info-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 8px 0; 
            border-bottom: 1px solid #e2e8f0; 
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-size: 12px; }
        .info-value { font-weight: 700; color: #1e293b; font-size: 13px; }
        
        .price { 
            font-size: 28px; 
            font-weight: 800; 
            color: #2563eb; 
            text-align: center; 
            margin: 15px 0; 
            padding: 12px; 
            background: #eff6ff; 
            border-radius: 16px; 
        }
        
        .selected-method { 
            background: #f1f5f9; 
            border-radius: 14px; 
            padding: 12px 16px; 
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid #2563eb;
        }
        .selected-method i { font-size: 24px; color: #2563eb; }
        .selected-method .method-info { flex: 1; }
        .selected-method .method-label { font-size: 11px; color: #64748b; }
        .selected-method .method-name { font-size: 14px; font-weight: 700; color: #1e293b; }
        
        .upload-area { 
            border: 2px dashed #e2e8f0; 
            border-radius: 14px; 
            padding: 20px; 
            text-align: center; 
            margin-bottom: 20px;
            transition: 0.3s;
            cursor: pointer;
        }
        .upload-area:hover { border-color: #2563eb; background: #eff6ff; }
        .upload-area i { font-size: 40px; color: #94a3b8; margin-bottom: 8px; display: block; }
        .upload-area p { font-size: 13px; color: #64748b; }
        .upload-area small { font-size: 10px; color: #94a3b8; }
        
        .preview-area {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 14px;
            text-align: center;
        }
        .preview-area label {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            display: block;
            margin-bottom: 10px;
        }
        .preview-image {
            max-width: 100%;
            max-height: 250px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 5px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .preview-pdf {
            width: 100%;
            height: 250px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: white;
        }
        
        .file-info { 
            background: #dcfce7; 
            border-radius: 12px; 
            padding: 10px 14px; 
            margin-bottom: 15px; 
            font-size: 12px;
            display: none;
            align-items: center;
            justify-content: space-between;
        }
        .file-info span { color: #16a34a; }
        .remove-file { color: #dc2626; cursor: pointer; margin-left: 10px; font-size: 11px; }
        
        .btn-bayar { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #22c55e, #16a34a); 
            color: white; 
            border: none; 
            border-radius: 40px; 
            font-size: 15px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: 0.3s; 
        }
        .btn-bayar:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(34,197,94,0.3); }
        
        .btn-back-cash {
            display: block;
            text-align: center;
            padding: 12px;
            background: #2563eb;
            color: white;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
            margin-top: 10px;
        }
        .btn-back-cash:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }
        
        .btn-back { 
            display: block; 
            text-align: center; 
            margin-top: 12px; 
            color: #64748b; 
            text-decoration: none; 
            font-size: 13px; 
        }
        .btn-back:hover { color: #2563eb; }
        
        .error-msg { background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 12px; margin-bottom: 15px; font-size: 12px; text-align: center; }
        .success-msg { background: #dcfce7; color: #16a34a; padding: 10px; border-radius: 12px; margin-bottom: 15px; font-size: 12px; text-align: center; }
        .info-note { background: #dbeafe; color: #1e40af; padding: 10px; border-radius: 12px; margin-top: 15px; font-size: 11px; text-align: center; }
        
        .payment-instruction { 
            background: #f8fafc; 
            border-radius: 14px; 
            padding: 14px; 
            margin-bottom: 20px;
            font-size: 12px;
        }
        .payment-instruction h4 { font-size: 13px; margin-bottom: 8px; color: #1e293b; }
        .payment-instruction p { color: #64748b; line-height: 1.4; }
        .payment-instruction .bank-detail { 
            background: white; 
            padding: 8px; 
            border-radius: 10px; 
            margin-top: 8px;
            font-family: monospace;
            font-size: 12px;
        }
        
        .cash-info-box {
            background: linear-gradient(135deg, #1e40af, #2563eb);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 20px;
            color: white;
            text-align: center;
        }
        .cash-info-box i {
            font-size: 28px;
            margin-bottom: 8px;
            display: block;
        }
        .cash-info-box h4 {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .cash-info-box p {
            font-size: 12px;
            opacity: 0.9;
            line-height: 1.4;
        }
        .cash-info-box .highlight {
            background: rgba(255,255,255,0.15);
            padding: 6px 10px;
            border-radius: 10px;
            margin-top: 10px;
            font-size: 11px;
        }
        
        @media (max-width: 480px) {
            .body { padding: 16px 20px; }
            .price { font-size: 24px; margin: 10px 0; padding: 10px; }
            .header { padding: 16px 20px; }
            .header h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h1><i class="fas fa-credit-card"></i> Konfirmasi Pembayaran</h1>
            <p>Selesaikan pembayaran sesuai metode yang dipilih</p>
        </div>
        <div class="body">
            <?php if(!empty($error)): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success_message)): ?>
            <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php if($metode_terpilih != 'cash'): ?>
            <div class="success-msg" style="background: #dbeafe; color: #1e40af; margin-top: 10px;">
                <i class="fas fa-clock"></i> Admin akan memverifikasi pembayaran Anda. Status akan berubah setelah diverifikasi.
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="info">
                <div class="info-row"><span class="info-label">ID Booking</span><span class="info-value">#<?php echo $booking['id_booking']; ?></span></div>
                <div class="info-row"><span class="info-label">Lapangan</span><span class="info-value"><?php echo htmlspecialchars($booking['nama_lapangan']); ?></span></div>
                <div class="info-row"><span class="info-label">Tanggal & Jam</span><span class="info-value"><?php echo date('d/m/Y', strtotime($booking['tanggal'])); ?> <?php echo substr($booking['jam_mulai'],0,5); ?> - <?php echo substr($booking['jam_selesai'],0,5); ?> WIB</span></div>
            </div>
            
            <div class="price">Total: Rp <?php echo number_format($booking['harga_per_jam'], 0, ',', '.'); ?></div>
            
            <div class="selected-method">
                <i class="fas <?php echo $metode_icon[$metode_terpilih]; ?>"></i>
                <div class="method-info">
                    <div class="method-label">Metode Pembayaran yang Dipilih</div>
                    <div class="method-name"><?php echo $metode_display[$metode_terpilih]; ?></div>
                </div>
            </div>
            
            <div class="payment-instruction">
                <?php if($metode_terpilih == 'transfer'): ?>
                    <h4><i class="fas fa-university"></i> Petunjuk Transfer Bank</h4>
                    <p>Silakan transfer ke rekening berikut:</p>
                    <div class="bank-detail">
                        <strong>Bank BCA</strong><br>
                        No. Rekening: 0140015062008<br>
                        Atas Nama: DBooking Sport<br>
                        <small>Nominal: Rp <?php echo number_format($booking['harga_per_jam'], 0, ',', '.'); ?></small>
                    </div>
                <?php elseif($metode_terpilih == 'ewallet'): ?>
                    <h4><i class="fas fa-mobile-alt"></i> Petunjuk E-Wallet</h4>
                    <p>Silakan transfer ke:</p>
                    <div class="bank-detail">
                        <strong>DANA / OVO / GoPay</strong><br>
                        No. Virtual Account: 082287617482<br>
                        <small>Nominal: Rp <?php echo number_format($booking['harga_per_jam'], 0, ',', '.'); ?></small>
                    </div>
                <?php else: ?>
                    <h4><i class="fas fa-money-bill"></i> Petunjuk Cash</h4>
                    <p>Pembayaran dilakukan secara tunai di lokasi sebelum jam main dimulai.</p>
                    <p><strong>Alamat:</strong> Jl. Arena Olahraga No. 12, Pariaman</p>
                <?php endif; ?>
            </div>
            
            <?php if($metode_terpilih == 'cash'): ?>
                <!-- TAMPILAN CASH - TANPA TOMBOL KONFIRMASI -->
                <div class="cash-info-box">
                    <i class="fas fa-info-circle"></i>
                    <h4>Informasi Pembayaran Cash</h4>
                    <p>Anda telah memilih pembayaran tunai. Silakan datang ke lokasi dan bayar langsung ke admin.</p>
                    <div class="highlight">
                        <i class="fas fa-clock"></i> Pemesanan akan dikonfirmasi admin setelah pembayaran diterima di lokasi
                    </div>
                </div>
                
                <a href="pemesanan_saya.php" class="btn-back-cash">
                    <i class="fas fa-arrow-left"></i> Kembali ke Pemesanan Saya
                </a>
                
            <?php else: ?>
                <!-- TAMPILAN TRANSFER & E-WALLET - DENGAN UPLOAD BUKTI -->
                <form method="POST" enctype="multipart/form-data" id="paymentForm">
                    <!-- PRATINJAU BUKTI YANG SUDAH DIUPLOAD SEBELUMNYA -->
                    <?php if(isset($existing_payment['bukti_pembayaran']) && !empty($existing_payment['bukti_pembayaran'])): ?>
                    <div class="preview-area">
                        <label><i class="fas fa-image"></i> Bukti Pembayaran yang Sudah Diupload:</label>
                        <?php 
                        $file_ext = strtolower(pathinfo($existing_payment['bukti_pembayaran'], PATHINFO_EXTENSION));
                        $file_path = 'uploads/bukti_pembayaran/' . $existing_payment['bukti_pembayaran'];
                        if(file_exists($file_path)):
                            if(in_array($file_ext, ['jpg', 'jpeg', 'png'])): ?>
                                <img src="<?php echo $file_path; ?>" class="preview-image" alt="Bukti Pembayaran">
                            <?php elseif($file_ext == 'pdf'): ?>
                                <iframe src="<?php echo $file_path; ?>" class="preview-pdf"></iframe>
                            <?php else: ?>
                                <div style="padding: 20px; background: #f1f5f9; border-radius: 12px;">
                                    <i class="fas fa-file"></i> <?php echo $existing_payment['bukti_pembayaran']; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="padding: 20px; background: #fee2e2; border-radius: 12px; color: #dc2626;">
                                <i class="fas fa-exclamation-triangle"></i> File tidak ditemukan
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div id="uploadArea" class="upload-area" onclick="document.getElementById('bukti_bayar').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Upload Bukti Pembayaran</p>
                        <small>JPG, PNG, PDF (Max 2MB)</small>
                        <input type="file" name="bukti_bayar" id="bukti_bayar" accept="image/jpeg,image/png,application/pdf" style="display:none" required>
                    </div>
                    
                    <!-- PRATINJAU FILE YANG BARU DIPILIH (SEBELUM UPLOAD) -->
                    <div id="newPreviewArea" class="preview-area" style="display: none;">
                        <label><i class="fas fa-eye"></i> Pratinjau File Baru:</label>
                        <img id="newPreviewImage" class="preview-image" style="display: none;" alt="Pratinjau">
                        <iframe id="newPreviewPdf" class="preview-pdf" style="display: none;"></iframe>
                        <div id="newPreviewFile" style="padding: 20px; background: #f1f5f9; border-radius: 12px; display: none;">
                            <i class="fas fa-file"></i> <span id="newPreviewName"></span>
                        </div>
                    </div>
                    
                    <div id="fileInfo" class="file-info">
                        <span><i class="fas fa-file-image"></i> <span id="fileName"></span></span>
                        <span class="remove-file" onclick="removeFile()"><i class="fas fa-trash-alt"></i> Hapus</span>
                    </div>
                    
                    <div class="info-note">
                        <i class="fas fa-info-circle"></i> Bukti akan diverifikasi admin. Status akan berubah setelah diverifikasi.
                    </div>

                    <button type="submit" class="btn-bayar">
                        <i class="fas fa-check-circle"></i> Kirim Bukti Pembayaran
                    </button>
                    <a href="pemesanan_saya.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Pemesanan Saya</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    <?php if($metode_terpilih != 'cash'): ?>
    const fileInput = document.getElementById('bukti_bayar');
    const newPreviewArea = document.getElementById('newPreviewArea');
    const newPreviewImage = document.getElementById('newPreviewImage');
    const newPreviewPdf = document.getElementById('newPreviewPdf');
    const newPreviewFile = document.getElementById('newPreviewFile');
    const newPreviewName = document.getElementById('newPreviewName');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File maksimal 2MB!');
                    this.value = '';
                    return;
                }
                const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!allowed.includes(file.type)) {
                    alert('Format harus JPG, PNG, atau PDF');
                    this.value = '';
                    return;
                }
                
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileInfo').style.display = 'flex';
                
                // Tampilkan pratinjau
                newPreviewArea.style.display = 'block';
                
                if (file.type.startsWith('image/')) {
                    newPreviewImage.style.display = 'block';
                    newPreviewPdf.style.display = 'none';
                    newPreviewFile.style.display = 'none';
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        newPreviewImage.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else if (file.type === 'application/pdf') {
                    newPreviewImage.style.display = 'none';
                    newPreviewPdf.style.display = 'block';
                    newPreviewFile.style.display = 'none';
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        newPreviewPdf.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    newPreviewImage.style.display = 'none';
                    newPreviewPdf.style.display = 'none';
                    newPreviewFile.style.display = 'block';
                    newPreviewName.textContent = file.name;
                }
            } else {
                newPreviewArea.style.display = 'none';
                fileInfo.style.display = 'none';
            }
        });
    }
    
    function removeFile() {
        const input = document.getElementById('bukti_bayar');
        if (input) input.value = '';
        document.getElementById('fileInfo').style.display = 'none';
        document.getElementById('newPreviewArea').style.display = 'none';
        newPreviewImage.style.display = 'none';
        newPreviewPdf.style.display = 'none';
        newPreviewFile.style.display = 'none';
    }
    <?php endif; ?>
</script>
</body>
</html>