<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

echo json_encode([
    'status' => 'success',
    'message' => 'OtoAsist Backend API is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server' => $_SERVER['SERVER_NAME'],
    'port' => $_SERVER['SERVER_PORT']
]);
?> 