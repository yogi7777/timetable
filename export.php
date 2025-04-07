<?php
session_start();
if (!isset($_SESSION['user_id'])) exit;

include 'db.php';
$user_id = $_SESSION['user_id'];

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="timer_export_' . date('Y-m') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Abteilung', 'Kostenstelle', 'Zeit Start', 'Zeit Ende', 'Zeit gesamt']);

$stmt = $db->prepare("SELECT d.name, d.cost_center, t.start_time, t.end_time, t.duration 
                     FROM timers t 
                     JOIN departments d ON t.department_id = d.id 
                     WHERE t.user_id = ? AND MONTH(t.start_time) = MONTH(NOW())");
$stmt->execute([$user_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $duration = gmdate("H:i:s", $row['duration']);
    fputcsv($output, [$row['name'], $row['cost_center'], $row['start_time'], $row['end_time'], $duration]);
}
fclose($output);
exit;