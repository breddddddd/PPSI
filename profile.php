<?php
include 'config/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Ambil data lengkap user dari DB
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil Pengguna</title>
  <link rel="stylesheet" href="assets/assets/style.css" />
  <link rel="stylesheet" href="assets/assets/profile.css">
</head>
<body>
  <header>
    <a href="home.php" class="logo" onclick="window.location.reload();">
      <img src="assets/assets/image/logo.png" alt="Logo" />
    </a>
    <ul class="navmenu">
      <li><a href="Home.php">home</a></li>
      <li><a href="products.php">Products</a></li>
      <li><a href="Favorite.php">Favorite</a></li>
        </ul>
      </li>              
    </ul>

    <div class="search-box" id="searchBox">
      <input type="text" id="searchInput" placeholder="Cari produk di sini..." onkeyup="searchProduct()" onkeypress="checkEnter(event)" />
      <button type="button" onclick="closeSearch()">&times;</button>
    </div>

    <div class="nav-icon">
      <a href="#"><i class='bx bx-search'></i></a>
      <a href="profile.php"><i class='bx bx-user' ></i></a>
      <a href="Chart.php"><i class='bx bx-cart' ></i></a>
      <div class="bx bx-menu" id="menu-icon"></div>
    </div>
  </header>

  <div class="container">
    <aside class="sidebar">
        <div class="profile-pic">
        <img id="profile-avatar" src="<?php echo $_SESSION['photo_url'] ?? 'assets/assets/image/default.png'; ?>" alt="Foto Profil">
        <h2><?= htmlspecialchars($_SESSION['username'] ?? 'Pengguna'); ?></h2>
        </div>
    </aside>

    <main class="content">
      <h1>Profil Pengguna</h1>
        <div class="profile-section">
        <div class="avatar-box">
            <img src="<?= $_SESSION['photo_url'] ?? 'assets/assets/image/default.png' ?>" alt="Foto Profil">
        </div>
        <div class="bio-box">
            <h2>Biodata Diri</h2>
            <p><strong>Nama:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'Pengguna'); ?> 
            <a href="#" onclick="openPopup('Ubah Nama', 'Nama', '<?= htmlspecialchars($_SESSION['username'] ?? '') ?>', 'Nama akan terlihat oleh pengguna lain')">Ubah</a></p>
            <p><strong>Tanggal Lahir:</strong> <?= htmlspecialchars($user['tanggal_lahir'] ?? 'Belum diatur') ?><a href="#" onclick="openDatePopup()">Ubah</a></p>
            <p><strong>Jenis Kelamin:</strong> <?= htmlspecialchars($user['jenis_kelamin'] ?? 'Belum diatur') ?> <a href="#" onclick="openGenderPopup()">Ubah</a></p>
            <h2>Kontak</h2>
            <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['email'] ?? '') ?><a href="#" onclick="openPopup('Ubah Email', 'Email', '<?= htmlspecialchars($_SESSION['email'] ?? '') ?>', 'Pastikan email aktif')">Ubah</a></p>
            <p><strong>No HP:</strong> <?= htmlspecialchars($user['nomor_hp'] ?? 'Belum diatur') ?><span class="verified">Terverifikasi</span><a href="#" onclick="openPopup('Ubah Nomor HP', 'No HP', '<?= htmlspecialchars($user['nomor_hp'] ?? '') ?>', 'Gunakan format internasional')">Ubah</a></p>
            <!-- Tombol untuk memicu popup -->
            <button type="button" class="btn-outline" onclick="openLogoutPopup()">Logout</button>
            <!-- Popup Konfirmasi Logout -->
            <div class="popup-overlay" id="logoutPopup">
              <div class="popup-box logout">
                <span class="close-btn" onclick="closeLogoutPopup()">×</span>
                <h2>Konfirmasi Logout</h2>
                <p>Kamu yakin ingin keluar dari akun ini?</p>
                <form action="logout.php" method="POST">
                  <button type="submit" class="save-btn">Ya, Logout</button>
                </form>
              </div>
            </div>
        </div>
        </div>
    </main>
  </div>

  <!-- Popup overlay untuk modular input -->
  <div class="popup-overlay" id="popupOverlay" onclick="closePopup(event)">
    <div class="popup-box">
      <span class="close-btn" onclick="closePopup(event)">×</span>
      <h2 id="popupTitle">Edit</h2>
      <p id="popupNote">Silakan isi data di bawah ini.</p>
      <label id="popupLabel" for="popupInput">Input</label>
      <input type="text" id="popupInput" placeholder="Masukkan data...">
      <small id="popupHelper">Data ini akan digunakan untuk keperluan akun Anda</small>
      <button class="save-btn" onclick="submitPopup()">Simpan</button>
    </div>
  </div>

  <!-- Popup Tanggal Lahir -->
  <div class="popup-overlay" id="popupDate" onclick="closePopup(event)">
    <div class="popup-box" onclick="event.stopPropagation()">
      <span class="close-btn" onclick="closePopup(event)">×</span>
      <h2>Tambah Tanggal Lahir</h2>
      <p>Kamu hanya dapat mengatur tanggal lahir satu kali. Pastikan tanggal lahir sudah benar.</p>
      <div class="date-select-group">
        <select id="tanggal"><option value="">Tanggal</option></select>
        <select id="bulan"><option value="">Bulan</option></select>
        <select id="tahun"><option value="">Tahun</option></select>
      </div>
      <button class="save-btn" onclick="submitTanggalLahir()">Simpan</button>
    </div>
  </div>

  <!-- Popup Jenis Kelamin -->
  <div class="popup-overlay" id="popupGender" onclick="closePopup(event)">
    <div class="popup-box" onclick="event.stopPropagation()">
      <span class="close-btn" onclick="closePopup(event)">×</span>
      <h2>Tambah Jenis Kelamin</h2>
      <p>Kamu hanya dapat mengubah data jenis kelamin 1 kali lagi. Pastikan data sudah benar</p>
      <div class="gender-options">
        <label class="gender-option">
          <input type="radio" name="gender" value="Pria">
          <div class="icon">
            <img src="https://img.icons8.com/ios-filled/50/000000/user-male-circle.png"/>
          </div>
          <span>Pria</span>
        </label>
        <label class="gender-option">
          <input type="radio" name="gender" value="Wanita">
          <div class="icon">
            <img src="https://img.icons8.com/ios-filled/50/000000/user-female-circle.png"/>
          </div>
          <span>Wanita</span>
        </label>
      </div>
      <button class="save-btn" onclick="submitGender()">Simpan</button>
    </div>
  </div>

  <script>
    function openPopup(title, label, value, helper) {
      document.getElementById("popupTitle").innerText = title;
      document.getElementById("popupLabel").innerText = label;
      document.getElementById("popupInput").placeholder = value || '';
      document.getElementById("popupInput").value = value || '';
      document.getElementById("popupHelper").innerText = helper || '';
      document.getElementById("popupOverlay").style.display = "flex";
    }

    function closePopup(event) {
      const isCloseBtn = event.target.classList.contains("close-btn");
      const isOverlay = event.target.classList.contains("popup-overlay") ||
                        event.target.id === "popupOverlay" ||
                        event.target.id === "popupDate" ||
                        event.target.id === "popupGender";

      if (isCloseBtn || isOverlay) {
        document.getElementById("popupOverlay").style.display = "none";
        document.getElementById("popupDate").style.display = "none";
        document.getElementById("popupGender").style.display = "none";
      }
    }

    function submitPopup() {
      const inputVal = document.getElementById("popupInput").value;
      const label = document.getElementById("popupLabel").innerText;

      let endpoint = "";
      let payload = {};

      if (label === "Nama") {
        endpoint = "updateusername.php";
        payload = { username: inputVal };
      } else if (label === "Email") {
        endpoint = "updateemail.php";
        payload = { email: inputVal };
      } else if (label === "No HP") {
        endpoint = "updatenohp.php";
        payload = { nomor_hp: inputVal };
      }else {
        alert("Data disimpan: " + inputVal);
        document.getElementById("popupOverlay").style.display = "none";
        return;
      }

      fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast(`${label} berhasil diperbarui`);
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          alert("Gagal: " + data.message);
        }
      })
      .catch(() => alert("Terjadi kesalahan saat menyimpan"));

      document.getElementById("popupOverlay").style.display = "none";
    }


    function showToast(message = "Nama berhasil diperbarui") {
      const toast = document.getElementById("successToast");
      toast.querySelector("p").innerText = message;
      toast.classList.add("show");

      setTimeout(() => {
        toast.classList.remove("show");
      }, 3000);
    }


    function openDatePopup() {
      document.getElementById("popupDate").style.display = "flex";
      generateTanggalLahirDropdown();
    }

    function generateTanggalLahirDropdown() {
      const tanggal = document.getElementById("tanggal");
      const bulan = document.getElementById("bulan");
      const tahun = document.getElementById("tahun");

      if (tanggal.options.length === 1) {
        for (let i = 1; i <= 31; i++) {
          tanggal.innerHTML += `<option value="${i}">${i}</option>`;
        }
      }

      if (bulan.options.length === 1) {
        const namaBulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni",
                           "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        for (let i = 0; i < namaBulan.length; i++) {
          bulan.innerHTML += `<option value="${i + 1}">${namaBulan[i]}</option>`;
        }
      }

      if (tahun.options.length === 1) {
        const now = new Date().getFullYear();
        for (let i = now; i >= 1950; i--) {
          tahun.innerHTML += `<option value="${i}">${i}</option>`;
        }
      }
    }

    function submitTanggalLahir() {
      const t = document.getElementById("tanggal").value;
      const b = document.getElementById("bulan").value;
      const y = document.getElementById("tahun").value;

      if (!t || !b || !y) {
        alert("Silakan lengkapi semua bagian tanggal lahir.");
        return;
      }

      const formatted = `${y}-${b.padStart(2, '0')}-${t.padStart(2, '0')}`;

      fetch("updatetanggallahir.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ tanggal_lahir: formatted })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast("Tanggal lahir berhasil diperbarui");
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          alert("Gagal: " + data.message);
        }
      })
      .catch(() => alert("Terjadi kesalahan saat menyimpan"));

      document.getElementById("popupDate").style.display = "none";
    }

    function openGenderPopup() {
      document.getElementById("popupGender").style.display = "flex";
    }

    function submitGender() {
      const gender = document.querySelector('input[name="gender"]:checked');
      if (!gender) {
        alert("Silakan pilih jenis kelamin.");
        return;
      }

      fetch("updategender.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ gender: gender.value })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast("Jenis kelamin berhasil diperbarui");
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          alert("Gagal: " + data.message);
        }
      })
      .catch(() => alert("Terjadi kesalahan saat menyimpan"));

      document.getElementById("popupGender").style.display = "none";
    }

  </script>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script>
    function handleGoogleLogout(event) {
      event.preventDefault();

      // Hapus session Google jika login via Google
      google.accounts.id.disableAutoSelect();

      // Lanjut ke logout.php setelah logout Google
      setTimeout(() => {
        event.target.submit();
      }, 100); // beri sedikit delay agar Google logout terpicu
    }
  </script>
  <script>
    function openLogoutPopup() {
      document.getElementById("logoutPopup").style.display = "flex";
    }
    function closeLogoutPopup() {
      document.getElementById("logoutPopup").style.display = "none";
    }
    </script>

  <?php if ($_SESSION['is_google_login'] ?? false): ?>
  <script>
    function handleGoogleLogout(event) {
      event.preventDefault();
      google.accounts.id.disableAutoSelect();
      setTimeout(() => { event.target.submit(); }, 100);
    }
  </script>
  <?php endif; ?>

  <!-- Toast Notifikasi -->
<div class="toast" id="successToast">
  <p>Nama berhasil diperbarui</p>
</div>
</body>
</html>