<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=yourtable", "username", "password");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
    http_response_code(500);
    die("Datenbankfehler. Bitte später erneut versuchen.");
}