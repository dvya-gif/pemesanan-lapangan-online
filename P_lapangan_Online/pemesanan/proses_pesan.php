<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_lapangan = $_POST['id_lapangan'];
    $nama_pemesan = mysqli_real_escape_string($conn, $_POST['nama_pemesan']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $tanggal_pesan = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $metode = $_POST['metode'];
    
    // Ambil harga dari tabel lapangan
    $query_harga = mysqli_query($conn, "SELECT harga_per_jam, nama_lapangan FROM lapangan WHERE id_lapangan = $id_lapangan");
    $harga_data = mysqli_fetch_assoc($query_harga);
    $total_harga = $harga_data['harga_per_jam'];
    $nama_lapangan = $harga_data['nama_lapangan'];
    
    // Hitung jam selesai (1 jam kemudian)
    $jam_selesai = date('H:i:s', strtotime($jam_mulai . ' +1 hour'));
    
    // Insert ke tabel pemesanan (tanpa id_jadwal)
    $query = "INSERT INTO pemesanan (id_pengguna, tanggal_pesan, jam_mulai, jam_selesai, total_harga, status, created_at) 
              VALUES ('$user_id', '$tanggal_pesan', '$jam_mulai', '$jam_selesai', '$total_harga', 'pending', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $id_pemesanan = mysqli_insert_id($conn);
        
        // Insert ke tabel pembayaran
        $query_bayar = "INSERT INTO pembayaran (id_pemesanan, id_pengguna, jumlah, metode, status, created_at) 
                        VALUES ('$id_pemesanan', '$user_id', '$total_harga', '$metode', 'pending', NOW())";
        mysqli_query($conn, $query_bayar);
        
        // Redirect ke halaman sukses
        header("Location: konfirmasi_sukses.php?id=$id_pemesanan");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: cari_lapangan.php");
}
?>