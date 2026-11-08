<?php
session_start();
include 'koneksi.php';

echo "<pre>";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'TIDAK ADA') . "<br>";
echo "Session user_name: " . ($_SESSION['user_name'] ?? 'TIDAK ADA') . "<br>";
echo "</pre>";

// Cek semua data session
echo "Semua Session: ";
print_r($_SESSION);
echo "</pre>";
?>