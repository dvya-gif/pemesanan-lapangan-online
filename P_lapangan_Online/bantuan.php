<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan - DBooking Sport</title>
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

        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }

        .logo {
            padding: 0 25px;
            margin-bottom: 35px;
            text-align: center;
        }
        .logo h1 { font-size: 24px; font-weight: 800; color: white; }
        .logo p { font-size: 11px; color: rgba(255,255,255,0.7); margin-top: 4px; }

        .nav-menu { list-style: none; }
        .nav-item { margin-bottom: 5px; }
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
        .nav-link:hover { background: rgba(255,255,255,0.15); color: white; transform: translateX(5px); }
        .nav-link.active { background: rgba(255,255,255,0.2); color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .nav-link i { width: 22px; }

        .main-content {
            margin-left: 260px;
            padding: 25px 35px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 15px 25px;
            border-radius: 20px;
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
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-info span { color: #475569; font-weight: 500; }
        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 8px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
        }
        .logout-btn:hover { background: #dc2626; transform: translateY(-2px); }

        .hero-banner {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 30px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-banner::before {
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
        .hero-banner h2 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        .hero-banner p {
            font-size: 14px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* FAQ ACCORDION */
        .faq-container {
            max-width: 800px;
            margin: 0 auto;
            margin-bottom: 30px;
        }
        .faq-item {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 15px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .faq-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .faq-question {
            padding: 18px 22px;
            background: #fafcff;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 15px;
            color: #1e293b;
            transition: all 0.2s;
        }
        .faq-question:hover {
            background: #f1f5f9;
        }
        .faq-icon {
            width: 36px;
            height: 36px;
            background: #eff6ff;
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-size: 16px;
        }
        .faq-question-text {
            flex: 1;
        }
        .faq-toggle {
            color: #94a3b8;
            transition: transform 0.3s;
        }
        .faq-item.active .faq-toggle {
            transform: rotate(180deg);
        }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: white;
        }
        .faq-item.active .faq-answer {
            max-height: 500px;
        }
        .faq-answer-content {
            padding: 20px 22px;
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
            border-top: 1px solid #f1f5f9;
            background: white;
        }

        .contact-section {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        .contact-header {
            padding: 20px 25px;
            background: #fafcff;
            border-bottom: 2px solid #f1f5f9;
        }
        .contact-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .contact-body {
            padding: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: space-around;
        }
        .contact-item {
            text-align: center;
            flex: 1;
            min-width: 150px;
        }
        .contact-icon {
            width: 60px;
            height: 60px;
            background: #eff6ff;
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            color: #2563eb;
        }
        .contact-item h4 {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .contact-item p {
            font-size: 13px;
            color: #475569;
        }
        .contact-item a {
            color: #2563eb;
            text-decoration: none;
        }

        .footer {
            text-align: center;
            padding: 30px;
            margin-top: 40px;
            color: #94a3b8;
            font-size: 12px;
            border-top: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo p, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
            .contact-body { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h1>🏸 DBooking</h1>
            <p>Sport</p>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="cari_lapangan.php" class="nav-link"><i class="fas fa-search"></i> <span>Cari Lapangan</span></a></li>
            <li class="nav-item"><a href="pemesanan_saya.php" class="nav-link"><i class="fas fa-calendar-alt"></i> <span>Pemesanan Saya</span></a></li>
            <li class="nav-item"><a href="riwayat_transaksi.php" class="nav-link"><i class="fas fa-history"></i> <span>Riwayat Transaksi</span></a></li>
            <li class="nav-item"><a href="profil_saya.php" class="nav-link"><i class="fas fa-user"></i> <span>Profil</span></a></li>
            <li class="nav-item"><a href="bantuan.php" class="nav-link active"><i class="fas fa-question-circle"></i> <span>Bantuan</span></a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Keluar</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-question-circle" style="margin-right: 10px; color:#2563eb;"></i> Pusat Bantuan</h1>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
            </div>
        </div>

        <div class="hero-banner">
            <h2>❓ Ada yang bisa kami bantu?</h2>
            <p>Temukan jawaban atas pertanyaan Anda seputar pemesanan lapangan</p>
        </div>

        <!-- FAQ ACCORDION - TANPA SEARCH -->
        <div class="faq-container" id="faqContainer">
            <div class="faq-item" data-title="cara memesan booking pesan lapangan">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <div class="faq-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="faq-question-text">📅 Bagaimana cara memesan lapangan?</div>
                    <div class="faq-toggle"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <strong>Ikuti langkah-langkah berikut:</strong><br><br>
                        1️⃣ Klik menu <strong>"Cari Lapangan"</strong> di sidebar<br>
                        2️⃣ Pilih lapangan yang diinginkan<br>
                        3️⃣ Klik tombol <strong>"Sewa Sekarang"</strong><br>
                        4️⃣ Pilih tanggal dan jam main<br>
                        5️⃣ Isi data pemesanan dan <strong>Konfirmasi</strong><br>
                        6️⃣ Lakukan pembayaran sesuai instruksi
                    </div>
                </div>
            </div>

            <div class="faq-item" data-title="pembayaran bayar metode transfer qris tunai ewallet">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <div class="faq-icon"><i class="fas fa-credit-card"></i></div>
                    <div class="faq-question-text">💰 Bagaimana cara melakukan pembayaran?</div>
                    <div class="faq-toggle"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <strong>Metode pembayaran yang tersedia:</strong><br><br>
                        💳 <strong>Transfer Bank</strong> (BCA, Mandiri, BRI, BNI)<br>
                        📱 <strong>E-Wallet</strong> (Dana,Go-pay,ovo)<br>
                        💵 <strong>Tunai</strong> (di tempat)<br><br>
                        Detail pembayaran akan muncul setelah pemesanan dikonfirmasi.
                    </div>
                </div>
            </div>

            <div class="faq-item" data-title="minimal jam durasi waktu sewa">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <div class="faq-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="faq-question-text">⏰ Berapa jam minimal pemesanan?</div>
                    <div class="faq-toggle"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <strong>Minimal pemesanan adalah 1 jam.</strong><br><br>
                        Anda dapat memesan lebih dari 1 jam sesuai kebutuhan.<br>
                        Setiap jam tambahan akan dikenakan biaya sesuai harga per jam lapangan.
                    </div>
                </div>
            </div>

            <div class="faq-item" data-title="batalkan pembatalan cancel batal refund">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <div class="faq-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="faq-question-text">❌ Bagaimana cara membatalkan pemesanan?</div>
                    <div class="faq-toggle"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <strong>Ketentuan pembatalan:</strong><br><br>
                        ✅ Pembatalan dapat dilakukan maksimal <strong>H-1</strong> sebelum jadwal main<br>
                        ✅ Buka menu <strong>"Pemesanan Saya"</strong> > pilih pesanan > klik tombol <strong>"Batalkan"</strong><br>
                        ⚠️ Pembatalan kurang dari 24 jam sebelum jadwal tidak dapat diproses<br>
                        💰 Dana akan dikembalikan sesuai ketentuan yang berlaku
                    </div>
                </div>
            </div>

            <div class="faq-item" data-title="tiket e-ticket cetak print download buka">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <div class="faq-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="faq-question-text">🎫 Bagaimana cara mendapatkan tiket?</div>
                    <div class="faq-toggle"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <strong>Setelah pembayaran dikonfirmasi:</strong><br><br>
                        1️⃣ Buka menu <strong>"Pemesanan Saya"</strong><br>
                        2️⃣ Pilih pemesanan dengan status <strong>"Akan Datang"</strong><br>
                        3️⃣ Klik tombol <strong>"Buka Tiket"</strong><br>
                        4️⃣ Tiket akan terbuka di halaman baru<br>
                        5️⃣ Anda bisa cetak atau simpan tiket sebagai bukti
                    </div>
                </div>
            </div>

            <div class="faq-item" data-title="status pending verifikasi lama cepat">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <div class="faq-icon"><i class="fas fa-clock"></i></div>
                    <div class="faq-question-text">⏳ Berapa lama verifikasi pembayaran?</div>
                    <div class="faq-toggle"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <strong>Waktu verifikasi pembayaran:</strong><br><br>
                        🕐 Transfer Bank: <strong>5-15 menit</strong> (saat jam kerja)<br>
                        📱 E-Wallet: <strong>5-30 menit</strong><br>
                        💵 Tunai: <strong>Langsung</strong> setelah pembayaran di tempat<br><br>
                        Jika pembayaran belum terverifikasi lebih dari 1 jam, segera hubungi customer service.
                    </div>
                </div>
            </div>
        </div>

        <div class="contact-section">
            <div class="contact-header">
                <h3><i class="fas fa-headset" style="color:#2563eb;"></i> Butuh Bantuan Langsung?</h3>
            </div>
            <div class="contact-body">
                <div class="contact-item">
                    <div class="contact-icon"><i class="fab fa-whatsapp"></i></div>
                    <h4>WhatsApp</h4>
                    <p><a href="https://wa.me/6281234567890" target="_blank">0822-8761-7482</a></p>
                </div>
                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                    <h4>Email</h4>
                    <p><a href="mailto:support@dbooking.com">divya@dbooking.com</a></p>
                </div>
                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-clock"></i></div>
                    <h4>Jam Operasional</h4>
                    <p>Senin - Minggu<br>08:00 - 22:00 WIB</p>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2026 DBooking Sport - Solusi Pemesanan Lapangan Online</p>
        </div>
    </div>

    <script>
        // Hanya satu FAQ yang terbuka dalam satu waktu
        function toggleFaq(element) {
            const currentItem = element.closest('.faq-item');
            const isActive = currentItem.classList.contains('active');
            
            // Tutup semua FAQ
            const allItems = document.querySelectorAll('.faq-item');
            allItems.forEach(item => {
                item.classList.remove('active');
            });
            
            // Buka yang diklik jika belum aktif
            if (!isActive) {
                currentItem.classList.add('active');
            }
        }
    </script>
</body>
</html>