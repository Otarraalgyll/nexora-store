<?php
// Optional overrides belong in environment variables, not public JavaScript.
define('APP_ENV', getenv('APP_ENV') ?: 'local');
define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/nexora-store', '/'));
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@example.com');
define('ROOT', dirname(__DIR__));
// Works both in XAMPP's /nexora-store subfolder and a PHP server's root.
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$base = dirname($script);
if (basename($base) === 'admin') $base = dirname($base);
define('BASE_URL', $base === '/' || $base === '.' ? '' : rtrim($base, '/'));
