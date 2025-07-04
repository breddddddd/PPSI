<?php
session_start();
include 'config/config.php'; // Pastikan path ini benar

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data pengiriman dari form
    $receiver_name = $_POST['receiver_name'] ?? '';
    $shipping_address = $_POST['shipping_address'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $total_amount = $_POST['total_amount'] ?? 0;
    $order_items_data = $_POST['items'] ?? []; // Data item dari hidden input di checkout.php

    $conn->begin_transaction(); // Mulai transaksi untuk memastikan atomicity

    try {
        // 1. Masukkan data pesanan ke tabel 'orders'
        // Kolom: user_id, order_date, total_amount, status, receiver_name, shipping_address, phone_number
        $stmt_order = $conn->prepare("INSERT INTO orders (user_id, order_date, total_amount, status, receiver_name, shipping_address, phone_number) VALUES (?, NOW(), ?, ?, ?, ?, ?)");
        $status = 'pending'; // Status awal pesanan

        // Parameter binding: i (int) untuk user_id, d (double) untuk total_amount, s (string) untuk status, receiver_name, shipping_address, phone_number
        $stmt_order->bind_param("idssss", $user_id, $total_amount, $status, $receiver_name, $shipping_address, $phone_number);

        if (!$stmt_order->execute()) {
            throw new Exception("Gagal menyimpan pesanan: " . $stmt_order->error);
        }
        $order_id = $stmt_order->insert_id; // Dapatkan ID pesanan yang baru saja dibuat
        $stmt_order->close();

        // 2. Masukkan detail item pesanan ke tabel 'order_items'
        // Kolom: order_id, product_id, item_name, item_price, quantity, custom_image
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, item_name, item_price, quantity, custom_image) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($order_items_data as $item) {
            // product_id bisa null jika produk kustom, pastikan penanganannya benar
            $product_id = !empty($item['product_id']) ? $item['product_id'] : null;
            $item_name = $item['name'];
            $item_price = $item['price'];
            $quantity = $item['quantity'];
            $custom_image = !empty($item['custom_image']) ? $item['custom_image'] : null;

            // Bind parameter
            // Tipe data untuk bind_param:
            // i = integer, d = double (float), s = string, b = blob
            // Untuk product_id yang bisa NULL, kita bisa bind sebagai 'i' jika integer, atau 's' jika NULL.
            // Metode yang lebih robust untuk NULL: menggunakan `bind_param` dengan tipe 's' dan passing NULL,
            // atau mempersiapkan statement dengan SET @p1 = NULL, lalu bind.
            // Untuk kesederhanaan, asumsikan MySQLi akan mengonversi NULL dengan benar untuk kolom INT (meskipun kadang bisa jadi 0).
            // Jika ada masalah, pastikan kolom `product_id` di `order_items` bisa NULL.
            $stmt_item->bind_param("iisids", $order_id, $product_id, $item_name, $item_price, $quantity, $custom_image);

            if (!$stmt_item->execute()) {
                throw new Exception("Gagal menyimpan item pesanan: " . $stmt_item->error);
            }
        }
        $stmt_item->close();


        // 3. Hapus item dari keranjang setelah pesanan berhasil dibuat
        $stmt_clear_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt_clear_cart->bind_param("i", $user_id);
        if (!$stmt_clear_cart->execute()) {
            throw new Exception("Gagal membersihkan keranjang: " . $stmt_clear_cart->error);
        }
        $stmt_clear_cart->close();

        $conn->commit(); // Commit transaksi jika semua berhasil
        $_SESSION['message'] = "Pesanan berhasil dibuat! ID Pesanan Anda: #" . $order_id;
        header("Location: success.php"); // Redirect ke halaman sukses
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaksi jika ada error
        $_SESSION['error'] = "Terjadi kesalahan saat memproses pesanan: " . $e->getMessage();
        header("Location: checkout.php"); // Kembali ke halaman checkout dengan pesan error
        exit();
    }
} else {
    // Jika diakses langsung tanpa POST, redirect ke halaman keranjang atau home
    header("Location: cart.php"); // Atau ke home.php
    exit();
}

$conn->close();
?>