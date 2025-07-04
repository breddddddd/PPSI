<?php
session_start();
include 'config/config.php'; // Pastikan path ini benar untuk koneksi database

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$popupSuccess = false;

// Ambil total item di cart
$total_cart_items = 0;
$stmt_cart = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();
$row_cart = $result_cart->fetch_assoc();
$total_cart_items = $row_cart['total_items'] ?? 0;
$stmt_cart->close();

// Ambil user_type
$user_type = '';
$stmt_user_type = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt_user_type->bind_param("i", $user_id);
$stmt_user_type->execute();
$result_user_type = $stmt_user_type->get_result();
$user_data = $result_user_type->fetch_assoc();
$user_type = $user_data['user_type'] ?? '';
$stmt_user_type->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['canvas_data'])) {
    $data = $_POST['canvas_data'];
    $data = str_replace('data:image/png;base64,', '', $data);
    $data = str_replace(' ', '+', $data);
    $imageData = base64_decode($data);

    // Pastikan folder 'uploads' ada dan bisa ditulis
    $upload_dir = 'uploads/'; // Ini path relatif dari custom.php ke folder uploads
    if (!is_dir($upload_dir)) {
        // Coba buat folder jika belum ada dengan izin tulis
        // Perhatikan: Folder 'uploads' ini harus bisa ditulisi oleh web server
        if (!mkdir($upload_dir, 0755, true)) { 
            // Jika gagal membuat direktori, tambahkan logging atau pesan error
            error_log("Failed to create upload directory: " . $upload_dir . " Error: " . (error_get_last()['message'] ?? 'N/A'));
            // Jangan lanjutkan proses file_put_contents jika folder tidak bisa dibuat
            $error_saving_image = true; 
        }
    }

    if (!isset($error_saving_image)) { // Lanjutkan hanya jika tidak ada masalah pembuatan direktori
        $filename = $upload_dir . 'custom_' . time() . '.png';
        
        if (file_put_contents($filename, $imageData)) {
            $product_id = 0; // Atau ID produk template jika ada
            $quantity = 1;
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, custom_image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $user_id, $product_id, $quantity, $filename);
            $stmt->execute();
            $stmt->close(); // Tutup statement
    
            $popupSuccess = true;
        } else {
            // Tambahkan logging error jika file_put_contents gagal
            error_log("Failed to save custom image file_put_contents: " . $filename . " Error details: " . (error_get_last()['message'] ?? 'N/A'));
            // Atau tampilkan pesan error ke user jika perlu
            // echo "<script>alert('Gagal menyimpan gambar custom. Mohon coba lagi.');</script>";
        }
    }
}

