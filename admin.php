<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit;
}

include 'db.php';

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
}

// Daten abrufen
$stmt = $db->query("SELECT * FROM departments");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Aktueller Abschnitt
$section = $_GET['section'] ?? 'departments';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
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
        <?php break; endswitch; ?>
    </div>
</body>
</html>