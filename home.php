<?php
    session_start();
    include 'config/config.php';

    $total_cart_items = 0;
    $fav_product_ids = [];
    $product = null;
    $user_type = '';

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        // Ambil user_type
        $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_type = $row['user_type'];
        }

        // Ambil total item di cart
        $stmt = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_cart_items = $row['total_items'] ?? 0;

        // Ambil daftar produk favorit user
        $fav_query = $conn->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
        $fav_query->bind_param("i", $user_id);
        $fav_query->execute();
        $fav_result = $fav_query->get_result();
        while ($fav_row = $fav_result->fetch_assoc()) {
            $fav_product_ids[] = $fav_row['product_id'];
        }
    }

    // Ambil data produk tertentu
    $product_id = 2;
    $stmt = $conn->prepare("SELECT name, price, status FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    // Cek apakah produk ini difavoritkan
    $is_fav = in_array($product_id, $fav_product_ids);
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
   /* === MENU SLIDE === */
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
  padding-top: 80px; /* Jarak atas konten menu */
}

.mobile-nav ul li {
  margin-bottom: 20px;
}

.mobile-nav ul li a {
  text-decoration: none;
  color: #333;
  font-size: 18px;
  font-weight: 500;
  transition: color 0.2s ease;
}

.mobile-nav ul li a:hover {
  color: #e91e63;
}

.mobile-nav.active {
  right: 0;
}

/* === OVERLAY BLUR === */
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
            <i class='bx bx-menu' id="menu-icon"></i>
            <nav class="mobile-nav" id="mobileNav">
            <ul>
                <?php if ($user_type === 'penjual'): ?>
                <li><a href="seller/dashboard.php">Dashboard Penjual</a></li>
                <?php else: ?>
                <li><a href="Home.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="Favorite.php">Favorite</a></li>
                <li><a href="customerorders.php">Pesanan Kamu</a></li>
                <?php endif; ?>
            </ul>
            </nav>
            <!-- Overlay blur -->
            <div class="overlay" id="overlay"></div>
        </div>
         
    </header>

    <section class="main-home">
        <div class="main-text">
            <h5>New Collection</h5>
            <h1>New Arrival <br> Collection 2025</h1>
            <p>Make Your Day Col·or·ful</p>

            <a href="products.php" class="main-btn">Shop Now <i class='bx bx-right-arrow-alt' ></i></a>
        </div>

        <div class="down-arrow">
            <a href="#trending" class="down"><i class='bx bx-down-arrow-alt' ></i></a>
        </div>
    </section>

    <!--trending-products-section-->
    <section class="trending-produt" id="trending">
        <div class="center-text">
            <h2>Our Trending <span>products</span></h2>
        </div>

        <div class="products">
           <div class="products">
        <?php
        $stmt = $conn->prepare("SELECT id, name, price, status, image FROM products WHERE status IN ('Hot', 'New Arrival')");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()):
            $product_id = $row['id'];
            $product_name = $row['name'];
            $product_price = $row['price'];
            $product_status = $row['status'];
            $product_image = $row['image'];
            $is_fav = in_array($product_id, $fav_product_ids);
            $file_link = strtolower(str_replace(' ', '', $product_name)) . ".php";?>
            <div class="row">
                <a href="<?= $file_link ?>?id=<?= $product_id ?>" style="text-decoration: none; color: inherit;">
                    <img src="<?= $product_image ?>" alt="<?= htmlspecialchars($product_name) ?>">
                    <div class="product-text">
                        <h5><?= htmlspecialchars($product_status) ?></h5>
                    </div>
                    <div class="heart-icon <?= $is_fav ? 'active' : '' ?>" onclick="event.preventDefault(); saveFavorite(this, <?= $product_id ?>);">
                        <i class='bx <?= $is_fav ? 'bxs-heart' : 'bx-heart' ?>'></i>
                    </div>
                    <div class="ratting">
                        <i class='bx bx-star'></i>
                        <i class='bx bx-star'></i>
                        <i class='bx bx-star'></i>
                        <i class='bx bx-star'></i>
                        <i class='bx bxs-star-half'></i>
                    </div>
                    <div class="price">
                        <h4><?= htmlspecialchars($product_name) ?></h4>
                        <p>Rp. <?= number_format($product_price) ?></p>
                    </div>
                </a>
            </div>
        <?php endwhile; ?>
        </div>
            </a>
        </div>
    </section>

    <!--contact section-->
    <section class="contact">
        <div class="contact-info">
            <div class="first-info">
                <img src="assets/assets/image/logo.png" alt="">

                <p>Jl. Mercubuana <br> Meruya, Jakarta Barat</p>
                <p>0813998976638</p>
                <p>jepitakulucu@gmail.com</p>

                <div class="social-icon">
                    <a href="#"><i class='bx bxl-facebook' ></i></a>
                    <a href="#"><i class='bx bxl-twitter' ></i></a>
                    <a href="#"><i class='bx bxl-instagram' ></i></a>
                    <a href="#"><i class='bx bxl-tiktok' ></i></a>
                </div>
            </div>

            <div class="second-info">
                <h4>Support</h4>
                <p>Contact Us</p>
                <p>About Page</p>
                <a href="products.php"><p>Shopping</p></a>
                <p>Privacy</p>
            </div>

            <div class="third-info">
                <h4>Shop</h4>
                <p>Men's Shopping</p>
                <p>Women's Shopping</p>
                <p>Kid's Shopping</p>
                <p>Furniture</p>
                <p>Discount</p>
            </div>

            <div class="fourth-info">
                <h4>Company</h4>
                <p>About</p>
                <p>Blog</p>
                <p>Affilate</p>
                <a href="login.php"><p>Login</p></a>
            </div>

            <div class="five">
                <h4>Subcribe</h4>
                <p>Recieve Updates, Hot Deals, Discount Sent Straight In Your Inbox Daily</p>
            </div>
        </div>
    </section>

    <div class="end-text">
        <p>Copyright © @2025. All Rights Reserved.Deisgn By jepitakulucu.</p>
    </div>

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
        const menuIcon = document.getElementById("menu-icon");
        const mobileNav = document.getElementById("mobileNav");
        const overlay = document.getElementById("overlay");

        menuIcon.addEventListener("click", () => {
            mobileNav.classList.toggle("active");
            overlay.classList.toggle("show");

            // Ganti ikon antara ☰ dan ✖
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

            // Kembalikan ikon ke menu ☰
            menuIcon.classList.remove("bx-x");
            menuIcon.classList.add("bx-menu");
        });
    </script>

</body>
</html>