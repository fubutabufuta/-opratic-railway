<?php
// Simple Router for Backend API
// Handles routing for different API endpoints

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string from URI
$requestUri = parse_url($requestUri, PHP_URL_PATH);

// Remove base path if exists
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/') {
    $requestUri = str_replace($basePath, '', $requestUri);
}

// Route definitions
$routes = [
    // Auth routes
    'POST /api/v1/auth/login' => 'api/v1/auth/login.php',
    'POST /api/v1/auth/register' => 'api/v1/auth/register.php',
    'POST /api/v1/auth/verify' => 'api/v1/auth/verify.php',
    'POST /api/v1/auth/forgot-password' => 'api/v1/auth/forgot-password.php',
    'POST /api/v1/auth/reset-password' => 'api/v1/auth/reset-password.php',
    'POST /api/v1/auth/refresh-token' => 'api/v1/auth/refresh-token.php',
    
    // Vehicle routes
    'GET /api/v1/vehicles' => 'api/v1/vehicles/index.php',
    'POST /api/v1/vehicles' => 'api/v1/vehicles/index.php',
    'PUT /api/v1/vehicles/{id}' => 'api/v1/vehicles/index.php',
    'DELETE /api/v1/vehicles/{id}' => 'api/v1/vehicles/index.php',
    
    // Reminder routes
    'GET /api/v1/reminders' => 'api/v1/reminders/index.php',
    'POST /api/v1/reminders' => 'api/v1/reminders/index.php',
    'PUT /api/v1/reminders/{id}' => 'api/v1/reminders/index.php',
    'DELETE /api/v1/reminders/{id}' => 'api/v1/reminders/index.php',
    
    // Campaign routes
    'GET /api/v1/campaigns' => 'api/v1/campaigns/index.php',
    'POST /api/v1/campaigns' => 'api/v1/campaigns/index.php',
    'PUT /api/v1/campaigns/{id}' => 'api/v1/campaigns/index.php',
    'DELETE /api/v1/campaigns/{id}' => 'api/v1/campaigns/index.php',
    
    // News routes
    'GET /api/v1/news' => 'api/v1/news/index.php',
    'GET /api/v1/news/{id}' => 'api/v1/news/index.php',
    
    // Slider routes
    'GET /api/v1/sliders' => 'api/v1/sliders/index.php',
    
    // Quote routes
    'GET /api/v1/quotes' => 'api/v1/quotes/index.php',
    'POST /api/v1/quotes' => 'api/v1/quotes/index.php',
    
    // Notification routes
    'GET /api/v1/notifications' => 'api/v1/notifications/index.php',
    'POST /api/v1/notifications' => 'api/v1/notifications/index.php',
];

// Find matching route
$matchedRoute = null;
$routeParams = [];

foreach ($routes as $route => $file) {
    list($method, $pattern) = explode(' ', $route, 2);
    
    if ($method !== $requestMethod) {
        continue;
    }
    
    // Convert route pattern to regex
    $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
    $regex = '#^' . $regex . '$#';
    
    if (preg_match($regex, $requestUri, $matches)) {
        $matchedRoute = $file;
        
        // Extract parameters
        preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
        for ($i = 1; $i < count($matches); $i++) {
            $paramName = $paramNames[1][$i - 1];
            $routeParams[$paramName] = $matches[$i];
        }
        break;
    }
}

// Handle the route
if ($matchedRoute) {
    // Add route parameters to $_GET
    foreach ($routeParams as $key => $value) {
        $_GET[$key] = $value;
    }
    
    $filePath = __DIR__ . '/' . $matchedRoute;
    
    if (file_exists($filePath)) {
        include $filePath;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found', 'file' => $matchedRoute]);
    }
} else {
    // Try fallback to index.php with endpoint parameter
    if (isset($_GET['endpoint'])) {
        $endpoint = $_GET['endpoint'];
        $fallbackFile = __DIR__ . '/index.php';
        
        if (file_exists($fallbackFile)) {
            include $fallbackFile;
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API not found']);
        }
    } else {
        http_response_code(404);
        echo json_encode([
            'error' => 'API endpoint not found',
            'uri' => $requestUri,
            'method' => $requestMethod,
            'available_routes' => array_keys($routes)
        ]);
    }
}
?>
