<?php
include 'db.php';
require_once 'auth.php';
checkAuth();

include 'db.php';
$user_id = $_SESSION['user_id'];

$order = json_decode($_POST['order'], true);
$stmt = $db->prepare("INSERT INTO user_settings (user_id, button_order) VALUES (?, ?) ON DUPLICATE KEY UPDATE button_order = ?");
$stmt->execute([$user_id, json_encode($order), json_encode($order)]);
echo "Order saved: " . json_encode($order); // Debugging