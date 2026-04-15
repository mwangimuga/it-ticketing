<?php
// Detect environment: Local (XAMPP) vs Production (Railway)
$is_localhost = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1');

if ($is_localhost) {
    // --- XAMPP SETTINGS ---
    $host   = "localhost";
    $port   = "3306";
    $user   = "root";
    $pass   = ""; // Default XAMPP has no password
    $dbname = "it_ticketing_system";
} else {
    // --- RAILWAY SETTINGS (From your string) ---
    $host   = "monorail.proxy.rlwy.net";
    $port   = "26450";
    $user   = "root";
    $pass   = "hhTEhKFHKzBydYGLsiqTPvMCqbzXcnZa";
    $dbname = "railway";
}

echo "Attempting to connect to: " . ($is_localhost ? "LOCAL XAMPP" : "REMOTE RAILWAY") . "<br>";
echo "Host: " . $host . " Port: " . $port;

try {
    // Data Source Name (DSN)
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    // Create PDO connection
    $pdo = new PDO($dsn, $user, $pass);
    
    // Configuration for Error Handling and Fetching
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // In a real enterprise app, you'd log this to a file, not just echo it
    die("Database Connection Error: " . $e->getMessage());
}
