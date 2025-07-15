<?php
// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string from URI
$path = parse_url($requestUri, PHP_URL_PATH);

// Log the request for debugging
error_log("Router: $requestMethod $path");

// Route API requests
if (strpos($path, '/api/v1/') === 0) {
    // Remove /api/v1 prefix and add .php extension if needed
    $apiPath = substr($path, 7); // Remove '/api/v1'

    // Map routes to files
    $routes = [
        '/auth/login' => 'api/v1/auth/login.php',
        '/auth/register' => 'api/v1/auth/register.php',
        '/auth/verify' => 'api/v1/auth/verify.php',
        '/vehicles' => 'api/v1/vehicles/index.php',
        '/reminders' => 'api/v1/reminders/index.php',
    ];

    $targetFile = null;
    foreach ($routes as $route => $file) {
        if (strpos($apiPath, $route) === 0) {
            $targetFile = $file;
            break;
        }
    }

    if ($targetFile && file_exists($targetFile)) {
        error_log("Router: Serving $targetFile");
        require $targetFile;
        exit();
    } else {
        error_log("Router: File not found for path: $path");
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found', 'path' => $path]);
        exit();
    }
}

// For non-API requests, serve static files
$filePath = $_SERVER['DOCUMENT_ROOT'] . $path;
if (file_exists($filePath) && !is_dir($filePath)) {
    return false; // Let PHP serve the file
}

// Default 404
http_response_code(404);
echo json_encode(['error' => 'Not found', 'path' => $path]);
