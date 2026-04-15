<?php
require_once 'db_connect.php';

try {
    // Generate a fresh hash using the server's built-in PHP
    $newHash = password_hash('password', PASSWORD_DEFAULT);
    
    // Update all users in the database to use this new hash
    $stmt = $pdo->prepare("UPDATE users SET password = ?");
    $stmt->execute([$newHash]);

    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h1 style='color: green;'>✅ Passwords Reset Successfully!</h1>";
    echo "<p>All user passwords in the database have been securely updated to: <strong>password</strong></p>";
    echo "<p><a href='login.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #4f46e5; color: white; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<h1>Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
