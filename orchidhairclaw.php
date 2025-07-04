<?php
include 'config/config.php';

// Ambil ID produk dari URL, default ke 11 jika tidak ada
$product_id = $_GET['id'] ?? 11;

// Ambil data produk utama
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
  echo "Produk tidak ditemukan.";
  exit;
}

// Ambil varian dari tabel product_variants
$stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$variants = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Jepitakulucu - <?= htmlspecialchars($product['name']) ?></title>
  <link rel="stylesheet" href="assets/assets/style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
  <link rel="stylesheet" href="assets/assets/katalog.css">
  <style>
    .color-box {
      width: 50px;
      height: 50px;
      background-size: cover;
      background-position: center;
      display: inline-block;
      margin: 5px;
      cursor: pointer;
      border: 2px solid transparent;
    }
    .color-box:hover {
      border: 2px solid #333;
    }
    .custom-alert {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: white;
      padding: 20px;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
      display: none;
      z-index: 999;
    }
    .custom-alert.show {
      display: block;
    }
  </style>
</head>
<body>
<header>
  <a href="home.php" class="logo"><img src="assets/assets/image/logo.png" alt="Logo"></a>
  <ul class="navmenu">
    <li><a href="Home.php">Home</a></li>
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
  <div class="nav-icon">
    <a href="#"><i class='bx bx-search'></i></a>
    <a href="#"><i class='bx bx-user'></i></a>
    <a href="Chart.php"><i class='bx bx-cart'></i></a>
    <div class="bx bx-menu" id="menu-icon"></div>
  </div>
</header>

<div class="container-section">
  <div class="container">
    <div class="image-section">
      <img id="main-image" src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
      <p class="caption"><?= htmlspecialchars($product['name']) ?></p>
    </div>

    <div class="details">
      <h2><?= htmlspecialchars($product['name']) ?></h2>
      <p class="rating">★★★★★ (2 Penilaian)</p>
      <p class="price">Rp<?= number_format($product['price'], 0, ',', '.') ?></p>
      <p><strong>Pengiriman:</strong> Pesanan Akan Tiba Pada Tanggal <span id="tanggal-pengiriman"></span></p>
      <p><strong>Jaminan Jepitakulucu:</strong> Bebas Pengembalian · COD-Cek Dulu · Proteksi Kerusakan</p>

      <p><strong>Pilihan:</strong></p>
      <div class="colors">
        <?php while ($variant = $variants->fetch_assoc()): 
          $img = $variant['variant_image'] ?? ($variant['variant_name'] . '.jpg');
        ?>
          <div class="variant-item">
            <div class="color-box"
                style="background-image: url('assets/assets/image/<?= $img ?>');"
                data-image="assets/assets/image/<?= $img ?>"
                data-product-id="<?= $variant['id'] ?>"
                onclick="changeMainImage(this)">
            </div>
            <p class="variant-name"><?= htmlspecialchars($variant['variant_name']) ?></p>
          </div>
        <?php endwhile; ?>
      </div>


      <!-- Alert -->
      <div id="customAlert" class="custom-alert">
        <div class="alert-box">
          <h3>Berhasil!</h3>
          <p id="alertMessage">Produk ditambahkan ke keranjang sebanyak <span id="alertQty">1</span> item.</p>
          <button onclick="closeAlert()">OK</button>
        </div>
      </div>

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
  let selectedProductId = null;

  function changeQty(amount) {
    const qtyInput = document.getElementById('quantity');
    let current = parseInt(qtyInput.value);
    if (!isNaN(current)) {
      const newVal = current + amount;
      qtyInput.value = newVal < 1 ? 1 : newVal;
    }
  }

  function changeMainImage(element) {
    const newImageSrc = element.getAttribute("data-image");
    selectedProductId = element.getAttribute("data-product-id");
    document.getElementById("main-image").src = newImageSrc;
  }

  function addToCart() {
    const quantity = document.getElementById('quantity').value;

    if (!selectedProductId) {
      alert('Silakan pilih varian terlebih dahulu.');
      return;
    }

    fetch("add_to_cart.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `product_id=${selectedProductId}&quantity=${quantity}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        document.getElementById('alertQty').textContent = quantity;
        document.getElementById('customAlert').classList.add('show');
      } else {
        alert("Gagal: " + data.message);
      }
    });
  }

  function closeAlert() {
    document.getElementById('customAlert').classList.remove('show');
  }

  // Tampilkan tanggal pengiriman
  const today = new Date();
  const start = new Date(today); start.setDate(today.getDate() + 3);
  const end = new Date(today); end.setDate(today.getDate() + 6);
  const options = { day: 'numeric', month: 'long' };
  document.getElementById('tanggal-pengiriman').textContent =
    `${start.toLocaleDateString('id-ID', options)} - ${end.toLocaleDateString('id-ID', options)}`;
</script>
</body>
</html>
