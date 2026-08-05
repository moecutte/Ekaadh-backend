<?php

/**
 * PHP built-in server router that adds CORS headers for static images.
 * Use when Flutter web loads covers from artisan/php -S:
 *
 *   C:\php\php.exe -S 127.0.0.1:8000 -t public public/router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$file = __DIR__.$uri;

if ($uri !== '/' && is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $imageTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'jfif' => 'image/jpeg',
        'bmp' => 'image/bmp',
        'ico' => 'image/x-icon',
    ];

    if (isset($imageTypes[$ext])) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        header('Content-Type: '.$imageTypes[$ext]);
        header('Content-Length: '.filesize($file));

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        readfile($file);

        return true;
    }

    // Let the built-in server serve other existing files as-is.
    return false;
}

require_once __DIR__.'/index.php';
