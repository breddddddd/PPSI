<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lupa Kata Sandi</title>
  <link rel="stylesheet" href="assets/assets/forgotpassword.css" />
</head>
<body>
  <div class="forgot-container">
  <form class="forgot-form" action="request_reset.php" method="POST">
      <img src="assets/assets/image/logo.png" alt="Logo" class="logo" />
      <h2>Lupa Kata Sandi?</h2>
      <p class="subtitle">Masukkan email yang terkait akun Anda. Kami akan mengirimkan tautan untuk mereset kata sandi.</p>
      <div class="input-group">
        <label for="email">Email</label>
        <input type="email" id="email" placeholder="Masukkan email Anda" required />
      </div>
      <button type="submit">Kirim Tautan Reset</button>
      <p class="back-link">Ingat kata sandi? <a href="login.php">Kembali ke Login</a></p>
    </form>
  </div>

  <!-- Pop-up -->
  <div class="popup-overlay" id="popup">
    <div class="popup-box">
      <img src="assets/assets/image/logo.png" alt="Logo" class="logo" />
      <h2>Tautan Reset Terkirim</h2>
      <p>Kami telah mengirimkan tautan ke email Anda.</p>
      <button onclick="closePopup()">Tutup</button>
    </div>
  </div>

  <script>
    function showPopup(event) {
      event.preventDefault(); // Mencegah form submit
      document.getElementById("popup").style.display = "flex";
    }

    function closePopup() {
      document.getElementById("popup").style.display = "none";
    }
  </script>
</body>
</html>
