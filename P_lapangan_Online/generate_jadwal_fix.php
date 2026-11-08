<?php
include 'koneksi.php';

// Hapus jadwal lama
mysqli_query($conn, "TRUNCATE TABLE jadwal");

// Ambil semua lapangan
$lapangan = mysqli_query($conn, "SELECT id_lapangan FROM lapangan");

// Jam operasional
$jam_list = [
    '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00',
    '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00',
    '18:00:00', '19:00:00', '20:00:00', '21:00:00', '22:00:00'
];

// Generate untuk 30 hari ke depan
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+30 days'));

$current_date = $start_date;
$total = 0;

while (strtotime($current_date) <= strtotime($end_date)) {
    mysqli_data_seek($lapangan, 0);
    
    while ($lap = mysqli_fetch_assoc($lapangan)) {
        foreach ($jam_list as $jam_mulai) {
            $jam_selesai = date('H:i:s', strtotime($jam_mulai . ' +1 hour'));
            
            $insert = "INSERT INTO jadwal (id_lapangan, tanggal, jam_mulai, jam_selesai, status) 
                      VALUES ({$lap['id_lapangan']}, '$current_date', '$jam_mulai', '$jam_selesai', 'tersedia')";
            
            if (mysqli_query($conn, $insert)) {
                $total++;
            }
        }
    }
    
    $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
}

echo "✅ Berhasil generate $total jadwal (tanpa kolom total_jam)!";
echo "<br><br>";
echo "<a href='cari_lapangan.php'>Kembali ke Cari Lapangan</a>";
?>