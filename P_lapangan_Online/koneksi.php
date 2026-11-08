<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "p_lapangan";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

date_default_timezone_set('Asia/Jakarta');
?>