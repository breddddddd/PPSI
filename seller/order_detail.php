<?php
session_start();
include '../config/config.php'; // Pastikan path ini benar

// Periksa apakah user_id session ada dan order_id diberikan melalui GET
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
  header("Location: orders.php"); // Redirect ke halaman daftar pesanan
  exit();
}

$order_id = $_GET['id'];
$seller_id = $_SESSION['user_id']; // ID penjual yang sedang login

// Langkah 1: Ambil detail pesanan utama dari tabel 'orders'.
// Pastikan pesanan ini relevan untuk penjual yang sedang login (memiliki produk dari penjual ini).
// Kita harus bergabung dengan order_items dan products untuk verifikasi seller_id.
$query_order_main = "
  SELECT
      o.id,
      o.order_date,
      o.total_amount,
      o.status,
      o.receiver_name,
      o.shipping_address,
      o.phone_number,
      o.payment_proof_image, -- Menambahkan kolom bukti pembayaran
      u.username AS buyer_name,
      u.email AS buyer_email,
      u.nomor_hp AS buyer_nomor_hp
  FROM orders o
  JOIN users u ON o.user_id = u.id
  WHERE o.id = ?
  AND EXISTS ( -- Memastikan ada setidaknya satu item di pesanan ini yang merupakan produk seller atau produk kustom
    SELECT 1
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = o.id
    AND (p.seller_id = ? OR oi.product_id IS NULL) -- Tambahkan kondisi untuk produk kustom
)
";

$stmt_order_main = $conn->prepare($query_order_main);
$stmt_order_main->bind_param("ii", $order_id, $seller_id);
$stmt_order_main->execute();
$result_order_main = $stmt_order_main->get_result();
$order = $result_order_main->fetch_assoc();
$stmt_order_main->close();

// Jika pesanan tidak ditemukan atau tidak relevan untuk penjual ini
if (!$order) {
  echo "Pesanan tidak ditemukan atau Anda tidak memiliki akses ke pesanan ini.";
  exit();
}

// Langkah 2: Ambil semua item yang terkait dengan pesanan ini dari tabel 'order_items'.
// Kita hanya akan mengambil item yang relevan dengan penjual yang login
// (jika sebuah pesanan punya item dari seller lain, item itu tidak akan tampil di detail seller ini)
$order_items = [];
$query_items = "
    SELECT
        oi.item_name,
        oi.item_price,
        oi.quantity,
        oi.custom_image,
        p.name AS product_original_name,
        p.image AS product_image_path
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    AND (p.seller_id = ? OR oi.product_id IS NULL OR oi.product_id = 0) -- Termasuk produk kustom (product_id IS NULL atau 0)
";
$stmt_items = $conn->prepare($query_items);
$stmt_items->bind_param("ii", $order_id, $seller_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
while ($item_row = $result_items->fetch_assoc()) {
    $order_items[] = $item_row;
}
$stmt_items->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Pesanan #<?= htmlspecialchars($order['id']) ?></title>
  <link rel="stylesheet" href="../assets/assets/seller.css">
  <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 20px;
    }
    .detail-container {
      max-width: 700px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(233, 30, 99, 0.1);
      color: #333;
    }
    .detail-container h3 {
      color: #e91e63;
      text-align: center;
      margin-bottom: 25px;
      font-size: 1.8em;
    }
    .detail-section {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    .detail-section:last-child {
        border-bottom: none;
        padding-bottom: 0;
        margin-bottom: 0;
    }
    .detail-section p {
      margin: 8px 0;
      font-size: 1.05em;
    }
    .detail-section p strong {
        color: #555;
    }
    .product-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding: 10px;
        background-color: #f9f9f9;
        border-radius: 8px;
        border: 1px solid #eee;
    }
    .product-item img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      margin-right: 15px;
      border-radius: 5px;
      border: 1px solid #ddd;
    }
    .product-info {
        flex-grow: 1;
    }
    .product-info h4 {
        margin: 0 0 5px 0;
        color: #e91e63;
        font-size: 1.1em;
    }
    .product-info p {
        margin: 2px 0;
        font-size: 0.95em;
        color: #666;
    }
    .payment-proof-img {
        max-width: 100%; /* Agar gambar tidak melebihi lebar kontainer */
        height: auto; /* Mempertahankan rasio aspek */
        display: block; /* Agar tidak ada spasi di bawah gambar */
        margin: 10px auto; /* Pusatkan gambar */
        border: 1px solid #ddd;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .back-link {
        display: block;
        text-align: center;
        margin-top: 30px;
        color: #007bff;
        text-decoration: none;
        font-weight: bold;
        transition: color 0.3s ease;
    }
    .back-link:hover {
        color: #0056b3;
    }
  </style>
