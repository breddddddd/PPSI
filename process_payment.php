<?php
session_start();
include 'config/config.php'; // Pastikan koneksi DB Anda termasuk di sini

if (!isset($_SESSION['user_id']) || !isset($_SESSION['order_details'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_details = $_SESSION['order_details'];
$payment_method = $_POST['payment_method'] ?? 'QRIS'; // Default ke QRIS
$payment_proof_filename = null; // Inisialisasi variabel untuk nama file bukti

// ===============================================
// Bagian Penanganan Unggahan Bukti Pembayaran
// ===============================================
if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "uploads/proofs/"; // Folder tempat menyimpan bukti pembayaran
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Buat folder jika belum ada (izin 0777 sangat permisif, sesuaikan jika Anda mengerti)
    }

    $file_tmp_name = $_FILES['payment_proof']['tmp_name'];
    $file_name = basename($_FILES['payment_proof']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png');

    // Validasi ekstensi file
    if (in_array($file_ext, $allowed_ext)) {
        // Buat nama file unik untuk menghindari tumpang tindih
        $payment_proof_filename = uniqid('proof_', true) . '.' . $file_ext;
        $target_file = $target_dir . $payment_proof_filename;

        // Pindahkan file yang diunggah
        if (!move_uploaded_file($file_tmp_name, $target_file)) {
            $payment_proof_filename = null; // Gagal upload
            error_log("Failed to move uploaded file: " . $file_name);
        }
    } else {
        error_log("Invalid file extension for payment proof: " . $file_ext);
    }
}

// ===============================================
// Bagian Penyimpanan Pesanan ke Database
// ===============================================
// 1. Simpan detail pesanan utama ke tabel 'orders'
// Query ini sudah disesuaikan dengan struktur 'orders' yang Anda berikan sebelumnya
$stmt = $conn->prepare("INSERT INTO orders (user_id, receiver_name, shipping_address, phone_number, total_amount, payment_method, payment_proof_image, status, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
$stmt->bind_param("isssiss",
    $user_id,
    $order_details['receiver_name'],
    $order_details['shipping_address'],
    $order_details['phone_number'],
    $order_details['total_amount'],
    $payment_method,
    $payment_proof_filename
);

if ($stmt->execute()) {
    $order_id = $stmt->insert_id; // Dapatkan ID pesanan yang baru dibuat

    // 2. Simpan item pesanan ke tabel 'order_items'
    foreach ($order_details['items'] as $item) {
        $product_id = !empty($item['product_id']) ? $item['product_id'] : null;
        $item_name = $item['name'];
        $quantity = $item['quantity'];
        $item_price_value = $item['price']; // Mengambil harga satuan dari data keranjang
        $custom_image = $item['custom_image'] ?? null;

        // Sesuaikan query INSERT untuk menggunakan 'item_price'
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, item_name, quantity, item_price, custom_image) VALUES (?, ?, ?, ?, ?, ?)");
        // 'iisdis' -> i: order_id (int), i: product_id (int/null), s: item_name (string), i: quantity (int), d: item_price_value (double/decimal), s: custom_image (string)
        $stmt_item->bind_param("iisdis", $order_id, $product_id, $item_name, $quantity, $item_price_value, $custom_image);
        $stmt_item->execute();
        $stmt_item->close();
    }

    // Hapus detail pesanan dari sesi setelah berhasil disimpan
    unset($_SESSION['order_details']);

    $message = "Pesanan Anda dengan nomor **#" . $order_id . "** telah berhasil dibuat dan bukti pembayaran telah diunggah. Kami akan memverifikasi pembayaran Anda sesegera mungkin.";
    $additional_info = "Tim kami akan segera memeriksa bukti pembayaran Anda. Status pesanan akan diperbarui setelah verifikasi.";

} else {
    $message = "Terjadi kesalahan saat membuat pesanan atau mengunggah bukti pembayaran. Mohon coba lagi.";
    $additional_info = "";
    error_log("Error saving order or payment proof: " . $stmt->error);
}

$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Konfirmasi Pembayaran - Jepit Aku Lucu</title>
  <link rel="stylesheet" href="assets/assets/checkout.css">
  <style>
    .payment-success-container { max-width: 800px; margin: auto; padding: 20px; text-align: center; }
    .payment-success-container h2 { color: #4CAF50; }
    .payment-success-container p { margin-bottom: 10px; }
    .payment-success-container .instructions {
        background: #e9ffe9;
        border: 1px solidrgb(255, 255, 255);
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
        text-align: left;
    }
    .payment-success-container .btn-back {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 20px;
        background-color: #e91e63;
        color: white;
        text-decoration: none;
        border-radius: 5px;
    }
    .payment-success-container .btn-back:hover {
        opacity: 0.9;
    }
  </style>
</head>
<body>

<div class="payment-success-container">
  <h2>Konfirmasi Pembayaran Anda Telah Diterima!</h2>
  <p><?= $message ?></p>

  <?php if (!empty($additional_info)): ?>
    <div class="instructions">
      <h3>Informasi Tambahan:</h3>
      <p><?= $additional_info ?></p>
      <p>Anda dapat memantau status pesanan Anda di halaman "Pesanan Saya".</p>
    </div>
  <?php endif; ?>

  <a href="home.php" class="btn-back">Kembali ke Beranda</a>
</div>

</body>
</html>