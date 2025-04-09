<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';
$user_id = $_SESSION['user_id'];

// Benutzername abrufen
$stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$username = $stmt->fetchColumn();

// Abteilungen abrufen
$stmt = $db->query("SELECT * FROM departments");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$dept_map = array_column($departments, null, 'id');

// Button-Reihenfolge abrufen
$stmt = $db->prepare("SELECT button_order FROM user_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$order_json = $stmt->fetchColumn();
$order = $order_json ? json_decode($order_json, true) : array_column($departments, 'id');

// Aktive Timer abrufen
$stmt = $db->prepare("SELECT department_id, UNIX_TIMESTAMP(start_time) as start_ts 
                     FROM timers WHERE user_id = ? AND end_time IS NULL");
$stmt->execute([$user_id]);
$active_timers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$active_timer_map = array_column($active_timers, 'start_ts', 'department_id');
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Timer - <?php echo htmlspecialchars($username); ?></h1>
        <br /><br />
        <div id="buttons" class="button-grid">
            <?php foreach ($order as $dept_id): ?>
                <?php if (isset($dept_map[$dept_id])): $dept = $dept_map[$dept_id]; ?>
                    <div class="button <?php if (isset($active_timer_map[$dept['id']])) echo 'running'; ?>" 
                         data-id="<?php echo $dept['id']; ?>" 
                         data-start="<?php echo $active_timer_map[$dept['id']] ?? 0; ?>" 
                         draggable="true">
                        <span class="dept-name"><?php echo htmlspecialchars($dept['name']); ?></span>
                        <span class="timer-display">00:00:00</span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="nav-bottom-position">
        <a class="nav-bottom" href="logout.php">Logout</a>
        <a class="nav-bottom" href="dashboard.php">Dashboard</a>
        <?php if ($_SESSION['is_admin']) echo '<a class="nav-bottom" href="admin.php">Admin</a>'; ?>
        <a class="nav-bottom" href="export.php">CSV Export</a>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>