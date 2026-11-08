<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil statistik untuk Dashboard
$stats = [];

// Total Pemesanan
$query_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE id_pengguna = $user_id");
$stats['total_pemesanan'] = mysqli_fetch_assoc($query_total)['total'];

// Menunggu pembayaran (status: pending)
$query_menunggu = mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE id_pengguna = $user_id AND status = 'pending'");
$stats['menunggu_pembayaran'] = mysqli_fetch_assoc($query_menunggu)['total'];

// Akan Datang (status: confirmed/approved dan tanggal >= hari ini)
$query_akan_datang = mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE id_pengguna = $user_id AND status IN ('confirmed', 'approved') AND tanggal >= CURDATE()");
$stats['akan_datang'] = mysqli_fetch_assoc($query_akan_datang)['total'];

// Selesai (status: completed/done)
$query_selesai = mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE id_pengguna = $user_id AND status = 'completed'");
$stats['selesai'] = mysqli_fetch_assoc($query_selesai)['total'];

// Dibatalkan (status: cancelled)
$query_dibatalkan = mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE id_pengguna = $user_id AND status = 'cancelled'");
$stats['dibatalkan'] = mysqli_fetch_assoc($query_dibatalkan)['total'];

// Total Pengeluaran (total harga dari transaksi yang lunas/confirmed)
$query_total_biaya = mysqli_query($conn, "SELECT SUM(total_harga) as total FROM pemesanan WHERE id_pengguna = $user_id AND status IN ('confirmed', 'approved', 'completed')");
$stats['total_pengeluaran'] = mysqli_fetch_assoc($query_total_biaya)['total'] ?? 0;

// Untuk Riwayat Transaksi
// Total transaksi lunas (confirmed/completed)
$query_lunas = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(total_harga) as nominal FROM pemesanan WHERE id_pengguna = $user_id AND status IN ('confirmed', 'approved', 'completed')");
$lunas_data = mysqli_fetch_assoc($query_lunas);
$stats['total_transaksi_lunas'] = $lunas_data['total'] ?? 0;
$stats['total_nominal_lunas'] = $lunas_data['nominal'] ?? 0;

// Total menunggu (pending)
$query_menunggu_total = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(total_harga) as nominal FROM pemesanan WHERE id_pengguna = $user_id AND status = 'pending'");
$menunggu_data = mysqli_fetch_assoc($query_menunggu_total);
$stats['total_menunggu'] = $menunggu_data['total'] ?? 0;
$stats['total_nominal_menunggu'] = $menunggu_data['nominal'] ?? 0;

// Transaksi berhasil (confirmed/completed)
$stats['transaksi_berhasil'] = $stats['total_transaksi_lunas'];

// Total riwayat (semua transaksi)
$stats['total_riwayat'] = $stats['total_pemesanan'];

// Ambil daftar transaksi untuk tabel riwayat
$query_transaksi = mysqli_query($conn, "SELECT p.*, l.nama_lapangan, l.harga_per_jam 
    FROM pemesanan p 
    JOIN lapangan l ON p.id_lapangan = l.id_lapangan 
    WHERE p.id_pengguna = $user_id 
    ORDER BY p.tanggal_pemesanan DESC");

$transaksi_list = [];
while ($row = mysqli_fetch_assoc($query_transaksi)) {
    $transaksi_list[] = $row;
}

$stats['transaksi_list'] = $transaksi_list;

echo json_encode($stats);
?>