<?php
// koneksi.php
$host = "localhost";
$username = "root";
$password = ""; // Sesuaikan dengan password database Anda
$database = "inventory_warmindo";

try {
    // Menggunakan PDO untuk keamanan ekstra (Anti SQL Injection)
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    
    // Mengatur mode error PDO ke Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan error
    die("Koneksi database gagal: " . $e->getMessage());
}
?>