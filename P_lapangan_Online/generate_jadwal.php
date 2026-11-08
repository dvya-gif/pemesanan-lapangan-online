<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Hanya admin yang bisa akses (opsional)
// if ($_SESSION['user_role'] != 'admin') {
//     header("Location: index.php");
//     exit();
// }

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    
    // Ambil semua lapangan
    $query_lapangan = "SELECT id_lapangan FROM lapangan";
    $result_lapangan = mysqli_query($conn, $query_lapangan);
    
    // Jam operasional (08:00 - 23:00)
    $jam_mulai_list = ['08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', 
                       '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00', 
                       '18:00:00', '19:00:00', '20:00:00', '21:00:00', '22:00:00'];
    
    $jam_selesai_list = ['09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', 
                         '14:00:00', '15:00:00', '16:00:00', '17:00:00', '18:00:00', 
                         '19:00:00', '20:00:00', '21:00:00', '22:00:00', '23:00:00'];
    
    $total_insert = 0;
    $skipped = 0;
    
    // Loop setiap tanggal
    $current_date = strtotime($tanggal_mulai);
    $end_date = strtotime($tanggal_selesai);
    
    while ($current_date <= $end_date) {
        $tanggal = date('Y-m-d', $current_date);
        
        // Reset pointer hasil lapangan
        mysqli_data_seek($result_lapangan, 0);
        
        // Loop setiap lapangan
        while ($lapangan = mysqli_fetch_assoc($result_lapangan)) {
            $id_lapangan = $lapangan['id_lapangan'];
            
            // Loop setiap jam
            for ($i = 0; $i < count($jam_mulai_list); $i++) {
                $jam_mulai = $jam_mulai_list[$i];
                $jam_selesai = $jam_selesai_list[$i];
                
                // Cek apakah jadwal sudah ada
                $cek_query = "SELECT id_jadwal FROM jadwal 
                              WHERE id_lapangan = $id_lapangan 
                              AND tanggal = '$tanggal' 
                              AND jam_mulai = '$jam_mulai'";
                $cek_result = mysqli_query($conn, $cek_query);
                
                if (mysqli_num_rows($cek_result) == 0) {
                    // Insert jadwal baru dengan status tersedia
                    $insert_query = "INSERT INTO jadwal (id_lapangan, tanggal, jam_mulai, jam_selesai, status) 
                                     VALUES ($id_lapangan, '$tanggal', '$jam_mulai', '$jam_selesai', 'tersedia')";
                    
                    if (mysqli_query($conn, $insert_query)) {
                        $total_insert++;
                    } else {
                        $error .= "Gagal insert: " . mysqli_error($conn) . "<br>";
                    }
                } else {
                    $skipped++;
                }
            }
        }
        
        $current_date = strtotime('+1 day', $current_date);
    }
    
    $message = "✅ Berhasil generate $total_insert jadwal baru! (Skip $skipped jadwal yang sudah ada)";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Jadwal - DBooking Sport</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f9ff; }
        .container { max-width: 600px; margin: 50px auto; background: white; border-radius: 24px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        h1 { font-size: 24px; color: #1e293b; margin-bottom: 10px; }
        .subtitle { color: #64748b; margin-bottom: 25px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b; }
        input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 14px; }
        button { width: 100%; padding: 14px; background: #2563eb; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .message { padding: 12px; border-radius: 12px; margin-bottom: 20px; }
        .success { background: #dcfce7; color: #16a34a; border-left: 4px solid #16a34a; }
        .error { background: #fee2e2; color: #dc2626; border-left: 4px solid #dc2626; }
        .info { background: #eff6ff; color: #2563eb; border-left: 4px solid #2563eb; margin-top: 20px; padding: 15px; border-radius: 12px; font-size: 13px; }
        .info i { margin-right: 8px; }
        .back-link { display: inline-block; margin-top: 20px; color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📅 Generate Jadwal Lapangan</h1>
        <p class="subtitle">Buat jadwal untuk semua lapangan dalam range tanggal tertentu</p>
        
        <?php if($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>📅 Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>📅 Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
            </div>
            <button type="submit"><i class="fas fa-sync-alt"></i> Generate Jadwal</button>
        </form>
        
        <div class="info">
            <i class="fas fa-info-circle"></i> 
            <strong>Informasi:</strong><br>
            • Jam operasional: 08:00 - 23:00 (15 jam per hari)<br>
            • Akan membuat jadwal untuk SEMUA lapangan<br>
            • Jadwal yang sudah ada akan dilewati (tidak di-duplikat)
        </div>
        
        <a href="cari_lapangan.php" class="back-link">← Kembali ke Cari Lapangan</a>
    </div>
</body>
</html>