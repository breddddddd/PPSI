<?php
session_start();
include '../config/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT username FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Penjual</title>
  <link rel="stylesheet" href="../assets/assets/seller.css">
</head>
<body>
  <div class="seller-container">
    <h2>Halo, <?= htmlspecialchars($user['username']) ?> 👋</h2>

    <div class="seller-menu">
      <a href="sellerproducts.php">📦 Produk Saya</a>
      <a href="add_product.php">➕ Tambah Produk</a>
      <a href="orders.php">🛒 Pesanan Masuk</a>
    </div>
  </div>
</body>
</html>
