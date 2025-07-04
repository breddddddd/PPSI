<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$nomor = trim($data['nomor_hp'] ?? '');

if (!preg_match('/^(\+62|62|0)?[0-9]{9,15}$/', $nomor)) {
  echo json_encode(['success' => false, 'message' => 'Format nomor HP tidak valid']);
  exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE users SET nomor_hp = ? WHERE id = ?");
$stmt->bind_param("si", $nomor, $user_id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => 'Gagal menyimpan nomor HP']);
}
