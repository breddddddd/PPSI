<?php
session_start(); // Mulai sesi untuk mengakses user_id
include 'config/config.php'; // Sertakan file konfigurasi database Anda. Pastikan path ini benar!

// Redirect jika pengguna belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Ganti dengan path ke halaman login Anda jika berbeda
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = ''; // Inisialisasi user_type

// Ambil user_type dari database untuk menampilkan sidebar yang sesuai
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $user_type = $row['user_type'];
}
$stmt->close();

// Ambil semua pesanan yang terkait dengan user_id yang sedang login
$orders = [];
// Query mengambil data dari tabel 'orders'. Gunakan 'user_id' dan 'total_amount', serta detail pengiriman
$stmt = $conn->prepare("SELECT id, order_date, total_amount, status, receiver_name, shipping_address, phone_number FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($order_row = $result->fetch_assoc()) {
    $order_id = $order_row['id'];
    $order_items = [];

    // Ambil detail item untuk setiap pesanan dari tabel order_items
    // Pastikan item_name dan custom_image ada di order_items
    $stmt_items = $conn->prepare("SELECT item_name, item_price, quantity, custom_image FROM order_items WHERE order_id = ?");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();
    while ($item_row = $items_result->fetch_assoc()) {
        $order_items[] = $item_row;
    }
    $stmt_items->close();

    $order_row['items'] = $order_items; // Tambahkan item ke dalam data pesanan
    $orders[] = $order_row;
}
$stmt->close();

