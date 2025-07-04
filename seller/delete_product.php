<?php
session_start();
include '../config/config.php'; // Pastikan path ini benar untuk koneksi database

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'penjual' || !isset($_GET['id'])) { // Menambah cek user_type
  header("Location: ../login.php");
  exit();
}

$product_id = $_GET['id']; // ID produk yang akan dihapus, berasal dari products.id
$seller_id = $_SESSION['user_id'];

// Mulai transaksi untuk memastikan konsistensi data
$conn->begin_transaction();

try {
    // 1. Hapus entri terkait di tabel 'cart'
    // Tabel 'cart' memiliki kolom 'product_id', jadi ini benar
    $stmt_cart = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
    $stmt_cart->bind_param("i", $product_id);
    $stmt_cart->execute();
    $stmt_cart->close();

    // 2. TENTANG TABEL 'ORDERS':
    // Berdasarkan screenshot, tabel 'orders' TIDAK memiliki kolom 'product_id'.
    // Oleh karena itu, kita TIDAK BISA langsung menghapus dari tabel 'orders' berdasarkan 'product_id'.
    // Jika Anda memiliki tabel 'order_items' (yang mengaitkan orders dengan products),
    // Anda harus menghapus dari 'order_items' terlebih dahulu.
    // Jika tidak ada tabel 'order_items', maka penghapusan produk tidak secara langsung menghapus data di 'orders'.
    // Jika Anda ingin mengupdate status order jika produk dihapus, itu memerlukan logika yang berbeda.
    // Untuk saat ini, baris yang menyebabkan error dihilangkan.

    // 3. Hapus produk dari tabel 'products'
    // Menggunakan 'id' sebagai primary key dan 'seller_id' untuk memastikan penjual yang berhak
    $stmt_products = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
    $stmt_products->bind_param("ii", $product_id, $seller_id);
    $stmt_products->execute();

    // Periksa apakah produk berhasil dihapus (milik seller yang login)
    if ($stmt_products->affected_rows > 0) {
        $conn->commit(); // Commit transaksi jika semua berhasil
        header("Location: sellerproducts.php?success=Produk berhasil dihapus!");
        exit();
    } else {
        $conn->rollback(); // Rollback jika tidak ada produk yang dihapus (mungkin ID tidak valid atau bukan milik seller)
        header("Location: sellerproducts.php?error=Produk tidak ditemukan atau Anda tidak memiliki izin untuk menghapus produk ini.");
        exit();
    }

    $stmt_products->close();
} catch (mysqli_sql_exception $e) {
    $conn->rollback(); // Rollback jika ada error SQL lainnya
    header("Location: sellerproducts.php?error=Terjadi kesalahan database: " . $e->getMessage());
    exit();
} finally {
    $conn->close(); // Tutup koneksi di akhir
}