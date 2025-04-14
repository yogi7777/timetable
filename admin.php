<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit;
}

include 'db.php';

// Flash-Nachrichten Funktionen
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Aktion ausführen
$action = $_POST['action'] ?? '';
switch ($action) {
    // Abteilungen
    case 'add_dept':
        $name = $_POST['name'];
        $cost_center = $_POST['cost_center'];
        $stmt = $db->prepare("INSERT INTO departments (name, cost_center) VALUES (?, ?)");
        $stmt->execute([$name, $cost_center]);
        break;
    case 'edit_dept':
        $id = $_POST['id'];
        $name = $_POST['name'];
        $cost_center = $_POST['cost_center'];
        $stmt = $db->prepare("UPDATE departments SET name = ?, cost_center = ? WHERE id = ?");
        $stmt->execute([$name, $cost_center, $id]);
        break;
    case 'delete_dept':
        $id = $_POST['id'];
        $stmt = $db->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        break;

    // User
    case 'add_user':
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        $stmt = $db->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password, $is_admin]);
        break;
    case 'edit_user':
        $id = $_POST['id'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        $stmt = $db->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->execute([$is_admin, $id]);
        break;
    case 'delete_user':
        $id = $_POST['id'];
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        break;

    // Timer-Daten
    case 'add_timer':
        $user_id = $_POST['user_id'];
        $department_id = $_POST['department_id'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'] ?: null;
        $duration = $end_time ? (strtotime($end_time) - strtotime($start_time)) : null;
        $stmt = $db->prepare("INSERT INTO timers (user_id, department_id, start_time, end_time, duration) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $department_id, $start_time, $end_time, $duration]);
        $new_timer_id = $db->lastInsertId();
        setFlashMessage("Timer-Eintrag erfolgreich hinzugefügt!", "success");
        $_SESSION['new_timer_id'] = $new_timer_id;

        // Filter anpassen, um den neuen Eintrag einzuschließen
        $selected_user = $_GET['user_filter'] ?? $user_id;
        $selected_start = $_GET['start_filter'] ?? $start_time;
        $selected_end = $_GET['end_filter'] ?? ($end_time ?: date('Y-m-d H:i:s')); // Aktuelle Zeit, falls kein end_time
        
        // Sicherstellen, dass der neue Eintrag im Bereich liegt
        if (strtotime($start_time) < strtotime($selected_start)) {
            $selected_start = $start_time;
        }
        if ($end_time && strtotime($end_time) > strtotime($selected_end)) {
            $selected_end = $end_time;
        } elseif (!$end_time && strtotime($selected_end) < time()) {
            $selected_end = date('Y-m-d H:i:s');
        }

        header("Location: admin.php?section=timers&user_filter=" . urlencode($selected_user) . "&start_filter=" . urlencode($selected_start) . "&end_filter=" . urlencode($selected_end));
        exit;
    case 'edit_timer':
        $id = $_POST['id'];
        $user_id = $_POST['user_id'];
        $department_id = $_POST['department_id'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'] ?: null;
        $duration = $end_time ? (strtotime($end_time) - strtotime($start_time)) : null;
        $stmt = $db->prepare("UPDATE timers SET user_id = ?, department_id = ?, start_time = ?, end_time = ?, duration = ? WHERE id = ?");
        $stmt->execute([$user_id, $department_id, $start_time, $end_time, $duration, $id]);
        break;
    case 'delete_timer':
        $id = $_POST['id'];
        $stmt = $db->prepare("DELETE FROM timers WHERE id = ?");
        $stmt->execute([$id]);
        break;
}

// Daten abrufen
$stmt = $db->query("SELECT * FROM departments");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_user = $_GET['user_filter'] ?? '';
$selected_start = $_GET['start_filter'] ?? '';
$selected_end = $_GET['end_filter'] ?? '';
$timers = [];
if ($selected_user && $selected_start && $selected_end) {
    $stmt = $db->prepare("
        SELECT t.*, u.username, d.name AS dept_name 
        FROM timers t
        JOIN users u ON t.user_id = u.id
        JOIN departments d ON t.department_id = d.id
        WHERE t.user_id = ? 
        AND t.start_time BETWEEN ? AND ?
        ORDER BY t.start_time DESC
    ");
    $stmt->execute([$selected_user, $selected_start, $selected_end]);
    $timers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Aktueller Abschnitt
$section = $_GET['section'] ?? 'departments';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Admin Panel</h1>
        <nav>
            <a href="index.php">Zurück</a>
            <a href="?section=departments" <?php if ($section === 'departments') echo 'class="active"'; ?>>Abteilungen</a>
            <a href="?section=users" <?php if ($section === 'users') echo 'class="active"'; ?>>User</a>
            <a href="?section=timers" <?php if ($section === 'timers') echo 'class="active"'; ?>>Timer-Daten</a>
        </nav>

        <?php switch ($section): case 'departments': ?>
            <!-- Abteilungen -->
            <h2>Abteilung hinzufügen</h2>
            <form method="POST" class="dept-form">
                <input type="hidden" name="action" value="add_dept">
                <input type="text" name="name" placeholder="Abteilung" required>
                <input type="text" name="cost_center" placeholder="Kostenstelle" required>
                <button type="submit">Hinzufügen</button>
            </form>
            <h2>Bestehende Abteilungen</h2>
            <?php foreach ($departments as $dept): ?>
                <form method="POST" class="dept-form">
                    <input type="hidden" name="action" value="edit_dept">
                    <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
                    <input type="text" name="name" value="<?php echo htmlspecialchars($dept['name']); ?>" required>
                    <input type="text" name="cost_center" value="<?php echo htmlspecialchars($dept['cost_center']); ?>" required>
                    <button type="submit">Speichern</button>
                    <button type="submit" class="dept-form-delete" name="action" value="delete_dept" onclick="return confirm('Sicher?');">Löschen</button>
                </form>
            <?php endforeach; ?>
        <?php break; case 'users': ?>
            <!-- User -->
            <h2>User hinzufügen</h2>
            <form method="POST" class="dept-form">
                <input type="hidden" name="action" value="add_user">
                <input type="text" name="username" placeholder="Benutzername" required>
                <input type="password" name="password" placeholder="Passwort" required>
                <label><input type="checkbox" name="is_admin"> Admin</label>
                <button type="submit">Hinzufügen</button>
            </form>
            <h2>Bestehende User</h2>
            <?php foreach ($users as $user): ?>
                <form method="POST" class="dept-form">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <label><input type="checkbox" name="is_admin" <?php if ($user['is_admin']) echo 'checked'; ?>> Admin</label>
                    <button type="submit">Speichern</button>
                    <button type="submit" class="dept-form-delete" name="action" value="delete_user" onclick="return confirm('Sicher?');">Löschen</button>
                </form>
            <?php endforeach; ?>
        <?php break; case 'timers': ?>
            <!-- Timer-Daten -->
            <h2>Timer-Daten verwalten</h2>
            <?php $flash_message = getFlashMessage(); if ($flash_message): ?>
                <div class="alert alert-<?php echo $flash_message['type']; ?>">
                    <?php echo $flash_message['message']; ?>
                    <button type="button" class="alert-close" onclick="this.parentElement.style.display='none';">×</button>
                </div>
            <?php endif; ?>
            <form method="GET" class="dept-form">
                <input type="hidden" name="section" value="timers">
                <select name="user_filter" required>
                    <option value="">User auswählen</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php if ($selected_user == $user['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label>Startdatum:</label>
                <input type="datetime-local" name="start_filter" value="<?php echo $selected_start; ?>" required>
                <label>Enddatum:</label>
                <input type="datetime-local" name="end_filter" value="<?php echo $selected_end; ?>" required>
                <button type="submit">Filtern</button>
            </form>

            <?php if ($selected_user && $selected_start && $selected_end): ?>
                <h2>Timer hinzufügen</h2>
                <form method="POST" class="dept-form">
                    <input type="hidden" name="action" value="add_timer">
                    <select name="user_id" required>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php if ($selected_user == $user['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="department_id" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="datetime-local" name="start_time" required>
                    <input type="datetime-local" name="end_time">
                    <button type="submit">Hinzufügen</button>
                </form>

                <h2>Bestehende Timer-Einträge</h2>
                <?php foreach ($timers as $timer): ?>
                    <form method="POST" class="dept-form <?php if (isset($_SESSION['new_timer_id']) && $timer['id'] == $_SESSION['new_timer_id']) echo 'highlight'; ?>">
                        <input type="hidden" name="action" value="edit_timer">
                        <input type="hidden" name="id" value="<?php echo $timer['id']; ?>">
                        <input type="text" value="<?php echo htmlspecialchars($timer['username']); ?>" disabled>
                        <select name="department_id" required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php if ($dept['id'] == $timer['department_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="datetime-local" name="start_time" value="<?php echo date('Y-m-d\TH:i', strtotime($timer['start_time'])); ?>" required>
                        <input type="datetime-local" name="end_time" value="<?php echo $timer['end_time'] ? date('Y-m-d\TH:i', strtotime($timer['end_time'])) : ''; ?>">
                        <button type="submit">Speichern</button>
                        <button type="submit" class="dept-form-delete" name="action" value="delete_timer" onclick="return confirm('Sicher?');">Löschen</button>
                    </form>
                <?php endforeach; ?>
                <?php unset($_SESSION['new_timer_id']); ?>
            <?php endif; ?>
        <?php break; endswitch; ?>
    </div>
</body>
</html>