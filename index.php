<?php
// OtoAsist API - Railway Simple Routing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request path
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);
$path = ltrim($path, '/');

// Debug info
error_log("Railway Request: " . $path);

// Health check
if (empty($path) || $path === 'health') {
    echo json_encode([
        'status' => 'success',
        'message' => 'OtoAsist API is running on Railway',
        'version' => '1.0.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'debug' => [
            'request_uri' => $request_uri,
            'path' => $path,
            'method' => $_SERVER['REQUEST_METHOD'],
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown'
        ]
    ]);
    exit;
}

// Route mappings
$routes = [
    'backend/api/test_railway.php' => 'backend/api/test_railway.php',
    'backend/api/v1/campaigns/' => 'backend/api/v1/campaigns/index.php',
    'backend/api/v1/campaigns' => 'backend/api/v1/campaigns/index.php',
    'backend/api/v1/vehicles/' => 'backend/api/v1/vehicles/index.php',
    'backend/api/v1/vehicles' => 'backend/api/v1/vehicles/index.php',
    'backend/api/v1/reminders/' => 'backend/api/v1/reminders/index.php',
    'backend/api/v1/reminders' => 'backend/api/v1/reminders/index.php',
    'backend/api/v1/news/' => 'backend/api/v1/news/index.php',
    'backend/api/v1/news' => 'backend/api/v1/news/index.php',
    'backend/api/v1/auth/login' => 'backend/api/v1/auth/login.php',
    'backend/api/v1/auth/register' => 'backend/api/v1/auth/register.php',
];

// Check if route exists
if (isset($routes[$path])) {
    $file_path = __DIR__ . '/' . $routes[$path];
    
    if (file_exists($file_path)) {
        // Set proper environment
        $_SERVER['SCRIPT_NAME'] = '/' . $routes[$path];
        $_SERVER['REQUEST_URI'] = $request_uri;
        
        require $file_path;
        exit;
    }
}

// Try direct file access
$file_path = __DIR__ . '/' . $path;
if (file_exists($file_path) && is_file($file_path)) {
    if (pathinfo($file_path, PATHINFO_EXTENSION) === 'php') {
        require $file_path;
        exit;
    }
}

// 404 response
http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'Endpoint not found',
    'debug' => [
        'requested_path' => $path,
        'request_uri' => $request_uri,
        'method' => $_SERVER['REQUEST_METHOD'],
        'available_routes' => array_keys($routes)
    ],
    'available_endpoints' => [
        'health' => '/health',
        'database_test' => '/backend/api/test_railway.php',
        'campaigns' => '/backend/api/v1/campaigns/',
        'vehicles' => '/backend/api/v1/vehicles/',
        'reminders' => '/backend/api/v1/reminders/',
        'news' => '/backend/api/v1/news/'
    ]
]);
?> 