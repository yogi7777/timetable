<?php
session_start();

// Funktion zur Generierung eines sicheren Tokens
function generateToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

// Funktion zur Prüfung der Authentifizierung
function checkAuth() {
    global $db;

    // Bot-Schutz
    if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|spider|slurp|mediapartners|adsbot/i', $_SERVER['HTTP_USER_AGENT'])) {
        http_response_code(403);
        die("Zugriff verweigert");
    }

    // Session prüfen
    if (isset($_SESSION['user_id'])) {
        $_SESSION['last_activity'] = time();
        return;
    }

    // Prüfe Remember-Token
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        try {
            $stmt = $db->prepare("SELECT user_id, token_hash, expires_at FROM remember_tokens WHERE token_hash = ?");
            $stmt->execute([hash('sha256', $token)]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tokenData && hash_equals($tokenData['token_hash'], hash('sha256', $token)) && new DateTime() < new DateTime($tokenData['expires_at'])) {
                // Session wiederherstellen
                $_SESSION['user_id'] = $tokenData['user_id'];
                $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
                $stmt->execute([$tokenData['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                $_SESSION['last_activity'] = time();
                return;
            }

            // Ungültiges Token: Cookie löschen
            if (!headers_sent()) {
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }
        } catch (PDOException $e) {
            error_log("Datenbankfehler in checkAuth: " . $e->getMessage());
        }
    }

    // Nicht authentifiziert
    if (!headers_sent()) {
        header('Location: login.php');
        exit;
    }
}

// Funktion zum Login
function login($username, $password, $remember = false, $device_name = '') {
    global $db;

    try {
        $stmt = $db->prepare("SELECT id, password, is_admin FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['last_activity'] = time();

            if ($remember) {
                $token = generateToken();
                $tokenHash = hash('sha256', $token);
                $expiresAt = (new DateTime())->modify('+1 year')->format('Y-m-d H:i:s');
                $deviceName = trim($device_name) ?: null;

                $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at, device_name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], $tokenHash, $expiresAt, $deviceName]);

                $cookieOptions = [
                    'expires' => time() + 31536000,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ];
                if (!headers_sent()) {
                    setcookie('remember_token', $token, $cookieOptions);
                }
            }

            return true;
        }
    } catch (PDOException $e) {
        error_log("Datenbankfehler in login: " . $e->getMessage());
    }

    return false;
}

// Funktion zum Logout
function logout() {
    global $db;

    try {
        // Cookie und Token löschen
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $db->prepare("DELETE FROM remember_tokens WHERE token_hash = ?");
            $stmt->execute([hash('sha256', $_COOKIE['remember_token'])]);

            if (!headers_sent()) {
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }
        }
    } catch (PDOException $e) {
        error_log("Datenbankfehler in logout: " . $e->getMessage());
    }

    // Session beenden
    session_unset();
    session_destroy();

    if (!headers_sent()) {
        header('Location: login.php');
        exit;
    } else {
        echo '<meta http-equiv="refresh" content="0;url=login.php">';
        exit;
    }
}