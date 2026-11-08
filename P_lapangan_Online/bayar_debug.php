<?php
session_start();
include 'koneksi.php';

echo "<h2>=== DEBUG BAYAR.PHP ===</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "❌ Session user_id tidak ada!<br>";
    exit();
}
echo "✅ Session user_id: " . $_SESSION['user_id'] . "<br>";

$user_id = $_SESSION['user_id'];
$id_booking = isset($_GET['id']) ? (int)$_GET['id'] : 0;
echo "✅ ID Booking: " . $id_booking . "<br>";

if ($id_booking == 0) {
    echo "❌ ID booking = 0, akan redirect<br>";
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

echo "<br>=== HASIL QUERY ===<br>";
if (!$booking) {
    echo "❌ BOOKING TIDAK DITEMUKAN!<br>";
    echo "Query: " . htmlspecialchars($query) . "<br>";
    echo "Error: " . mysqli_error($conn) . "<br>";
} else {
    echo "✅ BOOKING DITEMUKAN!<br>";
    echo "ID Booking: " . $booking['id_booking'] . "<br>";
    echo "Status: " . $booking['status'] . "<br>";
    echo "Lapangan: " . $booking['nama_lapangan'] . "<br>";
}

// Cek kondisi yang menyebabkan redirect
echo "<br>=== CEK KONDISI REDIRECT ===<br>";

// Kondisi 1: booking tidak ditemukan
if (!$booking) {
    echo "❌ Kondisi 1 TERPENUHI: Booking tidak ditemukan!<br>";
    echo "→ Akan redirect ke pemesanan_saya.php<br>";
}

// Kondisi 2: sudah ada pembayaran
$check_payment = "SELECT id_pembayaran, status_pembayaran FROM pembayaran WHERE id_booking = $id_booking";
$payment_result = mysqli_query($conn, $check_payment);
$existing_payment = mysqli_fetch_assoc($payment_result);

if ($existing_payment && $existing_payment['status_pembayaran'] != 'ditolak') {
    echo "❌ Kondisi 2 TERPENUHI: Sudah ada pembayaran dengan status {$existing_payment['status_pembayaran']}<br>";
    echo "→ Akan redirect ke pemesanan_saya.php?msg=already_paid<br>";
} else {
    echo "✅ Kondisi 2 TIDAK terpenuhi: Belum ada pembayaran<br>";
}

echo "<br>=== KESIMPULAN ===<br>";
if (!$booking || ($existing_payment && $existing_payment['status_pembayaran'] != 'ditolak')) {
    echo "⚠️ Akan terjadi REDIRECT!<br>";
} else {
    echo "✅ TIDAK akan redirect. Halaman seharusnya tampil!<br>";
}
?>