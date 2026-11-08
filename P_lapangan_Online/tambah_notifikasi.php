<?php
include 'koneksi.php';

// Contoh menambah notifikasi untuk user tertentu
function tambahNotifikasi($user_id, $judul, $pesan) {
    global $conn;
    $judul = mysqli_real_escape_string($conn, $judul);
    $pesan = mysqli_real_escape_string($conn, $pesan);
    $query = "INSERT INTO notifikasi (id_pengguna, judul, pesan, is_dibaca, created_at) 
              VALUES ('$user_id', '$judul', '$pesan', 0, NOW())";
    return mysqli_query($conn, $query);
}

// Contoh penggunaan:
// tambahNotifikasi(1, 'Pemesanan Baru Dibuat', 'Silakan selesaikan pembayaran...');
?>