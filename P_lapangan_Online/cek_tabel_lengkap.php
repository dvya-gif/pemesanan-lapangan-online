<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "p_lapangan";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

echo "<h2>📋 Struktur Tabel jadwal</h2>";
$result = mysqli_query($conn, "DESCRIBE jadwal");
echo "<table border='1' cellpadding='8' border-collapse='collapse'>";
echo "<tr style='background:#eee'><th>Kolom</th><th>Tipe</th><th>Null</th><th>Key</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>📋 Data di tabel jadwal</h2>";
$data = mysqli_query($conn, "SELECT * FROM jadwal LIMIT 10");
if (mysqli_num_rows($data) > 0) {
    echo "<table border='1' cellpadding='8' border-collapse='collapse'>";
    $first = true;
    while ($row = mysqli_fetch_assoc($data)) {
        if ($first) {
            echo "<tr style='background:#eee'>";
            foreach (array_keys($row) as $col) {
                echo "<th>$col</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . ($value ?? '-') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tabel jadwal kosong";
}

echo "<h2>📋 Struktur Tabel lapangan</h2>";
$result = mysqli_query($conn, "DESCRIBE lapangan");
echo "<table border='1' cellpadding='8' border-collapse='collapse'>";
echo "<tr style='background:#eee'><th>Kolom</th><th>Tipe</th><th>Null</th><th>Key</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>📋 Data di tabel lapangan</h2>";
$data = mysqli_query($conn, "SELECT * FROM lapangan");
if (mysqli_num_rows($data) > 0) {
    echo "<table border='1' cellpadding='8' border-collapse='collapse'>";
    $first = true;
    while ($row = mysqli_fetch_assoc($data)) {
        if ($first) {
            echo "<tr style='background:#eee'>";
            foreach (array_keys($row) as $col) {
                echo "<th>$col</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . ($value ?? '-') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tabel lapangan kosong";
}
?>