<?php
session_start();
include '../config/config.php'; // Pastikan path ini benar untuk koneksi database

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'penjual') { // Tambah cek user_type
  header("Location: ../login.php");
  exit();
}

$seller_id = $_SESSION['user_id']; //

// Ambil produk dari tabel 'products' yang dimiliki oleh seller_id yang sedang login
$stmt = $conn->prepare("SELECT id, name, price, description, stock, category, image FROM products WHERE seller_id = ?"); //
$stmt->bind_param("i", $seller_id); //
$stmt->execute(); //
$result = $stmt->get_result(); //
$conn->close(); // Tutup koneksi setelah semua data diambil
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Produk Saya</title>
  <link rel="stylesheet" href="../assets/assets/seller.css">
  <link rel="stylesheet" href="../assets/assets/sellerproducts.css">
  <style>
    /* Tambahan CSS jika diperlukan untuk tampilan gambar atau tata letak */
    .product-card img {
        width: 150px; /* Atur lebar gambar */
        height: 150px; /* Atur tinggi gambar */
        object-fit: cover; /* Pastikan gambar mengisi area tanpa terdistorsi */
        border-radius: 8px;
        margin-bottom: 10px;
    }
    .product-card {
        display: flex;
        flex-direction: column; /* Ubah menjadi column agar gambar di atas info */
        align-items: center; /* Pusatkan item horizontal */
        border: 1px solid #eee;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        background-color: #fff;
    }
    .product-info {
        text-align: center; /* Pusatkan teks info produk */
        width: 100%; /* Agar info produk mengambil seluruh lebar card */
    }
    .product-actions {
        margin-top: 15px;
        display: flex;
        gap: 10px; /* Memberi jarak antar tombol */
        justify-content: center; /* Pusatkan tombol */
    }
    .btn-edit, .btn-delete {
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.9em;
        transition: background-color 0.3s ease;
    }
    .btn-edit {
        background-color: #007bff;
        color: white;
    }
    .btn-edit:hover {
        background-color: #0056b3;
    }
    .btn-delete {
        background-color: #dc3545;
        color: white;
    }
    .btn-delete:hover {
        background-color: #c82333;
    }
    .seller-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 20px;
        background-color: #f8f8f8;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    h2 {
        text-align: center;
        color: #e91e63;
        margin-bottom: 30px;
    }
  </style>
</head>
<body>
  <div class="seller-container">
    <h2>📦 Produk Saya</h2>

    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="product-card">
          <?php
          // Nilai dari $row['image'] sudah seperti 'assets/assets/image/katalog1.png'
          // sellerproducts.php ada di 'seller/'
          // Jadi kita perlu '../' untuk naik satu level ke folder root 'PPSI/'
          // lalu digabungkan dengan path dari database.
          // Pastikan $row['image'] tidak kosong sebelum mencoba menampilkan gambar
          $full_image_path_for_html = '';
          if (!empty($row['image'])) {
              $full_image_path_for_html = '../' . htmlspecialchars($row['image']);
          } else {
              // Jika path gambar kosong, gunakan placeholder atau tampilkan pesan
              // Ganti dengan path gambar placeholder Anda yang valid jika diperlukan
              $full_image_path_for_html = '../assets/assets/image/placeholder.png'; // Contoh placeholder
          }
          ?>
          <img src="<?= $full_image_path_for_html ?>" alt="<?= htmlspecialchars($row['name']) ?>">
          <div class="product-info">
            <h4><?= htmlspecialchars($row['name']) ?></h4>
            <p>Rp <?= number_format($row['price'], 0, ',', '.') ?></p>
            <p><?= htmlspecialchars($row['description']) ?></p>
            <p>Stok: <?= $row['stock'] ?></p>
            <p>Kategori: <?= htmlspecialchars($row['category']) ?></p>
            <div class="product-actions">
                <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn-edit">✏️ Edit</a>
                <a href="delete_product.php?id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus produk ini? Ini akan juga mempengaruhi pesanan terkait.')">🗑️ Hapus</a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>Belum ada produk. <a href="add_product.php">Tambah Produk Baru</a></p>
    <?php endif; ?>
  </div>
</body>
</html>