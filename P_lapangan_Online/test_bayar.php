<?php
session_start();
include 'koneksi.php';

$user_id = $_SESSION['user_id'];
$id_booking = 34;

$query = "SELECT p.id_booking, p.status, l.nama_lapangan, l.harga_per_jam, j.tanggal, j.jam_mulai, j.jam_selesai
          FROM pemesanan p
          JOIN jadwal j ON p.id_jadwal = j.id_jadwal
          JOIN lapangan l ON j.id_lapangan = l.id_lapangan
          WHERE p.id_booking = $id_booking AND p.id_pengguna = $user_id";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

$booking = mysqli_fetch_assoc($result);

if ($booking) {
    echo "SUKSES! Data ditemukan:<br>";
    echo "ID Booking: " . $booking['id_booking'] . "<br>";
    echo "Lapangan: " . $booking['nama_lapangan'] . "<br>";
    echo "Harga: Rp " . number_format($booking['harga_per_jam'], 0, ',', '.') . "<br>";
} else {
    echo "GAGAL! Data tidak ditemukan.<br>";
    echo "Jumlah baris: " . mysqli_num_rows($result);
}
?>