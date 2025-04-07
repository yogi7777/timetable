<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';
$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'];

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$view = $_GET['view'] ?? 'own'; // Default: eigene Daten

$users = [];
if ($is_admin) {
    $stmt = $db->query("SELECT id, username FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Arbeitszeiten abrufen
$query = "
    SELECT 
        d.id AS dept_id,
        d.name AS department, 
        d.cost_center, 
        SUM(t.duration) AS total_seconds,
        MONTH(t.start_time) AS month,
        YEAR(t.start_time) AS year
    FROM timers t
    JOIN departments d ON t.department_id = d.id
    WHERE t.end_time IS NOT NULL
    AND MONTH(t.start_time) = ?
    AND YEAR(t.start_time) = ?
    " . ($view === 'own' ? "AND t.user_id = ?" : "") . "
    GROUP BY d.id, d.name, d.cost_center
    ORDER BY d.name
";

$stmt = $db->prepare($query);
$params = $view === 'own' ? [$month, $year, $user_id] : [$month, $year];
$stmt->execute($params);
$times = $stmt->fetchAll(PDO::FETCH_ASSOC);

$max_seconds = max(array_column($times, 'total_seconds') ?: [1]);

// Zufällige Farben für Abteilungen
$colors = [];
foreach ($times as $time) {
    if (!isset($colors[$time['dept_id']])) {
        $colors[$time['dept_id']] = sprintf('#%06X', rand(0, 0xFFFFFF));
    }
}

// Admin: Alle User-Daten (nur bei view=all)
$admin_data = [];
if ($is_admin && $view === 'all') {
    foreach ($users as $user) {
        $stmt = $db->prepare("
            SELECT 
                d.id AS dept_id,
                d.name AS department, 
                d.cost_center, 
                SUM(t.duration) AS total_seconds,
                MONTH(t.start_time) AS month,
                YEAR(t.start_time) AS year
            FROM timers t
            JOIN departments d ON t.department_id = d.id
            WHERE t.end_time IS NOT NULL
            AND t.user_id = ?
            AND MONTH(t.start_time) = ?
            AND YEAR(t.start_time) = ?
            GROUP BY d.id, d.name, d.cost_center
        ");
        $stmt->execute([$user['id'], $month, $year]);
        $user_times = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $admin_data[$user['username']] = $user_times;
        foreach ($user_times as $time) {
            if (!isset($colors[$time['dept_id']])) {
                $colors[$time['dept_id']] = sprintf('#%06X', rand(0, 0xFFFFFF));
            }
        }
    }
}

function format_time($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo htmlspecialchars($users[array_search($user_id, array_column($users, 'id'))]['username'] ?? 'User'); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Dashboard</h1>
        <nav>
            <a href="index.php">Zurück</a>
            <?php if ($is_admin) echo '<a href="admin.php">Admin</a>'; ?>
            <?php if ($is_admin): ?>
                <a href="?view=own&month=<?php echo $month; ?>&year=<?php echo $year; ?>" <?php if ($view === 'own') echo 'class="active"'; ?>>Eigene Daten</a>
                <a href="?view=all&month=<?php echo $month; ?>&year=<?php echo $year; ?>" <?php if ($view === 'all') echo 'class="active"'; ?>>Alle User</a>
            <?php endif; ?>
        </nav>

        <form method="GET" class="dept-form">
            <input type="hidden" name="view" value="<?php echo $view; ?>">
            <select name="month">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo sprintf('%02d', $m); ?>" <?php if ($month == $m) echo 'selected'; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
            <select name="year">
                <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
                    <option value="<?php echo $y; ?>" <?php if ($year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit">Filtern</button>
        </form>

        <?php switch ($view): case 'own': ?>
            <h2>Deine Arbeitszeiten (<?php echo "$month/$year"; ?>)</h2>
            <div class="chart">
                <?php if (empty($times)): ?>
                    <p>Keine Daten für diesen Zeitraum</p>
                <?php else: ?>
                    <?php foreach ($times as $time): ?>
                        <div class="bar-container">
                            <div class="bar" style="width: <?php echo ($time['total_seconds'] / $max_seconds) * 100; ?>%; background-color: <?php echo $colors[$time['dept_id']]; ?>;"></div>
                            <span class="bar-label"><?php echo htmlspecialchars($time['department']) . " (" . $time['cost_center'] . ")"; ?></span>
                            <span class="bar-time"><?php echo format_time($time['total_seconds']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php break; case 'all': if ($is_admin): ?>
            <h2>Alle User (<?php echo "$month/$year"; ?>)</h2>
            <?php foreach ($admin_data as $username => $user_times): ?>
                <h3><?php echo htmlspecialchars($username); ?></h3>
                <div class="chart">
                    <?php if (empty($user_times)): ?>
                        <p>Keine Daten für diesen Zeitraum</p>
                    <?php else: ?>
                        <?php $user_max = max(array_column($user_times, 'total_seconds') ?: [1]); ?>
                        <?php foreach ($user_times as $time): ?>
                            <div class="bar-container">
                                <div class="bar" style="width: <?php echo ($time['total_seconds'] / $user_max) * 100; ?>%; background-color: <?php echo $colors[$time['dept_id']]; ?>;"></div>
                                <span class="bar-label"><?php echo htmlspecialchars($time['department']) . " (" . $time['cost_center'] . ")"; ?></span>
                                <span class="bar-time"><?php echo format_time($time['total_seconds']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; break; endswitch; ?>
    </div>
</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when select fields change
    document.querySelectorAll('select[name="month"], select[name="year"]').forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>
</html>