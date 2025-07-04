<?php
session_start();
include '../config/config.php'; // Pastikan path ini benar untuk koneksi database

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'penjual') {
    // Redirect jika user belum login atau bukan penjual
    header("Location: ../login.php");
    exit();
}

$seller_id = $_SESSION['user_id'];

// Ambil semua pesanan yang berisi produk yang dijual oleh seller ini
// Kita akan mengambil pesanan utama, lalu mengambil item-itemnya secara terpisah.
$orders_for_seller = [];

// Langkah 1: Dapatkan daftar order_id yang relevan untuk seller ini
// Ini adalah order_id dari pesanan yang memiliki setidaknya satu item produk milik seller_id ini.
$stmt_relevant_order_ids = $conn->prepare("
    SELECT DISTINCT oi.order_id
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ? OR oi.product_id IS NULL AND ? IS NOT NULL
");
$stmt_relevant_order_ids->bind_param("ii", $seller_id, $seller_id);
$stmt_relevant_order_ids->execute();
$result_relevant_order_ids = $stmt_relevant_order_ids->get_result();

$relevant_order_ids = [];
while ($row_id = $result_relevant_order_ids->fetch_assoc()) {
    $relevant_order_ids[] = $row_id['order_id'];
}
$stmt_relevant_order_ids->close();

if (!empty($relevant_order_ids)) {
    $in_clause = implode(',', $relevant_order_ids);

    $query_orders = "
        SELECT o.id, o.order_date, o.total_amount, o.status,
               o.receiver_name, o.shipping_address, o.phone_number,
               u.username AS buyer_username
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id IN ($in_clause)
        ORDER BY o.order_date DESC
    ";
    $stmt_orders = $conn->prepare($query_orders);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();

    while ($order_row = $result_orders->fetch_assoc()) {
        $order_id = $order_row['id'];
        $order_items = [];

        $query_items = "
            SELECT oi.id, oi.item_name, oi.item_price, oi.quantity, oi.custom_image, p.name AS product_original_name
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            AND (p.seller_id = ? OR oi.product_id IS NULL)
        ";
        $stmt_items = $conn->prepare($query_items);
        $stmt_items->bind_param("ii", $order_id, $seller_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        while ($item_row = $result_items->fetch_assoc()) {
            $order_items[] = $item_row;
        }
        $stmt_items->close();

        if (!empty($order_items)) {
            $order_row['items'] = $order_items;
            $orders_for_seller[] = $order_row;
        }
    }
    $stmt_orders->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pesanan Masuk</title>
  <link rel="stylesheet" href="../assets/assets/seller.css">
  <link rel="stylesheet" href="../assets/assets/orders.css">
  <style>
    .cancel-button {
      background-color: #f44336; /* Merah */
      color: white;
      padding: 8px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9em;
      margin-left: 10px;
    }
    .cancel-button:hover {
      background-color: #d32f2f;
    }
    .order-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
    }
    .status-form {
        display: flex;
        align-items: center;
    }
    .status-form select {
        margin-left: 10px;
        padding: 6px;
        border-radius: 4px;
        border: 1px solid #ccc;
    }
  </style>
</head>
<body>
  <div class="seller-container">
    <h2>🛒 Pesanan Masuk</h2>

    <?php if (!empty($orders_for_seller)): ?>
      <?php foreach ($orders_for_seller as $order): ?>
        <div class="order-card">
          <h4>Pesanan ID: <?= htmlspecialchars($order['id']) ?></h4>
          <p>Pembeli: <?= htmlspecialchars($order['buyer_username']) ?></p>
          <p>Total Pesanan: Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></p>
          <p>Tanggal Pesanan: <?= date('d-m-Y H:i', strtotime($order['order_date'])) ?></p>
          <p>Status: <strong style="color: <?php
              if ($order['status'] == 'pending') echo 'orange';
              else if ($order['status'] == 'diproses') echo 'blue';
              else if ($order['status'] == 'dikirim') echo 'purple';
              else if ($order['status'] == 'selesai') echo 'green';
              else if ($order['status'] == 'dibatalkan') echo 'red'; // Akan ditampilkan jika tidak dihapus
              else echo 'black';
          ?>;"><?= htmlspecialchars(ucfirst($order['status'])) ?></strong></p>


          <div class="shipping-info">
            <strong>Detail Pengiriman:</strong><br>
            Nama Penerima: <?= htmlspecialchars($order['receiver_name']) ?><br>
            Alamat: <?= htmlspecialchars($order['shipping_address']) ?><br>
            Telepon: <?= htmlspecialchars($order['phone_number']) ?>
          </div>

          <?php if (!empty($order['items'])): ?>
            <div class="order-items-list">
              <h5>Item dalam Pesanan ini:</h5>
              <ul>
                <?php foreach ($order['items'] as $item): ?>
                  <li>
                    <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                    <span><?= $item['quantity'] ?> x Rp<?= number_format($item['item_price'], 0, ',', '.') ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <div class="order-actions">
            <form action="update_status.php" method="POST" class="status-form">
              <a href="order_detail.php?id=<?= $order['id'] ?>" class="detail-link">🔍 Lihat Detail</a>
              <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
              <select name="status" onchange="this.form.submit()"
                      <?= ($order['status'] == 'selesai' || $order['status'] == 'dibatalkan') ? 'disabled' : '' ?>>
                  <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="diproses" <?= $order['status'] == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                  <option value="dikirim" <?= $order['status'] == 'dikirim' ? 'selected' : '' ?>>Dikirim</option>
                  <option value="selesai" <?= $order['status'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                  <?php if ($order['status'] == 'dibatalkan'): ?>
                      <option value="dibatalkan" selected>Dibatalkan</option>
                  <?php endif; ?>
              </select>
            </form>

            <?php if ($order['status'] == 'pending'): ?>
                <form action="update_status.php" method="POST" style="display: inline-block;">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="action_type" value="delete_order"> <button type="submit" class="cancel-button" onclick="return confirm('PERINGATAN: Pesanan ini akan dihapus permanen. Apakah Anda yakin ingin membatalkan dan menghapus pesanan ini?');">Batalkan Pesanan</button>
                </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="no-orders">Belum ada pesanan masuk yang relevan dengan produk Anda.</p>
    <?php endif; ?>
  </div>
</body>
</html>