<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "ppsi";

// Membuat koneksi
$conn = mysqli_connect($host, $user, $password, $database);

// Mengecek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Tambahkan baris ini untuk mengatur karakter set koneksi
// Ini penting untuk penanganan karakter yang benar dan mencegah potensi masalah string
if (!mysqli_set_charset($conn, "utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", mysqli_error($conn));
    exit();
}
?>