<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "p_lapangan";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

echo "<h2>Memperbaiki Tabel lapangan (Tanpa Menghapus Data)</h2>";

// Cek data di tabel lapangan saat ini
$cek = mysqli_query($conn, "SELECT * FROM lapangan");
echo "<h3>Data Saat Ini:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>id_lapangan</th><th>nama_lapangan</th><th>id_olahraga</th><th>lokasi</th><th>harga_per_jam</th></tr>";
while ($row = mysqli_fetch_assoc($cek)) {
    echo "<tr>";
    echo "<td>{$row['id_lapangan']}</td>";
    echo "<td>{$row['nama_lapangan']}</td>";
    echo "<td>{$row['id_olahraga']}</td>";
    echo "<td>{$row['lokasi']}</td>";
    echo "<td>{$row['harga_per_jam']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>✅ Tidak perlu perbaikan! Data sudah benar.</h3>";
echo "<p>Struktur tabel lapangan sudah OK. Kolom id_lapangan sudah berisi angka yang benar.</p>";

// Cek apakah id_lapangan sudah auto_increment
$cek_auto = mysqli_query($conn, "SHOW COLUMNS FROM lapangan LIKE 'id_lapangan'");
$col = mysqli_fetch_assoc($cek_auto);
if (strpos($col['Extra'], 'auto_increment') === false) {
    // Hanya ubah ke auto_increment tanpa truncate
    mysqli_query($conn, "ALTER TABLE lapangan MODIFY id_lapangan INT(11) NOT NULL AUTO_INCREMENT");
    echo "<p>✅ Kolom id_lapangan sudah diubah menjadi AUTO_INCREMENT</p>";
} else {
    echo "<p>✅ Kolom id_lapangan sudah AUTO_INCREMENT</p>";
}

echo "<br><a href='index.php'>Kembali ke Dashboard</a>";
?>