</head>
<body>
  <div class="detail-container">
    <h3>Detail Pesanan #<?= htmlspecialchars($order['id']) ?></h3>

    <div class="detail-section">
        <p><strong>Status Pesanan:</strong> <span style="font-weight: bold; color: <?php
            // Menyesuaikan warna berdasarkan status
            $status_color = '';
            switch (strtolower($order['status'])) {
                case 'pending': $status_color = '#ffc107'; break; // Kuning
                case 'diproses': $status_color = '#fd7e14'; break; // Oranye
                case 'dikirim': $status_color = '#007bff'; break; // Biru
                case 'selesai': $status_color = '#28a745'; break; // Hijau
                case 'dibatalkan': $status_color = '#dc3545'; break; // Merah
                default: $status_color = '#333'; break;
            }
            echo $status_color;
        ?>;"><?= ucfirst(htmlspecialchars($order['status'])) ?></span></p>
        <p><strong>Total Pesanan:</strong> Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></p>
        <p><strong>Tanggal Pesanan:</strong> <?= date('d M Y H:i', strtotime($order['order_date'])) ?></p>
    </div>

    <div class="detail-section">
        <h4>Detail Pembeli:</h4>
        <p><strong>Nama Pembeli:</strong> <?= htmlspecialchars($order['buyer_name']) ?></p>
        <p><strong>Email Pembeli:</strong> <?= htmlspecialchars($order['buyer_email']) ?></p>
        <p><strong>Telepon Pembeli:</strong> <?= htmlspecialchars($order['buyer_nomor_hp']) ?></p>
    </div>

    <div class="detail-section">
        <h4>Detail Pengiriman:</h4>
        <p><strong>Nama Penerima:</strong> <?= htmlspecialchars($order['receiver_name']) ?></p>
        <p><strong>Alamat Pengiriman:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
        <p><strong>Nomor Telepon Penerima:</strong> <?= htmlspecialchars($order['phone_number']) ?></p>
    </div>

    <div class="detail-section">
        <h4>Bukti Pembayaran:</h4>
        <?php if (!empty($order['payment_proof_image'])): ?>
            <?php
            // Ini adalah bagian KRUSIAL yang diubah
            // Membuat path lengkap ke gambar.
            // Asumsi: $order['payment_proof_image'] hanya berisi 'nama_file.jpg'
            // dan gambar ada di 'C:\xampp\htdocs\PPSI\uploads\proofs\'
            $image_file_name = htmlspecialchars($order['payment_proof_image']);
            $full_image_path_for_html = '../uploads/proofs/' . $image_file_name;
            ?>
            <p>Klik gambar untuk melihat ukuran penuh:</p>
            <a href="<?= $full_image_path_for_html ?>" target="_blank">
                <img src="<?= $full_image_path_for_html ?>" alt="Bukti Pembayaran" class="payment-proof-img">
            </a>
        <?php else: ?>
            <p>Tidak ada bukti pembayaran diunggah.</p>
        <?php endif; ?>
    </div>

    <div class="detail-section">
        <h4>Item Pesanan:</h4>
        <?php if (!empty($order_items)): ?>
            <?php foreach ($order_items as $item): ?>
                <div class="product-item">
                    <?php if (!empty($item['custom_image'])): ?>
                        <img src="../<?= htmlspecialchars($item['custom_image']) ?>" alt="Custom Image">
                    <?php elseif (!empty($item['product_image_path'])): ?>
                        <img src="../<?= htmlspecialchars($item['product_image_path']) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                    <?php else: ?>
                        <img src="../assets/assets/image/placeholder.jpg" alt="No Image"> <?php endif; ?>
                    <div class="product-info">
                        <h4><?= htmlspecialchars($item['item_name']) ?></h4>
                        <p>Jumlah: <?= htmlspecialchars($item['quantity']) ?></p>
                        <p>Harga Satuan: Rp <?= number_format($item['item_price'], 0, ',', '.') ?></p>
                        <p>Subtotal: Rp <?= number_format($item['quantity'] * $item['item_price'], 0, ',', '.') ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Tidak ada item ditemukan untuk pesanan ini.</p>
        <?php endif; ?>
    </div>

    <a href="orders.php" class="back-link">⬅️ Kembali ke Daftar Pesanan</a>
  </div>
</body>
</html>