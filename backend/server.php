<?php
// PHP Built-in Server için özel router
// Bu dosya tüm API isteklerini doğru şekilde yönlendirir

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// API routing
if (strpos($path, '/api/v1/') === 0) {
    $apiPath = substr($path, 8); // Remove '/api/v1/'
    $parts = explode('/', $apiPath);
    $endpoint = $parts[0] ?? '';
    $subPath = implode('/', array_slice($parts, 1));
    
    // Set PATH_INFO for sub-routing
    if ($subPath) {
        $_SERVER['PATH_INFO'] = '/' . $subPath;
    }
    
    switch ($endpoint) {
        case 'sliders':
            include __DIR__ . '/api/v1/sliders/index.php';
            break;
        case 'vehicles':
            include __DIR__ . '/api/v1/vehicles/index.php';
            break;
        case 'reminders':
            include __DIR__ . '/api/v1/reminders/index.php';
            break;
        case 'news':
            include __DIR__ . '/api/v1/news/index.php';
            break;
        case 'campaigns':
            include __DIR__ . '/api/v1/campaigns/index.php';
            break;
        case 'notifications':
            include __DIR__ . '/api/v1/notifications/index.php';
            break;
        case 'auth':
            $authFile = $subPath ?: 'login';
            include __DIR__ . '/api/v1/auth/' . $authFile . '.php';
            break;
        case 'user-settings':
            include __DIR__ . '/api/v1/user-settings/index.php';
            break;
        case 'analytics':
            include __DIR__ . '/api/v1/analytics/index.php';
            break;
        case 'quotes':
            if ($subPath === 'responses') {
                include __DIR__ . '/api/v1/quotes/responses.php';
            } else {
                include __DIR__ . '/api/v1/quotes/index.php';
            }
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found', 'endpoint' => $endpoint]);
            break;
    }
} else {
    // Ana sayfa
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'message' => 'Oto Asist API',
        'version' => '1.0',
        'status' => 'running',
        'endpoints' => [
            '/api/v1/vehicles' => 'Araç işlemleri',
            '/api/v1/campaigns' => 'Kampanyalar',
            '/api/v1/news' => 'Haberler',
            '/api/v1/sliders' => 'Ana sayfa sliderları',
            '/api/v1/notifications' => 'Bildirimler',
            '/api/v1/reminders' => 'Hatırlatmalar',
            '/api/v1/quotes' => 'Teklifler',
            '/api/v1/analytics' => 'Analitik',
            '/api/v1/user-settings' => 'Kullanıcı ayarları'
        ]
    ]);
}
?> 