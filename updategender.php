<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$gender = $data['gender'] ?? '';

if (!in_array($gender, ['Pria', 'Wanita'])) {
  echo json_encode(['success' => false, 'message' => 'Pilihan jenis kelamin tidak valid']);
  exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE users SET jenis_kelamin = ? WHERE id = ?");
$stmt->bind_param("si", $gender, $user_id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => 'Gagal menyimpan jenis kelamin']);
}
