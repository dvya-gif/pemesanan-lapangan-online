<?php
include 'koneksi.php';

echo "<h2>Update Struktur Tabel</h2>";

// Cek dan tambah kolom status di tabel pemesanan
$cek_status = mysqli_query($conn, "SHOW COLUMNS FROM pemesanan LIKE 'status'");
if (mysqli_num_rows($cek_status) == 0) {
    $query = "ALTER TABLE pemesanan ADD COLUMN status ENUM('pending','dikonfirmasi','selesai','dibatalkan') DEFAULT 'pending'";
    if (mysqli_query($conn, $query)) {
        echo "✅ Kolom 'status' berhasil ditambahkan ke tabel pemesanan<br>";
    } else {
        echo "❌ Gagal menambah kolom status: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ Kolom 'status' sudah ada di tabel pemesanan<br>";
}

// Cek dan tambah kolom tanggal_pesan jika belum ada
$cek_tanggal = mysqli_query($conn, "SHOW COLUMNS FROM pemesanan LIKE 'tanggal_pesan'");
if (mysqli_num_rows($cek_tanggal) == 0) {
    $query = "ALTER TABLE pemesanan ADD COLUMN tanggal_pesan DATE";
    if (mysqli_query($conn, $query)) {
        echo "✅ Kolom 'tanggal_pesan' berhasil ditambahkan<br>";
    } else {
        echo "❌ Gagal menambah kolom tanggal_pesan<br>";
    }
} else {
    echo "✅ Kolom 'tanggal_pesan' sudah ada<br>";
}

// Cek dan tambah kolom jam_mulai
$cek_jam_mulai = mysqli_query($conn, "SHOW COLUMNS FROM pemesanan LIKE 'jam_mulai'");
if (mysqli_num_rows($cek_jam_mulai) == 0) {
    $query = "ALTER TABLE pemesanan ADD COLUMN jam_mulai TIME";
    if (mysqli_query($conn, $query)) {
        echo "✅ Kolom 'jam_mulai' berhasil ditambahkan<br>";
    } else {
        echo "❌ Gagal menambah kolom jam_mulai<br>";
    }
} else {
    echo "✅ Kolom 'jam_mulai' sudah ada<br>";
}

// Cek dan tambah kolom jam_selesai
$cek_jam_selesai = mysqli_query($conn, "SHOW COLUMNS FROM pemesanan LIKE 'jam_selesai'");
if (mysqli_num_rows($cek_jam_selesai) == 0) {
    $query = "ALTER TABLE pemesanan ADD COLUMN jam_selesai TIME";
    if (mysqli_query($conn, $query)) {
        echo "✅ Kolom 'jam_selesai' berhasil ditambahkan<br>";
    } else {
        echo "❌ Gagal menambah kolom jam_selesai<br>";
    }
} else {
    echo "✅ Kolom 'jam_selesai' sudah ada<br>";
}

// Cek dan tambah kolom total_harga
$cek_total = mysqli_query($conn, "SHOW COLUMNS FROM pemesanan LIKE 'total_harga'");
if (mysqli_num_rows($cek_total) == 0) {
    $query = "ALTER TABLE pemesanan ADD COLUMN total_harga INT DEFAULT 0";
    if (mysqli_query($conn, $query)) {
        echo "✅ Kolom 'total_harga' berhasil ditambahkan<br>";
    } else {
        echo "❌ Gagal menambah kolom total_harga<br>";
    }
} else {
    echo "✅ Kolom 'total_harga' sudah ada<br>";
}

// Cek dan tambah kolom created_at
$cek_created = mysqli_query($conn, "SHOW COLUMNS FROM pemesanan LIKE 'created_at'");
if (mysqli_num_rows($cek_created) == 0) {
    $query = "ALTER TABLE pemesanan ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    if (mysqli_query($conn, $query)) {
        echo "✅ Kolom 'created_at' berhasil ditambahkan<br>";
    } else {
        echo "❌ Gagal menambah kolom created_at<br>";
    }
} else {
    echo "✅ Kolom 'created_at' sudah ada<br>";
}

echo "<hr>";
echo "<h3>Update selesai! <a href='index.php'>Kembali ke Dashboard</a></h3>";
?>