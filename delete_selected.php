<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

if (!empty($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
  $user_id = $_SESSION['user_id'];
  $ids = $_POST['delete_ids'];

  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $types = str_repeat('i', count($ids)) . 'i';

  $sql = "DELETE FROM cart WHERE id IN ($placeholders) AND user_id = ?";
  $stmt = $conn->prepare($sql);
  $params = [...$ids, $user_id];

  $stmt->bind_param($types, ...$params);
  $stmt->execute();
}

header("Location: chart.php");
exit();
