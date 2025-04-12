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
require_once 'auth.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Ungültiges CSRF-Token";
    } else {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        $device_name = filter_input(INPUT_POST, 'device_name', FILTER_SANITIZE_STRING) ?? '';

        if (empty($username) || empty($password)) {
            $error = "Bitte alle Felder ausfüllen";
        } elseif (login($username, $password, $remember, $device_name)) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Falscher Benutzername oder Passwort";
            sleep(1); // Brute-Force-Schutz
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
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Angemeldet bleiben</label>
            </div>
            <div class="form-group" id="device_name_group" style="display: none;">
                <input type="text" 
                       name="device_name" 
                       id="device_name" 
                       placeholder="Gerätename (z. B. Mein Laptop)" 
                       maxlength="100"
                       class="form-control">
            </div>
            <button type="submit">Einloggen</button>
        </form>
    </div>
    <script>
        // Gerätename-Feld ein-/ausblenden
        document.getElementById('remember').addEventListener('change', function() {
            document.getElementById('device_name_group').style.display = this.checked ? 'block' : 'none';
        });
    </script>
</body>
</html>