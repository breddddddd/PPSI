<?php
session_start();
include 'config/config.php';

$data = json_decode(file_get_contents("php://input"));
$credential = $data->credential ?? '';

if (!$credential) {
  echo json_encode(['success' => false, 'error' => 'No credential']);
  exit;
}

// Decode JWT token dari Google
$parts = explode('.', $credential);
$payload = json_decode(base64_decode($parts[1]), true);

// Ambil data dari payload
$email = $payload['email'];
$name = $payload['name'];

// Fallback foto profil ke gravatar jika 'picture' tidak tersedia
$gravatar = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?s=100&d=identicon";
$photo = $payload['picture'] ?? $gravatar;

// Cek user di DB
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
  $user = $result->fetch_assoc();
  $user_id = $user['id'];

  // Update foto profil di DB hanya jika kosong atau berbeda
  if (empty($user['photo_url']) || $user['photo_url'] !== $photo) {
    $update = $conn->prepare("UPDATE users SET photo_url = ? WHERE id = ?");
    $update->bind_param("si", $photo, $user_id);
    $update->execute();
  }

} else {
  // Tambahkan user baru
  $insert = $conn->prepare("INSERT INTO users (username, email, photo_url) VALUES (?, ?, ?)");
  $insert->bind_param("sss", $name, $email, $photo);
  $insert->execute();
  $user_id = $insert->insert_id;
}

// Set session
$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = $name;
$_SESSION['email'] = $email;
$_SESSION['photo_url'] = $photo;
$_SESSION['is_google_login'] = true;

echo json_encode(['success' => true]);
