<?php
include 'config/config.php'; // koneksi ke DB

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 2;
$product = null;
$variants = [];

if ($product_id > 0) {
  $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();

    // Ambil semua varian jika produk ini memiliki nama sama dan varian tidak NULL
    $variant_stmt = $conn->prepare("SELECT DISTINCT variant FROM products WHERE name = ? AND variant IS NOT NULL");
    $variant_stmt->bind_param("s", $product['name']);
    $variant_stmt->execute();
    $variant_result = $variant_stmt->get_result();
    while ($row = $variant_result->fetch_assoc()) {
      $variants[] = $row['variant'];
    }
  } else {
    die("Produk tidak ditemukan.");
  }
} else {
  die("ID produk tidak valid.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jepitakulucu</title>
  <link rel="stylesheet" href="assets/assets/style.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
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
            <li class="dropdown">
                <a href="#" onclick="toggleDropdown(event)">Filter</a>
                <ul id="dropdownMenu" class="dropdown-content">
                  <li><a href="#">Trending</a></li>
                  <li><a href="#">Paling Laku</a></li>
                  <li><a href="#">4 Kamar</a></li>
                </ul>
              </li>              
        </ul>

    <div class="search-box" id="searchBox">
      <input type="text" id="searchInput" placeholder="Cari produk di sini..." onkeyup="searchProduct()" onkeypress="checkEnter(event)" />
      <button type="button" onclick="closeSearch()">&times;</button>
    </div>

    <div class="nav-icon">
      <a href="#"><i class='bx bx-search'></i></a>
      <a href="#"><i class='bx bx-user'></i></a>
      <a href="Chart.php"><i class='bx bx-cart'></i></a>
      <div class="bx bx-menu" id="menu-icon"></div>
    </div>
  </header>

  <link rel="stylesheet" href="assets/assets/katalog.css">

  <div class="container-section">
    <div class="container">
      <div class="image-section">
        <img src="assets/assets/image/katalog2.png" alt="Y2K Sunglasses">
        <p class="caption">Y2K Sunglasses</p>
      </div>

      <div class="details">
        <h2><?php echo htmlspecialchars($product['name']); ?></h2>
        <p class="rating">★★★★★ (2 Penilaian)</p>
        <p class="price">Rp<?php echo number_format($product['price'], 0, ',', '.'); ?></p>
        <p><strong>Pengiriman:</strong> Pesanan Akan Tiba Pada Tanggal <span id="tanggal-pengiriman"></span></p>
        <p><strong>Jaminan Jepitakulucu:</strong> Bebas Pengembalian · COD-Cek Dulu · Proteksi Kerusakan</p>
        
        <p><strong>Warna:</strong></p>
        <div class="colors">
          <div class="color-box" style="background-image: url('assets/image/color1.jpg');"></div>
          <div class="color-box" style="background-image: url('assets/image/color2.jpg');"></div>
          <div class="color-box" style="background-image: url('assets/image/color3.jpg');"></div>
        </div>

        <!-- Custom Alert Modal -->
        <div id="customAlert" class="custom-alert">
          <div class="alert-box">
            <h3>Berhasil!</h3>
            <p id="alertMessage">Produk ditambahkan ke keranjang sebanyak <span id="alertQty">1</span> item.</p>
            <button class="close-btn" onclick="closeAlert()">OK</button>
          </div>
        </div>

        <!-- Kuantitas dan tombol -->
        <div class="quantity-section">
          <label for="quantity"><strong>Kuantitas:</strong></label>
          <div class="quantity-controls">
            <button class="qty-btn" onclick="changeQty(-1)">−</button>
            <input type="number" id="quantity" value="1" min="1">
            <button class="qty-btn" onclick="changeQty(1)">+</button>
          </div>
          <button class="button outline" onclick="addToCart()">Masukkan Keranjang</button>
        </div>

        <a href="#" class="button">Beli Sekarang</a>
      </div>
    </div>
  </div>

  <script>
    function changeQty(amount) {
      const qtyInput = document.getElementById('quantity');
      let current = parseInt(qtyInput.value);
      if (!isNaN(current)) {
        const newVal = current + amount;
        qtyInput.value = newVal < 1 ? 1 : newVal;
      }
    }

    function addToCart() {
    const quantity = document.getElementById("quantity").value;
    const productId = <?php echo intval($product['id']); ?>;

    fetch("add_to_cart.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById('alertQty').textContent = quantity;
        document.getElementById('customAlert').classList.add('show');
        fetch("get_cart_count.php")
        .then(res => res.text())
        .then(count => {
          document.querySelector(".cart-count").textContent = count;
        });
      } else {
        alert(data.message);
        if (data.message === "Anda belum login.") {
          window.location.href = "login.php";
        }
      }
    });
  }

function closeAlert() {
  document.getElementById('customAlert').classList.remove('show');
}

  </script>

<script>
  // Ambil tanggal hari ini
  const today = new Date();

  // Buat tanggal awal (3 hari dari sekarang)
  const startDate = new Date();
  startDate.setDate(today.getDate() + 3);

  // Buat tanggal akhir (6 hari dari sekarang)
  const endDate = new Date();
  endDate.setDate(today.getDate() + 6);

  // Format ke Bahasa Indonesia
  const options = { day: 'numeric', month: 'long' };
  const startFormatted = startDate.toLocaleDateString('id-ID', options);
  const endFormatted = endDate.toLocaleDateString('id-ID', options);

  // Tampilkan di elemen HTML
  document.getElementById('tanggal-pengiriman').textContent = `${startFormatted} - ${endFormatted}`;
</script>

</body>
</html>
