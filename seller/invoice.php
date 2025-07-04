<?php
session_start();
include '../config/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
  header("Location: orders.php");
  exit();
}

$order_id = $_GET['id'];
$seller_id = $_SESSION['user_id'];

$query = "
  SELECT o.*, u.username AS buyer_name, u.email AS buyer_email, p.name AS product_name, p.price, p.image
  FROM orders o
  JOIN sellerproducts p ON o.product_id = p.id
  JOIN users u ON o.buyer_id = u.id
  WHERE o.id = ? AND p.seller_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
  echo "Invoice tidak ditemukan atau bukan milik Anda.";
  exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Invoice #<?= $order['id'] ?></title>
  <link rel="stylesheet" href="../assets/assets/invoice.css">
</head>
<body onload="window.print()">
  <div class="invoice-container">
    <h1>INVOICE</h1>
    <p><strong>ID Pesanan:</strong> <?= $order['id'] ?></p>
    <p><strong>Tanggal:</strong> <?= date('d M Y H:i', strtotime($order['order_date'])) ?></p>
    <p><strong>Status:</strong> <?= ucfirst($order['status']) ?></p>

    <hr>

    <h3>Pembeli</h3>
    <p>Nama: <?= htmlspecialchars($order['buyer_name']) ?></p>
    <p>Email: <?= htmlspecialchars($order['buyer_email']) ?></p>

    <hr>

    <h3>Detail Produk</h3>
    <table>
      <thead>
        <tr>
          <th>Produk</th>
          <th>Qty</th>
          <th>Harga</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?= htmlspecialchars($order['product_name']) ?></td>
          <td><?= $order['quantity'] ?></td>
          <td>Rp <?= number_format($order['price'], 0, ',', '.') ?></td>
          <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
        </tr>
      </tbody>
    </table>

    <hr>
    <h3 style="text-align: right;">TOTAL: Rp <?= number_format($order['total_price'], 0, ',', '.') ?></h3>
    <p style="text-align: center;">Terima kasih telah berbelanja di toko kami!</p>
  </div>
</body>
</html>
