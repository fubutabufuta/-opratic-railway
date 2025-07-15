<?php
// OtoAsist API - Railway Deployment
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request URI and method
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Parse URL and remove query parameters
$path = parse_url($request_uri, PHP_URL_PATH);
$path = ltrim($path, '/');

// If empty path, show health check
if (empty($path) || $path === 'health') {
    echo json_encode([
        'status' => 'success',
        'message' => 'OtoAsist API is running on Railway',
        'version' => '1.0.0',
        'environment' => $_ENV['RAILWAY_ENVIRONMENT'] ?? 'production',
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoints' => [
            'health' => '/health',
            'database_test' => '/backend/api/test_railway.php',
            'campaigns' => '/backend/api/v1/campaigns/',
            'vehicles' => '/backend/api/v1/vehicles/',
            'reminders' => '/backend/api/v1/reminders/',
            'news' => '/backend/api/v1/news/',
            'auth' => '/backend/api/v1/auth/'
        ]
    ]);
    exit;
}

// Route backend API requests
if (strpos($path, 'backend/api/') === 0) {
    // Remove 'backend/api/' from path
    $api_path = substr($path, 12); // Remove 'backend/api/'
    
    // Build file path
    $file_path = __DIR__ . '/backend/api/' . $api_path;
    
    // If path ends with slash, append index.php
    if (substr($api_path, -1) === '/' || empty($api_path)) {
        $file_path .= 'index.php';
    }
    
    // If no extension, try .php
    if (pathinfo($file_path, PATHINFO_EXTENSION) === '') {
        $file_path .= '.php';
    }
    
    // Check if file exists
    if (file_exists($file_path) && is_file($file_path)) {
        // Set environment for the included file
        $_SERVER['SCRIPT_NAME'] = '/backend/api/' . $api_path;
        $_SERVER['REQUEST_URI'] = $request_uri;
        
        require $file_path;
        exit;
    }
}

// Handle direct file requests
$file_path = __DIR__ . '/' . $path;
if (file_exists($file_path) && is_file($file_path)) {
    // Check if it's a PHP file
    if (pathinfo($file_path, PATHINFO_EXTENSION) === 'php') {
        require $file_path;
        exit;
    }
}

// API v1 routes
if (strpos($path, 'v1/') === 0) {
    $api_path = substr($path, 3); // Remove 'v1/'
    $file_path = __DIR__ . '/backend/api/v1/' . $api_path . '/index.php';
    
    if (file_exists($file_path)) {
        require $file_path;
        exit;
    }
}

// 404 for unknown routes
http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'Endpoint not found',
    'path' => $path,
    'method' => $method,
    'available_routes' => [
        'health' => '/health',
        'database_test' => '/backend/api/test_railway.php',
        'campaigns' => '/backend/api/v1/campaigns/',
        'vehicles' => '/backend/api/v1/vehicles/',
        'reminders' => '/backend/api/v1/reminders/',
        'news' => '/backend/api/v1/news/',
        'auth' => '/backend/api/v1/auth/'
    ]
]);
?> 