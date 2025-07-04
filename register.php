<?php
include 'config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST['name'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $confirm = $_POST['confirm_password'];
  $user_type = $_POST['user_type'];

  if ($password !== $confirm) {
    $error = "Konfirmasi kata sandi tidak cocok!";
  } else {
    // Cek apakah email sudah terdaftar
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
      $error = "Email sudah terdaftar!";
    } else {
      // Enkripsi password
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $photo_url = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?s=100&d=identicon";

      // Simpan ke database
      $query = "INSERT INTO users (username, email, password, photo_url, user_type) VALUES (?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("sssss", $username, $email, $hashed_password, $photo_url, $user_type);

      if ($stmt->execute()) {
        header("Location: login.php");
        exit();
      } else {
        $error = "Registrasi gagal: " . $stmt->error;
      }
    }
  }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daftar</title>
  <link rel="stylesheet" href="assets/assets/register.css" />
</head>
<style>
  .input-group select {
  width: 100%;
  padding: 10px 12px;
  font-size: 16px;
  border: 1px solid #ccc;
  border-radius: 5px;
  background-color: white;
  color: #333;
  appearance: none; /* Hilangkan gaya default browser */
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M4.646 6.146a.5.5 0 0 1 .708 0L8 8.793l2.646-2.647a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 16px;
  }
  .input-group select:focus {
    border-color: pink;
    outline: none;
    box-shadow: pink;
  }
</style>
<body>
  <div class="register-container">
    <form class="register-form" method="POST" action="">
      <img src="assets/assets/image/logo.png" alt="Logo" class="logo" />
      <h2>Buat Akun Baru</h2>

      <?php if (isset($error)) : ?>
        <p style="color: red; text-align: center;"><?= $error; ?></p>
      <?php endif; ?>

      <div class="input-group">
        <label for="name">Nama Lengkap</label>
        <input type="text" id="name" name="name" placeholder="Masukkan nama lengkap" required />
      </div>
      <div class="input-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Masukkan email" required />
      </div>
      <div class="input-group">
        <label for="password">Kata Sandi</label>
        <input type="password" id="password" name="password" placeholder="Buat kata sandi" required />
      </div>
      <div class="input-group">
        <label for="confirm-password">Konfirmasi Kata Sandi</label>
        <input type="password" id="confirm-password" name="confirm_password" placeholder="Ulangi kata sandi" required />
      </div>
      <button type="submit">Daftar</button>
      <p class="login-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
    </form>
  </div>
</body>
</html>