$conn->close(); // Tutup koneksi setelah semua data diambil dan diproses
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Produk</title>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
    
    <link rel="stylesheet" href="assets/assets/style.css">
    <link rel="stylesheet" href="assets/assets/chart.css"> 

    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
    <style>
        /* === DEFINISI VARIABEL CSS (sesuai dengan style.css jika ada) === */
        :root {
            --bg-color: #fdfbfb; /* Warna background umum yang cerah */
            --text-color: #333; /* Warna teks gelap */
            --main-color: #333; /* Warna utama teks */
            --hover: #e91e63; /* Warna pink/merah untuk hover */
            --other-color: #707070;
            --big-font: 4.5rem;
            --h2-font: 3rem;
            --h3-font: 2rem;
            --normal-font: 1rem;
        }

        body {
            font-family: 'Jost', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color); /* Menggunakan variabel */
            color: var(--text-color); /* Menggunakan variabel */
        }

        /* --- HEADER === */
        header {
            position: fixed; /* Header tetap di atas saat scroll */
            top: 0;
            left: 0;
            width: 100%;
            background: var(--bg-color); /* Warna background putih/cerah sesuai tone */
            box-shadow: 0 0.1rem 0.5rem rgba(0, 0, 0, 0.1); /* Bayangan lembut di bawah header */
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between; /* Untuk mengatur jarak antara logo, menu, dan ikon */
            z-index: 1000; /* Pastikan header selalu di atas konten lain */
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            max-width: 160px; /* Lebar maksimum logo */
            height: auto;
        }

        .navmenu {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .navmenu a {
            font-size: var(--normal-font); /* Ukuran font standar */
            color: var(--main-color);
            font-weight: 500;
            margin: 0 25px; /* Jarak antar item menu */
            transition: all .45s ease;
            text-decoration: none; /* Hapus garis bawah */
        }

        .navmenu a:hover {
            color: var(--hover); /* Warna hover sesuai tema */
        }

        .custom-btn {
            background: var(--hover); /* Warna tombol sesuai tema */
            color: var(--bg-color) !important; /* Teks putih/cerah */
            padding: 0.8rem 1.5rem;
            border-radius: 0.5rem;
        }

        /* --- Search Box --- */
        .search-box {
            position: absolute;
            top: 100%; /* Posisi di bawah header */
            left: 0;
            width: 100%;
            background: var(--bg-color); /* Background putih */
            box-shadow: 0 0.1rem 0.5rem rgba(0, 0, 0, 0.1);
            padding: 10px 20px;
            display: none; /* Sembunyikan secara default */
            z-index: 999;
            box-sizing: border-box; /* Pastikan padding tidak menambah lebar total */
        }
        .search-box input {
            width: calc(100% - 50px);
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .search-box button {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--main-color);
        }

        /* --- Nav Icons --- */
        .nav-icon {
            display: flex;
            align-items: center;
        }
        .nav-icon i {
            font-size: 1.5rem; /* Ukuran ikon */
            color: var(--main-color);
            margin-left: 10px; /* Jarak antar ikon */
            transition: all .45s ease;
            cursor: pointer;
        }
        .nav-icon i:hover {
            color: var(--hover);
        }
        .nav-icon .cart-icon {
            position: relative;
        }
        .nav-icon .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--hover);
            color: var(--bg-color);
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.8rem;
            font-weight: 600;
            line-height: 1; /* Pastikan teks angka tidak terlalu tinggi */
        }

        /* === Mobile Nav (Dropdown dari Samping) === */
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -250px; /* Awalnya sembunyikan di luar layar */
            width: 250px; /* Lebar menu */
            height: 100%; /* Memanjang ke bawah sepenuh tinggi layar */
            background-color: white; /* Kotak warna putih */
            box-shadow: -2px 0 10px rgba(0,0,0,0.2); /* Bayangan jelas */
            transition: right 0.4s ease-in-out; /* Animasi geser */
            z-index: 1001; /* Z-index agar di atas overlay tapi di bawah header */
            padding: 0; /* Hapus padding default di sini */
            display: flex;
            flex-direction: column;
            justify-content: start;
            overflow-y: auto; /* Bisa discroll jika konten panjang */
        }

        .mobile-nav ul {
            list-style: none;
            padding: 0; /* Hapus padding default UL */
            margin: 0; /* Hapus margin default UL */
            padding-top: 80px; /* Jarak atas konten menu, agar tidak tertutup area header */
        }

        .mobile-nav ul li {
            margin-bottom: 0; /* Hapus margin bawah li */
        }

        .mobile-nav ul li a {
            text-decoration: none;
            color: var(--main-color); /* Warna teks hitam/gelap */
            font-size: 18px;
            font-weight: 500;
            transition: color 0.2s ease;
            display: block; /* Agar seluruh area a bisa diklik dan mengambil lebar penuh */
            padding: 12px 20px; /* Padding vertikal & horizontal, membuat tulisan lurus ke bawah */
            text-align: left; /* Teks rata kiri */
        }

        .mobile-nav ul li a:hover {
            color: var(--hover); /* Warna hover sesuai tema */
            background-color: #f5f5f5; /* Sedikit warna abu-abu saat hover */
        }

        .mobile-nav.active {
            right: 0; /* Tampilkan menu saat kelas 'active' ditambahkan */
        }

        /* === OVERLAY / BLUR EFFECT === */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            backdrop-filter: blur(8px); /* Blur lebih kuat */
            background: rgba(0, 0, 0, 0.4); /* Background semi-transparan (gelap) */
            display: none;
            z-index: 999; /* Di bawah mobile-nav */
            transition: background 0.4s ease-in-out, backdrop-filter 0.4s ease-in-out;
        }

        .overlay.show {
            display: block;
        }

        /* === RESPONSIVE ADJUSTMENTS for header and mobile nav === */
        @media (max-width: 991px) { /* Medium screens */
            header {
                padding: 15px 10px;
            }
            .navmenu a {
                margin: 0 15px;
            }
        }

        @media (max-width: 768px) { /* Small screens (mobile) */
            .navmenu {
                display: none; /* Sembunyikan menu utama desktop */
            }
            .bx-menu {
                display: block; /* Tampilkan ikon menu mobile */
            }
            header .logo img {
                max-width: 140px;
            }
            .nav-icon i {
                margin-left: 8px;
            }
            .mobile-nav {
                width: 70%; /* Lebih lebar di layar mobile kecil */
                right: -70%;
            }
            .mobile-nav.active {
                right: 0;
            }
        }
        /* --- END HEADER & NAVIGASI CSS --- */


        /* --- Custom.php specific styles --- */
        .container-custom {
            max-width: 800px;
            /* Margin-top disesuaikan agar tidak tertutup header fixed */
            margin: 80px auto 40px; 
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .canvas-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        canvas {
            border: 1px solid #ccc;
        }

        input, button, select {
            padding: 10px;
            margin: 5px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-family: 'Jost', sans-serif;
        }

        .submit-btn {
            background-color: #e91e63;
            color: #fff;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #c2185b;
        }

        /* === Popup Berhasil === */
        .popup-success {
            display: <?= $popupSuccess ? 'flex' : 'none' ?>;
            position: fixed;
            top: 0; left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.3);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .popup-box {
            background: #ffe9f0;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            animation: fadeIn 0.3s ease-in-out;
        }

        .popup-box h2 {
            color: #e91e63;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .popup-box p {
            font-size: 14px;
            margin-bottom: 20px;
        }

        .popup-box button {
            background-color: #ff5f99;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<header>
    <a href="home.php" class="logo" onclick="window.location.reload();"
       title="Aplikasi ini dikembangkan sebagai platform e-commerce khusus yang berfokus pada penjualan aksesori lucu dan trendi, dengan kurasi visual yang kuat serta fitur personalisasi yang memudahkan pengguna menemukan produk sesuai selera mereka. Selain itu, aplikasi ini juga membuka peluang kolaborasi dengan UMKM dan kreator lokal dalam mendistribusikan produk secara lebih luas dan profesional. Aplikasi ini tidak hanya berfungsi sebagai kanal transaksi, tetapi juga sebagai sarana interaksi dan ekspresi diri bagi konsumen, sekaligus menjadi media distribusi yang efisien bagi pelaku UMKM lokal di bidang aksesori dan perhiasan ringan.">
        <img src="assets/assets/image/logo.png" alt="Logo Jepit Aku Lucu" />
    </a>
    
    <ul class="navmenu">
        <li><a href="home.php">home</a></li>
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
        <i class='bx bx-menu' id="menu-icon"></i> <nav class="mobile-nav" id="mobileNav">
            <ul>
                <?php if (isset($user_type) && $user_type === 'penjual'): ?>
                <li><a href="seller/dashboard.php">Dashboard Penjual</a></li>
                <?php else: ?>
                <li><a href="home.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="Favorite.php">Favorite</a></li>
                <li><a href="customerorders.php">Pesanan Kamu</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="overlay" id="overlay"></div>
    </div>
</header>

<div class="container-custom">
    <h2>Editor Custom Produk</h2>

    <label for="product-template">Pilih Produk:</label>
    <select id="product-template" onchange="changeTemplate()">
        <option value="">-- Pilih Produk --</option>
        <option value="totebag.jpg">Tote Bag</option>
        </select><br><br>


    <div class="canvas-wrapper">
        <canvas id="canvas" width="500" height="500"></canvas>
    </div>

    <label>Upload Gambar Desain Anda:</label><br>
    <input type="file" id="upload-image" accept="image/*"><br><br>

    <input type="text" id="add-text" placeholder="Tulis teks...">
    
    <label for="font-color">Warna Teks:</label>
    <select id="font-color">
        <option value="#e91e63">Pink</option>
        <option value="#000000">Hitam</option>
        <option value="#ffffff">Putih</option>
        <option value="#ff0000">Merah</option>
        <option value="#2196F3">Biru</option>
        <option value="#4CAF50">Hijau</option>
    </select>

    <label for="font-family">Font:</label>
    <select id="font-family">
        <option value="Arial">Arial</option>
        <option value="Courier New">Courier New</option>
        <option value="Georgia">Georgia</option>
        <option value="Times New Roman">Times New Roman</option>
        <option value="Verdana">Verdana</option>
    </select><br><br>


    <button onclick="addText()">Tambah Teks</button>
    <button onclick="deleteSelected()">Hapus Yang Dipilih</button><br><br>

    <form method="POST" onsubmit="prepareImageData()" id="saveForm">
        <input type="hidden" name="canvas_data" id="canvas_data">
        <button type="submit" class="submit-btn">Masukan keranjang</button>
    </form>

    <br>
    <a href="home.php">
        <button class="submit-btn">Selesai Custom</button>
    </a>
</div>

<div class="popup-success" id="popup">
    <div class="popup-box">
        <h2>Berhasil!</h2>
        <p>Produk ditambahkan ke keranjang sebanyak 1 item.</p>
        <button onclick="document.getElementById('popup').style.display='none'">OK</button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
<script>
    const canvas = new fabric.Canvas('canvas');

    function loadTemplate(filename) {
        const path = 'assets/assets/image/' + filename;
        // Pastikan filename tidak kosong jika dipanggil onload
        if (!filename || filename === '') {
            // Jika tidak ada template yang dipilih atau filename kosong, bersihkan kanvas
            canvas.clear();
            // Opsional: set background putih jika tidak ada gambar
            canvas.setBackgroundColor('#ffffff', canvas.renderAll.bind(canvas));
            return;
        }

        fabric.Image.fromURL(path, function(img) {
            // Hitung skala agar gambar mengisi kanvas namun tetap proporsional
            const scaleFactor = Math.min(canvas.width / img.width, canvas.height / img.height);
            img.set({
                selectable: false,
                evented: false,
                left: (canvas.width - img.width * scaleFactor) / 2, // Pusatkan secara horizontal
                top: (canvas.height - img.height * scaleFactor) / 2,  // Pusatkan secara vertikal
                scaleX: scaleFactor,
                scaleY: scaleFactor
            });
            canvas.clear();
            canvas.add(img);
            img.sendToBack();
        }, { crossOrigin: 'anonymous' }); // Penting untuk gambar dari sumber lain atau jika canvas diupload
    }

    function changeTemplate() {
        const selectedFile = document.getElementById('product-template').value;
        loadTemplate(selectedFile);
    }

    // Perbaikan: Panggil loadTemplate tanpa argumen saat awal, agar tidak ada gambar default
    // Jika Anda ingin ada gambar default, ganti 'null' dengan 'namafiledefault.jpg'
    window.onload = function() {
        loadTemplate(''); // Muat template kosong atau default saat halaman dimuat
        // Tampilkan popup jika ada
        const popup = document.getElementById('popup');
        if (popup && <?= $popupSuccess ? 'true' : 'false' ?>) {
            popup.style.display = 'flex';
        }
    }

    document.getElementById('upload-image').addEventListener('change', function (e) {
        const reader = new FileReader();
        reader.onload = function (f) {
            const data = f.target.result;
            fabric.Image.fromURL(data, function (img) {
                // Skala gambar yang diupload agar tidak terlalu besar
                img.scaleToWidth(canvas.width * 0.5); // Skala 50% dari lebar kanvas
                canvas.add(img).setActiveObject(img);
                img.center().setCoords(); // Pusatkan gambar dan update koordinat
            }, { crossOrigin: 'anonymous' });
        };
        if (e.target.files[0]) { // Pastikan ada file yang dipilih
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    function addText() {
        const value = document.getElementById('add-text').value;
        const color = document.getElementById('font-color').value;
        const font = document.getElementById('font-family').value;

        if (!value) return;

        const text = new fabric.Text(value, {
            left: 50, // Atur posisi awal agar tidak terlalu dekat tepi
            top: 50,
            fontSize: 30, // Ukuran font yang lebih terlihat
            fill: color,
            fontFamily: font
        });

        canvas.add(text).setActiveObject(text);
        canvas.centerObject(text); // Pusatkan teks saat ditambahkan
        text.setCoords(); // Update koordinat objek
    }


    function deleteSelected() {
        const activeObject = canvas.getActiveObject();
        if (activeObject) {
            canvas.remove(activeObject);
        }
    }

    function prepareImageData() {
        // Render canvas ke PNG data URL
        const dataURL = canvas.toDataURL({
            format: 'png',
            multiplier: 2 // Tingkatkan resolusi untuk kualitas lebih baik
        });
        document.getElementById('canvas_data').value = dataURL;
    }
</script>

<script src="assets/assets/java.js"></script> 
<script>
    // Fungsi untuk dropdown menu utama (jika ada)
    function toggleDropdown(event) {
        event.preventDefault();
        const dropdownMenu = document.getElementById("dropdownMenu");
        if (dropdownMenu) { 
            dropdownMenu.classList.toggle("show");
        }
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

    if (searchIcon && searchBox) { 
        searchIcon.addEventListener('click', () => {
            searchBox.style.display = searchBox.style.display === 'block' ? 'none' : 'block';
            document.getElementById('searchInput').focus();
        });
    }

    function closeSearch() {
        if (searchBox) { 
            searchBox.style.display = 'none';
        }
    }

    function searchProduct() {
        var input = document.getElementById("searchInput");
        if (!input) return;
        var filter = input.value.toLowerCase();
        var productsContainer = document.querySelector(".products"); // Ini mungkin tidak ada di custom.php
        if (!productsContainer) return;

        var products = productsContainer.querySelectorAll(".row");

        products.forEach(function(product) {
            var productNameElement = product.querySelector(".price h4");
            if (productNameElement) {
                var productName = productNameElement.innerText.toLowerCase();
                if (productName.includes(filter)) {
                    product.style.display = "block";
                } else {
                    product.style.display = "none";
                }
            }
        });
    }

    function checkEnter(event) {
        if (event.key === "Enter") {
            searchProduct();
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', searchProduct);
            searchInput.addEventListener('keypress', checkEnter);
        }
    });
</script>

<script>
    // Fungsi saveFavorite tidak relevan di custom.php kecuali Anda memiliki tombol favorite di sini
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

    if (menuIcon && mobileNav && overlay) { 
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
    }
</script>

</body>
</html>