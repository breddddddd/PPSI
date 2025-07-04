<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = '';
$total_cart_items = 0;

// Ambil user_type
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_type = $user['user_type'] ?? '';

// Ambil data favorit
$query = "SELECT p.* FROM favorites f JOIN products p ON f.product_id = p.id WHERE f.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Ambil total item di keranjang
$cart_stmt = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$row_cart = $cart_result->fetch_assoc();
$total_cart_items = $row_cart['total_items'] ?? 0;
?>

<div class="favorite-container">
  <h2 class="favorite-title">Favorit Kamu</h2>
    <div class="favorite-grid">
    <?php while ($row = $result->fetch_assoc()) {
        // Ubah nama produk jadi nama file, contoh: "Bow Hair Clips" => "Bowhairclips.php"
        $filename = strtolower(str_replace(' ', '', $row['name'])) . '.php';?>
        <a href="<?= $filename ?>?id=<?= $row['id'] ?>" class="product-link">
        <div class="product">
            <img src="<?= $row['image'] ?>" alt="<?= $row['name'] ?>">
            <h4><?= $row['name'] ?></h4>
            <p>Rp. <?= number_format($row['price'], 0, ',', '.') ?></p>
            <form action="remove_favorite.php" method="POST" style="margin-top: 10px;">
            <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
            <button type="submit" class="remove-favorite-btn">Hapus</button>
            </form>
        </div>
        </a>
    <?php } ?>
    </div>
    </div>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jepit Aku Lucu</title>
    <link rel="stylesheet" href="assets/assets/style.css">
    <link rel="stylesheet" href="assets/assets/favorite.css">
    <link rel="stylesheet" href="assets/assets/chart.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
</head>
<body>

    <style>
        .mobile-nav {
        position: fixed;
        top: 0;
        right: -100%;
        width: 25%;
        height: 100%;
        background-color: white;
        box-shadow: -2px 0 10px rgba(0,0,0,0.1);
        transition: right 0.3s ease;
        z-index: 1001;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        justify-content: start;
        }
        .mobile-nav ul {
        list-style: none;
        padding: 0;
        padding-top: 80px;
        }
        .mobile-nav ul li {
        margin-bottom: 20px;
        }
        .mobile-nav ul li a {
        text-decoration: none;
        color: #333;
        font-size: 18px;
        font-weight: 500;
        }
        .mobile-nav ul li a:hover {
        color: #e91e63;
        }
        .mobile-nav.active {
        right: 0;
        }
        .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        backdrop-filter: blur(4px);
        background: rgba(0, 0, 0, 0.2);
        display: none;
        z-index: 1000;
        }
        .overlay.show {
        display: block;
        }
    </style>

<header>
        <a href="home.php" class="logo" onclick="window.location.reload();">
            <img src="assets/assets/image/logo.png" alt="Logo" />
        </a>
        <ul class="navmenu">
            <li><a href="Home.php">home</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="Favorite.php">Favorite</a></li>
             <li><a href="custom.php" class="custom-btn">Custom</a></li>                   
        </ul>

        <!-- Search box -->
        <div class="search-box" id="searchBox">
            <input type="text" id="searchInput" placeholder="Cari produk di sini..." onkeyup="searchProduct()" onkeypress="checkEnter(event)" />
            <button type="button" onclick="closeSearch()">&times;</button>
        </div>
  
        <div class="nav-icon">
            <a href="#"><i class='bx bx-search'></i></a>
            <a href="profile.php"><i class='bx bx-user' ></i></a>
            <a href="Chart.php" class="cart-icon">
            <i class='bx bx-cart'></i>
            <span class="cart-count"><?php echo $total_cart_items; ?></span>
            </a>
            <div class="bx bx-menu" id="menu-icon"></div>
            <nav class="mobile-nav" id="mobileNav">
            <ul>
                <?php if ($user_type === 'penjual'): ?>
                <li><a href="seller/dashboard.php">Dashboard Penjual</a></li>
                <?php else: ?>
                <li><a href="Home.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="Favorite.php">Favorite</a></li>
                <?php endif; ?>
            </ul>
            </nav>
            <div class="overlay" id="overlay"></div>
        </div>
          
    </header>

    <script src="assets/assets/java.js"></script>
    
    <script>
        function toggleDropdown(event) {
          event.preventDefault();
          document.getElementById("dropdownMenu").classList.toggle("show");
        }
      
        window.onclick = function(e) {
          if (!e.target.matches('.dropdown > a')) {
            let dropdowns = document.getElementsByClassName("dropdown-content");
            for (let i = 0; i < dropdowns.length; i++) {
              let openDropdown = dropdowns[i];
              if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
              }
            }
          }
        }
      </script>

    <script>
        // Menangani pencarian melalui ikon search
        const searchIcon = document.querySelector('.bx-search');
        const searchBox = document.getElementById('searchBox');
    
        // Menampilkan atau menyembunyikan kotak pencarian saat ikon pencarian diklik
        searchIcon.addEventListener('click', () => {
            searchBox.style.display = searchBox.style.display === 'block' ? 'none' : 'block';
            document.getElementById('searchInput').focus();
        });
    
        // Menutup kotak pencarian
        function closeSearch() {
            searchBox.style.display = 'none';
        }

        // Fungsi pencarian produk
        function searchProduct() {
            // Ambil nilai dari input pencarian
            var input = document.getElementById("searchInput").value.toLowerCase();
            // Ambil semua elemen produk (pastikan elemen produk memiliki class 'row' atau 'product')
            var products = document.querySelectorAll(".products .row");

            // Loop untuk setiap produk dan cek apakah nama produk sesuai dengan input pencarian
            products.forEach(function(product) {
                var productName = product.querySelector(".price h4").innerText.toLowerCase(); // Ambil nama produk

                // Cek jika nama produk cocok dengan input pencarian
                if (productName.includes(input)) {
                    product.style.display = "block"; // Tampilkan produk
                } else {
                    product.style.display = "none"; // Sembunyikan produk
                }
            });
        }

        // Menangani penekanan tombol Enter untuk pencarian
        function checkEnter(event) {
            if (event.key === "Enter") {
                searchProduct();  // Panggil fungsi pencarian ketika tombol Enter ditekan
            }
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            
            // Pastikan elemen ditemukan
            if (searchInput) {
                searchInput.addEventListener('keyup', searchProduct);
                searchInput.addEventListener('keypress', checkEnter);
            } else {
                console.log('Elemen searchInput tidak ditemukan!');
            }
        });
    </script>

    <script>
        const menuIcon = document.getElementById("menu-icon");
        const mobileNav = document.getElementById("mobileNav");
        const overlay = document.getElementById("overlay");

        menuIcon.addEventListener("click", () => {
            mobileNav.classList.toggle("active");
            overlay.classList.toggle("show");

            if (menuIcon.classList.contains("bx-menu")) {
                menuIcon.classList.remove("bx-menu");
                menuIcon.classList.add("bx-x");
            } else {
                menuIcon.classList.remove("bx-x");
                menuIcon.classList.add("bx-menu");
            }
        });

        overlay.addEventListener("click", () => {
            mobileNav.classList.remove("active");
            overlay.classList.remove("show");
            menuIcon.classList.remove("bx-x");
            menuIcon.classList.add("bx-menu");
        });
    </script>
    </body>
    </html>