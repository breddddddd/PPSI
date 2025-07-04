<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$tanggal_lahir = $data['tanggal_lahir'] ?? '';

if (!$tanggal_lahir || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_lahir)) {
  echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid']);
  exit;
}

$user_id = $_SESSION['user_id'];

// Simpan ke database
$stmt = $conn->prepare("UPDATE users SET tanggal_lahir = ? WHERE id = ?");
$stmt->bind_param("si", $tanggal_lahir, $user_id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => 'Gagal menyimpan tanggal lahir']);
}
