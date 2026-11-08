<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "p_lapangan";

$conn = mysqli_connect($host, $user, $password, $database);

if ($conn) {
    echo "✅ Koneksi BERHASIL! Database p_lapangan terhubung.";
    echo "<br>Host: " . $host;
    echo "<br>Database: " . $database;
} else {
    echo "❌ Koneksi GAGAL: " . mysqli_connect_error();
}
?>