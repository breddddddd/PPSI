<?php
session_start();
include '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'penjual') { // Pastikan hanya penjual yang bisa akses
  header("Location: ../login.php");
  exit();
}

$seller_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Proses tambah produk
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = $_POST['name'];
  $category = $_POST['category'];
  $description = $_POST['description'];
  $price = $_POST['price'];
  $stock = $_POST['stock'];

  // Definisikan nilai default untuk status
  $status = "Normal";

  // Upload gambar
  $image_path = "";
  if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0755, true);
    }

    $filename = time() . '_' . basename($_FILES['image']['name']);
    $target_path = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
      // Path relatif yang akan disimpan di database
      $image_path = 'uploads/' . $filename;
    } else {
      $error = "Gagal mengunggah gambar.";
    }
  }

  if (!$error) {
    // KUNCI PERUBAHAN: Tambahkan 'seller_id' ke dalam query INSERT
    $stmt = $conn->prepare("INSERT INTO products (name, category, description, price, image, stock, status, seller_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // KUNCI PERUBAHAN: Tambahkan 'i' untuk integer seller_id dan variabel $seller_id
    $stmt->bind_param("sssdsisi", $name, $category, $description, $price, $image_path, $stock, $status, $seller_id);

    if ($stmt->execute()) {
      // Produk berhasil ditambahkan!
      // AMBIL ID PRODUK YANG BARU SAJA DI-INSERT
      $new_product_id = $conn->insert_id; // Ini akan mengambil ID dari baris yang baru saja dimasukkan

      // REDIRECT KE HALAMAN DETAIL PRODUK BARU
      header("Location: ../productdetail.php?id=" . $new_product_id); //
      exit(); // Sangat penting untuk menghentikan eksekusi skrip setelah header redirect

    } else {
      $error = "Gagal menambahkan produk: " . $stmt->error;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Produk</title>
  <link rel="stylesheet" href="../assets/assets/seller.css">
  <link rel="stylesheet" href="../assets/assets/addproduct.css">
</head>
<body>
  <div class="add-container">
    <h2>➕ Tambah Produk Baru</h2>

    <?php if ($error): ?>
      <p class="error-msg"><?= $error ?></p>
    <?php elseif ($success): ?>
      <p class="success-msg"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="add-form">
      <div class="form-group">
        <label for="name">Nama Produk</label>
        <input type="text" name="name" id="name" required>
      </div>

      <div class="form-group">
        <label for="category">Kategori</label>
        <select name="category" id="category" required>
            <option value="Gelang">Gelang</option>
            <option value="Kalung">Kalung</option>
            <option value="Jepit">Jepit</option>
            <option value="Aksesoris Lainnya">Aksesoris Lainnya</option>
        </select>
      </div>

      <div class="form-group">
        <label for="description">Deskripsi</label>
        <textarea name="description" id="description" required></textarea>
      </div>

      <div class="form-group">
        <label for="price">Harga</label>
        <input type="number" name="price" id="price" step="0.01" required>
      </div>

      <div class="form-group">
        <label for="image">Gambar Produk</label>
        <input type="file" name="image" id="image" accept="image/*" required>
      </div>

      <div class="form-group">
          <label for="stock">Stok</label>
          <input type="number" name="stock" id="stock" required>
      </div>
      
      <button type="submit">Tambah Produk</button>
    </form>
  </div>
</body>
</html>