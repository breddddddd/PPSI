<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['product_id'])) {
    header("Location: Favorite.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

// Hapus data dari tabel favorites
$stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();

// Kembali ke halaman Favorite
header("Location: Favorite.php");
exit();
?>
