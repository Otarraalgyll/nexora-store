<?php
// Development router: php -S 127.0.0.1:8080 -t . tools/router.php
// Apache uses .htaccess. PHP's development server does not, so enforce the
// same private-directory rules here. Never use the development server publicly.
$root = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (str_contains($path, "\0") || str_contains($path, '\\')) {
    http_response_code(403); exit('Forbidden');
}
$file = realpath($root . $path);
if (!$file || ($file !== $root && !str_starts_with($file, $root . DIRECTORY_SEPARATOR))) {
    http_response_code(404); exit('Not found');
}
$relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file, strlen($root)));
if (preg_match('~^/(config|includes|storage|tests|tools)(/|$)|/\.|\.(sql|md|log|ini|env|zip|json|sh)$|^/admin/_~i', $relative)
    || (str_starts_with($relative, '/uploads/') && !preg_match('~\.(jpg|jpeg|png|webp)$~i', $relative))) {
    http_response_code(403); exit('Forbidden');
}
if (is_dir($file)) {
    if (!str_ends_with($path, '/')) {
        header('Location: ' . $path . '/', true, 301); exit;
    }
    $file .= '/index.php';
    if (!is_file($file)) { http_response_code(404); exit('Not found'); }
    $_SERVER['SCRIPT_NAME'] = rtrim($path, '/') . '/index.php';
    require $file;
    return true;
}
if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
    $_SERVER['SCRIPT_NAME'] = $path;
    require $file;
    return true;
}
return false;
