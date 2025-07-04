<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Ambil isi keranjang
$sql = "SELECT c.quantity, p.name, p.price, c.custom_image, c.product_id, p.image
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
  $is_custom = empty($row['product_id']) || $row['product_id'] == 0;
  $harga = $is_custom ? 15000 : (isset($row['price']) ? $row['price'] : 0);
  $row['price'] = $harga; // Pastikan harga ini disimpan dalam $item
  $total += $harga * $row['quantity'];
  $items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Checkout - Jepit Aku Lucu</title>
  <link rel="stylesheet" href="assets/assets/checkout.css">
  <style>
    .checkout-container { max-width: 800px; margin: auto; padding: 20px; }
    .checkout-summary { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; }
    .checkout-summary h3 { margin-bottom: 10px; }
    .checkout-summary table { width: 100%; border-collapse: collapse; }
    .checkout-summary th, .checkout-summary td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
    .checkout-form { background: #f9f9f9; padding: 15px; border-radius: 5px; }
    .checkout-form input, .checkout-form textarea { width: 100%; margin-bottom: 10px; padding: 10px; }
    .checkout-form button { padding: 10px 20px; background-color: #e91e63; color: white; border: none; border-radius: 5px; cursor: pointer; }
    .checkout-form button:hover { opacity: 0.9; }
  </style>
</head>
<body>

<div class="checkout-container">
  <h2>Checkout</h2>

  <div class="checkout-summary">
    <h3>Ringkasan Belanja</h3>
    <table>
      <tr>
        <th>Produk</th>
        <th>Jumlah</th>
        <th>Harga</th>
        <th>Subtotal</th>
      </tr>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= htmlspecialchars($item['name'] ?? 'Custom Design') ?></td>
          <td><?= $item['quantity'] ?></td>
          <td>Rp<?= number_format($item['price'], 0, ',', '.') ?></td>
          <td>Rp<?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
      <tr>
        <th colspan="3">Total</th>
        <th>Rp<?= number_format($total, 0, ',', '.') ?></th>
      </tr>
    </table>
  </div>

  <form class="checkout-form" action="payment_options.php" method="POST">
    <h3>Data Pengiriman</h3>
    <input type="text" name="receiver_name" placeholder="Nama Penerima" required>
    <textarea name="shipping_address" rows="4" placeholder="Alamat Lengkap" required></textarea>
    <input type="text" name="phone_number" placeholder="Nomor Telepon" required>
    <input type="hidden" name="total_amount" value="<?= $total ?>">
    <?php
    foreach ($items as $index => $item) {
        echo '<input type="hidden" name="items[' . $index . '][product_id]" value="' . htmlspecialchars($item['product_id'] ?? '') . '">';
        echo '<input type="hidden" name="items[' . $index . '][name]" value="' . htmlspecialchars($item['name'] ?? 'Custom Design') . '">';
        echo '<input type="hidden" name="items[' . $index . '][quantity]" value="' . htmlspecialchars($item['quantity']) . '">';
        echo '<input type="hidden" name="items[' . $index . '][price]" value="' . htmlspecialchars($item['price']) . '">';
        echo '<input type="hidden" name="items[' . $index . '][custom_image]" value="' . htmlspecialchars($item['custom_image'] ?? '') . '">';
    }
    ?>
    <button type="submit">Bayar</button>
  </form>
</div>

</body>
</html>