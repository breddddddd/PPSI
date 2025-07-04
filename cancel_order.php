<?php
error_reporting(E_ALL); // Aktifkan semua pelaporan error PHP
ini_set('display_errors', 1); // Tampilkan error di browser (Hanya untuk DEVELOPMENT!)

session_start();
include 'config/config.php'; // Pastikan path ini benar sesuai lokasi file config Anda

// Redirect jika pengguna belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Pastikan order_id diterima melalui GET
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    $_SESSION['error'] = "ID Pesanan tidak valid.";
    header("Location: customerorders.php");
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Logging untuk debugging: Mencatat percobaan pembatalan/penghapusan
error_log("DEBUG: Attempting to delete order_id: " . $order_id . " by user_id: " . $user_id);

// Mulai transaksi database
$conn->begin_transaction();

try {
    // 1. Periksa apakah pesanan ini milik pengguna yang sedang login
    //    dan apakah statusnya memungkinkan untuk dihapus.
    //    Gunakan FOR UPDATE untuk mengunci baris agar tidak ada perubahan konkuren.
    $stmt = $conn->prepare("SELECT status, user_id FROM orders WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        // Logging jika pesanan tidak ditemukan atau tidak cocok dengan user_id
        error_log("ERROR: Order " . $order_id . " not found or does not belong to user " . $user_id . " during first check.");
        throw new Exception("Pesanan tidak ditemukan atau Anda tidak memiliki izin untuk menghapusnya.");
    }

    // Double check user_id matching even if FOR UPDATE fetched it
    if ($order['user_id'] != $user_id) {
        error_log("ERROR: User " . $user_id . " attempted to delete order " . $order_id . " which belongs to user " . $order['user_id'] . ".");
        throw new Exception("Anda tidak memiliki izin untuk menghapus pesanan ini.");
    }

    $current_status = $order['status'];

    // Izinkan penghapusan hanya untuk status 'pending'
    // Pertimbangkan baik-baik apakah Anda ingin mengizinkan penghapusan untuk status 'shipping'.
    // Umumnya, pesanan yang sudah dalam pengiriman tidak boleh dihapus begitu saja.
    if ($current_status != 'pending') {
        error_log("WARNING: Order " . $order_id . " (status: " . $current_status . ") cannot be deleted.");
        throw new Exception("Pesanan dengan status '" . htmlspecialchars($current_status) . "' tidak dapat dihapus.");
    }

    // 2. (Opsional, tapi sangat disarankan) Kembalikan stok produk jika ada manajemen stok
    //    Anda harus mengambil item_id dan quantity dari order_items yang terkait dengan order_id ini.
    //    Kemudian perbarui stok di tabel produk yang relevan SEBELUM menghapus pesanan.
    //    Jika Anda menghapus order_items lebih dulu, Anda akan kehilangan informasi ini.

    // Contoh pseudo-code untuk mengembalikan stok (anda harus menyesuaikannya dengan skema DB anda)
    // Asumsikan tabel order_items memiliki foreign key ke tabel orders
    $stmt_items = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $items_to_restore = $stmt_items->get_result();
    while ($item = $items_to_restore->fetch_assoc()) {
        // Asumsikan 'products' adalah tabel produk Anda dengan kolom 'stock' dan 'id'
        $update_stock_stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $update_stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        if (!$update_stock_stmt->execute()) {
             error_log("ERROR: Failed to restore stock for product " . $item['product_id'] . " before deleting order: " . $update_stock_stmt->error);
             // Anda bisa memilih untuk melempar exception di sini agar transaksi di-rollback
             // throw new Exception("Gagal mengembalikan stok produk.");
        }
        $update_stock_stmt->close();
    }
    $stmt_items->close();


    // 3. Hapus item pesanan terkait dari tabel 'order_items' (jika Anda memiliki tabel ini)
    //    Ini penting jika Anda memiliki constraint foreign key ON DELETE CASCADE atau jika Anda ingin membersihkan data terkait.
    $stmt_delete_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt_delete_items->bind_param("i", $order_id);
    if (!$stmt_delete_items->execute()) {
        error_log("ERROR: Failed to delete order items for order_id " . $order_id . ": " . $stmt_delete_items->error);
        throw new Exception("Gagal menghapus detail pesanan.");
    }
    $stmt_delete_items->close();

    // 4. Hapus pesanan dari tabel 'orders'
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);

    if (!$stmt->execute()) {
        // Logging jika query delete gagal
        error_log("ERROR: Failed to delete order for order_id " . $order_id . ": " . $stmt->error);
        throw new Exception("Gagal menghapus pesanan: " . $stmt->error);
    }

    // Periksa apakah ada baris yang terpengaruh (artinya delete berhasil)
    if ($stmt->affected_rows === 0) {
        // Ini bisa terjadi jika pesanan sudah dihapus atau user_id tidak cocok
        error_log("WARNING: No rows affected by DELETE query for order_id " . $order_id . ". Order might have already been deleted or user_id mismatch.");
        throw new Exception("Pesanan mungkin sudah dihapus atau tidak ditemukan.");
    }
    $stmt->close();

    $conn->commit(); // Commit transaksi jika semua berhasil
    $_SESSION['message'] = "Pesanan ID #" . htmlspecialchars($order_id) . " berhasil dihapus.";
    // Logging sukses
    error_log("INFO: Order ID #" . $order_id . " successfully deleted and committed.");
    header("Location: customerorders.php"); // Redirect ke halaman daftar pesanan
    exit();

} catch (Exception $e) {
    $conn->rollback(); // Rollback transaksi jika ada error
    $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    // Logging error yang menyebabkan rollback
    error_log("FATAL: Transaction rolled back for order_id " . $order_id . ". Error: " . $e->getMessage());
    header("Location: customerorders.php"); // Redirect ke halaman daftar pesanan dengan pesan error
    exit();
} finally {
    // Pastikan koneksi ditutup hanya jika masih valid
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>