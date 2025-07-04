<?php
session_start();
include 'config/config.php';

$total = 0;
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $stmt->bind_result($total);
  $stmt->fetch();
}
echo $total;
