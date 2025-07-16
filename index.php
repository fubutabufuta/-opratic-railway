<?php
// OtoAsist API - Railway Routing Handler
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

// Enhanced debug logging
error_log("Index.php Handler - Path: " . $path);
error_log("Index.php Handler - Request URI: " . $request_uri);
error_log("Index.php Handler - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Index.php Handler - Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set'));

// Health check
if (empty($path) || $path === 'health') {
    $response = [
        'status' => 'success',
        'message' => 'OtoAsist API is running on Railway',
        'version' => '1.0.1',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown'
        ],
        'debug' => [
            'request_uri' => $request_uri,
            'parsed_path' => $path,
            'working_directory' => getcwd(),
            'file_exists_check' => file_exists(__DIR__ . '/backend/api/v1/campaigns/index.php')
        ]
    ];
    
    error_log("Health response: " . json_encode($response));
    echo json_encode($response);
    exit;
}

// Route mappings with file existence check
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
    $target_file = $routes[$path];
    $file_path = __DIR__ . '/' . $target_file;
    
    error_log("Checking route: " . $path . " -> " . $target_file);
    error_log("File path: " . $file_path);
    error_log("File exists: " . (file_exists($file_path) ? 'YES' : 'NO'));
    
    if (file_exists($file_path)) {
        // Set proper environment variables
        $_SERVER['SCRIPT_NAME'] = '/' . $target_file;
        $_SERVER['REQUEST_URI'] = $request_uri;
        
        error_log("Including file: " . $file_path);
        require $file_path;
        exit;
    } else {
        error_log("File not found: " . $file_path);
    }
}

// Try direct file access
$file_path = __DIR__ . '/' . $path;
if (file_exists($file_path) && is_file($file_path)) {
    if (pathinfo($file_path, PATHINFO_EXTENSION) === 'php') {
        error_log("Direct file access: " . $file_path);
        require $file_path;
        exit;
    }
}

// 404 response with detailed debugging
$available_files = [];
if (is_dir(__DIR__ . '/backend/api/v1/campaigns')) {
    $available_files['campaigns'] = scandir(__DIR__ . '/backend/api/v1/campaigns');
}

http_response_code(404);
$error_response = [
    'status' => 'error',
    'message' => 'Endpoint not found',
    'debug' => [
        'requested_path' => $path,
        'request_uri' => $request_uri,
        'method' => $_SERVER['REQUEST_METHOD'],
        'available_routes' => array_keys($routes),
        'working_directory' => getcwd(),
        'directory_contents' => $available_files,
        'file_check_results' => []
    ],
    'available_endpoints' => [
        'health' => '/health',
        'database_test' => '/backend/api/test_railway.php',
        'campaigns' => '/backend/api/v1/campaigns/',
        'vehicles' => '/backend/api/v1/vehicles/',
        'reminders' => '/backend/api/v1/reminders/',
        'news' => '/backend/api/v1/news/'
    ]
];

// Check if key files exist
foreach ($routes as $route => $file) {
    $check_path = __DIR__ . '/' . $file;
    $error_response['debug']['file_check_results'][$route] = file_exists($check_path);
}

error_log("404 Error response: " . json_encode($error_response));
echo json_encode($error_response);
?> 