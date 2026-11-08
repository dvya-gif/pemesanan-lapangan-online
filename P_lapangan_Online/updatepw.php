<?php
include 'koneksi.php';

$query = mysqli_query($conn, "SELECT * FROM pengguna");
while ($user = mysqli_fetch_assoc($query)) {
    // Cek apakah password sudah terhash (panjang 60 karakter untuk bcrypt)
    if (strlen($user['password']) != 60 || !password_verify($user['password'], $user['password'])) {
        $hashed = password_hash($user['password'], PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE pengguna SET password = '$hashed' WHERE id_pengguna = " . $user['id_pengguna']);
        echo "Password untuk " . $user['nama_pengguna'] . " telah diupdate<br>";
    }
}
echo "Selesai!";
?>