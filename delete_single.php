<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

if (isset($_GET['id'])) {
  $cart_id = $_GET['id'];
  $user_id = $_SESSION['user_id'];

  // Hapus hanya jika cart_id milik user yang sedang login
  $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $cart_id, $user_id);
  $stmt->execute();
}

header("Location: chart.php"); // Kembali ke halaman keranjang
exit();
?>
