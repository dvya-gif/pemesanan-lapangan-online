<?php
include 'koneksi.php';

// Update status yang sudah lewat
$query = "UPDATE pemesanan 
          SET status = 'selesai' 
          WHERE status = 'dikonfirmasi' 
          AND tanggal_pesan < CURDATE()";

if (mysqli_query($conn, $query)) {
    echo "✅ " . date('Y-m-d H:i:s') . " - Updated " . mysqli_affected_rows($conn) . " records\n";
}

// Log ke file
file_put_contents('cron_log.txt', date('Y-m-d H:i:s') . " - Cron executed\n", FILE_APPEND);
?>