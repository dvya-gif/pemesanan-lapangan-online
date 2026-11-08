<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Harus login']);
    exit();
}

$user_id = $_SESSION['user_id'];
$id_booking = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Cek apakah pemesanan milik user dan statusnya pending
$query = "SELECT * FROM pemesanan WHERE id_booking = $id_booking AND id_pengguna = $user_id AND status = 'pending'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    // Update status menjadi dibatalkan
    $update = mysqli_query($conn, "UPDATE pemesanan SET status = 'dibatalkan' WHERE id_booking = $id_booking");
    
    // Update juga status pembayaran jika ada
    mysqli_query($conn, "UPDATE pembayaran SET status_pembayaran = 'dibatalkan' WHERE id_booking = $id_booking");
    
    if ($update) {
        echo json_encode(['success' => true, 'message' => 'Pemesanan dibatalkan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal membatalkan']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Pemesanan tidak ditemukan']);
}
?>