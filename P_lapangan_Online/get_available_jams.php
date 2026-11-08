<?php
include 'koneksi.php';

header('Content-Type: application/json');

$id_lapangan = isset($_GET['id_lapangan']) ? (int)$_GET['id_lapangan'] : 0;
$tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($conn, $_GET['tanggal']) : '';

if ($id_lapangan == 0 || empty($tanggal)) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap', 'data' => []]);
    exit();
}

// Query ambil jadwal yang tersedia
$query = "SELECT id_jadwal, jam_mulai, jam_selesai, 
          TIME_FORMAT(jam_mulai, '%H:%i') as jam_mulai_format,
          TIME_FORMAT(jam_selesai, '%H:%i') as jam_selesai_format
          FROM jadwal 
          WHERE id_lapangan = $id_lapangan 
          AND tanggal = '$tanggal' 
          AND status = 'tersedia'
          ORDER BY jam_mulai";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Error query: ' . mysqli_error($conn), 'data' => []]);
    exit();
}

$available = [];
while ($row = mysqli_fetch_assoc($result)) {
    $available[] = [
        'id_jadwal' => $row['id_jadwal'],
        'jam_mulai' => $row['jam_mulai_format'],
        'jam_selesai' => $row['jam_selesai_format'],
        'harga_per_jam' => 100000 // sesuaikan atau ambil dari database
    ];
}

echo json_encode([
    'success' => true, 
    'message' => 'Berhasil', 
    'total' => count($available),
    'data' => $available
]);
?>