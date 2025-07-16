<?php
// PHP Built-in Server Router for Railway
// This file handles routing for PHP built-in server

// Get the requested URI
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Log the request
error_log("Router Request: " . $path);

// Remove leading slash
$path = ltrim($path, '/');

// If it's a real file, serve it
if (!empty($path) && file_exists($path) && is_file($path)) {
    // Let PHP built-in server handle static files
    return false;
}

// For API routes, delegate to index.php
require_once 'index.php';
?> 