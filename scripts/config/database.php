<?php
// MySQL bağlantısı - Docker compose ayarlarına uygun
$mysqli = new mysqli('mysql', 'cs306user', 'cs306password', 'car_rental_db');

if ($mysqli->connect_error) {
    die('MySQL bağlantı hatası: ' . $mysqli->connect_error);
}

// Karakter setini ayarla - Türkçe karakterler için utf8mb4
$mysqli->set_charset("utf8mb4");

// Charset ayarlarını zorla
$mysqli->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
$mysqli->query("SET CHARACTER SET utf8mb4");
$mysqli->query("SET character_set_connection=utf8mb4");
$mysqli->query("SET character_set_client=utf8mb4");
$mysqli->query("SET character_set_results=utf8mb4");
?> 