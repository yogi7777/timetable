<?php
// Ändere den DB Namen, Benutzername und das Passwort.
$db = new PDO("mysql:host=localhost;dbname=yourtable", "username", "password");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);