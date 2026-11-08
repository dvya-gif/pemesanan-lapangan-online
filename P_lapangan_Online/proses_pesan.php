<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_pengguna = $_SESSION['user_id'];
    $id_lapangan = $_POST['id_lapangan'];
    $tanggal = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $total_harga = $_POST['total_harga'];
    
    $query = "INSERT INTO pemesanan (id_pengguna, id_lapangan, tanggal_pesan, jam_mulai, jam_selesai, total_harga, status) 
              VALUES ('$id_pengguna', '$id_lapangan', '$tanggal', '$jam_mulai', '$jam_selesai', '$total_harga', 'pending')";
    
    if (mysqli_query($conn, $query)) {
        $id_pemesanan = mysqli_insert_id($conn);
        
        $notif_query = "INSERT INTO notifikasi (id_pengguna, judul, pesan) 
                        VALUES ('$id_pengguna', 'Pemesanan Berhasil', 'Pemesanan lapangan telah berhasil. Silakan lakukan pembayaran.')";
        mysqli_query($conn, $notif_query);
        
        header("Location: pembayaran.php?success=1");
    } else {
        header("Location: cari_lapangan.php?error=1");
    }
} else {
    header("Location: cari_lapangan.php");
}
?>