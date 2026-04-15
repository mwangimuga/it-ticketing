<?php
$host = '127.0.0.1';
$db   = 'it_ticketing_system';
$user = 'root'; // Change as needed based on environment
$pass = ''; // Change as needed
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Exception on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Native prepared statements (prevent SQL injection)
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: Check db_connect.php settings.");
}
