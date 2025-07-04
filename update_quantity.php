<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cart_id = $_POST['cart_id'];
  $quantity = max(1, (int) $_POST['quantity']); // minimal 1
  $user_id = $_SESSION['user_id'];

  $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
  $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
  $stmt->execute();

  echo json_encode(['status' => 'success']);
}
?>
