<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Pastikan data POST diterima dari checkout.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari POST, simpan ke sesi
    $_SESSION['order_details'] = [
        'user_id' => $_SESSION['user_id'],
        'receiver_name' => $_POST['receiver_name'],
        'shipping_address' => $_POST['shipping_address'],
        'phone_number' => $_POST['phone_number'],
        'total_amount' => $_POST['total_amount'],
        'items' => $_POST['items']
    ];
    $total_amount = $_POST['total_amount']; // Ambil total_amount untuk tampilan
} elseif (isset($_SESSION['order_details'])) {
    // Jika sudah ada di sesi (misal, refresh halaman), ambil dari sesi
    $total_amount = $_SESSION['order_details']['total_amount'];
} else {
    // Jika diakses langsung tanpa POST atau sesi, redirect
    header("Location: checkout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pembayaran QRIS - Jepit Aku Lucu</title>
  <link rel="stylesheet" href="assets/assets/checkout.css">
  <style>
    .payment-container { max-width: 600px; margin: auto; padding: 20px; text-align: center; }
    .qris-section {
      margin-top: 30px;
      border: 1px solid #ddd;
      padding: 20px;
      border-radius: 8px;
      background-color: #f9f9f9;
    }
    .qris-section img {
      max-width: 80%; /* Sesuaikan ukuran QRIS */
      height: auto;
      margin-bottom: 20px;
      border: 1px solid #eee;
      padding: 5px;
    }
    .total-display {
        font-size: 1.5em;
        margin-bottom: 20px;
        font-weight: bold;
        color: #e91e63;
    }
    .upload-section {
      margin-top: 30px;
      padding: 20px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: #fff;
    }
    .upload-section input[type="file"] {
      margin-bottom: 15px;
      border: 1px solid #ccc;
      padding: 8px;
      border-radius: 4px;
      width: 100%;
      box-sizing: border-box;
    }
    .upload-section button {
      padding: 12px 25px;
      background-color: pink; /* Warna hijau untuk tombol upload */
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1.1em;
      width: 100%;
    }
    .upload-section button:hover {
      opacity: 0.9;
    }
    .info-text {
        font-size: 0.9em;
        color: #555;
        margin-top: 10px;
    }
  </style>
</head>
<body>

<div class="payment-container">
  <h2>Pembayaran dengan QRIS</h2>

  <div class="total-display">
    Total Pembayaran: Rp<?= number_format($total_amount ?? 0, 0, ',', '.') ?>
  </div>

  <div class="qris-section">
    <h3>Scan QRIS Ini untuk Pembayaran</h3>
    <img src="assets/assets/image/QRIS.jpg" alt="QRIS Code">
    <p class="info-text">Pastikan jumlah yang dibayarkan sesuai dengan total belanja Anda.</p>
  </div>

  <div class="upload-section">
    <h3>Unggah Bukti Pembayaran</h3>
    <p>Setelah melakukan pembayaran, mohon unggah bukti transfer/pembayaran Anda di sini.</p>
    <form action="process_payment.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="payment_method" value="QRIS">
      <input type="file" name="payment_proof" accept="image/*" required>
      <button type="submit">Konfirmasi Pembayaran</button>
      <p class="info-text">Format file yang diizinkan: JPG, JPEG, PNG. Ukuran maksimal 2MB.</p>
    </form>
  </div>
</div>

</body>
</html>