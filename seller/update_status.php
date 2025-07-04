<?php
session_start();
include '../config/config.php'; // Pastikan path ini benar untuk koneksi database

// Pastikan user sudah login, merupakan penjual, dan metode request adalah POST
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'penjual' || $_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: ../login.php"); // Redirect ke halaman login atau halaman lain yang sesuai
  exit();
}

// Ambil data dari POST
$order_id = $_POST['order_id'] ?? null;
$new_status = $_POST['status'] ?? null;
$action_type = $_POST['action_type'] ?? null; // Menambahkan variabel untuk tipe aksi
$seller_id = $_SESSION['user_id'];

// Validasi input
if (!isset($order_id)) { // order_id harus selalu ada
    header("Location: orders.php?error=invalid_input");
    exit();
}

// Daftar status yang diizinkan untuk mencegah injection atau nilai yang tidak valid
$allowed_statuses = ['pending', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];

// Langkah Verifikasi: Pastikan pesanan ini relevan untuk penjual yang login.
$is_order_relevant = false;
$stmt_check_relevance = $conn->prepare("
    SELECT COUNT(DISTINCT oi.order_id) as count_relevant_orders
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ? AND (p.seller_id = ? OR oi.product_id IS NULL)
");
$stmt_check_relevance->bind_param("ii", $order_id, $seller_id);
$stmt_check_relevance->execute();
$result_check = $stmt_check_relevance->get_result();
$row_check = $result_check->fetch_assoc();

if ($row_check['count_relevant_orders'] > 0) {
    $is_order_relevant = true;
}
$stmt_check_relevance->close();


if ($is_order_relevant) {
    // Logika untuk menghapus pesanan
    if ($action_type === 'delete_order') {
        // PERINGATAN: Ini akan menghapus data secara PERMANEN!
        // Mulai transaksi untuk memastikan kedua operasi (delete items dan delete order) berhasil atau gagal bersama
        $conn->begin_transaction();

        try {
            // Hapus item-item pesanan terkait terlebih dahulu
            $stmt_delete_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt_delete_items->bind_param("i", $order_id);
            if (!$stmt_delete_items->execute()) {
                throw new Exception("Gagal menghapus item pesanan.");
            }
            $stmt_delete_items->close();

            // Kemudian hapus pesanan itu sendiri
            $stmt_delete_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $stmt_delete_order->bind_param("i", $order_id);
            if (!$stmt_delete_order->execute()) {
                throw new Exception("Gagal menghapus pesanan.");
            }
            $stmt_delete_order->close();

            $conn->commit();
            $_SESSION['message'] = "Pesanan dan item terkait berhasil dibatalkan dan dihapus.";
            header("Location: orders.php?success=order_deleted");
            exit();

        } catch (Exception $e) {
            $conn->rollback(); // Batalkan semua operasi jika ada yang gagal
            error_log("Error deleting order: " . $e->getMessage());
            $_SESSION['error'] = "Gagal membatalkan dan menghapus pesanan: " . $e->getMessage();
            header("Location: orders.php?error=delete_failed");
            exit();
        }
    }
    // Logika standar untuk update status (jika bukan aksi hapus)
    else {
        if (!isset($new_status) || !in_array($new_status, $allowed_statuses)) {
            header("Location: orders.php?error=invalid_status");
            exit();
        }

        // Anda bisa menambahkan logika validasi transisi status di sini jika diperlukan
        // Contoh: $current_status_query = $conn->prepare("SELECT status FROM orders WHERE id = ?"); ...
        // Jika status saat ini adalah 'selesai' atau 'dibatalkan', jangan izinkan perubahan

        $query_update = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param("si", $new_status, $order_id);

        if ($stmt_update->execute()) {
            $_SESSION['message'] = "Status pesanan berhasil diperbarui menjadi " . htmlspecialchars($new_status);
            header("Location: orders.php?success=status_updated");
        } else {
            error_log("Error updating order status: " . $conn->error);
            $_SESSION['error'] = "Gagal memperbarui status pesanan: " . $conn->error;
            header("Location: orders.php?error=update_failed");
        }
        $stmt_update->close();
    }
} else {
    // Pesanan tidak relevan untuk penjual ini, atau tidak ada produk penjual di dalamnya
    $_SESSION['error'] = "Akses ditolak atau pesanan tidak ditemukan.";
    header("Location: orders.php?error=unauthorized_order");
}

$conn->close();
exit();
?>