<?php
include 'koneksi.php';
$sql = "ALTER TABLE pemesanan ADD COLUMN metode_pembayaran VARCHAR(20) NOT NULL DEFAULT 'cash'";
if (mysqli_query($conn, $sql)) {
    echo "Kolom berhasil ditambahkan! Silakan hapus file ini.";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>