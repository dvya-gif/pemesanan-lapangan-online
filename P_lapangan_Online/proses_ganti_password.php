<?php
session_start();
include 'koneksi.php';

// Cek apakah user sudah login
if(!isset($_SESSION['id_pengguna'])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['ganti_password'])) {
    $id_pengguna = $_SESSION['id_pengguna'];
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Validasi password baru minimal 6 karakter
    if(strlen($password_baru) < 6) {
        $_SESSION['error'] = "Password baru minimal 6 karakter!";
        header("Location: profil.php");
        exit();
    }
    
    // Validasi konfirmasi password
    if($password_baru != $konfirmasi_password) {
        $_SESSION['error'] = "Konfirmasi password tidak cocok!";
        header("Location: profil.php");
        exit();
    }
    
    // Ambil password dari database
    $query = "SELECT password FROM pengguna WHERE id_pengguna = '$id_pengguna'";
    $result = mysqli_query($koneksi, $query);
    $data = mysqli_fetch_assoc($result);
    
    // Verifikasi password lama
    if($password_lama != $data['password']) {  // Karena password tidak di-hash
        $_SESSION['error'] = "Password lama salah!";
        header("Location: profil.php");
        exit();
    }
    
    // Update password baru
    $update = "UPDATE pengguna SET password = '$password_baru' WHERE id_pengguna = '$id_pengguna'";
    
    if(mysqli_query($koneksi, $update)) {
        $_SESSION['success'] = "Password berhasil diubah!";
    } else {
        $_SESSION['error'] = "Gagal mengubah password: " . mysqli_error($koneksi);
    }
    
    header("Location: profil.php");
}
?>