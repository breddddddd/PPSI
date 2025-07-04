<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT c.id as cart_id, c.quantity, p.name, p.price, p.image, c.custom_image, c.product_id
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_all = 0;
$items = [];
while($row = $result->fetch_assoc()) {
  $is_custom = empty($row['product_id']) || $row['product_id'] == 0;
  $harga = $is_custom ? 15000 : (isset($row['price']) ? $row['price'] : 0);
  $row['price'] = $harga;
  $total_all += $harga * $row['quantity'];
  $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Jepit Aku Lucu - Chart</title>
  <link rel="stylesheet" href="assets/assets/style.css">
  <link rel="stylesheet" href="assets/assets/chart.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
</head>
<body>

<header>
  <a href="home.php" class="logo">
    <img src="assets/assets/image/logo.png" alt="Logo" />
  </a>
  <ul class="navmenu">
    <li><a href="Home.php">Home</a></li>
    <li><a href="products.php">Products</a></li>
    <li><a href="Favorite.php">Favorite</a></li>
  </ul>
  <div class="nav-icon">
    <a href="profile.php"><i class='bx bx-user'></i></a>
  </div>
</header>

<div class="cart-container">
  <div class="cart-header">
    <span>Keranjang Anda</span>
  </div>

  <form action="delete_selected.php" method="POST" id="cart-form">
    <?php if (count($items) > 0): ?>
      <?php foreach ($items as $index => $row): ?>
        <?php
          $is_custom = empty($row['product_id']) || $row['product_id'] == 0;
          $gambar = $is_custom
              ? (!empty($row['custom_image']) ? htmlspecialchars($row['custom_image']) : 'assets/assets/image/default.png')
              : (!empty($row['image']) ? htmlspecialchars($row['image']) : 'assets/assets/image/default.png');
          $judul = $is_custom ? 'Custom Design' : (!empty($row['name']) ? htmlspecialchars($row['name']) : 'Produk Tidak Diketahui');
          $harga = $row['price'];
          $jumlah = $row['quantity'];
        ?>
        <div class="cart-item" data-price="<?= $harga ?>" data-index="<?= $index ?>">
          <input type="checkbox" class="item-checkbox" name="delete_ids[]" value="<?= $row['cart_id'] ?>" onchange="updateTotal()" />
          <div class="product-info">
            <img src="<?= $gambar ?>" alt="<?= $judul ?>" width="70" />
            <div class="product-title"><?= $judul ?></div>
          </div>
          <div class="price">Rp<?= number_format($harga, 0, ',', '.') ?></div>
          <div class="qty-control">
            <button type="button" onclick="updateQuantity(<?= $index ?>, -1)">-</button>
            <input type="number" value="<?= $jumlah ?>" min="1" class="qty-input" data-index="<?= $index ?>" data-cart-id="<?= $row['cart_id'] ?>" onchange="updateTotal()" />
            <button type="button" onclick="updateQuantity(<?= $index ?>, 1)">+</button>
          </div>
          <div class="total-item">Rp<?= number_format($harga * $jumlah, 0, ',', '.') ?></div>
          <div class="remove">
            <a href="delete_single.php?id=<?= $row['cart_id'] ?>" class="delete-link">Hapus</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="padding: 20px;">Keranjang belanja kamu kosong.</p>
    <?php endif; ?>
  </form>

  <div class="cart-footer">
    <div class="left">
      <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)" />
      <button type="button" class="delete" id="bulk-delete-button">Hapus</button>
    </div>
    <div class="right">
      Total (<?= count($items) ?> produk): 
      <span class="total-price">Rp<?= number_format($total_all, 0, ',', '.') ?></span>
      <form action="checkout.php" method="POST" id="checkout-form">
        <input type="hidden" name="total_price" id="checkout-total" value="<?= $total_all ?>">
        <button type="submit" class="checkout">Checkout</button>
      </form>
    </div>
  </div>
</div>

<script>
  function updateTotal() {
    let total = 0;
    const items = document.querySelectorAll(".cart-item");

    items.forEach((item) => {
      const checkbox = item.querySelector(".item-checkbox");
      const qty = parseInt(item.querySelector(".qty-input").value);
      const price = parseInt(item.dataset.price);
      const totalDisplay = item.querySelector(".total-item");

      const subtotal = price * qty;
      totalDisplay.innerText = "Rp" + subtotal.toLocaleString("id-ID");

      if (checkbox.checked) {
        total += subtotal;
      }
    });

    const checkedCount = document.querySelectorAll(".item-checkbox:checked").length;
    let finalTotal = total;
    if (checkedCount === 0) {
      finalTotal = 0;
      items.forEach((item) => {
        const qty = parseInt(item.querySelector(".qty-input").value);
        const price = parseInt(item.dataset.price);
        finalTotal += price * qty;
      });
    }

    document.querySelector(".total-price").innerText = "Rp" + finalTotal.toLocaleString("id-ID");
    const hiddenInput = document.getElementById("checkout-total");
    if (hiddenInput) hiddenInput.value = finalTotal;
  }

function updateQuantity(index, delta) {
  const input = document.querySelector(`.qty-input[data-index='${index}']`);
  let value = parseInt(input.value) + delta;
  if (value < 1) value = 1;
  input.value = value;

  const cartId = input.dataset.cartId;

  fetch('update_quantity.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `cart_id=${cartId}&quantity=${value}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      updateTotal();
    }
  });
}

function toggleSelectAll(masterCheckbox) {
  const checkboxes = document.querySelectorAll(".item-checkbox");
  checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
  updateTotal();
}
</script>

<!-- Popup -->
<div id="custom-confirm" class="popup-overlay" style="display: none;">
  <div class="popup-box">
    <p>Yakin ingin menghapus produk ini dari keranjang?</p>
    <button id="confirm-yes">OK</button>
    <button id="confirm-no" class="cancel-btn">Batal</button>
  </div>
</div>

<script>
const modal = document.getElementById('custom-confirm');
const confirmYes = document.getElementById('confirm-yes');
const confirmNo = document.getElementById('confirm-no');
let currentLink = null;

document.querySelectorAll('.delete-link').forEach(link => {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    currentLink = this.href;
    modal.style.display = 'flex';
  });
});

confirmYes.onclick = () => {
  window.location.href = currentLink;
};

confirmNo.onclick = () => {
  modal.style.display = 'none';
  currentLink = null;
};

const bulkDeleteButton = document.getElementById('bulk-delete-button');
const cartForm = document.getElementById('cart-form');
let bulkDelete = false;

bulkDeleteButton.addEventListener('click', function () {
  bulkDelete = true;
  modal.style.display = 'flex';
});

confirmYes.addEventListener('click', () => {
  if (bulkDelete) {
    cartForm.submit();
  } else {
    window.location.href = currentLink;
  }
  modal.style.display = 'none';
  bulkDelete = false;
});

confirmNo.addEventListener('click', () => {
  modal.style.display = 'none';
  currentLink = null;
  bulkDelete = false;
});
</script>

</body>
</html>
