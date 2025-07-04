<?php
session_start();
include 'config/config.php'; // pastikan file koneksi database

if (!isset($_SESSION['user_id'])) {
  echo 'not_logged_in';
  exit;
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);

// Cek apakah sudah difavoritkan
$cek = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND product_id = ?");
$cek->bind_param("ii", $user_id, $product_id);
$cek->execute();
$result = $cek->get_result();

if ($result->num_rows > 0) {
  // Sudah favorit → hapus
  $hapus = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
  $hapus->bind_param("ii", $user_id, $product_id);
  $hapus->execute();
  echo 'removed';
} else {
  // Belum favorit → simpan
  $simpan = $conn->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
  $simpan->bind_param("ii", $user_id, $product_id);
  $simpan->execute();
  echo 'added';
}
?>
