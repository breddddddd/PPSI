<?php
session_start();
include 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

// Ambil data
$data = json_decode(file_get_contents('php://input'), true);
$new_username = trim($data['username'] ?? '');

if (!$new_username) {
  echo json_encode(['success' => false, 'message' => 'Nama tidak boleh kosong']);
  exit;
}

$user_id = $_SESSION['user_id'];

// Update database
$stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
$stmt->bind_param("si", $new_username, $user_id);
$success = $stmt->execute();

if ($success) {
  $_SESSION['username'] = $new_username; // update session juga
  echo json_encode(['success' => true, 'message' => 'Nama berhasil diperbarui']);
} else {
  echo json_encode(['success' => false, 'message' => 'Gagal menyimpan perubahan']);
}
?>
