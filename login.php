<?php
// Zugriff nur für Menschen erlauben und Bots blockieren
if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|spider|slurp|mediapartners|adsbot/i', $_SERVER['HTTP_USER_AGENT'])) {
    http_response_code(403);
    die("Zugriff verweigert");
}

// Robots abwehren
header("X-Robots-Tag: noindex, nofollow, noarchive", true);

session_start();
session_regenerate_id(true);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

include 'db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Ungültiges CSRF-Token");
    }

    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Bitte alle Felder ausfüllen";
    } else {
        try {
            $stmt = $db->prepare("SELECT id, password, is_admin FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                $_SESSION['last_activity'] = time();
                header("Location: index.php");
                exit;
            } else {
                $error = "Falscher Benutzername oder Passwort";
                sleep(1);
            }
        } catch (PDOException $e) {
            error_log("Datenbankfehler: " . $e->getMessage());
            $error = "Ein Fehler ist aufgetreten. Bitte später erneut versuchen.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- Suchmaschinen und Bots verbieten -->
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="text" 
                   name="username" 
                   placeholder="Benutzername" 
                   required 
                   maxlength="50"
                   autocomplete="username">
            <input type="password" 
                   name="password" 
                   placeholder="Passwort" 
                   required
                   maxlength="100"
                   autocomplete="current-password">
            <button type="submit">Einloggen</button>
        </form>
    </div>
</body>
</html>
