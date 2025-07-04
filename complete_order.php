<?php
session_start();
include 'config/config.php'; // Sesuaikan path jika perlu

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] !== "GET" || !isset($_GET['order_id'])) {
    header("Location: customerorders.php"); // Redirect jika tidak valid
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Verifikasi kepemilikan pesanan: Pastikan order_id ini milik user yang sedang login
$stmt_check_ownership = $conn->prepare("SELECT COUNT(*) FROM orders WHERE id = ? AND user_id = ? AND status = 'dikirim'");
$stmt_check_ownership->bind_param("ii", $order_id, $user_id);
$stmt_check_ownership->execute();
$result_ownership = $stmt_check_ownership->get_result();
$row_ownership = $result_ownership->fetch_assoc();

if ($row_ownership['COUNT(*)'] > 0) {
    // Jika pesanan valid dan milik user, serta statusnya 'dikirim', lakukan update
    $stmt_update = $conn->prepare("UPDATE orders SET status = 'selesai' WHERE id = ?");
    $stmt_update->bind_param("i", $order_id);

    if ($stmt_update->execute()) {
        $_SESSION['message'] = "Pesanan berhasil diselesaikan!";
    } else {
        $_SESSION['error'] = "Gagal menyelesaikan pesanan. Silakan coba lagi.";
    }
    $stmt_update->close();
} else {
    $_SESSION['error'] = "Pesanan tidak valid atau tidak dapat diselesaikan saat ini.";
}

$stmt_check_ownership->close();
$conn->close();

header("Location: customerorders.php"); // Kembali ke halaman pesanan customer
exit();
?>