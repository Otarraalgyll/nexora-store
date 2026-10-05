<?php
// XAMPP defaults. For a hosted server set environment variables instead.
// Keep this file PHP-only so credentials are never rendered as page content.
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $local = is_file(__DIR__.'/local.php') ? require __DIR__.'/local.php' : [];
        $host = $local['host'] ?? getenv('DB_HOST') ?: 'localhost';
        $name = $local['name'] ?? getenv('DB_NAME') ?: 'ecommerce_db';
        $user = $local['user'] ?? getenv('DB_USER') ?: 'root';
        $pass = $local['password'] ?? getenv('DB_PASS') ?: '';
        $port = $local['port'] ?? getenv('DB_PORT') ?: '3306';
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
