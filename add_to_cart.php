<?php
session_start();
include 'config/config.php'; // koneksi ke DB

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Anda belum login.']);
  exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];
$quantity = $_POST['quantity'];

// Cek apakah produk sudah ada di cart
$stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  // Update kuantitas
  $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
  $stmt->bind_param("iii", $quantity, $user_id, $product_id);
} else {
  // Tambahkan item baru ke cart
  $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
  $stmt->bind_param("iii", $user_id, $product_id, $quantity);
}

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => 'Gagal menambahkan ke keranjang.']);
}
?>