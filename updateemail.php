<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$new_email = trim($data['email'] ?? '');

if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
  exit;
}

$user_id = $_SESSION['user_id'];

// Cek apakah email sudah dipakai user lain
$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check->bind_param("si", $new_email, $user_id);
$check->execute();
$check_result = $check->get_result();

if ($check_result->num_rows > 0) {
  echo json_encode(['success' => false, 'message' => 'Email sudah digunakan pengguna lain']);
  exit;
}

// Update email di database
$update = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
$update->bind_param("si", $new_email, $user_id);
$success = $update->execute();

if ($success) {
  $_SESSION['email'] = $new_email;
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => 'Gagal memperbarui email']);
}
