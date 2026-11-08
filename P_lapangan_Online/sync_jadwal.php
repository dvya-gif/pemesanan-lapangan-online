<?php
session_start();
include 'koneksi.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Sync jadwal berdasarkan pemesanan
$query = "
    UPDATE jadwal j
    LEFT JOIN pemesanan p ON j.id_jadwal = p.id_jadwal 
        AND p.status IN ('pending', 'menunggu_verifikasi', 'dikonfirmasi', 'selesai')
    SET j.status = CASE 
        WHEN p.id_jadwal IS NOT NULL THEN 'dipesan'
        ELSE 'tersedia'
    END
";

if (mysqli_query($conn, $query)) {
    $affected = mysqli_affected_rows($conn);
    $_SESSION['message'] = "✅ Berhasil sinkronisasi $affected jadwal!";
} else {
    $_SESSION['error'] = "❌ Gagal sinkronisasi: " . mysqli_error($conn);
}

header("Location: jadwal_lapangan.php");
exit();
?>