<?php
session_start();
include 'koneksi.php';

// Header untuk JSON response
header('Content-Type: application/json');

// Cek parameter yang diperlukan
if (!isset($_GET['id_lapangan']) || !isset($_GET['tanggal'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter tidak lengkap. id_lapangan dan tanggal diperlukan.',
        'data' => []
    ]);
    exit();
}

$id_lapangan = (int)$_GET['id_lapangan'];
$tanggal = mysqli_real_escape_string($conn, $_GET['tanggal']);

// Validasi tanggal
if (empty($tanggal)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tanggal tidak boleh kosong.',
        'data' => []
    ]);
    exit();
}

// Query untuk mengambil jam yang sudah DIBOOKING
$query = "SELECT 
            j.id_jadwal,
            j.jam_mulai, 
            j.jam_selesai,
            p.id_booking,
            p.status as status_pemesanan,
            pg.nama_pengguna
          FROM jadwal j
          JOIN pemesanan p ON j.id_jadwal = p.id_jadwal
          JOIN pengguna pg ON p.id_pengguna = pg.id_pengguna
          WHERE j.id_lapangan = $id_lapangan 
          AND j.tanggal = '$tanggal' 
          AND j.status = 'dipesan'
          AND p.status IN ('pending', 'dikonfirmasi')
          ORDER BY j.jam_mulai";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Error query: ' . mysqli_error($conn),
        'data' => []
    ]);
    exit();
}

$booked = [];
while ($row = mysqli_fetch_assoc($result)) {
    $booked[] = [
        'id_jadwal' => $row['id_jadwal'],
        'jam_mulai' => substr($row['jam_mulai'], 0, 5),
        'jam_selesai' => substr($row['jam_selesai'], 0, 5),
        'id_booking' => $row['id_booking'],
        'status' => $row['status_pemesanan'],
        'pemesan' => $row['nama_pengguna']
    ];
}

echo json_encode([
    'success' => true,
    'message' => 'Berhasil mengambil data jadwal yang sudah dibooking',
    'tanggal' => $tanggal,
    'total' => count($booked),
    'data' => $booked
]);
?>