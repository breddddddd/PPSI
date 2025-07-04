<?php
session_start();
include 'config/config.php';

$total_cart_items = 0;
$user_type = '';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Ambil total item di cart
    $stmt = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_cart_items = $row['total_items'] ?? 0;

    // Ambil user_type
    $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_type = $user['user_type'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jepit Aku Lucu</title>
    <link rel="stylesheet" href="assets/assets/style.css">
    <link rel="stylesheet" href="assets/assets/chart.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
</head>

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

    <body>
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

    <section class="trending-produt" id="trending">
        <div class="center-text">
            <h2>Our <span>products</span></h2>
        </div>

        <?php
        $user_id = $_SESSION['user_id'] ?? null;

        // Ambil semua produk
        $product_query_stmt = $conn->prepare("SELECT id, name, price, image, status FROM products WHERE variant IS NULL");
        $product_query_stmt->execute();
        $product_result = $product_query_stmt->get_result();

        // Ambil produk favorit user
        $fav_product_ids = [];
        if ($user_id) {
            $fav_query_stmt = $conn->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
            $fav_query_stmt->bind_param("i", $user_id);
            $fav_query_stmt->execute();
            $fav_result = $fav_query_stmt->get_result();
            while ($row = $fav_result->fetch_assoc()) {
                $fav_product_ids[] = $row['product_id'];
            }
            $fav_query_stmt->close();
        }
        ?>

    <div class="products">
    <?php while ($row = mysqli_fetch_assoc($product_result)) {
        $is_fav = in_array($row['id'], $fav_product_ids);

        // LOGIKA UNTUK MENENTUKAN URL HALAMAN DETAIL
        $product_id = $row['id'];
        $url = "";
        if ($product_id > 12) { // Jika ID lebih dari 12, ini produk baru (mengarah ke productdetail.php)
            $url = "productdetail.php?id=" . $product_id;
        } else { // Jika ID 12 atau kurang, ini produk lama (mengarah ke file statis nama_produk.php)
            $url = strtolower(str_replace(' ', '', $row['name'])) . ".php";
            // Perhatian: Pastikan file statis ini ada di root folder Anda
            // Contoh: pasteriaphonestrap.php, 1111.php (untuk ID 11 yang saya lihat di database Anda)
        }

        // LOGIKA UNTUK MENENTUKAN PATH GAMBAR
        $image_source = "";
        if (strpos($row['image'], 'uploads/') === 0) { // Jika path dimulai dengan 'uploads/'
            $image_source = $row['image']; // Path sudah relatif dari root web (e.g., PPSI/uploads/)
        } else {
            // Ini adalah gambar statis dari assets/assets/image/
            $image_source = $row['image']; // Path sudah relatif dari root web (e.g., PPSI/assets/assets/image/)
        }
        ?>
        <a href="<?= $url ?>" style="text-decoration: none; color: inherit;">
            <div class="row">
                <img src="<?= $image_source ?? 'assets/assets/image/default.png' ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                <div class="product-text">
                    <h5><?= htmlspecialchars($row['status']) ?></h5>
                </div>
                <div class="heart-icon <?= $is_fav ? 'active' : '' ?>" onclick="event.preventDefault(); saveFavorite(this, <?= $row['id'] ?>);">
                    <i class='bx <?= $is_fav ? 'bxs-heart' : 'bx-heart' ?>'></i>
                </div>
                <div class="ratting">
                    <i class='bx bxs-star'></i>
                    <i class='bx bxs-star'></i>
                    <i class='bx bxs-star'></i>
                    <i class='bx bxs-star'></i>
                    <i class='bx bxs-star'></i>
                </div>
                <div class="price">
                    <h4><?= htmlspecialchars($row['name']) ?></h4>
                    <p>Rp. <?= number_format($row['price'], 0, ',', '.') ?></p>
                </div>
            </div>
        </a>
    <?php } ?>
    <?php $product_query_stmt->close(); // Tutup statement setelah loop ?>
    </div>
    </section>

    <script src="assets/assets/java.js"></script>
    <script>
    function saveFavorite(el, productId) {
        fetch('save_favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
        })
        .then(res => res.text())
        .then(response => {
        if (response === 'added') {
            el.classList.add("active");
            el.querySelector('i').className = 'bx bxs-heart';
        } else if (response === 'removed') {
            el.classList.remove("active");
            el.querySelector('i').className = 'bx bx-heart';
        } else {
            alert("Silakan login terlebih dahulu.");
        }
        });
    }
    </script>

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