<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "p_lapangan";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

echo "<h2>Memperbaiki Tabel lapangan</h2>";

// 1. Backup data dulu
$data_lama = mysqli_query($conn, "SELECT * FROM lapangan");
$backup = [];
while ($row = mysqli_fetch_assoc($data_lama)) {
    $backup[] = $row;
}

// 2. Hapus isi tabel
mysqli_query($conn, "TRUNCATE TABLE lapangan");

// 3. Ubah tipe kolom id_lapangan menjadi AUTO_INCREMENT
mysqli_query($conn, "ALTER TABLE lapangan MODIFY id_lapangan INT(11) NOT NULL AUTO_INCREMENT");

// 4. Masukkan data kembali dengan id yang benar
$data_baru = [
    ['nama_lapangan' => 'Futsal A', 'id_olahraga' => 1, 'lokasi' => 'Jl. Arena Olahraga No. 12, Kec. Pariaman Timur, Pariaman', 'harga_per_jam' => 100000],
    ['nama_lapangan' => 'Futsal B', 'id_olahraga' => 1, 'lokasi' => 'Jl. Arena Olahraga No. 14, Kec. Pariaman Timur, Pariaman', 'harga_per_jam' => 100000],
    ['nama_lapangan' => 'Basket A', 'id_olahraga' => 2, 'lokasi' => 'Jl. Arena Olahraga No. 20, Kec. Pariaman Timur, Pariaman', 'harga_per_jam' => 120000],
    ['nama_lapangan' => 'Basket B', 'id_olahraga' => 2, 'lokasi' => 'Jl. Arena Olahraga No. 18, Kec. Pariaman Timur, Pariaman', 'harga_per_jam' => 120000],
    ['nama_lapangan' => 'Badminton A', 'id_olahraga' => 3, 'lokasi' => 'Jl. Garuda No. 1, Kec. Pariaman Timur, Pariaman', 'harga_per_jam' => 80000],
    ['nama_lapangan' => 'Badminton B', 'id_olahraga' => 3, 'lokasi' => 'Jl. Garuda No. 5, Kec. Pariaman Timur, Pariaman', 'harga_per_jam' => 80000],
    ['nama_lapangan' => 'Tennis A', 'id_olahraga' => 4, 'lokasi' => 'Jl. Garuda No. 7, Kec. Pariaman Timur, Pariaman', 'harga_per_jam' => 130000],
    ['nama_lapangan' => 'Tennis B', 'id_olahraga' => 4, 'lokasi' => 'Jl. Garuda No. 8, Kec. Pariaman Timur, Pariaman', 'harga_per_jam' => 130000],
];

foreach ($data_baru as $data) {
    $query = "INSERT INTO lapangan (nama_lapangan, id_olahraga, lokasi, harga_per_jam) 
              VALUES ('{$data['nama_lapangan']}', '{$data['id_olahraga']}', '{$data['lokasi']}', '{$data['harga_per_jam']}')";
    mysqli_query($conn, $query);
}

echo "✅ Tabel lapangan telah diperbaiki!<br>";

// Cek hasil
$result = mysqli_query($conn, "SELECT * FROM lapangan");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>id_lapangan</th><th>nama_lapangan</th><th>id_olahraga</th><th>lokasi</th><th>harga_per_jam</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['id_lapangan']}</td>";
    echo "<td>{$row['nama_lapangan']}</td>";
    echo "<td>{$row['id_olahraga']}</td>";
    echo "<td>{$row['lokasi']}</td>";
    echo "<td>{$row['harga_per_jam']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><a href='index.php'>Kembali ke Dashboard</a>";
?>