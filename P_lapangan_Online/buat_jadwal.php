<?php
include 'koneksi.php';

echo "<h2>Membuat Jadwal Lapangan</h2>";

// Method 1: Hapus data jadwal yang tidak terkait dengan pemesanan
// Hapus dulu foreign key constraint sementara
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// Hapus semua data di jadwal
mysqli_query($conn, "DELETE FROM jadwal");

// Aktifkan kembali foreign key
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

// Buat jadwal untuk 7 hari ke depan
$lapangan = mysqli_query($conn, "SELECT id_lapangan FROM lapangan");
$lapangan_ids = [];
while ($l = mysqli_fetch_assoc($lapangan)) {
    $lapangan_ids[] = $l['id_lapangan'];
}

$total_insert = 0;

for ($i = 0; $i < 7; $i++) {
    $tanggal = date('Y-m-d', strtotime("+$i days"));
    
    // Jam operasional 07:00 - 21:00 (setiap 2 jam)
    $jam_mulai_list = ['07:00:00', '09:00:00', '11:00:00', '13:00:00', '15:00:00', '17:00:00', '19:00:00', '21:00:00'];
    
    foreach ($jam_mulai_list as $jam_mulai) {
        $jam_selesai = date('H:i:s', strtotime($jam_mulai . ' +2 hours'));
        
        foreach ($lapangan_ids as $id_lapangan) {
            $query = "INSERT INTO jadwal (id_lapangan, tanggal, jam_mulai, jam_selesai, total_jam, status) 
                      VALUES ('$id_lapangan', '$tanggal', '$jam_mulai', '$jam_selesai', '2', 'Tersedia')";
            if (mysqli_query($conn, $query)) {
                $total_insert++;
            } else {
                echo "Error: " . mysqli_error($conn) . "<br>";
            }
        }
    }
}

echo "✅ Berhasil membuat $total_insert jadwal untuk 7 hari ke depan!<br>";
echo "📅 Tanggal: " . date('d F Y') . " - " . date('d F Y', strtotime('+6 days')) . "<br>";
echo "<br><a href='cari_lapangan.php' style='background:#1a1a2e; color:white; padding:10px 20px; text-decoration:none; border-radius:8px;'>🔍 Lihat Lapangan Tersedia</a>";
?>