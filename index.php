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

// Basic routing
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove leading slash and split path
$path = ltrim($path, '/');
$segments = explode('/', $path);

// Health check endpoint
if (empty($path) || $path === 'health') {
    echo json_encode([
        'status' => 'success',
        'message' => 'OtoAsist API is running on Railway',
        'version' => '1.0.0',
        'environment' => $_ENV['RAILWAY_ENVIRONMENT'] ?? 'production',
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoints' => [
            'health' => '/health',
            'api' => '/backend/api/',
            'database_test' => '/backend/api/test_db.php',
            'railway_test' => '/backend/api/test_railway.php'
        ]
    ]);
    exit;
}

// Route API requests to backend
if ($segments[0] === 'backend' || $segments[0] === 'api') {
    $backend_path = __DIR__ . '/backend/api/index.php';
    if (file_exists($backend_path)) {
        require $backend_path;
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'API endpoint not found'
        ]);
    }
    exit;
}

// Default 404 for unknown routes
http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'Endpoint not found',
    'path' => $path,
    'available_routes' => [
        'health' => '/health',
        'api' => '/backend/api/*'
    ]
]);
?> 