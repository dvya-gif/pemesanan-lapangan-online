<?php
include 'koneksi.php';

echo "<h2>Cek Metode Pembayaran</h2>";

$query = "SELECT p.id_booking, p.total_harga, py.metode_bayar 
          FROM pemesanan p 
          LEFT JOIN pembayaran py ON p.id_booking = py.id_booking";
$result = mysqli_query($conn, $query);

echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID Booking</th><th>Total Harga</th><th>Metode Bayar</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    $metode = $row['metode_bayar'] ?: '<span style="color:red;">KOSONG</span>';
    echo "<tr>";
    echo "<td>{$row['id_booking']}</td>";
    echo "<td>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>";
    echo "<td>$metode</td>";
    echo "</tr>";
}
echo "</table>";
?>