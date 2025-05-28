<?php
require_once '/var/www/html/vendor/autoload.php';

try {
    // MongoDB Atlas bağlantısı
    $mongoClient = new MongoDB\Client("mongodb+srv://admin:admin123@cluster0.fxxmwk3.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0");
    
    // Veritabanı ve koleksiyon seçimi
    $database = $mongoClient->selectDatabase('car_rental_tickets');
    $ticketsCollection = $database->selectCollection('tickets');
    
    // Bağlantı testi
    $mongoClient->selectDatabase('admin')->command(['ping' => 1]);
    
} catch (Exception $e) {
    die('MongoDB bağlantı hatası: ' . $e->getMessage());
}
?> 