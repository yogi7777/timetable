<?php
session_start();
if (!isset($_SESSION['user_id'])) exit;

include 'db.php';
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_id = $_POST['dept_id'];
    $action = $_POST['action'];

    if ($action === 'start') {
        $stmt = $db->prepare("INSERT INTO timers (user_id, department_id, start_time) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $dept_id]);
        echo json_encode(['status' => 'started']);
    } elseif ($action === 'stop') {
        $stmt = $db->prepare("UPDATE timers SET end_time = NOW(), duration = TIMESTAMPDIFF(SECOND, start_time, NOW()) WHERE user_id = ? AND department_id = ? AND end_time IS NULL");
        $stmt->execute([$user_id, $dept_id]);
        echo json_encode(['status' => 'stopped']);
    }
}