$conn->close(); // Tutup koneksi database
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Kamu - Jepit Aku Lucu</title>
    <link rel="stylesheet" href="assets/assets/style.css">
    <link rel="stylesheet" href="assets/assets/chart.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
    <style>
        /* CSS tambahan untuk halaman pesanan */
        body {
            font-family: 'Jost', sans-serif;
            background-color: #f8f8f8;
            color: #333;
        }

        .order-container {
            max-width: 900px;
            margin: 120px auto 50px auto; /* Sesuaikan margin atas agar tidak tertutup header */
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .order-container h2 {
            text-align: center;
            color: #e91e63;
            margin-bottom: 30px;
            font-size: 2em;
            font-weight: 600;
        }

        .order-card {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px 20px;
            background-color: #fdfdfd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease-in-out;
        }

        .order-card:hover {
            transform: translateY(-5px);
        }

        .order-card h3 {
            font-size: 1.3em;
            color: #555;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-card p {
            margin: 5px 0;
            color: #666;
            font-size: 0.95em;
        }

        .order-card .status {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
            display: inline-block;
            margin-top: 10px;
        }

        /* --- Status Colors (Pastikan ini mencakup semua status yang mungkin dari database Anda) --- */
        .status.pending { background-color: #ffc107; } /* Kuning */
        .status.diproses { background-color: #fd7e14; } /* Oranye */
        .status.dikirim { background-color: #007bff; } /* Biru (sama dengan shipping) */
        .status.selesai { background-color: #28a745; } /* Hijau (sama dengan completed) */
        .status.dibatalkan { background-color: #dc3545; } /* Merah (sama dengan cancelled) */
        /* Anda bisa menambahkan atau mengganti jika ada status lain seperti 'pengembalian' dll. */


        .order-card .total-amount {
            font-weight: 700;
            color: #e91e63;
            font-size: 1.1em;
            margin-top: 10px;
        }

        .no-orders {
            text-align: center;
            font-size: 1.1em;
            color: #777;
            padding: 50px 0;
        }

        /* Detail item dalam pesanan */
        .order-items-list {
            margin-top: 15px;
            border-top: 1px dashed #eee;
            padding-top: 15px;
        }
        .order-items-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .order-items-list li {
            margin-bottom: 5px;
            color: #444;
            display: flex;
            justify-content: space-between;
            font-size: 0.9em;
        }
        .order-items-list li span:first-child {
            font-weight: 500;
        }
        .order-items-list .item-qty-price {
            color: #888;
            font-size: 0.85em;
        }
        .shipping-info {
            margin-top: 15px;
            border-top: 1px dashed #eee;
            padding-top: 15px;
            font-size: 0.9em;
            color: #555;
        }
        .shipping-info strong {
            color: #333;
        }

        /* --- CSS for Header and Mobile Nav (Copied from home.php) --- */
        header {
            position: fixed;
            width: 100%;
            top: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            padding: 20px 10%;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .logo img {
            max-width: 120px;
            height: auto;
        }

        .navmenu {
            display: flex;
        }

        .navmenu a {
            color: #2c2c2c;
            font-size: 16px;
            text-transform: capitalize;
            padding: 10px 20px;
            font-weight: 400;
            transition: all .42s ease;
        }

        .navmenu a:hover {
            color: #e91e63;
        }

        .nav-icon {
            display: flex;
            align-items: center;
        }

        .nav-icon i {
            margin-right: 20px;
            color: #2c2c2c;
            font-size: 24px;
            font-weight: 400;
            transition: all .42s ease;
        }

        .nav-icon i:hover {
            color: #e91e63;
            transform: scale(1.1);
        }

        #menu-icon {
            font-size: 35px;
            color: #2c2c2c;
            z-index: 10001;
            cursor: pointer;
            display: none;
        }

        /* Search Box */
        .search-box {
            position: absolute; /* Posisikan secara absolut */
            top: 100%; /* Di bawah header */
            left: 50%; /* Tengah secara horizontal */
            transform: translateX(-50%); /* Sesuaikan posisi ke tengah */
            width: 80%; /* Lebar kotak pencarian */
            max-width: 500px; /* Lebar maksimum */
            background: white; /* Latar belakang putih */
            padding: 10px; /* Padding */
            border-radius: 8px; /* Sudut melengkung */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Bayangan */
            display: none; /* Sembunyikan secara default, akan ditampilkan dengan JS */
            z-index: 999; /* Pastikan di atas konten lain */

            display: flex; /* Gunakan flexbox untuk menata elemen di dalamnya */
            align-items: center; /* Pusatkan vertikal */
            gap: 10px; /* Jarak antara input dan tombol */
        }

        .search-box input {
            flex-grow: 1;
            border: 1px solid #ddd;
            padding: 8px 12px;
            border-radius: 5px;
            outline: none;
            font-size: 1em;
        }

        .search-box button {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #555;
            padding: 5px;
        }

        .cart-icon {
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -10px;
            background-color: #e91e63;
            color: white;
            font-size: 12px;
            border-radius: 50%;
            padding: 3px 7px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-width: 20px;
            height: 20px;
        }

        /* Dropdown CSS */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            padding: 5px 0;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
            color: #e91e63;
        }

        .dropdown-content.show {
            display: block;
        }

        /* === MENU SLIDE === */
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -100%;
            width: 25%; /* Sesuaikan lebar sesuai kebutuhan */
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

        /* Responsiveness */
        @media (max-width: 1000px) {
            .navmenu {
                display: none;
            }
            #menu-icon {
                display: block;
            }
            header {
                padding: 15px 5%;
            }
            .search-box {
                width: 90%;
            }
        }

        @media (max-width: 600px) {
            .mobile-nav {
                width: 50%; /* Lebar sidebar lebih besar di layar kecil */
            }
            .order-container {
                margin: 100px 10px 30px 10px;
                padding: 15px;
            }
            .order-card h3 {
                font-size: 1.1em;
            }
            .order-card p, .order-card .total-amount {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

    <header>
        <a href="home.php" class="logo">
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
            <span class="cart-count"><?php echo $total_cart_items ?? 0; ?></span>
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
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            </nav>
            <div class="overlay" id="overlay"></div>
        </div>

    </header>

    <main class="order-container">
        <h2>Daftar Pesanan Kamu</h2>

        <?php
        // Tampilkan pesan sukses atau error dari sesi
        if (isset($_SESSION['message'])) {
            echo '<div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px;">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px;">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <h3>
                        Pesanan ID: #<?php echo htmlspecialchars($order['id']); ?>
                        <span class="status <?php echo strtolower(htmlspecialchars($order['status'])); ?>">
                            <?php
                            // Mengubah format tampilan status agar lebih mudah dibaca
                            $display_status = htmlspecialchars($order['status']);
                            switch (strtolower($display_status)) {
                                case 'pending':
                                    echo 'Pending';
                                    break;
                                case 'diproses':
                                    echo 'Sedang Diproses Penjual';
                                    break;
                                case 'dikirim':
                                    echo 'Sedang Dikirim';
                                    break;
                                case 'selesai':
                                    echo 'Selesai';
                                    break;
                                case 'dibatalkan':
                                    echo 'Dibatalkan';
                                    break;
                                default:
                                    echo ucfirst($display_status); // Tampilkan apa adanya jika tidak cocok
                                    break;
                            }
                            ?>
                        </span>
                    </h3>
                    <p>Tanggal Pesanan: <?php echo date('d M Y, H:i', strtotime($order['order_date'])); ?></p>
                    <p class="total-amount">Total: Rp. <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>

                    <div class="shipping-info">
                        <strong>Pengiriman ke:</strong><br>
                        Nama: <?php echo htmlspecialchars($order['receiver_name']); ?><br>
                        Alamat: <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                        Telepon: <?php echo htmlspecialchars($order['phone_number']); ?>
                    </div>

                    <?php if (!empty($order['items'])): ?>
                        <div class="order-items-list">
                            <strong>Detail Barang:</strong>
                            <ul>
                                <?php foreach ($order['items'] as $item): ?>
                                    <li>
                                        <span><?= htmlspecialchars($item['item_name']) ?></span>
                                        <span class="item-qty-price">
                                            <?= $item['quantity'] ?> x Rp<?= number_format($item['item_price'], 0, ',', '.') ?>
                                            (Subtotal: Rp<?= number_format($item['quantity'] * $item['item_price'], 0, ',', '.') ?>)
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Logika tombol "Batalkan Pesanan" dan "Pesanan Selesai"
                    // Menggunakan strtolower() untuk memastikan perbandingan case-insensitive
                    $current_status = strtolower($order['status']);

                    if ($current_status == 'pending' || $current_status == 'diproses'):
                    ?>
                        <div style="margin-top: 15px; text-align: right;">
                            <a href="cancel_order.php?order_id=<?php echo htmlspecialchars($order['id']); ?>"
                               onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')"
                               style="background-color: #dc3545; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9em; margin-left: 10px;">
                                Batalkan Pesanan
                            </a>
                        </div>
                    <?php elseif ($current_status == 'dikirim'): ?>
                        <div style="margin-top: 15px; text-align: right;">
                            <a href="complete_order.php?order_id=<?php echo htmlspecialchars($order['id']); ?>"
                               onclick="return confirm('Apakah Anda yakin ingin menandai pesanan ini sebagai selesai? Tindakan ini tidak bisa dibatalkan.')"
                               style="background-color: #28a745; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9em;">
                                Pesanan Selesai
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-orders">Kamu belum memiliki pesanan apapun saat ini.</p>
        <?php endif; ?>
    </main>

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
        // JavaScript untuk fungsionalitas dropdown, search, dan mobile nav
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

        const searchIcon = document.querySelector('.bx-search');
        const searchBox = document.getElementById('searchBox');

        searchIcon.addEventListener('click', () => {
            searchBox.style.display = searchBox.style.display === 'flex' ? 'none' : 'flex';
            if (searchBox.style.display === 'flex') {
                document.getElementById('searchInput').focus();
            }
        });

        function closeSearch() {
            searchBox.style.display = 'none';
        }

        function searchProduct() {
            // Functionality for product search (likely not needed on this specific page, but kept for consistency with header)
            // You might remove this if search is only for product listing pages
        }

        function checkEnter(event) {
            if (event.key === "Enter") {
                searchProduct();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', searchProduct);
                searchInput.addEventListener('keypress', checkEnter);
            }
        });

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