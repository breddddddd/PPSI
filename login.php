<?php
include 'config/config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST['email'];
  $password = $_POST['password'];

  // --- UBAH BARIS INI: Tambahkan user_type ke SELECT ---
  $stmt = $conn->prepare("SELECT id, username, email, password, user_type, is_google_login, photo_url FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['email'] = $user['email'];
      $_SESSION['is_google_login'] = false; // Tandai bahwa ini login manual

      // Gunakan Gravatar jika photo_url kosong
      $gravatar = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?s=100&d=identicon";
      $_SESSION['photo_url'] = !empty($user['photo_url']) ? $user['photo_url'] : $gravatar;

      // --- TAMBAHKAN BARIS INI ---
      $_SESSION['user_type'] = $user['user_type'];

      header("Location: home.php");
      exit();
    } else {
      $error = "Kata sandi salah!";
    }
  } else {
    $error = "Email tidak ditemukan!";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link rel="stylesheet" href="assets/assets/login.css" />
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
  <div class="login-container">
    <form class="login-form" method="POST" action="">
      <img src="assets/assets/image/logo.png" alt="Logo" class="logo" />
      <h2>Masuk ke Akun Anda</h2>

      <?php if (isset($error)) : ?>
        <p style="color: red; text-align: center;"><?= $error; ?></p>
      <?php endif; ?>

      <div class="input-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="Masukkan email" required />
      </div>
      <div class="input-group">
        <label for="password">Kata Sandi</label>
        <input type="password" name="password" id="password" placeholder="Masukkan kata sandi" required />
      </div>
      <button type="submit">Login</button>
      <p class="register-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
      <div class="forgot-link">
        <a href="forgotpassword.php">Forget Password?</a>
      </div>

    </form>

    <div class="google-login-wrapper">
      <div id="g_id_onload"
        data-client_id="92164992994-clfbam385nj5dkorqvmnd571t6re4bip.apps.googleusercontent.com"
        data-callback="handleCredentialResponse"
        data-auto_prompt="false">
      </div>

    <div class="g_id_signin"
        data-type="standard"
        data-shape="rectangular"
        data-theme="outline"
        data-text="sign_in_with"
        data-size="large"
        data-logo_alignment="left">
    </div>
  </div>
  </div>

  <script>
    function handleCredentialResponse(response) {
      fetch('googleloginhandler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ credential: response.credential })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          window.location.href = "home.php";
        } else {
          alert("Login Google gagal.");
        }
      });
    }
  </script>
</body>
</html>